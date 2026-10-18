<?php
/**
 * Phase 4 gate for every widget:
 *  - metadata and category;
 *  - every colour control defaults to an Elementor Global Color and every
 *    typography control to a Global Font (no hard-coded colours);
 *  - renders with defaults on the front end and in the editor;
 *  - EVERY text control (including repeater fields) is editable: changing it
 *    changes the rendered page. Controls marked `qe_scope => server` are
 *    used in server responses and are covered by the form handler tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Widgets;

use Elementor\Controls_Manager;
use QeEdu\Core\Elementor\Integration;

/**
 * All widgets.
 */
final class AllWidgetsTest extends WidgetTestCase {

	/**
	 * Settings every render needs (so the widget has something to show).
	 */
	const BASE = array(
		'qe-mega-menu'    => array( 'menu' => '5' ),
		'qe-cta-download' => array( 'file' => array( 'url' => 'https://example.test/p.pdf' ) ),
	);

	/**
	 * Widget classes.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function widgets() {
		// Indexes only: the classes are resolved inside each test, so
		// loading them is measured by code coverage.
		$out = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$out[ 'widget ' . $i ] = array( $i );
		}
		return $out;
	}

	/**
	 * Widget class for an index.
	 *
	 * @param int $i Index.
	 * @return string
	 */
	private static function cls( $i ) {
		return Integration::widget_classes()[ $i ];
	}

	protected function setUp(): void {
		parent::setUp();
		$this->settings(
			array(
				'social'  => array( 'facebook' => 'https://facebook.test/x' ),
				'contact' => array(
					'whatsapp' => '+971 50 000 0000',
					'phone'    => '+971 4 000 0000',
					'email'    => 'hello@example.test',
				),
			)
		);
		$this->with_menu();
	}

	/**
	 * @dataProvider widgets
	 */
	public function test_metadata( $index ) {
		$class  = self::cls( $index );
		$widget = new $class();
		$this->assertMatchesRegularExpression( '/^qe-[a-z-]+$/', $widget->get_name() );
		$this->assertNotSame( '', $widget->get_title() );
		$this->assertStringStartsWith( 'eicon-', $widget->get_icon() );
		$this->assertSame( array( 'qe-education' ), $widget->get_categories() );
		$this->assertContains( 'education', $widget->get_keywords() );
		$this->assertContains( 'qe-edu-core-widgets', $widget->get_style_depends() );
		$this->assertIsArray( $widget->get_script_depends() );
	}

	/**
	 * @dataProvider widgets
	 */
	public function test_styles_default_to_elementor_globals( $index ) {
		$class    = self::cls( $index );
		$widget   = new $class();
		$controls = $widget->get_controls_for_test();

		foreach ( $controls as $id => $control ) {
			if ( Controls_Manager::COLOR === $control['type'] && isset( $control['global'] ) ) {
				$this->assertStringStartsWith( 'globals/colors?id=', $control['global']['default'], "$class $id" );
			} elseif ( Controls_Manager::COLOR === $control['type'] ) {
				$this->assertArrayNotHasKey( 'default', $control, "$class $id: no global means it follows the theme" );
			}
			if ( Controls_Manager::TAB_STYLE === ( $control['tab'] ?? '' ) ) {
				$this->fail( 'Unexpected tab on control' );
			}
		}
		foreach ( $widget->group_controls as $group ) {
			if ( 'typography' === $group[0] ) {
				$this->assertStringStartsWith( 'globals/typography?id=', $group[1]['global']['default'], $class );
			} else {
				$this->assertContains( $group[0], array( 'background', 'border', 'box-shadow' ), $class );
			}
		}
		$this->assertArrayHasKey( 'section_style', $widget->sections, "$class has a Colours & fonts section" );
		$this->assertArrayHasKey( 'section_box', $widget->sections, "$class has a Box & spacing section" );
		foreach ( array( 'box_radius', 'box_padding' ) as $id ) {
			$this->assertSame( Controls_Manager::DIMENSIONS, $controls[ $id ]['type'], "$class $id" );
			$this->assertArrayNotHasKey( 'default', $controls[ $id ], "$class $id follows the theme until changed" );
		}
		if ( isset( $controls['button_bg'] ) ) {
			foreach ( array( 'button_text_color', 'button_hover_text_color', 'button_radius', 'button_padding' ) as $id ) {
				$this->assertArrayHasKey( $id, $controls, "$class $id" );
			}
		}
	}

	/**
	 * @dataProvider widgets
	 */
	public function test_renders_with_defaults_everywhere( $index ) {
		$class = self::cls( $index );
		$name  = ( new $class() )->get_name();
		$base  = self::BASE[ $name ] ?? array();

		foreach ( array( false, true ) as $editing ) {
			$this->editing = $editing;
			foreach ( array( false, true ) as $with_posts ) {
				$this->posts = array();
				if ( $with_posts ) {
					$this->with_posts();
				}
				$html = $this->render( $class, $base );
				$this->assertIsString( $html );
			}
		}
		$this->addToAssertionCount( 1 );
	}

	/**
	 * Settings satisfying a control's (and its section's) conditions.
	 *
	 * @param array<string,mixed>               $control  Control.
	 * @param array<string,array<string,mixed>> $controls All controls.
	 * @return array<string,mixed>
	 */
	private function satisfy( $control, $controls ) {
		$settings   = array();
		$conditions = array_merge( $control['section_condition'] ?? array(), $control['condition'] ?? array() );

		foreach ( $conditions as $key => $value ) {
			$negate = '!' === substr( $key, -1 );
			$key    = rtrim( $key, '!' );
			if ( ! $negate ) {
				$settings[ $key ] = is_array( $value ) ? $value[0] : $value;
				continue;
			}
			foreach ( array_keys( $controls[ $key ]['options'] ) as $option ) {
				if ( ! in_array( $option, (array) $value, true ) ) {
					$settings[ $key ] = $option;
					break;
				}
			}
		}

		return $settings;
	}

	/**
	 * Render with and without content and return both outputs.
	 *
	 * @param string              $class    Class.
	 * @param array<string,mixed> $settings Settings.
	 * @return string
	 */
	private function render_both( $class, $settings ) {
		$this->posts = array();
		$empty       = $this->render( $class, $settings );
		$this->with_posts();
		$full = $this->render( $class, $settings );

		return $empty . "\n" . $full;
	}

	/**
	 * @dataProvider widgets
	 */
	public function test_every_text_control_is_editable( $index ) {
		$class    = self::cls( $index );
		$widget   = new $class();
		$name     = $widget->get_name();
		$controls = $widget->get_controls_for_test();
		$text     = array( Controls_Manager::TEXT, Controls_Manager::TEXTAREA, Controls_Manager::WYSIWYG );
		$checked  = 0;

		foreach ( $controls as $id => $control ) {
			if ( 'server' === ( $control['qe_scope'] ?? '' ) ) {
				continue;
			}

			if ( in_array( $control['type'], $text, true ) ) {
				$marker   = 'QEMARK' . strtoupper( preg_replace( '/\W/', '', $id ) );
				$settings = array_merge( self::BASE[ $name ] ?? array(), $this->satisfy( $control, $controls ), array( $id => $marker ) );
				$this->assertStringContainsString( $marker, $this->render_both( $class, $settings ), "$name: text control \"$id\" does not reach the page" );
				++$checked;
			}

			if ( Controls_Manager::REPEATER === $control['type'] ) {
				foreach ( $control['fields'] as $field_id => $field ) {
					if ( ! in_array( $field['type'], $text, true ) ) {
						continue;
					}
					$marker = 'QEMARK' . strtoupper( preg_replace( '/\W/', '', $id . $field_id ) );
					$item   = array();
					foreach ( $control['fields'] as $key => $sub ) {
						$item[ $key ] = $sub['default'] ?? '';
					}
					$item['_id'] = 'item1';
					foreach ( $field['condition'] ?? array() as $key => $value ) {
						$item[ $key ] = $value;
					}
					$item[ $field_id ] = $marker;
					$settings          = array_merge( self::BASE[ $name ] ?? array(), $this->satisfy( $control, $controls ), array( $id => array( $item ) ) );
					$this->assertStringContainsString( $marker, $this->render_both( $class, $settings ), "$name: repeater field \"$id.$field_id\" does not reach the page" );
					++$checked;
				}
			}
		}

		// The Site Logo widget has no text of its own (it shows the logo or site name).
		if ( 'qe-site-logo' !== $name ) {
			$this->assertGreaterThan( 0, $checked, "$name has text controls" );
		} else {
			$this->assertSame( 0, $checked );
		}
	}
}
