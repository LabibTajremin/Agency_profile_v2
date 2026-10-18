<?php
/**
 * Base class for the "QE Education" Elementor widgets: shared controls
 * (every colour and font defaults to Elementor's Global Colors / Fonts) and
 * render helpers.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Base widget.
 */
abstract class Base extends Widget_Base {

	/**
	 * Elementor Global Color references.
	 */
	const COLOR_PRIMARY   = 'globals/colors?id=primary';
	const COLOR_SECONDARY = 'globals/colors?id=secondary';
	const COLOR_TEXT      = 'globals/colors?id=text';
	const COLOR_ACCENT    = 'globals/colors?id=accent';

	/**
	 * Elementor Global Font references.
	 */
	const FONT_PRIMARY   = 'globals/typography?id=primary';
	const FONT_SECONDARY = 'globals/typography?id=secondary';
	const FONT_TEXT      = 'globals/typography?id=text';
	const FONT_ACCENT    = 'globals/typography?id=accent';

	/**
	 * Widget category.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'qe-education' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'qe', 'education', 'study' );
	}

	/**
	 * Styles every widget needs.
	 *
	 * @return string[]
	 */
	public function get_style_depends() {
		return array( 'qe-edu-core-widgets' );
	}

	/**
	 * Widget script (only widgets with behaviour override this).
	 *
	 * @return string[]
	 */
	public function get_script_depends() {
		return array();
	}

	/**
	 * Sliders use Elementor's own Swiper (no second copy).
	 *
	 * @return string[]
	 */
	protected function slider_style_depends() {
		return array( 'swiper', 'e-swiper', 'qe-edu-core-widgets' );
	}

	/**
	 * Script handle for widgets with behaviour.
	 *
	 * @return string[]
	 */
	protected function behaviour_script_depends() {
		return array( 'qe-edu-core-widgets' );
	}

	/* -------------------------------------------------------------------
	 * Control helpers.
	 * ---------------------------------------------------------------- */

	/**
	 * One-line text control (dynamic tags allowed).
	 *
	 * @param string              $id      Control id.
	 * @param string              $label   Label.
	 * @param string              $default_value Default.
	 * @param array<string,mixed> $args    Extra args.
	 * @return void
	 */
	protected function add_text( $id, $label, $default_value, $args = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'       => $label,
					'type'        => Controls_Manager::TEXT,
					'default'     => $default_value,
					'label_block' => true,
					'dynamic'     => array( 'active' => true ),
				),
				$args
			)
		);
	}

	/**
	 * Multi-line text control.
	 *
	 * @param string              $id      Control id.
	 * @param string              $label   Label.
	 * @param string              $default_value Default.
	 * @param array<string,mixed> $args    Extra args.
	 * @return void
	 */
	protected function add_textarea( $id, $label, $default_value, $args = array() ) {
		$this->add_text( $id, $label, $default_value, array_merge( array( 'type' => Controls_Manager::TEXTAREA ), $args ) );
	}

	/**
	 * Link control.
	 *
	 * @param string              $id      Control id.
	 * @param string              $label   Label.
	 * @param string              $default_value Default URL.
	 * @param array<string,mixed> $args    Extra args.
	 * @return void
	 */
	protected function add_link( $id, $label, $default_value = '#', $args = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'   => $label,
					'type'    => Controls_Manager::URL,
					'default' => array( 'url' => $default_value ),
					'dynamic' => array( 'active' => true ),
				),
				$args
			)
		);
	}

	/**
	 * On/off switch.
	 *
	 * @param string              $id      Control id.
	 * @param string              $label   Label.
	 * @param string              $default_value yes|''.
	 * @param array<string,mixed> $args    Extra args.
	 * @return void
	 */
	protected function add_switch( $id, $label, $default_value = 'yes', $args = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'default'      => $default_value,
					'return_value' => 'yes',
				),
				$args
			)
		);
	}

	/**
	 * Select control.
	 *
	 * @param string               $id      Control id.
	 * @param string               $label   Label.
	 * @param array<string,string> $options Options.
	 * @param string               $default_value Default.
	 * @param array<string,mixed>  $args    Extra args.
	 * @return void
	 */
	protected function add_select( $id, $label, $options, $default_value, $args = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SELECT,
					'options' => $options,
					'default' => $default_value,
				),
				$args
			)
		);
	}

	/**
	 * Number control.
	 *
	 * @param string              $id      Control id.
	 * @param string              $label   Label.
	 * @param int|float           $default_value Default.
	 * @param int|float           $min     Minimum.
	 * @param int|float           $max     Maximum.
	 * @param array<string,mixed> $args    Extra args.
	 * @return void
	 */
	protected function add_number( $id, $label, $default_value, $min, $max, $args = array() ) {
		$this->add_control(
			$id,
			array_merge(
				array(
					'label'   => $label,
					'type'    => Controls_Manager::NUMBER,
					'default' => $default_value,
					'min'     => $min,
					'max'     => $max,
				),
				$args
			)
		);
	}

	/**
	 * Slider options (slides per view per device, autoplay, arrows, dots,
	 * static editor preview).
	 *
	 * @param int $desktop Default slides per view on desktop.
	 * @return void
	 */
	protected function add_slider_controls( $desktop = 3 ) {
		$this->start_controls_section(
			'section_slider',
			array( 'label' => __( 'Slider', 'qe-edu-core' ) )
		);
		$this->add_number( 'per_view', __( 'Cards visible on computers', 'qe-edu-core' ), $desktop, 1, 6 );
		$this->add_number( 'per_view_tablet', __( 'Cards visible on tablets', 'qe-edu-core' ), min( 2, $desktop ), 1, 4 );
		$this->add_number(
			'per_view_mobile',
			__( 'Cards visible on phones', 'qe-edu-core' ),
			1.15,
			1,
			2,
			array(
				'step'        => 0.05,
				'description' => __( '1.15 shows a peek of the next card, inviting a swipe.', 'qe-edu-core' ),
			)
		);
		$this->add_switch( 'autoplay', __( 'Move automatically', 'qe-edu-core' ), 'yes' );
		$this->add_number(
			'autoplay_delay',
			__( 'Seconds between slides', 'qe-edu-core' ),
			5,
			2,
			20,
			array( 'condition' => array( 'autoplay' => 'yes' ) )
		);
		$this->add_switch( 'arrows', __( 'Show arrows', 'qe-edu-core' ), 'yes' );
		$this->add_switch( 'dots', __( 'Show dots', 'qe-edu-core' ), 'yes' );
		$this->add_switch(
			'static_editor',
			__( 'Show as a still row while editing', 'qe-edu-core' ),
			'',
			array( 'description' => __( 'Makes editing easier; visitors still see the slider.', 'qe-edu-core' ) )
		);
		$this->end_controls_section();
	}

	/**
	 * Colour + typography controls for one element, defaulting to globals.
	 *
	 * @param string $id       Base id.
	 * @param string $label    Label.
	 * @param string $selector CSS selector inside the widget.
	 * @param string $color    Global colour reference.
	 * @param string $font     Global font reference.
	 * @return void
	 */
	protected function add_text_style( $id, $label, $selector, $color, $font ) {
		$this->add_control(
			$id . '_color',
			array(
				/* translators: %s: element name, e.g. Heading. */
				'label'     => sprintf( __( '%s colour', 'qe-edu-core' ), $label ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => $color ),
				'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => $id . '_typography',
				/* translators: %s: element name, e.g. Heading. */
				'label'    => sprintf( __( '%s font', 'qe-edu-core' ), $label ),
				'global'   => array( 'default' => $font ),
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
	}

	/**
	 * Button colours, defaulting to the accent globals.
	 *
	 * @param string $selector Button selector.
	 * @return void
	 */
	protected function add_button_style( $selector ) {
		$this->add_control(
			'button_bg',
			array(
				'label'     => __( 'Button colour', 'qe-edu-core' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => self::COLOR_PRIMARY ),
				'selectors' => array( '{{WRAPPER}} ' . $selector => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_hover_bg',
			array(
				'label'     => __( 'Button colour on hover', 'qe-edu-core' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => self::COLOR_ACCENT ),
				'selectors' => array( '{{WRAPPER}} ' . $selector . ':hover, {{WRAPPER}} ' . $selector . ':focus-visible' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Button text colour', 'qe-edu-core' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_hover_text_color',
			array(
				'label'     => __( 'Button text colour on hover', 'qe-edu-core' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} ' . $selector . ':hover, {{WRAPPER}} ' . $selector . ':focus-visible' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'label'    => __( 'Button font', 'qe-edu-core' ),
				'global'   => array( 'default' => self::FONT_ACCENT ),
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'button_border',
				'label'    => __( 'Button border', 'qe-edu-core' ),
				'selector' => '{{WRAPPER}} ' . $selector,
			)
		);
		$this->add_responsive_control(
			'button_radius',
			array(
				'label'      => __( 'Button corner radius', 'qe-edu-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( '{{WRAPPER}} ' . $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Button padding', 'qe-edu-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem' ),
				'selectors'  => array( '{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
	}

	/**
	 * A "Box & spacing" style section: background, border, corner radius,
	 * shadow and padding of the widget's cards (or its main box), plus the
	 * gap between items and the text alignment when given.
	 *
	 * Every control is empty by default, so the design follows the theme
	 * until the user changes something.
	 *
	 * @param string $selector Card / box selector inside the widget.
	 * @param array  $args {
	 *     Optional.
	 *
	 *     @type string $label      Section label.
	 *     @type string $gap        Selector of the items' container (sets `gap`).
	 *     @type string $gap_var    Set this CSS custom property instead of `gap` (sliders).
	 *     @type string $align      Selector whose text alignment can change.
	 *     @type string $min_height Selector whose minimum height can change.
	 * }
	 * @return void
	 */
	protected function add_box_style( $selector, $args = array() ) {
		$args   = array_merge(
			array(
				'label'      => __( 'Cards', 'qe-edu-core' ),
				'gap'        => '',
				'gap_var'    => '',
				'align'      => '',
				'min_height' => '',
			),
			$args
		);
		$target = '{{WRAPPER}} ' . $selector;

		$this->start_controls_section(
			'section_box',
			array(
				/* translators: %s: part of the widget, e.g. Cards. */
				'label' => sprintf( __( '%s: box & spacing', 'qe-edu-core' ), $args['label'] ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'box_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => $target,
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'box_border',
				'selector' => $target,
			)
		);
		$this->add_responsive_control(
			'box_radius',
			array(
				'label'      => __( 'Corner radius', 'qe-edu-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array( $target => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'box_shadow',
				'selector' => $target,
			)
		);
		$this->add_responsive_control(
			'box_padding',
			array(
				'label'      => __( 'Padding', 'qe-edu-core' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', 'rem', '%' ),
				'selectors'  => array( $target => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		if ( '' !== $args['min_height'] ) {
			$this->add_responsive_control(
				'box_min_height',
				array(
					'label'      => __( 'Minimum height', 'qe-edu-core' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'vh' ),
					'range'      => array(
						'px' => array(
							'min' => 0,
							'max' => 1200,
						),
						'vh' => array(
							'min' => 0,
							'max' => 100,
						),
					),
					'selectors'  => array( '{{WRAPPER}} ' . $args['min_height'] => 'min-height: {{SIZE}}{{UNIT}};' ),
				)
			);
		}
		if ( '' !== $args['gap'] ) {
			$property = '' !== $args['gap_var'] ? $args['gap_var'] : 'gap';
			$this->add_responsive_control(
				'box_gap',
				array(
					'label'      => __( 'Space between items', 'qe-edu-core' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => array( 'px', 'rem' ),
					'range'      => array(
						'px' => array(
							'min' => 0,
							'max' => 100,
						),
					),
					'selectors'  => array( '{{WRAPPER}} ' . $args['gap'] => $property . ': {{SIZE}}{{UNIT}};' ),
				)
			);
		}
		if ( '' !== $args['align'] ) {
			$this->add_responsive_control(
				'box_align',
				array(
					'label'     => __( 'Alignment', 'qe-edu-core' ),
					'type'      => Controls_Manager::CHOOSE,
					'options'   => array(
						'left'   => array(
							'title' => __( 'Left', 'qe-edu-core' ),
							'icon'  => 'eicon-text-align-left',
						),
						'center' => array(
							'title' => __( 'Centre', 'qe-edu-core' ),
							'icon'  => 'eicon-text-align-center',
						),
						'right'  => array(
							'title' => __( 'Right', 'qe-edu-core' ),
							'icon'  => 'eicon-text-align-right',
						),
					),
					'selectors' => array( '{{WRAPPER}} ' . $args['align'] => 'text-align: {{VALUE}};' ),
				)
			);
		}
		$this->end_controls_section();
	}

	/**
	 * Start the standard "Style" section.
	 *
	 * @return void
	 */
	protected function start_style_section() {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Colours & fonts', 'qe-edu-core' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
	}

	/* -------------------------------------------------------------------
	 * Render helpers.
	 * ---------------------------------------------------------------- */

	/**
	 * Print an optional heading.
	 *
	 * @param string $text  Text.
	 * @param string $tag   Tag (h1–h6, p, div).
	 * @param string $css_class CSS class.
	 * @return void
	 */
	protected function print_heading( $text, $tag, $css_class ) {
		if ( '' === trim( (string) $text ) ) {
			return;
		}

		$tag = in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ), true ) ? $tag : 'h2';

		printf( '<%1$s class="%2$s">%3$s</%1$s>', tag_escape( $tag ), esc_attr( $css_class ), wp_kses_post( $text ) );
	}

	/**
	 * Print an optional paragraph.
	 *
	 * @param string $text  Text.
	 * @param string $css_class CSS class.
	 * @return void
	 */
	protected function print_text( $text, $css_class ) {
		if ( '' === trim( (string) $text ) ) {
			return;
		}

		printf( '<p class="%1$s">%2$s</p>', esc_attr( $css_class ), wp_kses_post( nl2br( (string) $text ) ) );
	}

	/**
	 * Print a link/button.
	 *
	 * @param string              $key   Unique render-attribute key.
	 * @param string              $text  Button text.
	 * @param array<string,mixed> $link  Elementor URL value.
	 * @param string              $css_class CSS class.
	 * @param string              $context   Hidden text naming the target, for
	 *                                       screen readers ("Learn more: Visas").
	 * @return void
	 */
	protected function print_button( $key, $text, $link, $css_class = 'qe-btn', $context = '' ) {
		if ( '' === trim( (string) $text ) ) {
			return;
		}

		$this->add_render_attribute( $key, 'class', $css_class );
		if ( is_array( $link ) && ! empty( $link['url'] ) ) {
			$this->add_link_attributes( $key, $link );
			printf(
				'<a %1$s>%2$s%3$s</a>',
				$this->get_render_attribute_string( $key ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor escapes attributes.
				esc_html( $text ),
				'' !== $context ? '<span class="qe-sr">: ' . esc_html( $context ) . '</span>' : '' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped here.
			);
			return;
		}

		printf( '<span %1$s>%2$s</span>', $this->get_render_attribute_string( $key ), esc_html( $text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Print an image from an Elementor media value or an attachment id. Alt
	 * text comes from the Media Library, falling back to `$alt`.
	 *
	 * @param array<string,mixed>|int $media Media value or attachment id.
	 * @param string                  $size  Image size.
	 * @param string                  $css_class CSS class.
	 * @param string                  $alt   Fallback alt text.
	 * @param array<string,string>    $attrs Extra attributes.
	 * @return bool Whether an image was printed.
	 */
	protected function print_image( $media, $size, $css_class, $alt = '', $attrs = array() ) {
		$id  = is_array( $media ) ? absint( isset( $media['id'] ) ? $media['id'] : 0 ) : absint( $media );
		$url = is_array( $media ) && ! empty( $media['url'] ) ? (string) $media['url'] : '';

		// `alt` in $attrs forces the alt text (e.g. '' for a decorative image).
		$forced = array_key_exists( 'alt', $attrs ) ? (string) $attrs['alt'] : null;
		unset( $attrs['alt'] );

		if ( $id ) {
			$alt_text = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
			$html     = wp_get_attachment_image(
				$id,
				$size,
				false,
				array_merge(
					array(
						'class' => $css_class,
						'alt'   => null !== $forced ? $forced : ( '' !== $alt_text ? $alt_text : $alt ),
					),
					$attrs
				)
			);
			if ( '' !== $html ) {
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated markup.
				return true;
			}
		}

		if ( '' !== $url ) {
			$extra = '';
			foreach ( $attrs as $name => $value ) {
				$extra .= sprintf( ' %1$s="%2$s"', esc_attr( $name ), esc_attr( $value ) );
			}
			printf( '<img src="%1$s" class="%2$s" alt="%3$s" loading="lazy" decoding="async"%4$s>', esc_url( $url ), esc_attr( $css_class ), esc_attr( null !== $forced ? $forced : $alt ), $extra ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			return true;
		}

		return false;
	}

	/**
	 * Load the attachments of many images at once: two queries for the whole
	 * list instead of two per image when each is printed.
	 *
	 * @param array<int,array<string,mixed>> $items Items.
	 * @param string[]                       $keys  Keys holding a media value or attachment id.
	 * @return void
	 */
	protected function prime_images( $items, $keys = array( 'image' ) ) {
		$ids = array();
		foreach ( $items as $item ) {
			foreach ( $keys as $key ) {
				$media = isset( $item[ $key ] ) ? $item[ $key ] : 0;
				$id    = is_array( $media ) ? absint( isset( $media['id'] ) ? $media['id'] : 0 ) : absint( $media );
				if ( $id ) {
					$ids[ $id ] = $id;
				}
			}
		}

		if ( $ids ) {
			_prime_post_caches( array_values( $ids ), false, true );
		}
	}

	/**
	 * Print an Elementor icon value.
	 *
	 * @param array<string,mixed> $icon Icon value.
	 * @return void
	 */
	protected function print_icon( $icon ) {
		if ( is_array( $icon ) && ! empty( $icon['value'] ) ) {
			Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
		}
	}

	/**
	 * A message shown only inside the Elementor editor (e.g. "No events yet").
	 *
	 * @param string $message Message.
	 * @return void
	 */
	protected function print_editor_notice( $message ) {
		if ( $this->is_edit_mode() ) {
			printf( '<div class="qe-editor-notice">%s</div>', esc_html( $message ) );
		}
	}

	/**
	 * Whether the Elementor editor is rendering.
	 *
	 * @return bool
	 */
	public function is_edit_mode() {
		return isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();
	}

	/**
	 * Repeater items with every field present (Elementor can hand over items
	 * that lack fields added later, or defaults without every key).
	 *
	 * @param mixed               $items    Repeater value.
	 * @param array<string,mixed> $defaults Field defaults.
	 * @return array<int,array<string,mixed>>
	 */
	public static function items_with( $items, $defaults ) {
		$out = array();
		foreach ( is_array( $items ) ? $items : array() as $item ) {
			if ( is_array( $item ) ) {
				$out[] = array_merge( $defaults, $item );
			}
		}

		return $out;
	}

	/**
	 * Swiper data attributes for a slider wrapper.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	protected function slider_attributes( $settings ) {
		$config = array(
			'perView'       => (float) $settings['per_view'],
			'perViewTablet' => (float) $settings['per_view_tablet'],
			'perViewMobile' => (float) $settings['per_view_mobile'],
			'autoplay'      => 'yes' === $settings['autoplay'] ? (int) $settings['autoplay_delay'] * 1000 : 0,
			'arrows'        => 'yes' === $settings['arrows'],
			'dots'          => 'yes' === $settings['dots'],
			'static'        => 'yes' === $settings['static_editor'] && $this->is_edit_mode(),
		);

		return sprintf(
			' data-qe-slider="%1$s" style="--qe-per-view:%2$s;--qe-per-view-tablet:%3$s;--qe-per-view-mobile:%4$s"',
			esc_attr( (string) wp_json_encode( $config ) ),
			esc_attr( (string) $config['perView'] ),
			esc_attr( (string) $config['perViewTablet'] ),
			esc_attr( (string) $config['perViewMobile'] )
		);
	}

	/**
	 * Arrows and dots markup for a slider.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return void
	 */
	protected function print_slider_nav( $settings ) {
		if ( 'yes' === $settings['arrows'] ) {
			printf(
				'<button type="button" class="qe-slider__arrow qe-slider__arrow--prev" aria-label="%1$s"></button><button type="button" class="qe-slider__arrow qe-slider__arrow--next" aria-label="%2$s"></button>',
				esc_attr__( 'Previous slide', 'qe-edu-core' ),
				esc_attr__( 'Next slide', 'qe-edu-core' )
			);
		}
		if ( 'yes' === $settings['dots'] ) {
			echo '<div class="qe-slider__dots"></div>';
		}
	}

	/**
	 * Id of the post / template this widget is saved in (for form lookups).
	 *
	 * @return int
	 */
	protected function document_id() {
		$documents = isset( \Elementor\Plugin::$instance->documents ) ? \Elementor\Plugin::$instance->documents : null;
		$document  = $documents ? $documents->get_current() : null;

		return $document ? (int) $document->get_main_id() : (int) get_the_ID();
	}

	/**
	 * Posts of a content type for a widget.
	 *
	 * @param string              $type  Post type.
	 * @param int                 $count How many (-1 for all, capped at 100).
	 * @param array<string,mixed> $args  Extra query args.
	 * @return \WP_Post[]
	 */
	protected function posts( $type, $count, $args = array() ) {
		return get_posts(
			array_merge(
				array(
					'post_type'        => $type,
					'post_status'      => 'publish',
					'posts_per_page'   => $count > 0 ? min( 100, (int) $count ) : 100,
					'orderby'          => array(
						'menu_order' => 'ASC',
						'date'       => 'DESC',
					),
					'no_found_rows'    => true,
					'suppress_filters' => false,
				),
				$args
			)
		);
	}
}
