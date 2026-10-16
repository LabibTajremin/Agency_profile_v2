<?php
/**
 * "Required plugins" prompt: a lightweight notice that links to install or
 * activate Elementor and the QE Edu Core companion plugin.
 *
 * @package QeEdu
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugins the theme expects.
 *
 * @return array<int,array<string,mixed>>
 */
function qe_edu_required_plugins() {
	return array(
		array(
			'name'     => __( 'Elementor', 'qe-edu' ),
			'slug'     => 'elementor',
			'file'     => 'elementor/elementor.php',
			'loaded'   => 'elementor/loaded',
			'bundled'  => false,
			'required' => true,
		),
		array(
			'name'     => __( 'QE Edu Core', 'qe-edu' ),
			'slug'     => 'qe-edu-core',
			'file'     => 'qe-edu-core/qe-edu-core.php',
			'loaded'   => 'qe_edu_core_loaded',
			'bundled'  => true,
			'required' => true,
		),
	);
}

/**
 * Required plugins that are not active yet.
 *
 * @return array<int,array<string,mixed>>
 */
function qe_edu_missing_plugins() {
	return array_values(
		array_filter(
			qe_edu_required_plugins(),
			static function ( $plugin ) {
				return ! did_action( $plugin['loaded'] );
			}
		)
	);
}

/**
 * The action link for a missing plugin (activate if installed, otherwise install).
 *
 * @param array<string,mixed> $plugin Plugin definition.
 * @return string HTML link, or an empty string.
 */
function qe_edu_plugin_action_link( $plugin ) {
	$installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin['file'] );

	if ( $installed ) {
		$url   = wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin['file'] ) ),
			'activate-plugin_' . $plugin['file']
		);
		$label = __( 'Activate', 'qe-edu' );
	} elseif ( $plugin['bundled'] ) {
		$url   = self_admin_url( 'plugin-install.php?tab=upload' );
		$label = __( 'Upload the qe-edu-core.zip that came with the theme', 'qe-edu' );
	} else {
		$url   = wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $plugin['slug'] ) ),
			'install-plugin_' . $plugin['slug']
		);
		$label = __( 'Install', 'qe-edu' );
	}

	return sprintf( '<a class="button button-primary" href="%1$s">%2$s</a>', esc_url( $url ), esc_html( $label ) );
}

/**
 * Show the required-plugins notice to administrators.
 *
 * @return void
 */
function qe_edu_required_plugins_notice() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	$missing = qe_edu_missing_plugins();
	if ( ! $missing ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>';
	echo esc_html__( 'QE Edu theme needs these plugins to unlock the editable layouts:', 'qe-edu' );
	echo '</strong></p><ul>';
	foreach ( $missing as $plugin ) {
		printf(
			'<li>%1$s &nbsp; %2$s</li>',
			esc_html( $plugin['name'] ),
			qe_edu_plugin_action_link( $plugin ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
		);
	}
	echo '</ul></div>';
}
add_action( 'admin_notices', 'qe_edu_required_plugins_notice' );
