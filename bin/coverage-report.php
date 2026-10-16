<?php
/**
 * Print per-file line coverage from a Clover XML report and exit non-zero
 * if any file is below the required percentage.
 *
 * Usage: php bin/coverage-report.php coverage/clover.xml [min-percent]
 *
 * @package QeEdu
 */

$file     = $argv[1] ?? 'coverage/clover.xml';
$required = isset( $argv[2] ) ? (float) $argv[2] : 100.0;
$xml      = simplexml_load_file( $file );
$root     = dirname( __DIR__ ) . '/';
$failed   = false;

foreach ( $xml->xpath( '//file' ) as $node ) {
	$total   = 0;
	$covered = 0;
	$missed  = array();
	foreach ( $node->line as $line ) {
		if ( 'stmt' !== (string) $line['type'] ) {
			continue;
		}
		++$total;
		if ( (int) $line['count'] > 0 ) {
			++$covered;
		} else {
			$missed[] = (int) $line['num'];
		}
	}
	$pct = $total ? 100 * $covered / $total : 100;
	if ( $pct < $required ) {
		$failed = true;
	}
	printf( "%6.2f%%  %s%s\n", $pct, str_replace( $root, '', (string) $node['name'] ), $missed ? '  missed: ' . implode( ',', $missed ) : '' );
}

$metrics = $xml->project->metrics;
printf( "\nTOTAL lines: %d/%d (%.2f%%)\n", (int) $metrics['coveredstatements'], (int) $metrics['statements'], 100 * (int) $metrics['coveredstatements'] / max( 1, (int) $metrics['statements'] ) );
exit( $failed ? 1 : 0 );
