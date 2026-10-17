<?php
/**
 * Behaviour of individual widgets (beyond the all-widgets gate).
 *
 * @package QeEdu\Tests
 */

namespace QeEdu\Tests\Plugin\Widgets;

use Brain\Monkey\Functions;
use QeEdu\Core\Elementor\Widgets\AnimatedHero;
use QeEdu\Core\Elementor\Widgets\Base;
use QeEdu\Core\Elementor\Widgets\BenefitsForm;
use QeEdu\Core\Elementor\Widgets\Breadcrumbs;
use QeEdu\Core\Elementor\Widgets\CtaDownload;
use QeEdu\Core\Elementor\Widgets\DestinationsGrid;
use QeEdu\Core\Elementor\Widgets\EventList;
use QeEdu\Core\Elementor\Widgets\Faq;
use QeEdu\Core\Elementor\Widgets\LogoCarousel;
use QeEdu\Core\Elementor\Widgets\MegaMenu;
use QeEdu\Core\Elementor\Widgets\NewsSlider;
use QeEdu\Core\Elementor\Widgets\Newsletter;
use QeEdu\Core\Elementor\Widgets\PageTitleBanner;
use QeEdu\Core\Elementor\Widgets\RegistrationForm;
use QeEdu\Core\Elementor\Widgets\ServiceSlider;
use QeEdu\Core\Elementor\Widgets\SiteLogo;
use QeEdu\Core\Elementor\Widgets\SocialLinks;
use QeEdu\Core\Elementor\Widgets\StatsCounter;
use QeEdu\Core\Elementor\Widgets\TeamGrid;
use QeEdu\Core\Elementor\Widgets\Testimonials;
use QeEdu\Core\Elementor\Widgets\TopBar;

/**
 * Widget branches.
 */
final class WidgetBranchesTest extends WidgetTestCase {

	public function test_hero_backgrounds_and_floats() {
		$light = $this->render( AnimatedHero::class );
		$this->assertMatchesRegularExpression( '#<span class="qe-hero__rotator" aria-hidden="true" data-words="[^"]+">(<span class="qe-hero__sizer">[^<]+</span>){3}<span class="qe-hero__live"><span class="qe-hero__word">#', $light, 'Every phrase reserves its space.' );
		$this->assertStringContainsString( 'class="qe-hero qe-hero--light"', $light );
		$this->assertStringContainsString( '<h1 class="qe-hero__title">', $light );
		$this->assertStringContainsString( '<span class="screen-reader-text">15 Years of Excellence, Global Education Pathways, Student Success Worldwide</span>', $light );
		$this->assertStringContainsString( 'aria-hidden="true" data-words=', $light );
		$this->assertStringContainsString( '<span class="qe-hero__cursor"></span>', $light );

		$video = $this->render(
			AnimatedHero::class,
			array(
				'background'       => 'video',
				'background_image' => array( 'url' => 'https://example.test/poster.jpg' ),
				'background_video' => 'https://example.test/v.mp4',
				'heading_tag'      => 'h2',
				'cursor'           => '',
				'words'            => array( array( 'word' => '' ) ),
				'floats'           => array(
					array(
						'image' => array( 'id' => 4 ),
						'x'     => 60,
						'y'     => 10,
						'size'  => 200,
						'depth' => 0.8,
					),
				),
			)
		);
		$this->assertStringContainsString( 'qe-hero--video has-overlay', $video );
		$this->assertStringContainsString( '<img src="https://example.test/poster.jpg" class="qe-hero__bg" alt="" loading="lazy" decoding="async" fetchpriority="high">', $video );
		$this->assertStringContainsString( '<video class="qe-hero__video" src="https://example.test/v.mp4" autoplay muted loop playsinline aria-hidden="true">', $video );
		$this->assertStringContainsString( '<h2 class="qe-hero__title">', $video );
		$this->assertStringNotContainsString( 'qe-hero__rotator', $video );
		$this->assertStringContainsString( 'data-depth="0.8" style="left:60%;top:10%;width:200px"', $video );
		$this->assertStringContainsString( '<img src="https://example.test/4.jpg"', $video );

		$gradient = $this->render( AnimatedHero::class, array( 'background' => 'gradient', 'overlay' => 'yes' ) );
		$this->assertStringContainsString( 'qe-hero--gradient"', $gradient );
		$this->assertStringNotContainsString( 'has-overlay', $gradient );

		$this->assertStringContainsString( 'qe-hero--light', $this->render( AnimatedHero::class, array( 'background' => 'evil' ) ) );
	}

	public function test_base_print_helpers() {
		$button = $this->render( AnimatedHero::class, array( 'button_1_link' => array( 'url' => '' ), 'button_2_text' => '' ) );
		$this->assertStringContainsString( '<span class="qe-btn">Start Your Journey</span>', $button );
		$this->assertStringNotContainsString( 'qe-btn--ghost', $button );

		$external = $this->render( AnimatedHero::class, array( 'button_1_link' => array( 'url' => 'https://x.test', 'is_external' => 'on', 'nofollow' => 'on' ) ) );
		$this->assertStringContainsString( 'href="https://x.test" target="_blank" rel="nofollow"', $external );

		// wp_get_attachment_image() returning '' falls back to the URL.
		Functions\when( 'wp_get_attachment_image' )->justReturn( '' );
		$this->meta[4]['_wp_attachment_image_alt'] = 'Alt';
		$html = $this->render( CtaDownload::class, array( 'image' => array( 'id' => 4, 'url' => 'https://example.test/i.jpg' ) ) );
		$this->assertStringContainsString( '<img src="https://example.test/i.jpg" class="qe-cta__img" alt="Company Profile" loading="lazy" decoding="async">', $html );

		$this->assertSame(
			array( array( 'a' => 1, 'b' => 2 ) ),
			Base::items_with( array( array( 'b' => 2 ), 'x' ), array( 'a' => 1, 'b' => 0 ) )
		);
		$this->assertSame( array(), Base::items_with( 'x', array() ) );
	}

	public function test_alt_text_from_media_library() {
		$this->meta[4]['_wp_attachment_image_alt'] = 'Students on campus';
		$html = $this->render( CtaDownload::class, array( 'image' => array( 'id' => 4 ) ) );
		$this->assertStringContainsString( 'alt="Students on campus"', $html );
	}

	public function test_cta_file_sources() {
		$this->assertSame( '', CtaDownload::file_url( array( 'file' => array( 'url' => '' ) ) ) );
		$this->settings( array( 'brand' => array( 'profile_pdf' => 6 ) ) );
		$this->assertSame( 'https://example.test/6.pdf', CtaDownload::file_url( array( 'file' => array( 'url' => '' ) ) ) );

		$html = $this->render( CtaDownload::class );
		$this->assertStringContainsString( 'download="6.pdf" class="qe-btn qe-btn--download" href="https://example.test/6.pdf"', $html );
		$this->assertStringContainsString( 'qe-btn--download', $html );

		$this->settings( array() );
		$this->editing = true;
		$this->assertStringContainsString( 'qe-editor-notice', $this->render( CtaDownload::class ) );
		$this->editing = false;
		$this->assertStringNotContainsString( 'qe-editor-notice', $this->render( CtaDownload::class ) );
	}

	public function test_services_cpt_and_samples() {
		$this->assertStringNotContainsString( '<h2', $this->render( ServiceSlider::class, array( 'heading' => '  ' ) ) );

		$html = $this->render( ServiceSlider::class );
		$this->assertSame( 4, substr_count( $html, 'qe-scard"' ) );
		$this->assertStringContainsString( 'qe-placeholder', $html );

		$this->with_posts();
		$this->meta[101]['_qe_link']        = 'https://custom.test';
		$this->meta[101]['_qe_button_text'] = 'Discover';
		$html = $this->render( ServiceSlider::class );
		$this->assertStringContainsString( 'href="https://custom.test">Discover<span class="qe-sr">: Post A</span></a>', $html, 'Hidden context names the target.' );
		$this->assertStringContainsString( 'href="https://example.test/p/102/">Learn More<span class="qe-sr">: Post B</span></a>', $html );
		$this->assertStringContainsString( '<div class="swiper-slide"><article class="qe-scard">', $html );

		$static = $this->render( ServiceSlider::class, array( 'static_editor' => 'yes' ) );
		$this->assertStringContainsString( '&quot;static&quot;:false', $static );
		$this->editing = true;
		$static        = $this->render( ServiceSlider::class, array( 'static_editor' => 'yes', 'autoplay' => '', 'arrows' => '', 'dots' => '' ) );
		$this->assertStringContainsString( '&quot;static&quot;:true', $static );
		$this->assertStringContainsString( '&quot;autoplay&quot;:0', $static );
		$this->assertStringNotContainsString( 'qe-slider__arrow', $static );
		$this->assertStringNotContainsString( 'qe-slider__dots', $static );
	}

	public function test_destinations_layouts() {
		$grid = $this->render( DestinationsGrid::class, array( 'count' => 3 ) );
		$this->assertSame( 3, substr_count( $grid, 'class="qe-dcard ' ) );
		$this->assertStringContainsString( '<div class="qe-dcard qe-dcard--zoom">', $grid );
		$this->assertStringContainsString( 'Study in United Kingdom', $grid );

		$this->with_posts();
		$this->meta[102]['_qe_country'] = '';
		$this->meta[102]['_qe_link']    = 'https://ie.test';
		$slider                         = $this->render( DestinationsGrid::class, array( 'layout' => 'slider', 'hover' => 'lift' ) );
		$this->assertStringContainsString( '<div class="swiper-slide"><a class="qe-dcard qe-dcard--lift" href="https://example.test/p/101/">', $slider );
		$this->assertStringContainsString( 'href="https://ie.test"', $slider );
		$this->assertStringContainsString( 'Study in Post B', $slider );
		$this->assertStringContainsString( 'qe-dcard__flag', $slider );
		$this->assertStringContainsString( 'swiper-wrapper', $slider );

		$this->assertStringNotContainsString( 'qe-dcard__flag', $this->render( DestinationsGrid::class, array( 'show_flag' => '', 'hover' => 'odd' ) ) );
	}

	public function test_news_sources() {
		$this->assertStringContainsString( 'News is coming soon.', $this->render( NewsSlider::class ) );

		$manual = $this->render( NewsSlider::class, array( 'source' => 'manual' ) );
		$this->assertStringContainsString( 'Business &amp; Management', $manual );
		$this->assertStringContainsString( '<span class="qe-link-arrow">Read more</span>', $manual );

		$this->with_posts();
		$posts = $this->render( NewsSlider::class );
		$this->assertStringContainsString( '<p class="qe-ncard__meta">January 1, 2026</p>', $posts );

		$this->options['date_format'] = 'F j, Y';
		$events                       = $this->render( NewsSlider::class, array( 'source' => 'events' ) );
		$this->assertStringContainsString( '<p class="qe-ncard__meta">January 1, 2030</p>', $events );

		$this->meta[101]['_qe_date'] = '';
		$this->meta[102]['_qe_date'] = '';
		$this->assertStringNotContainsString( 'qe-ncard__meta', $this->render( NewsSlider::class, array( 'source' => 'events' ) ) );
	}

	public function test_logo_modes() {
		$this->editing = true;
		$this->assertStringContainsString( 'qe-editor-notice', $this->render( LogoCarousel::class ) );
		$this->editing = false;
		$this->assertSame( '', $this->render( LogoCarousel::class ) );

		$this->with_posts();
		$marquee = $this->render( LogoCarousel::class, array( 'partner_type' => 'university' ) );
		$this->assertSame( 2, substr_count( $marquee, 'qe-marquee__track' ) );
		$this->assertStringContainsString( 'aria-hidden="true" inert', $marquee );
		$this->assertStringContainsString( 'href="https://partner.test/101" target="_blank" rel="noopener"', $marquee );
		$this->assertStringContainsString( '<span class="qe-logo__name">Post A</span>', $marquee );
		$this->assertStringContainsString( 'is-grayscale', $marquee );

		$step = $this->render( LogoCarousel::class, array( 'mode' => 'step', 'grayscale' => '' ) );
		$this->assertStringContainsString( 'qe-logos--step"', $step );
		$this->assertStringContainsString( '<div class="swiper-slide"><a class="qe-logo "', $step );

		$static = $this->render( LogoCarousel::class, array( 'mode' => 'static' ) );
		$this->assertStringContainsString( 'qe-logos__grid', $static );

		$gallery = $this->render(
			LogoCarousel::class,
			array(
				'source'  => 'gallery',
				'mode'    => 'odd',
				'gallery' => array( array( 'id' => 3, 'url' => 'https://example.test/3.png' ) ),
			)
		);
		$this->assertStringContainsString( 'qe-logos--marquee', $gallery );
		$this->assertStringContainsString( '<span class="qe-logo ">', $gallery );
	}

	public function test_mega_menu() {
		Functions\when( 'get_queried_object_id' )->justReturn( 78 );
		$this->meta[1]['_qe_mega_columns'] = '3';
		$this->meta[1]['_qe_mega_image']   = '12';
		$this->with_menu();

		$html = $this->render( MegaMenu::class, array( 'menu' => '5', 'breakpoint' => '767' ) );
		$this->assertStringContainsString( 'data-breakpoint="767"', $html );
		$this->assertStringContainsString( '<li class="qe-mega__item has-children is-mega is-current">', $html, 'Parent of the current page' );
		$this->assertStringContainsString( '<li class="qe-mega__item is-current">', $html );
		$this->assertStringContainsString( 'style="--qe-mega-cols:3"', $html );
		$this->assertStringContainsString( '<img src="https://example.test/12.jpg" class="qe-mega__img" alt="About">', $html );
		$this->assertStringContainsString( '<span class="qe-mega__desc">Desc</span>', $html );
		$this->assertStringContainsString( 'title="Tip"', $html );
		$this->assertStringContainsString( 'target="_blank" rel="noopener"', $html );
		$this->assertStringContainsString( 'Show submenu: About', $html );
		$this->assertStringContainsString( 'Orphan', $html, 'Items with a missing parent are kept at the top level' );

		$this->assertStringContainsString( 'data-breakpoint="1024"', $this->render( MegaMenu::class, array( 'menu' => '5' ) ) );

		$this->editing = true;
		$this->assertStringContainsString( 'qe-editor-notice', $this->render( MegaMenu::class ) );
	}

	public function test_mega_menu_tree() {
		$this->with_menu();
		$tree = MegaMenu::tree( $this->menu_items );
		$this->assertSame( array( 'About', 'Study', 'Orphan' ), array_map( static fn( $n ) => $n['item']->title, $tree ) );
		$this->assertCount( 2, $tree[0]['children'] );
		Functions\when( 'get_queried_object_id' )->justReturn( 0 );
		$this->assertFalse( MegaMenu::is_current( $tree[0] ) );
	}

	public function test_newsletter_switched_off_and_shortcode() {
		$this->settings( array( 'footer' => array( 'newsletter' => false ) ) );
		$this->assertSame( '', $this->render( Newsletter::class ) );
		$this->editing = true;
		$this->assertStringContainsString( 'qe-editor-notice', $this->render( Newsletter::class ) );
		$this->editing = false;

		$this->settings( array() );
		$html = $this->render( Newsletter::class );
		$this->assertStringContainsString( 'name="action" value="qe_edu_subscribe"', $html );
		$this->assertStringContainsString( 'name="post_id" value="42"', $html );
		$this->assertStringContainsString( 'name="nonce" value="nonce123"', $html );
		$this->assertStringContainsString( 'name="website"', $html );

		$short = $this->render( Newsletter::class, array( 'provider' => 'shortcode', 'shortcode' => '[form]' ) );
		$this->assertStringContainsString( '[form]', $short );
		$this->assertStringNotContainsString( '<form', $short );
	}

	public function test_breadcrumbs_and_banner() {
		$this->settings( array( 'seo' => array( 'breadcrumbs' => false ) ) );
		$this->assertSame( '', $this->render( Breadcrumbs::class ) );
		$this->editing = true;
		$this->assertStringContainsString( 'qe-editor-notice', $this->render( Breadcrumbs::class ) );
		$this->editing = false;
		$this->assertStringNotContainsString( 'qe-breadcrumbs', $this->render( PageTitleBanner::class ) );

		$this->settings( array() );
		$crumbs = $this->render( Breadcrumbs::class );
		$this->assertStringContainsString( '<a href="https://example.test/">Home</a><span class="qe-breadcrumbs__sep" aria-hidden="true">›</span>', $crumbs );
		$this->assertStringContainsString( '<span aria-current="page">Archive</span>', $crumbs );

		$banner = $this->render( PageTitleBanner::class, array( 'background_image' => array( 'url' => 'https://example.test/b.jpg' ) ) );
		$this->assertStringContainsString( 'qe-banner has-image', $banner );
		$this->assertStringContainsString( 'fetchpriority="high"', $banner, 'The banner is the largest paint.' );
		$this->assertStringContainsString( '<h1 class="qe-banner__title">Archive</h1>', $banner );
		$this->assertStringContainsString( 'qe-breadcrumbs', $banner );

		Functions\when( 'is_archive' )->justReturn( false );
		$this->assertStringContainsString( '<h1 class="qe-banner__title">Current page</h1>', $this->render( PageTitleBanner::class ) );
	}

	public function test_page_title_banner_title_for_every_request() {
		$s = array( 'title' => '' );
		$this->assertSame( 'Own title', PageTitleBanner::title( array( 'title' => 'Own title' ) ) );

		Functions\when( 'get_the_archive_title' )->alias(
			static function () {
				return ( false !== has_filter( 'get_the_archive_title_prefix', '__return_empty_string' ) ? '' : 'Category: ' ) . '<span>News</span> ';
			}
		);
		$this->assertSame( 'News', PageTitleBanner::title( $s ), 'Prefix removed in the banner only.' );
		$this->assertFalse( has_filter( 'get_the_archive_title_prefix', '__return_empty_string' ) );

		Functions\when( 'is_archive' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( true );
		$this->assertSame( 'Page not found', PageTitleBanner::title( $s ) );

		Functions\when( 'is_search' )->justReturn( true );
		Functions\when( 'get_search_query' )->justReturn( 'mba' );
		$this->assertSame( 'Search results for “mba”', PageTitleBanner::title( $s ) );

		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'single_post_title' )->justReturn( 'News' );
		$this->assertSame( 'News', PageTitleBanner::title( $s ), 'Blog page title, not the first post.' );

		Functions\when( 'is_front_page' )->justReturn( true );
		Functions\when( 'is_search' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );
		$this->assertSame( 'Current page', PageTitleBanner::title( $s ) );

		Functions\when( 'is_singular' )->justReturn( true );
		Functions\when( 'get_queried_object_id' )->justReturn( 7 );
		Functions\when( 'get_the_title' )->alias(
			static function ( $id = 0 ) {
				return 7 === $id ? 'Queried post' : 'Loop post';
			}
		);
		$this->assertSame( 'Queried post', PageTitleBanner::title( $s ) );
	}

	public function test_hero_button_colour_leaves_the_ghost_button_alone() {
		$hero = new AnimatedHero();
		( new \ReflectionMethod( AnimatedHero::class, 'register_controls' ) )->invoke( $hero );
		$this->assertSame(
			array( '{{WRAPPER}} .qe-hero__actions .qe-btn:not(.qe-btn--ghost)' => 'background-color: {{VALUE}};' ),
			$hero->controls['button_bg']['selectors']
		);
	}

	public function test_images_are_loaded_in_one_go() {
		$widget = ( new \ReflectionClass( DestinationsGrid::class ) )->newInstanceWithoutConstructor();
		$prime  = new \ReflectionMethod( DestinationsGrid::class, 'prime_images' );
		$prime->invoke(
			$widget,
			array(
				array( 'image' => array( 'id' => '5' ), 'flag' => 9 ),
				array( 'image' => 5 ),
				array( 'image' => array( 'url' => 'x' ) ),
				array( 'other' => 3 ),
			),
			array( 'image', 'flag' )
		);
		$this->assertSame( array( array( 5, 9 ) ), $this->primed );

		$this->primed = array();
		$prime->invoke( $widget, array( array( 'image' => 0 ) ) );
		$this->assertSame( array(), $this->primed, 'Nothing to load.' );

		$this->with_posts();
		$this->render( DestinationsGrid::class );
		$this->render( ServiceSlider::class );
		$this->render( NewsSlider::class );
		$this->render( Testimonials::class );
		$this->render( LogoCarousel::class );
		$this->render( TeamGrid::class );
		$this->assertNotSame( array(), $this->primed );
	}

	public function test_print_image_forced_alt() {
		$widget = ( new \ReflectionClass( DestinationsGrid::class ) )->newInstanceWithoutConstructor();
		$print  = new \ReflectionMethod( DestinationsGrid::class, 'print_image' );
		$this->meta[7]['_wp_attachment_image_alt'] = 'Library alt';
		Functions\when( 'wp_get_attachment_image' )->alias(
			static function ( $id, $size, $icon, $attrs ) {
				return '<img alt="' . $attrs['alt'] . '" data-x="' . ( isset( $attrs['data-x'] ) ? $attrs['data-x'] : '' ) . '">';
			}
		);

		$html = $this->capture( static function () use ( $print, $widget ) { $print->invoke( $widget, 7, 'large', 'c', 'Fallback', array( 'alt' => '', 'data-x' => '1' ) ); } );
		$this->assertSame( '<img alt="" data-x="1">', $html );
		$html = $this->capture( static function () use ( $print, $widget ) { $print->invoke( $widget, 7, 'large', 'c', 'Fallback' ); } );
		$this->assertSame( '<img alt="Library alt" data-x="">', $html );
		$html = $this->capture( static function () use ( $print, $widget ) { $print->invoke( $widget, array( 'url' => 'https://example.test/u.jpg' ), 'large', 'c', 'Fallback', array( 'alt' => '' ) ); } );
		$this->assertSame( '<img src="https://example.test/u.jpg" class="c" alt="" loading="lazy" decoding="async">', $html, 'One alt attribute only.' );
		$html = $this->capture( static function () use ( $print, $widget ) { $print->invoke( $widget, array( 'url' => 'https://example.test/u.jpg' ), 'large', 'c', 'Fallback' ); } );
		$this->assertStringContainsString( 'alt="Fallback"', $html );
	}

	public function test_site_logo_variants() {
		$this->assertStringContainsString( '<span class="qe-site-logo__name">Test Site</span>', $this->render( SiteLogo::class ) );

		Functions\when( 'get_theme_mod' )->justReturn( 3 );
		$this->assertSame( 3, SiteLogo::logo_id( 'default' ) );

		$this->settings( array( 'brand' => array( 'logo' => 4, 'logo_dark' => 5 ) ) );
		$this->assertSame( 4, SiteLogo::logo_id( 'default' ) );
		$this->assertSame( 5, SiteLogo::logo_id( 'dark' ) );
		$this->assertStringContainsString( 'src="https://example.test/5.jpg"', $this->render( SiteLogo::class, array( 'variant' => 'dark' ) ) );

		$this->settings( array( 'brand' => array( 'logo' => 4 ) ) );
		$this->assertSame( 4, SiteLogo::logo_id( 'dark' ), 'Falls back to the normal logo' );
	}

	public function test_social_links() {
		$this->assertSame( '', $this->render( SocialLinks::class ) );
		$this->editing = true;
		$this->assertStringContainsString( 'qe-editor-notice', $this->render( SocialLinks::class ) );
		$this->editing = false;

		$this->settings(
			array(
				'social'  => array( 'x' => 'https://x.test/me' ),
				'contact' => array( 'whatsapp' => '+1 555' ),
			)
		);
		$html = $this->render( SocialLinks::class, array( 'shape' => 'square' ) );
		$this->assertStringContainsString( 'qe-social--square', $html );
		$this->assertStringContainsString( 'aria-label="X (Twitter)"', $html );
		$this->assertStringContainsString( 'href="https://wa.me/1555"', $html );
		$this->assertSame( 2, count( \Elementor\Icons_Manager::$rendered ) );

		$this->assertStringNotContainsString( 'wa.me', $this->render( SocialLinks::class, array( 'whatsapp' => '', 'shape' => 'odd' ) ) );
		$this->assertSame( '', SocialLinks::whatsapp_url( 'none' ) );
	}

	public function test_team_options() {
		$this->with_posts();
		$html = $this->render( TeamGrid::class, array( 'show_bio' => 'yes' ) );
		$this->assertStringContainsString( '<p class="qe-person__bio">Excerpt A</p>', $html );
		$this->assertStringContainsString( 'href="https://linkedin.test/101"', $html );
		$this->assertStringContainsString( 'qe-placeholder', $html );

		$this->meta[101]['_qe_linkedin'] = '';
		$this->assertSame( 1, substr_count( $this->render( TeamGrid::class ), 'qe-person__link' ) );
		$this->assertStringNotContainsString( 'qe-person__link', $this->render( TeamGrid::class, array( 'show_linkedin' => '' ) ) );
	}

	public function test_top_bar_sources() {
		$this->settings( array() );
		$links = $this->render( TopBar::class, array( 'links' => array( array( 'text' => 'Blog', 'link' => array( 'url' => 'https://b.test', 'is_external' => 'on' ) ) ) ) );
		$this->assertStringContainsString( '<a href="https://b.test" target="_blank" rel="noopener">Blog</a>', $links );
		$this->assertStringContainsString( '<p class="qe-topbar-w__contact"></p>', $links );

		$this->with_menu();
		$this->menu_items[3]->target = '_blank';
		$menu                        = $this->render( TopBar::class, array( 'source' => 'menu', 'menu' => '5', 'show_contact' => '' ) );
		$this->assertSame( 2, substr_count( $menu, '<li>' ), 'Top-level items only' );
		$this->assertStringContainsString( 'target="_blank"', $menu );

		$this->assertSame( 0, substr_count( $this->render( TopBar::class, array( 'source' => 'menu', 'menu' => '' ) ), '<li>' ) );

		$this->settings( array( 'contact' => array( 'phone' => '+1 (555) 01', 'email' => 'a@b.test' ) ) );
		$contact = $this->render( TopBar::class );
		$this->assertStringContainsString( 'href="tel:+155501"', $contact );
		$this->assertStringContainsString( 'href="mailto:a@b.test"', $contact );
	}

	public function test_registration_form_markup() {
		$html = $this->render( RegistrationForm::class );
		$this->assertStringContainsString( 'data-qe-form="register"', $html );
		$this->assertSame( 2, substr_count( $html, '<fieldset class="qe-form__step"' ) );
		$this->assertStringContainsString( 'data-step="2" hidden', $html );
		$this->assertStringContainsString( 'autocomplete="given-name"', $html );
		$this->assertStringContainsString( 'autocomplete="bday"', $html );
		$this->assertStringContainsString( 'autocomplete="country-name"', $html );
		$this->assertStringContainsString( '<option value="Ireland">Ireland</option>', $html );
		$this->assertStringContainsString( 'required aria-required="true"', $html );
		$this->assertStringContainsString( 'data-step-only="2" hidden', $html );
		$this->assertStringNotContainsString( 'type="password"', $html, 'Registration closed: no password field' );

		$this->options['users_can_register'] = '1';
		$this->assertStringContainsString( 'type="password" id="qe-abc123-password" name="qe_password" autocomplete="new-password"', $this->render( RegistrationForm::class ) );

		$single = $this->render(
			RegistrationForm::class,
			array(
				'multi_step'   => '',
				'consent_text' => '',
				'fields'       => array(
					array( '_id' => 'msg', 'label' => 'Message', 'type' => 'textarea', 'required' => '', 'placeholder' => 'Hi', 'width' => 'half', 'step' => '2' ),
					array( '_id' => 'misc', 'label' => 'Misc', 'type' => 'text', 'required' => '' ),
				),
			)
		);
		$this->assertSame( 1, substr_count( $single, '<fieldset' ) );
		$this->assertStringContainsString( '<textarea id="qe-abc123-msg" name="qe_msg" autocomplete="on" data-type="textarea" rows="4" class="qe-form__input" placeholder="Hi"></textarea>', $single );
		$this->assertStringContainsString( 'qe-form__field--half', $single );
		$this->assertStringNotContainsString( 'qe-form__consent', $single );
		$this->assertStringNotContainsString( 'data-qe-next', $single );
		$this->assertStringContainsString( '<button type="submit" class="qe-btn" data-qe-submit>', $single );
	}

	public function test_benefits_layouts() {
		$html = $this->render( BenefitsForm::class, array( 'layout' => 'left' ) );
		$this->assertStringContainsString( 'qe-benefits--form-left', $html );
		$this->assertSame( 3, count( \Elementor\Icons_Manager::$rendered ) );
		$this->assertStringContainsString( 'data-qe-form="register"', $html );
		$this->assertStringContainsString( 'qe-benefits--form-right', $this->render( BenefitsForm::class, array( 'benefits' => array() ) ) );
	}

	public function test_stats_testimonials_faq_events() {
		$stats = $this->render( StatsCounter::class, array( 'duration' => 1.5 ) );
		$this->assertStringContainsString( 'data-duration="1.5"', $stats );
		$this->assertStringContainsString( 'data-to="45000">45,000</span>', $stats );

		$this->with_posts();
		$testimonials = $this->render( Testimonials::class );
		$this->assertStringContainsString( 'aria-label="Rated 5 out of 5"', $testimonials );
		$this->assertStringContainsString( '<span class="qe-tcard__role">Role 101</span>', $testimonials );
		$this->assertStringContainsString( '<div class="swiper-slide"><figure class="qe-tcard">', $testimonials );
		$manual = $this->render( Testimonials::class, array( 'source' => 'manual', 'show_rating' => '' ) );
		$this->assertStringContainsString( 'Amira K.', $manual );
		$this->assertStringNotContainsString( 'qe-stars', $manual );

		$faq = $this->render( Faq::class, array( 'first_open' => '', 'schema' => '' ) );
		$this->assertStringNotContainsString( ' open>', $faq );
		$this->assertStringNotContainsString( 'ld+json', $faq );
		$this->assertStringContainsString( '"@type":"FAQPage"', $this->render( Faq::class ) );
		$this->assertSame( 'Question', Faq::schema( array( array( 'question' => 'Q', 'answer' => 'A' ) ) )['mainEntity'][0]['@type'] );

		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.test/e.jpg' );
		$events = $this->render( EventList::class, array( 'layout' => 'grid', 'show_past' => 'yes' ) );
		$this->assertStringContainsString( 'qe-events--grid', $events );
		$this->assertStringContainsString( '<time class="qe-event__date" datetime="2030-01-01T10:00">', $events );
		$this->assertStringContainsString( '10:00 · Online', $events );
		$this->assertStringContainsString( 'Venue 102', $events );
		$this->assertStringContainsString( '"eventAttendanceMode":"https://schema.org/OnlineEventAttendanceMode"', $events );
		$this->assertStringContainsString( '"@type":"Place"', $events );
		$this->assertStringContainsString( '"image":"https://example.test/e.jpg"', $events );

		$this->meta[101]['_qe_date'] = '';
		$this->assertStringNotContainsString( 'ld+json', $this->render( EventList::class, array( 'schema' => '' ) ) );
	}
}
