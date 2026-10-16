<?php
/**
 * Template helpers. Every user-facing string the theme prints comes either from
 * the "Theme Settings" option (managed by the QE Edu Core plugin) or from a
 * translatable default defined here — never from literal text in a template.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a value from the shared `qe_edu_settings` option using a dotted path.
 *
 * @param string $path    Dotted path, e.g. `footer.copyright`.
 * @param mixed  $fallback Value returned when the path is not set.
 * @return mixed
 */
function qe_edu_setting( $path, $fallback = null ) {
	$value = get_option( 'qe_edu_settings', array() );

	foreach ( explode( '.', $path ) as $segment ) {
		if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
			$value = $fallback;
			break;
		}
		$value = $value[ $segment ];
	}

	/**
	 * Filters a theme setting value.
	 *
	 * @param mixed  $value Setting value.
	 * @param string $path  Dotted setting path.
	 */
	return apply_filters( 'qe_edu_setting', $value, $path );
}

/**
 * Whether a boolean-like setting is switched on.
 *
 * @param string $path    Dotted path.
 * @param bool   $fallback Default when unset.
 * @return bool
 */
function qe_edu_enabled( $path, $fallback = true ) {
	return (bool) filter_var( qe_edu_setting( $path, $fallback ), FILTER_VALIDATE_BOOLEAN );
}

/**
 * Translatable default strings used by theme templates.
 *
 * @return array<string,string>
 */
function qe_edu_default_strings() {
	$strings = array(
		'skip_link'          => __( 'Skip to content', 'qe-edu' ),
		'menu_open'          => __( 'Open menu', 'qe-edu' ),
		'menu_close'         => __( 'Close menu', 'qe-edu' ),
		'menu_label'         => __( 'Main menu', 'qe-edu' ),
		'topbar_label'       => __( 'Utility links', 'qe-edu' ),
		'footer_label'       => __( 'Footer links', 'qe-edu' ),
		'legal_label'        => __( 'Legal links', 'qe-edu' ),
		'submenu_toggle'     => __( 'Show submenu', 'qe-edu' ),
		'back_to_top'        => __( 'Back to top', 'qe-edu' ),
		'copyright'          => __( '© {year} {site}. All rights reserved.', 'qe-edu' ),
		'not_found_title'    => __( 'Page not found', 'qe-edu' ),
		'not_found_text'     => __( 'The page you are looking for has moved or no longer exists. Try a search instead.', 'qe-edu' ),
		'search_title'       => __( 'Search results for: {query}', 'qe-edu' ),
		'search_none'        => __( 'Nothing matched your search. Please try different keywords.', 'qe-edu' ),
		'archive_none'       => __( 'There is nothing here yet.', 'qe-edu' ),
		'blog_title'         => __( 'News', 'qe-edu' ),
		'read_more'          => __( 'Read more', 'qe-edu' ),
		'posted_on'          => __( 'Posted on {date}', 'qe-edu' ),
		'by_author'          => __( 'by {author}', 'qe-edu' ),
		'pages'              => __( 'Pages:', 'qe-edu' ),
		'previous'           => __( 'Previous', 'qe-edu' ),
		'next'               => __( 'Next', 'qe-edu' ),
		'comments_title'     => __( 'Comments', 'qe-edu' ),
		'comments_closed'    => __( 'Comments are closed.', 'qe-edu' ),
		'search_label'       => __( 'Search for:', 'qe-edu' ),
		'search_button'      => __( 'Search', 'qe-edu' ),
		'search_placeholder' => __( 'Search…', 'qe-edu' ),
		'header_cta_text'    => __( 'Apply Now', 'qe-edu' ),
	);

	/**
	 * Filters the theme's default strings.
	 *
	 * @param array<string,string> $strings Strings keyed by id.
	 */
	return apply_filters( 'qe_edu_default_strings', $strings );
}

/**
 * Return an editable text: the value of `strings.{key}` from Theme Settings if
 * set, otherwise the translatable default.
 *
 * @param string               $key     String id.
 * @param array<string,string> $replace Extra placeholder values.
 * @return string
 */
function qe_edu_text( $key, $replace = array() ) {
	return qe_edu_setting_text( 'strings.' . $key, $key, $replace );
}

/**
 * Return the text stored at a settings path, falling back to the default
 * string `$key` when empty. Placeholders `{year}`, `{site}` and any passed in
 * `$replace` are substituted.
 *
 * @param string               $path    Dotted settings path.
 * @param string               $key     Default string id.
 * @param array<string,string> $replace Extra placeholder values.
 * @return string
 */
function qe_edu_setting_text( $path, $key, $replace = array() ) {
	$value = qe_edu_setting( $path, '' );

	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		$strings = qe_edu_default_strings();
		$value   = isset( $strings[ $key ] ) ? $strings[ $key ] : '';
	}

	$replace = array_merge(
		array(
			'year' => wp_date( 'Y' ),
			'site' => get_bloginfo( 'name' ),
		),
		$replace
	);

	foreach ( $replace as $token => $replacement ) {
		$value = str_replace( '{' . $token . '}', (string) $replacement, $value );
	}

	return $value;
}

/**
 * Echo an editable text, escaped for HTML.
 *
 * @param string               $key     String id.
 * @param array<string,string> $replace Placeholder values.
 * @return void
 */
function qe_edu_echo_text( $key, $replace = array() ) {
	echo esc_html( qe_edu_text( $key, $replace ) );
}

/**
 * Whether Elementor is loaded.
 *
 * @return bool
 */
function qe_edu_has_elementor() {
	return (bool) did_action( 'elementor/loaded' );
}

/**
 * Whether the given post is built with Elementor.
 *
 * @param int|null $post_id Post id, defaults to the current post.
 * @return bool
 */
function qe_edu_is_elementor_page( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return $post_id && 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * Let Elementor Pro's Theme Builder render a location, when it has a template
 * for it.
 *
 * @param string $location Location name: header, footer, single, archive.
 * @return bool Whether Elementor Pro rendered it.
 */
function qe_edu_pro_location( $location ) {
	return function_exists( 'elementor_theme_do_location' ) && elementor_theme_do_location( $location );
}

/**
 * Render the Elementor template whose id is stored in a Theme Settings path.
 *
 * @param string $path Dotted settings path holding an `elementor_library` id.
 * @return bool Whether a template was rendered.
 */
function qe_edu_render_template_setting( $path ) {
	$template_id = absint( qe_edu_setting( $path, 0 ) );

	if ( ! $template_id || ! qe_edu_has_elementor() || 'publish' !== get_post_status( $template_id ) ) {
		return false;
	}

	$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id );

	if ( '' === $content ) {
		return false;
	}

	// Elementor returns already-escaped builder markup.
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	return true;
}

/**
 * Render the header or footer. Order of precedence:
 * 1. Elementor Pro Theme Builder;
 * 2. the Elementor template chosen in Theme Settings (`templates.{location}`);
 * 3. the theme's own fallback template part.
 *
 * @param string $location `header` or `footer`.
 * @return string Which renderer was used: `pro`, `template` or `fallback`.
 */
function qe_edu_render_location( $location ) {
	if ( qe_edu_pro_location( $location ) ) {
		return 'pro';
	}

	if ( qe_edu_render_template_setting( 'templates.' . $location ) ) {
		return 'template';
	}

	get_template_part( 'template-parts/' . $location, 'fallback' );

	return 'fallback';
}

/**
 * Whether the page title should be hidden: always on Elementor-built pages
 * (the design supplies its own heading) and whenever "Hide title" is ticked.
 *
 * @param int|null $post_id Post id.
 * @return bool
 */
function qe_edu_hide_title( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	return qe_edu_is_elementor_page( $post_id ) || (bool) get_post_meta( $post_id, '_qe_hide_title', true );
}

/**
 * Print the site logo, or the site name when no logo is set.
 *
 * @return void
 */
function qe_edu_site_branding() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}

	printf(
		'<a class="qe-site-title" href="%1$s" rel="home">%2$s</a>',
		esc_url( home_url( '/' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}

/**
 * Print a navigation menu if it is assigned.
 *
 * @param string $location Menu location.
 * @param string $label    Accessible label key from the strings table.
 * @param string $css_class    Extra CSS class for the <nav>.
 * @param int    $depth    Menu depth.
 * @return bool Whether a menu was printed.
 */
function qe_edu_nav( $location, $label, $css_class, $depth = 1 ) {
	if ( ! has_nav_menu( $location ) ) {
		return false;
	}

	printf( '<nav class="%1$s" aria-label="%2$s">', esc_attr( $css_class ), esc_attr( qe_edu_text( $label ) ) );
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'depth'          => $depth,
			'fallback_cb'    => false,
		)
	);
	echo '</nav>';

	return true;
}

/**
 * Print "Posted on {date} by {author}" meta for the current post.
 *
 * @return void
 */
function qe_edu_posted_on() {
	$time = sprintf(
		'<time datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( DATE_W3C ) ),
		esc_html( get_the_date() )
	);

	$author = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( get_author_posts_url( (int) get_the_author_meta( 'ID' ) ) ),
		esc_html( get_the_author() )
	);

	echo '<p class="qe-entry-meta">';
	echo wp_kses_post( str_replace( '{date}', $time, esc_html( qe_edu_text( 'posted_on' ) ) ) );
	echo ' ';
	echo wp_kses_post( str_replace( '{author}', $author, esc_html( qe_edu_text( 'by_author' ) ) ) );
	echo '</p>';
}
