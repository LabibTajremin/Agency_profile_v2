<?php
/**
 * 301 redirects (Theme Settings → SEO) and a small 404 log (last 500).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

use QeEdu\Core\Settings\AdminPage;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Redirects.
 */
class Redirects {

	/**
	 * Option holding the 404 log.
	 */
	const LOG = 'qe_edu_404_log';

	/**
	 * Maximum log entries.
	 */
	const MAX = 500;

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 1 );
		add_action( 'template_redirect', array( $this, 'log_404' ), 99 );
		add_action( 'qe_edu_settings_after_field', array( $this, 'render_log' ) );
		add_action( 'admin_post_qe_edu_clear_404_log', array( $this, 'handle_clear' ) );
	}

	/**
	 * Path of the current request, normalised (no query, leading slash,
	 * no trailing slash unless root).
	 *
	 * @param string $uri Request URI.
	 * @return string
	 */
	public static function normalize( $uri ) {
		$path = (string) wp_parse_url( (string) $uri, PHP_URL_PATH );
		$path = '/' . trim( rawurldecode( $path ), '/' );

		return strtolower( $path );
	}

	/**
	 * The redirect target for a path, if any.
	 *
	 * @param string $path Normalised path.
	 * @return string
	 */
	public static function target( $path ) {
		foreach ( (array) Settings::get( 'seo.redirects' ) as $row ) {
			if ( isset( $row['from'], $row['to'] ) && self::normalize( $row['from'] ) === $path ) {
				return 0 === strpos( $row['to'], '/' ) ? home_url( $row['to'] ) : $row['to'];
			}
		}

		return '';
	}

	/**
	 * Redirect when the request matches a row.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$target = self::target( self::normalize( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );

		if ( '' !== $target ) {
			wp_redirect( $target, 301, 'QE Edu' ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- targets are set by administrators and may be external.
			$this->halt();
		}
	}

	/**
	 * Record a 404.
	 *
	 * @return void
	 */
	public function log_404() {
		if ( ! is_404() || ! Settings::get( 'seo.log_404' ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$path = self::normalize( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		$log  = get_option( self::LOG, array() );
		$log  = is_array( $log ) ? $log : array();

		$entry = isset( $log[ $path ] ) ? $log[ $path ] : array( 'count' => 0 );
		++$entry['count'];
		$entry['last'] = time();
		unset( $log[ $path ] );
		$log[ $path ] = $entry;

		if ( count( $log ) > self::MAX ) {
			$log = array_slice( $log, -self::MAX, null, true );
		}

		update_option( self::LOG, $log, false );
	}

	/**
	 * Show the log under the redirects table.
	 *
	 * @param array<string,mixed> $field Field definition.
	 * @return void
	 */
	public function render_log( $field ) {
		if ( 'seo.redirects' !== $field['path'] ) {
			return;
		}

		$log = get_option( self::LOG, array() );
		$log = is_array( $log ) ? $log : array();
		uasort(
			$log,
			static function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);

		echo '<h3>' . esc_html__( 'Addresses visitors could not find (404)', 'qe-edu-core' ) . '</h3>';

		if ( ! $log ) {
			echo '<p class="description">' . esc_html__( 'Nothing logged yet.', 'qe-edu-core' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr><th scope="col">' . esc_html__( 'Address', 'qe-edu-core' ) . '</th><th scope="col">' . esc_html__( 'Times', 'qe-edu-core' ) . '</th><th scope="col">' . esc_html__( 'Last seen', 'qe-edu-core' ) . '</th></tr></thead><tbody>';
		foreach ( array_slice( $log, 0, 50, true ) as $path => $entry ) {
			printf(
				'<tr><td><code>%1$s</code></td><td>%2$d</td><td>%3$s</td></tr>',
				esc_html( $path ),
				(int) $entry['count'],
				esc_html( wp_date( (string) get_option( 'date_format' ), (int) $entry['last'] ) )
			);
		}
		echo '</tbody></table>';

		printf(
			'<p><a class="button" href="%1$s">%2$s</a></p>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=qe_edu_clear_404_log' ), 'qe_edu_clear_404_log' ) ),
			esc_html__( 'Clear the list', 'qe-edu-core' )
		);
	}

	/**
	 * Clear the log.
	 *
	 * @return void
	 */
	public function handle_clear() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'qe-edu-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'qe_edu_clear_404_log' );

		delete_option( self::LOG );
		wp_safe_redirect( AdminPage::url( 'seo', array( 'qe_notice' => 'saved' ) ) );
		$this->halt();
	}

	/**
	 * Stop the request (overridable in tests).
	 *
	 * @return void
	 */
	protected function halt() {
		exit;
	}
}
