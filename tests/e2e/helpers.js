/**
 * Shared e2e helpers.
 */
const { PNG } = require( 'pngjs' );
const pixelmatch = require( 'pixelmatch' );

/**
 * Collect console errors and failed requests on a page.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {string[]} Live array of problems.
 */
function watchErrors( page ) {
	const errors = [];
	page.on( 'console', ( msg ) => {
		if ( msg.type() === 'error' ) {
			// The location tells first-party errors from third-party embeds.
			errors.push(
				'console: ' + msg.text() + ' @ ' + msg.location().url
			);
		}
	} );
	page.on( 'pageerror', ( err ) =>
		errors.push(
			// The stack tells first-party errors from third-party embeds.
			'pageerror: ' + err.message + '\n' + ( err.stack || '' )
		)
	);
	page.on( 'response', ( res ) => {
		if ( res.status() >= 400 ) {
			errors.push( 'http ' + res.status() + ': ' + res.url() );
		}
	} );
	return errors;
}

/**
 * Percentage of differing pixels between two PNG buffers. Images of different
 * size are compared over their common area plus the size difference.
 *
 * @param {Buffer} a PNG.
 * @param {Buffer} b PNG.
 * @return {number} Percent different (0-100).
 */
function diffPercent( a, b ) {
	const imgA = PNG.sync.read( a );
	const imgB = PNG.sync.read( b );
	const width = Math.min( imgA.width, imgB.width );
	const height = Math.min( imgA.height, imgB.height );
	const crop = ( img ) => {
		const out = new PNG( { width, height } );
		PNG.bitblt( img, out, 0, 0, width, height, 0, 0 );
		return out.data;
	};
	const diff = pixelmatch( crop( imgA ), crop( imgB ), null, width, height, {
		threshold: 0.1,
	} );
	const total = Math.max(
		imgA.width * imgA.height,
		imgB.width * imgB.height
	);
	const missing = total - width * height;
	return ( ( diff + missing ) / total ) * 100;
}

module.exports = { watchErrors, diffPercent };
