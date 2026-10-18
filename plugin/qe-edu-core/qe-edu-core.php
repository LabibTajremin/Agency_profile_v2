<?php
/**
 * Plugin Name:       QE Edu Core
 * Plugin URI:        https://example.com/qe-edu
 * Description:       Companion plugin for the QE Edu theme: Theme Settings panel, 12 accent palettes, Elementor widgets, content types, forms, SEO and one-click demo import.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Your Education Group
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       qe-edu-core
 * Domain Path:       /languages
 *
 * @package QeEdu\Core
 */

defined( 'ABSPATH' ) || exit;

define( 'QE_EDU_CORE_VERSION', '1.0.0' );
define( 'QE_EDU_CORE_FILE', __FILE__ );
define( 'QE_EDU_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'QE_EDU_CORE_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'QeEdu\\Core\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = QE_EDU_CORE_DIR . 'src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

register_activation_hook( __FILE__, array( \QeEdu\Core\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \QeEdu\Core\Plugin::class, 'deactivate' ) );

// Boot on init (priority 1) so translations are available to every module.
add_action( 'init', array( \QeEdu\Core\Plugin::instance(), 'boot' ), 1 );
