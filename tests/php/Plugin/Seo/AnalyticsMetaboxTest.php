<?php
/**
 * Analytics / consent and SEO metabox tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Seo;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use QeEdu\Core\Seo\Analytics;
use QeEdu\Core\Seo\Metabox;

/**
 * Analytics and metabox.
 */
final class AnalyticsMetaboxTest extends SeoTestCase {

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * Analytics settings.
	 *
	 * @param array<string,mixed> $seo SEO settings.
	 * @return void
	 */
	private function seo( array $seo ) {
		$this->settings( array( 'seo' => $seo ) );
	}

	/**
	 * A post object.
	 *
	 * @param int    $id   Id.
	 * @param string $type Post type.
	 * @return object
	 */
	private function post( $id = 5, $type = 'page' ) {
		return (object) array(
			'ID'           => $id,
			'post_type'    => $type,
			'post_title'   => 'Study in Canada',
			'post_excerpt' => '',
			'post_content' => 'Content here.',
		);
	}

	public function test_analytics_register() {
		$analytics = new Analytics();
		$analytics->register();
		$this->assertNotFalse( has_action( 'wp_head', array( $analytics, 'head' ) ) );
		$this->assertNotFalse( has_action( 'wp_body_open', array( $analytics, 'body' ) ) );
		$this->assertNotFalse( has_action( 'wp_footer', array( $analytics, 'consent_bar' ) ) );
	}

	public function test_analytics_register_with_rank_math() {
		$this->seo_plugin( 'rankmath' );
		Functions\expect( 'add_action' )->never();
		( new Analytics() )->register();
		$this->addToAssertionCount( 1 );
	}

	public function test_ids_are_validated() {
		$this->assertSame( '', Analytics::ga4() );
		$this->assertSame( '', Analytics::gtm() );
		$this->assertFalse( Analytics::needs_consent() );

		$this->seo(
			array(
				'ga4_id' => ' g-abc1234 ',
				'gtm_id' => 'gtm-xyz987',
			)
		);
		$this->assertSame( 'G-ABC1234', Analytics::ga4() );
		$this->assertSame( 'GTM-XYZ987', Analytics::gtm() );

		$this->seo(
			array(
				'ga4_id' => 'G-1"><script>',
				'gtm_id' => 'UA-1234',
			)
		);
		$this->assertSame( '', Analytics::ga4() );
		$this->assertSame( '', Analytics::gtm() );
	}

	public function test_needs_consent() {
		$this->seo( array( 'consent' => true ) );
		$this->assertFalse( Analytics::needs_consent(), 'Nothing to load.' );
		$this->seo(
			array(
				'consent' => true,
				'gtm_id'  => 'GTM-ABCD',
			)
		);
		$this->assertTrue( Analytics::needs_consent() );
		$this->seo(
			array(
				'consent' => true,
				'ga4_id'  => 'G-ABCD',
			)
		);
		$this->assertTrue( Analytics::needs_consent() );
		$this->seo( array( 'ga4_id' => 'G-ABCD' ) );
		$this->assertFalse( Analytics::needs_consent() );
	}

	public function test_loader() {
		$this->assertSame( '', Analytics::loader() );

		$this->seo( array( 'ga4_id' => 'G-ABCD' ) );
		$js = Analytics::loader();
		$this->assertStringContainsString( "gtag/js?id=G-ABCD'", $js );
		$this->assertStringContainsString( "gtag('config','G-ABCD');", $js );
		$this->assertStringNotContainsString( 'gtm.js', $js );

		$this->seo( array( 'gtm_id' => 'GTM-ABCD' ) );
		$js = Analytics::loader();
		$this->assertStringContainsString( "'dataLayer','GTM-ABCD');", $js );
		$this->assertStringNotContainsString( 'gtag/js', $js );
	}

	public function test_head() {
		$analytics = new Analytics();
		$this->assertSame( '', $this->capture( array( $analytics, 'head' ) ) );

		$this->seo( array( 'ga4_id' => 'G-ABCD' ) );
		$html = $this->capture( array( $analytics, 'head' ) );
		$this->assertStringStartsWith( "<script>var s=document.createElement('script')", $html );
		$this->assertStringNotContainsString( 'qe_consent', $html );

		$this->seo(
			array(
				'ga4_id'  => 'G-ABCD',
				'consent' => true,
			)
		);
		$html = $this->capture( array( $analytics, 'head' ) );
		$this->assertStringStartsWith( '<script>window.qeEduLoadAnalytics=function(){', $html );
		$this->assertStringContainsString( "localStorage.getItem('qe_consent')==='yes'", $html );
		$this->assertStringEndsWith( "</script>\n", $html );
	}

	public function test_body_noscript() {
		$analytics = new Analytics();
		$this->assertSame( '', $this->capture( array( $analytics, 'body' ) ) );

		$this->seo( array( 'gtm_id' => 'GTM-ABCD' ) );
		$this->assertStringContainsString( 'ns.html?id=GTM-ABCD"', $this->capture( array( $analytics, 'body' ) ) );

		$this->seo(
			array(
				'gtm_id'  => 'GTM-ABCD',
				'consent' => true,
			)
		);
		$this->assertSame( '', $this->capture( array( $analytics, 'body' ) ) );
	}

	public function test_consent_bar() {
		$analytics = new Analytics();
		Functions\expect( 'wp_enqueue_style' )->twice()->with( 'qe-edu-core-widgets' );
		Functions\expect( 'wp_enqueue_script' )->twice()->with( 'qe-edu-core-consent', QE_EDU_CORE_URL . 'assets/js/consent.min.js', array(), QE_EDU_CORE_VERSION, true );

		$this->assertSame( '', $this->capture( array( $analytics, 'consent_bar' ) ) );

		$this->seo(
			array(
				'gtm_id'  => 'GTM-ABCD',
				'consent' => true,
			)
		);
		$html = $this->capture( array( $analytics, 'consent_bar' ) );
		$this->assertStringContainsString( '<div class="qe-consent" role="region" aria-label="Cookie consent" hidden>', $html );
		$this->assertStringContainsString( '<p>We use cookies to understand how visitors use this site. Is that OK?</p>', $html );
		$this->assertStringContainsString( 'data-qe-consent="yes">Accept</button>', $html );
		$this->assertStringContainsString( 'data-qe-consent="no">Decline</button>', $html );

		$this->seo(
			array(
				'gtm_id'          => 'GTM-ABCD',
				'consent'         => true,
				'consent_text'    => 'Cookies?',
				'consent_accept'  => 'Sure',
				'consent_decline' => 'Nope',
			)
		);
		$html = $this->capture( array( $analytics, 'consent_bar' ) );
		$this->assertStringContainsString( '<p>Cookies?</p>', $html );
		$this->assertStringContainsString( '>Sure</button>', $html );
		$this->assertStringContainsString( '>Nope</button>', $html );
	}

	public function test_metabox_register_and_add() {
		$metabox = new Metabox();
		$metabox->register();
		$this->assertNotFalse( has_action( 'add_meta_boxes', array( $metabox, 'add' ) ) );
		$this->assertNotFalse( has_action( 'save_post', array( $metabox, 'save' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $metabox, 'assets' ) ) );
		$this->assertNotFalse( has_action( 'elementor/documents/register_controls', array( $metabox, 'elementor_controls' ) ) );
		$this->assertNotFalse( has_action( 'elementor/document/after_save', array( $metabox, 'elementor_save' ) ) );

		Functions\expect( 'add_meta_box' )->once()->with( 'qe-edu-seo', 'SEO', array( $metabox, 'render' ), array( 'post', 'page', 'qe_event' ), 'normal', 'default' );
		$metabox->add();
	}

	public function test_assets() {
		$metabox = new Metabox();
		Functions\expect( 'wp_enqueue_media' )->once();
		Functions\expect( 'wp_enqueue_style' )->once()->with( 'qe-edu-admin', QE_EDU_CORE_URL . 'assets/css/admin.css', array(), QE_EDU_CORE_VERSION );
		$scripts = array();
		Functions\when( 'wp_enqueue_script' )->alias(
			static function ( $handle ) use ( &$scripts ) {
				$scripts[] = $handle;
			}
		);
		$localized = array();
		Functions\when( 'wp_localize_script' )->alias(
			static function ( $handle, $name, $data ) use ( &$localized ) {
				$localized[ $name ] = $data;
			}
		);

		$metabox->assets( 'index.php' );
		$this->assertSame( array(), $scripts );

		$metabox->assets( 'post.php' );
		$this->assertSame( array( 'qe-edu-admin', 'qe-edu-seo' ), $scripts );
		$this->assertArrayHasKey( 'qeEduAdmin', $localized );
		$this->assertSame( 'Test Site', $localized['qeEduSeo']['site'] );
		$this->assertSame( '|', $localized['qeEduSeo']['sep'] );
		$this->assertSame( 'Study abroad', $localized['qeEduSeo']['tagline'] );
		$this->assertSame( array( 'length', 'keywordTitle', 'keywordDesc', 'keywordUrl', 'keywordHeading', 'missing', 'ok' ), array_keys( $localized['qeEduSeo']['i18n'] ) );
	}

	public function test_render() {
		$metabox = new Metabox();
		Functions\expect( 'wp_nonce_field' )->times( 3 )->with( Metabox::NONCE, Metabox::NONCE );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/study-in-canada/' );
		Functions\when( 'esc_textarea' )->alias( 'htmlspecialchars' );
		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.test/share.jpg' );

		$html = $this->capture(
			function () use ( $metabox ) {
				$metabox->render( $this->post() );
			}
		);
		$this->assertStringContainsString( '<span class="qe-seo__preview-url" data-qe-seo-url>https://example.test/study-in-canada/</span>', $html );
		$this->assertStringContainsString( 'data-qe-seo-preview-title>Study in Canada</span>', $html );
		$this->assertStringContainsString( 'data-qe-seo-preview-desc>Content here.</span>', $html );
		foreach ( array( 'title', 'description', 'keyword', 'schema', 'canonical', 'noindex', 'nofollow', 'og_title', 'og_description', 'og_image' ) as $key ) {
			$this->assertStringContainsString( 'name="qe_seo[' . $key . ']"', $html, $key );
		}
		$this->assertStringContainsString( '<input type="url" id="qe-seo-canonical"', $html );
		$this->assertStringContainsString( '<input type="text" id="qe-seo-title"', $html );
		$this->assertStringContainsString( '<textarea id="qe-seo-description" name="qe_seo[description]" rows="3" class="widefat" data-qe-seo-input="description"></textarea>', $html );
		$this->assertStringContainsString( '<option value="WebPage" selected=\'selected\'>', $html );
		$this->assertStringContainsString( 'data-qe-seo-meter="title"', $html );
		$this->assertStringContainsString( 'data-qe-seo-meter="description"', $html );
		$this->assertStringNotContainsString( 'checked', $html );
		$this->assertLessThan( strpos( $html, '<details' ), strpos( $html, 'data-qe-seo-checks' ) );
		$this->assertLessThan( strpos( $html, 'qe_seo[canonical]' ), strpos( $html, '<details' ) );

		$post               = $this->post();
		$post->post_excerpt = 'Short excerpt';
		$this->meta[5]      = array(
			'_qe_seo_title'       => 'Custom & title',
			'_qe_seo_description' => 'Custom desc',
			'_qe_seo_schema'      => 'Course',
			'_qe_seo_noindex'     => '1',
			'_qe_seo_og_image'    => '9',
		);
		$html               = $this->capture(
			static function () use ( $metabox, $post ) {
				$metabox->render( $post );
			}
		);
		$this->assertStringContainsString( 'data-qe-seo-preview-title>Custom &amp; title</span>', $html );
		$this->assertStringContainsString( 'data-qe-seo-preview-desc>Custom desc</span>', $html );
		$this->assertStringContainsString( '<option value="Course" selected=\'selected\'>', $html );
		$this->assertStringContainsString( 'name="qe_seo[noindex]" value="1" checked=\'checked\'>', $html );
		$this->assertStringContainsString( 'value="9" data-qe-media-id>', $html );
		$this->assertStringContainsString( '<img src="https://example.test/share.jpg" alt="">', $html );

		unset( $this->meta[5]['_qe_seo_description'] );
		$html = $this->capture(
			static function () use ( $metabox, $post ) {
				$metabox->render( $post );
			}
		);
		$this->assertStringContainsString( 'data-qe-seo-preview-desc>Short excerpt</span>', $html );
	}

	public function test_save() {
		$metabox = new Metabox();
		Functions\when( 'wp_verify_nonce' )->alias(
			static function ( $nonce ) {
				return 'good' === $nonce;
			}
		);
		Functions\when( 'current_user_can' )->justReturn( true );

		$metabox->save( 5, $this->post( 5, 'attachment' ) );
		$metabox->save( 5, $this->post() );
		$_POST[ Metabox::NONCE ] = 'bad';
		$metabox->save( 5, $this->post() );
		$this->assertSame( array(), $this->meta );

		$_POST[ Metabox::NONCE ] = 'good';
		Functions\when( 'current_user_can' )->justReturn( false );
		$metabox->save( 5, $this->post() );
		$this->assertSame( array(), $this->meta );

		Functions\when( 'current_user_can' )->justReturn( true );
		$this->meta[5]['_qe_seo_keyword'] = 'old';
		$_POST['qe_seo']                  = array(
			'title'   => '<b>New</b>',
			'noindex' => '1',
			'schema'  => 'Bogus',
		);
		$metabox->save( 5, $this->post() );
		$this->assertSame(
			array(
				'_qe_seo_title'   => 'New',
				'_qe_seo_noindex' => '1',
			),
			$this->meta[5]
		);

		$_POST['qe_seo'] = 'not-an-array';
		$metabox->save( 5, $this->post() );
		$this->assertSame( array(), $this->meta[5] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_save_skips_autosave() {
		define( 'DOING_AUTOSAVE', true );
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\expect( 'current_user_can' )->never();
		$_POST[ Metabox::NONCE ] = 'good';
		$_POST['qe_seo']         = array( 'title' => 'X' );

		( new Metabox() )->save( 5, $this->post() );
		$this->assertSame( array(), $this->meta );
	}

	public function test_elementor_controls() {
		$metabox  = new Metabox();
		$document = \Mockery::mock();
		$document->shouldReceive( 'get_main_id' )->andReturn( 5 );
		Functions\when( 'get_post_type' )->justReturn( 'attachment' );
		$document->shouldNotReceive( 'start_controls_section' );
		$metabox->elementor_controls( $document );

		Functions\when( 'get_post_type' )->justReturn( 'page' );
		$this->meta[5]['_qe_seo_title'] = 'Stored';
		$controls                       = array();
		$document                       = \Mockery::mock();
		$document->shouldReceive( 'get_main_id' )->andReturn( 5 );
		$document->shouldReceive( 'start_controls_section' )->once()->with(
			'qe_seo_section',
			array(
				'label' => 'SEO',
				'tab'   => 'settings',
			)
		);
		$document->shouldReceive( 'add_control' )->times( 3 )->andReturnUsing(
			static function ( $id, $args ) use ( &$controls ) {
				$controls[ $id ] = $args;
			}
		);
		$document->shouldReceive( 'end_controls_section' )->once();
		$metabox->elementor_controls( $document );

		$this->assertSame( array( 'qe_seo_title', 'qe_seo_description', 'qe_seo_noindex' ), array_keys( $controls ) );
		$this->assertSame( 'text', $controls['qe_seo_title']['type'] );
		$this->assertSame( 'Stored', $controls['qe_seo_title']['default'] );
		$this->assertSame( 'textarea', $controls['qe_seo_description']['type'] );
		$this->assertSame( 'switcher', $controls['qe_seo_noindex']['type'] );
		$this->assertSame( '1', $controls['qe_seo_noindex']['return_value'] );
		$this->assertNotSame( '', $controls['qe_seo_title']['description'] );
	}

	public function test_elementor_save() {
		$metabox  = new Metabox();
		$document = \Mockery::mock();
		$document->shouldReceive( 'get_main_id' )->andReturn( 5 );
		Functions\when( 'current_user_can' )->justReturn( true );

		$metabox->elementor_save( $document, array() );
		$metabox->elementor_save( $document, array( 'settings' => 'x' ) );
		$this->assertSame( array(), $this->meta );

		$this->meta[5]['_qe_seo_description'] = 'Keep';
		$metabox->elementor_save(
			$document,
			array(
				'settings' => array(
					'qe_seo_title'   => 'From Elementor',
					'qe_seo_noindex' => '',
				),
			)
		);
		$this->assertSame(
			array(
				'_qe_seo_description' => 'Keep',
				'_qe_seo_title'       => 'From Elementor',
			),
			$this->meta[5]
		);

		Functions\when( 'current_user_can' )->justReturn( false );
		$metabox->elementor_save( $document, array( 'settings' => array( 'qe_seo_title' => 'Nope' ) ) );
		$this->assertSame( 'From Elementor', $this->meta[5]['_qe_seo_title'] );
	}
}
