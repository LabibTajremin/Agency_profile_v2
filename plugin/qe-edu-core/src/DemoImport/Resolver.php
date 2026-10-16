<?php
/**
 * Replace demo placeholders ({{image:key}}, {{page-url:key}}, …) in nested
 * data with real ids and URLs.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\DemoImport;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder resolver.
 */
final class Resolver {

	/**
	 * Placeholder pattern: {{kind}} or {{kind:argument}}.
	 */
	const PATTERN = '/\{\{([a-z-]+)(?::([^}]+))?\}\}/';

	/**
	 * Callback returning the value for (kind, argument).
	 *
	 * @var callable
	 */
	private $lookup;

	/**
	 * Constructor.
	 *
	 * @param callable $lookup function( string $kind, string $argument ): string.
	 */
	public function __construct( callable $lookup ) {
		$this->lookup = $lookup;
	}

	/**
	 * Resolve every placeholder in a value (recursively for arrays).
	 *
	 * @param mixed $value Value.
	 * @return mixed
	 */
	public function resolve( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( $this, 'resolve' ), $value );
		}

		if ( ! is_string( $value ) || false === strpos( $value, '{{' ) ) {
			return $value;
		}

		return (string) preg_replace_callback(
			self::PATTERN,
			function ( $found ) {
				return (string) call_user_func( $this->lookup, $found[1], isset( $found[2] ) ? $found[2] : '' );
			},
			$value
		);
	}

	/**
	 * Every argument used with a placeholder kind (e.g. the image keys).
	 *
	 * @param mixed  $value Value.
	 * @param string $kind  Kind.
	 * @return string[]
	 */
	public static function find( $value, $kind ) {
		preg_match_all( self::PATTERN, (string) wp_json_encode( $value ), $matches, PREG_SET_ORDER );

		$found = array();
		foreach ( $matches as $match ) {
			if ( $kind === $match[1] && isset( $match[2] ) ) {
				$found[ $match[2] ] = true;
			}
		}

		return array_keys( $found );
	}
}
