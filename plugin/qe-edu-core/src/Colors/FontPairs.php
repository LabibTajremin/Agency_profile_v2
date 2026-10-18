<?php
/**
 * The 12 curated heading / body font pairs offered in Theme Settings →
 * Typography. The default pair (Montserrat + Open Sans) is self-hosted by the
 * theme; the others load from Google Fonts unless self-hosting applies.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Colors;

defined( 'ABSPATH' ) || exit;

/**
 * Font pairs.
 */
final class FontPairs {

	/**
	 * Default pair slug (self-hosted by the theme).
	 */
	const DEFAULT_PAIR = 'montserrat-open-sans';

	/**
	 * Pairs: slug => [label, heading family, body family].
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function all() {
		$pair = static function ( $label, $heading, $body ) {
			return array(
				'label'   => $label,
				'heading' => $heading,
				'body'    => $body,
			);
		};

		return array(
			'montserrat-open-sans'    => $pair( __( 'Montserrat + Open Sans (default, fastest)', 'qe-edu-core' ), 'Montserrat', 'Open Sans' ),
			'poppins-inter'           => $pair( __( 'Poppins + Inter (modern)', 'qe-edu-core' ), 'Poppins', 'Inter' ),
			'playfair-source-sans'    => $pair( __( 'Playfair Display + Source Sans 3 (classic)', 'qe-edu-core' ), 'Playfair Display', 'Source Sans 3' ),
			'raleway-lato'            => $pair( __( 'Raleway + Lato (elegant)', 'qe-edu-core' ), 'Raleway', 'Lato' ),
			'dm-serif-dm-sans'        => $pair( __( 'DM Serif Display + DM Sans (editorial)', 'qe-edu-core' ), 'DM Serif Display', 'DM Sans' ),
			'lora-nunito-sans'        => $pair( __( 'Lora + Nunito Sans (friendly)', 'qe-edu-core' ), 'Lora', 'Nunito Sans' ),
			'oswald-roboto'           => $pair( __( 'Oswald + Roboto (bold)', 'qe-edu-core' ), 'Oswald', 'Roboto' ),
			'merriweather-work-sans'  => $pair( __( 'Merriweather + Work Sans (academic)', 'qe-edu-core' ), 'Merriweather', 'Work Sans' ),
			'sora-manrope'            => $pair( __( 'Sora + Manrope (tech)', 'qe-edu-core' ), 'Sora', 'Manrope' ),
			'libre-baskerville-karla' => $pair( __( 'Libre Baskerville + Karla (heritage)', 'qe-edu-core' ), 'Libre Baskerville', 'Karla' ),
			'outfit-jakarta'          => $pair( __( 'Outfit + Plus Jakarta Sans (clean)', 'qe-edu-core' ), 'Outfit', 'Plus Jakarta Sans' ),
			'cormorant-mulish'        => $pair( __( 'Cormorant Garamond + Mulish (luxury)', 'qe-edu-core' ), 'Cormorant Garamond', 'Mulish' ),
		);
	}

	/**
	 * Get a pair, falling back to the default.
	 *
	 * @param string $slug Slug.
	 * @return array<string,string> Includes `slug`.
	 */
	public static function get( $slug ) {
		$pairs = self::all();
		$slug  = isset( $pairs[ $slug ] ) ? $slug : self::DEFAULT_PAIR;

		return array_merge( array( 'slug' => $slug ), $pairs[ $slug ] );
	}

	/**
	 * Google Fonts CSS2 URL for a pair (heading 600–800, body 400–700).
	 *
	 * @param string $slug Slug.
	 * @return string
	 */
	public static function google_url( $slug ) {
		$pair = self::get( $slug );

		$family = static function ( $name, $weights ) {
			return 'family=' . str_replace( ' ', '+', $name ) . ':wght@' . $weights;
		};

		return 'https://fonts.googleapis.com/css2?' . $family( $pair['heading'], '600;700;800' ) . '&' . $family( $pair['body'], '400;600;700' ) . '&display=swap';
	}

	/**
	 * CSS font stack for a family.
	 *
	 * @param string $family Family name.
	 * @return string
	 */
	public static function stack( $family ) {
		return "'" . $family . "', system-ui, -apple-system, 'Segoe UI', sans-serif";
	}
}
