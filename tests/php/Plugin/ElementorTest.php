<?php
/**
 * Elementor integration, menu fields and dynamic tag tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\Elementor\Integration;
use QeEdu\Core\Elementor\MenuFields;
use QeEdu\Core\Elementor\Tags\Logo;
use QeEdu\Core\Elementor\Tags\SettingText;
use QeEdu\Core\Elementor\Tags\SettingUrl;
use QeEdu\Core\Elementor\Tags\Values;

/**
 * Elementor.
 */
final class ElementorTest extends PluginTestCase {

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	public function test_integration_hooks() {
		$integration = new Integration();
		$integration->register();
		$this->assertNotFalse( has_action( 'elementor/elements/categories_registered', array( $integration, 'category' ) ) );
		$this->assertNotFalse( has_action( 'elementor/widgets/register', array( $integration, 'widgets' ) ) );
		$this->assertNotFalse( has_action( 'elementor/dynamic_tags/register', array( $integration, 'tags' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( $integration, 'register_assets' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( $integration, 'after_theme_styles' ) ) );
	}

	public function test_twenty_widgets() {
		$classes = Integration::widget_classes();
		$this->assertCount( 20, $classes );
		$this->assertCount( 20, array_unique( array_map( static fn( $c ) => ( new $c() )->get_name(), $classes ) ) );
	}

	public function test_category_widgets_and_tags() {
		$integration = new Integration();

		$elements = \Mockery::mock();
		$elements->shouldReceive( 'add_category' )->once()->with(
			'qe-education',
			array(
				'title' => 'QE Education',
				'icon'  => 'eicon-library-open',
			)
		);
		$integration->category( $elements );

		$widgets = \Mockery::mock();
		$widgets->shouldReceive( 'register' )->times( 20 );
		$integration->widgets( $widgets );

		$tags = \Mockery::mock();
		$tags->shouldReceive( 'register_group' )->once()->with( 'qe-edu', array( 'title' => 'Theme Settings' ) );
		$tags->shouldReceive( 'register' )->times( 3 );
		$integration->tags( $tags );
		$this->addToAssertionCount( 1 );
	}

	public function test_register_assets() {
		Functions\expect( 'wp_register_style' )->once()->with( 'qe-edu-core-widgets', QE_EDU_CORE_URL . 'assets/css/widgets.css', array(), '1.0.0' );
		Functions\expect( 'wp_register_script' )->once()->with( 'qe-edu-core-widgets', QE_EDU_CORE_URL . 'assets/js/widgets.min.js', array( 'elementor-frontend' ), '1.0.0', array( 'in_footer' => true ) );
		Functions\expect( 'wp_localize_script' )->once()->with( 'qe-edu-core-widgets', 'qeEduWidgets', \Mockery::on( static fn( $d ) => isset( $d['pause'], $d['play'], $d['error'] ) ) );
		Functions\expect( 'wp_enqueue_style' )->never();
		( new Integration() )->register_assets();
		$this->addToAssertionCount( 1 );
	}

	public function test_widget_styles_print_after_the_theme_stylesheet() {
		$styles = (object) array( 'registered' => array() );
		Functions\when( 'wp_styles' )->justReturn( $styles );
		$integration = new Integration();

		$integration->after_theme_styles();
		$this->assertSame( array(), $styles->registered, 'Nothing registered: nothing to do.' );

		$styles->registered = array(
			'qe-edu'              => (object) array( 'deps' => array() ),
			'qe-edu-core-widgets' => (object) array( 'deps' => array( 'swiper' ) ),
		);
		$integration->after_theme_styles();
		$integration->after_theme_styles();
		$this->assertSame( array( 'swiper', 'qe-edu' ), $styles->registered['qe-edu-core-widgets']->deps, 'Added once.' );

		unset( $styles->registered['qe-edu'] );
		$styles->registered['qe-edu-core-widgets']->deps = array();
		$integration->after_theme_styles();
		$this->assertSame( array(), $styles->registered['qe-edu-core-widgets']->deps, 'Another theme: untouched.' );
	}

	public function test_widget_styles_load_early_with_a_header_or_footer_template() {
		Functions\when( 'wp_register_style' )->justReturn( true );
		Functions\when( 'wp_register_script' )->justReturn( true );
		Functions\when( 'wp_localize_script' )->justReturn( true );
		Functions\expect( 'wp_enqueue_style' )->twice()->with( 'qe-edu-core-widgets' );
		$this->settings( array( 'templates' => array( 'header' => 5 ) ) );
		( new Integration() )->register_assets();
		$this->settings( array( 'templates' => array( 'footer' => 6 ) ) );
		( new Integration() )->register_assets();
		$this->addToAssertionCount( 1 );
	}

	public function test_menu_fields_hooks_and_values() {
		$fields = new MenuFields();
		$fields->register();
		$this->assertNotFalse( has_action( 'wp_nav_menu_item_custom_fields', array( $fields, 'render' ) ) );
		$this->assertNotFalse( has_action( 'wp_update_nav_menu_item', array( $fields, 'save' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $fields, 'assets' ) ) );

		$this->meta[1] = array(
			'_qe_mega_columns' => '3',
			'_qe_mega_image'   => '8',
		);
		$this->meta[2] = array( '_qe_mega_columns' => '7' );
		$this->assertSame( 3, MenuFields::columns( 1 ) );
		$this->assertSame( 0, MenuFields::columns( 2 ) );
		$this->assertSame( 8, MenuFields::image( 1 ) );
	}

	public function test_menu_fields_assets() {
		Functions\expect( 'wp_enqueue_media' )->never();
		( new MenuFields() )->assets( 'index.php' );

		Functions\expect( 'wp_enqueue_media' )->once();
		Functions\expect( 'wp_enqueue_style' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();
		Functions\expect( 'wp_localize_script' )->once();
		( new MenuFields() )->assets( 'nav-menus.php' );
		$this->addToAssertionCount( 1 );
	}

	public function test_menu_fields_render() {
		Functions\when( 'wp_get_attachment_url' )->justReturn( false );
		$this->meta[4] = array( '_qe_mega_columns' => '2' );
		$fields        = new MenuFields();

		$this->assertSame( '', $this->capture( fn() => $fields->render( 4, null, 1 ) ) );
		$out = $this->capture( fn() => $fields->render( 4, null, 0 ) );
		$this->assertStringContainsString( 'name="qe_mega_columns[4]"', $out );
		$this->assertStringContainsString( '<option value="2" selected=\'selected\'>', $out );
		$this->assertStringContainsString( 'name="qe_mega_image[4]"', $out );
	}

	public function test_menu_fields_save() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->alias( fn( $n ) => 'ok' === $n );
		$fields = new MenuFields();

		$fields->save( 1, 4 );
		$_POST = array(
			'update-nav-menu-nonce' => 'bad',
			'qe_mega_columns'       => array( 4 => '3' ),
		);
		$fields->save( 1, 4 );
		$this->assertArrayNotHasKey( 4, $this->meta );

		$_POST = array(
			'update-nav-menu-nonce' => 'ok',
			'qe_mega_columns'       => array( 4 => '3' ),
			'qe_mega_image'         => array( 4 => '9' ),
		);
		$fields->save( 1, 4 );
		$this->assertSame(
			array(
				'_qe_mega_columns' => 3,
				'_qe_mega_image'   => 9,
			),
			$this->meta[4]
		);

		$_POST = array(
			'update-nav-menu-nonce' => 'ok',
			'qe_mega_columns'       => array( 4 => '5' ),
		);
		$fields->save( 1, 4 );
		$this->assertSame( array(), $this->meta[4] );

		Functions\when( 'current_user_can' )->justReturn( false );
		$_POST['qe_mega_columns'] = array( 4 => '2' );
		$fields->save( 1, 4 );
		$this->assertSame( array(), $this->meta[4] );
	}

	public function test_values_text() {
		$this->settings(
			array(
				'brand'   => array( 'org_name' => 'Org Ltd' ),
				'contact' => array(
					'phone'   => '+1 2',
					'address' => "Line 1\nLine 2",
				),
				'footer'  => array( 'copyright' => '{year} {site}' ),
			)
		);
		$this->assertArrayHasKey( 'copyright', Values::text_options() );
		$this->assertSame( 'Org Ltd', Values::text( 'org_name' ) );
		$this->assertSame( '+1 2', Values::text( 'phone' ) );
		$this->assertSame( '2026 Test Site', Values::text( 'copyright' ) );
		$this->assertSame( '', Values::text( 'nope' ) );

		$this->settings( array() );
		$this->assertSame( 'Test Site', Values::text( 'org_name' ) );
		$this->assertSame( '© 2026 Test Site. All rights reserved.', Values::text( 'copyright' ) );
	}

	public function test_values_url() {
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.test/p.pdf' );
		$this->settings(
			array(
				'brand'   => array( 'profile_pdf' => 4 ),
				'social'  => array( 'facebook' => 'https://fb.test' ),
				'contact' => array(
					'phone'    => '+971 (4) 000-0000',
					'email'    => 'a@b.test',
					'whatsapp' => '+971 50 1',
					'map'      => 'https://maps.test',
				),
			)
		);
		$this->assertArrayHasKey( 'social_threads', Values::url_options() );
		$this->assertSame( 'https://fb.test', Values::url( 'social_facebook' ) );
		$this->assertSame( 'tel:+97140000000', Values::url( 'phone' ) );
		$this->assertSame( '', Values::url( 'phone_2' ) );
		$this->assertSame( 'mailto:a@b.test', Values::url( 'email' ) );
		$this->assertSame( 'https://wa.me/971501', Values::url( 'whatsapp' ) );
		$this->assertSame( 'https://maps.test', Values::url( 'map' ) );
		$this->assertSame( 'https://example.test/p.pdf', Values::url( 'profile_pdf' ) );
		$this->assertSame( '', Values::url( 'nope' ) );

		$this->settings( array() );
		$this->assertSame( '', Values::url( 'email' ) );
		$this->assertSame( '', Values::url( 'profile_pdf' ) );
	}

	public function test_tags() {
		$this->settings(
			array(
				'contact' => array(
					'address'  => "A\nB",
					'whatsapp' => '123',
				),
				'brand'   => array( 'logo' => 6 ),
			)
		);
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.test/logo.png' );

		$text = new SettingText();
		$this->assertSame( 'qe-setting-text', $text->get_name() );
		$this->assertSame( 'qe-edu', $text->get_group() );
		$this->assertSame( array( 'text' ), $text->get_categories() );
		$this->assertNotSame( '', $text->get_title() );
		$this->assertArrayHasKey( 'key', $text->call_register_controls() );
		$text->test_settings = array( 'key' => 'address' );
		$this->assertSame( "A<br />\nB", $text->render_for_test() );

		$url = new SettingUrl();
		$this->assertSame( 'qe-setting-url', $url->get_name() );
		$this->assertSame( 'qe-edu', $url->get_group() );
		$this->assertSame( array( 'url' ), $url->get_categories() );
		$this->assertNotSame( '', $url->get_title() );
		$this->assertArrayHasKey( 'key', $url->call_register_controls() );
		$url->test_settings = array( 'key' => 'whatsapp' );
		$this->assertSame( 'https://wa.me/123', $url->get_value_for_test() );

		$logo = new Logo();
		$this->assertSame( 'qe-logo', $logo->get_name() );
		$this->assertSame( 'qe-edu', $logo->get_group() );
		$this->assertSame( array( 'image' ), $logo->get_categories() );
		$this->assertNotSame( '', $logo->get_title() );
		$this->assertArrayHasKey( 'variant', $logo->call_register_controls() );
		$logo->test_settings = array( 'variant' => 'default' );
		$this->assertSame(
			array(
				'id'  => 6,
				'url' => 'https://example.test/logo.png',
			),
			$logo->get_value_for_test()
		);

		$this->settings( array() );
		Functions\when( 'get_theme_mod' )->justReturn( 0 );
		$this->assertSame(
			array(
				'id'  => 0,
				'url' => '',
			),
			$logo->get_value_for_test()
		);
	}
}
