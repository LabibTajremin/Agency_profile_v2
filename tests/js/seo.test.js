/**
 * Tests for plugin/qe-edu-core/assets/js/src/seo.js and consent.js.
 */

const SEO = '../../plugin/qe-edu-core/assets/js/src/seo.js';
const CONSENT = '../../plugin/qe-edu-core/assets/js/src/consent.js';

const I18N = {
	length: '%1$s characters (aim for %2$s)',
	keywordTitle: 'In title',
	keywordDesc: 'In description',
	keywordUrl: 'In address',
	keywordHeading: 'In heading',
	missing: 'Not yet',
	ok: 'Yes',
};

function box( heading = true ) {
	document.body.innerHTML = `
		${ heading ? '<input id="title" value="Study in Canada">' : '' }
		<div data-qe-seo>
			<div><span data-qe-seo-url>https://example.test/study-in-canada/</span>
			<span data-qe-seo-preview-title>Old</span>
			<span data-qe-seo-preview-desc>Excerpt</span></div>
			<input data-qe-seo-input="title" value="">
			<p data-qe-seo-meter="title"></p>
			<textarea data-qe-seo-input="description"></textarea>
			<p data-qe-seo-meter="description"></p>
			<input data-qe-seo-input="keyword" value="">
			<ul data-qe-seo-checks></ul>
		</div>`;
	return document.querySelector( '[data-qe-seo]' );
}

function load( file ) {
	jest.isolateModules( () => {
		require( file );
	} );
}

function type( el, text ) {
	el.value = text;
	el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
}

describe( 'seo.js', () => {
	beforeEach( () => {
		window.qeEduSeo = {
			site: 'QE',
			sep: '|',
			tagline: 'Study abroad',
			i18n: I18N,
		};
		box();
		load( SEO );
	} );

	afterEach( () => {
		delete window.qeEduSeo;
	} );

	it( 'helpers', () => {
		const api = window.qeEduSeoApi;
		expect( api.contains( 'Study In Canada', 'canada' ) ).toBe( true );
		expect( api.contains( 'Study', '' ) ).toBe( false );
		expect( api.contains( 'Study', 'mba' ) ).toBe( false );
		expect( api.slug( '  Study in Canada!! ' ) ).toBe( 'study-in-canada' );
		expect( api.meter( 'title', 'x'.repeat( 10 ) ) ).toEqual( {
			length: 10,
			status: 'short',
		} );
		expect( api.meter( 'title', 'x'.repeat( 45 ) ).status ).toBe( 'good' );
		expect( api.meter( 'title', 'x'.repeat( 61 ) ).status ).toBe( 'long' );
		expect( api.meter( 'description', 'x'.repeat( 160 ) ).status ).toBe(
			'good'
		);
	} );

	it( 'checks the focus keyword', () => {
		const state = {
			keyword: 'Canada',
			title: 'Study in Canada',
			description: 'Nothing here',
			url: 'https://example.test/study-in-canada/',
			heading: 'Study abroad',
		};
		expect( window.qeEduSeoApi.checks( state ) ).toEqual( [
			{ key: 'keywordTitle', ok: true },
			{ key: 'keywordDesc', ok: false },
			{ key: 'keywordUrl', ok: true },
			{ key: 'keywordHeading', ok: false },
		] );
		expect(
			window.qeEduSeoApi.checks( { ...state, keyword: 'বিদেশে' } )[ 2 ].ok
		).toBe( false );
	} );

	it( 'renders the preview, meters and checks on load and input', () => {
		const root = document.querySelector( '[data-qe-seo]' );
		const previewTitle = root.querySelector(
			'[data-qe-seo-preview-title]'
		);
		const previewDesc = root.querySelector( '[data-qe-seo-preview-desc]' );
		const titleMeter = root.querySelector( '[data-qe-seo-meter="title"]' );
		const descMeter = root.querySelector(
			'[data-qe-seo-meter="description"]'
		);
		const list = root.querySelector( '[data-qe-seo-checks]' );

		expect( previewTitle.textContent ).toBe( 'Study in Canada' );
		expect( previewDesc.textContent ).toBe( 'Excerpt' );
		expect( titleMeter.textContent ).toBe(
			'15 characters (aim for 30–60)'
		);
		expect( titleMeter.dataset.status ).toBe( 'short' );
		expect( descMeter.dataset.status ).toBe( 'short' );
		expect( list.children ).toHaveLength( 0 );

		type(
			root.querySelector( '[data-qe-seo-input="title"]' ),
			'{title} {sep} {site} {sep} {tagline}'
		);
		expect( previewTitle.textContent ).toBe(
			'Study in Canada | QE | Study abroad'
		);

		type(
			root.querySelector( '[data-qe-seo-input="description"]' ),
			'Learn about Canada'
		);
		expect( previewDesc.textContent ).toBe( 'Learn about Canada' );

		type( root.querySelector( '[data-qe-seo-input="keyword"]' ), 'canada' );
		expect( [ ...list.children ].map( ( li ) => li.className ) ).toEqual( [
			'is-missing',
			'is-ok',
			'is-ok',
			'is-ok',
		] );
		expect( list.children[ 0 ].textContent ).toBe( 'Not yet: In title' );
		expect( list.children[ 1 ].textContent ).toBe( 'Yes: In description' );

		type( document.querySelector( '#title' ), 'Study in Germany' );
		expect( list.children[ 3 ].className ).toBe( 'is-missing' );
		expect( previewTitle.textContent ).toBe(
			'Study in Germany | QE | Study abroad'
		);
	} );

	it( 'works without the classic title field', () => {
		box( false );
		const run = window.qeEduSeoApi.init();
		expect( typeof run ).toBe( 'function' );
		expect(
			document.querySelector( '[data-qe-seo-preview-title]' ).textContent
		).toBe( '' );
	} );

	it( 'reads and follows the block editor title', () => {
		let title = 'Block title';
		let listener = null;
		window.wp = {
			data: {
				select: () => ( {
					getEditedPostAttribute: ( key ) =>
						key === 'title' ? title : '',
				} ),
				subscribe: ( callback ) => {
					listener = callback;
				},
			},
		};
		box( false );
		window.qeEduSeoApi.init();
		const preview = document.querySelector( '[data-qe-seo-preview-title]' );
		expect( preview.textContent ).toBe( 'Block title' );

		preview.textContent = 'unchanged';
		listener();
		expect( preview.textContent ).toBe( 'unchanged' );

		title = 'Renamed';
		listener();
		expect( preview.textContent ).toBe( 'Renamed' );

		title = undefined;
		expect( window.qeEduSeoApi.pageTitle() ).toBe( '' );

		window.wp.data.select = () => null;
		expect( window.qeEduSeoApi.pageTitle() ).toBe( '' );
		delete window.wp;
	} );

	it( 'tolerates a box without the keyword field', () => {
		box();
		document.querySelector( '[data-qe-seo-input="keyword"]' ).remove();
		window.qeEduSeoApi.init();
		expect(
			document.querySelector( '[data-qe-seo-checks]' ).children
		).toHaveLength( 0 );
	} );

	it( 'does nothing without the box or the data', () => {
		document.body.innerHTML = '';
		expect( window.qeEduSeoApi.init() ).toBeNull();
		box();
		delete window.qeEduSeo;
		expect( window.qeEduSeoApi.init() ).toBeNull();
	} );

	it( 'waits for DOMContentLoaded while loading', () => {
		const state = jest
			.spyOn( document, 'readyState', 'get' )
			.mockReturnValue( 'loading' );
		box();
		load( SEO );
		const preview = document.querySelector( '[data-qe-seo-preview-title]' );
		expect( preview.textContent ).toBe( 'Old' );
		state.mockRestore();
		document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
		expect( preview.textContent ).toBe( 'Study in Canada' );
	} );
} );

describe( 'consent.js', () => {
	function bar() {
		document.body.innerHTML = `<div class="qe-consent" hidden><p>Cookies?</p>
			<button data-qe-consent="yes">Accept</button>
			<button data-qe-consent="no">Decline</button></div>`;
		return document.querySelector( '.qe-consent' );
	}

	beforeEach( () => {
		window.localStorage.clear();
		delete window.qeEduLoadAnalytics;
	} );

	it( 'shows the bar until a choice is made, accept loads analytics', () => {
		const el = bar();
		window.qeEduLoadAnalytics = jest.fn();
		load( CONSENT );
		expect( el.hidden ).toBe( false );

		el.querySelector( 'p' ).click();
		expect( el.hidden ).toBe( false );

		el.querySelector( '[data-qe-consent="yes"]' ).click();
		expect( el.hidden ).toBe( true );
		expect( window.localStorage.getItem( 'qe_consent' ) ).toBe( 'yes' );
		expect( window.qeEduLoadAnalytics ).toHaveBeenCalledTimes( 1 );

		const again = bar();
		load( CONSENT );
		expect( again.hidden ).toBe( true );
	} );

	it( 'decline stores the choice without loading', () => {
		const el = bar();
		window.qeEduLoadAnalytics = jest.fn();
		load( CONSENT );
		el.querySelector( '[data-qe-consent="no"]' ).click();
		expect( el.hidden ).toBe( true );
		expect( window.localStorage.getItem( 'qe_consent' ) ).toBe( 'no' );
		expect( window.qeEduLoadAnalytics ).not.toHaveBeenCalled();
	} );

	it( 'accept without a loader is harmless', () => {
		const el = bar();
		load( CONSENT );
		el.querySelector( '[data-qe-consent="yes"]' ).click();
		expect( el.hidden ).toBe( true );
	} );

	it( 'does nothing without the bar', () => {
		document.body.innerHTML = '';
		load( CONSENT );
		expect( window.qeEduConsent.init( document ) ).toBeNull();
	} );

	it( 'survives blocked storage', () => {
		const get = jest
			.spyOn( window.Storage.prototype, 'getItem' )
			.mockImplementation( () => {
				throw new Error( 'blocked' );
			} );
		const set = jest
			.spyOn( window.Storage.prototype, 'setItem' )
			.mockImplementation( () => {
				throw new Error( 'blocked' );
			} );
		const el = bar();
		load( CONSENT );
		expect( window.qeEduConsent.read() ).toBeNull();
		expect( el.hidden ).toBe( false );
		el.querySelector( '[data-qe-consent="no"]' ).click();
		expect( el.hidden ).toBe( true );
		get.mockRestore();
		set.mockRestore();
	} );
} );
