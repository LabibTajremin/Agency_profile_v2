<?php
/**
 * Front-end design tokens: prints the CSS variables for the chosen accent,
 * mode, fonts and sizes, and loads the font pair when the theme is not
 * self-hosting it.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Colors;

use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Styles.
 */
final class Styles {

	/**
	 * Hook in.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
	}

	/**
	 * Heading scale multipliers.
	 *
	 * @return array<string,float>
	 */
	public static function heading_scales() {
		return array(
			'compact' => 0.88,
			'normal'  => 1.0,
			'large'   => 1.12,
		);
	}

	/**
	 * Resolve every token from a settings array.
	 *
	 * @param array<string,mixed> $settings Settings merged over defaults.
	 * @return array<string,string> CSS custom property => value.
	 */
	public static function tokens( $settings ) {
		$colors  = Palette::resolve( (string) Settings::get_path( $settings, 'colors.accent' ), (string) Settings::get_path( $settings, 'colors.custom' ) );
		$pair    = FontPairs::get( (string) Settings::get_path( $settings, 'typography.pair' ) );
		$scales  = self::heading_scales();
		$scale   = (string) Settings::get_path( $settings, 'typography.heading_scale' );
		$surface = array(
			'neutral' => '#F6F5FB',
			'accent'  => Palette::mix( $colors['primary'], '#FFFFFF', 0.94 ),
			'white'   => '#FFFFFF',
		);
		$tint    = (string) Settings::get_path( $settings, 'colors.background' );

		return array(
			'--qe-primary'         => $colors['primary'],
			'--qe-primary-dark'    => $colors['dark'],
			'--qe-primary-soft'    => $colors['soft'],
			'--qe-on-primary'      => $colors['on_primary'],
			'--qe-on-primary-dark' => $colors['on_dark'],
			'--qe-link'            => $colors['link'],
			'--qe-link-dark-mode'  => $colors['link_dark_mode'],
			'--qe-surface-alt'     => isset( $surface[ $tint ] ) ? $surface[ $tint ] : $surface['neutral'],
			'--qe-hero-gradient'   => Settings::get_path( $settings, 'colors.hero_gradient' )
				? 'linear-gradient(135deg, ' . $colors['primary'] . ' 0%, #140342 100%)'
				: 'none',
			'--qe-font-heading'    => FontPairs::stack( $pair['heading'] ),
			'--qe-font-body'       => FontPairs::stack( $pair['body'] ),
			'--qe-base-size'       => round( absint( Settings::get_path( $settings, 'typography.base_size', 16 ) ) / 16, 4 ) . 'rem',
			'--qe-h-scale'         => (string) ( isset( $scales[ $scale ] ) ? $scales[ $scale ] : 1.0 ),
		);
	}

	/**
	 * The :root rule.
	 *
	 * @param array<string,mixed> $settings Settings merged over defaults.
	 * @return string
	 */
	public static function css( $settings ) {
		$css = '';
		foreach ( self::tokens( $settings ) as $property => $value ) {
			$css .= $property . ':' . $value . ';';
		}

		return ':root{' . $css . '}';
	}

	/**
	 * Whether the font pair must be loaded from Google Fonts.
	 *
	 * @param array<string,mixed> $settings Settings merged over defaults.
	 * @return bool
	 */
	public static function needs_google_fonts( $settings ) {
		return ! ( Settings::get_path( $settings, 'typography.self_host' )
			&& FontPairs::DEFAULT_PAIR === FontPairs::get( (string) Settings::get_path( $settings, 'typography.pair' ) )['slug'] );
	}

	/**
	 * Print the tokens after the theme stylesheet and load fonts if needed.
	 *
	 * @return void
	 */
	public function enqueue() {
		$settings = Settings::all();
		$handle   = 'qe-edu';

		if ( ! wp_style_is( $handle, 'registered' ) ) {
			$handle = 'qe-edu-core-tokens';
			wp_register_style( $handle, false, array(), QE_EDU_CORE_VERSION );
			wp_enqueue_style( $handle );
		}

		wp_add_inline_style( $handle, self::css( $settings ) );

		if ( self::needs_google_fonts( $settings ) ) {
			wp_enqueue_style( 'qe-edu-fonts', FontPairs::google_url( (string) $settings['typography']['pair'] ), array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google Fonts URL must not carry a version.
		}
	}
}
