<?php
/**
 * FAQ: accessible accordion (native <details>) with optional FAQPage schema.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

/**
 * FAQ.
 */
class Faq extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-faq';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'FAQ', 'qe-edu-core' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-accordion';
	}

	/**
	 * Controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Questions', 'qe-edu-core' ) ) );
		$this->add_text( 'heading', __( 'Heading', 'qe-edu-core' ), __( 'Frequently asked questions', 'qe-edu-core' ) );
		$items = new Repeater();
		$items->add_control(
			'question',
			array(
				'label'       => __( 'Question', 'qe-edu-core' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$items->add_control(
			'answer',
			array(
				'label'   => __( 'Answer', 'qe-edu-core' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '',
			)
		);
		$this->add_control(
			'items',
			array(
				'label'       => __( 'Questions', 'qe-edu-core' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'title_field' => '{{{ question }}}',
				'default'     => array(
					array(
						'question' => __( 'How much does your service cost?', 'qe-edu-core' ),
						'answer'   => __( 'Our advice for students is free. Partner institutions support our work.', 'qe-edu-core' ),
					),
					array(
						'question' => __( 'When should I apply?', 'qe-edu-core' ),
						'answer'   => __( 'Ideally 9–12 months before your course starts, so there is time for visas and housing.', 'qe-edu-core' ),
					),
				),
			)
		);
		$this->add_switch( 'first_open', __( 'Open the first question', 'qe-edu-core' ) );
		$this->add_switch( 'schema', __( 'Tell Google these are FAQs (rich results)', 'qe-edu-core' ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'question', __( 'Question', 'qe-edu-core' ), '.qe-faq__question', self::COLOR_SECONDARY, self::FONT_SECONDARY );
		$this->add_text_style( 'answer', __( 'Answer', 'qe-edu-core' ), '.qe-faq__answer', self::COLOR_TEXT, self::FONT_TEXT );
		$this->end_controls_section();
		$this->add_box_style(
			'.qe-faq__item',
			array(
				'label' => __( 'Questions', 'qe-edu-core' ),
				'gap'   => '.qe-faq',
			)
		);
	}

	/**
	 * FAQPage JSON-LD for items.
	 *
	 * @param array<int,array<string,string>> $items Items.
	 * @return array<string,mixed>
	 */
	public static function schema( $items ) {
		return array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map(
				static function ( $item ) {
					return array(
						'@type'          => 'Question',
						'name'           => wp_strip_all_tags( (string) $item['question'] ),
						'acceptedAnswer' => array(
							'@type' => 'Answer',
							'text'  => wp_kses_post( (string) $item['answer'] ),
						),
					);
				},
				array_values( $items )
			),
		);
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = self::items_with(
			$s['items'],
			array(
				'question' => '',
				'answer'   => '',
			)
		);

		echo '<div class="qe-section-head">';
		$this->print_heading( $s['heading'], 'h2', 'qe-section-head__title' );
		echo '</div><div class="qe-faq">';
		foreach ( $items as $i => $item ) {
			printf(
				'<details class="qe-faq__item"%1$s><summary class="qe-faq__question">%2$s</summary><div class="qe-faq__answer">%3$s</div></details>',
				0 === $i && 'yes' === $s['first_open'] ? ' open' : '',
				esc_html( $item['question'] ),
				wp_kses_post( wpautop( (string) $item['answer'] ) )
			);
		}
		echo '</div>';

		if ( 'yes' === $s['schema'] && $items ) {
			printf( '<script type="application/ld+json">%s</script>', wp_json_encode( self::schema( $items ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with HEX_TAG.
		}
	}
}
