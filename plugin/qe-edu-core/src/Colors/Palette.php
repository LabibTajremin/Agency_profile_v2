<?php
/**
 * Accent palette registry and colour maths.
 *
 * Each accent defines a primary and dark shade. Everything else — the 8% soft
 * tint, the text colour on the primary and on the dark shade, and the link
 * colour for light and dark backgrounds — is computed so it always reaches the
 * WCAG AA contrast ratio of 4.5:1.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Colors;

defined( 'ABSPATH' ) || exit;

/**
 * Palette.
 */
final class Palette {

	/**
	 * Minimum contrast ratio (WCAG 2.2 AA, normal text).
	 */
	const MIN_CONTRAST = 4.5;

	/**
	 * Default accent slug.
	 */
	const DEFAULT_ACCENT = 'qe-crimson';

	/**
	 * Slug of the custom swatch.
	 */
	const CUSTOM = 'custom';

	/**
	 * Light surface the link colour must contrast with.
	 */
	const LIGHT_SURFACE = '#FFFFFF';

	/**
	 * Dark-mode surface the link colour must contrast with.
	 */
	const DARK_SURFACE = '#0E0B1F';

	/**
	 * The 12 accent palettes. `on` is the preferred text colour on the primary;
	 * it is replaced automatically if it does not reach 4.5:1.
	 *
	 * Deviation from the build spec table, documented in the README: Emerald's
	 * primary is #0F875D instead of #0F8A5F (2% darker) because #0F8A5F reaches
	 * only 4.36:1 with white and 4.33:1 with near-black text.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function accents() {
		return array(
			'qe-crimson'      => array(
				'name'    => __( 'QE Crimson', 'qe-edu' ),
				'primary' => '#A41E34',
				'dark'    => '#7E1426',
				'on'      => '#FFFFFF',
			),
			'royal-indigo'    => array(
				'name'    => __( 'Royal Indigo', 'qe-edu' ),
				'primary' => '#363D97',
				'dark'    => '#262B70',
				'on'      => '#FFFFFF',
			),
			'electric-violet' => array(
				'name'    => __( 'Electric Violet', 'qe-edu' ),
				'primary' => '#6440FB',
				'dark'    => '#4A2BD1',
				'on'      => '#FFFFFF',
			),
			'midnight-navy'   => array(
				'name'    => __( 'Midnight Navy', 'qe-edu' ),
				'primary' => '#140342',
				'dark'    => '#0B0127',
				'on'      => '#FFFFFF',
			),
			'ocean-blue'      => array(
				'name'    => __( 'Ocean Blue', 'qe-edu' ),
				'primary' => '#005A94',
				'dark'    => '#003E65',
				'on'      => '#FFFFFF',
			),
			'emerald'         => array(
				'name'    => __( 'Emerald', 'qe-edu' ),
				'primary' => '#0F875D',
				'dark'    => '#0A6345',
				'on'      => '#FFFFFF',
			),
			'teal-lagoon'     => array(
				'name'    => __( 'Teal Lagoon', 'qe-edu' ),
				'primary' => '#0E7C86',
				'dark'    => '#095961',
				'on'      => '#FFFFFF',
			),
			'champagne-gold'  => array(
				'name'    => __( 'Champagne Gold', 'qe-edu' ),
				'primary' => '#B8913A',
				'dark'    => '#8C6B24',
				'on'      => '#111111',
			),
			'sunset-coral'    => array(
				'name'    => __( 'Sunset Coral', 'qe-edu' ),
				'primary' => '#E2574C',
				'dark'    => '#B83E35',
				'on'      => '#FFFFFF',
			),
			'amber-saffron'   => array(
				'name'    => __( 'Amber Saffron', 'qe-edu' ),
				'primary' => '#E89B12',
				'dark'    => '#B4760A',
				'on'      => '#111111',
			),
			'rose-quartz'     => array(
				'name'    => __( 'Rose Quartz', 'qe-edu' ),
				'primary' => '#C2477A',
				'dark'    => '#962F5B',
				'on'      => '#FFFFFF',
			),
			'graphite'        => array(
				'name'    => __( 'Graphite', 'qe-edu' ),
				'primary' => '#2F3542',
				'dark'    => '#1C2029',
				'on'      => '#FFFFFF',
			),
		);
	}

	/**
	 * Resolve the full token set for an accent slug (or the custom colour).
	 *
	 * @param string $slug   Accent slug, or `custom`.
	 * @param string $custom Custom primary colour, used when `$slug` is `custom`.
	 * @return array<string,string> Keys: slug, name, primary, dark, soft, on_primary, on_dark, link, link_dark_mode, dm_primary, dm_on_primary, dm_hover, dm_on_hover.
	 */
	public static function resolve( $slug, $custom = '' ) {
		$accents = self::accents();

		if ( self::CUSTOM === $slug && self::normalize( $custom ) ) {
			$primary = self::readable_custom( $custom );
			$accent  = array(
				'name'    => __( 'Custom', 'qe-edu' ),
				'primary' => $primary,
				'dark'    => self::mix( $primary, '#000000', 0.23 ),
				'on'      => '#FFFFFF',
			);
		} else {
			$slug   = isset( $accents[ $slug ] ) ? $slug : self::DEFAULT_ACCENT;
			$accent = $accents[ $slug ];
		}

		$primary = $accent['primary'];
		$dark    = $accent['dark'];

		// Dark mode: a lighter accent that reads as text on the dark surface,
		// with button text picked to stay readable on it.
		$dm_primary = self::dark_mode_accent( $primary );
		$dm_hover   = self::mix( $dm_primary, '#FFFFFF', 0.18 );

		return array(
			'slug'           => $slug,
			'name'           => $accent['name'],
			'primary'        => $primary,
			'dark'           => $dark,
			'soft'           => self::rgba( $primary, 0.08 ),
			'on_primary'     => self::text_on( $primary, $accent['on'] ),
			'on_dark'        => self::text_on( $dark, $accent['on'] ),
			'link'           => self::ensure_contrast( $primary, self::LIGHT_SURFACE ),
			'link_dark_mode' => $dm_primary,
			'dm_primary'     => $dm_primary,
			'dm_on_primary'  => self::text_on( $dm_primary, '#FFFFFF' ),
			'dm_hover'       => $dm_hover,
			'dm_on_hover'    => self::text_on( $dm_hover, '#FFFFFF' ),
		);
	}

	/**
	 * A custom colour that neither white nor near-black text can sit on with
	 * 4.5:1 (mid-tones such as #777777) is darkened just enough for white text.
	 * Every other colour is kept exactly as chosen.
	 *
	 * @param string $color Custom colour.
	 * @return string
	 */
	public static function readable_custom( $color ) {
		$color = self::normalize( $color );

		if ( max( self::contrast( $color, '#FFFFFF' ), self::contrast( $color, '#111111' ) ) >= self::MIN_CONTRAST ) {
			return $color;
		}

		return self::ensure_contrast( $color, '#FFFFFF' );
	}

	/**
	 * Dark-mode accent: readable as text on the dark surface AND able to carry
	 * readable button text (white or near-black). Lightens in 2% steps until
	 * both hold; near-white always satisfies both.
	 *
	 * @param string $color Light-mode accent.
	 * @return string
	 */
	public static function dark_mode_accent( $color ) {
		$base      = self::ensure_contrast( $color, self::DARK_SURFACE );
		$candidate = $base;
		$step      = 0;

		while ( max( self::contrast( $candidate, '#FFFFFF' ), self::contrast( $candidate, '#111111' ) ) < self::MIN_CONTRAST && $step < 50 ) {
			++$step;
			$candidate = self::mix( $base, '#FFFFFF', $step / 50 );
		}

		return $candidate;
	}

	/**
	 * Normalise a colour to upper-case #RRGGBB, or '' when invalid.
	 *
	 * @param mixed $color Colour (#RGB or #RRGGBB).
	 * @return string
	 */
	public static function normalize( $color ) {
		if ( ! is_string( $color ) || ! preg_match( '/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', trim( $color ), $m ) ) {
			return '';
		}

		$hex = $m[1];
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return '#' . strtoupper( $hex );
	}

	/**
	 * RGB channels (0-255).
	 *
	 * @param string $color Colour.
	 * @return int[]
	 */
	public static function rgb( $color ) {
		$hex = substr( self::normalize( $color ), 1 );

		return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	}

	/**
	 * Relative luminance (WCAG).
	 *
	 * @param string $color Colour.
	 * @return float
	 */
	public static function luminance( $color ) {
		$channels = array_map(
			static function ( $c ) {
				$c = $c / 255;
				return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
			},
			self::rgb( $color )
		);

		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	/**
	 * Contrast ratio between two colours (1–21).
	 *
	 * @param string $a Colour.
	 * @param string $b Colour.
	 * @return float
	 */
	public static function contrast( $a, $b ) {
		$la = self::luminance( $a );
		$lb = self::luminance( $b );

		return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
	}

	/**
	 * Mix two colours.
	 *
	 * @param string $color  Base colour.
	 * @param string $target Colour mixed in.
	 * @param float  $amount 0–1 share of `$target`.
	 * @return string
	 */
	public static function mix( $color, $target, $amount ) {
		$a = self::rgb( $color );
		$b = self::rgb( $target );

		$out = '#';
		for ( $i = 0; $i < 3; $i++ ) {
			$out .= sprintf( '%02X', (int) round( $a[ $i ] * ( 1 - $amount ) + $b[ $i ] * $amount ) );
		}

		return $out;
	}

	/**
	 * `rgb(r g b / a%)` string.
	 *
	 * @param string $color Colour.
	 * @param float  $alpha 0–1.
	 * @return string
	 */
	public static function rgba( $color, $alpha ) {
		list( $r, $g, $b ) = self::rgb( $color );

		return sprintf( 'rgb(%d %d %d / %s%%)', $r, $g, $b, round( $alpha * 100 ) );
	}

	/**
	 * Text colour to use on a background: the preferred colour if it reaches
	 * 4.5:1, otherwise whichever of white / near-black contrasts more.
	 *
	 * @param string $background Background.
	 * @param string $preferred  Preferred text colour.
	 * @return string
	 */
	public static function text_on( $background, $preferred = '#FFFFFF' ) {
		if ( self::contrast( $background, $preferred ) >= self::MIN_CONTRAST ) {
			return $preferred;
		}

		return self::contrast( $background, '#FFFFFF' ) >= self::contrast( $background, '#111111' ) ? '#FFFFFF' : '#111111';
	}

	/**
	 * Move a colour towards black or white (whichever contrasts more with the
	 * background) in 2% steps until it reaches 4.5:1 against the background.
	 * Pure black or white always reaches at least 4.58:1, so this terminates
	 * with a passing colour.
	 *
	 * @param string $color      Colour.
	 * @param string $background Background.
	 * @return string
	 */
	public static function ensure_contrast( $color, $background ) {
		$color     = self::normalize( $color );
		$target    = self::contrast( $background, '#000000' ) >= self::contrast( $background, '#FFFFFF' ) ? '#000000' : '#FFFFFF';
		$candidate = $color;
		$step      = 0;

		while ( self::contrast( $candidate, $background ) < self::MIN_CONTRAST && $step < 50 ) {
			++$step;
			$candidate = self::mix( $color, $target, $step / 50 );
		}

		return $candidate;
	}
}
