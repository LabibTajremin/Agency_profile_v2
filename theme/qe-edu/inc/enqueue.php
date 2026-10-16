<?php
/**
 * Front-end assets.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Asset version: file modification time so caches bust on every build.
 *
 * @param string $relative Path relative to the theme directory.
 * @return string
 */
function qe_edu_asset_version( $relative ) {
	$file = QE_EDU_DIR . '/' . $relative;

	return file_exists( $file ) ? QE_EDU_VERSION . '.' . filemtime( $file ) : QE_EDU_VERSION;
}

/**
 * The @font-face rules for the self-hosted fonts.
 *
 * @return string
 */
function qe_edu_font_face_css() {
	$base  = QE_EDU_URI . '/assets/fonts/';
	$faces = array(
		array( 'Montserrat', 'montserrat-latin-wght-normal.woff2', 'normal' ),
		array( 'Open Sans', 'open-sans-latin-wght-normal.woff2', 'normal' ),
		array( 'Open Sans', 'open-sans-latin-wght-italic.woff2', 'italic' ),
	);

	$css = '';
	foreach ( $faces as $face ) {
		$css .= sprintf(
			"@font-face{font-family:'%1\$s';font-style:%3\$s;font-weight:100 900;font-display:swap;src:url('%2\$s') format('woff2');}",
			$face[0],
			esc_url_raw( $base . $face[1] ),
			$face[2]
		);
	}

	return $css;
}

/**
 * Whether the bundled fonts should be self-hosted. Off when the visitor chose
 * another font pair (the QE Edu Core plugin then loads that pair).
 *
 * @return bool
 */
function qe_edu_self_host_fonts() {
	return qe_edu_enabled( 'typography.self_host', true )
		&& 'montserrat-open-sans' === qe_edu_setting( 'typography.pair', 'montserrat-open-sans' );
}

/**
 * Enqueue theme styles and scripts.
 *
 * @return void
 */
function qe_edu_enqueue_assets() {
	wp_enqueue_style( 'qe-edu', QE_EDU_URI . '/assets/css/main.css', array(), qe_edu_asset_version( 'assets/css/main.css' ) );

	if ( qe_edu_self_host_fonts() ) {
		wp_add_inline_style( 'qe-edu', qe_edu_font_face_css() );
	}

	wp_enqueue_script(
		'qe-edu',
		QE_EDU_URI . '/assets/js/theme.min.js',
		array(),
		qe_edu_asset_version( 'assets/js/theme.min.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script(
		'qe-edu',
		'qeEduTheme',
		array(
			'menuOpen'  => qe_edu_text( 'menu_open' ),
			'menuClose' => qe_edu_text( 'menu_close' ),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'qe_edu_enqueue_assets' );

/**
 * Preload the self-hosted heading and body fonts.
 *
 * @return void
 */
function qe_edu_preload_fonts() {
	if ( ! qe_edu_self_host_fonts() ) {
		return;
	}

	foreach ( array( 'montserrat-latin-wght-normal.woff2', 'open-sans-latin-wght-normal.woff2' ) as $file ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( QE_EDU_URI . '/assets/fonts/' . $file )
		);
	}
}
add_action( 'wp_head', 'qe_edu_preload_fonts', 2 );
