<?php
/**
 * Simple per-IP rate limit for public forms (stored in transients; the IP is
 * hashed, never stored).
 *
 * @package QeEdu\Core
 */

namespace QeEdu\Core\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Rate limiter.
 */
final class RateLimiter {

	/**
	 * Maximum attempts per window.
	 */
	const MAX = 5;

	/**
	 * Window length in seconds.
	 */
	const WINDOW = 600;

	/**
	 * Transient key for an action and the current visitor.
	 *
	 * @param string $action Form action.
	 * @return string
	 */
	public static function key( $action ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return 'qe_rl_' . md5( $action . '|' . $ip . '|' . wp_salt( 'nonce' ) );
	}

	/**
	 * Record an attempt; returns false once the visitor is over the limit.
	 *
	 * @param string $action Form action.
	 * @return bool Whether the attempt is allowed.
	 */
	public static function attempt( $action ) {
		$key   = self::key( $action );
		$count = (int) get_transient( $key );

		/**
		 * Filters how many submissions one visitor may send per 10 minutes.
		 *
		 * @param int    $max    Maximum attempts.
		 * @param string $action Form action (register|subscribe).
		 */
		$max = (int) apply_filters( 'qe_edu_rate_limit', self::MAX, $action );

		if ( $count >= $max ) {
			return false;
		}

		set_transient( $key, $count + 1, self::WINDOW );

		return true;
	}
}
