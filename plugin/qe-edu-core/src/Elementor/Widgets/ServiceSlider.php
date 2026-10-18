<?php
/**
 * Service Cards Slider: image, title, text and "Learn more" per service,
 * from the Services content type or typed in by hand.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use QeEdu\Core\PostTypes\Types;

defined( 'ABSPATH' ) || exit;

/**
 * Service slider.
 */
class ServiceSlider extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-service-slider';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Service Cards Slider', 'qe-edu-core' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slides';
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
	 * Sample cards shown until Services are added.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function sample_items() {
		return array(
			array(
				'title' => __( 'Direct Entry Student Recruitment', 'qe-edu-core' ),
				'text'  => __( 'We connect qualified students directly with partner universities and colleges.', 'qe-edu-core' ),
			),
			array(
				'title' => __( 'International Pathways', 'qe-edu-core' ),
				'text'  => __( 'Foundation and pre-master\'s programmes that lead to degree study.', 'qe-edu-core' ),
			),
			array(
				'title' => __( 'In-country University Preparation', 'qe-edu-core' ),
				'text'  => __( 'Academic and English preparation before you travel.', 'qe-edu-core' ),
			),
			array(
				'title' => __( 'Managed Campus', 'qe-edu-core' ),
				'text'  => __( 'Study centres that deliver partner programmes close to home.', 'qe-edu-core' ),
			),
		);
	}

	/**
	 * Fields every card has.
	 *
	 * @return array<string,mixed>
	 */
	public static function item_defaults() {
		return array(
			'image'  => array(),
			'title'  => '',
			'text'   => '',
			'link'   => array( 'url' => '' ),
			'button' => '',
		);
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Cards', 'qe-edu-core' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu-core' ), __( 'What We Do', 'qe-edu-core' ) );
		$this->add_textarea( 'intro', __( 'Introduction', 'qe-edu-core' ), __( 'Four ways we help students and institutions succeed.', 'qe-edu-core' ) );
		$this->add_select(
			'source',
			__( 'Cards come from', 'qe-edu-core' ),
			array(
				'cpt'    => __( 'Services (dashboard menu) — updates automatically', 'qe-edu-core' ),
				'manual' => __( 'Typed in below', 'qe-edu-core' ),
			),
			'cpt'
		);
		$this->add_number( 'count', __( 'How many', 'qe-edu-core' ), 8, 1, 30, array( 'condition' => array( 'source' => 'cpt' ) ) );

		$items = new Repeater();
		$items->add_control(
			'image',
			array(
				'label'   => __( 'Image', 'qe-edu-core' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$items->add_control(
			'title',
			array(
				'label'       => __( 'Title', 'qe-edu-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$items->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'qe-edu-core' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$items->add_control(
			'link',
			array(
				'label'   => __( 'Link', 'qe-edu-core' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => '' ),
			)
		);
		$this->add_control(
			'items',
			array(
				'label'       => __( 'Cards', 'qe-edu-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'default'     => self::sample_items(),
				'title_field' => '{{{ title }}}',
				'condition'   => array( 'source' => 'manual' ),
			)
		);
		$this->add_text( 'button_text', __( 'Button text', 'qe-edu-core' ), __( 'Learn More', 'qe-edu-core' ) );
		$this->end_controls_section();

		$this->add_slider_controls( 3 );

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu-core' ), '.qe-section-head__title', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'card_title', __( 'Card title', 'qe-edu-core' ), '.qe-card-title', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->add_text_style( 'card_text', __( 'Card text', 'qe-edu-core' ), '.qe-card-text', self::COLOR_TEXT, self::FONT_TEXT );
		$this->end_controls_section();
		$this->add_box_style(
			'.qe-scard',
			array(
				'gap'     => '.qe-slider',
				'gap_var' => '--qe-gap',
			)
		);
	}

	/**
	 * Cards to show.
	 *
	 * @param array<string,mixed> $s Settings.
	 * @return array<int,array<string,mixed>>
	 */
	public function items( $s ) {
		if ( 'manual' === $s['source'] ) {
			return self::items_with( $s['items'], self::item_defaults() );
		}

		$items = array();
		foreach ( $this->posts( 'qe_service', (int) $s['count'] ) as $post ) {
			$link    = Types::get( $post->ID, 'link' );
			$button  = Types::get( $post->ID, 'button_text' );
			$items[] = array(
				'image'  => (int) get_post_thumbnail_id( $post ),
				'title'  => get_the_title( $post ),
				'text'   => get_the_excerpt( $post ),
				'link'   => array( 'url' => '' !== $link ? $link : get_permalink( $post ) ),
				'button' => $button,
			);
		}

		if ( ! $items ) {
			$this->print_editor_notice( __( 'Showing sample cards. Add your own under Services in the dashboard menu.', 'qe-edu-core' ) );
			return self::items_with( self::sample_items(), self::item_defaults() );
		}

		return $items;
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		$this->print_text( $s['intro'], 'qe-section-head__text' );
		echo '</div>';

		echo '<div class="qe-slider"' . $this->slider_attributes( $s ) . '><div class="swiper"><div class="swiper-wrapper">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in slider_attributes().
		$items = $this->items( $s );
		$this->prime_images( $items );
		foreach ( $items as $i => $item ) {
			$button = '' !== (string) $item['button'] ? $item['button'] : $s['button_text'];

			echo '<div class="swiper-slide"><article class="qe-scard">';
			echo '<div class="qe-scard__media">';
			if ( ! $this->print_image( $item['image'], 'qe-card', 'qe-scard__img', $item['title'] ) ) {
				echo '<span class="qe-placeholder" aria-hidden="true"></span>';
			}
			echo '</div><div class="qe-scard__body">';
			printf( '<h3 class="qe-card-title">%s</h3>', esc_html( $item['title'] ) );
			$this->print_text( $item['text'], 'qe-card-text' );
			$this->print_button( 'card-link-' . $i, $button, $item['link'], 'qe-link-arrow', $item['title'] );
			echo '</div></article></div>';
		}
		echo '</div></div>';
		$this->print_slider_nav( $s );
		echo '</div>';
	}
}
