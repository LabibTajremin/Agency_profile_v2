<?php
/**
 * Animated Hero: headline with typed rotating words, two buttons, image /
 * video / gradient background and mouse-move parallax images.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

/**
 * Animated hero.
 */
class AnimatedHero extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-animated-hero';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Animated Hero', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-header';
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
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Headline', 'qe-edu' ) ) );
		$this->add_text( 'pre_text', __( 'Text before the moving words', 'qe-edu' ), __( 'Celebrating', 'qe-edu' ) );

		$words = new Repeater();
		$words->add_control(
			'word',
			array(
				'label'       => __( 'Words', 'qe-edu' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$this->add_control(
			'words',
			array(
				'label'       => __( 'Moving words (typed one after another)', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $words->get_controls(),
				'title_field' => '{{{ word }}}',
				'default'     => array(
					array( 'word' => __( '15 Years of Excellence', 'qe-edu' ) ),
					array( 'word' => __( 'Global Education Pathways', 'qe-edu' ) ),
					array( 'word' => __( 'Student Success Worldwide', 'qe-edu' ) ),
				),
			)
		);
		$this->add_text( 'post_text', __( 'Text after the moving words', 'qe-edu' ), '' );
		$this->add_select(
			'heading_tag',
			__( 'Heading level', 'qe-edu' ),
			array(
				'h1' => 'H1',
				'h2' => 'H2',
			),
			'h1',
			array( 'description' => __( 'Use H1 for the first section of a page.', 'qe-edu' ) )
		);
		$this->add_textarea( 'description', __( 'Text under the headline', 'qe-edu' ), __( 'Your trusted partner for studying abroad — from choosing a course to arriving on campus.', 'qe-edu' ) );
		$this->add_switch( 'cursor', __( 'Show typing cursor', 'qe-edu' ) );
		$this->add_number( 'speed', __( 'Typing speed (milliseconds per letter)', 'qe-edu' ), 70, 20, 300 );
		$this->add_number( 'pause', __( 'Pause on each phrase (milliseconds)', 'qe-edu' ), 1800, 500, 8000 );
		$this->end_controls_section();

		$this->start_controls_section( 'section_buttons', array( 'label' => __( 'Buttons', 'qe-edu' ) ) );
		$this->add_text( 'button_1_text', __( 'First button text', 'qe-edu' ), __( 'Start Your Journey', 'qe-edu' ) );
		$this->add_link( 'button_1_link', __( 'First button link', 'qe-edu' ), '#register' );
		$this->add_text( 'button_2_text', __( 'Second button text', 'qe-edu' ), __( 'Find Courses', 'qe-edu' ) );
		$this->add_link( 'button_2_link', __( 'Second button link', 'qe-edu' ), '#courses' );
		$this->end_controls_section();

		$this->start_controls_section( 'section_background', array( 'label' => __( 'Background', 'qe-edu' ) ) );
		$this->add_select(
			'background',
			__( 'Background type', 'qe-edu' ),
			array(
				'light'    => __( 'Soft light colour', 'qe-edu' ),
				'gradient' => __( 'Accent-to-navy gradient (white text)', 'qe-edu' ),
				'image'    => __( 'Image', 'qe-edu' ),
				'video'    => __( 'Video', 'qe-edu' ),
			),
			'light'
		);
		$this->add_control(
			'background_image',
			array(
				'label'     => __( 'Background image', 'qe-edu' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array( 'url' => '' ),
				'condition' => array( 'background' => array( 'image', 'video' ) ),
			)
		);
		$this->add_text(
			'background_video',
			__( 'Video file link (MP4)', 'qe-edu' ),
			'',
			array(
				'condition'   => array( 'background' => 'video' ),
				'description' => __( 'Upload the video to the Media Library and paste its link. The image above shows while it loads and on phones.', 'qe-edu' ),
			)
		);
		$this->add_switch( 'overlay', __( 'Darken the background for readable text', 'qe-edu' ), 'yes', array( 'condition' => array( 'background' => array( 'image', 'video' ) ) ) );
		$this->add_number( 'min_height', __( 'Minimum height (pixels)', 'qe-edu' ), 640, 320, 1200 );
		$this->end_controls_section();

		$this->start_controls_section( 'section_floats', array( 'label' => __( 'Floating images', 'qe-edu' ) ) );
		$floats = new Repeater();
		$floats->add_control(
			'image',
			array(
				'label'   => __( 'Image', 'qe-edu' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => array( 'url' => '' ),
			)
		);
		$floats->add_control(
			'x',
			array(
				'label'   => __( 'Horizontal position (%)', 'qe-edu' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 70,
				'min'     => 0,
				'max'     => 100,
			)
		);
		$floats->add_control(
			'y',
			array(
				'label'   => __( 'Vertical position (%)', 'qe-edu' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 20,
				'min'     => 0,
				'max'     => 100,
			)
		);
		$floats->add_control(
			'size',
			array(
				'label'   => __( 'Width (pixels)', 'qe-edu' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 220,
				'min'     => 40,
				'max'     => 800,
			)
		);
		$floats->add_control(
			'depth',
			array(
				'label'   => __( 'Movement (0 = still, 1 = most)', 'qe-edu' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0.5,
				'min'     => 0,
				'max'     => 1,
				'step'    => 0.1,
			)
		);
		$this->add_control(
			'floats',
			array(
				'label'       => __( 'Images that move with the mouse (desktop only)', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $floats->get_controls(),
				'default'     => array(),
				'title_field' => '{{{ x }}}% / {{{ y }}}%',
			)
		);
		$this->add_number( 'parallax', __( 'Mouse-move strength (pixels)', 'qe-edu' ), 24, 0, 80 );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'title', __( 'Headline', 'qe-edu' ), '.qe-hero__title', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_control(
			'highlight_color',
			array(
				'label'     => __( 'Moving words colour', 'qe-edu' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => self::COLOR_PRIMARY ),
				'selectors' => array( '{{WRAPPER}} .qe-hero__rotator' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_text_style( 'description', __( 'Text', 'qe-edu' ), '.qe-hero__text', self::COLOR_TEXT, self::FONT_TEXT );
		$this->add_button_style( '.qe-hero__actions .qe-btn' );
		$this->end_controls_section();
	}

	/**
	 * Non-empty words.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return string[]
	 */
	public static function words( $settings ) {
		$words = array();
		foreach ( self::items_with( $settings['words'], array( 'word' => '' ) ) as $item ) {
			$word = trim( (string) $item['word'] );
			if ( '' !== $word ) {
				$words[] = $word;
			}
		}

		return $words;
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s          = $this->get_settings_for_display();
		$words      = self::words( $s );
		$background = in_array( $s['background'], array( 'light', 'gradient', 'image', 'video' ), true ) ? $s['background'] : 'light';
		$tag        = 'h2' === $s['heading_tag'] ? 'h2' : 'h1';

		printf(
			'<section class="qe-hero qe-hero--%1$s%2$s" style="--qe-hero-min:%3$dpx" data-qe-hero data-speed="%4$d" data-pause="%5$d" data-parallax="%6$d">',
			esc_attr( $background ),
			'yes' === $s['overlay'] && in_array( $background, array( 'image', 'video' ), true ) ? ' has-overlay' : '',
			absint( $s['min_height'] ),
			absint( $s['speed'] ),
			absint( $s['pause'] ),
			absint( $s['parallax'] )
		);

		if ( in_array( $background, array( 'image', 'video' ), true ) ) {
			$this->print_image( $s['background_image'], 'qe-hero', 'qe-hero__bg', '', array( 'fetchpriority' => 'high' ) );
		}
		if ( 'video' === $background && '' !== trim( (string) $s['background_video'] ) ) {
			printf( '<video class="qe-hero__video" src="%s" autoplay muted loop playsinline aria-hidden="true"></video>', esc_url( $s['background_video'] ) );
		}

		echo '<div class="qe-hero__inner">';
		printf( '<%s class="qe-hero__title">', tag_escape( $tag ) );
		if ( '' !== trim( (string) $s['pre_text'] ) ) {
			printf( '<span class="qe-hero__pre">%s</span> ', esc_html( $s['pre_text'] ) );
		}
		if ( $words ) {
			// Screen readers get every phrase once; the animation is hidden from them.
			printf( '<span class="screen-reader-text">%s</span>', esc_html( implode( ', ', $words ) ) );
			// Invisible copies of every phrase reserve the space of the longest
			// one, so typing never moves the content below (no layout shift).
			$sizers = '';
			foreach ( $words as $word ) {
				$sizers .= '<span class="qe-hero__sizer">' . esc_html( $word ) . '</span>';
			}
			printf(
				'<span class="qe-hero__rotator" aria-hidden="true" data-words="%1$s">%2$s<span class="qe-hero__live"><span class="qe-hero__word">%3$s</span>%4$s</span></span>',
				esc_attr( (string) wp_json_encode( $words ) ),
				$sizers, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_html( $words[0] ),
				'yes' === $s['cursor'] ? '<span class="qe-hero__cursor"></span>' : ''
			);
		}
		if ( '' !== trim( (string) $s['post_text'] ) ) {
			printf( ' <span class="qe-hero__post">%s</span>', esc_html( $s['post_text'] ) );
		}
		printf( '</%s>', tag_escape( $tag ) );

		$this->print_text( $s['description'], 'qe-hero__text' );

		echo '<div class="qe-hero__actions">';
		$this->print_button( 'button_1', $s['button_1_text'], $s['button_1_link'], 'qe-btn' );
		$this->print_button( 'button_2', $s['button_2_text'], $s['button_2_link'], 'qe-btn qe-btn--ghost' );
		echo '</div></div>';

		if ( ! empty( $s['floats'] ) ) {
			echo '<div class="qe-hero__floats" aria-hidden="true">';
			$floats = self::items_with(
				$s['floats'],
				array(
					'image' => array(),
					'x'     => 70,
					'y'     => 20,
					'size'  => 220,
					'depth' => 0.5,
				)
			);
			foreach ( $floats as $float ) {
				printf(
					'<div class="qe-hero__float" data-depth="%1$s" style="left:%2$s%%;top:%3$s%%;width:%4$dpx">',
					esc_attr( (string) (float) $float['depth'] ),
					esc_attr( (string) (float) $float['x'] ),
					esc_attr( (string) (float) $float['y'] ),
					absint( $float['size'] )
				);
				$this->print_image( $float['image'], 'medium_large', 'qe-hero__float-img' );
				echo '</div>';
			}
			echo '</div>';
		}

		echo '</section>';
	}
}
