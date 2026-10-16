<?php
/**
 * Elementor integration: theme locations, sane defaults on activation, Kit
 * (Global Colors / Fonts) defaults, page-template auto-switching, editor
 * styles. Everything here is about making the Elementor editor match the live
 * page with no jumping, collapsing or double padding.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Full Width template path, relative to the theme.
 */
const QE_EDU_FULL_WIDTH_TEMPLATE = 'templates/elementor-canvas-full.php';

/**
 * Register every core Theme Builder location (header, footer, single, archive)
 * so Elementor Pro's Theme Builder can take them over.
 *
 * @param \ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $manager Locations manager.
 * @return void
 */
function qe_edu_register_elementor_locations( $manager ) {
	$manager->register_all_core_location();
}
add_action( 'elementor/theme/register_locations', 'qe_edu_register_elementor_locations' );

/**
 * Base Global Colors written to the Elementor Kit on activation. The QE Edu
 * Core plugin keeps them in sync with the chosen accent afterwards.
 *
 * @return array<int,array<string,string>>
 */
function qe_edu_kit_system_colors() {
	return array(
		array(
			'_id'   => 'primary',
			'title' => __( 'Primary (accent)', 'qe-edu' ),
			'color' => '#A41E34',
		),
		array(
			'_id'   => 'secondary',
			'title' => __( 'Secondary (navy)', 'qe-edu' ),
			'color' => '#140342',
		),
		array(
			'_id'   => 'text',
			'title' => __( 'Text', 'qe-edu' ),
			'color' => '#3D3A4F',
		),
		array(
			'_id'   => 'accent',
			'title' => __( 'Accent (dark)', 'qe-edu' ),
			'color' => '#7E1426',
		),
	);
}

/**
 * Base Global Fonts written to the Elementor Kit on activation.
 *
 * @return array<int,array<string,string>>
 */
function qe_edu_kit_system_typography() {
	$font = static function ( $id, $title, $family, $weight ) {
		return array(
			'_id'                    => $id,
			'title'                  => $title,
			'typography_typography'  => 'custom',
			'typography_font_family' => $family,
			'typography_font_weight' => $weight,
		);
	};

	return array(
		$font( 'primary', __( 'Headings', 'qe-edu' ), 'Montserrat', '700' ),
		$font( 'secondary', __( 'Sub-headings', 'qe-edu' ), 'Montserrat', '600' ),
		$font( 'text', __( 'Body text', 'qe-edu' ), 'Open Sans', '400' ),
		$font( 'accent', __( 'Buttons', 'qe-edu' ), 'Montserrat', '600' ),
	);
}

/**
 * Kit (Site Settings) layout defaults: 1240px content width, 20px widget
 * spacing, custom breakpoints mobile 767 / tablet 1024 / laptop 1366, and the
 * selector Elementor's "Hide title" switch targets.
 *
 * @return array<string,mixed>
 */
function qe_edu_kit_layout_defaults() {
	return array(
		'container_width'       => array(
			'unit'  => 'px',
			'size'  => 1240,
			'sizes' => array(),
		),
		'space_between_widgets' => array(
			'column'   => '20',
			'row'      => '20',
			'isLinked' => true,
			'unit'     => 'px',
			'size'     => 20,
		),
		'active_breakpoints'    => array( 'viewport_mobile', 'viewport_tablet', 'viewport_laptop' ),
		'viewport_mobile'       => 767,
		'viewport_md'           => 768,
		'viewport_tablet'       => 1024,
		'viewport_lg'           => 1025,
		'viewport_laptop'       => 1366,
		'page_title_selector'   => '.qe-entry__header .qe-page-title',
	);
}

/**
 * Merge settings into the active Elementor Kit's page settings.
 *
 * @param array<string,mixed> $settings Settings to merge.
 * @param bool                $only_missing Only set keys that are not set yet.
 * @return bool Whether a kit was updated.
 */
function qe_edu_update_kit( $settings, $only_missing = false ) {
	$kit_id = absint( get_option( 'elementor_active_kit' ) );

	if ( ! $kit_id ) {
		return false;
	}

	$current = get_post_meta( $kit_id, '_elementor_page_settings', true );
	$current = is_array( $current ) ? $current : array();
	$merged  = $only_missing ? array_merge( $settings, $current ) : array_merge( $current, $settings );

	update_post_meta( $kit_id, '_elementor_page_settings', $merged );

	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	return true;
}

/**
 * Apply Elementor defaults exactly once, when the theme is activated (or the
 * first time Elementor is present afterwards). A stored version flag makes
 * sure later changes the site owner makes in Elementor are never overwritten.
 *
 * @return void
 */
function qe_edu_apply_elementor_defaults() {
	if ( ! qe_edu_has_elementor() || get_option( 'qe_edu_elementor_defaults' ) ) {
		return;
	}

	update_option( 'elementor_experiment-container', 'active' );
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	update_option( 'elementor_font_display', 'swap' );

	if ( qe_edu_self_host_fonts() ) {
		// The theme serves Montserrat + Open Sans itself; stop Elementor from
		// loading a second copy from Google.
		update_option( 'elementor_google_font', '0' );
	}

	$kit_updated = qe_edu_update_kit(
		array_merge(
			qe_edu_kit_layout_defaults(),
			array(
				'system_colors'     => qe_edu_kit_system_colors(),
				'system_typography' => qe_edu_kit_system_typography(),
			)
		)
	);

	if ( $kit_updated ) {
		update_option( 'qe_edu_elementor_defaults', QE_EDU_VERSION );
	}
}
add_action( 'after_switch_theme', 'qe_edu_apply_elementor_defaults' );
add_action( 'elementor/init', 'qe_edu_apply_elementor_defaults' );

/**
 * When a page is switched to the Elementor builder and still uses the default
 * template, switch it to "Full Width (Header + Footer)" so the user never
 * picks the wrong template.
 *
 * @param int    $meta_id    Meta id.
 * @param int    $post_id    Post id.
 * @param string $meta_key   Meta key.
 * @param mixed  $meta_value Meta value.
 * @return void
 */
function qe_edu_auto_full_width( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( '_elementor_edit_mode' !== $meta_key || 'builder' !== $meta_value ) {
		return;
	}

	if ( ! in_array( get_post_type( $post_id ), array( 'page', 'post' ), true ) ) {
		return;
	}

	$template = get_post_meta( $post_id, '_wp_page_template', true );

	if ( '' === $template || 'default' === $template ) {
		update_post_meta( $post_id, '_wp_page_template', QE_EDU_FULL_WIDTH_TEMPLATE );
	}
}
add_action( 'added_post_meta', 'qe_edu_auto_full_width', 10, 4 );
add_action( 'updated_post_meta', 'qe_edu_auto_full_width', 10, 4 );

/**
 * Never run wpautop over Elementor-built content (it injects stray <p>/<br>
 * that break the layout between editor and front end).
 *
 * @param string $content Post content.
 * @return string
 */
function qe_edu_elementor_no_autop( $content ) {
	if ( qe_edu_is_elementor_page() ) {
		remove_filter( 'the_content', 'wpautop' );
	}

	return $content;
}
add_filter( 'the_content', 'qe_edu_elementor_no_autop', 0 );

/**
 * Show the theme's fonts in the Elementor editor panel.
 *
 * @return void
 */
function qe_edu_elementor_editor_styles() {
	wp_enqueue_style( 'qe-edu-elementor-editor', QE_EDU_URI . '/assets/css/editor.css', array(), qe_edu_asset_version( 'assets/css/editor.css' ) );
}
add_action( 'elementor/editor/after_enqueue_styles', 'qe_edu_elementor_editor_styles' );

/**
 * Elementor content CSS must load after the theme stylesheet in the preview
 * too, so that Elementor's styles always win.
 *
 * @return void
 */
function qe_edu_elementor_preview_styles() {
	wp_enqueue_style( 'qe-edu' );
}
add_action( 'elementor/preview/enqueue_styles', 'qe_edu_elementor_preview_styles' );
