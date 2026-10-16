<?php
/**
 * Yoast SEO / Rank Math detection. When either is active, this module's
 * front-end output switches off and administrators can copy their per-page
 * values across.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * Conflict handling.
 */
class Conflict {

	/**
	 * The active SEO plugin, if any.
	 *
	 * @return string `yoast`, `rankmath` or ''.
	 */
	public static function active_plugin() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}

		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			return 'rankmath';
		}

		return '';
	}

	/**
	 * Whether this module should print SEO tags on the front end.
	 *
	 * @return bool
	 */
	public static function output_enabled() {
		return '' === self::active_plugin();
	}

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'admin_post_qe_edu_seo_import', array( $this, 'handle_import' ) );
	}

	/**
	 * Meta-key map: our field => the other plugin's key.
	 *
	 * @param string $plugin yoast|rankmath.
	 * @return array<string,string>
	 */
	public static function key_map( $plugin ) {
		if ( 'yoast' === $plugin ) {
			return array(
				'title'          => '_yoast_wpseo_title',
				'description'    => '_yoast_wpseo_metadesc',
				'keyword'        => '_yoast_wpseo_focuskw',
				'canonical'      => '_yoast_wpseo_canonical',
				'og_title'       => '_yoast_wpseo_opengraph-title',
				'og_description' => '_yoast_wpseo_opengraph-description',
			);
		}

		return array(
			'title'          => 'rank_math_title',
			'description'    => 'rank_math_description',
			'keyword'        => 'rank_math_focus_keyword',
			'canonical'      => 'rank_math_canonical_url',
			'og_title'       => 'rank_math_facebook_title',
			'og_description' => 'rank_math_facebook_description',
		);
	}

	/**
	 * Copy our per-page values into the active plugin's fields, never
	 * overwriting values that plugin already has.
	 *
	 * @param string $plugin yoast|rankmath.
	 * @return int Number of values copied.
	 */
	public static function import( $plugin ) {
		$map   = self::key_map( $plugin );
		$count = 0;
		$posts = array_unique( array_merge( self::posts_with( 'title' ), self::posts_with( 'description' ), self::posts_with( 'noindex' ) ) );

		foreach ( $posts as $post_id ) {
			foreach ( $map as $ours => $theirs ) {
				$value = Fields::get( $post_id, $ours );
				if ( '' !== $value && '' === (string) get_post_meta( $post_id, $theirs, true ) ) {
					update_post_meta( $post_id, $theirs, $value );
					++$count;
				}
			}
			if ( '1' === Fields::get( $post_id, 'noindex' ) ) {
				if ( 'yoast' === $plugin ) {
					update_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', '1' );
				} else {
					update_post_meta( $post_id, 'rank_math_robots', array( 'noindex' ) );
				}
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Ids of posts that have a value for a field.
	 *
	 * @param string $key Field key.
	 * @return int[]
	 */
	private static function posts_with( $key ) {
		return get_posts(
			array(
				'post_type'      => Fields::post_types(),
				'post_status'    => 'any',
				'posts_per_page' => -1, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'fields'         => 'ids',
				'meta_key'       => Fields::meta_key( $key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_compare'   => 'EXISTS',
			)
		);
	}

	/**
	 * Notice explaining that the other plugin is in charge.
	 *
	 * @return void
	 */
	public function notice() {
		$plugin = self::active_plugin();
		if ( '' === $plugin || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$names = array(
			'yoast'    => 'Yoast SEO',
			'rankmath' => 'Rank Math',
		);

		if ( isset( $_GET['qe_seo_imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				/* translators: %d: number of values. */
				esc_html( sprintf( __( 'Copied %d SEO values.', 'qe-edu' ), absint( $_GET['qe_seo_imported'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}

		echo '<div class="notice notice-info"><p>';
		printf(
			/* translators: %s: SEO plugin name. */
			esc_html__( '%s is active, so the theme\'s own SEO tags are switched off to avoid duplicates. You can copy the SEO titles and descriptions you entered with the theme into it.', 'qe-edu' ),
			esc_html( $names[ $plugin ] )
		);
		echo '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'qe_edu_seo_import' );
		echo '<input type="hidden" name="action" value="qe_edu_seo_import">';
		submit_button( __( 'Copy my SEO values', 'qe-edu' ), 'secondary', 'submit', false );
		echo '</form></div>';
	}

	/**
	 * Handle the import button.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'qe-edu' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'qe_edu_seo_import' );

		$plugin = self::active_plugin();
		$count  = '' !== $plugin ? self::import( $plugin ) : 0;

		wp_safe_redirect( add_query_arg( 'qe_seo_imported', $count, admin_url( 'index.php' ) ) );
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
