<?php
/**
 * Elementor integration: "QE Education" widget category, widgets, dynamic
 * tags and their assets (loaded only on pages that use the widgets).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Integration.
 */
final class Integration {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'widgets' ) );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'tags' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'after_theme_styles' ), 20 );
	}

	/**
	 * Print the widget styles after the theme's stylesheet (`qe-edu`), so
	 * widget rules such as the outline button win over the theme's generic
	 * button styles whatever order things were enqueued in.
	 *
	 * @return void
	 */
	public function after_theme_styles() {
		$styles = wp_styles();
		if ( ! isset( $styles->registered['qe-edu-core-widgets'], $styles->registered['qe-edu'] ) ) {
			return;
		}

		$widgets = $styles->registered['qe-edu-core-widgets'];
		if ( ! in_array( 'qe-edu', $widgets->deps, true ) ) {
			$widgets->deps[] = 'qe-edu';
		}
	}

	/**
	 * Widget classes.
	 *
	 * @return string[]
	 */
	public static function widget_classes() {
		return array(
			Widgets\AnimatedHero::class,
			Widgets\BenefitsForm::class,
			Widgets\RegistrationForm::class,
			Widgets\ServiceSlider::class,
			Widgets\DestinationsGrid::class,
			Widgets\NewsSlider::class,
			Widgets\LogoCarousel::class,
			Widgets\CtaDownload::class,
			Widgets\StatsCounter::class,
			Widgets\MegaMenu::class,
			Widgets\TopBar::class,
			Widgets\Newsletter::class,
			Widgets\EventList::class,
			Widgets\TeamGrid::class,
			Widgets\Testimonials::class,
			Widgets\Faq::class,
			Widgets\Breadcrumbs::class,
			Widgets\PageTitleBanner::class,
			Widgets\SiteLogo::class,
			Widgets\SocialLinks::class,
		);
	}

	/**
	 * Add the widget category at the top of the panel.
	 *
	 * @param object $manager Elements manager.
	 * @return void
	 */
	public function category( $manager ) {
		$manager->add_category(
			'qe-education',
			array(
				'title' => __( 'QE Education', 'qe-edu-core' ),
				'icon'  => 'eicon-library-open',
			)
		);
	}

	/**
	 * Register the widgets.
	 *
	 * @param object $manager Widgets manager.
	 * @return void
	 */
	public function widgets( $manager ) {
		foreach ( self::widget_classes() as $class ) {
			$manager->register( new $class() );
		}
	}

	/**
	 * Register the dynamic tags.
	 *
	 * @param object $manager Dynamic tags manager.
	 * @return void
	 */
	public function tags( $manager ) {
		$manager->register_group( 'qe-edu', array( 'title' => __( 'Theme Settings', 'qe-edu-core' ) ) );
		$manager->register( new Tags\SettingText() );
		$manager->register( new Tags\SettingUrl() );
		$manager->register( new Tags\Logo() );
	}

	/**
	 * Register (not enqueue) widget assets; Elementor enqueues them only on
	 * pages that contain a widget depending on them.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'qe-edu-core-widgets', QE_EDU_CORE_URL . 'assets/css/widgets.css', array(), QE_EDU_CORE_VERSION );
		wp_register_script(
			'qe-edu-core-widgets',
			QE_EDU_CORE_URL . 'assets/js/widgets.min.js',
			array( 'elementor-frontend' ),
			QE_EDU_CORE_VERSION,
			array( 'in_footer' => true )
		);
		wp_localize_script(
			'qe-edu-core-widgets',
			'qeEduWidgets',
			array(
				'pause' => __( 'Pause', 'qe-edu-core' ),
				'play'  => __( 'Play', 'qe-edu-core' ),
				'error' => __( 'Sorry, something went wrong. Please try again.', 'qe-edu-core' ),
			)
		);

		// A header or footer template shows our widgets on every page: load
		// their styles in <head> instead of where the template renders, so the
		// header never shows unstyled first (layout shift).
		if ( absint( Settings::get( 'templates.header' ) ) || absint( Settings::get( 'templates.footer' ) ) ) {
			wp_enqueue_style( 'qe-edu-core-widgets' );
		}
	}
}
