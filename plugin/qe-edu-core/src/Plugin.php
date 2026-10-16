<?php
/**
 * Plugin bootstrap: wires every module.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Booted modules, keyed by class name.
	 *
	 * @var array<string,object>
	 */
	private $modules = array();

	/**
	 * Whether boot() ran.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Get the instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Reset the singleton (tests only).
	 *
	 * @return void
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Module classes, in boot order. Each exposes `register()`.
	 *
	 * @return string[]
	 */
	public function module_classes() {
		return (array) apply_filters(
			'qe_edu_core_modules',
			array(
				Settings\AdminPage::class,
				Colors\Styles::class,
				Colors\KitSync::class,
				Performance\Optimizer::class,
				PostTypes\Registrar::class,
				PostTypes\Leads::class,
				Forms\Handler::class,
				Elementor\Integration::class,
				Elementor\MenuFields::class,
				Frontend\MobileBar::class,
				Frontend\EditLinks::class,
			)
		);
	}

	/**
	 * Boot all modules.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( 'qe-edu', false, dirname( plugin_basename( QE_EDU_CORE_FILE ) ) . '/languages' );

		foreach ( $this->module_classes() as $class_name ) {
			$module = new $class_name();
			$module->register();
			$this->modules[ $class_name ] = $module;
		}

		do_action( 'qe_edu_core_loaded', $this );
	}

	/**
	 * Get a booted module.
	 *
	 * @param string $class_name Class name.
	 * @return object|null
	 */
	public function module( $class_name ) {
		return isset( $this->modules[ $class_name ] ) ? $this->modules[ $class_name ] : null;
	}

	/**
	 * Activation.
	 *
	 * @return void
	 */
	public static function activate() {
		// During activation `init` has already run, so boot here.
		self::instance()->boot();

		/**
		 * Fires when the plugin is activated (content types register, the
		 * Elementor Kit syncs).
		 */
		do_action( 'qe_edu_core_activated' );
		flush_rewrite_rules();
	}

	/**
	 * Deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
