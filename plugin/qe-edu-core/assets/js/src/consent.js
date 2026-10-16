/**
 * Cookie consent bar: shows until the visitor chooses; "Accept" loads the
 * analytics prepared in <head> (window.qeEduLoadAnalytics).
 *
 * @param {Window} root Browser window.
 */
( function ( root ) {
	function read() {
		try {
			return root.localStorage.getItem( 'qe_consent' );
		} catch ( e ) {
			return null;
		}
	}

	function write( choice ) {
		try {
			root.localStorage.setItem( 'qe_consent', choice );
		} catch ( e ) {
			// Private mode: the choice lasts for this page view only.
		}
	}

	function init( doc ) {
		const bar = doc.querySelector( '.qe-consent' );
		if ( ! bar ) {
			return null;
		}
		if ( ! read() ) {
			bar.hidden = false;
		}
		bar.addEventListener( 'click', ( event ) => {
			const choice = event.target.getAttribute( 'data-qe-consent' );
			if ( ! choice ) {
				return;
			}
			write( choice );
			bar.hidden = true;
			if ( choice === 'yes' && root.qeEduLoadAnalytics ) {
				root.qeEduLoadAnalytics();
			}
		} );
		return bar;
	}

	root.qeEduConsent = { read, write, init };
	init( root.document );
} )( window );
