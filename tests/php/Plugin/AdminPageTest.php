<?php
/**
 * Theme Settings admin page tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use QeEdu\Core\Settings\AdminPage;
use QeEdu\Core\Settings\Schema;
use QeEdu\Tests\Doubles\TestableAdminPage;

/**
 * Admin page.
 */
final class AdminPageTest extends PluginTestCase {

	/** @var string|null */
	private $redirect;

	protected function setUp(): void {
		parent::setUp();
		$this->redirect = null;
		$_GET           = array();
		$_POST          = array();
		$_FILES         = array();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) {
				$this->redirect = $url;
			}
		);
		Functions\when( 'wp_nonce_field' )->alias(
			static function ( $action ) {
				echo '<input type="hidden" name="_wpnonce" value="' . $action . '">';
			}
		);
		Functions\when( 'wp_nonce_url' )->alias(
			static function ( $url, $action ) {
				return $url . '&_wpnonce=' . $action;
			}
		);
		Functions\when( 'submit_button' )->alias(
			static function ( $text ) {
				echo '<button type="submit">' . $text . '</button>';
			}
		);
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\when( 'wp_get_attachment_url' )->justReturn( false );
	}

	protected function tearDown(): void {
		$_GET   = array();
		$_POST  = array();
		$_FILES = array();
		parent::tearDown();
	}

	public function test_register() {
		$page = new AdminPage();
		$page->register();
		foreach ( array( 'admin_menu', 'admin_enqueue_scripts', 'admin_post_qe_edu_save_settings', 'admin_post_qe_edu_export_settings', 'admin_post_qe_edu_import_settings', 'admin_post_qe_edu_reset_settings' ) as $hook ) {
			$this->assertNotFalse( has_action( $hook ), $hook );
		}
	}

	public function test_menu() {
		$page = new AdminPage();
		Functions\expect( 'add_menu_page' )->once()->with( 'Theme Settings', 'Theme Settings', 'manage_options', 'qe-edu-settings', array( $page, 'render' ), 'dashicons-admin-appearance', 59 );
		$page->menu();
		$this->addToAssertionCount( 1 );
	}

	public function test_url_and_current_tab() {
		$this->assertSame( 'https://example.test/wp-admin/admin.php?page=qe-edu-settings&tab=seo&x=1', AdminPage::url( 'seo', array( 'x' => 1 ) ) );
		$this->assertSame( 'brand', AdminPage::current_tab() );
		$_GET['tab'] = 'colors';
		$this->assertSame( 'colors', AdminPage::current_tab() );
		$_GET['tab'] = 'evil';
		$this->assertSame( 'brand', AdminPage::current_tab() );
	}

	public function test_script_data() {
		$data = AdminPage::script_data();
		$this->assertCount( 12, $data['palettes'] );
		$this->assertCount( 12, $data['fonts'] );
		$this->assertSame( 4.5, $data['minContrast'] );
		$this->assertSame( 'montserrat-open-sans', $data['defaultPair'] );
		$this->assertStringContainsString( '%s', $data['i18n']['lowContrast'] );
	}

	public function test_assets_only_on_this_screen() {
		Functions\expect( 'wp_enqueue_media' )->never();
		( new AdminPage() )->assets( 'index.php' );

		Functions\expect( 'wp_enqueue_media' )->once();
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/css/admin.css', array(), '1.0.0' );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/js/admin.min.js', array(), '1.0.0', true );
		Functions\expect( 'wp_localize_script' )->once()->with( 'qe-edu-admin', 'qeEduAdmin', \Mockery::type( 'array' ) );
		( new AdminPage() )->assets( 'toplevel_page_qe-edu-settings' );
		$this->addToAssertionCount( 1 );
	}

	public function test_guard_blocks_users_without_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'died' ) );
		Functions\expect( 'check_admin_referer' )->never();
		$this->expectExceptionMessage( 'died' );
		( new TestableAdminPage() )->handle_save();
	}

	public function test_save_rejects_unknown_tab() {
		$page          = new TestableAdminPage();
		$_POST['tab']  = 'nope';
		$page->handle_save();
		$this->assertStringContainsString( 'tab=brand&qe_notice=error', $this->redirect );
		$this->assertSame( 1, $page->halts );
	}

	public function test_save_tab() {
		$page                     = new TestableAdminPage();
		$_POST['tab']             = 'header';
		$_POST['qe_edu_settings'] = array( 'header' => array( 'cta_text' => 'Apply' ) );
		Functions\expect( 'set_theme_mod' )->never();
		$page->handle_save();

		$this->assertSame( 'Apply', $this->options['qe_edu_settings']['header']['cta_text'] );
		$this->assertFalse( $this->options['qe_edu_settings']['header']['sticky'] );
		$this->assertStringContainsString( 'tab=header&qe_notice=saved', $this->redirect );
	}

	public function test_save_without_values_and_brand_sync() {
		$page         = new TestableAdminPage();
		$_POST['tab'] = 'brand';
		Functions\expect( 'remove_theme_mod' )->once()->with( 'custom_logo' );
		$page->handle_save();
		$this->assertSame( 0, $this->options['site_icon'] );
	}

	public function test_sync_brand_sets_logo_and_icon() {
		Functions\expect( 'set_theme_mod' )->once()->with( 'custom_logo', 5 );
		AdminPage::sync_brand(
			array(
				'brand' => array(
					'logo'    => 5,
					'favicon' => 6,
				),
			)
		);
		$this->assertSame( 6, $this->options['site_icon'] );
	}

	public function test_export() {
		$page = new TestableAdminPage();
		Functions\expect( 'nocache_headers' )->once();
		$headers = array();
		Functions\when( 'header' )->alias(
			static function ( $h ) use ( &$headers ) {
				$headers[] = $h;
			}
		);
		$out = $this->capture( array( $page, 'handle_export' ) );
		$this->assertSame( 'qe-edu-core', json_decode( $out, true )['plugin'] );
		$this->assertSame( 'Content-Type: application/json; charset=utf-8', $headers[0] );
		$this->assertStringStartsWith( 'Content-Disposition: attachment; filename=qe-edu-settings-', $headers[1] );
		$this->assertSame( 1, $page->halts );
	}

	public function test_import_without_file() {
		$page = new TestableAdminPage();
		$page->handle_import();
		$this->assertStringContainsString( 'qe_notice=import_error', $this->redirect );
	}

	public function test_import_success_and_bad_file() {
		$file = tempnam( sys_get_temp_dir(), 'qe' );
		file_put_contents( $file, '{"plugin":"qe-edu-core","settings":{"brand":{"logo":3}}}' );
		$_FILES['qe_edu_import'] = array( 'tmp_name' => $file );
		Functions\when( 'is_uploaded_file' )->justReturn( true );
		Functions\expect( 'set_theme_mod' )->once()->with( 'custom_logo', 3 );

		( new TestableAdminPage() )->handle_import();
		$this->assertStringContainsString( 'tab=tools&qe_notice=imported', $this->redirect );
		$this->assertSame( 3, $this->options['qe_edu_settings']['brand']['logo'] );

		file_put_contents( $file, 'nope' );
		( new TestableAdminPage() )->handle_import();
		$this->assertStringContainsString( 'qe_notice=import_error', $this->redirect );
		unlink( $file );
	}

	public function test_reset() {
		$_POST['reset_tab'] = 'colors';
		( new TestableAdminPage() )->handle_reset();
		$this->assertStringContainsString( 'qe_notice=reset', $this->redirect );

		$_POST = array();
		( new TestableAdminPage() )->handle_reset();
		$this->assertStringContainsString( 'qe_notice=error', $this->redirect );
	}

	public function test_render_requires_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		$this->assertSame( '', $this->capture( array( new AdminPage(), 'render' ) ) );
	}

	public function test_render_every_tab() {
		foreach ( array_keys( Schema::tabs() ) as $tab ) {
			$_GET['tab'] = $tab;
			$out         = $this->capture( array( new AdminPage(), 'render' ) );
			$this->assertStringContainsString( 'nav-tab nav-tab-active" aria-current="page"', $out, $tab );
			if ( 'tools' === $tab ) {
				$this->assertStringContainsString( 'qe_edu_export_settings', $out );
				$this->assertStringContainsString( 'name="qe_edu_import"', $out );
				$this->assertStringContainsString( 'name="reset_tab"', $out );
				$this->assertStringNotContainsString( '<option value="tools">', $out );
			} else {
				$this->assertStringContainsString( 'name="tab" value="' . $tab . '"', $out );
			}
		}
	}

	public function test_render_preview_and_notice() {
		$_GET['tab']       = 'colors';
		$_GET['qe_notice'] = 'saved';
		$out               = $this->capture( array( new AdminPage(), 'render' ) );
		$this->assertStringContainsString( 'data-qe-preview', $out );
		$this->assertStringContainsString( 'notice-success', $out );
		$this->assertStringContainsString( 'data-qe-accent', $out );
		$this->assertSame( 13, substr_count( $out, 'data-qe-accent' ) );
	}

	public function test_tools_hook() {
		$_GET['tab'] = 'tools';
		Actions\expectDone( 'qe_edu_tools_tab' )->once();
		$this->capture( array( new AdminPage(), 'render' ) );
	}

	public function test_field_names_and_ids() {
		$this->assertSame( 'qe_edu_settings[templates][header]', AdminPage::field_name( 'templates.header' ) );
		$this->assertSame( 'qe-social-facebook', AdminPage::field_id( 'social.facebook' ) );
		$this->assertSame( 'qe-header-cta-text', AdminPage::field_id( 'header.cta_text' ) );
	}

	public function test_render_field_checkbox_and_after_hook() {
		$field = array(
			'type'  => 'checkbox',
			'label' => 'Sticky',
			'help'  => 'Help',
			'path'  => 'header.sticky',
		);
		Actions\expectDone( 'qe_edu_settings_after_field' )->once()->with( $field );
		$out = $this->capture( fn() => ( new AdminPage() )->render_field( $field, true ) );
		$this->assertStringContainsString( 'type="checkbox" id="qe-header-sticky" name="qe_edu_settings[header][sticky]" value="1" checked=\'checked\'', $out );
		$this->assertStringContainsString( '<p class="description">Help</p>', $out );
	}

	public function test_render_field_without_help() {
		$out = $this->capture(
			fn() => ( new AdminPage() )->render_field(
				array(
					'type'  => 'text',
					'label' => 'L',
					'help'  => '',
					'path'  => 'strings.x',
				),
				'v'
			)
		);
		$this->assertStringNotContainsString( 'description', $out );
		$this->assertStringContainsString( '<label for="qe-strings-x" class="qe-field__label">L</label>', $out );
	}

	public function test_controls() {
		$page = new TestableAdminPage();

		$this->assertStringContainsString( '<textarea id="id" name="name" rows="4" class="large-text" placeholder="">a&lt;b</textarea>', $page->control( array( 'type' => 'textarea' ), 'a<b' ) );
		$this->assertStringContainsString( 'class="large-text code"', $page->control( array( 'type' => 'code' ), '' ) );
		$this->assertStringContainsString( 'type="number" id="id" name="name" value="16" min="14" max="20"', $page->control( array( 'type' => 'number', 'min' => 14, 'max' => 20 ), 16 ) );
		$this->assertStringContainsString( '<option value="b" selected=\'selected\'>B</option>', $page->control( array( 'type' => 'select', 'options' => array( 'a' => 'A', 'b' => 'B' ) ), 'b' ) );
		$this->assertStringContainsString( 'type="color" id="id" name="name" value="#a41e34" data-qe-custom-color', $page->control( array( 'type' => 'color' ), '#A41E34' ) );
		$this->assertStringContainsString( 'type="url"', $page->control( array( 'type' => 'url' ), '' ) );
		$this->assertStringContainsString( 'type="email"', $page->control( array( 'type' => 'email' ), '' ) );
		$this->assertStringContainsString( 'type="tel"', $page->control( array( 'type' => 'tel' ), '' ) );
		$this->assertStringContainsString( 'type="text" id="id" name="name" value="v" class="regular-text" placeholder="Default"', $page->control( array( 'type' => 'text', 'placeholder' => 'Default' ), 'v' ) );

		$swatches = $page->control( array( 'type' => 'swatch' ), 'graphite' );
		$this->assertStringContainsString( 'value="graphite" checked=\'checked\'', $swatches );
		$this->assertStringContainsString( 'qe-swatch--custom', $swatches );
		$this->assertStringContainsString( 'role="radiogroup"', $swatches );
	}

	public function test_media_control() {
		$page = new TestableAdminPage();

		$empty = $page->control( array( 'type' => 'media', 'mime' => 'image' ), 0 );
		$this->assertStringContainsString( 'data-qe-media-remove hidden', $empty );
		$this->assertStringContainsString( '<div class="qe-media__preview" data-qe-media-preview></div>', $empty );

		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://x.test/logo.png' );
		$this->assertStringContainsString( '<img src="https://x.test/logo.png" alt="">', $page->control( array( 'type' => 'media', 'mime' => 'image' ), 4 ) );

		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://x.test/profile.pdf' );
		Functions\when( 'wp_basename' )->alias( 'basename' );
		$pdf = $page->control( array( 'type' => 'media', 'mime' => 'application/pdf' ), 5 );
		$this->assertStringContainsString( '>profile.pdf</a>', $pdf );
		$this->assertStringNotContainsString( 'data-qe-media-remove hidden', $pdf );
	}

	public function test_template_control() {
		$page = new TestableAdminPage();
		$this->assertStringContainsString( 'No Elementor templates yet', $page->control( array( 'type' => 'template' ), 0 ) );

		Functions\when( 'get_posts' )->justReturn(
			array(
				new \WP_Post(
					array(
						'ID'         => 7,
						'post_title' => 'Header',
					)
				),
			)
		);
		$out = $page->control( array( 'type' => 'template' ), 7 );
		$this->assertStringContainsString( '<option value="7" selected=\'selected\'>Header</option>', $out );
		$this->assertStringNotContainsString( 'No Elementor templates yet', $out );
	}

	public function test_redirects_control() {
		$page = new TestableAdminPage();
		$out  = $page->control( array( 'type' => 'redirects' ), array( array( 'from' => '/a', 'to' => '/b' ) ) );
		$this->assertSame( 4, substr_count( $out, '<tr><td>' ) );
		$this->assertStringContainsString( 'name="name[0][from]" value="/a"', $out );
		$this->assertStringContainsString( 'data-qe-add-row', $out );
		$this->assertSame( 3, substr_count( $page->control( array( 'type' => 'redirects' ), 'x' ), '<tr><td>' ) );
	}

	public function test_halt_exits() {
		$exited = 0;
		\Patchwork\redefine(
			'exit',
			static function () use ( &$exited ) {
				++$exited;
			}
		);
		$method = new \ReflectionMethod( AdminPage::class, 'halt' );
		$method->invoke( new AdminPage() );
		$this->assertSame( 1, $exited );
	}

	public function test_notices() {
		$this->assertSame( array( 'saved', 'imported', 'reset', 'import_error', 'error' ), array_keys( AdminPage::notices() ) );
	}
}
