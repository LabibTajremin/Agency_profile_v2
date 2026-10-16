<?php
/**
 * Minimal WP_CLI stub recording commands and messages.
 *
 * @package QeEdu\Tests
 */

// phpcs:disable

if ( ! class_exists( 'WP_CLI' ) ) {
	class WP_CLI {
		/** @var array<string,callable> */
		public static $commands = array();
		/** @var string[] */
		public static $messages = array();

		public static function add_command( $name, $callable ) {
			self::$commands[ $name ] = $callable;
		}

		public static function success( $message ) {
			self::$messages[] = $message;
		}
	}
}
