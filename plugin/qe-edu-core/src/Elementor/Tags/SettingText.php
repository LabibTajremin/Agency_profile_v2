<?php
/**
 * Dynamic tag: a text from Theme Settings (phone, email, address…).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Setting text tag.
 */
class SettingText extends Tag {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-setting-text';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Theme Settings text', 'qe-edu-core' );
	}

	/**
	 * Group.
	 *
	 * @return string
	 */
	public function get_group() {
		return 'qe-edu';
	}

	/**
	 * Categories.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( Module::TEXT_CATEGORY );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'key',
			array(
				'label'   => __( 'Which text', 'qe-edu-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => Values::text_options(),
				'default' => 'phone',
			)
		);
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	public function render() {
		echo wp_kses_post( nl2br( esc_html( Values::text( (string) $this->get_settings( 'key' ) ) ) ) );
	}
}
