<?php
/**
 * Tests for inc/elementor-compat.php.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Theme;

use Brain\Monkey\Functions;

/**
 * Elementor compatibility.
 */
final class ElementorCompatTest extends ThemeTestCase {

	protected function tearDown(): void {
		\Elementor\Plugin::$instance = null;
		parent::tearDown();
	}

	public function test_template_css_is_enqueued_in_head() {
		$this->assertTrue( \QeEdu\Tests\ThemeLoader::has( 'wp_enqueue_scripts', 'qe_edu_enqueue_template_css', 20 ) );
		\Elementor\Core\Files\CSS\Post::$enqueued = array();
		foreach ( array( 'is_singular', 'is_home', 'is_archive', 'is_search', 'is_404' ) as $tag ) {
			Functions\when( $tag )->justReturn( false );
		}
		Functions\when( 'get_post_status' )->alias(
			static function ( $id ) {
				return 99 === $id ? 'draft' : 'publish';
			}
		);

		Functions\when( 'did_action' )->justReturn( 0 );
		qe_edu_enqueue_template_css();
		$this->assertSame( array(), \Elementor\Core\Files\CSS\Post::$enqueued, 'No Elementor.' );

		Functions\when( 'did_action' )->justReturn( 1 );
		qe_edu_enqueue_template_css();
		$this->assertSame( array(), \Elementor\Core\Files\CSS\Post::$enqueued, 'No templates chosen.' );

		$frontend = \Mockery::mock();
		$frontend->shouldReceive( 'enqueue_styles' )->once();
		\Elementor\Plugin::$instance = (object) array( 'frontend' => $frontend );
		$this->options['qe_edu_settings'] = array(
			'templates' => array(
				'header'    => 11,
				'footer'    => 99,
				'single'    => 12,
				'archive'   => 13,
				'not_found' => 14,
			),
		);
		qe_edu_enqueue_template_css();
		$this->assertSame( array( 11 ), \Elementor\Core\Files\CSS\Post::$enqueued, 'Unpublished templates are skipped.' );

		Functions\when( 'is_singular' )->justReturn( true );
		$this->assertSame( array( 11, 12 ), qe_edu_active_template_ids() );
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'is_search' )->justReturn( true );
		$this->assertSame( array( 11, 13 ), qe_edu_active_template_ids() );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( true );
		$this->assertSame( array( 11, 14 ), qe_edu_active_template_ids() );
	}

	public function test_register_locations() {
		$manager = \Mockery::mock();
		$manager->shouldReceive( 'register_all_core_location' )->once();
		qe_edu_register_elementor_locations( $manager );
		$this->addToAssertionCount( 1 );
	}

	public function test_kit_defaults() {
		$colors = qe_edu_kit_system_colors();
		$this->assertSame( array( 'primary', 'secondary', 'text', 'accent' ), array_column( $colors, '_id' ) );
		$this->assertSame( '#A41E34', $colors[0]['color'] );

		$fonts = qe_edu_kit_system_typography();
		$this->assertSame( array( 'Montserrat', 'Montserrat', 'Open Sans', 'Montserrat' ), array_column( $fonts, 'typography_font_family' ) );

		$layout = qe_edu_kit_layout_defaults();
		$this->assertSame( 1240, $layout['container_width']['size'] );
		$this->assertSame( 767, $layout['viewport_mobile'] );
		$this->assertSame( 1024, $layout['viewport_tablet'] );
		$this->assertSame( 1366, $layout['viewport_laptop'] );
		$this->assertSame( 20, $layout['space_between_widgets']['size'] );
	}

	public function test_update_kit_without_kit() {
		$this->assertFalse( qe_edu_update_kit( array( 'a' => 1 ) ) );
	}

	public function test_update_kit_merges_and_clears_cache() {
		$this->options['elementor_active_kit']        = 12;
		$this->meta[12]['_elementor_page_settings'] = array(
			'a' => 'old',
			'b' => 'keep',
		);
		$files = \Mockery::mock();
		$files->shouldReceive( 'clear_cache' )->twice();
		\Elementor\Plugin::$instance = (object) array( 'files_manager' => $files );

		$this->assertTrue( qe_edu_update_kit( array( 'a' => 'new' ) ) );
		$this->assertSame(
			array(
				'a' => 'new',
				'b' => 'keep',
			),
			$this->meta[12]['_elementor_page_settings']
		);

		$this->assertTrue( qe_edu_update_kit( array( 'a' => 'ignored', 'c' => 'added' ), true ) );
		$this->assertSame( 'new', $this->meta[12]['_elementor_page_settings']['a'] );
		$this->assertSame( 'added', $this->meta[12]['_elementor_page_settings']['c'] );
	}

	public function test_update_kit_handles_non_array_meta_and_no_files_manager() {
		$this->options['elementor_active_kit']        = 3;
		$this->meta[3]['_elementor_page_settings'] = 'garbage';

		$this->assertTrue( qe_edu_update_kit( array( 'x' => 1 ) ) );
		$this->assertSame( array( 'x' => 1 ), $this->meta[3]['_elementor_page_settings'] );
	}

	public function test_apply_defaults_requires_elementor_and_runs_once() {
		qe_edu_apply_elementor_defaults();
		$this->assertArrayNotHasKey( 'elementor_disable_color_schemes', $this->options );

		do_action( 'elementor/loaded' );
		$this->options['qe_edu_elementor_defaults'] = '1.0.0';
		qe_edu_apply_elementor_defaults();
		$this->assertArrayNotHasKey( 'elementor_disable_color_schemes', $this->options );
	}

	public function test_apply_defaults_writes_options_and_kit() {
		do_action( 'elementor/loaded' );
		$this->options['elementor_active_kit'] = 8;

		qe_edu_apply_elementor_defaults();

		$this->assertSame( 'active', $this->options['elementor_experiment-container'] );
		$this->assertSame( 'yes', $this->options['elementor_disable_color_schemes'] );
		$this->assertSame( 'yes', $this->options['elementor_disable_typography_schemes'] );
		$this->assertSame( '0', $this->options['elementor_google_font'] );
		$this->assertSame( '1.0.0', $this->options['qe_edu_elementor_defaults'] );
		$this->assertSame( '#A41E34', $this->meta[8]['_elementor_page_settings']['system_colors'][0]['color'] );
		$this->assertSame( 1240, $this->meta[8]['_elementor_page_settings']['container_width']['size'] );
	}

	public function test_apply_defaults_without_kit_or_self_hosting() {
		do_action( 'elementor/loaded' );
		$this->settings( array( 'typography' => array( 'self_host' => false ) ) );

		qe_edu_apply_elementor_defaults();

		$this->assertArrayNotHasKey( 'elementor_google_font', $this->options );
		$this->assertArrayNotHasKey( 'qe_edu_elementor_defaults', $this->options, 'Retried once a kit exists' );
	}

	public function test_auto_full_width() {
		Functions\when( 'get_post_type' )->alias(
			static function ( $id ) {
				return 99 === $id ? 'elementor_library' : 'page';
			}
		);

		qe_edu_auto_full_width( 1, 5, '_elementor_edit_mode', 'builder' );
		$this->assertSame( 'templates/elementor-canvas-full.php', $this->meta[5]['_wp_page_template'] );

		$this->meta[6]['_wp_page_template'] = 'default';
		qe_edu_auto_full_width( 1, 6, '_elementor_edit_mode', 'builder' );
		$this->assertSame( 'templates/elementor-canvas-full.php', $this->meta[6]['_wp_page_template'] );

		$this->meta[7]['_wp_page_template'] = 'templates/canvas.php';
		qe_edu_auto_full_width( 1, 7, '_elementor_edit_mode', 'builder' );
		$this->assertSame( 'templates/canvas.php', $this->meta[7]['_wp_page_template'] );

		qe_edu_auto_full_width( 1, 8, 'other', 'builder' );
		qe_edu_auto_full_width( 1, 8, '_elementor_edit_mode', '' );
		qe_edu_auto_full_width( 1, 99, '_elementor_edit_mode', 'builder' );
		$this->assertArrayNotHasKey( 8, $this->meta );
		$this->assertArrayNotHasKey( 99, $this->meta );
	}

	public function test_no_autop_on_elementor_pages() {
		Functions\when( 'get_the_ID' )->justReturn( 4 );
		Functions\expect( 'remove_filter' )->never();
		$this->assertSame( 'c', qe_edu_elementor_no_autop( 'c' ) );
	}

	public function test_no_autop_removes_wpautop() {
		Functions\when( 'get_the_ID' )->justReturn( 4 );
		$this->meta[4]['_elementor_edit_mode'] = 'builder';
		Functions\expect( 'remove_filter' )->once()->with( 'the_content', 'wpautop' );
		$this->assertSame( 'c', qe_edu_elementor_no_autop( 'c' ) );
	}

	public function test_editor_and_preview_styles() {
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu-elementor-editor', QE_EDU_URI . '/assets/css/editor.css', array(), \Mockery::type( 'string' ) );
		qe_edu_elementor_editor_styles();

		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu' );
		qe_edu_elementor_preview_styles();
		$this->addToAssertionCount( 1 );
	}
}
