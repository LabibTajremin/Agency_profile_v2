<?php
/**
 * Tests for inc/template-tags.php.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

use Brain\Monkey\Functions;
use Brain\Monkey\Filters;
use QeEdu\Tests\ThemeLoader;

/**
 * Template tags.
 */
final class TemplateTagsTest extends ThemeTestCase {

	protected function tearDown(): void {
		\Elementor\Plugin::$instance = null;
		parent::tearDown();
	}

	public function test_hooks_registered_by_all_theme_files() {
		$this->assertTrue( ThemeLoader::has( 'after_setup_theme', 'qe_edu_setup' ) );
		$this->assertTrue( ThemeLoader::has( 'after_setup_theme', 'qe_edu_content_width', 0 ) );
		$this->assertTrue( ThemeLoader::has( 'wp_enqueue_scripts', 'qe_edu_enqueue_assets' ) );
		$this->assertTrue( ThemeLoader::has( 'wp_head', 'qe_edu_preload_fonts', 2 ) );
		$this->assertTrue( ThemeLoader::has( 'elementor/theme/register_locations', 'qe_edu_register_elementor_locations' ) );
		$this->assertTrue( ThemeLoader::has( 'after_switch_theme', 'qe_edu_apply_elementor_defaults' ) );
		$this->assertTrue( ThemeLoader::has( 'the_content', 'qe_edu_elementor_no_autop', 0 ) );
		$this->assertTrue( ThemeLoader::has( 'customize_register', 'qe_edu_customize_register' ) );
		$this->assertTrue( ThemeLoader::has( 'admin_notices', 'qe_edu_required_plugins_notice' ) );
		$this->assertTrue( ThemeLoader::has( 'init', 'qe_edu_block_styles' ) );
	}

	public function test_setting_reads_dotted_paths_and_falls_back() {
		$this->settings( array( 'footer' => array( 'copyright' => 'Hi' ) ) );

		$this->assertSame( 'Hi', qe_edu_setting( 'footer.copyright' ) );
		$this->assertSame( array( 'copyright' => 'Hi' ), qe_edu_setting( 'footer' ) );
		$this->assertSame( 'x', qe_edu_setting( 'footer.missing', 'x' ) );
		$this->assertSame( 'y', qe_edu_setting( 'footer.copyright.deeper', 'y' ) );
	}

	public function test_setting_is_filterable() {
		Filters\expectApplied( 'qe_edu_setting' )->once()->with( null, 'a.b' )->andReturn( 'filtered' );

		$this->assertSame( 'filtered', qe_edu_setting( 'a.b' ) );
	}

	public function test_enabled_casts_boolean_like_values() {
		$this->settings(
			array(
				'h' => array(
					'on'  => '1',
					'off' => '0',
					'no'  => false,
				),
			)
		);

		$this->assertTrue( qe_edu_enabled( 'h.on' ) );
		$this->assertFalse( qe_edu_enabled( 'h.off' ) );
		$this->assertFalse( qe_edu_enabled( 'h.no' ) );
		$this->assertTrue( qe_edu_enabled( 'h.missing' ) );
		$this->assertFalse( qe_edu_enabled( 'h.missing', false ) );
	}

	public function test_default_strings_are_filterable_and_complete() {
		$strings = qe_edu_default_strings();

		foreach ( array( 'skip_link', 'copyright', 'not_found_title', 'search_title', 'header_cta_text' ) as $key ) {
			$this->assertArrayHasKey( $key, $strings );
			$this->assertNotSame( '', $strings[ $key ] );
		}
	}

	public function test_text_prefers_settings_and_replaces_placeholders() {
		$this->assertSame( '© 2026 Test Site. All rights reserved.', qe_edu_text( 'copyright' ) );

		$this->settings( array( 'strings' => array( 'copyright' => 'Copyright {year} {site} {x}' ) ) );
		$this->assertSame( 'Copyright 2026 Test Site Y', qe_edu_text( 'copyright', array( 'x' => 'Y' ) ) );

		$this->settings( array( 'strings' => array( 'copyright' => '   ' ) ) );
		$this->assertSame( '© 2026 Test Site. All rights reserved.', qe_edu_text( 'copyright' ) );

		$this->settings( array( 'strings' => array( 'copyright' => array( 'not a string' ) ) ) );
		$this->assertSame( '© 2026 Test Site. All rights reserved.', qe_edu_text( 'copyright' ) );

		$this->assertSame( '', qe_edu_text( 'unknown_key' ) );
	}

	public function test_setting_text_reads_any_path() {
		$this->settings( array( 'header' => array( 'cta_text' => 'Apply {site}' ) ) );

		$this->assertSame( 'Apply Test Site', qe_edu_setting_text( 'header.cta_text', 'header_cta_text' ) );
		$this->assertSame( 'Apply Now', qe_edu_setting_text( 'header.none', 'header_cta_text' ) );
	}

	public function test_echo_text_escapes() {
		$this->settings( array( 'strings' => array( 'skip_link' => '<b>Skip</b>' ) ) );

		$this->assertSame( '&lt;b&gt;Skip&lt;/b&gt;', $this->capture( static fn() => qe_edu_echo_text( 'skip_link' ) ) );
	}

	public function test_has_elementor() {
		$this->assertFalse( qe_edu_has_elementor() );
		do_action( 'elementor/loaded' );
		$this->assertTrue( qe_edu_has_elementor() );
	}

	public function test_is_elementor_page() {
		$this->meta[5]['_elementor_edit_mode'] = 'builder';
		Functions\when( 'get_the_ID' )->justReturn( 5 );

		$this->assertTrue( qe_edu_is_elementor_page( 5 ) );
		$this->assertTrue( qe_edu_is_elementor_page() );
		$this->assertFalse( qe_edu_is_elementor_page( 6 ) );

		Functions\when( 'get_the_ID' )->justReturn( false );
		$this->assertFalse( qe_edu_is_elementor_page() );
	}

	public function test_pro_location() {
		Functions\expect( 'elementor_theme_do_location' )->once()->with( 'header' )->andReturn( true );
		$this->assertTrue( qe_edu_pro_location( 'header' ) );
	}

	/**
	 * Install a fake Elementor frontend.
	 *
	 * @param string $content Builder content.
	 * @return void
	 */
	private function fake_elementor( $content ) {
		do_action( 'elementor/loaded' );
		$frontend = \Mockery::mock();
		$frontend->shouldReceive( 'get_builder_content_for_display' )->andReturn( $content );
		\Elementor\Plugin::$instance = (object) array( 'frontend' => $frontend );
	}

	public function test_render_template_setting_guards() {
		$this->assertFalse( qe_edu_render_template_setting( 'templates.header' ) );

		$this->settings( array( 'templates' => array( 'header' => 9 ) ) );
		$this->assertFalse( qe_edu_render_template_setting( 'templates.header' ), 'Elementor not loaded' );

		$this->fake_elementor( '' );
		Functions\when( 'get_post_status' )->justReturn( 'draft' );
		$this->assertFalse( qe_edu_render_template_setting( 'templates.header' ), 'Draft template' );

		Functions\when( 'get_post_status' )->justReturn( 'publish' );
		$this->assertFalse( qe_edu_render_template_setting( 'templates.header' ), 'Empty content' );
	}

	public function test_render_template_setting_prints_content() {
		$this->settings( array( 'templates' => array( 'footer' => 9 ) ) );
		$this->fake_elementor( '<div>footer</div>' );
		Functions\when( 'get_post_status' )->justReturn( 'publish' );

		$out = $this->capture( fn() => $this->assertTrue( qe_edu_render_template_setting( 'templates.footer' ) ) );
		$this->assertSame( '<div>footer</div>', $out );
	}

	public function test_render_location_precedence() {
		Functions\when( 'elementor_theme_do_location' )->justReturn( true );
		$this->assertSame( 'pro', qe_edu_render_location( 'header' ) );

		Functions\when( 'elementor_theme_do_location' )->justReturn( false );
		$this->settings( array( 'templates' => array( 'header' => 9 ) ) );
		$this->fake_elementor( '<header></header>' );
		Functions\when( 'get_post_status' )->justReturn( 'publish' );
		$this->assertSame( 'template', $this->capture_return( 'header' ) );

		$this->settings( array() );
		Functions\expect( 'get_template_part' )->once()->with( 'template-parts/header', 'fallback' );
		$this->assertSame( 'fallback', qe_edu_render_location( 'header' ) );
	}

	/**
	 * Render a location while discarding output.
	 *
	 * @param string $location Location.
	 * @return string
	 */
	private function capture_return( $location ) {
		ob_start();
		$result = qe_edu_render_location( $location );
		ob_end_clean();
		return $result;
	}

	public function test_hide_title() {
		Functions\when( 'get_the_ID' )->justReturn( 3 );
		$this->assertFalse( qe_edu_hide_title() );

		$this->meta[3]['_qe_hide_title'] = '1';
		$this->assertTrue( qe_edu_hide_title() );

		$this->meta[4]['_elementor_edit_mode'] = 'builder';
		$this->assertTrue( qe_edu_hide_title( 4 ) );
	}

	public function test_site_branding_logo_or_name() {
		Functions\when( 'has_custom_logo' )->justReturn( true );
		Functions\expect( 'the_custom_logo' )->once();
		qe_edu_site_branding();

		Functions\when( 'has_custom_logo' )->justReturn( false );
		$out = $this->capture( 'qe_edu_site_branding' );
		$this->assertSame( '<a class="qe-site-title" href="https://example.test/" rel="home">Test Site</a>', $out );
	}

	public function test_nav() {
		Functions\when( 'has_nav_menu' )->justReturn( false );
		$this->assertFalse( qe_edu_nav( 'primary', 'menu_label', 'x' ) );

		Functions\when( 'has_nav_menu' )->justReturn( true );
		Functions\expect( 'wp_nav_menu' )->once()->with(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'depth'          => 3,
				'fallback_cb'    => false,
			)
		);
		$out = $this->capture( fn() => $this->assertTrue( qe_edu_nav( 'primary', 'menu_label', 'qe-primary-nav', 3 ) ) );
		$this->assertSame( '<nav class="qe-primary-nav" aria-label="Main menu"></nav>', $out );
	}

	public function test_posted_on() {
		Functions\when( 'get_the_date' )->alias(
			static function ( $format = '' ) {
				return '' === $format ? 'January 1, 2026' : '2026-01-01T00:00:00+00:00';
			}
		);
		Functions\when( 'get_the_author_meta' )->justReturn( 1 );
		Functions\when( 'get_author_posts_url' )->justReturn( 'https://example.test/author/a/' );
		Functions\when( 'get_the_author' )->justReturn( 'Ada' );

		$out = $this->capture( 'qe_edu_posted_on' );

		$this->assertStringContainsString( 'Posted on <time datetime="2026-01-01T00:00:00+00:00">January 1, 2026</time>', $out );
		$this->assertStringContainsString( 'by <a href="https://example.test/author/a/">Ada</a>', $out );

		Functions\when( 'get_the_author' )->justReturn( '' );
		$out = $this->capture( 'qe_edu_posted_on' );
		$this->assertStringNotContainsString( '<a ', $out, 'No empty author link.' );
		$this->assertStringEndsWith( '</time></p>', $out );
	}
}
