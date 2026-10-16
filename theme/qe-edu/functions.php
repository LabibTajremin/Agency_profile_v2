<?php
/**
 * Theme bootstrap. Loads the files in /inc only.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

define( 'QE_EDU_VERSION', '1.0.0' );
define( 'QE_EDU_DIR', get_template_directory() );
define( 'QE_EDU_URI', get_template_directory_uri() );

require_once QE_EDU_DIR . '/inc/template-tags.php';
require_once QE_EDU_DIR . '/inc/setup.php';
require_once QE_EDU_DIR . '/inc/enqueue.php';
require_once QE_EDU_DIR . '/inc/elementor-compat.php';
require_once QE_EDU_DIR . '/inc/customizer.php';
require_once QE_EDU_DIR . '/inc/tgmpa.php';
