<?php
/**
 * Plugin Name: QE Edu e2e helpers
 * Description: Test sites only. Raises the form rate limit so repeated e2e runs are not blocked.
 *
 * Copy to wp-content/mu-plugins/ on the test site.
 *
 * @package QeEdu
 */

add_filter(
	'qe_edu_rate_limit',
	static function () {
		return 1000;
	}
);
