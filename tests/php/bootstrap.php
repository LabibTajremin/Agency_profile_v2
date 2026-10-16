<?php
/**
 * PHPUnit bootstrap: Composer autoload, WordPress constants and minimal
 * stubs for WordPress / Elementor classes. WordPress functions are mocked
 * per test with Brain Monkey.
 *
 * @package QeEdu\Tests
 */

// Patchwork first, so the internals listed in patchwork.json can be mocked.
require_once dirname( __DIR__, 2 ) . '/vendor/antecedent/patchwork/Patchwork.php';
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

define( 'ABSPATH', sys_get_temp_dir() . '/qe-edu-wp/' );
define( 'WP_PLUGIN_DIR', __DIR__ . '/fixtures/plugins' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );

define( 'QE_EDU_VERSION', '1.0.0' );
define( 'QE_EDU_DIR', dirname( __DIR__, 2 ) . '/theme/qe-edu' );
define( 'QE_EDU_URI', 'https://example.test/wp-content/themes/qe-edu' );

define( 'QE_EDU_CORE_VERSION', '1.0.0' );
define( 'QE_EDU_CORE_FILE', dirname( __DIR__, 2 ) . '/plugin/qe-edu-core/qe-edu-core.php' );
define( 'QE_EDU_CORE_DIR', dirname( __DIR__, 2 ) . '/plugin/qe-edu-core/' );
define( 'QE_EDU_CORE_URL', 'https://example.test/wp-content/plugins/qe-edu-core/' );

require_once __DIR__ . '/stubs/wordpress.php';
require_once __DIR__ . '/stubs/elementor.php';
