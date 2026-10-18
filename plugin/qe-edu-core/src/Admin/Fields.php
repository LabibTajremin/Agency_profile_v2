<?php
/**
 * Shared admin field markup.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Fields.
 */
final class Fields {

	/**
	 * Media picker: hidden attachment id, preview, "Choose file" and "Remove".
	 * Wired up by assets/js/admin.js.
	 *
	 * @param string $mime  Library filter: `image` or a MIME type such as `application/pdf`.
	 * @param int    $value Attachment id.
	 * @param string $id    Element id.
	 * @param string $name  Input name.
	 * @return string
	 */
	public static function media( $mime, $value, $id, $name ) {
		$url     = $value ? wp_get_attachment_url( $value ) : '';
		$preview = '';

		if ( $url ) {
			$preview = 'image' === $mime
				? '<img src="' . esc_url( $url ) . '" alt="">'
				: '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( wp_basename( $url ) ) . '</a>';
		}

		return sprintf(
			'<div class="qe-media" data-qe-media data-mime="%1$s"><input type="hidden" id="%2$s" name="%3$s" value="%4$s" data-qe-media-id><div class="qe-media__preview" data-qe-media-preview>%5$s</div><button type="button" class="button" data-qe-media-choose>%6$s</button> <button type="button" class="button-link qe-media__remove" data-qe-media-remove%7$s>%8$s</button></div>',
			esc_attr( $mime ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( (string) $value ),
			$preview,
			esc_html__( 'Choose file', 'qe-edu-core' ),
			$value ? '' : ' hidden',
			esc_html__( 'Remove', 'qe-edu-core' )
		);
	}
}
