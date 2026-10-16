<?php
/**
 * Leads tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\PostTypes\Leads;
use QeEdu\Tests\Doubles\TestableLeads;

/**
 * Leads.
 */
final class LeadsTest extends PluginTestCase {

	public function test_register() {
		$args = null;
		Functions\when( 'register_post_type' )->alias(
			function ( $type, $a ) use ( &$args ) {
				$args = $a;
			}
		);
		$leads = new Leads();
		$leads->register();

		$this->assertFalse( $args['public'] );
		$this->assertSame( 'do_not_allow', $args['capabilities']['create_posts'] );
		$this->assertSame( 'manage_options', $args['capabilities']['edit_posts'] );
		$this->assertSame( '', $args['labels']['add_new_item'] );
		foreach ( array( 'manage_qe_lead_posts_custom_column', 'add_meta_boxes_qe_lead', 'admin_post_qe_edu_export_leads', 'manage_posts_extra_tablenav' ) as $hook ) {
			$this->assertNotFalse( has_action( $hook ), $hook );
		}
		$this->assertNotFalse( has_filter( 'manage_qe_lead_posts_columns' ) );
		$this->assertNotFalse( has_filter( 'wp_sitemaps_post_types' ) );
	}

	public function test_types() {
		$this->assertSame( array( 'registration', 'newsletter' ), array_keys( Leads::types() ) );
	}

	public function test_create() {
		$captured = null;
		Functions\when( 'wp_insert_post' )->alias(
			function ( $args ) use ( &$captured ) {
				$captured = $args;
				return 55;
			}
		);
		$this->assertSame(
			55,
			Leads::create(
				'registration',
				array(
					'First name' => 'Ada',
					'Last name'  => 'Lovelace',
					'Phone'      => '1',
				),
				'ada@example.test',
				'https://example.test/'
			)
		);
		$this->assertSame( 'private', $captured['post_status'] );
		$this->assertSame( 'ada@example.test', $captured['post_title'] );
		$this->assertSame( 'registration', $captured['meta_input']['_qe_lead_type'] );

		Leads::create( 'newsletter', array( 'A' => 'Ada', 'B' => 'L' ), '', '' );
		$this->assertSame( 'Ada L', $captured['post_title'] );

		Functions\when( 'wp_insert_post' )->justReturn( new \WP_Error( 'x', 'y' ) );
		$this->assertSame( 0, Leads::create( 'newsletter', array(), 'a@b.test', '' ) );
	}

	public function test_columns() {
		$columns = ( new Leads() )->columns( array( 'cb' => '<input>' ) );
		$this->assertSame( array( 'cb', 'title', 'qe_lead_type', 'qe_lead_source', 'date' ), array_keys( $columns ) );
		$this->assertSame( '', ( new Leads() )->columns( array() )['cb'] );
	}

	public function test_column_output() {
		$this->meta[3] = array(
			'_qe_lead_type'   => 'newsletter',
			'_qe_lead_source' => 'https://example.test/about/',
		);
		$this->meta[4] = array( '_qe_lead_type' => 'custom' );
		$leads         = new Leads();

		$this->assertSame( 'Newsletter', $this->capture( fn() => $leads->column( 'qe_lead_type', 3 ) ) );
		$this->assertSame( 'custom', $this->capture( fn() => $leads->column( 'qe_lead_type', 4 ) ) );
		$this->assertStringContainsString( '>/about/</a>', $this->capture( fn() => $leads->column( 'qe_lead_source', 3 ) ) );
		$this->assertSame( '', $this->capture( fn() => $leads->column( 'qe_lead_source', 4 ) ) );
		$this->assertSame( '', $this->capture( fn() => $leads->column( 'other', 3 ) ) );
	}

	public function test_meta_box_and_render() {
		Functions\expect( 'add_meta_box' )->once()->with( 'qe-edu-lead', 'Submitted details', \Mockery::type( 'array' ), 'qe_lead', 'normal', 'high' );
		( new Leads() )->meta_box();

		$this->meta[8] = array(
			'_qe_lead_fields' => array( 'Email' => '<a@b.test>' ),
			'_qe_lead_source' => 'https://example.test/',
		);
		$out = $this->capture( fn() => ( new Leads() )->render( new \WP_Post( array( 'ID' => 8 ) ) ) );
		$this->assertStringContainsString( '<th scope="row">Email</th><td>&lt;a@b.test&gt;</td>', $out );

		$out = $this->capture( fn() => ( new Leads() )->render( new \WP_Post( array( 'ID' => 9 ) ) ) );
		$this->assertStringContainsString( '<th scope="row">Page</th>', $out );
	}

	public function test_export_button() {
		Functions\when( 'wp_nonce_url' )->alias( fn( $url, $action ) => $url . '&_wpnonce=' . $action );
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'post_type' => 'qe_lead' ) );
		$leads = new Leads();

		$this->assertStringContainsString( 'action=qe_edu_export_leads', $this->capture( fn() => $leads->export_button( 'top' ) ) );
		$this->assertSame( '', $this->capture( fn() => $leads->export_button( 'bottom' ) ) );

		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'post_type' => 'post' ) );
		$this->assertSame( '', $this->capture( fn() => $leads->export_button( 'top' ) ) );
		Functions\when( 'get_current_screen' )->justReturn( null );
		$this->assertSame( '', $this->capture( fn() => $leads->export_button( 'top' ) ) );
	}

	public function test_csv_cell() {
		$this->assertSame( "'=SUM(A1)", Leads::csv_cell( '=SUM(A1)' ) );
		$this->assertSame( "'+1", Leads::csv_cell( '+1' ) );
		$this->assertSame( "'-1", Leads::csv_cell( '-1' ) );
		$this->assertSame( "'@x", Leads::csv_cell( '@x' ) );
		$this->assertSame( 'safe', Leads::csv_cell( 'safe' ) );
		$this->assertSame( '', Leads::csv_cell( '' ) );
	}

	/**
	 * Two leads with different fields.
	 *
	 * @return void
	 */
	private function leads() {
		Functions\when( 'get_posts' )->justReturn(
			array(
				new \WP_Post( array( 'ID' => 1 ) ),
				new \WP_Post( array( 'ID' => 2 ) ),
			)
		);
		Functions\when( 'get_the_date' )->justReturn( '2026-01-01 10:00' );
		$this->meta[1] = array(
			'_qe_lead_type'   => 'registration',
			'_qe_lead_email'  => 'a@b.test',
			'_qe_lead_source' => 'https://example.test/',
			'_qe_lead_fields' => array(
				'Name'  => '=cmd',
				'Phone' => '123',
			),
		);
		$this->meta[2] = array(
			'_qe_lead_type'   => 'newsletter',
			'_qe_lead_email'  => 'c@d.test',
			'_qe_lead_fields' => 'broken',
		);
	}

	public function test_csv_rows() {
		$this->leads();
		$rows = Leads::csv_rows();
		$this->assertSame( array( 'Received', 'Form', 'Email', 'Name', 'Phone', 'Page' ), $rows[0] );
		$this->assertSame( array( '2026-01-01 10:00', 'registration', 'a@b.test', "'=cmd", '123', 'https://example.test/' ), $rows[1] );
		$this->assertSame( array( '2026-01-01 10:00', 'newsletter', 'c@d.test', '', '', '' ), $rows[2] );
	}

	public function test_export_requires_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'died' ) );
		$this->expectExceptionMessage( 'died' );
		( new TestableLeads() )->export();
	}

	public function test_export_streams_csv() {
		$this->leads();
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'check_admin_referer' )->once()->with( 'qe_edu_export_leads' );
		Functions\expect( 'nocache_headers' )->once();
		$headers = array();
		Functions\when( 'header' )->alias(
			static function ( $h ) use ( &$headers ) {
				$headers[] = $h;
			}
		);
		$leads = new TestableLeads();
		$out   = $this->capture( array( $leads, 'export' ) );

		$this->assertStringStartsWith( "\xEF\xBB\xBF" . 'Received,Form,Email,Name,Phone,Page', $out );
		$this->assertStringContainsString( "'=cmd", $out );
		$this->assertSame( 'Content-Type: text/csv; charset=utf-8', $headers[0] );
		$this->assertSame( 1, $leads->halts );
	}

	public function test_halt_exits() {
		$exited = 0;
		\Patchwork\redefine(
			'exit',
			static function () use ( &$exited ) {
				++$exited;
			}
		);
		( new \ReflectionMethod( Leads::class, 'halt' ) )->invoke( new Leads() );
		$this->assertSame( 1, $exited );
	}

	public function test_exclude_from_sitemap() {
		$this->assertSame( array( 'page' => 1 ), ( new Leads() )->exclude_from_sitemap( array( 'page' => 1, 'qe_lead' => 2 ) ) );
	}
}
