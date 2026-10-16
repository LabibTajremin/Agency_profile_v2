<?php
/**
 * Customizer: a quick-settings mirror of the most used Theme Settings. Values
 * are stored in the same `qe_edu_settings` option, so both screens stay in sync.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quick settings shown in the Customizer.
 *
 * @return array<string,array<string,mixed>> Keyed by dotted settings path.
 */
function qe_edu_customizer_fields() {
	return array(
		'header.sticky'      => array(
			'type'    => 'checkbox',
			'label'   => __( 'Keep the header visible while scrolling', 'qe-edu' ),
			'default' => true,
		),
		'header.topbar'      => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the top bar (small links above the header)', 'qe-edu' ),
			'default' => true,
		),
		'header.cta_text'    => array(
			'type'    => 'text',
			'label'   => __( 'Header button text', 'qe-edu' ),
			'default' => '',
		),
		'header.cta_link'    => array(
			'type'    => 'url',
			'label'   => __( 'Header button link', 'qe-edu' ),
			'default' => '',
		),
		'footer.copyright'   => array(
			'type'        => 'text',
			'label'       => __( 'Copyright text', 'qe-edu' ),
			'description' => __( 'Use {year} for the current year and {site} for the site name.', 'qe-edu' ),
			'default'     => '',
		),
		'footer.back_to_top' => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the "back to top" button', 'qe-edu' ),
			'default' => true,
		),
	);
}

/**
 * Convert a dotted path to the Customizer setting id inside the option array.
 *
 * @param string $path Dotted path.
 * @return string
 */
function qe_edu_customizer_id( $path ) {
	return 'qe_edu_settings[' . str_replace( '.', '][', $path ) . ']';
}

/**
 * Sanitize a Customizer checkbox.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function qe_edu_sanitize_checkbox( $value ) {
	return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Register the "Theme quick settings" section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 * @return void
 */
function qe_edu_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'qe_edu_quick',
		array(
			'title'       => __( 'Theme quick settings', 'qe-edu' ),
			'description' => __( 'All other options are under Theme Settings in the dashboard menu.', 'qe-edu' ),
			'priority'    => 30,
		)
	);

	$sanitizers = array(
		'checkbox' => 'qe_edu_sanitize_checkbox',
		'text'     => 'sanitize_text_field',
		'url'      => 'esc_url_raw',
	);

	foreach ( qe_edu_customizer_fields() as $path => $field ) {
		$id = qe_edu_customizer_id( $path );

		$wp_customize->add_setting(
			$id,
			array(
				'type'              => 'option',
				'default'           => $field['default'],
				'sanitize_callback' => $sanitizers[ $field['type'] ],
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'section'     => 'qe_edu_quick',
				'type'        => $field['type'],
				'label'       => $field['label'],
				'description' => isset( $field['description'] ) ? $field['description'] : '',
			)
		);
	}

	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
}
add_action( 'customize_register', 'qe_edu_customize_register' );
