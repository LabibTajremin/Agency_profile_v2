<?php
/**
 * "No hard-coded strings" check (WP_THEME_BUILD_INSTRUCTIONS.md §3).
 *
 * Scans every PHP file in the theme and the plugin for user-facing text that
 * is not passed through a translation function or a setting:
 *  - literal text between HTML tags (T_INLINE_HTML tokens);
 *  - echo / print of a bare string literal containing letters.
 *
 * Exits non-zero and lists offending lines when anything is found.
 *
 * @package QeEdu
 */

$roots    = array( __DIR__ . '/../theme/qe-edu', __DIR__ . '/../plugin/qe-edu-core' );
$problems = array();

foreach ( $roots as $root ) {
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		if ( 'php' !== $file->getExtension() || false !== strpos( $file->getPathname(), '/vendor/' ) ) {
			continue;
		}
		$tokens = token_get_all( file_get_contents( $file->getPathname() ) );
		$count  = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			$token = $tokens[ $i ];
			if ( ! is_array( $token ) ) {
				continue;
			}
			if ( T_INLINE_HTML === $token[0] ) {
				// Drop tags, attributes, comments, doctype and entities; what
				// remains is visible text.
				$text = preg_replace( '/<!--.*?-->|<!doctype[^>]*>|<[^>]*>|&[a-z#0-9]+;/is', ' ', $token[1] );
				// An unterminated tag (attributes continue after a PHP block).
				$text = preg_replace( '/<[^>]*$|^[^<]*>/s', ' ', $text );
				// Attribute fragments between two PHP blocks inside one tag.
				$text = preg_replace( '/[a-z-]+="[^"]*"?|"/i', ' ', $text );
				if ( preg_match( '/\p{L}{2,}/u', $text ) ) {
					$problems[] = sprintf( '%s:%d  inline text: %s', $file->getPathname(), $token[2], trim( preg_replace( '/\s+/', ' ', $text ) ) );
				}
			}
			if ( in_array( $token[0], array( T_ECHO, T_PRINT ), true ) ) {
				$j = $i + 1;
				while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
					++$j;
				}
				$next = $tokens[ $j ] ?? null;
				$then = $tokens[ $j + 1 ] ?? null;
				if ( is_array( $next ) && T_CONSTANT_ENCAPSED_STRING === $next[0] && ';' === $then ) {
					$literal = strip_tags( stripcslashes( substr( $next[1], 1, -1 ) ) );
					if ( preg_match( '/\p{L}{2,}/u', $literal ) ) {
						$problems[] = sprintf( '%s:%d  echoed literal: %s', $file->getPathname(), $next[2], $literal );
					}
				}
			}
		}
	}
}

if ( $problems ) {
	fwrite( STDERR, "Hard-coded user-facing strings found:\n" . implode( "\n", $problems ) . "\n" );
	exit( 1 );
}

echo "OK: no hard-coded user-facing strings.\n";
