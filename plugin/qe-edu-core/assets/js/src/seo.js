/**
 * SEO box: live Google snippet preview, length meters and simple focus
 * keyword checks.
 *
 * @param {Window} root Browser window.
 */
( function ( root ) {
	const doc = root.document;
	const RANGES = { title: [ 30, 60 ], description: [ 120, 160 ] };

	function value( box, key ) {
		const input = box.querySelector( `[data-qe-seo-input="${ key }"]` );
		return input ? input.value.trim() : '';
	}

	function blockEditor() {
		const data = root.wp && root.wp.data;
		return data ? data.select( 'core/editor' ) : null;
	}

	/**
	 * The page title from the classic editor field or the block editor.
	 *
	 * @return {string} Title.
	 */
	function pageTitle() {
		const input = doc.querySelector( '#title' );
		if ( input ) {
			return input.value.trim();
		}
		const editor = blockEditor();
		return editor
			? String( editor.getEditedPostAttribute( 'title' ) || '' ).trim()
			: '';
	}

	function contains( haystack, needle ) {
		return (
			needle !== '' &&
			haystack.toLowerCase().indexOf( needle.toLowerCase() ) !== -1
		);
	}

	function slug( text ) {
		return text
			.toLowerCase()
			.trim()
			.replace( /[^a-z0-9]+/g, '-' )
			.replace( /^-+|-+$/g, '' );
	}

	/**
	 * Length status for a field.
	 *
	 * @param {string} key  title|description.
	 * @param {string} text Text.
	 * @return {Object} { length, status } — status is short|good|long.
	 */
	function meter( key, text ) {
		const [ min, max ] = RANGES[ key ];
		const length = text.length;
		let status = 'good';
		if ( length < min ) {
			status = 'short';
		} else if ( length > max ) {
			status = 'long';
		}
		return { length, status };
	}

	/**
	 * Keyword checks.
	 *
	 * @param {Object} state { keyword, title, description, url, heading }.
	 * @return {Object[]} [{ key, ok }].
	 */
	function checks( state ) {
		const keyword = state.keyword;
		const keywordSlug = slug( keyword );
		return [
			{ key: 'keywordTitle', ok: contains( state.title, keyword ) },
			{ key: 'keywordDesc', ok: contains( state.description, keyword ) },
			{
				key: 'keywordUrl',
				ok:
					keywordSlug !== '' &&
					state.url.indexOf( keywordSlug ) !== -1,
			},
			{ key: 'keywordHeading', ok: contains( state.heading, keyword ) },
		];
	}

	function update( box, data ) {
		const i18n = data.i18n;
		const title = value( box, 'title' ) || pageTitle();
		const description = value( box, 'description' );
		const url = box.querySelector( '[data-qe-seo-url]' ).textContent;

		box.querySelector( '[data-qe-seo-preview-title]' ).textContent = title
			.split( '{title}' )
			.join( pageTitle() )
			.split( '{site}' )
			.join( data.site )
			.split( '{sep}' )
			.join( data.sep )
			.split( '{tagline}' )
			.join( data.tagline );
		if ( description ) {
			box.querySelector( '[data-qe-seo-preview-desc]' ).textContent =
				description;
		}

		[ 'title', 'description' ].forEach( ( key ) => {
			const el = box.querySelector( `[data-qe-seo-meter="${ key }"]` );
			const result = meter( key, key === 'title' ? title : description );
			el.textContent = i18n.length
				.replace( '%1$s', String( result.length ) )
				.replace( '%2$s', RANGES[ key ].join( '–' ) );
			el.dataset.status = result.status;
		} );

		const list = box.querySelector( '[data-qe-seo-checks]' );
		const keyword = value( box, 'keyword' );
		list.textContent = '';
		if ( ! keyword ) {
			return;
		}
		checks( {
			keyword,
			title,
			description,
			url,
			heading: pageTitle(),
		} ).forEach( ( check ) => {
			const li = doc.createElement( 'li' );
			li.className = check.ok ? 'is-ok' : 'is-missing';
			li.textContent = `${ check.ok ? i18n.ok : i18n.missing }: ${
				i18n[ check.key ]
			}`;
			list.appendChild( li );
		} );
	}

	function init() {
		const box = doc.querySelector( '[data-qe-seo]' );
		const data = root.qeEduSeo;
		if ( ! box || ! data ) {
			return null;
		}
		const run = () => update( box, data );
		box.addEventListener( 'input', run );
		const heading = doc.querySelector( '#title' );
		if ( heading ) {
			heading.addEventListener( 'input', run );
		} else if ( blockEditor() ) {
			let last = pageTitle();
			root.wp.data.subscribe( () => {
				const current = pageTitle();
				if ( current !== last ) {
					last = current;
					run();
				}
			} );
		}
		run();
		return run;
	}

	root.qeEduSeoApi = {
		contains,
		slug,
		meter,
		checks,
		pageTitle,
		update,
		init,
	};

	if ( doc.readyState === 'loading' ) {
		doc.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )( window );
