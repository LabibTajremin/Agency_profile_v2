<?php
/**
 * Page Title Banner: page title, subtitle and breadcrumbs over an optional
 * background image (top of inner pages).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\Seo\Breadcrumbs as Trail;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Page title banner.
 */
class PageTitleBanner extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-page-title';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Page Title Banner', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-site-title';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Title', 'qe-edu' ) ) );
		$this->add_text(
			'title',
			__( 'Title', 'qe-edu' ),
			'',
			array( 'description' => __( 'Leave empty to use the page title.', 'qe-edu' ) )
		);
		$this->add_textarea( 'subtitle', __( 'Subtitle', 'qe-edu' ), '' );
		$this->add_switch( 'show_breadcrumbs', __( 'Show breadcrumbs', 'qe-edu' ) );
		$this->add_text( 'home_label', __( 'Breadcrumb first link', 'qe-edu' ), __( 'Home', 'qe-edu' ), array( 'condition' => array( 'show_breadcrumbs' => 'yes' ) ) );
		$this->add_text( 'nav_label', __( 'Breadcrumb label for screen readers', 'qe-edu' ), __( 'Breadcrumb', 'qe-edu' ), array( 'condition' => array( 'show_breadcrumbs' => 'yes' ) ) );
		$this->add_control(
			'background_image',
			array(
				'label'   => __( 'Background image', 'qe-edu' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$this->add_number( 'min_height', __( 'Minimum height (pixels)', 'qe-edu' ), 280, 120, 800 );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'title', __( 'Title', 'qe-edu' ), '.qe-banner__title', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'subtitle', __( 'Subtitle', 'qe-edu' ), '.qe-banner__subtitle', self::COLOR_TEXT, self::FONT_TEXT );
		$this->end_controls_section();
	}

	/**
	 * The title to show.
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return string
	 */
	public static function title( $s ) {
		if ( '' !== trim( (string) $s['title'] ) ) {
			return (string) $s['title'];
		}

		if ( is_singular() ) {
			return (string) get_the_title( get_queried_object_id() );
		}

		if ( is_home() && ! is_front_page() ) {
			return (string) single_post_title( '', false );
		}

		if ( is_search() ) {
			/* translators: %s: search terms. */
			return sprintf( __( 'Search results for “%s”', 'qe-edu' ), get_search_query( false ) );
		}

		if ( is_404() ) {
			return __( 'Page not found', 'qe-edu' );
		}

		if ( is_archive() ) {
			// A banner reads better without "Category:" / "Archives:".
			add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
			$title = wp_strip_all_tags( get_the_archive_title() );
			remove_filter( 'get_the_archive_title_prefix', '__return_empty_string' );
			return trim( $title );
		}

		return (string) get_the_title();
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s         = $this->get_settings_for_display();
		$has_image = ! empty( $s['background_image']['url'] ) || ! empty( $s['background_image']['id'] );

		printf( '<header class="qe-banner%1$s" style="--qe-banner-min:%2$dpx">', $has_image ? ' has-image' : '', absint( $s['min_height'] ) );
		if ( $has_image ) {
			$this->print_image( $s['background_image'], 'qe-hero', 'qe-banner__bg', '', array( 'fetchpriority' => 'high' ) );
		}
		echo '<div class="qe-banner__inner">';
		printf( '<h1 class="qe-banner__title">%s</h1>', esc_html( self::title( $s ) ) );
		$this->print_text( $s['subtitle'], 'qe-banner__subtitle' );
		if ( 'yes' === $s['show_breadcrumbs'] && Settings::get( 'seo.breadcrumbs' ) ) {
			Breadcrumbs::print_trail( Trail::trail( (string) $s['home_label'] ), '›', (string) $s['nav_label'] );
		}
		echo '</div></header>';
	}
}
