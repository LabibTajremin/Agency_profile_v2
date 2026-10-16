/**
 * Minify the vanilla JS sources: every `assets/js/src/*.js` file becomes
 * `assets/js/<name>.min.js` next to it.
 */
import { readdirSync, readFileSync, writeFileSync, existsSync } from 'node:fs';
import { join, basename } from 'node:path';
import { minify } from 'terser';

const roots = [ 'theme/qe-edu/assets/js', 'plugin/qe-edu-core/assets/js' ];

for ( const root of roots ) {
	const src = join( root, 'src' );
	if ( ! existsSync( src ) ) {
		continue;
	}
	for ( const file of readdirSync( src ).filter( ( f ) =>
		f.endsWith( '.js' )
	) ) {
		const code = readFileSync( join( src, file ), 'utf8' );
		const result = await minify( code, {
			compress: true,
			mangle: true,
			format: { comments: false },
		} );
		const out = join( root, basename( file, '.js' ) + '.min.js' );
		writeFileSync( out, result.code );
		// eslint-disable-next-line no-console
		console.log( `${ out } ${ result.code.length } bytes` );
	}
}
