<?php
/**
 * Demo package, placeholder resolver and the Tools card / handler / CLI.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\DemoImport;

use Brain\Monkey\Functions;
use QeEdu\Core\DemoImport\Admin;
use QeEdu\Core\DemoImport\Package;
use QeEdu\Core\DemoImport\Resolver;
use QeEdu\Tests\Doubles\TestableDemoAdmin;
use QeEdu\Tests\Plugin\PluginTestCase;

/**
 * Package, resolver and admin.
 */
final class PackageAdminTest extends PluginTestCase {

	/** @var string */
	private $fixture;

	protected function setUp(): void {
		parent::setUp();
		$this->fixture = dirname( __DIR__, 2 ) . '/fixtures/demo';
		Functions\when( 'trailingslashit' )->alias(
			static function ( $path ) {
				return rtrim( $path, '/' ) . '/';
			}
		);
		Functions\when( 'get_permalink' )->alias(
			static function ( $id ) {
				return 'https://example.test/?p=' . $id;
			}
		);
		\WP_CLI::$commands = array();
		\WP_CLI::$messages = array();
	}

	protected function tearDown(): void {
		$_GET  = array();
		$_POST = array();
		parent::tearDown();
	}

	public function test_package() {
		$package = new Package( $this->fixture );
		$this->assertTrue( $package->valid() );
		$this->assertSame( array( 'home', 'contact', 'news', 'privacy', 'about', 'broken' ), array_keys( $package->pages() ) );
		$this->assertSame( 'home', $package->value( 'front_page' ) );
		$this->assertSame( '', $package->value( 'images' ), 'Not a string.' );
		$this->assertSame( '', $package->value( 'nothing' ) );
		$this->assertSame( array(), $package->get( 'nothing' ) );
		$this->assertSame( array(), $package->get( 'front_page' ), 'Not an array.' );
		$this->assertCount( 4, $package->get( 'posts' ) );
		$this->assertSame( array(), $package->json( 'pages/nope.json' ) );
		$this->assertSame( 'heading', $package->json( 'pages/contact.json' )[0]['widgetType'] );
		$this->assertSame( $this->fixture . '/images/a.png', $package->image_path( 'img-a' ) );
		$this->assertSame( '', $package->image_path( 'missing' ), 'File not there.' );
		$this->assertSame( '', $package->image_path( 'unknown' ) );

		$empty = new Package( dirname( $this->fixture ) . '/demo-empty' );
		$this->assertFalse( $empty->valid() );
		$this->assertSame( '', $empty->value( 'front_page' ) );
	}

	public function test_bundled_package_is_complete() {
		$package = new Package();
		$this->assertTrue( $package->valid() );
		$pages = $package->pages();
		$this->assertCount( 18, $pages );
		foreach ( array( 'home', 'about', 'what-we-do', 'study-abroad', 'international-pathways', 'language-academy', 'elp-test', 'partner-with-us', 'partner-institutions', 'recruitment-partners', 'study-mobility-program', 'careers', 'events', 'news', 'contact', 'privacy-policy', 'payment-terms', 'refund-policy' ) as $key ) {
			$this->assertArrayHasKey( $key, $pages, $key );
			if ( isset( $pages[ $key ]['file'] ) ) {
				$this->assertNotSame( array(), $package->json( $pages[ $key ]['file'] ), $key );
			}
		}
		$this->assertSame( array( 'header', 'footer', 'not-found', 'single', 'archive' ), array_column( $package->get( 'templates' ), 'key' ) );
		foreach ( $package->get( 'templates' ) as $template ) {
			$this->assertNotSame( array(), $package->json( $template['file'] ), $template['key'] );
		}

		// Every image placeholder points at a bundled file.
		$all = array( $package->manifest() );
		foreach ( array_merge( $pages, $package->get( 'templates' ) ) as $entry ) {
			if ( isset( $entry['file'] ) ) {
				$all[] = $package->json( $entry['file'] );
			}
		}
		foreach ( array( 'image', 'image-url' ) as $kind ) {
			foreach ( Resolver::find( $all, $kind ) as $key ) {
				$this->assertNotSame( '', $package->image_path( $key ), $key );
			}
		}
		foreach ( Resolver::find( $all, 'page-url' ) as $key ) {
			$this->assertArrayHasKey( $key, $pages, $key );
		}
		foreach ( Resolver::find( $all, 'menu' ) as $key ) {
			$this->assertArrayHasKey( $key, $package->get( 'menus' ), $key );
		}
	}

	public function test_resolver() {
		$resolver = new Resolver(
			static function ( $kind, $argument ) {
				return $kind . '=' . $argument;
			}
		);
		$this->assertSame(
			array(
				'a' => 'page-url=contact#form',
				'b' => array( 'home-url=' ),
				'c' => 7,
				'd' => 'plain',
				'e' => '{{ not a token }}',
			),
			$resolver->resolve(
				array(
					'a' => '{{page-url:contact}}#form',
					'b' => array( '{{home-url}}' ),
					'c' => 7,
					'd' => 'plain',
					'e' => '{{ not a token }}',
				)
			)
		);
		$this->assertSame( array( 'a', 'b' ), Resolver::find( array( '{{image:a}}', array( 'x' => '{{image:b}} {{image:a}} {{page:c}} {{home-url}}' ) ), 'image' ) );
		$this->assertSame( array(), Resolver::find( '{{home-url}}', 'home-url' ), 'Needs an argument.' );
	}

	public function test_register() {
		$admin = new Admin();
		$admin->register();
		$this->assertNotFalse( has_action( 'qe_edu_tools_tab', array( $admin, 'card' ) ) );
		$this->assertNotFalse( has_action( 'admin_post_qe_edu_demo_import', array( $admin, 'handle' ) ) );
		$this->assertSame( array( $admin, 'cli' ), \WP_CLI::$commands['qe-edu demo-import'] );
	}

	public function test_register_without_wp_cli() {
		\Patchwork\redefine(
			'class_exists',
			static function ( $name ) {
				return 'WP_CLI' === $name ? false : \Patchwork\relay();
			}
		);
		( new Admin() )->register();
		$this->assertSame( array(), \WP_CLI::$commands );
	}

	public function test_card() {
		Functions\when( 'did_action' )->justReturn( 1 );
		Functions\when( 'wp_nonce_field' )->justReturn( '' );
		Functions\when( 'submit_button' )->alias(
			static function ( $text, $type, $name, $wrap, $attrs = array() ) {
				echo '<button' . ( $attrs ? ' disabled' : '' ) . '>' . $text . '</button>';
			}
		);
		$admin               = new TestableDemoAdmin();
		$admin->fake_package = new Package( $this->fixture );

		$html = $this->capture( array( $admin, 'card' ) );
		$this->assertStringContainsString( '<h2>Import the demo</h2>', $html );
		$this->assertStringContainsString( 'action="https://example.test/wp-admin/admin-post.php"', $html );
		$this->assertStringContainsString( 'name="action" value="qe_edu_demo_import"', $html );
		$this->assertStringContainsString( '<option value="all">The whole site (recommended)</option>', $html );
		$this->assertStringContainsString( '<option value="contact">Contact</option>', $html );
		$this->assertStringContainsString( '<button>Import demo</button>', $html );
		$this->assertStringNotContainsString( 'Elementor first', $html );
		$this->assertStringNotContainsString( 'notice-success', $html );

		$admin->active = false;
		$html          = $this->capture( array( $admin, 'card' ) );
		$this->assertStringContainsString( 'Please install and activate Elementor first', $html );
		$this->assertStringContainsString( '<button disabled>', $html );

		$admin->fake_package = new Package( dirname( $this->fixture ) . '/demo-empty' );
		$html                = $this->capture( array( $admin, 'card' ) );
		$this->assertStringContainsString( 'The demo files are missing', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}

	public function test_card_result() {
		Functions\when( 'wp_nonce_field' )->justReturn( '' );
		Functions\when( 'submit_button' )->justReturn( '' );
		$admin               = new TestableDemoAdmin();
		$admin->fake_package = new Package( $this->fixture );
		$admin->active       = true;

		$_GET = array(
			'qe_demo' => 'done',
			'created' => '12',
			'skipped' => '3',
		);
		$html = $this->capture( array( $admin, 'card' ) );
		$this->assertStringContainsString( 'Demo imported: 12 items added, 3 already there (left unchanged).', $html );
		$this->assertStringContainsString( '<a href="https://example.test/">View your site</a>', $html );

		$_GET = array(
			'qe_demo' => 'done',
			'page_id' => '9',
		);
		$html = $this->capture( array( $admin, 'card' ) );
		$this->assertStringContainsString( 'Demo imported: 0 items added, 0 already there', $html );
		$this->assertStringContainsString( '<a href="https://example.test/?p=9">View the page</a>', $html );
	}

	public function test_elementor_active() {
		Functions\when( 'did_action' )->justReturn( 0 );
		$method = new \ReflectionMethod( Admin::class, 'elementor_active' );
		$this->assertFalse( $method->invoke( new Admin() ) );
		Functions\when( 'did_action' )->justReturn( 2 );
		$this->assertTrue( $method->invoke( new Admin() ) );
	}

	public function test_default_importer_and_package() {
		$admin = new Admin();
		$this->assertInstanceOf( \QeEdu\Core\DemoImport\Importer::class, ( new \ReflectionMethod( Admin::class, 'importer' ) )->invoke( $admin ) );
		$this->assertTrue( ( new \ReflectionMethod( Admin::class, 'package' ) )->invoke( $admin )->valid() );
	}

	/**
	 * A fake importer recording the scope.
	 *
	 * @return object
	 */
	private function fake_importer() {
		return new class() {
			/** @var string[] */
			public $scopes = array();

			public function run( $scope ) {
				$this->scopes[] = $scope;
				return array(
					'created' => 4,
					'skipped' => 1,
					'page'    => 'all' === $scope ? 0 : 12,
				);
			}
		};
	}

	public function test_handle() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'check_admin_referer' )->twice()->with( Admin::ACTION );
		$redirects = array();
		Functions\when( 'wp_safe_redirect' )->alias(
			static function ( $url ) use ( &$redirects ) {
				$redirects[] = $url;
			}
		);
		$admin                = new TestableDemoAdmin();
		$admin->fake_importer = $this->fake_importer();

		$admin->handle();
		$_POST['qe_demo_scope'] = 'Contact';
		$admin->handle();

		$this->assertSame( array( 'all', 'contact' ), $admin->fake_importer->scopes );
		$this->assertSame( 2, $admin->halts );
		$this->assertSame( 'https://example.test/wp-admin/admin.php?page=qe-edu-settings&tab=tools&qe_demo=done&created=4&skipped=1&page_id=0', $redirects[0] );
		$this->assertStringEndsWith( 'page_id=12', $redirects[1] );
	}

	public function test_handle_empty_scope_means_all() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'check_admin_referer' )->justReturn( true );
		Functions\when( 'wp_safe_redirect' )->justReturn( true );
		$admin                  = new TestableDemoAdmin();
		$admin->fake_importer   = $this->fake_importer();
		$_POST['qe_demo_scope'] = '!!';
		$admin->handle();
		$this->assertSame( array( 'all' ), $admin->fake_importer->scopes );
	}

	public function test_handle_denied() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'died' ) );
		$this->expectExceptionMessage( 'died' );
		( new TestableDemoAdmin() )->handle();
	}

	public function test_cli() {
		$admin                = new TestableDemoAdmin();
		$admin->fake_importer = $this->fake_importer();
		$admin->cli( array(), array() );
		$admin->cli( array(), array( 'page' => 'contact' ) );
		$this->assertSame( array( 'all', 'contact' ), $admin->fake_importer->scopes );
		$this->assertSame( 'Demo imported: 4 added, 1 already there.', \WP_CLI::$messages[0] );
	}

	public function test_halt_exits() {
		$exited = 0;
		\Patchwork\redefine(
			'exit',
			static function () use ( &$exited ) {
				++$exited;
			}
		);
		( new \ReflectionMethod( Admin::class, 'halt' ) )->invoke( new Admin() );
		$this->assertSame( 1, $exited );
	}
}
