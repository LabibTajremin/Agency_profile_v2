<?php
/**
 * Content types: definitions and their simple fields.
 *
 * Every type uses a plain edit screen (title, featured image, short text and
 * a few labelled fields) instead of the block editor, so adding a country,
 * service or partner logo feels like filling in a form.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Types.
 */
final class Types {

	/**
	 * Type definitions: slug => [labels, menu icon, fields, public].
	 *
	 * Field types: text|url|date|time|number|select|media|textarea.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		return array(
			'qe_destination' => array(
				'singular' => __( 'Destination', 'qe-edu' ),
				'plural'   => __( 'Destinations', 'qe-edu' ),
				'slug'     => 'destinations',
				'icon'     => 'dashicons-admin-site-alt3',
				'public'   => true,
				'excerpt'  => __( 'Short introduction (one or two sentences)', 'qe-edu' ),
				'image'    => __( 'Country photo', 'qe-edu' ),
				'fields'   => array(
					'country' => array(
						'type'  => 'text',
						'label' => __( 'Country name', 'qe-edu' ),
						'help'  => __( 'Used in "Study in {name}". Leave empty to use the title.', 'qe-edu' ),
					),
					'flag'    => array(
						'type'  => 'media',
						'label' => __( 'Flag', 'qe-edu' ),
						'help'  => __( 'A small flag image (optional).', 'qe-edu' ),
					),
					'link'    => array(
						'type'  => 'url',
						'label' => __( 'Link', 'qe-edu' ),
						'help'  => __( 'Where the card leads. Leave empty to open this destination\'s own page.', 'qe-edu' ),
					),
				),
			),
			'qe_service'     => array(
				'singular' => __( 'Service', 'qe-edu' ),
				'plural'   => __( 'Services', 'qe-edu' ),
				'slug'     => 'services',
				'icon'     => 'dashicons-welcome-learn-more',
				'public'   => true,
				'excerpt'  => __( 'Short description shown on the card', 'qe-edu' ),
				'image'    => __( 'Card image', 'qe-edu' ),
				'fields'   => array(
					'link'        => array(
						'type'  => 'url',
						'label' => __( 'Link', 'qe-edu' ),
						'help'  => __( 'Where "Learn more" leads. Leave empty to open this service\'s own page.', 'qe-edu' ),
					),
					'button_text' => array(
						'type'  => 'text',
						'label' => __( 'Button text', 'qe-edu' ),
						'help'  => __( 'Leave empty to use the widget\'s button text.', 'qe-edu' ),
					),
				),
			),
			'qe_partner'     => array(
				'singular' => __( 'Partner', 'qe-edu' ),
				'plural'   => __( 'Partners', 'qe-edu' ),
				'slug'     => 'partners',
				'icon'     => 'dashicons-groups',
				'public'   => false,
				'excerpt'  => '',
				'image'    => __( 'Logo', 'qe-edu' ),
				'fields'   => array(
					'url'  => array(
						'type'  => 'url',
						'label' => __( 'Website', 'qe-edu' ),
						'help'  => __( 'The partner\'s website (optional).', 'qe-edu' ),
					),
					'type' => array(
						'type'    => 'select',
						'label'   => __( 'Partner type', 'qe-edu' ),
						'help'    => __( 'Lets widgets show only one kind of partner.', 'qe-edu' ),
						'options' => self::partner_types(),
					),
				),
			),
			'qe_event'       => array(
				'singular' => __( 'Event', 'qe-edu' ),
				'plural'   => __( 'Events', 'qe-edu' ),
				'slug'     => 'events',
				'icon'     => 'dashicons-calendar-alt',
				'public'   => true,
				'excerpt'  => __( 'Short summary', 'qe-edu' ),
				'image'    => __( 'Event image', 'qe-edu' ),
				'editor'   => true,
				'fields'   => array(
					'date'        => array(
						'type'  => 'date',
						'label' => __( 'Date', 'qe-edu' ),
						'help'  => __( 'The day the event takes place.', 'qe-edu' ),
					),
					'time'        => array(
						'type'  => 'time',
						'label' => __( 'Start time', 'qe-edu' ),
						'help'  => __( 'Optional.', 'qe-edu' ),
					),
					'venue'       => array(
						'type'  => 'text',
						'label' => __( 'Venue', 'qe-edu' ),
						'help'  => __( 'Place name and city, or "Online".', 'qe-edu' ),
					),
					'online_link' => array(
						'type'  => 'url',
						'label' => __( 'Online link', 'qe-edu' ),
						'help'  => __( 'Webinar or registration link (optional).', 'qe-edu' ),
					),
				),
			),
			'qe_team'        => array(
				'singular' => __( 'Team Member', 'qe-edu' ),
				'plural'   => __( 'Team', 'qe-edu' ),
				'slug'     => 'team',
				'icon'     => 'dashicons-businessperson',
				'public'   => false,
				'excerpt'  => __( 'Short bio', 'qe-edu' ),
				'image'    => __( 'Photo', 'qe-edu' ),
				'fields'   => array(
					'role'     => array(
						'type'  => 'text',
						'label' => __( 'Job title', 'qe-edu' ),
						'help'  => __( 'For example "Admissions Director".', 'qe-edu' ),
					),
					'linkedin' => array(
						'type'  => 'url',
						'label' => __( 'LinkedIn profile', 'qe-edu' ),
						'help'  => __( 'Optional.', 'qe-edu' ),
					),
				),
			),
			'qe_testimonial' => array(
				'singular' => __( 'Testimonial', 'qe-edu' ),
				'plural'   => __( 'Testimonials', 'qe-edu' ),
				'slug'     => 'testimonials',
				'icon'     => 'dashicons-format-quote',
				'public'   => false,
				'excerpt'  => __( 'What they said', 'qe-edu' ),
				'image'    => __( 'Photo', 'qe-edu' ),
				'fields'   => array(
					'role'   => array(
						'type'  => 'text',
						'label' => __( 'Who they are', 'qe-edu' ),
						'help'  => __( 'For example "MSc student, University of Leeds".', 'qe-edu' ),
					),
					'rating' => array(
						'type'    => 'select',
						'label'   => __( 'Star rating', 'qe-edu' ),
						'help'    => __( 'Shown as stars.', 'qe-edu' ),
						'options' => array(
							'5' => '★★★★★',
							'4' => '★★★★',
							'3' => '★★★',
							'0' => __( 'No stars', 'qe-edu' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Partner types.
	 *
	 * @return array<string,string>
	 */
	public static function partner_types() {
		return array(
			'university'    => __( 'University / institution', 'qe-edu' ),
			'recruitment'   => __( 'Recruitment partner', 'qe-edu' ),
			'accreditation' => __( 'Accreditation body', 'qe-edu' ),
		);
	}

	/**
	 * Meta key for a field.
	 *
	 * @param string $field Field key.
	 * @return string
	 */
	public static function meta_key( $field ) {
		return '_qe_' . $field;
	}

	/**
	 * Read a field value.
	 *
	 * @param int    $post_id Post id.
	 * @param string $field   Field key.
	 * @return string
	 */
	public static function get( $post_id, $field ) {
		return (string) get_post_meta( $post_id, self::meta_key( $field ), true );
	}

	/**
	 * Sanitise a field value.
	 *
	 * @param array<string,mixed> $field Field definition.
	 * @param mixed               $value Raw value.
	 * @return string
	 */
	public static function sanitize( $field, $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';

		switch ( $field['type'] ) {
			case 'url':
				return esc_url_raw( $value );
			case 'media':
				return (string) absint( $value );
			case 'date':
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
			case 'time':
				return preg_match( '/^\d{2}:\d{2}$/', $value ) ? $value : '';
			case 'select':
				return array_key_exists( $value, $field['options'] ) ? $value : (string) array_key_first( $field['options'] );
			default:
				return sanitize_text_field( $value );
		}
	}
}
