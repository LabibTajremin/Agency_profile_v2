<?php
/**
 * Image / News Slider: latest posts, upcoming events or hand-made slides
 * ("Expand Your Horizons").
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * News slider.
 */
class NewsSlider extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-news-slider';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Image / News Slider', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-carousel';
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
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Slides', 'qe-edu' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Expand Your Horizons', 'qe-edu' ) );
		$this->add_textarea( 'subtitle', __( 'Subtitle', 'qe-edu' ), __( 'Choose your dream career through countless universities.', 'qe-edu' ) );
		$this->add_select(
			'source',
			__( 'Slides come from', 'qe-edu' ),
			array(
				'posts'  => __( 'Latest news posts', 'qe-edu' ),
				'events' => __( 'Upcoming events', 'qe-edu' ),
				'manual' => __( 'Typed in below', 'qe-edu' ),
			),
			'posts'
		);
		$this->add_number( 'count', __( 'How many', 'qe-edu' ), 6, 1, 20, array( 'condition' => array( 'source!' => 'manual' ) ) );

		$items = new Repeater();
		$items->add_control(
			'image',
			array(
				'label'   => __( 'Image', 'qe-edu' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$items->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'qe-edu' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$items->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'qe-edu' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$items->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'qe-edu' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
			)
		);
		$this->add_control(
			'items',
			array(
				'label'       => __( 'Slides', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'title_field' => '{{{ title }}}',
				'condition'   => array( 'source' => 'manual' ),
				'default'     => array(
					array(
						'title' => __( 'Business & Management', 'qe-edu' ),
						'text'  => __( 'Lead global organisations.', 'qe-edu' ),
					),
					array(
						'title' => __( 'Engineering & Technology', 'qe-edu' ),
						'text'  => __( 'Build what comes next.', 'qe-edu' ),
					),
					array(
						'title' => __( 'Health & Medicine', 'qe-edu' ),
						'text'  => __( 'Care for the world.', 'qe-edu' ),
					),
				),
			)
		);
		$this->add_text( 'read_more', __( 'Link text', 'qe-edu' ), __( 'Read more', 'qe-edu' ) );
		$this->add_text( 'empty_text', __( 'Text when there is nothing to show', 'qe-edu' ), __( 'News is coming soon.', 'qe-edu' ) );
		$this->end_controls_section();

		$this->add_slider_controls( 3 );

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu' ), '.qe-section-head__title', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'card_title', __( 'Slide title', 'qe-edu' ), '.qe-card-title', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->end_controls_section();
	}

	/**
	 * Slides: [image, title, text, link, meta].
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return array<int,array<string,mixed>>
	 */
	public function items( $s ) {
		if ( 'manual' === $s['source'] ) {
			return self::items_with(
				$s['items'],
				array(
					'image' => array(),
					'title' => '',
					'text'  => '',
					'link'  => array( 'url' => '' ),
					'meta'  => '',
				)
			);
		}

		$events = 'events' === $s['source'];
		$args   = $events ? array(
			'meta_key'   => '_qe_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- small, indexed meta table lookup.
			'orderby'    => 'meta_value',
			'order'      => 'ASC',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_qe_date',
					'value'   => wp_date( 'Y-m-d' ),
					'compare' => '>=',
				),
			),
		) : array( 'orderby' => 'date' );

		$items = array();
		foreach ( $this->posts( $events ? 'qe_event' : 'post', (int) $s['count'], $args ) as $post ) {
			$date    = $events ? Types::get( $post->ID, 'date' ) : '';
			$items[] = array(
				'image' => (int) get_post_thumbnail_id( $post ),
				'title' => get_the_title( $post ),
				'text'  => get_the_excerpt( $post ),
				'link'  => array( 'url' => (string) get_permalink( $post ) ),
				'meta'  => $events ? ( '' !== $date ? date_i18n( (string) get_option( 'date_format' ), (int) strtotime( $date ) ) : '' ) : get_the_date( '', $post ),
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

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		$this->print_text( $s['subtitle'], 'qe-section-head__text' );
		echo '</div>';

		if ( ! $items ) {
			$this->print_text( $s['empty_text'], 'qe-empty' );
			return;
		}

		$this->prime_images( $items );
		echo '<div class="qe-slider"' . $this->slider_attributes( $s ) . '><div class="swiper"><div class="swiper-wrapper">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in slider_attributes().
		foreach ( $items as $i => $item ) {
			echo '<div class="swiper-slide"><article class="qe-ncard"><div class="qe-ncard__media">';
			if ( ! $this->print_image( $item['image'], 'qe-card', 'qe-ncard__img', $item['title'] ) ) {
				echo '<span class="qe-placeholder" aria-hidden="true"></span>';
			}
			echo '</div><div class="qe-ncard__body">';
			if ( '' !== $item['meta'] ) {
				printf( '<p class="qe-ncard__meta">%s</p>', esc_html( $item['meta'] ) );
			}
			printf( '<h3 class="qe-card-title">%s</h3>', esc_html( $item['title'] ) );
			$this->print_text( $item['text'], 'qe-card-text' );
			$this->print_button( 'slide-link-' . $i, $s['read_more'], $item['link'], 'qe-link-arrow', $item['title'] );
			echo '</div></article></div>';
		}
		echo '</div></div>';
		$this->print_slider_nav( $s );
		echo '</div>';
	}
}
