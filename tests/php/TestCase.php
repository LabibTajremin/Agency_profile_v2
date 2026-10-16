<?php
/**
 * Base test case: Brain Monkey lifecycle plus common WordPress stubs.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Base test case.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Fake option store used by get_option/update_option stubs.
	 *
	 * @var array<string,mixed>
	 */
	protected $options = array();

	/**
	 * Fake post meta store: [post_id][key] => value.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected $meta = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();

		$this->options = array();
		$this->meta    = array();

		Functions\when( 'get_option' )->alias(
			function ( $key, $fallback = false ) {
				return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $fallback;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( $key, $value ) {
				$this->options[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			function ( $key ) {
				unset( $this->options[ $key ] );
				return true;
			}
		);
		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key = '', $single = false ) {
				$value = isset( $this->meta[ $post_id ][ $key ] ) ? $this->meta[ $post_id ][ $key ] : '';
				return $single ? $value : ( '' === $value ? array() : array( $value ) );
			}
		);
		Functions\when( 'update_post_meta' )->alias(
			function ( $post_id, $key, $value ) {
				$this->meta[ $post_id ][ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_post_meta' )->alias(
			function ( $post_id, $key ) {
				unset( $this->meta[ $post_id ][ $key ] );
				return true;
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			static function ( $value ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
			}
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'wp_timezone' )->alias(
			static function () {
				return new \DateTimeZone( 'Asia/Dhaka' );
			}
		);
		Functions\when( 'wp_date' )->alias(
			static function ( $format ) {
				return gmdate( $format, 1767225600 ); // 2026-01-01.
			}
		);
		Functions\when( 'get_bloginfo' )->alias(
			static function ( $show = '' ) {
				return 'description' === $show ? 'Study abroad' : 'Test Site';
			}
		);
		Functions\when( 'home_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.test' . $path;
			}
		);
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $data, $flags = 0 ) {
				return json_encode( $data, $flags );
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Capture echoed output.
	 *
	 * @param callable $callback Callback.
	 * @return string
	 */
	protected function capture( callable $callback ) {
		ob_start();
		$callback();
		return (string) ob_get_clean();
	}
}
