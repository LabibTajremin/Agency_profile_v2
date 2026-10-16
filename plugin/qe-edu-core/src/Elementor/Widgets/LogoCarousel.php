<?php
/**
 * Logo Carousel: partner logos from the Partners content type (or a
 * gallery), as a continuous marquee or a step slider.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Logo carousel.
 */
class LogoCarousel extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-logo-carousel';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Logo Carousel', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-logo';
	}

	/**
	 * Styles.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return $this->slider_style_depends();
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
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Logos', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Trusted by leaders, experts and institutions across the globe', 'qe-edu' ) );
		$this->add_select(
			'source',
			__( 'Logos come from', 'qe-edu' ),
			array(
				'cpt'     => __( 'Partners (dashboard menu) — updates automatically', 'qe-edu' ),
				'gallery' => __( 'Images chosen below', 'qe-edu' ),
			),
			'cpt'
		);
		$this->add_select(
			'partner_type',
			__( 'Which partners', 'qe-edu' ),
			array_merge( array( '' => __( 'All partners', 'qe-edu' ) ), Types::partner_types() ),
			'',
			array( 'condition' => array( 'source' => 'cpt' ) )
		);
		$this->add_control(
			'gallery',
			array(
				'label'     => __( 'Logos', 'qe-edu' ),
				'type'      => Controls_Manager::GALLERY,
				'default'   => array(),
				'condition' => array( 'source' => 'gallery' ),
			)
		);
		$this->add_select(
			'mode',
			__( 'Movement', 'qe-edu' ),
			array(
				'marquee' => __( 'Continuous scroll', 'qe-edu' ),
				'step'    => __( 'Step by step (slider)', 'qe-edu' ),
				'static'  => __( 'No movement (grid)', 'qe-edu' ),
			),
			'marquee'
		);
		$this->add_number( 'speed', __( 'Scroll duration (seconds per loop)', 'qe-edu' ), 30, 5, 120, array( 'condition' => array( 'mode' => 'marquee' ) ) );
		$this->add_switch( 'grayscale', __( 'Grey logos, colour on hover', 'qe-edu' ) );
		$this->end_controls_section();

		$this->add_slider_controls( 5 );

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu' ), '.qe-section-head__title', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->end_controls_section();
	}

	/**
	 * Logos: [image id/media, name, url].
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return array<int,array<string,mixed>>
	 */
	public function items( $s ) {
		if ( 'gallery' === $s['source'] ) {
			return array_map(
				static function ( $image ) {
					return array(
						'image' => $image,
						'name'  => '',
						'url'   => '',
					);
				},
				(array) $s['gallery']
			);
		}

		$args = array();
		if ( '' !== (string) $s['partner_type'] ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => Types::meta_key( 'type' ),
					'value' => (string) $s['partner_type'],
				),
			);
		}

		$items = array();
		foreach ( $this->posts( 'qe_partner', 100, $args ) as $post ) {
			$items[] = array(
				'image' => (int) get_post_thumbnail_id( $post ),
				'name'  => get_the_title( $post ),
				'url'   => Types::get( $post->ID, 'url' ),
			);
		}

		return $items;
	}

	/**
	 * Print one logo.
	 *
	 * @param array<string,mixed> $item      Logo.
	 * @param string              $css_class Extra class.
	 * @return void
	 */
	private function print_logo( $item, $css_class ) {
		$tag = '' !== $item['url'] ? 'a' : 'span';
		printf(
			'<%1$s class="qe-logo %2$s"%3$s>',
			tag_escape( $tag ),
			esc_attr( $css_class ),
			'a' === $tag ? ' href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener"' : ''
		);
		if ( ! $this->print_image( $item['image'], 'qe-logo', 'qe-logo__img', $item['name'] ) ) {
			printf( '<span class="qe-logo__name">%s</span>', esc_html( $item['name'] ) );
		}
		printf( '</%s>', tag_escape( $tag ) );
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = $this->items( $s );
		$mode  = in_array( $s['mode'], array( 'marquee', 'step', 'static' ), true ) ? $s['mode'] : 'marquee';

		if ( ! $items ) {
			$this->print_editor_notice( __( 'No logos yet. Add them under Partners in the dashboard menu, or choose images.', 'qe-edu' ) );
			return;
		}

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		echo '</div>';

		printf( '<div class="qe-logos qe-logos--%1$s%2$s">', esc_attr( $mode ), 'yes' === $s['grayscale'] ? ' is-grayscale' : '' );

		if ( 'step' === $mode ) {
			echo '<div class="qe-slider"' . $this->slider_attributes( $s ) . '><div class="swiper"><div class="swiper-wrapper">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in slider_attributes().
			foreach ( $items as $item ) {
				$this->print_logo( $item, 'swiper-slide' );
			}
			echo '</div></div>';
			$this->print_slider_nav( $s );
			echo '</div>';
		} elseif ( 'marquee' === $mode ) {
			printf( '<div class="qe-marquee" style="--qe-marquee-duration:%ds">', absint( $s['speed'] ) );
			// The track is printed twice for a seamless loop; the copy is hidden from screen readers and keyboard focus.
			foreach ( array( '', ' aria-hidden="true" inert' ) as $copy ) {
				echo '<div class="qe-marquee__track"' . $copy . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup.
				foreach ( $items as $item ) {
					$this->print_logo( $item, '' );
				}
				echo '</div>';
			}
			echo '</div>';
		} else {
			echo '<div class="qe-logos__grid">';
			foreach ( $items as $item ) {
				$this->print_logo( $item, '' );
			}
			echo '</div>';
		}

		echo '</div>';
	}
}
