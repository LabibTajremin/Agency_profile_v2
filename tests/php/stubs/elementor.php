<?php
/**
 * Minimal Elementor stubs. The fake widget base records every control so
 * tests can render a widget with its defaults and change every text control.
 *
 * @package QeEdu\Tests
 */

// phpcs:disable

namespace Elementor {

	class Plugin {
		/** @var object */
		public static $instance;
	}

	class Controls_Manager {
		const TAB_CONTENT = 'content';
		const TAB_STYLE   = 'style';
		const TEXT        = 'text';
		const TEXTAREA    = 'textarea';
		const WYSIWYG     = 'wysiwyg';
		const NUMBER      = 'number';
		const SELECT      = 'select';
		const SWITCHER    = 'switcher';
		const URL         = 'url';
		const MEDIA       = 'media';
		const ICONS       = 'icons';
		const COLOR       = 'color';
		const SLIDER      = 'slider';
		const DIMENSIONS  = 'dimensions';
		const REPEATER    = 'repeater';
		const CHOOSE      = 'choose';
		const HEADING     = 'heading';
		const DATE_TIME   = 'date_time';
		const GALLERY     = 'gallery';
		const HIDDEN      = 'hidden';
		const RAW_HTML    = 'raw_html';
	}

	class Repeater {
		public $controls = array();

		public function add_control( $id, $args ) {
			$this->controls[ $id ] = $args;
		}

		public function add_responsive_control( $id, $args ) {
			$this->controls[ $id ] = $args;
		}

		public function add_group_control( $type, $args ) {}

		public function get_controls() {
			return $this->controls;
		}
	}

	class Group_Control_Typography {
		public static function get_type() {
			return 'typography';
		}
	}

	class Group_Control_Background {
		public static function get_type() {
			return 'background';
		}
	}

	class Group_Control_Box_Shadow {
		public static function get_type() {
			return 'box-shadow';
		}
	}

	class Group_Control_Border {
		public static function get_type() {
			return 'border';
		}
	}

	class Icons_Manager {
		public static $rendered = array();

		public static function render_icon( $icon, $attributes = array(), $tag = 'i' ) {
			self::$rendered[] = $icon;
			$value = is_array( $icon ) && isset( $icon['value'] ) ? $icon['value'] : '';
			echo '<i class="' . htmlspecialchars( (string) $value ) . '" aria-hidden="true"></i>';
			return true;
		}
	}

	abstract class Widget_Base {
		/** Controls registered by the widget, keyed by id. */
		public $controls = array();
		/** Sections opened. */
		public $sections = array();
		/** Group controls. */
		public $group_controls = array();
		/** Settings overriding control defaults. */
		public $test_settings = array();
		/** Render attributes. */
		public $render_attributes = array();
		/** Whether the editor is active. */
		public static $edit_mode = false;
		/** Widget id. */
		public $id = 'abc123';

		public function __construct( $data = array(), $args = null ) {
			if ( isset( $data['settings'] ) ) {
				$this->test_settings = $data['settings'];
			}
		}

		public function get_id() {
			return $this->id;
		}

		/** Condition of the open section. */
		public $section_condition = array();

		public function start_controls_section( $id, $args = array() ) {
			$this->sections[ $id ]   = $args;
			$this->section_condition = isset( $args['condition'] ) ? $args['condition'] : array();
		}

		public function end_controls_section() {
			$this->section_condition = array();
		}

		public function start_controls_tabs( $id ) {}

		public function end_controls_tabs() {}

		public function start_controls_tab( $id, $args ) {}

		public function end_controls_tab() {}

		public function add_control( $id, $args ) {
			if ( $this->section_condition ) {
				$args['section_condition'] = $this->section_condition;
			}
			$this->controls[ $id ] = $args;
		}

		public function add_responsive_control( $id, $args ) {
			$this->add_control( $id, $args );
		}

		public function add_group_control( $type, $args ) {
			$this->group_controls[] = array( $type, $args );
		}

		public function get_controls_for_test() {
			if ( ! $this->controls ) {
				$this->register_controls();
			}
			return $this->controls;
		}

		public function get_settings_for_display( $key = null ) {
			$settings = array();
			foreach ( $this->get_controls_for_test() as $id => $control ) {
				$settings[ $id ] = array_key_exists( 'default', $control ) ? $control['default'] : '';
			}
			$settings = array_merge( $settings, $this->test_settings );

			if ( null !== $key ) {
				return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
			}
			return $settings;
		}

		public function add_render_attribute( $element, $key = null, $value = null, $overwrite = false ) {
			if ( is_array( $key ) ) {
				foreach ( $key as $k => $v ) {
					$this->render_attributes[ $element ][ $k ] = (array) $v;
				}
				return $this;
			}
			if ( $overwrite || ! isset( $this->render_attributes[ $element ][ $key ] ) ) {
				$this->render_attributes[ $element ][ $key ] = array();
			}
			$this->render_attributes[ $element ][ $key ] = array_merge( $this->render_attributes[ $element ][ $key ], (array) $value );
			return $this;
		}

		public function add_link_attributes( $element, $url_control, $overwrite = false ) {
			if ( ! empty( $url_control['url'] ) ) {
				$this->add_render_attribute( $element, 'href', $url_control['url'], $overwrite );
			}
			if ( ! empty( $url_control['is_external'] ) ) {
				$this->add_render_attribute( $element, 'target', '_blank', $overwrite );
			}
			if ( ! empty( $url_control['nofollow'] ) ) {
				$this->add_render_attribute( $element, 'rel', 'nofollow', $overwrite );
			}
			return $this;
		}

		public function get_render_attribute_string( $element ) {
			if ( empty( $this->render_attributes[ $element ] ) ) {
				return '';
			}
			$out = array();
			foreach ( $this->render_attributes[ $element ] as $key => $values ) {
				$out[] = $key . '="' . htmlspecialchars( implode( ' ', $values ), ENT_QUOTES ) . '"';
			}
			return implode( ' ', $out );
		}

		public function print_render_attribute_string( $element ) {
			echo $this->get_render_attribute_string( $element );
		}

		public function is_edit_mode() {
			return self::$edit_mode;
		}

		abstract public function get_name();

		abstract protected function register_controls();

		abstract protected function render();

		public function render_for_test() {
			ob_start();
			$this->render();
			return ob_get_clean();
		}

		public function call_register_controls() {
			$this->controls = array();
			$this->register_controls();
			return $this->controls;
		}
	}

	class Utils {
		public static function get_placeholder_image_src() {
			return 'https://example.test/placeholder.png';
		}
	}
}

namespace Elementor\Core\DynamicTags {

	abstract class Tag {
		public $test_settings = array();

		public function get_settings( $key = null ) {
			return null === $key ? $this->test_settings : ( isset( $this->test_settings[ $key ] ) ? $this->test_settings[ $key ] : null );
		}

		public $controls = array();

		public function add_control( $id, $args ) {
			$this->controls[ $id ] = $args;
		}

		public function call_register_controls() {
			$this->register_controls();
			return $this->controls;
		}

		public function render_for_test() {
			ob_start();
			$this->render();
			return ob_get_clean();
		}
	}

	abstract class Data_Tag extends Tag {
		public function get_value_for_test( array $options = array() ) {
			return $this->get_value( $options );
		}
	}
}

namespace Elementor\Modules\DynamicTags {

	class Module {
		const TEXT_CATEGORY  = 'text';
		const URL_CATEGORY   = 'url';
		const IMAGE_CATEGORY = 'image';
		const MEDIA_CATEGORY = 'media';
	}
}
