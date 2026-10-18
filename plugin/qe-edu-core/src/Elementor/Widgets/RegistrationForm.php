<?php
/**
 * Registration Form widget: student sign-up, single or two steps.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

defined( 'ABSPATH' ) || exit;

/**
 * Registration form.
 */
class RegistrationForm extends Base {

	use RegistrationFormTrait;

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-registration-form';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Registration Form', 'qe-edu-core' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
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
		$this->register_form_controls();

		$this->start_style_section();
		$this->add_text_style( 'label', __( 'Labels', 'qe-edu-core' ), '.qe-form__label', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->add_button_style( '.qe-form .qe-btn:not(.qe-btn--ghost)' );
		$this->end_controls_section();
		$this->add_box_style( '.qe-form', array( 'label' => __( 'Form box', 'qe-edu-core' ) ) );
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		echo '<div class="qe-form-wrap">';
		$this->print_form( $this->get_settings_for_display() );
		echo '</div>';
	}
}
