<?php
/**
 * SEO head output tests.
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Seo;

use Brain\Monkey\Functions;
use QeEdu\Core\Seo\Head;

/**
 * Head.
 */
final class HeadTest extends SeoTestCase {

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'get_the_title' )->alias(
			static function ( $post = 0 ) {
				return is_object( $post ) ? $post->post_title : 'Title ' . $post;
			}
		);
		Functions\when( 'get_locale' )->justReturn( 'en_US' );
		Functions\when( 'get_post_type' )->justReturn( 'page' );
		Functions\when( 'get_theme_mod' )->justReturn( 0 );
		Functions\when( 'wp_get_canonical_url' )->alias(
			static function ( $id ) {
				return 'https://example.test/p/' . $id . '/';
			}
		);
		Functions\when( 'wp_get_attachment_image_src' )->justReturn( false );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );
	}

	/**
	 * Make the request a singular view of $id.
	 *
	 * @param int    $id   Post id.
	 * @param string $type Post type.
	 * @return void
	 */
	private function singular( $id, $type = 'page' ) {
		$this->conditionals( array( 'is_singular' ) );
		Functions\when( 'get_queried_object_id' )->justReturn( $id );
		Functions\when( 'get_post_type' )->justReturn( $type );
		$post = (object) array(
			'ID'           => $id,
			'post_type'    => $type,
			'post_title'   => 'Title ' . $id,
			'post_excerpt' => '',
			'post_content' => 'Body text of the page.',
		);
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_queried_object' )->justReturn( $post );
		Functions\when( 'has_excerpt' )->justReturn( false );
		Functions\when( 'get_post_type_object' )->justReturn( null );
		Functions\when( 'get_post_ancestors' )->justReturn( array() );
	}

	public function test_register_hooks_only_without_other_seo_plugin() {
		$head = new Head();
		Functions\expect( 'remove_action' )->once()->with( 'wp_head', 'rel_canonical' );
		$head->register();
		$this->assertNotFalse( has_filter( 'document_title_parts', array( $head, 'title_parts' ) ) );
		$this->assertNotFalse( has_filter( 'wp_robots', array( $head, 'robots' ) ) );
		$this->assertNotFalse( has_action( 'wp_head', array( $head, 'head' ) ) );
	}

	public function test_register_steps_aside_for_yoast() {
		$this->seo_plugin( 'yoast' );
		Functions\expect( 'add_filter' )->never();
		Functions\expect( 'add_action' )->never();
		Functions\expect( 'remove_action' )->never();
		( new Head() )->register();
		$this->addToAssertionCount( 1 );
	}

	public function test_fill() {
		$this->assertSame( 'About | Test Site', Head::fill( '{title} {sep} {site}', 'About' ) );
		$this->settings( array( 'seo' => array( 'separator' => '–' ) ) );
		$this->assertSame( 'Test Site – Study abroad', Head::fill( "{site}  {sep}\n{tagline}", '' ) );
	}

	public function test_title_for_every_request_type() {
		$head = new Head();

		$this->assertSame( 'fallback', $head->title( 'fallback' ) );

		$this->conditionals( array( 'is_front_page' ) );
		$this->assertSame( 'Test Site | Study abroad', $head->title() );
		$this->settings( array( 'seo' => array( 'home_title' => '{title} – home' ) ) );
		$this->assertSame( 'Test Site – home', $head->title() );
		$this->settings( array() );
		Functions\when( 'get_bloginfo' )->alias(
			static function ( $show = '' ) {
				return 'description' === $show ? '' : 'Test Site';
			}
		);
		$this->assertSame( 'Test Site', $head->title() );

		$this->singular( 7 );
		$this->assertSame( 'Title 7 | Test Site', $head->title() );
		$this->meta[7]['_qe_seo_title'] = 'Custom {title}';
		$this->assertSame( 'Custom Title 7', $head->title() );

		$this->conditionals( array( 'is_singular', 'is_front_page' ) );
		$this->meta[7] = array();
		$this->assertSame( 'Test Site', $head->title() );

		$this->conditionals( array( 'is_home' ) );
		Functions\when( 'single_post_title' )->justReturn( 'Blog' );
		$this->assertSame( 'Blog | Test Site', $head->title() );

		$this->conditionals( array( 'is_search' ) );
		Functions\when( 'get_search_query' )->justReturn( 'mba' );
		$this->assertSame( 'Search results for “mba” | Test Site', $head->title() );

		$this->conditionals( array( 'is_404' ) );
		$this->assertSame( 'Page not found | Test Site', $head->title() );

		$this->conditionals( array( 'is_archive' ) );
		Functions\when( 'get_the_archive_title' )->justReturn( '<span>Category:</span> News' );
		$this->assertSame( 'Category: News | Test Site', $head->title() );
	}

	public function test_title_parts() {
		$head  = new Head();
		$parts = array(
			'title' => 'Core',
			'site'  => 'Test Site',
		);
		$this->assertSame( $parts, $head->title_parts( $parts ) );

		$this->conditionals( array( 'is_404' ) );
		$this->assertSame( array( 'title' => 'Page not found | Test Site' ), $head->title_parts( $parts ) );
	}

	public function test_blog_page_uses_its_own_seo_values() {
		$this->conditionals( array( 'is_home' ) );
		$this->options['page_for_posts'] = 12;
		Functions\when( 'get_post' )->justReturn(
			(object) array(
				'post_excerpt' => '',
				'post_content' => '',
			)
		);
		Functions\when( 'has_excerpt' )->justReturn( false );
		$this->meta[12] = array(
			'_qe_seo_description' => 'Study news.',
			'_qe_seo_title'       => 'Our news {sep} {site}',
		);
		$head = new Head();
		$this->assertSame( 'Our news | Test Site', $head->title() );
		$this->assertSame( 'Study news.', Head::description() );
		$this->assertSame( 'https://example.test/p/12/', Head::canonical() );
		Functions\when( 'wp_get_document_title' )->justReturn( 'News' );
		Functions\when( 'single_post_title' )->justReturn( 'News' );
		$this->assertSame( 'CollectionPage', Head::graph()['@graph'][2]['@type'] );

		$this->options['page_for_posts'] = 0;
		$this->assertSame( '', Head::description(), 'Blog index without a Posts page.' );
	}

	public function test_excerpt() {
		$this->assertSame( 'Short text.', Head::excerpt( "  Short\n text. " ) );
		$long    = str_repeat( 'word ', 60 );
		$excerpt = Head::excerpt( $long );
		$this->assertStringEndsWith( 'word…', $excerpt );
		$this->assertLessThanOrEqual( 160, strlen( $excerpt ) );
	}

	public function test_description() {
		$this->assertSame( '', Head::description() );

		$this->conditionals( array( 'is_front_page' ) );
		$this->assertSame( 'Study abroad', Head::description() );
		$this->settings( array( 'seo' => array( 'home_description' => 'Home text' ) ) );
		$this->assertSame( 'Home text', Head::description() );

		$this->singular( 4 );
		$this->assertSame( 'Body text of the page.', Head::description() );
		Functions\when( 'has_excerpt' )->justReturn( true );
		Functions\when( 'get_post' )->justReturn( (object) array( 'post_excerpt' => 'The excerpt' ) );
		$this->assertSame( 'The excerpt', Head::description() );
		$this->meta[4]['_qe_seo_description'] = 'Custom description';
		$this->assertSame( 'Custom description', Head::description() );

		$this->conditionals( array( 'is_tag' ) );
		Functions\when( 'term_description' )->justReturn( '<p>Term text</p>' );
		$this->assertSame( 'Term text', Head::description() );
	}

	public function test_canonical() {
		$this->assertSame( '', Head::canonical() );

		$this->conditionals( array( 'is_front_page' ) );
		$this->assertSame( 'https://example.test/', Head::canonical() );

		$this->singular( 9 );
		$this->assertSame( 'https://example.test/p/9/', Head::canonical() );
		$this->meta[9]['_qe_seo_canonical'] = 'https://other.test/';
		$this->assertSame( 'https://other.test/', Head::canonical() );

		$this->conditionals( array( 'is_category' ) );
		Functions\when( 'get_queried_object' )->justReturn( (object) array( 'term_id' => 2 ) );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.test/category/news/' );
		$this->assertSame( 'https://example.test/category/news/', Head::canonical() );
		Functions\when( 'get_term_link' )->justReturn( new \stdClass() );
		$this->assertSame( '', Head::canonical() );

		$this->conditionals( array( 'is_post_type_archive' ) );
		Functions\when( 'get_query_var' )->justReturn( 'qe_event' );
		Functions\when( 'get_post_type_archive_link' )->justReturn( 'https://example.test/events/' );
		$this->assertSame( 'https://example.test/events/', Head::canonical() );
	}

	public function test_robots() {
		$head = new Head();
		$this->assertSame( array( 'max-image-preview' => 'large' ), $head->robots( array() ) );

		$this->conditionals( array( 'is_search' ) );
		$this->assertSame( array( 'noindex' => true ), $head->robots( array() ) );

		$this->conditionals( array( 'is_404' ) );
		$this->assertSame( array( 'noindex' => true ), $head->robots( array() ) );

		$this->singular( 3 );
		$this->assertSame( array( 'max-image-preview' => 'large' ), $head->robots( array() ) );
		$this->meta[3] = array(
			'_qe_seo_noindex'  => '1',
			'_qe_seo_nofollow' => '1',
		);
		$this->assertSame(
			array(
				'noindex'  => true,
				'nofollow' => true,
			),
			$head->robots( array() )
		);
	}

	public function test_image() {
		$this->assertNull( Head::image() );

		Functions\when( 'wp_get_attachment_image_src' )->alias(
			static function ( $id ) {
				return 11 === $id ? false : array( 'https://example.test/img-' . $id . '.jpg', 1200, 630 );
			}
		);
		$this->settings( array( 'seo' => array( 'default_image' => 12 ) ) );
		$this->assertSame( array( 'https://example.test/img-12.jpg', 1200, 630 ), Head::image() );

		$this->singular( 5 );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 13 );
		$this->assertSame( 'https://example.test/img-13.jpg', Head::image()[0] );

		$this->meta[5]['_qe_seo_og_image'] = '11';
		$this->assertSame( 'https://example.test/img-13.jpg', Head::image()[0], 'Missing attachment falls through.' );
		$this->meta[5]['_qe_seo_og_image'] = '14';
		$this->assertSame( 'https://example.test/img-14.jpg', Head::image()[0] );
	}

	public function test_head_minimal() {
		Functions\when( 'wp_get_document_title' )->justReturn( 'Doc' );
		$html = $this->capture( array( new Head(), 'head' ) );

		$this->assertStringContainsString( '<!-- QE Edu SEO -->', $html );
		$this->assertStringNotContainsString( 'rel="canonical"', $html );
		$this->assertStringNotContainsString( 'name="description"', $html );
		$this->assertStringContainsString( '<meta property="og:type" content="website">', $html );
		$this->assertStringContainsString( '<meta name="twitter:card" content="summary">', $html );
		$this->assertStringNotContainsString( 'og:image', $html );
		$this->assertStringNotContainsString( 'google-site-verification', $html );
		$this->assertMatchesRegularExpression( '#<script type="application/ld\+json">\{.*\}</script>#', $html );
		$this->assertStringContainsString( '<!-- / QE Edu SEO -->', $html );
	}

	public function test_head_full() {
		$this->singular( 8, 'post' );
		Functions\when( 'wp_get_document_title' )->justReturn( 'Doc' );
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 20 );
		Functions\when( 'wp_get_attachment_image_src' )->justReturn( array( 'https://example.test/i.jpg', 800, 400 ) );
		Functions\when( 'get_the_category' )->justReturn( array() );
		$this->meta[8] = array(
			'_qe_seo_og_title'       => 'OG title',
			'_qe_seo_og_description' => 'OG desc',
		);
		$this->settings(
			array(
				'seo' => array(
					'facebook_app_id'  => '123',
					'twitter_handle'   => '@qe',
					'verify_google'    => 'g',
					'verify_bing'      => 'b',
					'verify_yandex'    => 'y',
					'verify_pinterest' => 'p',
				),
			)
		);

		$html = $this->capture( array( new Head(), 'head' ) );

		foreach ( array(
			'<meta name="description" content="Body text of the page.">',
			'<link rel="canonical" href="https://example.test/p/8/">',
			'<meta property="og:locale" content="en_US">',
			'<meta property="og:type" content="article">',
			'<meta property="og:title" content="OG title">',
			'<meta property="og:description" content="OG desc">',
			'<meta property="og:url" content="https://example.test/p/8/">',
			'<meta property="og:site_name" content="Test Site">',
			'<meta property="fb:app_id" content="123">',
			'<meta property="og:image" content="https://example.test/i.jpg">',
			'<meta property="og:image:width" content="800">',
			'<meta property="og:image:height" content="400">',
			'<meta name="twitter:card" content="summary_large_image">',
			'<meta name="twitter:site" content="@qe">',
			'<meta name="twitter:title" content="OG title">',
			'<meta name="twitter:description" content="OG desc">',
			'<meta name="twitter:image" content="https://example.test/i.jpg">',
			'<meta name="google-site-verification" content="g">',
			'<meta name="msvalidate.01" content="b">',
			'<meta name="yandex-verification" content="y">',
			'<meta name="p:domain_verify" content="p">',
		) as $expected ) {
			$this->assertStringContainsString( $expected, $html );
		}

		$this->meta[8] = array();
		$html          = $this->capture( array( new Head(), 'head' ) );
		$this->assertStringContainsString( '<meta property="og:title" content="Doc">', $html );
		$this->assertStringContainsString( '<meta property="og:description" content="Body text of the page.">', $html );
	}

	public function test_organization() {
		$this->assertSame(
			array(
				'@type' => 'EducationalOrganization',
				'@id'   => 'https://example.test/#organization',
				'name'  => 'Test Site',
				'url'   => 'https://example.test/',
			),
			Head::organization()
		);

		Functions\when( 'wp_get_attachment_url' )->justReturn( 'https://example.test/logo.png' );
		$this->settings(
			array(
				'brand'   => array(
					'org_name' => 'QE Ltd',
					'logo'     => 3,
				),
				'seo'     => array( 'org_type' => 'Organization' ),
				'contact' => array(
					'phone'   => '+1 555',
					'email'   => 'hi@example.test',
					'address' => "1 Road\n  Town ",
				),
				'social'  => array( 'facebook' => 'https://facebook.com/qe' ),
			)
		);
		$node = Head::organization();
		$this->assertSame( 'Organization', $node['@type'] );
		$this->assertSame( 'QE Ltd', $node['name'] );
		$this->assertSame( 'https://example.test/logo.png', $node['logo'] );
		$this->assertSame( '+1 555', $node['telephone'] );
		$this->assertSame( 'hi@example.test', $node['email'] );
		$this->assertSame(
			array(
				'@type'         => 'PostalAddress',
				'streetAddress' => '1 Road, Town',
			),
			$node['address']
		);
		$this->assertSame( array( 'https://facebook.com/qe' ), $node['sameAs'] );

		Functions\when( 'wp_get_attachment_url' )->justReturn( false );
		$this->assertArrayNotHasKey( 'logo', Head::organization() );
	}

	public function test_graph_without_page_node() {
		$this->conditionals( array( 'is_404' ) );
		$graph = Head::graph();
		$this->assertSame( 'https://schema.org', $graph['@context'] );
		$this->assertSame( array( 'EducationalOrganization', 'WebSite' ), array_column( $graph['@graph'], '@type' ) );
		$this->assertSame( 'https://example.test/?s={search_term_string}', $graph['@graph'][1]['potentialAction']['target'] );
	}

	public function test_graph_front_page() {
		$this->conditionals( array( 'is_front_page' ) );
		Functions\when( 'wp_get_document_title' )->justReturn( 'Home' );
		$graph = Head::graph()['@graph'];
		$this->assertSame( array( 'EducationalOrganization', 'WebSite', 'WebPage' ), array_column( $graph, '@type' ) );
		$this->assertSame( 'https://example.test/#webpage', $graph[2]['@id'] );
		$this->assertSame( 'Study abroad', $graph[2]['description'] );
		$this->assertArrayNotHasKey( 'breadcrumb', $graph[2] );
	}

	public function test_graph_archive_with_breadcrumbs() {
		$this->conditionals( array( 'is_archive' ) );
		Functions\when( 'wp_get_document_title' )->justReturn( 'Archive' );
		Functions\when( 'get_the_archive_title' )->justReturn( 'News' );
		$graph = Head::graph()['@graph'];

		$this->assertSame( array( 'EducationalOrganization', 'WebSite', 'CollectionPage', 'BreadcrumbList' ), array_column( $graph, '@type' ) );
		$this->assertSame( 'https://example.test/#webpage', $graph[2]['@id'] );
		$this->assertArrayNotHasKey( 'description', $graph[2] );
		$this->assertSame( 'https://example.test/#webpage-breadcrumb', $graph[2]['breadcrumb']['@id'] );
		$this->assertSame( 'https://example.test/#webpage-breadcrumb', $graph[3]['@id'] );
		$this->assertArrayNotHasKey( '@context', $graph[3] );
		$this->assertSame( array( 'Home', 'News' ), array_column( $graph[3]['itemListElement'], 'name' ) );

		$this->settings( array( 'seo' => array( 'breadcrumbs' => false ) ) );
		$this->assertCount( 3, Head::graph()['@graph'] );

		$this->conditionals( array( 'is_home' ) );
		$this->assertSame( 'CollectionPage', Head::graph()['@graph'][2]['@type'] );
	}

	public function test_graph_page_types() {
		$this->settings( array( 'seo' => array( 'breadcrumbs' => false ) ) );
		Functions\when( 'wp_get_document_title' )->justReturn( 'Doc' );
		$this->singular( 6 );

		$this->meta[6]['_qe_seo_schema'] = 'AboutPage';
		$graph                           = Head::graph()['@graph'];
		$this->assertSame( 'AboutPage', $graph[2]['@type'] );
		$this->assertSame( 'https://example.test/p/6/#webpage', $graph[2]['@id'] );
		$this->assertCount( 3, $graph );

		$this->meta[6]['_qe_seo_schema']      = 'Course';
		$this->meta[6]['_qe_seo_description'] = 'Learn';
		$graph                                = Head::graph()['@graph'];
		$this->assertSame( 'WebPage', $graph[2]['@type'] );
		$this->assertSame(
			array(
				'@type'       => 'Course',
				'name'        => 'Title 6',
				'description' => 'Learn',
				'provider'    => array( '@id' => 'https://example.test/#organization' ),
			),
			$graph[3]
		);

		Functions\when( 'get_post' )->justReturn(
			(object) array(
				'post_excerpt' => '',
				'post_content' => '',
			)
		);
		unset( $this->meta[6]['_qe_seo_description'] );
		$this->assertSame( 'Title 6', Head::graph()['@graph'][3]['description'] );
	}

	public function test_graph_event() {
		$this->settings( array( 'seo' => array( 'breadcrumbs' => false ) ) );
		Functions\when( 'wp_get_document_title' )->justReturn( 'Fair' );
		$this->singular( 30, 'qe_event' );
		Functions\when( 'get_the_excerpt' )->justReturn( 'Excerpt' );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/events/fair/' );
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );

		$this->assertCount( 3, Head::graph()['@graph'], 'No event node without a date.' );

		$this->meta[30]['_qe_date'] = '2026-05-01';
		$graph                      = Head::graph()['@graph'];
		$this->assertSame( 'Event', $graph[3]['@type'] );
		$this->assertSame( '2026-05-01', $graph[3]['startDate'] );

		$this->meta[30]['_qe_time'] = '10:30';
		$this->assertSame( '2026-05-01T10:30:00+06:00', Head::graph()['@graph'][3]['startDate'] );
		$this->meta[30]['_qe_time'] = 'soon';
		$this->assertSame( '2026-05-01Tsoon', Head::graph()['@graph'][3]['startDate'], 'Unparseable time is passed through.' );
		$this->assertArrayNotHasKey( '@context', $graph[3] );
	}

	public function test_graph_without_canonical() {
		$this->settings( array( 'seo' => array( 'breadcrumbs' => false ) ) );
		Functions\when( 'wp_get_document_title' )->justReturn( 'Doc' );
		$this->conditionals( array( 'is_archive', 'is_category' ) );
		Functions\when( 'get_queried_object' )->justReturn( null );
		Functions\when( 'get_term_link' )->justReturn( new \stdClass() );
		Functions\when( 'term_description' )->justReturn( '' );
		$this->assertSame( 'https://example.test/', Head::graph()['@graph'][2]['url'] );
	}
}
