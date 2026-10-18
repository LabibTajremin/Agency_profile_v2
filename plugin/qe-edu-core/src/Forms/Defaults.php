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
			'text'     => __( 'Text', 'qe-edu-core' ),
			'email'    => __( 'Email', 'qe-edu-core' ),
			'tel'      => __( 'Phone', 'qe-edu-core' ),
			'date'     => __( 'Date', 'qe-edu-core' ),
			'country'  => __( 'Country list', 'qe-edu-core' ),
			'select'   => __( 'Drop-down', 'qe-edu-core' ),
			'textarea' => __( 'Long text', 'qe-edu-core' ),
			'password' => __( 'Password (only used to create an account)', 'qe-edu-core' ),
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
				self::field( 'first_name', __( 'First name', 'qe-edu-core' ), 'text', 'half', '1' ),
				self::field( 'last_name', __( 'Last name', 'qe-edu-core' ), 'text', 'half', '1' ),
				self::field( 'email', __( 'Email address', 'qe-edu-core' ), 'email', 'full', '1', __( 'you@example.com', 'qe-edu-core' ) ),
				self::field( 'phone', __( 'Phone number', 'qe-edu-core' ), 'tel', 'full', '1', __( '+971 50 000 0000', 'qe-edu-core' ) ),
				self::field( 'dob', __( 'Date of birth', 'qe-edu-core' ), 'date', 'half', '2' ),
				self::field( 'country', __( 'Home country', 'qe-edu-core' ), 'country', 'half', '2' ),
				self::field( 'password', __( 'Create a password', 'qe-edu-core' ), 'password', 'full', '2' ),
			),
			'countries'              => Countries::as_text(),
			'multi_step'             => 'yes',
			'step_1_title'           => __( 'Your details', 'qe-edu-core' ),
			'step_2_title'           => __( 'About you', 'qe-edu-core' ),
			'next_label'             => __( 'Next', 'qe-edu-core' ),
			'back_label'             => __( 'Back', 'qe-edu-core' ),
			'submit_label'           => __( 'Sign Up', 'qe-edu-core' ),
			'sending_label'          => __( 'Sending…', 'qe-edu-core' ),
			'select_placeholder'     => __( 'Please choose…', 'qe-edu-core' ),
			'consent_text'           => __( 'I agree to be contacted about my application and accept the privacy policy.', 'qe-edu-core' ),
			'create_account'         => 'yes',
			'recipient'              => '',
			'email_subject'          => __( 'New student registration', 'qe-edu-core' ),
			'success_message'        => __( 'Thank you! Your registration was received. Our advisors will contact you shortly.', 'qe-edu-core' ),
			'error_message'          => __( 'Sorry, something went wrong. Please try again.', 'qe-edu-core' ),
			'required_message'       => __( 'Please fill in this field.', 'qe-edu-core' ),
			'invalid_message'        => __( 'Please check this value.', 'qe-edu-core' ),
			'rate_message'           => __( 'Too many attempts. Please wait a few minutes and try again.', 'qe-edu-core' ),
			'account_exists_message' => __( 'An account with this email already exists. Please log in instead.', 'qe-edu-core' ),
		);
	}

	/**
	 * Newsletter defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function newsletter() {
		return array(
			'heading'         => __( 'Subscribe to our newsletter', 'qe-edu-core' ),
			'text'            => __( 'Study tips, scholarships and events — once a month, no spam.', 'qe-edu-core' ),
			'placeholder'     => __( 'Your email address', 'qe-edu-core' ),
			'button'          => __( 'Subscribe', 'qe-edu-core' ),
			'provider'        => 'builtin',
			'shortcode'       => '',
			'success_message' => __( 'Thanks for subscribing!', 'qe-edu-core' ),
			'invalid_message' => __( 'Please enter a valid email address.', 'qe-edu-core' ),
			'error_message'   => __( 'Sorry, something went wrong. Please try again.', 'qe-edu-core' ),
			'rate_message'    => __( 'Too many attempts. Please wait a few minutes and try again.', 'qe-edu-core' ),
		);
	}
}
