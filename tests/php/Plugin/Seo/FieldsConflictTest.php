<?php
/**
 * SEO fields and Yoast / Rank Math conflict tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Seo;

use Brain\Monkey\Functions;
use QeEdu\Core\Seo\Conflict;
use QeEdu\Core\Seo\Fields;
use QeEdu\Tests\Doubles\TestableConflict;

/**
 * Fields and conflict.
 */
final class FieldsConflictTest extends SeoTestCase {

	protected function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

	public function test_fields() {
		$fields = Fields::all();
		$this->assertSame( array( 'title', 'description', 'keyword', 'canonical', 'noindex', 'nofollow', 'og_title', 'og_description', 'og_image', 'schema' ), array_keys( $fields ) );
		foreach ( $fields as $key => $field ) {
			$this->assertNotSame( '', $field['label'], $key );
			$this->assertNotSame( '', $field['help'], $key );
		}
		$this->assertArrayHasKey( 'Course', Fields::schema_types() );
		$this->assertSame( '_qe_seo_title', Fields::meta_key( 'title' ) );
		$this->assertSame( array( 'post', 'page', 'qe_event' ), Fields::post_types() );
	}

	public function test_sanitize_and_save() {
		$this->assertSame( '1', Fields::sanitize( 'noindex', 'on' ) );
		$this->assertSame( '', Fields::sanitize( 'noindex', '0' ) );
		$this->assertSame( '', Fields::sanitize( 'noindex', '' ) );
		$this->assertSame( 'https://x.test', Fields::sanitize( 'canonical', 'https://x.test' ) );
		$this->assertSame( '5', Fields::sanitize( 'og_image', '5' ) );
		$this->assertSame( '', Fields::sanitize( 'og_image', '0' ) );
		$this->assertSame( 'Course', Fields::sanitize( 'schema', 'Course' ) );
		$this->assertSame( '', Fields::sanitize( 'schema', 'Bogus' ) );
		$this->assertSame( "a\nb", Fields::sanitize( 'description', "a\nb" ) );
		$this->assertSame( 'T', Fields::sanitize( 'title', '<b>T</b>' ) );
		$this->assertSame( '', Fields::sanitize( 'title', array() ) );

		Fields::save( 3, 'title', 'Hello' );
		$this->assertSame( 'Hello', Fields::get( 3, 'title' ) );
		Fields::save( 3, 'title', '' );
		$this->assertSame( '', Fields::get( 3, 'title' ) );
	}

	public function test_active_plugin_detection() {
		$this->assertSame( '', Conflict::active_plugin() );
		$this->assertTrue( Conflict::output_enabled() );

		$this->seo_plugin( 'yoast' );
		$this->assertSame( 'yoast', Conflict::active_plugin() );
		$this->assertFalse( Conflict::output_enabled() );

		$this->seo_plugin( 'rankmath' );
		$this->assertSame( 'rankmath', Conflict::active_plugin() );
	}

	public function test_register() {
		$conflict = new Conflict();
		$conflict->register();
		$this->assertNotFalse( has_action( 'admin_notices', array( $conflict, 'notice' ) ) );
		$this->assertNotFalse( has_action( 'admin_post_qe_edu_seo_import', array( $conflict, 'handle_import' ) ) );
	}

	public function test_key_maps() {
		$this->assertSame( '_yoast_wpseo_metadesc', Conflict::key_map( 'yoast' )['description'] );
		$this->assertSame( 'rank_math_description', Conflict::key_map( 'rankmath' )['description'] );
	}

	/**
	 * Posts 1 and 2 with SEO values; 2 already has a Yoast title.
	 *
	 * @return void
	 */
	private function posts() {
		Functions\when( 'get_posts' )->alias(
			static function ( $args ) {
				return '_qe_seo_title' === $args['meta_key'] ? array( 1, 2 ) : array( 2 );
			}
		);
		$this->meta[1] = array(
			'_qe_seo_title'       => 'One',
			'_qe_seo_description' => 'Desc one',
			'_qe_seo_noindex'     => '1',
		);
		$this->meta[2] = array(
			'_qe_seo_title'      => 'Two',
			'_yoast_wpseo_title' => 'Existing',
		);
	}

	public function test_import_into_yoast() {
		$this->posts();
		$this->assertSame( 3, Conflict::import( 'yoast' ) );
		$this->assertSame( 'One', $this->meta[1]['_yoast_wpseo_title'] );
		$this->assertSame( 'Desc one', $this->meta[1]['_yoast_wpseo_metadesc'] );
		$this->assertSame( '1', $this->meta[1]['_yoast_wpseo_meta-robots-noindex'] );
		$this->assertSame( 'Existing', $this->meta[2]['_yoast_wpseo_title'], 'Never overwrites' );
	}

	public function test_import_into_rank_math() {
		$this->posts();
		$this->assertSame( 4, Conflict::import( 'rankmath' ) );
		$this->assertSame( 'Two', $this->meta[2]['rank_math_title'] );
		$this->assertSame( array( 'noindex' ), $this->meta[1]['rank_math_robots'] );
	}

	public function test_notice() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_nonce_field' )->justReturn( '' );
		Functions\when( 'submit_button' )->alias(
			static function ( $text ) {
				echo '<button>' . $text . '</button>';
			}
		);
		$conflict = new Conflict();
		$this->assertSame( '', $this->capture( array( $conflict, 'notice' ) ) );

		$this->seo_plugin( 'yoast' );
		$_GET['qe_seo_imported'] = '3';
		$out                     = $this->capture( array( $conflict, 'notice' ) );
		$this->assertStringContainsString( 'Copied 3 SEO values.', $out );
		$this->assertStringContainsString( 'Yoast SEO is active', $out );
		$this->assertStringContainsString( 'value="qe_edu_seo_import"', $out );

		$_GET = array();
		$this->seo_plugin( 'rankmath' );
		$out = $this->capture( array( $conflict, 'notice' ) );
		$this->assertStringContainsString( 'Rank Math is active', $out );
		$this->assertStringNotContainsString( 'Copied', $out );

		Functions\when( 'current_user_can' )->justReturn( false );
		$this->assertSame( '', $this->capture( array( $conflict, 'notice' ) ) );
	}

	public function test_handle_import() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'denied' ) );
		try {
			( new TestableConflict() )->handle_import();
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'denied', $e->getMessage() );
		}

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'check_admin_referer' )->twice()->with( 'qe_edu_seo_import' );
		$urls = array();
		Functions\when( 'wp_safe_redirect' )->alias(
			static function ( $url ) use ( &$urls ) {
				$urls[] = $url;
			}
		);

		$conflict = new TestableConflict();
		$conflict->handle_import();
		$this->assertStringContainsString( 'qe_seo_imported=0', $urls[0] );

		$this->posts();
		$this->seo_plugin( 'yoast' );
		$conflict->handle_import();
		$this->assertStringContainsString( 'qe_seo_imported=3', $urls[1] );
		$this->assertSame( 2, $conflict->halts );
	}

	public function test_halt_exits() {
		$exited = 0;
		\Patchwork\redefine(
			'exit',
			static function () use ( &$exited ) {
				++$exited;
			}
		);
		( new \ReflectionMethod( Conflict::class, 'halt' ) )->invoke( new Conflict() );
		$this->assertSame( 1, $exited );
	}
}
