<?php
/**
 * Default settings of the form widgets. Used both as the Elementor control
 * defaults and by the server when it validates a submission (Elementor only
 * stores values the user changed).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Defaults.
 */
final class Defaults {

	/**
	 * Field types offered in the registration form.
	 *
	 * @return array<string,string>
	 */
	public static function field_types() {
		return array(
			'text'     => __( 'Text', 'qe-edu' ),
			'email'    => __( 'Email', 'qe-edu' ),
			'tel'      => __( 'Phone', 'qe-edu' ),
			'date'     => __( 'Date', 'qe-edu' ),
			'country'  => __( 'Country list', 'qe-edu' ),
			'select'   => __( 'Drop-down', 'qe-edu' ),
			'textarea' => __( 'Long text', 'qe-edu' ),
			'password' => __( 'Password (only used to create an account)', 'qe-edu' ),
		);
	}

	/**
	 * One field definition.
	 *
	 * @param string $id          Stable id.
	 * @param string $label       Label.
	 * @param string $type        Type.
	 * @param string $width       full|half.
	 * @param string $step        1|2.
	 * @param string $placeholder Placeholder.
	 * @return array<string,string>
	 */
	private static function field( $id, $label, $type, $width, $step, $placeholder = '' ) {
		return array(
			'_id'         => $id,
			'label'       => $label,
			'type'        => $type,
			'required'    => 'yes',
			'placeholder' => $placeholder,
			'options'     => '',
			'width'       => $width,
			'step'        => $step,
		);
	}

	/**
	 * Registration form defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function registration() {
		return array(
			'fields'                 => array(
				self::field( 'first_name', __( 'First name', 'qe-edu' ), 'text', 'half', '1' ),
				self::field( 'last_name', __( 'Last name', 'qe-edu' ), 'text', 'half', '1' ),
				self::field( 'email', __( 'Email address', 'qe-edu' ), 'email', 'full', '1', __( 'you@example.com', 'qe-edu' ) ),
				self::field( 'phone', __( 'Phone number', 'qe-edu' ), 'tel', 'full', '1', __( '+971 50 000 0000', 'qe-edu' ) ),
				self::field( 'dob', __( 'Date of birth', 'qe-edu' ), 'date', 'half', '2' ),
				self::field( 'country', __( 'Home country', 'qe-edu' ), 'country', 'half', '2' ),
				self::field( 'password', __( 'Create a password', 'qe-edu' ), 'password', 'full', '2' ),
			),
			'countries'              => Countries::as_text(),
			'multi_step'             => 'yes',
			'step_1_title'           => __( 'Your details', 'qe-edu' ),
			'step_2_title'           => __( 'About you', 'qe-edu' ),
			'next_label'             => __( 'Next', 'qe-edu' ),
			'back_label'             => __( 'Back', 'qe-edu' ),
			'submit_label'           => __( 'Sign Up', 'qe-edu' ),
			'sending_label'          => __( 'Sending…', 'qe-edu' ),
			'select_placeholder'     => __( 'Please choose…', 'qe-edu' ),
			'consent_text'           => __( 'I agree to be contacted about my application and accept the privacy policy.', 'qe-edu' ),
			'create_account'         => 'yes',
			'recipient'              => '',
			'email_subject'          => __( 'New student registration', 'qe-edu' ),
			'success_message'        => __( 'Thank you! Your registration was received. Our advisors will contact you shortly.', 'qe-edu' ),
			'error_message'          => __( 'Sorry, something went wrong. Please try again.', 'qe-edu' ),
			'required_message'       => __( 'Please fill in this field.', 'qe-edu' ),
			'invalid_message'        => __( 'Please check this value.', 'qe-edu' ),
			'rate_message'           => __( 'Too many attempts. Please wait a few minutes and try again.', 'qe-edu' ),
			'account_exists_message' => __( 'An account with this email already exists. Please log in instead.', 'qe-edu' ),
		);
	}

	/**
	 * Newsletter defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function newsletter() {
		return array(
			'heading'         => __( 'Subscribe to our newsletter', 'qe-edu' ),
			'text'            => __( 'Study tips, scholarships and events — once a month, no spam.', 'qe-edu' ),
			'placeholder'     => __( 'Your email address', 'qe-edu' ),
			'button'          => __( 'Subscribe', 'qe-edu' ),
			'provider'        => 'builtin',
			'shortcode'       => '',
			'success_message' => __( 'Thanks for subscribing!', 'qe-edu' ),
			'invalid_message' => __( 'Please enter a valid email address.', 'qe-edu' ),
			'error_message'   => __( 'Sorry, something went wrong. Please try again.', 'qe-edu' ),
			'rate_message'    => __( 'Too many attempts. Please wait a few minutes and try again.', 'qe-edu' ),
		);
	}
}
