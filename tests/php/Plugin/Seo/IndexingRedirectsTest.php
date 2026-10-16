<?php
/**
 * Sitemap / robots.txt and redirects / 404 log tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Seo;

use Brain\Monkey\Functions;
use QeEdu\Core\Seo\Indexing;
use QeEdu\Core\Seo\Redirects;
use QeEdu\Tests\Doubles\TestableRedirects;

/**
 * Indexing and redirects.
 */
final class IndexingRedirectsTest extends SeoTestCase {

	protected function tearDown(): void {
		unset( $_SERVER['REQUEST_URI'] );
		parent::tearDown();
	}

	public function test_indexing_register() {
		$indexing = new Indexing();
		$indexing->register();
		$this->assertNotFalse( has_action( 'admin_notices', array( $indexing, 'discouraged_notice' ) ) );
		$this->assertFalse( has_filter( 'wp_sitemaps_enabled', '__return_false' ) );
		$this->assertNotFalse( has_filter( 'wp_sitemaps_posts_query_args', array( $indexing, 'exclude_noindex' ) ) );
		$this->assertNotFalse( has_filter( 'robots_txt', array( $indexing, 'robots_txt' ) ) );
	}

	public function test_indexing_register_sitemap_off() {
		$this->settings( array( 'seo' => array( 'sitemap' => false ) ) );
		( new Indexing() )->register();
		$this->assertNotFalse( has_filter( 'wp_sitemaps_enabled', '__return_false' ) );
	}

	public function test_indexing_register_with_yoast() {
		$this->seo_plugin( 'yoast' );
		Functions\expect( 'add_filter' )->never();
		Functions\expect( 'add_action' )->once()->with( 'admin_notices', \Mockery::type( 'array' ) );
		( new Indexing() )->register();
		$this->addToAssertionCount( 1 );
	}

	public function test_exclude_noindex() {
		$args = ( new Indexing() )->exclude_noindex( array( 'post_type' => 'page' ) );
		$this->assertSame( 'page', $args['post_type'] );
		$this->assertSame(
			array(
				array(
					'key'     => '_qe_seo_noindex',
					'compare' => 'NOT EXISTS',
				),
			),
			$args['meta_query']
		);
	}

	public function test_robots_txt() {
		$indexing = new Indexing();
		$base     = "User-agent: *\nDisallow: /wp-admin/\n";
		$core     = $base . "\nSitemap: https://example.test/wp-sitemap.xml\n";

		$this->assertSame( $core, $indexing->robots_txt( $base, true ), 'Adds the sitemap when core did not.' );
		$this->assertSame( $base, $indexing->robots_txt( $base, false ) );
		$this->assertSame( $core, $indexing->robots_txt( $core, true ), 'Core sitemap line is not duplicated.' );
		$this->assertSame( '', $indexing->robots_txt( '', false ) );

		$this->settings(
			array(
				'seo' => array(
					'robots_txt' => "  Disallow: /private/\r\nDisallow: /tmp/\n",
				),
			)
		);
		$this->assertSame(
			"User-agent: *\nDisallow: /wp-admin/\nDisallow: /private/\nDisallow: /tmp/\n\nSitemap: https://example.test/wp-sitemap.xml\n",
			$indexing->robots_txt( $core, true ),
			'Extra rules join the default group, before the sitemap.'
		);
		$this->assertSame( "Disallow: /private/\nDisallow: /tmp/\n", $indexing->robots_txt( '', false ) );

		$this->settings(
			array(
				'seo' => array(
					'robots_txt' => "User-agent: GPTBot\nDisallow: /",
					'sitemap'    => false,
				),
			)
		);
		$this->assertSame( "User-agent: *\nDisallow: /wp-admin/\n\nUser-agent: GPTBot\nDisallow: /\n", $indexing->robots_txt( $base, true ), 'A new group is separated by a blank line.' );
		$this->assertSame( "User-agent: GPTBot\nDisallow: /\n\nSitemap: https://x.test/s.xml\n", $indexing->robots_txt( "Sitemap: https://x.test/s.xml\n", true ) );
	}

	public function test_discouraged_notice() {
		$indexing = new Indexing();
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->options['blog_public'] = '1';
		$this->assertSame( '', $this->capture( array( $indexing, 'discouraged_notice' ) ) );

		$this->options['blog_public'] = '0';
		$html                         = $this->capture( array( $indexing, 'discouraged_notice' ) );
		$this->assertStringContainsString( 'Search engines are blocked.', $html );
		$this->assertStringContainsString( 'href="https://example.test/wp-admin/options-reading.php"', $html );

		Functions\when( 'current_user_can' )->justReturn( false );
		$this->assertSame( '', $this->capture( array( $indexing, 'discouraged_notice' ) ) );
	}

	public function test_redirects_register() {
		$redirects = new Redirects();
		$redirects->register();
		$this->assertNotFalse( has_action( 'template_redirect', array( $redirects, 'maybe_redirect' ) ) );
		$this->assertNotFalse( has_action( 'template_redirect', array( $redirects, 'log_404' ) ) );
		$this->assertNotFalse( has_action( 'qe_edu_settings_after_field', array( $redirects, 'render_log' ) ) );
		$this->assertNotFalse( has_action( 'admin_post_qe_edu_clear_404_log', array( $redirects, 'handle_clear' ) ) );
	}

	public function test_normalize_and_target() {
		$this->assertSame( '/', Redirects::normalize( '/' ) );
		$this->assertSame( '/', Redirects::normalize( '' ) );
		$this->assertSame( '/old/page', Redirects::normalize( '/Old/Page/?x=1' ) );
		$this->assertSame( '/café', Redirects::normalize( 'https://example.test/caf%C3%A9/' ) );

		$this->assertSame( '', Redirects::target( '/old' ) );
		$this->settings(
			array(
				'seo' => array(
					'redirects' => array(
						array( 'from' => '/broken' ),
						array(
							'from' => '/Old/',
							'to'   => '/new/',
						),
						array(
							'from' => '/away',
							'to'   => 'https://other.test/',
						),
					),
				),
			)
		);
		$this->assertSame( 'https://example.test/new/', Redirects::target( '/old' ) );
		$this->assertSame( 'https://other.test/', Redirects::target( '/away' ) );
		$this->assertSame( '', Redirects::target( '/broken' ) );
	}

	public function test_maybe_redirect() {
		$redirects = new TestableRedirects();
		$this->settings(
			array(
				'seo' => array(
					'redirects' => array(
						array(
							'from' => '/old',
							'to'   => '/new',
						),
					),
				),
			)
		);
		Functions\expect( 'wp_redirect' )->once()->with( 'https://example.test/new', 301, 'QE Edu' );

		$redirects->maybe_redirect();
		$this->assertSame( 0, $redirects->halts, 'No request URI.' );

		$_SERVER['REQUEST_URI'] = '/other';
		$redirects->maybe_redirect();
		$this->assertSame( 0, $redirects->halts );

		$_SERVER['REQUEST_URI'] = '/old/?utm=1';
		$redirects->maybe_redirect();
		$this->assertSame( 1, $redirects->halts );

		$this->conditionals( array( 'is_admin' ) );
		$redirects->maybe_redirect();
		$this->assertSame( 1, $redirects->halts );
	}

	public function test_log_404() {
		$redirects = new Redirects();

		$_SERVER['REQUEST_URI'] = '/missing';
		$redirects->log_404();
		$this->assertArrayNotHasKey( Redirects::LOG, $this->options, 'Not a 404.' );

		$this->conditionals( array( 'is_404' ) );
		unset( $_SERVER['REQUEST_URI'] );
		$redirects->log_404();
		$this->assertArrayNotHasKey( Redirects::LOG, $this->options, 'No request URI.' );

		$_SERVER['REQUEST_URI']          = '/missing';
		$this->options[ Redirects::LOG ] = 'corrupt';
		$redirects->log_404();
		$redirects->log_404();
		$_SERVER['REQUEST_URI'] = '/other';
		$redirects->log_404();
		$_SERVER['REQUEST_URI'] = '/missing/';
		$redirects->log_404();

		$log = $this->options[ Redirects::LOG ];
		$this->assertSame( array( '/other', '/missing' ), array_keys( $log ), 'Latest hit moves to the end.' );
		$this->assertSame( 3, $log['/missing']['count'] );
		$this->assertSame( 1, $log['/other']['count'] );
		$this->assertIsInt( $log['/other']['last'] );

		$this->settings( array( 'seo' => array( 'log_404' => false ) ) );
		$redirects->log_404();
		$this->assertSame( 3, $this->options[ Redirects::LOG ]['/missing']['count'] );
	}

	public function test_log_404_is_capped() {
		$this->conditionals( array( 'is_404' ) );
		$log = array();
		for ( $i = 0; $i < Redirects::MAX; $i++ ) {
			$log[ '/p' . $i ] = array(
				'count' => 1,
				'last'  => 1,
			);
		}
		$this->options[ Redirects::LOG ] = $log;
		$_SERVER['REQUEST_URI']          = '/new';
		( new Redirects() )->log_404();

		$log = $this->options[ Redirects::LOG ];
		$this->assertCount( Redirects::MAX, $log );
		$this->assertArrayNotHasKey( '/p0', $log );
		$this->assertArrayHasKey( '/new', $log );
	}

	public function test_render_log() {
		$redirects = new Redirects();
		$this->assertSame(
			'',
			$this->capture(
				static function () use ( $redirects ) {
					$redirects->render_log( array( 'path' => 'seo.title_template' ) );
				}
			)
		);

		$this->options[ Redirects::LOG ] = 'corrupt';
		$html                            = $this->capture(
			static function () use ( $redirects ) {
				$redirects->render_log( array( 'path' => 'seo.redirects' ) );
			}
		);
		$this->assertStringContainsString( 'Nothing logged yet.', $html );

		Functions\when( 'wp_nonce_url' )->alias(
			static function ( $url, $action ) {
				return $url . '&_wpnonce=' . $action;
			}
		);
		$this->options['date_format']    = 'Y-m-d';
		$this->options[ Redirects::LOG ] = array(
			'/rare'   => array(
				'count' => 1,
				'last'  => 1,
			),
			'/common' => array(
				'count' => 9,
				'last'  => 1,
			),
		);
		$html                            = $this->capture(
			static function () use ( $redirects ) {
				$redirects->render_log( array( 'path' => 'seo.redirects' ) );
			}
		);
		$this->assertLessThan( strpos( $html, '/rare' ), strpos( $html, '/common' ), 'Most frequent first.' );
		$this->assertStringContainsString( '<tr><td><code>/common</code></td><td>9</td><td>2026-01-01</td></tr>', $html );
		$this->assertStringContainsString( 'admin-post.php?action=qe_edu_clear_404_log&_wpnonce=qe_edu_clear_404_log', $html );
	}

	public function test_handle_clear() {
		$redirects = new TestableRedirects();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'check_admin_referer' )->once()->with( 'qe_edu_clear_404_log' );
		Functions\expect( 'wp_safe_redirect' )->once()->with( 'https://example.test/wp-admin/admin.php?page=qe-edu-settings&tab=seo&qe_notice=saved' );
		$this->options[ Redirects::LOG ] = array( '/x' => array() );

		$redirects->handle_clear();
		$this->assertArrayNotHasKey( Redirects::LOG, $this->options );
		$this->assertSame( 1, $redirects->halts );
	}

	public function test_handle_clear_denied() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'died' ) );
		$this->expectExceptionMessage( 'died' );
		( new TestableRedirects() )->handle_clear();
	}

	public function test_halt_exits() {
		$exited = 0;
		\Patchwork\redefine(
			'exit',
			static function () use ( &$exited ) {
				++$exited;
			}
		);
		( new \ReflectionMethod( Redirects::class, 'halt' ) )->invoke( new Redirects() );
		$this->assertSame( 1, $exited );
	}
}
