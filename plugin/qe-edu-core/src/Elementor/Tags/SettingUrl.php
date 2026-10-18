<?php
/**
 * Dynamic tag: a link from Theme Settings (tel:, mailto:, WhatsApp, social…).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;

defined( 'ABSPATH' ) || exit;

/**
 * Setting URL tag.
 */
class SettingUrl extends Data_Tag {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-setting-url';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Theme Settings link', 'qe-edu-core' );
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
		return array( Module::URL_CATEGORY );
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
				'label'   => __( 'Which link', 'qe-edu-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => Values::url_options(),
				'default' => 'whatsapp',
			)
		);
	}

	/**
	 * Value.
	 *
	 * @param array<string,mixed> $options Options.
	 * @return string
	 */
	public function get_value( array $options = array() ) {
		return esc_url( Values::url( (string) $this->get_settings( 'key' ) ) );
	}
}
