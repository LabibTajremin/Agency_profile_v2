<?php
/**
 * WP-CLI fixture: a page containing every QE Education widget with default
 * settings ("QE Widgets"), plus a menu for the Mega Menu / Top Bar widgets.
 * Prints the page id.
 *
 * Usage: wp eval-file tests/e2e/fixtures/create-qe-widgets-page.php
 *
 * @package QeEdu
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WP-CLI script scope.

$menu = wp_get_nav_menu_object( 'QE Test Menu' );
$menu_id = $menu ? $menu->term_id : wp_create_nav_menu( 'QE Test Menu' );
if ( ! $menu ) {
	$parent = wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'About Us', 'menu-item-url' => home_url( '/about/' ), 'menu-item-status' => 'publish' ) );
	foreach ( array( 'About QE Group', 'What We Do', 'Vision & Values' ) as $title ) {
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => $title, 'menu-item-url' => home_url( '/' ), 'menu-item-parent-id' => $parent, 'menu-item-status' => 'publish', 'menu-item-description' => 'Short description' ) );
	}
	update_post_meta( $parent, '_qe_mega_columns', 3 );
	wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Study Abroad', 'menu-item-url' => home_url( '/' ), 'menu-item-status' => 'publish' ) );
}

$elements = array();
foreach ( \QeEdu\Core\Elementor\Integration::widget_classes() as $class ) {
	$widget   = new $class();
	$type     = $widget->get_name();
	$settings = array();
	if ( in_array( $type, array( 'qe-mega-menu', 'qe-top-bar' ), true ) ) {
		$settings = array( 'menu' => (string) $menu_id, 'source' => 'qe-top-bar' === $type ? 'links' : '' );
	}
	$elements[] = array(
		'id'       => substr( md5( 'c' . $type ), 0, 7 ),
		'elType'   => 'container',
		'settings' => array(),
		'elements' => array(
			array(
				'id'         => substr( md5( 'w' . $type ), 0, 7 ),
				'elType'     => 'widget',
				'widgetType' => $type,
				'settings'   => $settings,
				'elements'   => array(),
			),
		),
	);
}

$existing = get_page_by_path( 'qe-widgets' );
$page_id  = $existing ? $existing->ID : wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'QE Widgets',
		'post_name'   => 'qe-widgets',
	)
);

update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
\Elementor\Plugin::$instance->files_manager->clear_cache();

WP_CLI::log( (string) $page_id );
