<?php
/**
 * Minimal Mailchimp client: add or update one subscriber.
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Mailchimp.
 */
final class Mailchimp {

	/**
	 * Subscribe an email address to an audience.
	 *
	 * @param string $api_key  API key ("…-us21").
	 * @param string $list_id  Audience id.
	 * @param string $email    Email.
	 * @return bool
	 */
	public static function subscribe( $api_key, $list_id, $email ) {
		$parts = explode( '-', $api_key );
		$dc    = end( $parts );

		if ( '' === $list_id || count( $parts ) < 2 || ! preg_match( '/^[a-z]+\d+$/', $dc ) ) {
			return false;
		}

		$response = wp_remote_request(
			sprintf( 'https://%1$s.api.mailchimp.com/3.0/lists/%2$s/members/%3$s', $dc, rawurlencode( $list_id ), md5( strtolower( $email ) ) ),
			array(
				'method'  => 'PUT',
				'timeout' => 10,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'qe-edu:' . $api_key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- HTTP Basic auth.
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'email_address' => $email,
						'status_if_new' => 'subscribed',
					)
				),
			)
		);

		$code = (int) wp_remote_retrieve_response_code( $response );

		return $code >= 200 && $code < 300;
	}
}
