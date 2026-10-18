<?php
/**
 * Form helper tests: countries, defaults, locator, rate limiter, Mailchimp.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\Forms\Countries;
use QeEdu\Core\Forms\Defaults;
use QeEdu\Core\Forms\Locator;
use QeEdu\Core\Forms\Mailchimp;
use QeEdu\Core\Forms\RateLimiter;

/**
 * Forms helpers.
 */
final class FormsTest extends PluginTestCase {

	protected function tearDown(): void {
		unset( $_SERVER['REMOTE_ADDR'] );
		parent::tearDown();
	}

	public function test_countries() {
		$all = Countries::all();
		$this->assertGreaterThan( 190, count( $all ) );
		$this->assertContains( 'United Arab Emirates', $all );
		$this->assertSame( $all, Countries::parse( Countries::as_text() ) );
		$this->assertSame( array( 'A', 'B' ), Countries::parse( " A \r\n\n B \n" ) );
	}

	public function test_defaults() {
		$this->assertArrayHasKey( 'password', Defaults::field_types() );
		$registration = Defaults::registration();
		$this->assertSame( array( 'first_name', 'last_name', 'email', 'phone', 'dob', 'country', 'password' ), array_column( $registration['fields'], '_id' ) );
		$this->assertSame( 'Sign Up', $registration['submit_label'] );
		$this->assertSame( 'builtin', Defaults::newsletter()['provider'] );
	}

	/**
	 * Store Elementor data for a post.
	 *
	 * @param int   $post_id Post id.
	 * @param array $data    Elements.
	 * @return void
	 */
	private function elementor_data( $post_id, $data ) {
		$this->meta[ $post_id ]['_elementor_data'] = wp_json_encode( $data );
	}

	public function test_locator() {
		Functions\when( 'get_post_status' )->alias( fn( $id ) => 9 === $id ? 'draft' : 'publish' );
		$this->elementor_data(
			5,
			array(
				array(
					'id'       => 'c1',
					'elType'   => 'container',
					'elements' => array(
						array(
							'id'         => 'w0',
							'widgetType' => 'heading',
							'elements'   => array(),
						),
						array(
							'id'         => 'w1',
							'widgetType' => 'qe-newsletter',
							'settings'   => array( 'button' => 'Join' ),
						),
						array(
							'id'         => 'w2',
							'widgetType' => 'qe-newsletter',
						),
					),
				),
			)
		);
		$defaults = array(
			'button'  => 'Subscribe',
			'heading' => 'H',
		);

		$this->assertSame(
			array(
				'button'  => 'Join',
				'heading' => 'H',
			),
			Locator::find( 5, 'w1', array( 'qe-newsletter' ), $defaults )
		);
		$this->assertSame( $defaults, Locator::find( 5, 'w2', array( 'qe-newsletter' ), $defaults ) );
		$this->assertNull( Locator::find( 5, 'w1', array( 'qe-registration-form' ), $defaults ), 'Wrong widget type' );
		$this->assertNull( Locator::find( 5, 'w0', array( 'qe-newsletter' ), $defaults ), 'Not a form' );
		$this->assertNull( Locator::find( 5, 'zz', array( 'qe-newsletter' ), $defaults ), 'Missing id' );
		$this->assertNull( Locator::find( 0, 'w1', array( 'qe-newsletter' ), $defaults ) );
		$this->assertNull( Locator::find( 5, '', array( 'qe-newsletter' ), $defaults ) );
		$this->assertNull( Locator::find( 9, 'w1', array( 'qe-newsletter' ), $defaults ), 'Draft' );
		$this->assertNull( Locator::find( 6, 'w1', array( 'qe-newsletter' ), $defaults ), 'No data' );
	}

	public function test_rate_limiter() {
		$store = array();
		Functions\when( 'wp_salt' )->justReturn( 'salt' );
		Functions\when( 'get_transient' )->alias(
			function ( $k ) use ( &$store ) {
				return $store[ $k ] ?? false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			function ( $k, $v, $ttl ) use ( &$store ) {
				$store[ $k ] = $v;
				return 600 === $ttl;
			}
		);

		$this->assertSame( 'qe_rl_' . md5( 'register||salt' ), RateLimiter::key( 'register' ) );
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		$this->assertSame( 'qe_rl_' . md5( 'register|203.0.113.9|salt' ), RateLimiter::key( 'register' ) );

		for ( $i = 0; $i < 5; $i++ ) {
			$this->assertTrue( RateLimiter::attempt( 'register' ) );
		}
		$this->assertFalse( RateLimiter::attempt( 'register' ) );
		$this->assertTrue( RateLimiter::attempt( 'subscribe' ), 'Separate bucket' );
	}

	public function test_mailchimp_rejects_bad_config() {
		Functions\expect( 'wp_remote_request' )->never();
		$this->assertFalse( Mailchimp::subscribe( 'nokey', 'list', 'a@b.test' ) );
		$this->assertFalse( Mailchimp::subscribe( 'abc-us21', '', 'a@b.test' ) );
		$this->assertFalse( Mailchimp::subscribe( 'abc-../evil', 'list', 'a@b.test' ) );
	}

	public function test_mailchimp_subscribes() {
		Functions\expect( 'wp_remote_request' )->once()->with(
			'https://us21.api.mailchimp.com/3.0/lists/abc123/members/' . md5( 'a@b.test' ),
			\Mockery::on(
				static function ( $args ) {
					return 'PUT' === $args['method']
						&& 'Basic ' . base64_encode( 'qe-edu:key-us21' ) === $args['headers']['Authorization']
						&& false !== strpos( $args['body'], '"status_if_new":"subscribed"' );
				}
			)
		)->andReturn( array( 'response' => array( 'code' => 200 ) ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( fn( $r ) => $r['response']['code'] );
		$this->assertTrue( Mailchimp::subscribe( 'key-us21', 'abc123', 'A@B.test' ) );
	}

	public function test_mailchimp_failure() {
		Functions\when( 'wp_remote_request' )->justReturn( array( 'response' => array( 'code' => 400 ) ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->alias( fn( $r ) => $r['response']['code'] );
		$this->assertFalse( Mailchimp::subscribe( 'key-us21', 'abc123', 'a@b.test' ) );
	}
}
