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

		if ( Settings::get( 'performance.jquery_footer' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'jquery_to_footer' ), 1 );
		}

		add_filter( 'script_loader_tag', array( $this, 'defer_scripts' ), 10, 2 );
	}

	/**
	 * Print jQuery with the footer scripts instead of blocking the page in
	 * <head> (Elementor and the theme run their scripts in the footer).
	 *
	 * @return void
	 */
	public function jquery_to_footer() {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		$scripts = wp_scripts();
		foreach ( array( 'jquery', 'jquery-core', 'jquery-migrate' ) as $handle ) {
			$scripts->add_data( $handle, 'group', 1 );
		}
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
	 * The image at the top of an Elementor page: the background of the first
	 * Animated Hero (when it shows an image) or Page Title Banner.
	 *
	 * @param int $post_id Post id.
	 * @return array{id:int,url:string}|null
	 */
	public static function hero_image( $post_id ) {
		$data = json_decode( (string) get_post_meta( $post_id, '_elementor_data', true ), true );

		return is_array( $data ) ? self::find_hero_image( $data ) : null;
	}

	/**
	 * Depth-first search for the first hero / banner widget.
	 *
	 * @param array<int,array<string,mixed>> $elements Elementor elements.
	 * @return array{id:int,url:string}|null
	 */
	private static function find_hero_image( $elements ) {
		foreach ( $elements as $element ) {
			$type = isset( $element['widgetType'] ) ? $element['widgetType'] : '';

			if ( 'qe-animated-hero' === $type || 'qe-page-title' === $type ) {
				$settings = isset( $element['settings'] ) ? $element['settings'] : array();
				$shown    = 'qe-page-title' === $type || ( isset( $settings['background'] ) && in_array( $settings['background'], array( 'image', 'video' ), true ) );
				$image    = isset( $settings['background_image'] ) ? $settings['background_image'] : array();
				$url      = isset( $image['url'] ) ? (string) $image['url'] : '';

				return $shown && '' !== $url
					? array(
						'id'  => absint( isset( $image['id'] ) ? $image['id'] : 0 ),
						'url' => $url,
					)
					: null;
			}

			if ( ! empty( $element['elements'] ) ) {
				$found = self::find_hero_image( $element['elements'] );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Preload the top image on singular pages, with the same sources and
	 * sizes as the rendered <img>, so phones fetch the small version once.
	 *
	 * @return void
	 */
	public function preload_hero() {
		if ( ! is_singular() ) {
			return;
		}

		$image = self::hero_image( (int) get_queried_object_id() );
		if ( null === $image ) {
			return;
		}

		$srcset = $image['id'] ? (string) wp_get_attachment_image_srcset( $image['id'], 'qe-hero' ) : '';
		$sizes  = $image['id'] ? (string) wp_get_attachment_image_sizes( $image['id'], 'qe-hero' ) : '';

		printf(
			'<link rel="preload" as="image" href="%1$s"%2$s fetchpriority="high">' . "\n",
			esc_url( $image['url'] ),
			'' !== $srcset ? sprintf( ' imagesrcset="%1$s" imagesizes="%2$s"', esc_attr( $srcset ), esc_attr( $sizes ) ) : ''
		);
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
