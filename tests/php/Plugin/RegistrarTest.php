<?php
/**
 * Content type registrar tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\PostTypes\Registrar;
use QeEdu\Core\PostTypes\Types;

/**
 * Registrar.
 */
final class RegistrarTest extends PluginTestCase {

	/** @var array<string,array<string,mixed>> */
	private $registered = array();

	/** @var array<string,array<string,mixed>> */
	private $meta_args = array();

	protected function setUp(): void {
		parent::setUp();
		$_POST            = array();
		$this->registered = array();
		$this->meta_args  = array();
		Functions\when( 'register_post_type' )->alias(
			function ( $type, $args ) {
				$this->registered[ $type ] = $args;
			}
		);
		Functions\when( 'register_post_meta' )->alias(
			function ( $type, $key, $args ) {
				$this->meta_args[ $type . $key ] = $args;
			}
		);
		Functions\when( 'wp_get_attachment_url' )->justReturn( false );
		Functions\when( 'sanitize_title' )->alias(
			static function ( $title ) {
				return trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( strtr( $title, array( 'Ñ' => 'n' ) ) ) ), '-' );
			}
		);
	}

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	public function test_register_types_and_hooks() {
		$registrar = new Registrar();
		$registrar->register();

		$this->assertSame( array_keys( Types::all() ), array_keys( $this->registered ) );
		$this->assertTrue( $this->registered['qe_destination']['public'] );
		$this->assertSame( array( 'slug' => 'destinations' ), $this->registered['qe_destination']['rewrite'] );
		$this->assertFalse( $this->registered['qe_partner']['rewrite'] );
		$this->assertContains( 'editor', $this->registered['qe_event']['supports'] );
		$this->assertNotContains( 'excerpt', $this->registered['qe_partner']['supports'] );
		$this->assertTrue( $this->registered['qe_service']['show_in_rest'] );

		foreach ( array( 'qe_edu_core_activated', 'add_meta_boxes', 'save_post', 'admin_enqueue_scripts' ) as $hook ) {
			$this->assertNotFalse( has_action( $hook ), $hook );
		}
		$this->assertNotFalse( has_filter( 'use_block_editor_for_post_type', array( $registrar, 'classic_editor' ) ) );
		$this->assertNotFalse( has_filter( 'enter_title_here', array( $registrar, 'title_placeholder' ) ) );
	}

	public function test_addresses_and_switching_types_off() {
		Functions\when( 'flush_rewrite_rules' )->justReturn( null );
		\QeEdu\Core\Settings\Schema::flush();
		$this->settings(
			array(
				'post_types' => array(
					'qe_destination_slug' => 'Destinos Ñ',
					'qe_event_slug'       => '',
					'qe_team_enabled'     => false,
					'qe_partner_enabled'  => '1',
				),
			)
		);
		( new Registrar() )->register_types();

		$this->assertSame( array( 'slug' => 'destinos-n' ), $this->registered['qe_destination']['rewrite'] );
		$this->assertSame( array( 'slug' => 'events' ), $this->registered['qe_event']['rewrite'], 'Empty: built-in address.' );
		$this->assertArrayNotHasKey( 'qe_team', $this->registered, 'Switched off.' );
		$this->assertArrayHasKey( 'qe_partner', $this->registered );

		$hash = $this->options['qe_edu_rewrite_hash'];
		$this->assertNotFalse( has_action( 'wp_loaded', array( Registrar::class, 'flush' ) ), 'Permalinks rebuilt after a change.' );
		Registrar::flush();

		\Brain\Monkey\Actions\expectAdded( 'wp_loaded' )->never();
		( new Registrar() )->register_types();
		$this->assertSame( $hash, $this->options['qe_edu_rewrite_hash'], 'No rebuild when nothing changed.' );
	}

	public function test_meta_callbacks() {
		( new Registrar() )->register_types();
		$args = $this->meta_args['qe_event_qe_date'];
		$this->assertSame( '2026-01-02', ( $args['sanitize_callback'] )( '2026-01-02' ) );
		$this->assertSame( '', ( $args['sanitize_callback'] )( 'bad' ) );

		Functions\expect( 'current_user_can' )->once()->with( 'edit_post', 5 )->andReturn( true );
		$this->assertTrue( ( $args['auth_callback'] )( false, '_qe_date', 5 ) );
	}

	public function test_labels() {
		$labels = Registrar::labels( 'Destination', 'Destinations' );
		$this->assertSame( 'Add new Destination', $labels['add_new_item'] );
		$this->assertSame( 'No Destinations yet.', $labels['not_found'] );
	}

	public function test_classic_editor_and_placeholder() {
		$registrar = new Registrar();
		$this->assertFalse( $registrar->classic_editor( true, 'qe_service' ) );
		$this->assertTrue( $registrar->classic_editor( true, 'page' ) );

		$this->assertSame( 'Partner name', $registrar->title_placeholder( 'Add title', new \WP_Post( array( 'post_type' => 'qe_partner' ) ) ) );
		$this->assertSame( 'Add title', $registrar->title_placeholder( 'Add title', new \WP_Post( array( 'post_type' => 'page' ) ) ) );
	}

	public function test_meta_boxes() {
		Functions\expect( 'add_meta_box' )->times( count( Types::all() ) );
		( new Registrar() )->meta_boxes();
		$this->addToAssertionCount( 1 );
	}

	public function test_render_fields() {
		Functions\when( 'wp_nonce_field' )->justReturn( '' );
		$this->meta[9] = array(
			'_qe_type' => 'recruitment',
			'_qe_url'  => 'https://partner.test',
		);
		$out           = $this->capture(
			fn() => ( new Registrar() )->render(
				new \WP_Post(
					array(
						'ID'        => 9,
						'post_type' => 'qe_partner',
					)
				)
			)
		);
		$this->assertStringContainsString( '<option value="recruitment" selected=\'selected\'>', $out );
		$this->assertStringContainsString( 'type="url" id="qe-field-url" name="qe_fields[url]" value="https://partner.test"', $out );

		$out = $this->capture(
			fn() => ( new Registrar() )->render(
				new \WP_Post(
					array(
						'ID'        => 10,
						'post_type' => 'qe_destination',
					)
				)
			)
		);
		$this->assertStringContainsString( 'data-qe-media', $out );
		$this->assertStringContainsString( 'name="qe_fields[flag]"', $out );
		$this->assertStringContainsString( 'type="text" id="qe-field-country"', $out );

		$out = $this->capture(
			fn() => ( new Registrar() )->render(
				new \WP_Post(
					array(
						'ID'        => 11,
						'post_type' => 'qe_event',
					)
				)
			)
		);
		$this->assertStringContainsString( 'type="date"', $out );
		$this->assertStringContainsString( 'type="time"', $out );
	}

	/**
	 * Save with given POST data.
	 *
	 * @param string $type Post type.
	 * @return void
	 */
	private function save( $type ) {
		( new Registrar() )->save(
			20,
			new \WP_Post(
				array(
					'ID'        => 20,
					'post_type' => $type,
				)
			)
		);
	}

	public function test_save_guards() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( false );

		$this->save( 'page' );
		$this->save( 'qe_service' );
		$_POST['qe_edu_fields'] = 'bad';
		$_POST['qe_fields']     = array( 'link' => 'https://x.test' );
		$this->save( 'qe_service' );
		$this->assertArrayNotHasKey( 20, $this->meta );

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );
		$this->save( 'qe_service' );
		$this->assertArrayNotHasKey( 20, $this->meta );
	}

	public function test_save_updates_and_deletes() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		$_POST['qe_edu_fields'] = 'ok';
		$_POST['qe_fields']     = array(
			'country' => 'Ireland',
			'flag'    => '0',
			'link'    => '',
		);
		$this->meta[20]         = array(
			'_qe_flag' => '4',
			'_qe_link' => 'https://old.test',
		);

		$this->save( 'qe_destination' );

		$this->assertSame( array( '_qe_country' => 'Ireland' ), $this->meta[20] );

		$_POST['qe_fields'] = 'not-an-array';
		$this->save( 'qe_destination' );
		$this->assertSame( array(), $this->meta[20] );
	}

	public function test_assets_only_on_our_edit_screens() {
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'post_type' => 'qe_service' ) );
		Functions\expect( 'wp_enqueue_media' )->never();
		( new Registrar() )->assets( 'edit.php' );

		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'post_type' => 'page' ) );
		( new Registrar() )->assets( 'post.php' );

		Functions\when( 'get_current_screen' )->justReturn( null );
		( new Registrar() )->assets( 'post.php' );

		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'post_type' => 'qe_service' ) );
		Functions\expect( 'wp_enqueue_media' )->once();
		Functions\expect( 'wp_enqueue_style' )->once();
		Functions\expect( 'wp_enqueue_script' )->once();
		Functions\expect( 'wp_localize_script' )->once()->with( 'qe-edu-admin', 'qeEduAdmin', \Mockery::type( 'array' ) );
		( new Registrar() )->assets( 'post-new.php' );
		$this->addToAssertionCount( 1 );
	}
}
