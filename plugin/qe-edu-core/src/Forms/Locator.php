<?php
/**
 * Finds the saved settings of a form widget on a page, so submissions are
 * validated against the real configuration (fields, recipient, messages)
 * instead of anything sent by the browser.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Locator.
 */
final class Locator {

	/**
	 * Settings of widget `$element_id` on post `$post_id`, if it is one of
	 * `$widget_types` and the post is published, merged over `$defaults`.
	 *
	 * @param int                 $post_id      Post (page or Elementor template) id.
	 * @param string              $element_id   Elementor element id.
	 * @param string[]            $widget_types Allowed widget types.
	 * @param array<string,mixed> $defaults     Default settings.
	 * @return array<string,mixed>|null
	 */
	public static function find( $post_id, $element_id, $widget_types, $defaults ) {
		if ( ! $post_id || '' === $element_id || 'publish' !== get_post_status( $post_id ) ) {
			return null;
		}

		$data = json_decode( (string) get_post_meta( $post_id, '_elementor_data', true ), true );

		$settings = is_array( $data ) ? self::search( $data, $element_id, $widget_types ) : null;

		return null === $settings ? null : array_merge( $defaults, $settings );
	}

	/**
	 * Depth-first search.
	 *
	 * @param array<int,array<string,mixed>> $elements     Elements.
	 * @param string                         $element_id   Id.
	 * @param string[]                       $widget_types Allowed types.
	 * @return array<string,mixed>|null
	 */
	private static function search( $elements, $element_id, $widget_types ) {
		foreach ( $elements as $element ) {
			if ( isset( $element['id'], $element['widgetType'] ) && $element['id'] === $element_id ) {
				if ( ! in_array( $element['widgetType'], $widget_types, true ) ) {
					return null;
				}
				return isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
			}

			if ( ! empty( $element['elements'] ) ) {
				$found = self::search( $element['elements'], $element_id, $widget_types );
				if ( null !== $found ) {
					return $found;
				}
			}
		}

		return null;
	}
}
