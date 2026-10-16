<?php
/**
 * Styles tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\Colors\Styles;
use QeEdu\Core\Settings\Settings;

/**
 * Styles.
 */
final class StylesTest extends PluginTestCase {

	public function test_register() {
		( new Styles() )->register();
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', 'QeEdu\Core\Colors\Styles->enqueue()' ) );
	}

	public function test_default_tokens() {
		$tokens = Styles::tokens( Settings::all() );
		$this->assertSame( '#A41E34', $tokens['--qe-primary'] );
		$this->assertSame( '#F6F5FB', $tokens['--qe-surface-alt'] );
		$this->assertSame( '1rem', $tokens['--qe-base-size'] );
		$this->assertSame( '1', $tokens['--qe-h-scale'] );
		$this->assertStringContainsString( '#A41E34 0%, #140342 100%', $tokens['--qe-hero-gradient'] );
		$this->assertStringStartsWith( "'Montserrat'", $tokens['--qe-font-heading'] );
	}

	public function test_custom_tokens() {
		$this->settings(
			array(
				'colors'     => array(
					'accent'        => 'ocean-blue',
					'background'    => 'accent',
					'hero_gradient' => false,
				),
				'typography' => array(
					'pair'          => 'poppins-inter',
					'base_size'     => 18,
					'heading_scale' => 'large',
				),
			)
		);
		$tokens = Styles::tokens( Settings::all() );
		$this->assertSame( '#005A94', $tokens['--qe-primary'] );
		$this->assertNotSame( '#F6F5FB', $tokens['--qe-surface-alt'] );
		$this->assertSame( 'none', $tokens['--qe-hero-gradient'] );
		$this->assertSame( '1.125rem', $tokens['--qe-base-size'] );
		$this->assertSame( '1.12', $tokens['--qe-h-scale'] );
		$this->assertStringStartsWith( "'Inter'", $tokens['--qe-font-body'] );
	}

	public function test_unknown_tint_and_scale_fall_back() {
		$settings                              = Settings::all();
		$settings['colors']['background']      = 'odd';
		$settings['typography']['heading_scale'] = 'odd';
		$tokens                                = Styles::tokens( $settings );
		$this->assertSame( '#F6F5FB', $tokens['--qe-surface-alt'] );
		$this->assertSame( '1', $tokens['--qe-h-scale'] );
		$this->assertSame( '#FFFFFF', Styles::tokens( array_replace_recursive( $settings, array( 'colors' => array( 'background' => 'white' ) ) ) )['--qe-surface-alt'] );
	}

	public function test_css() {
		$css = Styles::css( Settings::all() );
		$this->assertStringStartsWith( ':root{--qe-primary:#A41E34;', $css );
		$this->assertStringEndsWith( ';}', $css );
	}

	public function test_needs_google_fonts() {
		$this->assertFalse( Styles::needs_google_fonts( Settings::all() ) );
		$this->settings( array( 'typography' => array( 'pair' => 'sora-manrope' ) ) );
		$this->assertTrue( Styles::needs_google_fonts( Settings::all() ) );
		$this->settings( array( 'typography' => array( 'self_host' => false ) ) );
		$this->assertTrue( Styles::needs_google_fonts( Settings::all() ) );
	}

	public function test_enqueue_after_theme_styles() {
		Functions\when( 'wp_style_is' )->justReturn( true );
		Functions\expect( 'wp_register_style' )->never();
		Functions\expect( 'wp_add_inline_style' )->once()->with( 'qe-edu', \Mockery::pattern( '/^:root\{/' ) );
		Functions\expect( 'wp_enqueue_style' )->never();
		( new Styles() )->enqueue();
		$this->addToAssertionCount( 1 );
	}

	public function test_enqueue_without_theme_and_with_google_fonts() {
		$this->settings( array( 'typography' => array( 'pair' => 'raleway-lato' ) ) );
		Functions\when( 'wp_style_is' )->justReturn( false );
		Functions\expect( 'wp_register_style' )->once()->with( 'qe-edu-core-tokens', false, array(), '1.0.0' );
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu-core-tokens' );
		Functions\expect( 'wp_add_inline_style' )->once()->with( 'qe-edu-core-tokens', \Mockery::type( 'string' ) );
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu-fonts', \Mockery::pattern( '/family=Raleway/' ), array(), null );
		( new Styles() )->enqueue();
		$this->addToAssertionCount( 1 );
	}
}
