<?php
/**
 * Testimonials slider.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Testimonials.
 */
class Testimonials extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-testimonials';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Testimonials', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-testimonial-carousel';
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
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Testimonials', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'What our students say', 'qe-edu' ) );
		$this->add_select(
			'source',
			__( 'Testimonials come from', 'qe-edu' ),
			array(
				'cpt'    => __( 'Testimonials (dashboard menu) — updates automatically', 'qe-edu' ),
				'manual' => __( 'Typed in below', 'qe-edu' ),
			),
			'cpt'
		);
		$items = new Repeater();
		$items->add_control(
			'quote',
			array(
				'label'   => __( 'What they said', 'qe-edu' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$items->add_control(
			'name',
			array(
				'label'   => __( 'Name', 'qe-edu' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$items->add_control(
			'role',
			array(
				'label'   => __( 'Who they are', 'qe-edu' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$items->add_control(
			'image',
			array(
				'label'   => __( 'Photo', 'qe-edu' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$items->add_control(
			'rating',
			array(
				'label'   => __( 'Stars', 'qe-edu' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 5,
				'min'     => 0,
				'max'     => 5,
			)
		);
		$this->add_control(
			'items',
			array(
				'label'       => __( 'Testimonials', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'title_field' => '{{{ name }}}',
				'condition'   => array( 'source' => 'manual' ),
				'default'     => array(
					array(
						'quote'  => __( 'The advisors guided me through every step. I am now studying my dream course abroad.', 'qe-edu' ),
						'name'   => __( 'Amira K.', 'qe-edu' ),
						'role'   => __( 'MSc student', 'qe-edu' ),
						'rating' => 5,
					),
					array(
						'quote'  => __( 'From choosing a university to my visa, everything was clear and fast.', 'qe-edu' ),
						'name'   => __( 'Daniel O.', 'qe-edu' ),
						'role'   => __( 'Foundation student', 'qe-edu' ),
						'rating' => 5,
					),
				),
			)
		);
		$this->add_switch( 'show_rating', __( 'Show stars', 'qe-edu' ) );
		/* translators: %d: number of stars. */
		$this->add_text( 'rating_label', __( 'Star rating text for screen readers', 'qe-edu' ), __( 'Rated %d out of 5', 'qe-edu' ) );
		$this->end_controls_section();

		$this->add_slider_controls( 2 );

		$this->start_style_section();
		$this->add_text_style( 'quote', __( 'Quote', 'qe-edu' ), '.qe-tcard__quote', self::COLOR_TEXT, self::FONT_TEXT );
		$this->add_text_style( 'name', __( 'Name', 'qe-edu' ), '.qe-tcard__name', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->end_controls_section();
	}

	/**
	 * Items: [quote, name, role, image, rating].
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return array<int,array<string,mixed>>
	 */
	public function items( $s ) {
		if ( 'manual' === $s['source'] ) {
			return self::items_with(
				$s['items'],
				array(
					'quote'  => '',
					'name'   => '',
					'role'   => '',
					'image'  => array(),
					'rating' => 0,
				)
			);
		}

		$items = array();
		foreach ( $this->posts( 'qe_testimonial', 30 ) as $post ) {
			$items[] = array(
				'quote'  => get_the_excerpt( $post ),
				'name'   => get_the_title( $post ),
				'role'   => Types::get( $post->ID, 'role' ),
				'image'  => (int) get_post_thumbnail_id( $post ),
				'rating' => (int) Types::get( $post->ID, 'rating' ),
			);
		}

		return $items;
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = $this->items( $s );

		if ( ! $items ) {
			$this->print_editor_notice( __( 'No testimonials yet. Add them under Testimonials in the dashboard menu.', 'qe-edu' ) );
			return;
		}

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		echo '</div>';

		$this->prime_images( $items );
		echo '<div class="qe-slider"' . $this->slider_attributes( $s ) . '><div class="swiper"><div class="swiper-wrapper">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in slider_attributes().
		foreach ( $items as $item ) {
			$rating = max( 0, min( 5, (int) $item['rating'] ) );
			echo '<div class="swiper-slide"><figure class="qe-tcard">';
			if ( 'yes' === $s['show_rating'] && $rating ) {
				printf(
					'<p class="qe-stars" role="img" aria-label="%1$s">%2$s</p>',
					esc_attr( sprintf( (string) $s['rating_label'], $rating ) ),
					esc_html( str_repeat( '★', $rating ) )
				);
			}
			printf( '<blockquote class="qe-tcard__quote">%s</blockquote>', wp_kses_post( wpautop( (string) $item['quote'] ) ) );
			echo '<figcaption class="qe-tcard__author">';
			$this->print_image( $item['image'], 'thumbnail', 'qe-tcard__photo', $item['name'] );
			printf( '<span><span class="qe-tcard__name">%1$s</span><span class="qe-tcard__role">%2$s</span></span>', esc_html( $item['name'] ), esc_html( $item['role'] ) );
			echo '</figcaption></figure></div>';
		}
		echo '</div></div>';
		$this->print_slider_nav( $s );
		echo '</div>';
	}
}
