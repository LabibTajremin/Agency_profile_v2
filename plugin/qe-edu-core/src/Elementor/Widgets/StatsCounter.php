<?php
/**
 * Stats Counter: numbers that count up when scrolled into view.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Repeater;

defined( 'ABSPATH' ) || exit;

/**
 * Stats counter.
 */
class StatsCounter extends Base {

	/**
	 * Name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'qe-stats';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Stats Counter', 'qe-edu' );
	}

	/**
	 * Icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-counter';
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
		$this->start_controls_section( 'section_content', array( 'label' => __( 'Numbers', 'qe-edu' ) ) );
		$items = new Repeater();
		$items->add_control(
			'number',
			array(
				'label'   => __( 'Number', 'qe-edu' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 100,
				'min'     => 0,
			)
		);
		$items->add_control(
			'prefix',
			array(
				'label'   => __( 'Before the number', 'qe-edu' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$items->add_control(
			'suffix',
			array(
				'label'   => __( 'After the number', 'qe-edu' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '+',
			)
		);
		$items->add_control(
			'label',
			array(
				'label'       => __( 'Label', 'qe-edu' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			)
		);
		$this->add_control(
			'items',
			array(
				'label'       => __( 'Numbers', 'qe-edu' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $items->get_controls(),
				'title_field' => '{{{ prefix }}}{{{ number }}}{{{ suffix }}} {{{ label }}}',
				'default'     => array(
					array(
						'prefix' => '',
						'number' => 15,
						'suffix' => '+',
						'label'  => __( 'Years of excellence', 'qe-edu' ),
					),
					array(
						'prefix' => '',
						'number' => 45000,
						'suffix' => '+',
						'label'  => __( 'Programmes to choose from', 'qe-edu' ),
					),
					array(
						'prefix' => '',
						'number' => 500,
						'suffix' => '+',
						'label'  => __( 'Partner institutions', 'qe-edu' ),
					),
					array(
						'prefix' => '',
						'number' => 30,
						'suffix' => '+',
						'label'  => __( 'Study destinations', 'qe-edu' ),
					),
				),
			)
		);
		$this->add_number( 'duration', __( 'Count-up time (seconds)', 'qe-edu' ), 2, 0, 10, array( 'step' => 0.5 ) );
		$this->end_controls_section();

		$this->start_style_section();
		$this->add_text_style( 'number', __( 'Number', 'qe-edu' ), '.qe-stat__number', self::COLOR_PRIMARY, self::FONT_PRIMARY );
		$this->add_text_style( 'label', __( 'Label', 'qe-edu' ), '.qe-stat__label', self::COLOR_TEXT, self::FONT_TEXT );
		$this->end_controls_section();
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render() {
		$s = $this->get_settings_for_display();

		printf( '<div class="qe-stats" data-qe-counter data-duration="%s">', esc_attr( (string) (float) $s['duration'] ) );
		$items = self::items_with(
			$s['items'],
			array(
				'number' => 0,
				'prefix' => '',
				'suffix' => '',
				'label'  => '',
			)
		);
		foreach ( $items as $item ) {
			$number = (float) $item['number'];
			printf(
				'<div class="qe-stat"><p class="qe-stat__number"><span>%1$s</span><span class="qe-stat__value" data-to="%2$s">%3$s</span><span>%4$s</span></p><p class="qe-stat__label">%5$s</p></div>',
				esc_html( $item['prefix'] ),
				esc_attr( (string) $number ),
				esc_html( number_format_i18n( $number ) ),
				esc_html( $item['suffix'] ),
				esc_html( $item['label'] )
			);
		}
		echo '</div>';
	}
}
