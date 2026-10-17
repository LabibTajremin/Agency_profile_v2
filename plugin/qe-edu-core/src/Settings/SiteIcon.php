<?php
/**
 * Turn the logo into a square site icon (favicon) on a transparent 512×512
 * canvas. For a wide "symbol + name" logo only the symbol is used; any other
 * logo is centred whole, never squashed. Made once per logo and reused.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Site icon from the logo.
 */
class SiteIcon {

	/**
	 * Icon size in pixels (what WordPress asks for).
	 */
	const SIZE = 512;

	/**
	 * Meta key on the logo pointing at its square copy.
	 */
	const META = '_qe_site_icon';

	/**
	 * The site icon for a logo: a square copy when one can be made, otherwise
	 * the logo itself.
	 *
	 * @param int $logo_id Logo attachment id.
	 * @return int Attachment id.
	 */
	public static function from_logo( $logo_id ) {
		$logo_id = absint( $logo_id );
		if ( ! $logo_id ) {
			return 0;
		}

		$existing = absint( get_post_meta( $logo_id, self::META, true ) );
		if ( $existing && get_post( $existing ) ) {
			return $existing;
		}

		$png = self::square_png( (string) get_attached_file( $logo_id ) );
		if ( '' === $png ) {
			return $logo_id;
		}

		$upload = wp_upload_bits( 'site-icon-' . $logo_id . '.png', null, $png );
		if ( ! empty( $upload['error'] ) ) {
			return $logo_id;
		}

		$icon = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/png',
				'post_title'     => get_the_title( $logo_id ),
				'post_status'    => 'inherit',
			),
			$upload['file']
		);
		if ( ! $icon || is_wp_error( $icon ) ) {
			return $logo_id;
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		wp_update_attachment_metadata( $icon, wp_generate_attachment_metadata( $icon, $upload['file'] ) );
		// Same context WordPress gives icons made in the Customizer.
		update_post_meta( $icon, '_wp_attachment_context', 'site-icon' );
		update_post_meta( $logo_id, self::META, $icon );

		return (int) $icon;
	}

	/**
	 * A square, transparent PNG with the image centred, or '' when the file
	 * cannot be read as a raster image (e.g. SVG) or GD is missing.
	 *
	 * @param string $file Image path.
	 * @return string PNG bytes.
	 */
	public static function square_png( $file ) {
		if ( '' === $file || ! is_readable( $file ) || ! function_exists( 'imagecreatefromstring' ) ) {
			return '';
		}

		$source = @imagecreatefromstring( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- unreadable images return false.
		if ( ! $source ) {
			return '';
		}

		$box    = self::mark_box( $source );
		$src_x  = $box ? $box[0] : 0;
		$src_y  = $box ? $box[1] : 0;
		$width  = $box ? $box[2] : imagesx( $source );
		$height = $box ? $box[3] : imagesy( $source );
		$scale  = min( self::SIZE / $width, self::SIZE / $height );
		$new_w  = max( 1, (int) round( $width * $scale ) );
		$new_h  = max( 1, (int) round( $height * $scale ) );

		$canvas = imagecreatetruecolor( self::SIZE, self::SIZE );
		imagealphablending( $canvas, false );
		imagesavealpha( $canvas, true );
		imagefill( $canvas, 0, 0, imagecolorallocatealpha( $canvas, 0, 0, 0, 127 ) );
		imagealphablending( $canvas, true );
		imagecopyresampled( $canvas, $source, (int) ( ( self::SIZE - $new_w ) / 2 ), (int) ( ( self::SIZE - $new_h ) / 2 ), $src_x, $src_y, $new_w, $new_h, $width, $height );

		ob_start();
		imagepng( $canvas );

		return (string) ob_get_clean();
	}

	/**
	 * In a wide "symbol + name" logo, the box of the roughly square symbol on
	 * the left (separated from the name by empty space), so the icon shows the
	 * symbol instead of a tiny unreadable strip. Null when there is none.
	 *
	 * @param \GdImage|resource $image Image.
	 * @return int[]|null [x, y, width, height].
	 */
	public static function mark_box( $image ) {
		$width  = imagesx( $image );
		$height = imagesy( $image );
		if ( $width < 1.5 * $height ) {
			return null;
		}

		$background = imagecolorsforindex( $image, imagecolorat( $image, 0, 0 ) );
		$empty      = static function ( $x ) use ( $image, $height, $background ) {
			for ( $y = 0; $y < $height; $y++ ) {
				if ( ! self::is_background( imagecolorsforindex( $image, imagecolorat( $image, $x, $y ) ), $background ) ) {
					return false;
				}
			}
			return true;
		};

		// First filled column, then the first empty column after it.
		$start = 0;
		while ( $start < $width && $empty( $start ) ) {
			++$start;
		}
		$end = $start;
		while ( $end < $width && ! $empty( $end ) ) {
			++$end;
		}
		$rest = $end;
		while ( $rest < $width && $empty( $rest ) ) {
			++$rest;
		}
		if ( $start >= $width || $rest >= $width ) {
			return null; // Blank, or nothing after the first block (no separate name).
		}

		// Rows used by the left block.
		$top    = $height;
		$bottom = -1;
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = $start; $x < $end; $x++ ) {
				if ( ! self::is_background( imagecolorsforindex( $image, imagecolorat( $image, $x, $y ) ), $background ) ) {
					$top    = min( $top, $y );
					$bottom = max( $bottom, $y );
					break;
				}
			}
		}

		$box_w = $end - $start;
		$box_h = $bottom - $top + 1;
		$ratio = $box_w / $box_h;

		return $ratio >= 0.6 && $ratio <= 1.6 && $box_h >= 0.4 * $height
			? array( $start, $top, $box_w, $box_h )
			: null;
	}

	/**
	 * Whether a pixel matches the background colour (both transparent, or
	 * the same colour within a small tolerance).
	 *
	 * @param array<string,int> $pixel      Pixel colour.
	 * @param array<string,int> $background Background colour.
	 * @return bool
	 */
	private static function is_background( $pixel, $background ) {
		if ( $pixel['alpha'] >= 120 && $background['alpha'] >= 120 ) {
			return true;
		}

		return abs( $pixel['alpha'] - $background['alpha'] ) <= 20
			&& abs( $pixel['red'] - $background['red'] ) + abs( $pixel['green'] - $background['green'] ) + abs( $pixel['blue'] - $background['blue'] ) <= 30;
	}
}
