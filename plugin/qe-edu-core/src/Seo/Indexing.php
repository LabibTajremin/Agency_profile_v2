<?php
/**
 * Sitemap, robots.txt and the "search engines are blocked" warning.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Indexing.
 */
final class Indexing {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'discouraged_notice' ) );

		if ( ! Conflict::output_enabled() ) {
			return;
		}

		if ( ! Settings::get( 'seo.sitemap' ) ) {
			add_filter( 'wp_sitemaps_enabled', '__return_false' );
		}
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'exclude_noindex' ) );
		add_filter( 'robots_txt', array( $this, 'robots_txt' ), 10, 2 );
	}

	/**
	 * Leave pages marked "noindex" out of the sitemap.
	 *
	 * @param array<string,mixed> $args WP_Query args.
	 * @return array<string,mixed>
	 */
	public function exclude_noindex( $args ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- sitemap pages are cached by core.
			array(
				'key'     => Fields::meta_key( 'noindex' ),
				'compare' => 'NOT EXISTS',
			),
		);

		return $args;
	}

	/**
	 * Add the extra rules and the sitemap address to robots.txt. Extra rules
	 * join the default "User-agent: *" group (or start their own group when
	 * they begin with "User-agent:"); Sitemap lines always come last.
	 *
	 * @param string $output    Robots.txt.
	 * @param bool   $is_public Whether the site is public.
	 * @return string
	 */
	public function robots_txt( $output, $is_public ) {
		$rules    = array();
		$sitemaps = array();
		foreach ( explode( "\n", str_replace( "\r", '', (string) $output ) ) as $line ) {
			if ( 0 === stripos( ltrim( $line ), 'sitemap:' ) ) {
				$sitemaps[] = trim( $line );
			} else {
				$rules[] = $line;
			}
		}

		$body  = trim( implode( "\n", $rules ) );
		$extra = trim( str_replace( "\r", '', (string) Settings::get( 'seo.robots_txt' ) ) );
		if ( '' !== $extra ) {
			$glue = '' === $body ? '' : ( 0 === stripos( $extra, 'user-agent:' ) ? "\n\n" : "\n" );
			$body = $body . $glue . $extra;
		}

		if ( ! $sitemaps && $is_public && Settings::get( 'seo.sitemap' ) ) {
			$sitemaps[] = 'Sitemap: ' . home_url( '/wp-sitemap.xml' );
		}

		if ( $sitemaps ) {
			$body .= ( '' === $body ? '' : "\n\n" ) . implode( "\n", $sitemaps );
		}

		return '' === $body ? '' : $body . "\n";
	}

	/**
	 * Warn administrators when "Discourage search engines" is ticked.
	 *
	 * @return void
	 */
	public function discouraged_notice() {
		if ( '0' !== (string) get_option( 'blog_public' ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
			esc_html__( 'Search engines are blocked.', 'qe-edu' ),
			esc_html__( 'Google will not show this site while "Discourage search engines" is ticked.', 'qe-edu' ),
			esc_url( admin_url( 'options-reading.php' ) ),
			esc_html__( 'Change this in Settings → Reading', 'qe-edu' )
		);
	}
}
