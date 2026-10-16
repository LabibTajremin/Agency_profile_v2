<?php
/**
 * Destinations Grid: "Study in {country}" cards from the Destinations
 * content type, as a grid or a slider.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Destinations grid.
 */
class DestinationsGrid extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-destinations';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Destinations Grid', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-gallery-grid';
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
	 * Sample countries shown until Destinations are added.
	 *
	 * @return string[]
	 */
	public static function sample_countries() {
		return array(
			__( 'United Kingdom', 'qe-edu' ),
			__( 'United States', 'qe-edu' ),
			__( 'Canada', 'qe-edu' ),
			__( 'Australia', 'qe-edu' ),
			__( 'Ireland', 'qe-edu' ),
			__( 'New Zealand', 'qe-edu' ),
			__( 'Germany', 'qe-edu' ),
			__( 'France', 'qe-edu' ),
			__( 'Netherlands', 'qe-edu' ),
			__( 'Malaysia', 'qe-edu' ),
			__( 'Singapore', 'qe-edu' ),
			__( 'United Arab Emirates', 'qe-edu' ),
			__( 'Cyprus', 'qe-edu' ),
			__( 'Malta', 'qe-edu' ),
			__( 'Switzerland', 'qe-edu' ),
			__( 'Japan', 'qe-edu' ),
		);
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Destinations', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Make the world your classroom', 'qe-edu' ) );
		$this->add_textarea( 'intro', __( 'Introduction', 'qe-edu' ), __( 'Explore study destinations chosen by thousands of our students.', 'qe-edu' ) );
		$this->add_text(
			'title_template',
			__( 'Card title', 'qe-edu' ),
			__( 'Study in {name}', 'qe-edu' ),
			array( 'description' => __( '{name} becomes the country name.', 'qe-edu' ) )
		);
		$this->add_number( 'count', __( 'How many', 'qe-edu' ), 16, 1, 60 );
		$this->add_select(
			'layout',
			__( 'Layout', 'qe-edu' ),
			array(
				'grid'   => __( 'Grid', 'qe-edu' ),
				'slider' => __( 'Slider', 'qe-edu' ),
			),
			'grid'
		);
		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Columns', 'qe-edu' ),
				'type'           => Controls_Manager::SELECT,
				'options'        => array(
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				),
				'default'        => '4',
				'tablet_default' => '3',
				'mobile_default' => '2',
				'selectors'      => array( '{{WRAPPER}} .qe-dgrid' => '--qe-cols: {{VALUE}};' ),
				'condition'      => array( 'layout' => 'grid' ),
			)
		);
		$this->add_select(
			'hover',
			__( 'Hover effect', 'qe-edu' ),
			array(
				'zoom' => __( 'Zoom the photo', 'qe-edu' ),
				'lift' => __( 'Lift the card', 'qe-edu' ),
				'none' => __( 'None', 'qe-edu' ),
			),
			'zoom'
		);
		$this->add_switch( 'show_flag', __( 'Show flags', 'qe-edu' ) );
		$this->end_controls_section();

		$this->add_slider_controls( 4 );

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu' ), '.qe-section-head__title', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'card_title', __( 'Card title', 'qe-edu' ), '.qe-dcard__title', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->end_controls_section();
	}

	/**
	 * Cards: [name, image, flag, url].
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return array<int,array<string,mixed>>
	 */
	public function items( $s ) {
		$items = array();
		foreach ( $this->posts( 'qe_destination', (int) $s['count'] ) as $post ) {
			$country = Types::get( $post->ID, 'country' );
			$link    = Types::get( $post->ID, 'link' );
			$items[] = array(
				'name'  => '' !== $country ? $country : get_the_title( $post ),
				'image' => (int) get_post_thumbnail_id( $post ),
				'flag'  => (int) Types::get( $post->ID, 'flag' ),
				'url'   => '' !== $link ? $link : (string) get_permalink( $post ),
			);
		}

		if ( $items ) {
			return $items;
		}

		$this->print_editor_notice( __( 'Showing sample countries. Add your own under Destinations in the dashboard menu.', 'qe-edu' ) );

		return array_map(
			static function ( $name ) {
				return array(
					'name'  => $name,
					'image' => 0,
					'flag'  => 0,
					'url'   => '',
				);
			},
			array_slice( self::sample_countries(), 0, max( 1, (int) $s['count'] ) )
		);
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s      = $this->get_settings_for_display();
		$slider = 'slider' === $s['layout'];
		$hover  = in_array( $s['hover'], array( 'zoom', 'lift', 'none' ), true ) ? $s['hover'] : 'zoom';

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		$this->print_text( $s['intro'], 'qe-section-head__text' );
		echo '</div>';

		if ( $slider ) {
			echo '<div class="qe-slider"' . $this->slider_attributes( $s ) . '><div class="swiper"><div class="swiper-wrapper">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in slider_attributes().
		} else {
			echo '<div class="qe-dgrid">';
		}

		foreach ( $this->items( $s ) as $item ) {
			$title = str_replace( '{name}', $item['name'], (string) $s['title_template'] );
			$tag   = '' !== $item['url'] ? 'a' : 'div';

			printf(
				'<%1$s class="qe-dcard qe-dcard--%2$s%3$s"%4$s>',
				tag_escape( $tag ),
				esc_attr( $hover ),
				$slider ? ' swiper-slide' : '',
				'a' === $tag ? ' href="' . esc_url( $item['url'] ) . '"' : ''
			);
			echo '<span class="qe-dcard__media">';
			if ( ! $this->print_image( $item['image'], 'qe-card', 'qe-dcard__img', $item['name'] ) ) {
				echo '<span class="qe-placeholder" aria-hidden="true"></span>';
			}
			echo '</span>';
			if ( 'yes' === $s['show_flag'] && $item['flag'] ) {
				echo '<span class="qe-dcard__flag">';
				$this->print_image( $item['flag'], 'thumbnail', 'qe-dcard__flag-img' );
				echo '</span>';
			}
			printf( '<span class="qe-dcard__title">%s</span></%s>', esc_html( $title ), tag_escape( $tag ) );
		}

		if ( $slider ) {
			echo '</div></div>';
			$this->print_slider_nav( $s );
		}
		echo '</div>';
	}
}
