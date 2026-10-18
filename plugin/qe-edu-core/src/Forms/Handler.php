<?php
/**
 * AJAX handlers for the registration and newsletter forms.
 *
 * Security: nonce, honeypot, per-IP rate limit, server-side configuration
 * lookup (fields, recipient and messages come from the saved widget, never
 * from the request), strict validation, sanitised storage. Passwords are only
 * used to create an account and are never stored or emailed.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Forms;

use QeEdu\Core\PostTypes\Leads;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Handler.
 */
final class Handler {

	/**
	 * Nonce action shared by both forms.
	 */
	const NONCE = 'qe_edu_form';

	/**
	 * Widgets that contain the registration form.
	 */
	const REGISTRATION_WIDGETS = array( 'qe-registration-form', 'qe-benefits-form' );

	/**
	 * Widgets that contain the newsletter form.
	 */
	const NEWSLETTER_WIDGETS = array( 'qe-newsletter' );

	/**
	 * Maximum length of any submitted value.
	 */
	const MAX_LENGTH = 500;

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_qe_edu_register', array( $this, 'registration' ) );
		add_action( 'wp_ajax_nopriv_qe_edu_register', array( $this, 'registration' ) );
		add_action( 'wp_ajax_qe_edu_subscribe', array( $this, 'newsletter' ) );
		add_action( 'wp_ajax_nopriv_qe_edu_subscribe', array( $this, 'newsletter' ) );
	}

	/**
	 * A POSTed scalar, unslashed and trimmed (sanitised by the caller).
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private static function post( $key ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked in common_checks(); callers sanitise.
		return isset( $_POST[ $key ] ) && is_scalar( $_POST[ $key ] ) ? trim( (string) wp_unslash( $_POST[ $key ] ) ) : '';
	}

	/**
	 * Nonce, honeypot and rate limit. Sends the response itself on failure.
	 *
	 * @param string              $action   Rate-limit bucket.
	 * @param array<string,mixed> $messages Messages (error_message, rate_message).
	 * @param string              $success  Success message for honeypot hits.
	 * @return bool Whether processing may continue.
	 */
	private function common_checks( $action, $messages, $success ) {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => $messages['error_message'] ), 403 );
			return false;
		}

		// Bots fill the hidden "website" field; pretend success and store nothing.
		if ( '' !== self::post( 'website' ) ) {
			wp_send_json_success( array( 'message' => $success ) );
			return false;
		}

		if ( ! RateLimiter::attempt( $action ) ) {
			wp_send_json_error( array( 'message' => $messages['rate_message'] ), 429 );
			return false;
		}

		return true;
	}

	/**
	 * Whether visitors may create accounts with this form.
	 *
	 * @param array<string,mixed> $config Widget settings.
	 * @return bool
	 */
	public static function can_create_account( $config ) {
		return 'yes' === $config['create_account'] && (bool) get_option( 'users_can_register' );
	}

	/**
	 * Fields that are active for a configuration (password fields only when an
	 * account can be created).
	 *
	 * @param array<string,mixed> $config Widget settings.
	 * @return array<int,array<string,string>>
	 */
	public static function active_fields( $config ) {
		$account = self::can_create_account( $config );

		$defaults = array(
			'_id'         => '',
			'label'       => '',
			'type'        => 'text',
			'required'    => '',
			'placeholder' => '',
			'options'     => '',
			'width'       => 'full',
			'step'        => '1',
		);

		$fields = array();
		foreach ( is_array( $config['fields'] ) ? $config['fields'] : array() as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$field = array_merge( $defaults, $field );
			if ( '' !== (string) $field['_id'] && ( 'password' !== $field['type'] || $account ) ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Validate one value. Returns the clean value, or null when invalid.
	 *
	 * @param array<string,string> $field  Field definition.
	 * @param string               $value  Raw value.
	 * @param array<string,mixed>  $config Widget settings.
	 * @return string|null
	 */
	public static function validate( $field, $value, $config ) {
		if ( strlen( $value ) > self::MAX_LENGTH ) {
			return null;
		}

		switch ( $field['type'] ) {
			case 'email':
				$value = sanitize_email( $value );
				return is_email( $value ) ? $value : null;

			case 'tel':
				return preg_match( '/^[0-9+()\-.\s]{6,25}$/', $value ) ? $value : null;

			case 'date':
				$date = \DateTime::createFromFormat( '!Y-m-d', $value );
				return $date && $date->format( 'Y-m-d' ) === $value ? $value : null;

			case 'country':
				return in_array( $value, Countries::parse( $config['countries'] ), true ) ? $value : null;

			case 'select':
				return in_array( $value, Countries::parse( $field['options'] ), true ) ? $value : null;

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'password':
				return strlen( $value ) >= 8 ? $value : null;

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Registration form submission.
	 *
	 * @return void
	 */
	public function registration() {
		$defaults = Defaults::registration();

		if ( ! $this->common_checks( 'register', $defaults, $defaults['success_message'] ) ) {
			return;
		}

		$config = Locator::find( absint( self::post( 'post_id' ) ), sanitize_key( self::post( 'form_id' ) ), self::REGISTRATION_WIDGETS, $defaults );

		if ( null === $config ) {
			wp_send_json_error( array( 'message' => $defaults['error_message'] ), 400 );
			return;
		}

		$values   = array();
		$errors   = array();
		$email    = '';
		$password = '';

		foreach ( self::active_fields( $config ) as $field ) {
			$raw = self::post( 'qe_' . $field['_id'] );

			if ( '' === $raw ) {
				if ( 'yes' === $field['required'] ) {
					$errors[ $field['_id'] ] = $config['required_message'];
				}
				continue;
			}

			$clean = self::validate( $field, $raw, $config );
			if ( null === $clean ) {
				$errors[ $field['_id'] ] = $config['invalid_message'];
				continue;
			}

			if ( 'password' === $field['type'] ) {
				$password = $clean;
				continue;
			}
			if ( 'email' === $field['type'] && '' === $email ) {
				$email = $clean;
			}
			$values[ $field['label'] ] = $clean;
		}

		if ( '' !== trim( (string) $config['consent_text'] ) && '1' !== self::post( 'qe_consent' ) ) {
			$errors['consent'] = $config['required_message'];
		}

		if ( $errors ) {
			wp_send_json_error(
				array(
					'message' => $config['invalid_message'],
					'errors'  => $errors,
				),
				422
			);
			return;
		}

		if ( '' !== $password && '' !== $email ) {
			if ( email_exists( $email ) ) {
				wp_send_json_error( array( 'message' => $config['account_exists_message'] ), 409 );
				return;
			}
			$user_id = wp_insert_user(
				array(
					'user_login' => $email,
					'user_email' => $email,
					'user_pass'  => $password,
					'role'       => get_option( 'default_role', 'subscriber' ),
				)
			);
			if ( is_wp_error( $user_id ) ) {
				wp_send_json_error( array( 'message' => $config['error_message'] ), 500 );
				return;
			}
		}

		$source = esc_url_raw( (string) wp_get_referer() );
		Leads::create( 'registration', $values, $email, $source );
		self::notify( $config, $values, $email, $source );

		wp_send_json_success( array( 'message' => $config['success_message'] ) );
	}

	/**
	 * Email the site owner about a registration.
	 *
	 * @param array<string,mixed>  $config Widget settings.
	 * @param array<string,string> $values Submitted values (label => value).
	 * @param string               $email  Visitor email (Reply-To).
	 * @param string               $source Page URL.
	 * @return bool
	 */
	public static function notify( $config, $values, $email, $source ) {
		$to = sanitize_email( (string) $config['recipient'] );
		$to = '' !== $to ? $to : sanitize_email( (string) Settings::get( 'contact.email' ) );
		$to = '' !== $to ? $to : (string) get_option( 'admin_email' );

		$body = '';
		foreach ( $values as $label => $value ) {
			$body .= $label . ': ' . $value . "\n";
		}
		$body .= "\n" . $source . "\n";

		$headers = '' !== $email ? array( 'Reply-To: ' . $email ) : array();

		return (bool) wp_mail( $to, $config['email_subject'] . ' — ' . get_bloginfo( 'name' ), $body, $headers );
	}

	/**
	 * Newsletter submission.
	 *
	 * @return void
	 */
	public function newsletter() {
		$defaults = Defaults::newsletter();

		if ( ! $this->common_checks( 'subscribe', $defaults, $defaults['success_message'] ) ) {
			return;
		}

		$config = Locator::find( absint( self::post( 'post_id' ) ), sanitize_key( self::post( 'form_id' ) ), self::NEWSLETTER_WIDGETS, $defaults );

		if ( null === $config ) {
			wp_send_json_error( array( 'message' => $defaults['error_message'] ), 400 );
			return;
		}

		$email = sanitize_email( self::post( 'qe_email' ) );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => $config['invalid_message'] ), 422 );
			return;
		}

		$key  = (string) Settings::get( 'footer.mailchimp_key' );
		$list = (string) Settings::get( 'footer.mailchimp_list' );

		if ( 'mailchimp' === $config['provider'] && '' !== $key && ! Mailchimp::subscribe( $key, $list, $email ) ) {
			wp_send_json_error( array( 'message' => $config['error_message'] ), 502 );
			return;
		}

		Leads::create( 'newsletter', array( __( 'Email', 'qe-edu-core' ) => $email ), $email, esc_url_raw( (string) wp_get_referer() ) );

		wp_send_json_success( array( 'message' => $config['success_message'] ) );
	}
}
