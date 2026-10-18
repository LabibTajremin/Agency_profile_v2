<?php
/**
 * Performance optimizer tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\Performance\Optimizer;

/**
 * Optimizer.
 */
final class OptimizerTest extends PluginTestCase {

	public function test_register_defaults() {
		$optimizer = new Optimizer();
		$optimizer->register();
		$this->assertFalse( has_filter( 'wp_lazy_loading_enabled', '__return_false' ) );
		$this->assertNotFalse( has_action( 'init', array( $optimizer, 'disable_emojis' ) ) );
		$this->assertFalse( has_action( 'init', array( $optimizer, 'disable_embeds' ) ) );
		$this->assertNotFalse( has_action( 'wp_head', array( $optimizer, 'preload_hero' ) ) );
		$this->assertNotFalse( has_filter( 'script_loader_tag', array( $optimizer, 'defer_scripts' ) ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( $optimizer, 'jquery_to_footer' ) ) );
	}

	public function test_jquery_to_footer() {
		$scripts = \Mockery::mock();
		Functions\when( 'wp_scripts' )->justReturn( $scripts );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_customize_preview' )->justReturn( false );
		foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $handle ) {
			$scripts->shouldReceive( 'add_data' )->once()->with( $handle, 'group', 1 );
		}
		( new Optimizer() )->jquery_to_footer();

		Functions\when( 'is_customize_preview' )->justReturn( true );
		( new Optimizer() )->jquery_to_footer();
		Functions\when( 'is_admin' )->justReturn( true );
		( new Optimizer() )->jquery_to_footer();
	}

	public function test_register_inverted_settings() {
		$this->settings(
			array(
				'performance' => array(
					'lazy_load'      => false,
					'disable_emojis' => false,
					'disable_embeds' => true,
					'preload_hero'   => false,
					'jquery_footer'  => false,
				),
			)
		);
		$optimizer = new Optimizer();
		$optimizer->register();
		$this->assertNotFalse( has_filter( 'wp_lazy_loading_enabled', '__return_false' ) );
		$this->assertFalse( has_action( 'init', array( $optimizer, 'disable_emojis' ) ) );
		$this->assertNotFalse( has_action( 'init', array( $optimizer, 'disable_embeds' ) ) );
		$this->assertFalse( has_action( 'wp_head', array( $optimizer, 'preload_hero' ) ) );
		$this->assertFalse( has_action( 'wp_enqueue_scripts', array( $optimizer, 'jquery_to_footer' ) ) );
	}

	public function test_disable_emojis() {
		Functions\expect( 'remove_action' )->times( 6 );
		Functions\expect( 'remove_filter' )->times( 3 );
		( new Optimizer() )->disable_emojis();
		$this->assertNotFalse( has_filter( 'emoji_svg_url', '__return_false' ) );
	}

	public function test_disable_embeds() {
		Functions\expect( 'remove_action' )->twice();
		$callback = null;
		Functions\when( 'add_action' )->alias(
			static function ( $hook, $cb ) use ( &$callback ) {
				$callback = $cb;
			}
		);
		( new Optimizer() )->disable_embeds();

		Functions\expect( 'wp_dequeue_script' )->once()->with( 'wp-embed' );
		$callback();
		$this->addToAssertionCount( 1 );
	}

	public function test_hero_image() {
		$this->assertNull( Optimizer::hero_image( 1 ) );

		$this->meta[2]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'elType'   => 'container',
					'elements' => array(
						array(
							'elType'     => 'widget',
							'widgetType' => 'heading',
							'elements'   => array(),
						),
					),
				),
				array(
					'elType'   => 'container',
					'elements' => array(
						array(
							'widgetType' => 'qe-animated-hero',
							'settings'   => array(
								'background'       => 'image',
								'background_image' => array(
									'id'  => '7',
									'url' => 'https://x.test/hero.jpg',
								),
							),
						),
					),
				),
			)
		);
		$this->assertSame(
			array(
				'id'  => 7,
				'url' => 'https://x.test/hero.jpg',
			),
			Optimizer::hero_image( 2 )
		);

		$this->meta[3]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'widgetType' => 'qe-animated-hero',
					'settings'   => array(),
				),
			)
		);
		$this->assertNull( Optimizer::hero_image( 3 ), 'No image.' );

		$this->meta[4]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'widgetType' => 'qe-animated-hero',
					'settings'   => array( 'background_image' => array( 'url' => 'https://x.test/h.jpg' ) ),
				),
			)
		);
		$this->assertNull( Optimizer::hero_image( 4 ), 'A light/gradient hero does not show its image.' );

		$this->meta[5]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'widgetType' => 'qe-page-title',
					'settings'   => array( 'background_image' => array( 'url' => 'https://x.test/b.jpg' ) ),
				),
			)
		);
		$this->assertSame(
			array(
				'id'  => 0,
				'url' => 'https://x.test/b.jpg',
			),
			Optimizer::hero_image( 5 )
		);
	}

	public function test_preload_hero() {
		Functions\when( 'is_singular' )->justReturn( false );
		$this->assertSame( '', $this->capture( array( new Optimizer(), 'preload_hero' ) ) );

		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 4 );
		$this->assertSame( '', $this->capture( array( new Optimizer(), 'preload_hero' ) ) );

		$this->meta[4]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'widgetType' => 'qe-page-title',
					'settings'   => array( 'background_image' => array( 'url' => 'https://x.test/h.jpg' ) ),
				),
			)
		);
		$this->assertSame( '<link rel="preload" as="image" href="https://x.test/h.jpg" fetchpriority="high">' . "\n", $this->capture( array( new Optimizer(), 'preload_hero' ) ) );

		Functions\when( 'wp_get_attachment_image_srcset' )->justReturn( 'https://x.test/h-768.jpg 768w, https://x.test/h.jpg 1600w' );
		Functions\when( 'wp_get_attachment_image_sizes' )->justReturn( '(max-width: 1600px) 100vw, 1600px' );
		$this->meta[4]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'widgetType' => 'qe-page-title',
					'settings'   => array(
						'background_image' => array(
							'id'  => 9,
							'url' => 'https://x.test/h.jpg',
						),
					),
				),
			)
		);
		$this->assertSame(
			'<link rel="preload" as="image" href="https://x.test/h.jpg" imagesrcset="https://x.test/h-768.jpg 768w, https://x.test/h.jpg 1600w" imagesizes="(max-width: 1600px) 100vw, 1600px" fetchpriority="high">' . "\n",
			$this->capture( array( new Optimizer(), 'preload_hero' ) )
		);

		Functions\when( 'wp_get_attachment_image_srcset' )->justReturn( false );
		$this->assertStringNotContainsString( 'imagesrcset', $this->capture( array( new Optimizer(), 'preload_hero' ) ) );
	}

	public function test_defer_scripts() {
		$optimizer = new Optimizer();
		$tag       = '<script src="x.js" id="qe-edu-core-hero-js"></script>';
		Functions\when( 'is_admin' )->justReturn( false );

		$this->assertSame( '<script defer src="x.js" id="qe-edu-core-hero-js"></script>', $optimizer->defer_scripts( $tag, 'qe-edu-core-hero' ) );
		$this->assertSame( $tag, $optimizer->defer_scripts( $tag, 'jquery' ) );
		$this->assertSame( '<script defer src="x.js"></script>', $optimizer->defer_scripts( '<script defer src="x.js"></script>', 'qe-edu-core-x' ) );

		$this->settings( array( 'performance' => array( 'defer_js' => false ) ) );
		$this->assertSame( $tag, $optimizer->defer_scripts( $tag, 'qe-edu-core-hero' ) );

		Functions\when( 'is_admin' )->justReturn( true );
		$this->assertSame( $tag, $optimizer->defer_scripts( $tag, 'qe-edu-core-hero' ) );
	}
}
