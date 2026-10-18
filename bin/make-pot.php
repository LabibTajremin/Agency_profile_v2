<?php
/**
 * Build the translation templates:
 *   theme/qe-edu/languages/qe-edu.pot              (text domain `qe-edu`)
 *   plugin/qe-edu-core/languages/qe-edu-core.pot   (text domain `qe-edu-core`)
 *
 * Uses WP-CLI's `wp i18n make-pot` (bundled with WP-CLI 2.x).
 * Usage: npm run pot
 *
 * @package QeEdu
 */

// phpcs:disable -- developer build script, not shipped.

$root    = dirname( __DIR__ );
$targets = array(
	'theme/qe-edu'       => array( 'qe-edu', 'theme/qe-edu/languages/qe-edu.pot' ),
	'plugin/qe-edu-core' => array( 'qe-edu-core', 'plugin/qe-edu-core/languages/qe-edu-core.pot' ),
);
$exclude = 'node_modules,vendor,tests,demo,assets/js,assets/css,assets/scss,languages';

exec( 'wp --version --allow-root 2>/dev/null', $out, $code );
if ( 0 !== $code ) {
	fwrite( STDERR, "WP-CLI is required: https://wp-cli.org (or run inside wp-env: npx wp-env run cli wp i18n make-pot ...)\n" );
	exit( 1 );
}

$failed = false;
foreach ( $targets as $source => list( $domain, $pot ) ) {
	$command = sprintf(
		'wp i18n make-pot %s %s --domain=%s --exclude=%s --skip-audit --allow-root 2>&1',
		escapeshellarg( $root . '/' . $source ),
		escapeshellarg( $root . '/' . $pot ),
		escapeshellarg( $domain ),
		escapeshellarg( $exclude )
	);
	exec( $command, $lines, $status );
	$count = is_readable( $root . '/' . $pot ) ? substr_count( (string) file_get_contents( $root . '/' . $pot ), "\nmsgid " ) : 0;
	printf( "%s %s (%d strings)\n", 0 === $status ? 'OK  ' : 'FAIL', $pot, $count );
	$failed = $failed || 0 !== $status;
}

exit( $failed ? 1 : 0 );
