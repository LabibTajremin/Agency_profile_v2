<?php
/**
 * Per-page SEO fields (stored as post meta) and helpers to read them.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * SEO fields.
 */
final class Fields {

	/**
	 * Field definitions: key => [type, label, help].
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function all() {
		return array(
			'title'          => array(
				'type'  => 'text',
				'label' => __( 'SEO title', 'qe-edu-core' ),
				'help'  => __( 'The blue headline in Google. About 50–60 characters. Leave empty to use the page title.', 'qe-edu-core' ),
			),
			'description'    => array(
				'type'  => 'textarea',
				'label' => __( 'Meta description', 'qe-edu-core' ),
				'help'  => __( 'The grey text under the headline in Google. About 120–160 characters.', 'qe-edu-core' ),
			),
			'keyword'        => array(
				'type'  => 'text',
				'label' => __( 'Focus keyword', 'qe-edu-core' ),
				'help'  => __( 'The phrase people search for, e.g. "study in Canada". You will see simple tips below.', 'qe-edu-core' ),
			),
			'canonical'      => array(
				'type'  => 'url',
				'label' => __( 'Canonical address', 'qe-edu-core' ),
				'help'  => __( 'Advanced. Only if this page copies another page; paste the original address.', 'qe-edu-core' ),
			),
			'noindex'        => array(
				'type'  => 'checkbox',
				'label' => __( 'Hide this page from search engines (noindex)', 'qe-edu-core' ),
				'help'  => __( 'For thank-you pages, private landing pages and similar.', 'qe-edu-core' ),
			),
			'nofollow'       => array(
				'type'  => 'checkbox',
				'label' => __( 'Ask search engines not to follow links on this page (nofollow)', 'qe-edu-core' ),
				'help'  => __( 'Rarely needed.', 'qe-edu-core' ),
			),
			'og_title'       => array(
				'type'  => 'text',
				'label' => __( 'Title when shared on social media', 'qe-edu-core' ),
				'help'  => __( 'Leave empty to use the SEO title.', 'qe-edu-core' ),
			),
			'og_description' => array(
				'type'  => 'textarea',
				'label' => __( 'Description when shared on social media', 'qe-edu-core' ),
				'help'  => __( 'Leave empty to use the meta description.', 'qe-edu-core' ),
			),
			'og_image'       => array(
				'type'  => 'media',
				'label' => __( 'Image when shared on social media', 'qe-edu-core' ),
				'help'  => __( '1200×630 is ideal. Leave empty to use the featured image or the default share image.', 'qe-edu-core' ),
			),
			'schema'         => array(
				'type'    => 'select',
				'label'   => __( 'What kind of page is this?', 'qe-edu-core' ),
				'help'    => __( 'Helps Google show the right kind of result. Questions come from the FAQ widget (switch on "Tell Google these are FAQs") and event details from the event itself.', 'qe-edu-core' ),
				'options' => self::schema_types(),
			),
		);
	}

	/**
	 * Page types for schema.org.
	 *
	 * @return array<string,string>
	 */
	public static function schema_types() {
		return array(
			'WebPage'     => __( 'Normal page', 'qe-edu-core' ),
			'AboutPage'   => __( 'About page', 'qe-edu-core' ),
			'ContactPage' => __( 'Contact page', 'qe-edu-core' ),
			'FAQPage'     => __( 'Questions & answers page', 'qe-edu-core' ),
			'Event'       => __( 'Event', 'qe-edu-core' ),
			'Course'      => __( 'Course / programme', 'qe-edu-core' ),
		);
	}

	/**
	 * Meta key for a field.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public static function meta_key( $key ) {
		return '_qe_seo_' . $key;
	}

	/**
	 * Read a field.
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Field key.
	 * @return string
	 */
	public static function get( $post_id, $key ) {
		return (string) get_post_meta( $post_id, self::meta_key( $key ), true );
	}

	/**
	 * Sanitise a field value.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Raw value.
	 * @return string
	 */
	public static function sanitize( $key, $value ) {
		$fields = self::all();
		$value  = is_scalar( $value ) ? trim( (string) $value ) : '';

		switch ( $fields[ $key ]['type'] ) {
			case 'checkbox':
				return '' !== $value && '0' !== $value ? '1' : '';
			case 'url':
				return esc_url_raw( $value );
			case 'media':
				return absint( $value ) ? (string) absint( $value ) : '';
			case 'select':
				return array_key_exists( $value, $fields[ $key ]['options'] ) ? $value : '';
			case 'textarea':
				return sanitize_textarea_field( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Save one field (empty values delete the meta).
	 *
	 * @param int    $post_id Post id.
	 * @param string $key     Field key.
	 * @param mixed  $value   Raw value.
	 * @return void
	 */
	public static function save( $post_id, $key, $value ) {
		$clean = self::sanitize( $key, $value );

		if ( '' === $clean ) {
			delete_post_meta( $post_id, self::meta_key( $key ) );
		} else {
			update_post_meta( $post_id, self::meta_key( $key ), $clean );
		}
	}

	/**
	 * Post types that get SEO fields: every public type except attachments.
	 *
	 * @return string[]
	 */
	public static function post_types() {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );

		return array_values( $types );
	}
}
