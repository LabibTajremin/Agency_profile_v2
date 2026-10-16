<?php
/**
 * Theme Settings values exposed to Elementor dynamic tags.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Elementor\Tags;

use QeEdu\Core\Elementor\Widgets\SocialLinks;
use QeEdu\Core\Settings\Schema;
use QeEdu\Core\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Values.
 */
final class Values {

	/**
	 * Text values: key => label.
	 *
	 * @return array<string,string>
	 */
	public static function text_options() {
		return array(
			'org_name'  => __( 'Organisation name', 'qe-edu' ),
			'phone'     => __( 'Main phone', 'qe-edu' ),
			'phone_2'   => __( 'Second phone', 'qe-edu' ),
			'email'     => __( 'Email', 'qe-edu' ),
			'whatsapp'  => __( 'WhatsApp number', 'qe-edu' ),
			'address'   => __( 'Address', 'qe-edu' ),
			'copyright' => __( 'Copyright text', 'qe-edu' ),
		);
	}

	/**
	 * A text value.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	public static function text( $key ) {
		switch ( $key ) {
			case 'org_name':
				$name = (string) Settings::get( 'brand.org_name' );
				return '' !== $name ? $name : (string) get_bloginfo( 'name' );
			case 'copyright':
				$text = (string) Settings::get( 'footer.copyright' );
				$text = '' !== $text ? $text : __( '© {year} {site}. All rights reserved.', 'qe-edu' );
				return str_replace( array( '{year}', '{site}' ), array( wp_date( 'Y' ), get_bloginfo( 'name' ) ), $text );
			case 'phone':
			case 'phone_2':
			case 'email':
			case 'whatsapp':
			case 'address':
				return (string) Settings::get( 'contact.' . $key );
			default:
				return '';
		}
	}

	/**
	 * Link values: key => label.
	 *
	 * @return array<string,string>
	 */
	public static function url_options() {
		$options = array(
			'phone'       => __( 'Call the main phone', 'qe-edu' ),
			'phone_2'     => __( 'Call the second phone', 'qe-edu' ),
			'email'       => __( 'Send an email', 'qe-edu' ),
			'whatsapp'    => __( 'Chat on WhatsApp', 'qe-edu' ),
			'map'         => __( 'Map link', 'qe-edu' ),
			'profile_pdf' => __( 'Company profile PDF', 'qe-edu' ),
		);
		foreach ( Schema::social_networks() as $key => $label ) {
			$options[ 'social_' . $key ] = $label;
		}

		return $options;
	}

	/**
	 * A link value.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	public static function url( $key ) {
		if ( 0 === strpos( $key, 'social_' ) ) {
			return (string) Settings::get( 'social.' . substr( $key, 7 ) );
		}

		switch ( $key ) {
			case 'phone':
			case 'phone_2':
				$phone = preg_replace( '/[^0-9+]/', '', (string) Settings::get( 'contact.' . $key ) );
				return '' !== $phone ? 'tel:' . $phone : '';
			case 'email':
				$email = (string) Settings::get( 'contact.email' );
				return '' !== $email ? 'mailto:' . $email : '';
			case 'whatsapp':
				return SocialLinks::whatsapp_url( (string) Settings::get( 'contact.whatsapp' ) );
			case 'map':
				return (string) Settings::get( 'contact.map' );
			case 'profile_pdf':
				$id = absint( Settings::get( 'brand.profile_pdf' ) );
				return $id ? (string) wp_get_attachment_url( $id ) : '';
			default:
				return '';
		}
	}
}
