<?php
/**
 * Demo importer tests, against an in-memory fake of the WordPress APIs.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\DemoImport;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use QeEdu\Core\DemoImport\Importer;
use QeEdu\Core\DemoImport\Package;
use QeEdu\Tests\Plugin\PluginTestCase;

/**
 * Importer.
 */
final class ImporterTest extends PluginTestCase {

	/** @var array<int,array<string,mixed>> Fake posts table. */
	private $posts = array();
	/** @var array<int,array<string,mixed>> Fake term meta. */
	private $term_meta = array();
	/** @var array<int,array<string,mixed>> Menu items added. */
	private $items = array();
	/** @var array<int,string> Menus created: id => name. */
	private $menus = array();
	/** @var array<string,int> Theme menu locations. */
	private $locations = array( 'topbar' => 77 );
	/** @var array<string,mixed> Recorded calls. */
	private $calls = array();
	/** @var int Next id. */
	private $next = 100;
	/** @var bool */
	private $upload_error = false;
	/** @var bool */
	private $attach_fail = false;

	protected function setUp(): void {
		parent::setUp();

		$this->options['show_on_front'] = 'posts';
		\Elementor\Plugin::$instance = (object) array();

		Functions\when( 'wp_raise_memory_limit' )->justReturn( '256M' );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'get_users' )->justReturn( array( '42' ) );
		Functions\when( 'wp_slash' )->returnArg();
		Functions\when( 'trailingslashit' )->alias(
			static function ( $path ) {
				return rtrim( $path, '/' ) . '/';
			}
		);
		Functions\when( 'is_wp_error' )->alias(
			static function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);
		Functions\when( 'current_time' )->justReturn( 1767225600 );
		Functions\when( 'get_permalink' )->alias(
			static function ( $id ) {
				return 'https://example.test/?p=' . $id;
			}
		);
		Functions\when( 'wp_get_attachment_url' )->alias(
			static function ( $id ) {
				return 'https://example.test/up/' . $id . '.png';
			}
		);
		Functions\when( 'get_posts' )->alias(
			function ( $args ) {
				foreach ( $this->posts as $id => $post ) {
					if ( in_array( $post['post_type'], (array) $args['post_type'], true )
						&& isset( $this->meta[ $id ][ $args['meta_key'] ] )
						&& $this->meta[ $id ][ $args['meta_key'] ] === $args['meta_value'] ) {
						return array( $id );
					}
				}
				return array();
			}
		);
		Functions\when( 'wp_insert_post' )->alias(
			function ( $args ) {
				if ( 0 === strpos( $args['post_title'], 'Broken' ) ) {
					return new \WP_Error( 'nope', 'Nope' );
				}
				$id                 = $this->next++;
				$this->posts[ $id ] = $args;
				return $id;
			}
		);
		Functions\when( 'wp_update_post' )->alias(
			function ( $args ) {
				$this->posts[ $args['ID'] ] = array_merge( $this->posts[ $args['ID'] ], $args );
				return $args['ID'];
			}
		);
		Functions\when( 'get_page_by_path' )->alias(
			function ( $slug ) {
				foreach ( $this->posts as $id => $post ) {
					if ( 'page' === $post['post_type'] && isset( $post['post_name'] ) && $slug === $post['post_name'] ) {
						return (object) array_merge(
							array(
								'post_date_gmt'     => '',
								'post_modified_gmt' => '',
							),
							$post,
							array( 'ID' => $id )
						);
					}
				}
				return null;
			}
		);
		Functions\when( 'wp_upload_bits' )->alias(
			function ( $name ) {
				return array(
					'file'  => '/uploads/' . $name,
					'error' => $this->upload_error ? 'Disk full' : false,
				);
			}
		);
		Functions\when( 'wp_check_filetype' )->justReturn(
			array(
				'ext'  => 'png',
				'type' => 'image/png',
			)
		);
		Functions\when( 'wp_insert_attachment' )->alias(
			function ( $args ) {
				if ( $this->attach_fail ) {
					return 0;
				}
				$id                 = $this->next++;
				$this->posts[ $id ] = array_merge( $args, array( 'post_type' => 'attachment' ) );
				return $id;
			}
		);
		Functions\when( 'wp_generate_attachment_metadata' )->justReturn( array( 'width' => 1 ) );
		Functions\when( 'wp_update_attachment_metadata' )->justReturn( true );
		Functions\when( 'term_exists' )->alias(
			static function ( $name ) {
				return 'Tips' === $name ? array( 'term_id' => '5' ) : null;
			}
		);
		Functions\when( 'wp_insert_term' )->justReturn( array( 'term_id' => 6 ) );
		Functions\when( 'set_post_thumbnail' )->alias(
			function ( $post, $thumb ) {
				$this->calls['thumbnail'][ $post ] = $thumb;
			}
		);
		Functions\when( 'wp_set_object_terms' )->alias(
			function ( $id, $term, $taxonomy ) {
				$this->calls['terms'][ $id ] = array( $term, $taxonomy );
			}
		);
		Functions\when( 'get_terms' )->alias(
			function ( $args ) {
				foreach ( $this->term_meta as $id => $meta ) {
					if ( isset( $meta[ $args['meta_key'] ] ) && $meta[ $args['meta_key'] ] === $args['meta_value'] ) {
						return array( $id );
					}
				}
				return array();
			}
		);
		Functions\when( 'update_term_meta' )->alias(
			function ( $id, $key, $value ) {
				$this->term_meta[ $id ][ $key ] = $value;
			}
		);
		Functions\when( 'get_nav_menu_locations' )->alias(
			function () {
				return $this->locations;
			}
		);
		Functions\when( 'set_theme_mod' )->alias(
			function ( $name, $value ) {
				$this->locations = $value;
			}
		);
		Functions\when( 'wp_get_nav_menu_object' )->alias(
			static function ( $name ) {
				return 'Top' === $name ? (object) array( 'term_id' => 3 ) : false;
			}
		);
		Functions\when( 'wp_create_nav_menu' )->alias(
			function ( $name ) {
				if ( 0 === strpos( $name, 'Broken' ) ) {
					return new \WP_Error( 'nope', 'Nope' );
				}
				$id                 = $this->next++;
				$this->menus[ $id ] = $name;
				return $id;
			}
		);
		Functions\when( 'wp_update_nav_menu_item' )->alias(
			function ( $menu, $item, $args ) {
				$id                 = $this->next++;
				$this->items[ $id ] = array_merge( $args, array( 'menu' => $menu ) );
				return $id;
			}
		);
	}

	/**
	 * Importer for the fixture package.
	 *
	 * @param Package|null $package Package.
	 * @return Importer
	 */
	private function importer( $package = null ) {
		return new Importer( $package ? $package : new Package( dirname( __DIR__, 2 ) . '/fixtures/demo' ) );
	}

	/**
	 * Post id by demo key.
	 *
	 * @param string $key Key.
	 * @return int
	 */
	private function id( $key ) {
		foreach ( $this->meta as $id => $meta ) {
			if ( isset( $meta[ Importer::META ] ) && $key === $meta[ Importer::META ] ) {
				return $id;
			}
		}
		return 0;
	}

	/**
	 * A fresh site with WordPress's privacy draft and the owner's own "About" page.
	 *
	 * @return void
	 */
	private function existing_site() {
		$this->posts[3]                              = array(
			'post_type'         => 'page',
			'post_name'         => 'privacy-policy',
			'post_title'        => 'Privacy Policy',
			'post_status'       => 'draft',
			'post_date_gmt'     => '2026-01-01 00:00:00',
			'post_modified_gmt' => '2026-01-01 00:00:00',
		);
		$this->posts[4]                              = array(
			'post_type'   => 'page',
			'post_name'   => 'about',
			'post_title'  => 'About me',
			'post_status' => 'publish',
		);
		$this->options['wp_page_for_privacy_policy'] = 3;
		$this->settings(
			array(
				'templates' => array( 'footer' => 50 ),
				'contact'   => array( 'phone' => '+9 999' ),
			)
		);
	}

	public function test_full_import() {
		$this->existing_site();
		$files = \Mockery::mock();
		$files->shouldReceive( 'clear_cache' )->once();
		\Elementor\Plugin::$instance = (object) array( 'files_manager' => $files );

		$report = $this->importer()->run();

		$this->assertSame( 0, $report['page'] );
		$this->assertGreaterThan( 0, $report['created'] );
		$this->assertSame( 1, $report['skipped'], 'The owner\'s published "About" page is reused.' );

		// Pages.
		$home = $this->id( 'page:home' );
		$this->assertSame( 42, $this->posts[ $home ]['post_author'], 'From WP-CLI: the first administrator.' );
		$this->assertSame( 'Home', $this->posts[ $home ]['post_title'] );
		$this->assertSame( 'publish', $this->posts[ $home ]['post_status'] );
		$this->assertSame( Importer::PAGE_TEMPLATE, $this->meta[ $home ]['_wp_page_template'] );
		$this->assertSame( 'builder', $this->meta[ $home ]['_elementor_edit_mode'] );
		$this->assertSame( 'wp-page', $this->meta[ $home ]['_elementor_template_type'] );
		$this->assertSame( '', $this->meta[ $home ]['_elementor_version'] );
		$this->assertSame( 'Home https://example.test/', $this->meta[ $home ]['_qe_seo_description'] );

		$data     = json_decode( $this->meta[ $home ]['_elementor_data'], true );
		$settings = $data[0]['settings'];
		$image    = $this->id( 'image:img-a' );
		$this->assertSame( (string) $image, $settings['image']['id'] );
		$this->assertSame( 'https://example.test/up/' . $image . '.png', $settings['image']['url'] );
		$this->assertSame( 'https://example.test/?p=' . $this->id( 'page:contact' ), $settings['link']['url'] );
		$this->assertSame( 'https://example.test/', $settings['home'] );
		$this->assertSame( '', $settings['unknown'] );
		$this->assertSame( (string) $home, $settings['page'] );
		$this->assertSame( '2026-03-01', $settings['date'] );
		$this->assertSame( 'https://example.test/ghost/', $settings['nolink'], 'Unknown page key falls back to /key/.' );
		$this->assertSame( '', $settings['noimg'] );
		$this->assertSame( 'no tokens', $settings['plain'] );
		$this->assertSame( 5, $settings['num'] );
		$this->assertSame( (string) array_search( 'Main', $this->menus, true ), $settings['menu'], 'Menus exist before page layouts are written.' );

		$this->assertArrayNotHasKey( '_elementor_data', $this->meta[ $this->id( 'page:news' ) ], 'The news page is a plain page.' );
		$this->assertSame( 3, $this->id( 'page:privacy' ), 'WordPress\'s untouched privacy draft is reused.' );
		$this->assertSame( 'publish', $this->posts[3]['post_status'] );
		$this->assertSame( 0, $this->id( 'page:about' ), 'The owner\'s page is not marked as demo.' );
		$this->assertSame( 'About me', $this->posts[4]['post_title'], 'and not changed.' );
		$this->assertSame( 0, $this->id( 'page:broken' ) );

		// Images.
		$this->assertSame( 'qe-demo-img-a', $this->posts[ $image ]['post_name'] );
		$this->assertSame( 'Alt A', $this->meta[ $image ]['_wp_attachment_image_alt'] );
		$this->assertSame( 0, $this->id( 'image:missing' ) );

		// Posts.
		$destination = $this->id( 'post:d1' );
		$this->assertSame( 3, $this->posts[ $destination ]['menu_order'] );
		$this->assertSame( 'Canada', $this->meta[ $destination ]['_qe_country'] );
		$this->assertSame( 'https://example.test/?p=' . $this->id( 'page:contact' ), $this->meta[ $destination ]['_qe_link'], 'Pages exist before posts link to them.' );
		$this->assertSame( $image, $this->calls['thumbnail'][ $destination ] );
		$news = $this->id( 'post:n1' );
		$this->assertSame( '2026-01-10 09:00:00', $this->posts[ $news ]['post_date'] );
		$this->assertSame( array( 5 ), $this->posts[ $news ]['post_category'] );
		$this->assertArrayNotHasKey( $news, $this->calls['thumbnail'], 'Missing image: no thumbnail.' );
		$this->assertSame( array( 6 ), $this->posts[ $this->id( 'post:n2' ) ]['post_category'] );
		$this->assertArrayNotHasKey( 'post_date', $this->posts[ $this->id( 'post:n2' ) ] );
		$this->assertSame( 0, $this->id( 'post:bad' ) );

		// Templates.
		$header = $this->id( 'template:header' );
		$this->assertSame( 'elementor_library', $this->posts[ $header ]['post_type'] );
		$this->assertSame( 'section', $this->meta[ $header ]['_elementor_template_type'] );
		$this->assertSame( array( 'section', 'elementor_library_type' ), $this->calls['terms'][ $header ] );
		$stored = $this->options['qe_edu_settings'];
		$this->assertSame( $header, $stored['templates']['header'] );
		$this->assertSame( 50, $stored['templates']['footer'], 'An assigned footer is kept.' );
		$this->assertSame( 0, $this->id( 'template:broken' ) );

		// Menus.
		$main = array_search( 'Main', $this->menus, true );
		$this->assertSame( 'menu:primary', $this->term_meta[ $main ]['_qe_demo'] );
		$this->assertContains( 'Top (demo)', $this->menus, 'A clashing name gets a suffix.' );
		$this->assertSame( $main, $this->locations['primary'] );
		$this->assertSame( 77, $this->locations['topbar'], 'An assigned location is kept.' );
		$items = array_values( $this->items );
		$this->assertSame( array( 'Home', 'Elsewhere', 'Contact' ), array_column( $items, 'menu-item-title' ) );
		$this->assertSame( 'post_type', $items[0]['menu-item-type'] );
		$this->assertSame( $home, $items[0]['menu-item-object-id'] );
		$this->assertSame( 'custom', $items[1]['menu-item-type'] );
		$this->assertSame( 'https://example.test/nowhere/', $items[1]['menu-item-url'] );
		$this->assertSame( array_keys( $this->items )[1], $items[2]['menu-item-parent-id'] );

		// Settings and reading.
		$this->assertSame( $image, $stored['brand']['logo'] );
		$this->assertSame( 'demo@example.test', $stored['contact']['email'] );
		$this->assertSame( '+9 999', $stored['contact']['phone'], 'Existing values are kept.' );
		$this->assertSame( 'page', $this->options['show_on_front'] );
		$this->assertSame( $home, $this->options['page_on_front'] );
		$this->assertSame( $this->id( 'page:news' ), $this->options['page_for_posts'] );
	}

	public function test_author() {
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		$this->importer()->run( 'contact' );
		$this->assertSame( 7, $this->posts[ $this->id( 'page:contact' ) ]['post_author'] );

		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'get_users' )->justReturn( array() );
		$this->importer()->run( 'news' );
		$this->assertSame( 0, $this->posts[ $this->id( 'page:news' ) ]['post_author'] );
	}

	public function test_import_again_creates_nothing() {
		$this->importer()->run();
		$posts  = count( $this->posts );
		$menus  = count( $this->menus );
		$report = $this->importer()->run();

		$this->assertSame( 0, $report['created'] );
		$this->assertGreaterThan( 0, $report['skipped'] );
		$this->assertCount( $posts, $this->posts );
		$this->assertCount( $menus, $this->menus );
	}

	public function test_one_page() {
		$report = $this->importer()->run( 'contact' );
		$this->assertSame( $this->id( 'page:contact' ), $report['page'] );
		$this->assertSame( 1, $report['created'] );
		$this->assertSame( array(), $this->menus );
		$this->assertArrayNotHasKey( 'qe_edu_settings', $this->options );

		$this->assertSame(
			array(
				'created' => 0,
				'skipped' => 0,
				'page'    => 0,
			),
			$this->importer()->run( 'ghost' )
		);
	}

	public function test_reading_is_left_alone_when_a_home_page_is_set() {
		$this->options['show_on_front'] = 'page';
		$this->options['page_on_front'] = 9;
		$this->importer()->run();
		$this->assertSame( 9, $this->options['page_on_front'] );
	}

	public function test_reading_needs_the_front_page() {
		$package = new class( dirname( __DIR__, 2 ) . '/fixtures/demo' ) extends Package {
			public function value( $key ) {
				return 'posts_page' === $key ? 'news' : 'ghost';
			}
		};
		$this->importer( $package )->run();
		$this->assertSame( 'posts', $this->options['show_on_front'] );

		$package = new class( dirname( __DIR__, 2 ) . '/fixtures/demo' ) extends Package {
			public function value( $key ) {
				return 'front_page' === $key ? 'home' : 'ghost';
			}
		};
		$this->importer( $package )->run();
		$this->assertSame( 'page', $this->options['show_on_front'] );
		$this->assertArrayNotHasKey( 'page_for_posts', $this->options );
	}

	public function test_image_failures() {
		$importer = $this->importer();
		$image    = new \ReflectionMethod( Importer::class, 'import_image' );

		$this->upload_error = true;
		$this->assertSame( 0, $image->invoke( $importer, 'img-a' ) );

		$this->upload_error = false;
		$this->attach_fail  = true;
		$this->assertSame( 0, $image->invoke( $importer, 'img-a' ) );

		$this->attach_fail = false;
		$id                = $image->invoke( $importer, 'img-noalt' );
		$this->assertSame( '', $this->meta[ $id ]['_wp_attachment_image_alt'] );
		$this->assertSame( $id, $image->invoke( $importer, 'img-noalt' ), 'Found again, not re-uploaded.' );
	}

	public function test_untouched_placeholder() {
		$this->options['wp_page_for_privacy_policy'] = 3;
		$draft = (object) array(
			'ID'                => 3,
			'post_status'       => 'draft',
			'post_date_gmt'     => 'a',
			'post_modified_gmt' => 'a',
		);
		$this->assertTrue( Importer::is_untouched_placeholder( $draft ) );
		$draft->post_modified_gmt = 'b';
		$this->assertFalse( Importer::is_untouched_placeholder( $draft ), 'Edited.' );
		$draft->post_modified_gmt = 'a';
		$draft->post_status       = 'publish';
		$this->assertFalse( Importer::is_untouched_placeholder( $draft ) );
		$draft->post_status = 'draft';
		$draft->ID          = 8;
		$this->assertFalse( Importer::is_untouched_placeholder( $draft ), 'Not the privacy page.' );
	}

	public function test_unpublished_slug_clash_creates_a_new_page() {
		$this->posts[5] = array(
			'post_type'   => 'page',
			'post_name'   => 'contact',
			'post_title'  => 'Old contact draft',
			'post_status' => 'draft',
		);
		$this->importer()->run( 'contact' );
		$this->assertNotSame( 5, $this->id( 'page:contact' ) );
		$this->assertSame( 'Old contact draft', $this->posts[5]['post_title'] );
	}

	public function test_find_menu_handles_errors() {
		Functions\when( 'get_terms' )->justReturn( new \WP_Error( 'x', 'y' ) );
		$this->assertSame( 0, Importer::find_menu( 'primary' ) );
	}

	public function test_default_package() {
		$this->assertTrue( ( new \ReflectionProperty( Importer::class, 'package' ) )->getValue( new Importer() )->valid() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_loads_the_image_functions_when_needed() {
		$dir = ABSPATH . 'wp-admin/includes';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir, 0777, true );
		}
		file_put_contents( $dir . '/image.php', '<?php $GLOBALS["qe_image_loaded"] = true;' );
		\Patchwork\redefine(
			'function_exists',
			static function ( $name ) {
				return 'wp_generate_attachment_metadata' === $name ? ! empty( $GLOBALS['qe_image_loaded'] ) : \Patchwork\relay();
			}
		);
		Functions\when( 'wp_update_attachment_metadata' )->alias(
			function ( $id, $meta ) {
				$this->calls['metadata'] = $meta;
			}
		);
		$importer = $this->importer();
		( new \ReflectionMethod( Importer::class, 'import_image' ) )->invoke( $importer, 'img-a' );
		unlink( $dir . '/image.php' );
		$this->assertTrue( $GLOBALS['qe_image_loaded'] );
		$this->assertSame( array( 'width' => 1 ), $this->calls['metadata'] );
	}
}
