<?php
/**
 * Mobile bar, edit pencils and breadcrumb trail tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\Frontend\EditLinks;
use QeEdu\Core\Frontend\MobileBar;
use QeEdu\Core\Seo\Breadcrumbs;

/**
 * Frontend.
 */
final class FrontendTest extends PluginTestCase {

	protected function tearDown(): void {
		unset( $_GET['elementor-preview'] );
		parent::tearDown();
	}

	public function test_mobile_bar_hooks() {
		$bar = new MobileBar();
		$bar->register();
		$this->assertNotFalse( has_action( 'wp_footer', array( $bar, 'render' ) ) );
		$this->assertNotFalse( has_filter( 'body_class', array( $bar, 'body_class' ) ) );
	}

	public function test_mobile_bar_buttons() {
		$this->assertSame( array(), MobileBar::buttons(), 'Nothing configured' );

		$this->settings(
			array(
				'footer'  => array( 'mobile_bar_link' => 'https://example.test/apply' ),
				'contact' => array( 'whatsapp' => '+971 50' ),
			)
		);
		$buttons = MobileBar::buttons();
		$this->assertSame( array( 'Apply now', 'WhatsApp us' ), array_column( $buttons, 'text' ) );
		$this->assertSame( 'https://wa.me/97150', $buttons[1]['url'] );

		$this->settings(
			array(
				'footer'  => array(
					'mobile_bar_link' => 'https://example.test/apply',
					'mobile_bar_text' => 'Start',
					'mobile_bar_chat' => 'Chat',
				),
				'contact' => array( 'whatsapp' => '+971 50' ),
			)
		);
		$this->assertSame( array( 'Start', 'Chat' ), array_column( MobileBar::buttons(), 'text' ) );

		$this->settings(
			array(
				'footer' => array(
					'mobile_bar'      => false,
					'mobile_bar_link' => 'https://x.test',
				),
			)
		);
		$this->assertSame( array(), MobileBar::buttons() );
	}

	public function test_mobile_bar_render_and_body_class() {
		$bar = new MobileBar();
		$this->assertSame( array( 'a' ), $bar->body_class( array( 'a' ) ) );
		$this->assertSame( '', $this->capture( array( $bar, 'render' ) ) );

		$this->settings(
			array(
				'footer'  => array( 'mobile_bar_link' => 'https://example.test/apply' ),
				'contact' => array( 'whatsapp' => '+971 50' ),
			)
		);
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu-core-widgets' );
		$out = $this->capture( array( $bar, 'render' ) );
		$this->assertStringContainsString( '<a class="qe-mobile-bar__apply" href="https://example.test/apply">Apply now</a>', $out );
		$this->assertStringContainsString( 'href="https://wa.me/97150" target="_blank" rel="noopener"', $out );
		$this->assertSame( array( 'a', 'qe-has-mobile-bar' ), $bar->body_class( array( 'a' ) ) );
	}

	public function test_edit_links() {
		$links = new EditLinks();
		$links->register();
		$this->assertNotFalse( has_action( 'wp_footer', array( $links, 'render' ) ) );
		$this->assertSame( 'https://example.test/wp-admin/post.php?post=5&action=elementor', EditLinks::edit_url( 5 ) );

		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_admin' )->justReturn( false );
		$this->assertSame( array(), EditLinks::links() );
		$this->assertSame( '', $this->capture( array( $links, 'render' ) ) );

		Functions\when( 'current_user_can' )->justReturn( true );
		$this->assertSame( array(), EditLinks::links(), 'Elementor not loaded' );

		do_action( 'elementor/loaded' );
		$_GET['elementor-preview'] = '5';
		$this->assertSame( array(), EditLinks::links(), 'Inside the editor preview' );
		unset( $_GET['elementor-preview'] );
		$_GET['qe_preview'] = '1';
		$this->assertSame( array(), EditLinks::links(), 'Inside the Theme Settings preview' );
		$this->assertFalse( has_filter( 'show_admin_bar', '__return_false' ) );
		( new EditLinks() )->register();
		$this->assertNotFalse( has_filter( 'show_admin_bar', '__return_false' ), 'No admin bar in the preview.' );
		unset( $_GET['qe_preview'] );
		$_GET['elementor-preview'] = '5';
		unset( $_GET['elementor-preview'] );

		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 7 );
		$this->meta[7]['_elementor_edit_mode'] = 'builder';
		$this->settings(
			array(
				'templates' => array(
					'header' => 8,
					'footer' => 9,
				),
			)
		);

		$this->assertSame( array( 'Edit this page', 'Edit header', 'Edit footer' ), array_keys( EditLinks::links() ) );

		Functions\expect( 'wp_enqueue_style' )->once();
		$out = $this->capture( array( $links, 'render' ) );
		$this->assertStringContainsString( 'post=9&amp;action=elementor', str_replace( '&', '&amp;', html_entity_decode( $out ) ) );
		$this->assertSame( 3, substr_count( $out, '<a ' ) );

		Functions\when( 'is_singular' )->justReturn( false );
		$this->settings( array() );
		$this->assertSame( array(), EditLinks::links() );
	}

	/**
	 * Common conditional-tag stubs.
	 *
	 * @param array<string,bool> $true Tags returning true.
	 * @return void
	 */
	private function conditionals( $true ) {
		foreach ( array( 'is_front_page', 'is_singular', 'is_home', 'is_search', 'is_404' ) as $tag ) {
			Functions\when( $tag )->justReturn( in_array( $tag, $true, true ) );
		}
	}

	public function test_trail_front_page_and_archives() {
		$this->conditionals( array( 'is_front_page' ) );
		$this->assertSame(
			array(
				array(
					'label' => 'Home',
					'url'   => '',
				),
			),
			Breadcrumbs::trail( 'Home' )
		);

		$this->conditionals( array( 'is_home' ) );
		Functions\when( 'single_post_title' )->justReturn( 'News' );
		$this->assertSame( 'News', Breadcrumbs::trail( 'Home' )[1]['label'] );

		$this->conditionals( array( 'is_search' ) );
		Functions\when( 'get_search_query' )->justReturn( 'mba' );
		$this->assertSame( 'mba', Breadcrumbs::trail( 'Home' )[1]['label'] );

		$this->conditionals( array( 'is_404' ) );
		$this->assertSame( 'Page not found', Breadcrumbs::trail( 'Home' )[1]['label'] );

		$this->conditionals( array() );
		Functions\when( 'get_the_archive_title' )->justReturn( '<span>Events</span>' );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		$this->assertSame(
			array(
				'label' => 'Events',
				'url'   => '',
			),
			Breadcrumbs::trail( 'Home' )[1]
		);
	}

	public function test_trail_singular() {
		$this->conditionals( array( 'is_singular' ) );
		Functions\when( 'get_the_title' )->alias( fn( $p ) => is_object( $p ) ? $p->post_title : 'Title ' . $p );
		Functions\when( 'get_permalink' )->alias( fn( $p ) => 'https://example.test/' . ( is_object( $p ) ? $p->ID : $p ) . '/' );

		// A blog post with a category and a "Posts page".
		$post = new \WP_Post(
			array(
				'ID'         => 3,
				'post_type'  => 'post',
				'post_title' => 'Hello',
			)
		);
		Functions\when( 'get_queried_object' )->justReturn( $post );
		Functions\when( 'get_post_type_object' )->justReturn( (object) array( 'has_archive' => false ) );
		Functions\when( 'get_the_category' )->justReturn( array( (object) array( 'name' => 'Tips' ) ) );
		Functions\when( 'get_category_link' )->justReturn( 'https://example.test/tips/' );
		Functions\when( 'get_post_ancestors' )->justReturn( array() );
		$this->options['page_for_posts'] = 11;
		$this->assertSame( array( 'Home', 'Title 11', 'Tips', 'Hello' ), array_column( Breadcrumbs::trail( 'Home' ), 'label' ) );

		// A post without category or posts page.
		Functions\when( 'get_the_category' )->justReturn( array() );
		$this->options['page_for_posts'] = 0;
		$this->assertSame( array( 'Home', 'Hello' ), array_column( Breadcrumbs::trail( 'Home' ), 'label' ) );

		// A child page.
		$page = new \WP_Post(
			array(
				'ID'         => 5,
				'post_type'  => 'page',
				'post_title' => 'Child',
			)
		);
		Functions\when( 'get_queried_object' )->justReturn( $page );
		Functions\when( 'get_post_ancestors' )->justReturn( array( 2, 1 ) );
		$this->assertSame( array( 'Home', 'Title 1', 'Title 2', 'Child' ), array_column( Breadcrumbs::trail( 'Home' ), 'label' ) );

		// A custom post type with an archive.
		$event = new \WP_Post(
			array(
				'ID'         => 6,
				'post_type'  => 'qe_event',
				'post_title' => 'Fair',
			)
		);
		Functions\when( 'get_queried_object' )->justReturn( $event );
		Functions\when( 'get_post_ancestors' )->justReturn( array() );
		Functions\when( 'get_post_type_object' )->justReturn(
			(object) array(
				'has_archive' => true,
				'labels'      => (object) array( 'name' => 'Events' ),
			)
		);
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.test/events/' );
		$trail = Breadcrumbs::trail( 'Home' );
		$this->assertSame( array( 'Home', 'Events', 'Fair' ), array_column( $trail, 'label' ) );
		$this->assertSame( 'https://example.test/events/', $trail[1]['url'] );
		$this->assertSame( '', $trail[2]['url'] );
	}

	public function test_schema() {
		$schema = Breadcrumbs::schema(
			array(
				array(
					'label' => 'Home',
					'url'   => 'https://example.test/',
				),
				array(
					'label' => 'Page',
					'url'   => '',
				),
			)
		);
		$this->assertSame( 'BreadcrumbList', $schema['@type'] );
		$this->assertSame(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'Home',
				'item'     => 'https://example.test/',
			),
			$schema['itemListElement'][0]
		);
		$this->assertArrayNotHasKey( 'item', $schema['itemListElement'][1] );
	}
}
