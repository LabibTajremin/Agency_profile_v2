<?php
/**
 * Breadcrumbs: "Home › Section › Page". Hidden when Theme Settings → SEO →
 * Breadcrumbs is off. The matching schema is printed by the SEO module.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use QeEdu\Core\Seo\Breadcrumbs as Trail;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumbs.
 */
class Breadcrumbs extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-breadcrumbs';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Breadcrumbs', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-breadcrumbs';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Breadcrumbs', 'qe-edu' ) ) );
		$this->add_text( 'home_label', __( 'First link text', 'qe-edu' ), __( 'Home', 'qe-edu' ) );
		$this->add_text( 'separator', __( 'Separator', 'qe-edu' ), '›' );
		$this->add_text( 'nav_label', __( 'Label for screen readers', 'qe-edu' ), __( 'Breadcrumb', 'qe-edu' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'text', __( 'Text', 'qe-edu' ), '.qe-breadcrumbs', self::COLOR_TEXT, self::FONT_TEXT );
		$this->end_controls_section();
	}

	/**
	 * Print a trail.
	 *
	 * @param array<int,array<string,string>> $trail     Trail.
	 * @param string                          $separator Separator.
	 * @param string                          $label     Nav label.
	 * @return void
	 */
	public static function print_trail( $trail, $separator, $label ) {
		printf( '<nav class="qe-breadcrumbs" aria-label="%s"><ol>', esc_attr( $label ) );
		$last = count( $trail ) - 1;
		foreach ( array_values( $trail ) as $i => $crumb ) {
			echo '<li>';
			if ( '' !== $crumb['url'] && $i !== $last ) {
				printf( '<a href="%1$s">%2$s</a>', esc_url( $crumb['url'] ), esc_html( $crumb['label'] ) );
			} else {
				printf( '<span aria-current="page">%s</span>', esc_html( $crumb['label'] ) );
			}
			if ( $i !== $last ) {
				printf( '<span class="qe-breadcrumbs__sep" aria-hidden="true">%s</span>', esc_html( $separator ) );
			}
			echo '</li>';
		}
		echo '</ol></nav>';
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		if ( ! Settings::get( 'seo.breadcrumbs' ) ) {
			$this->print_editor_notice( __( 'Breadcrumbs are switched off in Theme Settings → SEO.', 'qe-edu' ) );
			return;
		}

		self::print_trail( Trail::trail( (string) $s['home_label'] ), (string) $s['separator'], (string) $s['nav_label'] );
	}
}
