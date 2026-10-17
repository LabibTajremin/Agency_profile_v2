<?php
/**
 * "Edit this section" pencils for logged-in administrators: small buttons
 * that open the current page, header or footer straight in Elementor.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Frontend;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Edit links.
 */
final class EditLinks {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_footer', array( $this, 'render' ), 20 );

		// The Theme Settings live preview shows the site as visitors see it.
		if ( isset( $_GET['qe_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag.
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/**
	 * Elementor edit URL of a post.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function edit_url( $post_id ) {
		return add_query_arg(
			array(
				'post'   => (int) $post_id,
				'action' => 'elementor',
			),
			admin_url( 'post.php' )
		);
	}

	/**
	 * Links to show: label => url.
	 *
	 * @return array<string,string>
	 */
	public static function links() {
		if ( ! current_user_can( 'edit_pages' ) || is_admin() || ! did_action( 'elementor/loaded' ) || isset( $_GET['elementor-preview'] ) || isset( $_GET['qe_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check.
			return array();
		}

		$links = array();
		if ( is_singular() && 'builder' === get_post_meta( (int) get_queried_object_id(), '_elementor_edit_mode', true ) && current_user_can( 'edit_post', (int) get_queried_object_id() ) ) {
			$links[ __( 'Edit this page', 'qe-edu' ) ] = self::edit_url( (int) get_queried_object_id() );
		}

		foreach ( array(
			'header' => __( 'Edit header', 'qe-edu' ),
			'footer' => __( 'Edit footer', 'qe-edu' ),
		) as $location => $label ) {
			$template = absint( Settings::get( 'templates.' . $location ) );
			if ( $template && current_user_can( 'edit_post', $template ) ) {
				$links[ $label ] = self::edit_url( $template );
			}
		}

		return $links;
	}

	/**
	 * Print the pencils.
	 *
	 * @return void
	 */
	public function render() {
		$links = self::links();
		if ( ! $links ) {
			return;
		}

		wp_enqueue_style( 'qe-edu-core-widgets' );

		printf( '<nav class="qe-edit-links" aria-label="%s">', esc_attr__( 'Edit with Elementor', 'qe-edu' ) );
		foreach ( $links as $label => $url ) {
			printf( '<a href="%1$s"><span aria-hidden="true">✎</span> %2$s</a>', esc_url( $url ), esc_html( $label ) );
		}
		echo '</nav>';
	}
}
