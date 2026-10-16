<?php
/**
 * Benefits + Form widget: heading, intro and benefit bullets beside the
 * registration form (the "International Students" section).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

/**
 * Benefits + form.
 */
class BenefitsForm extends Base {

	use RegistrationFormTrait;

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-benefits-form';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Benefits + Form', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-call-to-action';
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
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Text & benefits', 'qe-edu' ) ) );
		$this->add_text( 'eyebrow', __( 'Small text above the heading', 'qe-edu' ), __( 'International Students', 'qe-edu' ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu' ), __( 'Your future starts with one application', 'qe-edu' ) );
		$this->add_textarea( 'text', __( 'Introduction', 'qe-edu' ), __( 'Create your free account to explore programmes, apply to universities and get one-to-one guidance from our advisors.', 'qe-edu' ) );

		$benefits = new Repeater();
		$benefits->add_control(
			'icon',
			array(
				'label'   => __( 'Icon', 'qe-edu' ),
				'type'    => Controls_Manager::ICONS,
				'default' => array(
					'value'   => 'fas fa-check-circle',
					'library' => 'fa-solid',
				),
			)
		);
		$benefits->add_control(
			'title',
			array(
				'label'       => __( 'Bold first words', 'qe-edu' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$benefits->add_control(
			'text',
			array(
				'label'   => __( 'Text', 'qe-edu' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$this->add_control(
			'benefits',
			array(
				'label'       => __( 'Benefits', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $benefits->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'icon'  => array(
							'value'   => 'fas fa-check-circle',
							'library' => 'fa-solid',
						),
						'title' => __( 'Apply with Ease', 'qe-edu' ),
						'text'  => __( 'One simple application for universities around the world.', 'qe-edu' ),
					),
					array(
						'icon'  => array(
							'value'   => 'fas fa-check-circle',
							'library' => 'fa-solid',
						),
						'title' => __( 'Vast Program Selection', 'qe-edu' ),
						'text'  => __( 'Choose from 45,000+ programmes at leading institutions.', 'qe-edu' ),
					),
					array(
						'icon'  => array(
							'value'   => 'fas fa-check-circle',
							'library' => 'fa-solid',
						),
						'title' => __( 'End-to-End Support', 'qe-edu' ),
						'text'  => __( 'Guidance from your first question to your first day on campus.', 'qe-edu' ),
					),
				),
			)
		);
		$this->add_select(
			'layout',
			__( 'Form position', 'qe-edu' ),
			array(
				'right' => __( 'Form on the right', 'qe-edu' ),
				'left'  => __( 'Form on the left', 'qe-edu' ),
			),
			'right'
		);
		$this->end_controls_section();

		$this->start_controls_section( 'section_form', array( 'label' => __( 'Form', 'qe-edu' ) ) );
		$this->add_text( 'form_heading', __( 'Form heading', 'qe-edu' ), __( 'Register now', 'qe-edu' ) );
		$this->add_select(
			'form_source',
			__( 'Form to show', 'qe-edu' ),
			array(
				'builtin'   => __( 'Built-in registration form', 'qe-edu' ),
				'shortcode' => __( 'Another form plugin (shortcode)', 'qe-edu' ),
			),
			'builtin'
		);
		$this->add_text(
			'shortcode',
			__( 'Form shortcode', 'qe-edu' ),
			'',
			array(
				'condition'   => array( 'form_source' => 'shortcode' ),
				'description' => __( 'For example [contact-form-7 id="123"].', 'qe-edu' ),
			)
		);
		$this->end_controls_section();

		$this->register_form_controls( array( 'form_source' => 'builtin' ) );

		$this->start_style_section();
		$this->add_text_style( 'heading', __( 'Heading', 'qe-edu' ), '.qe-benefits__heading', self::COLOR_SECONDARY, self::FONT_PRIMARY );
		$this->add_text_style( 'text', __( 'Text', 'qe-edu' ), '.qe-benefits__text, .qe-benefits__item-text', self::COLOR_TEXT, self::FONT_TEXT );
		$this->add_control(
			'icon_color',
			array(
				'label'     => __( 'Icon colour', 'qe-edu' ),
				'type'      => Controls_Manager::COLOR,
				'global'    => array( 'default' => self::COLOR_PRIMARY ),
				'selectors' => array( '{{WRAPPER}} .qe-benefits__icon' => 'color: {{VALUE}}; fill: {{VALUE}};' ),
			)
		);
		$this->add_button_style( '.qe-form .qe-btn:not(.qe-btn--ghost)' );
		$this->end_controls_section();
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		printf( '<div class="qe-benefits qe-benefits--form-%s">', 'left' === $s['layout'] ? 'left' : 'right' );

		echo '<div class="qe-benefits__content">';
		$this->print_heading( $s['eyebrow'], 'p', 'qe-eyebrow' );
		$this->print_heading( $s['heading'], 'h2', 'qe-benefits__heading' );
		$this->print_text( $s['text'], 'qe-benefits__text' );

		if ( ! empty( $s['benefits'] ) ) {
			echo '<ul class="qe-benefits__list">';
			$benefits = self::items_with(
				$s['benefits'],
				array(
					'icon'  => array(),
					'title' => '',
					'text'  => '',
				)
			);
			foreach ( $benefits as $benefit ) {
				echo '<li class="qe-benefits__item"><span class="qe-benefits__icon">';
				$this->print_icon( $benefit['icon'] );
				printf(
					'</span><p class="qe-benefits__item-text"><strong>%1$s</strong> %2$s</p></li>',
					esc_html( $benefit['title'] ),
					esc_html( $benefit['text'] )
				);
			}
			echo '</ul>';
		}
		echo '</div>';

		echo '<div class="qe-benefits__form qe-form-wrap">';
		$this->print_heading( $s['form_heading'], 'h3', 'qe-benefits__form-heading' );
		if ( 'shortcode' === $s['form_source'] ) {
			echo do_shortcode( shortcode_unautop( (string) $s['shortcode'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output.
		} else {
			$this->print_form( $s );
		}
		echo '</div></div>';
	}
}
