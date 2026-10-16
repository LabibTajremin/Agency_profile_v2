<?php
/**
 * Loads the theme's /inc files once, inside a test so coverage counts the
 * top-level hook registrations, and records which hooks were added.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests;

/**
 * Theme loader.
 */
final class ThemeLoader {

	/**
	 * Hooks registered while loading: list of [type, hook, callback, priority, args].
	 *
	 * @var array<int,array<int,mixed>>
	 */
	public static $hooks = array();

	/**
	 * Whether the files are loaded.
	 *
	 * @var bool
	 */
	private static $loaded = false;

	/**
	 * Load the theme files (Brain Monkey must be set up).
	 *
	 * @return void
	 */
	public static function load() {
		if ( self::$loaded ) {
			return;
		}
		self::$loaded = true;

		$record = static function ( $type ) {
			return static function ( $hook, $callback, $priority = 10, $args = 1 ) use ( $type ) {
				ThemeLoader::$hooks[] = array( $type, $hook, $callback, $priority, $args );
				return true;
			};
		};

		\Brain\Monkey\Functions\when( 'add_action' )->alias( $record( 'action' ) );
		\Brain\Monkey\Functions\when( 'add_filter' )->alias( $record( 'filter' ) );

		foreach ( array( 'template-tags', 'setup', 'enqueue', 'elementor-compat', 'customizer', 'tgmpa' ) as $file ) {
			require_once QE_EDU_DIR . '/inc/' . $file . '.php';
		}
	}

	/**
	 * Whether a hook was registered.
	 *
	 * @param string $hook     Hook name.
	 * @param string $callback Callback.
	 * @param int    $priority Priority.
	 * @return bool
	 */
	public static function has( $hook, $callback, $priority = 10 ) {
		foreach ( self::$hooks as $entry ) {
			if ( $entry[1] === $hook && $entry[2] === $callback && $entry[3] === $priority ) {
				return true;
			}
		}
		return false;
	}
}
