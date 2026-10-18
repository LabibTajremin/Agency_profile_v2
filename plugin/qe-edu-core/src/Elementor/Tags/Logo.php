<?php
/**
 * Dynamic tag: the logo from Theme Settings → Brand.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Tags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module;
use QeEdu\Core\Elementor\Widgets\SiteLogo;

defined( 'ABSPATH' ) || exit;

/**
 * Logo tag.
 */
class Logo extends Data_Tag {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-logo';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Theme Settings logo', 'qe-edu-core' );
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
		return array( Module::IMAGE_CATEGORY );
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'variant',
			array(
				'label'   => __( 'Which logo', 'qe-edu-core' ),
				'type'    => Controls_Manager::SELECT,
				'options' => array(
					'default' => __( 'Normal logo', 'qe-edu-core' ),
					'dark'    => __( 'Logo for dark backgrounds', 'qe-edu-core' ),
				),
				'default' => 'default',
			)
		);
	}

	/**
	 * Value.
	 *
	 * @param array<string,mixed> $options Options.
	 * @return array<string,mixed>
	 */
	public function get_value( array $options = array() ) {
		$id = SiteLogo::logo_id( (string) $this->get_settings( 'variant' ) );

		return array(
			'id'  => $id,
			'url' => $id ? (string) wp_get_attachment_url( $id ) : '',
		);
	}
}
