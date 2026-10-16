<?php
/**
 * Performance options from Theme Settings → Performance.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Performance;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Optimizer.
 */
final class Optimizer {

	/**
	 * Hook in according to the settings.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! Settings::get( 'performance.lazy_load' ) ) {
			add_filter( 'wp_lazy_loading_enabled', '__return_false' );
		}

		if ( Settings::get( 'performance.disable_emojis' ) ) {
			add_action( 'init', array( $this, 'disable_emojis' ) );
		}

		if ( Settings::get( 'performance.disable_embeds' ) ) {
			add_action( 'init', array( $this, 'disable_embeds' ), 9999 );
		}

		if ( Settings::get( 'performance.preload_hero' ) ) {
			add_action( 'wp_head', array( $this, 'preload_hero' ), 3 );
		}

		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 2 );
	}

	/**
	 * Remove the emoji detection script and styles.
	 *
	 * @return void
	 */
	public function disable_emojis() {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
	}

	/**
	 * Remove the oEmbed discovery links and the wp-embed script.
	 *
	 * @return void
	 */
	public function disable_embeds() {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_action(
			'wp_footer',
			static function () {
				wp_dequeue_script( 'wp-embed' );
			}
		);
	}

	/**
	 * The hero background image URL of an Elementor page: the first
	 * "Animated Hero" widget's image, if any.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function hero_image( $post_id ) {
		$data = json_decode( (string) get_post_meta( $post_id, '_elementor_data', true ), true );

		return is_array( $data ) ? self::find_hero_image( $data ) : '';
	}

	/**
	 * Depth-first search for the first hero widget image.
	 *
	 * @param array<int,array<string,mixed>> $elements Elementor elements.
	 * @return string
	 */
	private static function find_hero_image( $elements ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['widgetType'] ) && 'qe-animated-hero' === $element['widgetType'] ) {
				return isset( $element['settings']['background_image']['url'] ) ? (string) $element['settings']['background_image']['url'] : '';
			}

			if ( ! empty( $element['elements'] ) ) {
				$found = self::find_hero_image( $element['elements'] );
				if ( '' !== $found ) {
					return $found;
				}
			}
		}

		return '';
	}

	/**
	 * Preload the hero image on singular pages.
	 *
	 * @return void
	 */
	public function preload_hero() {
		if ( ! is_singular() ) {
			return;
		}

		$url = self::hero_image( (int) get_queried_object_id() );

		if ( '' !== $url ) {
			printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $url ) );
		}
	}

	/**
	 * Add `defer` to the plugin's own front-end scripts when "Load theme
	 * scripts after the page" is on.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Handle.
	 * @return string
	 */
	public function defer_scripts( $tag, $handle ) {
		if ( is_admin() || 0 !== strpos( $handle, 'qe-edu-core-' ) || ! Settings::get( 'performance.defer_js' ) || false !== strpos( $tag, ' defer' ) ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}
}
