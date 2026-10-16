<?php
/**
 * Settings store: read, sanitise, save, export, import and reset the single
 * `qe_edu_settings` option.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Settings;

use QeEdu\Core\Colors\Palette;

defined( 'ABSPATH' ) || exit;

/**
 * Settings.
 */
final class Settings {

	/**
	 * Option name (shared with the theme).
	 */
	const OPTION = 'qe_edu_settings';

	/**
	 * Read a nested value by dotted path.
	 *
	 * @param array<string,mixed> $data     Data.
	 * @param string              $path     Dotted path.
	 * @param mixed               $fallback Value when missing.
	 * @return mixed
	 */
	public static function get_path( $data, $path, $fallback = null ) {
		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $data ) || ! array_key_exists( $segment, $data ) ) {
				return $fallback;
			}
			$data = $data[ $segment ];
		}

		return $data;
	}

	/**
	 * Set a nested value by dotted path.
	 *
	 * @param array<string,mixed> $data  Data (by reference).
	 * @param string              $path  Dotted path.
	 * @param mixed               $value Value.
	 * @return void
	 */
	public static function set_path( &$data, $path, $value ) {
		$segments = explode( '.', $path );
		$last     = array_pop( $segments );
		$node     = &$data;

		foreach ( $segments as $segment ) {
			if ( ! isset( $node[ $segment ] ) || ! is_array( $node[ $segment ] ) ) {
				$node[ $segment ] = array();
			}
			$node = &$node[ $segment ];
		}

		$node[ $last ] = $value;
	}

	/**
	 * Remove a nested value by dotted path.
	 *
	 * @param array<string,mixed> $data Data (by reference).
	 * @param string              $path Dotted path.
	 * @return void
	 */
	public static function unset_path( &$data, $path ) {
		$segments = explode( '.', $path );
		$last     = array_pop( $segments );
		$node     = &$data;

		foreach ( $segments as $segment ) {
			if ( ! isset( $node[ $segment ] ) || ! is_array( $node[ $segment ] ) ) {
				return;
			}
			$node = &$node[ $segment ];
		}

		unset( $node[ $last ] );
	}

	/**
	 * Stored values (without defaults).
	 *
	 * @return array<string,mixed>
	 */
	public static function stored() {
		$stored = get_option( self::OPTION, array() );

		return is_array( $stored ) ? $stored : array();
	}

	/**
	 * Get a setting (stored value or the schema default).
	 *
	 * @param string $path     Dotted path.
	 * @param mixed  $fallback Used when neither a value nor a default exists.
	 * @return mixed
	 */
	public static function get( $path, $fallback = null ) {
		$value = self::get_path( self::stored(), $path, null );

		if ( null !== $value ) {
			return $value;
		}

		$fields = Schema::fields();

		return isset( $fields[ $path ] ) ? $fields[ $path ]['default'] : $fallback;
	}

	/**
	 * Every setting merged over the defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function all() {
		return array_replace_recursive( Schema::defaults(), self::stored() );
	}

	/**
	 * Sanitise one value for a field.
	 *
	 * @param array<string,mixed> $field Field definition.
	 * @param mixed               $value Raw value.
	 * @return mixed
	 */
	public static function sanitize_field( $field, $value ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );

			case 'number':
				$number = is_numeric( $value ) ? (int) $value : (int) $field['default'];
				$min    = isset( $field['min'] ) ? $field['min'] : PHP_INT_MIN;
				$max    = isset( $field['max'] ) ? $field['max'] : PHP_INT_MAX;
				return max( $min, min( $max, $number ) );

			case 'media':
			case 'template':
				return absint( $value );

			case 'select':
				$value = is_scalar( $value ) ? (string) $value : '';
				return array_key_exists( $value, $field['options'] ) ? $value : $field['default'];

			case 'swatch':
				$value = is_scalar( $value ) ? (string) $value : '';
				return ( Palette::CUSTOM === $value || array_key_exists( $value, Palette::accents() ) ) ? $value : $field['default'];

			case 'color':
				$color = Palette::normalize( $value );
				return '' !== $color ? $color : $field['default'];

			case 'url':
				return esc_url_raw( is_scalar( $value ) ? trim( (string) $value ) : '' );

			case 'email':
				return sanitize_email( is_scalar( $value ) ? (string) $value : '' );

			case 'tel':
				return preg_replace( '/[^0-9+()\-\s]/', '', is_scalar( $value ) ? (string) $value : '' );

			case 'textarea':
			case 'code':
				return sanitize_textarea_field( is_scalar( $value ) ? (string) $value : '' );

			case 'redirects':
				return self::sanitize_redirects( $value );

			default:
				return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
		}
	}

	/**
	 * Sanitise the redirects table: rows of [from, to]. `from` is a site path
	 * starting with "/"; `to` is a path or full URL. Incomplete rows are dropped.
	 *
	 * @param mixed $rows Raw rows.
	 * @return array<int,array<string,string>>
	 */
	public static function sanitize_redirects( $rows ) {
		$clean = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) || empty( $row['from'] ) || empty( $row['to'] ) ) {
				continue;
			}

			$from = '/' . ltrim( (string) wp_parse_url( sanitize_text_field( (string) $row['from'] ), PHP_URL_PATH ), '/' );
			$to   = trim( (string) $row['to'] );
			$to   = 0 === strpos( $to, '/' ) ? '/' . ltrim( sanitize_text_field( $to ), '/' ) : esc_url_raw( $to );

			if ( '/' === $from || '' === $to || $from === $to ) {
				continue;
			}

			$clean[] = array(
				'from' => $from,
				'to'   => $to,
			);
		}

		return array_slice( $clean, 0, 500 );
	}

	/**
	 * Sanitise submitted values for some tabs (all tabs when `$tabs` is null).
	 * Missing checkboxes in a submitted tab mean "off"; other missing fields
	 * keep their stored value.
	 *
	 * @param array<string,mixed> $input Raw nested input.
	 * @param string[]|null       $tabs  Tabs that were submitted.
	 * @param array<string,mixed> $base  Values to start from.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $input, $tabs = null, $base = array() ) {
		$input  = is_array( $input ) ? $input : array();
		$output = $base;

		foreach ( Schema::fields() as $path => $field ) {
			if ( null !== $tabs && ! in_array( $field['tab'], $tabs, true ) ) {
				continue;
			}

			$raw = self::get_path( $input, $path, null );

			if ( null === $raw ) {
				if ( 'checkbox' === $field['type'] && null !== $tabs ) {
					self::set_path( $output, $path, false );
				}
				continue;
			}

			self::set_path( $output, $path, self::sanitize_field( $field, $raw ) );
		}

		return $output;
	}

	/**
	 * Save submitted values for one tab, keeping every other tab.
	 *
	 * @param string              $tab   Tab id.
	 * @param array<string,mixed> $input Raw nested input.
	 * @return array<string,mixed> The saved settings.
	 */
	public static function save_tab( $tab, $input ) {
		$settings = self::sanitize( $input, array( $tab ), self::stored() );
		update_option( self::OPTION, $settings );

		return $settings;
	}

	/**
	 * Export as pretty JSON.
	 *
	 * @return string
	 */
	public static function export() {
		return (string) wp_json_encode(
			array(
				'plugin'   => 'qe-edu-core',
				'version'  => QE_EDU_CORE_VERSION,
				'settings' => self::stored(),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}

	/**
	 * Import from JSON (as produced by export()). Everything is sanitised.
	 *
	 * @param string $json JSON.
	 * @return bool Whether the import succeeded.
	 */
	public static function import( $json ) {
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) || ! isset( $data['plugin'], $data['settings'] ) || 'qe-edu-core' !== $data['plugin'] || ! is_array( $data['settings'] ) ) {
			return false;
		}

		update_option( self::OPTION, self::sanitize( $data['settings'] ) );

		return true;
	}

	/**
	 * Reset one tab to its defaults.
	 *
	 * @param string $tab Tab id.
	 * @return bool Whether the tab exists.
	 */
	public static function reset_tab( $tab ) {
		$tabs = Schema::tabs();
		if ( ! isset( $tabs[ $tab ] ) ) {
			return false;
		}

		$settings = self::stored();
		foreach ( Schema::fields() as $path => $field ) {
			if ( $field['tab'] === $tab ) {
				self::unset_path( $settings, $path );
			}
		}

		update_option( self::OPTION, $settings );

		return true;
	}
}
