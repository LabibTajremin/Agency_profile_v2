<?php
/**
 * Base for plugin tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Tests\TestCase;

/**
 * Plugin test case with common admin / template stubs.
 */
abstract class PluginTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.test/wp-admin/' . $path;
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			static function ( $args, $url ) {
				return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $args );
			}
		);
		Functions\when( 'checked' )->alias(
			static function ( $a, $b = true, $echo = true ) {
				return (string) $a === (string) $b ? ' checked=\'checked\'' : '';
			}
		);
		Functions\when( 'selected' )->alias(
			static function ( $a, $b = true, $echo = true ) {
				return (string) $a === (string) $b ? ' selected=\'selected\'' : '';
			}
		);
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'sanitize_email' )->alias(
			static function ( $email ) {
				return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
			}
		);
		Functions\when( 'wp_parse_url' )->alias(
			static function ( $url, $component = -1 ) {
				return parse_url( $url, $component );
			}
		);
	}

	/**
	 * Store settings in the fake option table.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @return void
	 */
	protected function settings( array $settings ) {
		$this->options['qe_edu_settings'] = $settings;
	}
}
