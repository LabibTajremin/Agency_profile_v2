<?php
/**
 * Tests for inc/enqueue.php.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

use Brain\Monkey\Functions;

/**
 * Enqueue.
 */
final class EnqueueTest extends ThemeTestCase {

	public function test_asset_version() {
		$this->assertSame( '1.0.0.' . filemtime( QE_EDU_DIR . '/style.css' ), qe_edu_asset_version( 'style.css' ) );
		$this->assertSame( '1.0.0', qe_edu_asset_version( 'missing.css' ) );
	}

	public function test_font_face_css() {
		$css = qe_edu_font_face_css();
		$this->assertSame( 3, substr_count( $css, '@font-face' ) );
		$this->assertStringContainsString( "font-family:'Montserrat'", $css );
		$this->assertStringContainsString( 'font-display:swap', $css );
		$this->assertStringContainsString( QE_EDU_URI . '/assets/fonts/open-sans-latin-wght-italic.woff2', $css );
	}

	public function test_self_host_fonts() {
		$this->assertTrue( qe_edu_self_host_fonts() );

		$this->settings( array( 'typography' => array( 'pair' => 'playfair-inter' ) ) );
		$this->assertFalse( qe_edu_self_host_fonts() );

		$this->settings( array( 'typography' => array( 'self_host' => false ) ) );
		$this->assertFalse( qe_edu_self_host_fonts() );
	}

	public function test_enqueue_assets_with_fonts_and_comments() {
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu', QE_EDU_URI . '/assets/css/main.css', array(), \Mockery::type( 'string' ) );
		Functions\expect( 'wp_add_inline_style' )->once()->with( 'qe-edu', \Mockery::type( 'string' ) );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'qe-edu', QE_EDU_URI . '/assets/js/theme.min.js', array(), \Mockery::type( 'string' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'comment-reply' );
		Functions\expect( 'wp_localize_script' )->once()->with(
			'qe-edu',
			'qeEduTheme',
			array(
				'menuOpen'  => 'Open menu',
				'menuClose' => 'Close menu',
			)
		);
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'comments_open' )->justReturn( true );
		$this->options['thread_comments'] = '1';

		qe_edu_enqueue_assets();
		$this->addToAssertionCount( 1 );
	}

	public function test_enqueue_assets_without_fonts_or_comments() {
		$this->settings( array( 'typography' => array( 'self_host' => false ) ) );
		Functions\expect( 'wp_enqueue_style' )->once();
		Functions\expect( 'wp_add_inline_style' )->never();
		Functions\expect( 'wp_enqueue_script' )->once();
		Functions\expect( 'wp_localize_script' )->once();
		Functions\when( 'is_singular' )->justReturn( false );

		qe_edu_enqueue_assets();
		$this->addToAssertionCount( 1 );
	}

	public function test_preload_fonts() {
		$out = $this->capture( 'qe_edu_preload_fonts' );
		$this->assertSame( 2, substr_count( $out, 'rel="preload"' ) );
		$this->assertStringContainsString( 'as="font" type="font/woff2" crossorigin', $out );

		$this->settings( array( 'typography' => array( 'self_host' => false ) ) );
		$this->assertSame( '', $this->capture( 'qe_edu_preload_fonts' ) );
	}
}
