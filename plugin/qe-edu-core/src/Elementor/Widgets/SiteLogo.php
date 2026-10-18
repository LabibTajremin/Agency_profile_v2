<?php
/**
 * Site Logo: the logo from Theme Settings → Brand (or the site name),
 * linked to the home page. Used in the header and footer templates.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Site logo.
 */
class SiteLogo extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-site-logo';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Site Logo', 'qe-edu-core' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-site-logo';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Logo', 'qe-edu-core' ) ) );
		$this->add_select(
			'variant',
			__( 'Which logo', 'qe-edu-core' ),
			array(
				'default' => __( 'Normal logo', 'qe-edu-core' ),
				'dark'    => __( 'Logo for dark backgrounds', 'qe-edu-core' ),
			),
			'default',
			array( 'description' => __( 'Logos are set in Theme Settings → Brand.', 'qe-edu-core' ) )
		);
		$this->add_responsive_control(
			'width',
			array(
				'label'      => __( 'Width', 'qe-edu-core' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 40,
						'max' => 400,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 180,
				),
				'selectors'  => array( '{{WRAPPER}} .qe-site-logo img' => 'width: {{SIZE}}{{UNIT}}; height: auto;' ),
			)
		);
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'name', __( 'Site name (when there is no logo)', 'qe-edu-core' ), '.qe-site-logo__name', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->end_controls_section();
		$this->add_box_style( '.qe-site-logo', array( 'label' => __( 'Logo', 'qe-edu-core' ) ) );
	}

	/**
	 * Attachment id of the logo to show.
	 *
	 * @param string $variant default|dark.
	 * @return int
	 */
	public static function logo_id( $variant ) {
		$dark = absint( Settings::get( 'brand.logo_dark' ) );
		if ( 'dark' === $variant && $dark ) {
			return $dark;
		}

		$logo = absint( Settings::get( 'brand.logo' ) );

		return $logo ? $logo : absint( get_theme_mod( 'custom_logo' ) );
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s    = $this->get_settings_for_display();
		$name = get_bloginfo( 'name' );

		printf( '<a class="qe-site-logo" href="%s" rel="home">', esc_url( home_url( '/' ) ) );
		if ( ! $this->print_image( self::logo_id( (string) $s['variant'] ), 'full', 'qe-site-logo__img', $name ) ) {
			printf( '<span class="qe-site-logo__name">%s</span>', esc_html( $name ) );
		}
		echo '</a>';
	}
}
