<?php
/**
 * Newsletter: email sign-up stored as a lead (built-in list), sent to
 * Mailchimp, or replaced by another plugin's shortcode.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use QeEdu\Core\Forms\Defaults;
use QeEdu\Core\Forms\Handler;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Newsletter.
 */
class Newsletter extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-newsletter';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Newsletter', 'qe-edu-core' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-mailchimp';
	}

	/**
	 * Script.
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return $this->behaviour_script_depends();
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$d = Defaults::newsletter();

		$this->start_controls_section( 'section_content', array( 'label' => __( 'Newsletter', 'qe-edu-core' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu-core' ), $d['heading'] );
		$this->add_textarea( 'text', __( 'Text', 'qe-edu-core' ), $d['text'] );
		$this->add_select(
			'provider',
			__( 'Send sign-ups to', 'qe-edu-core' ),
			array(
				'builtin'   => __( 'Leads list on this website', 'qe-edu-core' ),
				'mailchimp' => __( 'Mailchimp (set the key in Theme Settings → Footer)', 'qe-edu-core' ),
				'shortcode' => __( 'Another plugin (shortcode)', 'qe-edu-core' ),
			),
			$d['provider']
		);
		$this->add_text( 'shortcode', __( 'Form shortcode', 'qe-edu-core' ), $d['shortcode'], array( 'condition' => array( 'provider' => 'shortcode' ) ) );
		$this->add_text( 'placeholder', __( 'Hint inside the email box', 'qe-edu-core' ), $d['placeholder'], array( 'condition' => array( 'provider!' => 'shortcode' ) ) );
		$this->add_text( 'button', __( 'Button text', 'qe-edu-core' ), $d['button'], array( 'condition' => array( 'provider!' => 'shortcode' ) ) );
		$this->add_text( 'success_message', __( 'Thank-you message', 'qe-edu-core' ), $d['success_message'], array( 'qe_scope' => 'server' ) );
		$this->add_text( 'invalid_message', __( 'Wrong email message', 'qe-edu-core' ), $d['invalid_message'], array( 'condition' => array( 'provider!' => 'shortcode' ) ) );
		$this->add_text( 'error_message', __( 'Error message', 'qe-edu-core' ), $d['error_message'], array( 'qe_scope' => 'server' ) );
		$this->add_text( 'rate_message', __( 'Too many attempts', 'qe-edu-core' ), $d['rate_message'], array( 'qe_scope' => 'server' ) );
		$this->add_text( 'sending_label', __( 'Button while sending', 'qe-edu-core' ), __( 'Sending…', 'qe-edu-core' ), array( 'condition' => array( 'provider!' => 'shortcode' ) ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu-core' ), '.qe-newsletter__heading', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'text', __( 'Text', 'qe-edu-core' ), '.qe-newsletter__text', self::COLOR_TEXT, self::FONT_TEXT );
		$this->add_button_style( '.qe-newsletter .qe-btn' );
		$this->end_controls_section();
		$this->add_box_style(
			'.qe-newsletter',
			array(
				'label' => __( 'Box', 'qe-edu-core' ),
				'align' => '.qe-newsletter',
			)
		);
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		if ( ! Settings::get( 'footer.newsletter' ) ) {
			$this->print_editor_notice( __( 'The newsletter is switched off in Theme Settings → Footer.', 'qe-edu-core' ) );
			return;
		}

		$id = 'qe-nl-' . $this->get_id();

		echo '<div class="qe-newsletter">';
		$this->print_heading( $s['heading'], 'h2', 'qe-newsletter__heading' );
		$this->print_text( $s['text'], 'qe-newsletter__text' );

		if ( 'shortcode' === $s['provider'] ) {
			echo do_shortcode( (string) $s['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
			echo '</div>';
			return;
		}

		printf(
			'<form class="qe-newsletter__form" method="post" action="%1$s" novalidate data-qe-form="subscribe" data-invalid="%2$s" data-required="%2$s" data-sending="%3$s">',
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_attr( $s['invalid_message'] ),
			esc_attr( $s['sending_label'] )
		);
		echo '<input type="hidden" name="action" value="qe_edu_subscribe">';
		printf( '<input type="hidden" name="post_id" value="%d">', absint( $this->document_id() ) );
		printf( '<input type="hidden" name="form_id" value="%s">', esc_attr( $this->get_id() ) );
		printf( '<input type="hidden" name="nonce" value="%s">', esc_attr( wp_create_nonce( Handler::NONCE ) ) );
		printf(
			'<div class="qe-form__hp" aria-hidden="true"><label>%s<input type="text" name="website" value="" tabindex="-1" autocomplete="off"></label></div>',
			esc_html__( 'Leave this field empty', 'qe-edu-core' )
		);
		printf(
			'<div class="qe-newsletter__row"><label for="%1$s" class="screen-reader-text">%2$s</label><input type="email" id="%1$s" name="qe_email" required autocomplete="email" placeholder="%2$s" class="qe-form__input" data-type="email"><button type="submit" class="qe-btn" data-qe-submit>%3$s</button></div>',
			esc_attr( $id ),
			esc_attr( $s['placeholder'] ),
			esc_html( $s['button'] )
		);
		echo '<p class="qe-form__error" role="alert" hidden></p><div class="qe-form__message" role="status" aria-live="polite"></div></form></div>';
	}
}
