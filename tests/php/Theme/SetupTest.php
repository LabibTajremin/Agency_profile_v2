<?php
/**
 * Tests for inc/setup.php.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

use Brain\Monkey\Functions;

/**
 * Setup.
 */
final class SetupTest extends ThemeTestCase {

	public function test_setup_registers_supports_menus_and_sizes() {
		Functions\expect( 'load_theme_textdomain' )->once()->with( 'qe-edu', QE_EDU_DIR . '/languages' );
		$supports = array();
		Functions\when( 'add_theme_support' )->alias(
			static function ( $feature ) use ( &$supports ) {
				$supports[] = $feature;
			}
		);
		Functions\expect( 'add_editor_style' )->once()->with( 'assets/css/editor.css' );
		Functions\expect( 'register_nav_menus' )->once()->with(
			\Mockery::on(
				static function ( $menus ) {
					return array( 'primary', 'topbar', 'footer-resources', 'footer-study', 'footer-about', 'legal' ) === array_keys( $menus );
				}
			)
		);
		Functions\expect( 'add_image_size' )->times( 3 );

		qe_edu_setup();

		foreach ( array( 'title-tag', 'post-thumbnails', 'custom-logo', 'html5', 'responsive-embeds', 'editor-styles', 'custom-background' ) as $feature ) {
			$this->assertContains( $feature, $supports );
		}
	}

	public function test_content_width() {
		qe_edu_content_width();
		$this->assertSame( 1240, $GLOBALS['content_width'] );
		unset( $GLOBALS['content_width'] );
	}

	public function test_image_size_names() {
		$sizes = qe_edu_image_size_names( array( 'full' => 'Full' ) );
		$this->assertSame( array( 'full', 'qe-card', 'qe-hero' ), array_keys( $sizes ) );
	}

	public function test_body_classes_defaults() {
		Functions\when( 'is_singular' )->justReturn( false );

		$this->assertSame(
			array( 'x', 'qe-has-sticky-header', 'qe-mode-light', 'qe-btn-rounded' ),
			qe_edu_body_classes( array( 'x' ) )
		);
	}

	public function test_body_classes_from_settings() {
		$this->settings(
			array(
				'header' => array(
					'sticky'      => false,
					'transparent' => true,
				),
				'colors' => array(
					'mode'           => 'dark',
					'button_style'   => 'pill',
					'link_underline' => false,
				),
			)
		);
		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_the_ID' )->justReturn( 7 );
		$this->meta[7]['_elementor_edit_mode'] = 'builder';

		$this->assertSame(
			array( 'qe-has-transparent-header', 'qe-mode-dark', 'qe-btn-pill', 'qe-no-link-underline', 'qe-elementor-page' ),
			qe_edu_body_classes( array() )
		);
	}

	public function test_body_classes_ignore_invalid_values() {
		$this->settings(
			array(
				'colors' => array(
					'mode'         => 'neon',
					'button_style' => 'blob',
				),
			)
		);
		Functions\when( 'is_singular' )->justReturn( false );

		$this->assertSame( array( 'qe-has-sticky-header' ), qe_edu_body_classes( array() ) );
	}

	public function test_submenu_toggle() {
		$args   = (object) array( 'theme_location' => 'primary' );
		$parent = new \WP_Post( array( 'classes' => array( 'menu-item-has-children' ) ) );
		$leaf   = new \WP_Post( array( 'classes' => array() ) );

		$out = qe_edu_submenu_toggle( '<a>x</a>', $parent, 0, $args );
		$this->assertStringContainsString( 'class="qe-submenu-toggle" aria-expanded="false"', $out );
		$this->assertStringContainsString( 'Show submenu', $out );

		$this->assertSame( '<a>x</a>', qe_edu_submenu_toggle( '<a>x</a>', $leaf, 0, $args ) );
		$this->assertSame( '<a>x</a>', qe_edu_submenu_toggle( '<a>x</a>', $parent, 0, (object) array( 'theme_location' => 'legal' ) ) );
	}

	public function test_block_styles() {
		Functions\expect( 'register_block_style' )->once()->with( 'core/button', \Mockery::type( 'array' ) );
		qe_edu_block_styles();
		$this->addToAssertionCount( 1 );
	}
}
