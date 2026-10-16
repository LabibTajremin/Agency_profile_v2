<?php
/**
 * Theme supports, menus and image sizes.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports, menus and image sizes.
 *
 * @return void
 */
function qe_edu_setup() {
	load_theme_textdomain( 'qe-edu', QE_EDU_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'custom-background',
		array(
			'default-color' => 'ffffff',
		)
	);

	add_editor_style( 'assets/css/editor.css' );

	register_nav_menus(
		array(
			'primary'          => __( 'Main menu (header)', 'qe-edu' ),
			'topbar'           => __( 'Top bar links', 'qe-edu' ),
			'footer-resources' => __( 'Footer: Resources column', 'qe-edu' ),
			'footer-study'     => __( 'Footer: Study column', 'qe-edu' ),
			'footer-about'     => __( 'Footer: About column', 'qe-edu' ),
			'legal'            => __( 'Footer: Legal links', 'qe-edu' ),
		)
	);

	add_image_size( 'qe-card', 640, 420, true );
	add_image_size( 'qe-hero', 1920, 1080, true );
	add_image_size( 'qe-logo', 320, 160, false );
}
add_action( 'after_setup_theme', 'qe_edu_setup' );

/**
 * Set the content width used by embeds.
 *
 * @return void
 */
function qe_edu_content_width() {
	$GLOBALS['content_width'] = (int) apply_filters( 'qe_edu_content_width', 1240 );
}
add_action( 'after_setup_theme', 'qe_edu_content_width', 0 );

/**
 * Human-friendly names for the image sizes in the media picker.
 *
 * @param array<string,string> $sizes Sizes.
 * @return array<string,string>
 */
function qe_edu_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'qe-card' => __( 'Card (640×420)', 'qe-edu' ),
			'qe-hero' => __( 'Hero (1920×1080)', 'qe-edu' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'qe_edu_image_size_names' );

/**
 * Body classes used by the stylesheet.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function qe_edu_body_classes( $classes ) {
	if ( qe_edu_enabled( 'header.sticky', true ) ) {
		$classes[] = 'qe-has-sticky-header';
	}

	if ( qe_edu_enabled( 'header.transparent', false ) ) {
		$classes[] = 'qe-has-transparent-header';
	}

	$mode = qe_edu_setting( 'colors.mode', 'light' );
	if ( in_array( $mode, array( 'light', 'dark', 'auto' ), true ) ) {
		$classes[] = 'qe-mode-' . $mode;
	}

	$button = qe_edu_setting( 'colors.button_style', 'rounded' );
	if ( in_array( $button, array( 'rounded', 'pill', 'square' ), true ) ) {
		$classes[] = 'qe-btn-' . $button;
	}

	if ( ! qe_edu_enabled( 'colors.link_underline', true ) ) {
		$classes[] = 'qe-no-link-underline';
	}

	if ( is_singular() && qe_edu_is_elementor_page() ) {
		$classes[] = 'qe-elementor-page';
	}

	return $classes;
}
add_filter( 'body_class', 'qe_edu_body_classes' );

/**
 * Add a submenu toggle button after parent menu items in the fallback header,
 * so mobile visitors can open submenus with a 48px tap target.
 *
 * @param string   $item_output Item HTML.
 * @param WP_Post  $item        Menu item.
 * @param int      $depth       Depth.
 * @param stdClass $args        Menu args.
 * @return string
 */
function qe_edu_submenu_toggle( $item_output, $item, $depth, $args ) {
	if ( isset( $args->theme_location ) && 'primary' === $args->theme_location
		&& in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		$item_output .= sprintf(
			'<button type="button" class="qe-submenu-toggle" aria-expanded="false"><span class="screen-reader-text">%s</span></button>',
			esc_html( qe_edu_text( 'submenu_toggle' ) )
		);
	}

	return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'qe_edu_submenu_toggle', 10, 4 );

/**
 * Block styles offered in the block editor (Elementor users get the same
 * looks through widget style controls).
 *
 * @return void
 */
function qe_edu_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'         => 'qe-outline',
			'label'        => __( 'Outline (accent)', 'qe-edu' ),
			'inline_style' => '.wp-block-button.is-style-qe-outline .wp-block-button__link{background:transparent;color:var(--qe-primary);box-shadow:inset 0 0 0 2px currentColor}',
		)
	);
}
add_action( 'init', 'qe_edu_block_styles' );
