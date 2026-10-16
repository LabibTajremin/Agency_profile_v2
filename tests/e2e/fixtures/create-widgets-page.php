<?php
/**
 * WP-CLI fixture: create (or refresh) the "Elementor Gate" test page that
 * contains every core Elementor widget, one per top-level container, each
 * preceded by a heading naming the widget. Prints the page id.
 *
 * Usage: wp eval-file tests/e2e/fixtures/create-widgets-page.php
 *
 * @package QeEdu
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- WP-CLI script scope.

// Base classes / legacy section, and `e-component` (an instance of a saved
// Component; it cannot exist without one).
$skip = array( 'common', 'common-base', 'common-optimized', 'inner-section', 'e-component' );
$only = getenv( 'QE_GATE_ONLY' ) ? explode( ',', getenv( 'QE_GATE_ONLY' ) ) : null;

// One local image for the image widgets (idempotent).
$image = get_posts(
	array(
		'post_type'   => 'attachment',
		'title'       => 'qe-gate-image',
		'numberposts' => 1,
		'fields'      => 'ids',
	)
);
if ( $image ) {
	$image_id = $image[0];
} else {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$tmp = wp_tempnam( 'qe-gate-image.png' );
	copy( get_template_directory() . '/screenshot.png', $tmp );
	$image_id = media_handle_sideload(
		array(
			'name'     => 'qe-gate-image.png',
			'tmp_name' => $tmp,
		),
		0,
		'qe-gate-image'
	);
}
$image_ref = array(
	'id'  => $image_id,
	'url' => wp_get_attachment_url( $image_id ),
);

// Real content for widgets that otherwise show an editor-only placeholder.
$content = array(
	'text-editor'    => array( 'editor' => '<p>Lorem ipsum dolor sit amet.</p>' ),
	'html'           => array( 'html' => '<p>Custom HTML widget</p>' ),
	'shortcode'      => array( 'shortcode' => '[qe_gate_unknown_shortcode]' ),
	'image'          => array( 'image' => $image_ref ),
	'image-box'      => array( 'image' => $image_ref ),
	'testimonial'    => array( 'testimonial_image' => $image_ref ),
	'image-carousel' => array(
		'carousel'        => array( $image_ref, $image_ref, $image_ref ),
		'autoplay'        => 'no',
		'navigation'      => 'none',
		'slides_to_show'  => '3',
	),
	'image-gallery'  => array( 'wp_gallery' => array( $image_ref, $image_ref, $image_ref ) ),
);

$elements = array();
$i        = 0;
foreach ( \Elementor\Plugin::$instance->widgets_manager->get_widget_types() as $type => $widget ) {
	$class = get_class( $widget );
	if ( ( $only && ! in_array( $type, $only, true ) ) || in_array( $type, $skip, true ) || 0 !== strpos( $class, 'Elementor\\' ) || false !== strpos( $class, 'Promotions' ) ) {
		continue;
	}
	++$i;
	$elements[] = array(
		'id'       => substr( md5( 'c' . $type ), 0, 7 ),
		'elType'   => 'container',
		'settings' => array( '_title' => $type ),
		'elements' => array(
			array(
				'id'         => substr( md5( 'h' . $type ), 0, 7 ),
				'elType'     => 'widget',
				'widgetType' => 'heading',
				'settings'   => array( 'title' => 'Widget: ' . $type ),
				'elements'   => array(),
			),
			array(
				'id'         => substr( md5( 'w' . $type ), 0, 7 ),
				'elType'     => 'widget',
				'widgetType' => $type,
				'settings'   => isset( $content[ $type ] ) ? $content[ $type ] : array(),
				'elements'   => array(),
			),
		),
	);
}

$existing = get_page_by_path( 'elementor-gate' );
$page_id  = $existing ? $existing->ID : wp_insert_post(
	array(
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_title'  => 'Elementor Gate',
		'post_name'   => 'elementor-gate',
	)
);

update_post_meta( $page_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $page_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
update_post_meta( $page_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
\Elementor\Plugin::$instance->files_manager->clear_cache();

WP_CLI::log( (string) $page_id );
WP_CLI::log( 'widgets: ' . $i );
WP_CLI::log( 'template: ' . get_post_meta( $page_id, '_wp_page_template', true ) );
