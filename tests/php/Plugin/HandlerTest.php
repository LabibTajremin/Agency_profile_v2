<?php
/**
 * Form submission handler tests (also verifies the "server" text controls:
 * recipient, subject and every response message are used).
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin;

use Brain\Monkey\Functions;
use QeEdu\Core\Forms\Defaults;
use QeEdu\Core\Forms\Handler;

/**
 * Handler.
 */
final class HandlerTest extends PluginTestCase {

	/** @var array<int,array<int,mixed>> */
	private $responses = array();

	/** @var array<int,array<int,mixed>> */
	private $mails = array();

	/** @var array<int,array<string,mixed>> */
	private $leads = array();

	/** @var int */
	private $attempts = 0;

	protected function setUp(): void {
		parent::setUp();
		$_POST           = array();
		$this->responses = array();
		$this->mails     = array();
		$this->leads     = array();
		$this->attempts  = 0;

		Functions\when( 'check_ajax_referer' )->alias( fn() => 'good' === ( $_POST['nonce'] ?? '' ) );
		Functions\when( 'wp_send_json_success' )->alias(
			function ( $data ) {
				$this->responses[] = array( true, $data, 200 );
			}
		);
		Functions\when( 'wp_send_json_error' )->alias(
			function ( $data, $code = null ) {
				$this->responses[] = array( false, $data, $code );
			}
		);
		Functions\when( 'wp_salt' )->justReturn( 'salt' );
		Functions\when( 'get_transient' )->alias( fn() => $this->attempts );
		Functions\when( 'set_transient' )->alias(
			function ( $k, $v ) {
				$this->attempts = $v;
				return true;
			}
		);
		Functions\when( 'get_post_status' )->justReturn( 'publish' );
		Functions\when( 'is_email' )->alias( fn( $e ) => (bool) filter_var( $e, FILTER_VALIDATE_EMAIL ) );
		Functions\when( 'wp_get_referer' )->justReturn( 'https://example.test/apply/' );
		Functions\when( 'wp_insert_post' )->alias(
			function ( $args ) {
				$this->leads[] = $args;
				return 300;
			}
		);
		Functions\when( 'wp_mail' )->alias(
			function ( ...$args ) {
				$this->mails[] = $args;
				return true;
			}
		);
		Functions\when( 'is_wp_error' )->alias( fn( $v ) => $v instanceof \WP_Error );
	}

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * Save a registration widget with settings on post 10.
	 *
	 * @param array $settings Settings.
	 * @param string $type    Widget type.
	 * @return void
	 */
	private function widget( $settings = array(), $type = 'qe-registration-form' ) {
		$this->meta[10]['_elementor_data'] = wp_json_encode(
			array(
				array(
					'id'         => 'form1',
					'widgetType' => $type,
					'settings'   => $settings,
				),
			)
		);
	}

	/**
	 * A valid registration POST.
	 *
	 * @param array $extra Extra/overriding fields.
	 * @return void
	 */
	private function post( $extra = array() ) {
		$_POST = array_merge(
			array(
				'nonce'         => 'good',
				'post_id'       => '10',
				'form_id'       => 'form1',
				'qe_first_name' => 'Ada',
				'qe_last_name'  => 'Lovelace',
				'qe_email'      => 'ada@example.test',
				'qe_phone'      => '+971 50 123 4567',
				'qe_dob'        => '2004-02-29',
				'qe_country'    => 'Ireland',
				'qe_consent'    => '1',
			),
			$extra
		);
	}

	private function last() {
		return end( $this->responses );
	}

	public function test_register_hooks() {
		$handler = new Handler();
		$handler->register();
		foreach ( array( 'wp_ajax_qe_edu_register', 'wp_ajax_nopriv_qe_edu_register', 'wp_ajax_qe_edu_subscribe', 'wp_ajax_nopriv_qe_edu_subscribe' ) as $hook ) {
			$this->assertNotFalse( has_action( $hook ), $hook );
		}
	}

	public function test_bad_nonce() {
		$this->post( array( 'nonce' => 'bad' ) );
		( new Handler() )->registration();
		$this->assertSame( array( false, array( 'message' => Defaults::registration()['error_message'] ), 403 ), $this->last() );
	}

	public function test_honeypot_pretends_success_and_stores_nothing() {
		$this->widget();
		$this->post( array( 'website' => 'spam.example' ) );
		( new Handler() )->registration();
		$this->assertTrue( $this->last()[0] );
		$this->assertSame( array(), $this->leads );
		$this->assertSame( array(), $this->mails );
	}

	public function test_rate_limit() {
		$this->widget();
		$this->post();
		$this->attempts = 5;
		( new Handler() )->registration();
		$this->assertSame( 429, $this->last()[2] );
		$this->assertSame( Defaults::registration()['rate_message'], $this->last()[1]['message'] );
	}

	public function test_unknown_form() {
		$this->widget( array(), 'heading' );
		$this->post();
		( new Handler() )->registration();
		$this->assertSame( 400, $this->last()[2] );
	}

	public function test_successful_registration_stores_lead_and_emails() {
		$this->widget(
			array(
				'recipient'       => 'admissions@example.test',
				'email_subject'   => 'New lead',
				'success_message' => 'Thanks QE!',
			)
		);
		$this->post();
		Functions\expect( 'wp_insert_user' )->never();

		( new Handler() )->registration();

		$this->assertSame( array( true, array( 'message' => 'Thanks QE!' ), 200 ), $this->last() );
		$this->assertSame( 'ada@example.test', $this->leads[0]['meta_input']['_qe_lead_email'] );
		$this->assertSame( 'Ireland', $this->leads[0]['meta_input']['_qe_lead_fields']['Home country'] );
		$this->assertSame( 'https://example.test/apply/', $this->leads[0]['meta_input']['_qe_lead_source'] );
		$this->assertSame( 'admissions@example.test', $this->mails[0][0] );
		$this->assertSame( 'New lead — Test Site', $this->mails[0][1] );
		$this->assertStringContainsString( "First name: Ada\n", $this->mails[0][2] );
		$this->assertSame( array( 'Reply-To: ada@example.test' ), $this->mails[0][3] );
	}

	public function test_validation_errors() {
		$this->widget(
			array(
				'required_message' => 'Required!',
				'invalid_message'  => 'Invalid!',
			)
		);
		$this->post(
			array(
				'qe_first_name' => '',
				'qe_email'      => 'not-an-email',
				'qe_phone'      => 'abc',
				'qe_dob'        => '2023-02-30',
				'qe_country'    => 'Atlantis',
				'qe_last_name'  => str_repeat( 'x', 600 ),
				'qe_consent'    => '',
			)
		);
		( new Handler() )->registration();

		$this->assertSame( 422, $this->last()[2] );
		$this->assertSame(
			array(
				'first_name' => 'Required!',
				'last_name'  => 'Invalid!',
				'email'      => 'Invalid!',
				'phone'      => 'Invalid!',
				'dob'        => 'Invalid!',
				'country'    => 'Invalid!',
				'consent'    => 'Required!',
			),
			$this->last()[1]['errors']
		);
		$this->assertSame( array(), $this->leads );
	}

	public function test_optional_fields_selects_and_textarea() {
		$this->widget(
			array(
				'consent_text' => '',
				'fields'       => array(
					array( '_id' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => '' ),
					array( '_id' => 'level', 'label' => 'Level', 'type' => 'select', 'required' => 'yes', 'options' => "Bachelor\nMaster" ),
					array( '_id' => 'msg', 'label' => 'Message', 'type' => 'textarea', 'required' => '' ),
					array( '_id' => 'contact', 'label' => 'Email', 'type' => 'email', 'required' => 'yes' ),
					array( '_id' => 'contact2', 'label' => 'Second email', 'type' => 'email', 'required' => '' ),
					'not-an-array',
					array( 'label' => 'No id' ),
				),
			)
		);
		$_POST = array(
			'nonce'       => 'good',
			'post_id'     => '10',
			'form_id'     => 'form1',
			'qe_level'    => 'Master',
			'qe_msg'      => "Hello\nthere",
			'qe_contact'  => 'x@y.test',
			'qe_contact2' => 'z@y.test',
		);
		( new Handler() )->registration();

		$this->assertTrue( $this->last()[0] );
		$this->assertSame(
			array(
				'Level'        => 'Master',
				'Message'      => "Hello\nthere",
				'Email'        => 'x@y.test',
				'Second email' => 'z@y.test',
			),
			$this->leads[0]['meta_input']['_qe_lead_fields']
		);
		$this->assertSame( 'x@y.test', $this->leads[0]['meta_input']['_qe_lead_email'] );

		$_POST['qe_level'] = 'PhD';
		( new Handler() )->registration();
		$this->assertSame( 422, $this->last()[2] );
	}

	public function test_account_creation() {
		$this->options['users_can_register'] = '1';
		$this->options['default_role']       = 'subscriber';
		$this->widget( array( 'account_exists_message' => 'Exists!' ) );

		// Password too short.
		$this->post( array( 'qe_password' => 'short' ) );
		( new Handler() )->registration();
		$this->assertSame( 422, $this->last()[2] );

		// Email already registered.
		$this->post( array( 'qe_password' => 'long-enough-1' ) );
		Functions\when( 'email_exists' )->justReturn( 3 );
		( new Handler() )->registration();
		$this->assertSame( array( false, array( 'message' => 'Exists!' ), 409 ), $this->last() );

		// Account creation fails.
		Functions\when( 'email_exists' )->justReturn( false );
		Functions\when( 'wp_insert_user' )->justReturn( new \WP_Error( 'x', 'y' ) );
		( new Handler() )->registration();
		$this->assertSame( 500, $this->last()[2] );

		// Account created; the password is never stored or emailed.
		$user = null;
		Functions\when( 'wp_insert_user' )->alias(
			function ( $args ) use ( &$user ) {
				$user = $args;
				return 12;
			}
		);
		( new Handler() )->registration();
		$this->assertTrue( $this->last()[0] );
		$this->assertSame( 'long-enough-1', $user['user_pass'] );
		$this->assertSame( 'subscriber', $user['role'] );
		$this->assertStringNotContainsString( 'long-enough-1', wp_json_encode( $this->leads ) );
		$this->assertStringNotContainsString( 'long-enough-1', wp_json_encode( $this->mails ) );
	}

	public function test_password_fields_hidden_without_open_registration() {
		$config = Defaults::registration();
		$this->assertNotContains( 'password', array_column( Handler::active_fields( $config ), 'type' ) );
		$this->assertFalse( Handler::can_create_account( $config ) );
		$this->options['users_can_register'] = '1';
		$this->assertTrue( Handler::can_create_account( $config ) );
		$this->assertContains( 'password', array_column( Handler::active_fields( $config ), 'type' ) );
		$config['create_account'] = '';
		$this->assertFalse( Handler::can_create_account( $config ) );
		$config['fields'] = 'broken';
		$this->assertSame( array(), Handler::active_fields( $config ) );
	}

	public function test_notify_recipient_fallbacks() {
		$config = Defaults::registration();
		$this->settings( array( 'contact' => array( 'email' => 'office@example.test' ) ) );
		Handler::notify( $config, array( 'A' => 'B' ), '', 'https://example.test/' );
		$this->assertSame( 'office@example.test', $this->mails[0][0] );
		$this->assertSame( array(), $this->mails[0][3] );

		$this->settings( array() );
		$this->options['admin_email'] = 'admin@example.test';
		Handler::notify( $config, array(), 'v@example.test', '' );
		$this->assertSame( 'admin@example.test', $this->mails[1][0] );
	}

	/**
	 * A newsletter POST.
	 *
	 * @param string $email Email.
	 * @return void
	 */
	private function subscribe( $email ) {
		$_POST = array(
			'nonce'    => 'good',
			'post_id'  => '10',
			'form_id'  => 'form1',
			'qe_email' => $email,
		);
		( new Handler() )->newsletter();
	}

	public function test_newsletter_guards() {
		$this->widget( array(), 'qe-newsletter' );
		$_POST = array( 'nonce' => 'bad' );
		( new Handler() )->newsletter();
		$this->assertSame( 403, $this->last()[2] );

		$this->widget( array(), 'qe-registration-form' );
		$this->subscribe( 'a@b.test' );
		$this->assertSame( 400, $this->last()[2] );

		$this->widget( array( 'invalid_message' => 'Bad email!' ), 'qe-newsletter' );
		$this->subscribe( 'nope' );
		$this->assertSame( array( false, array( 'message' => 'Bad email!' ), 422 ), $this->last() );
	}

	public function test_newsletter_builtin() {
		$this->widget( array( 'success_message' => 'Subscribed!' ), 'qe-newsletter' );
		$this->subscribe( 'a@b.test' );
		$this->assertSame( array( true, array( 'message' => 'Subscribed!' ), 200 ), $this->last() );
		$this->assertSame( 'newsletter', $this->leads[0]['meta_input']['_qe_lead_type'] );
	}

	public function test_newsletter_mailchimp() {
		$this->settings(
			array(
				'footer' => array(
					'mailchimp_key'  => 'key-us21',
					'mailchimp_list' => 'list1',
				),
			)
		);
		$this->widget(
			array(
				'provider'      => 'mailchimp',
				'error_message' => 'Mailchimp failed!',
			),
			'qe-newsletter'
		);
		Functions\when( 'wp_remote_request' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 500 );
		$this->subscribe( 'a@b.test' );
		$this->assertSame( array( false, array( 'message' => 'Mailchimp failed!' ), 502 ), $this->last() );
		$this->assertSame( array(), $this->leads );

		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		$this->subscribe( 'a@b.test' );
		$this->assertTrue( $this->last()[0] );
		$this->assertCount( 1, $this->leads );
	}

	public function test_validate_date_and_text() {
		$config = Defaults::registration();
		$this->assertSame( '2024-02-29', Handler::validate( array( 'type' => 'date' ), '2024-02-29', $config ) );
		$this->assertNull( Handler::validate( array( 'type' => 'date' ), 'tomorrow', $config ) );
		$this->assertSame( 'Ada', Handler::validate( array( 'type' => 'text' ), '<b>Ada</b>', $config ) );
	}
}
