/**
 * Build the installable packages:
 *   dist/qe-edu.zip       — the theme (Appearance → Themes → Add New → Upload)
 *   dist/qe-edu-core.zip  — the companion plugin (Plugins → Add New → Upload)
 *
 * Runs the asset build first so the zips always contain fresh CSS/JS.
 * Usage: npm run zip
 */
/* eslint-disable no-console -- command-line report. */
import { execSync } from 'node:child_process';
import { createWriteStream, mkdirSync, rmSync, statSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { ZipArchive } from 'archiver';

const root = join( dirname( fileURLToPath( import.meta.url ) ), '..' );
const dist = join( root, 'dist' );

// Never shipped: OS clutter and editor files.
const IGNORE = [ '**/.DS_Store', '**/Thumbs.db', '**/*.map', '**/.gitkeep' ];

const PACKAGES = [
	{ name: 'qe-edu', source: 'theme/qe-edu' },
	{ name: 'qe-edu-core', source: 'plugin/qe-edu-core' },
];

function pack( { name, source } ) {
	const file = join( dist, `${ name }.zip` );
	return new Promise( ( resolve, reject ) => {
		const output = createWriteStream( file );
		const archive = new ZipArchive( { zlib: { level: 9 } } );
		output.on( 'close', () => resolve( file ) );
		archive.on( 'warning', reject );
		archive.on( 'error', reject );
		archive.pipe( output );
		// Files sit inside a folder named after the slug, as WordPress expects.
		archive.glob(
			'**/*',
			{
				cwd: join( root, source ),
				ignore: IGNORE,
				dot: false,
			},
			{ prefix: name }
		);
		archive.finalize();
	} );
}

execSync( 'npm run build', { cwd: root, stdio: 'inherit' } );
rmSync( dist, { recursive: true, force: true } );
mkdirSync( dist, { recursive: true } );

for ( const pkg of PACKAGES ) {
	const file = await pack( pkg );
	console.log(
		`${ file.replace( root + '/', '' ) }  ${ Math.round(
			statSync( file ).size / 1024
		) } KB`
	);
}
