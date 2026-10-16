/**
 * Tests for plugin/qe-edu-core/assets/js/src/admin.js.
 */

const PHP_CUSTOM = {
	'#777777': {
		primary: '#757575',
		dark: '#5A5A5A',
		soft: 'rgb(117 117 117 / 8%)',
		on_primary: '#FFFFFF',
		on_dark: '#FFFFFF',
		link: '#757575',
		link_dark_mode: '#7B7B7B',
	},
	'#3366FF': {
		primary: '#3366FF',
		dark: '#274FC4',
		soft: 'rgb(51 102 255 / 8%)',
		on_primary: '#FFFFFF',
		on_dark: '#FFFFFF',
		link: '#3366FF',
		link_dark_mode: '#3F6FFF',
	},
	'#FFFF00': {
		primary: '#FFFF00',
		dark: '#C4C400',
		soft: 'rgb(255 255 0 / 8%)',
		on_primary: '#111111',
		on_dark: '#111111',
		link: '#7A7A00',
		link_dark_mode: '#FFFF00',
	},
	'#E89B12': {
		primary: '#E89B12',
		dark: '#B3770E',
		soft: 'rgb(232 155 18 / 8%)',
		on_primary: '#111111',
		on_dark: '#111111',
		link: '#9E690C',
		link_dark_mode: '#E89B12',
	},
};

const DATA = {
	palettes: {
		emerald: {
			primary: '#0F875D',
			dark: '#0A6345',
			soft: 's',
			on_primary: '#FFFFFF',
			on_dark: '#FFFFFF',
			link: '#0F875D',
			link_dark_mode: '#198C63',
		},
	},
	fonts: {
		'montserrat-open-sans': {
			heading: 'H1',
			body: 'B1',
			url: 'https://g/1',
		},
		'poppins-inter': { heading: 'H2', body: 'B2', url: 'https://g/2' },
	},
	defaultPair: 'montserrat-open-sans',
	scales: { compact: 0.88, normal: 1, large: 1.12 },
	minContrast: 4.5,
	lightSurface: '#FFFFFF',
	darkSurface: '#0E0B1F',
	i18n: {
		lowContrast: 'Low %s',
		okContrast: 'OK %s',
		choose: 'Choose',
		use: 'Use',
	},
};

function colorsForm( accent = 'emerald', extra = '' ) {
	return `
	<form data-qe-form>
		<div data-qe-field="colors.accent">
			<label><input type="radio" name="a" value="emerald" ${
				accent === 'emerald' ? 'checked' : ''
			}></label>
			<label><input type="radio" name="a" value="custom" ${
				accent === 'custom' ? 'checked' : ''
			}></label>
		</div>
		<div data-qe-field="colors.custom"><input type="color" value="#3366ff" data-qe-custom-color> <span data-qe-contrast></span></div>
		<div data-qe-field="colors.mode"><select><option value="light">L</option><option value="dark" selected>D</option></select></div>
		<div data-qe-field="colors.background"><select><option value="accent" selected>A</option></select></div>
		<div data-qe-field="colors.button_style"><select><option value="pill" selected>P</option></select></div>
		<div data-qe-field="colors.link_underline"><input type="checkbox"></div>
		<div data-qe-field="colors.hero_gradient"><input type="checkbox" checked></div>
		${ extra }
	</form>`;
}

const TYPO_FORM = ( pair = 'poppins-inter', selfHost = true ) => `
	<form data-qe-form>
		<div data-qe-field="typography.pair"><select><option value="montserrat-open-sans" ${
			pair === 'montserrat-open-sans' ? 'selected' : ''
		}>M</option><option value="poppins-inter" ${
			pair === 'poppins-inter' ? 'selected' : ''
		}>P</option></select></div>
		<div data-qe-field="typography.base_size"><input type="number" value="18"></div>
		<div data-qe-field="typography.heading_scale"><select><option value="large" selected>L</option></select></div>
		<div data-qe-field="typography.self_host"><input type="checkbox" ${
			selfHost ? 'checked' : ''
		}></div>
	</form>`;

function previewDoc() {
	return document.implementation.createHTMLDocument( 'preview' );
}

describe( 'admin.js', () => {
	let api;

	beforeAll( () => {
		window.qeEduAdmin = DATA;
		document.body.innerHTML = '';
		require( '../../plugin/qe-edu-core/assets/js/src/admin.js' );
		api = window.qeEduAdminApi;
	} );

	test( 'colour maths', () => {
		expect( api.normalize( 'abc' ) ).toBe( '#AABBCC' );
		expect( api.normalize( 'nope' ) ).toBe( '' );
		expect( api.normalize( undefined ) ).toBe( '' );
		expect( api.rgb( '#A41E34' ) ).toEqual( [ 164, 30, 52 ] );
		expect( api.contrast( '#000000', '#FFFFFF' ) ).toBeCloseTo( 21, 3 );
		expect( api.mix( '#000000', '#FFFFFF', 0.5 ) ).toBe( '#808080' );
		expect( api.rgba( '#000000', 0.5 ) ).toBe( 'rgb(0 0 0 / 50%)' );
		expect( api.textOn( '#000000', '#FFFFFF', 4.5 ) ).toBe( '#FFFFFF' );
		expect( api.textOn( '#FFFF00', '#FFFFFF', 4.5 ) ).toBe( '#111111' );
		expect( api.textOn( '#0000AA', '#111111', 4.5 ) ).toBe( '#FFFFFF' );
		expect( api.ensureContrast( '#000000', '#FFFFFF', 4.5 ) ).toBe(
			'#000000'
		);
		expect( api.readableCustom( '#36f', 4.5 ) ).toBe( '#3366FF' );
	} );

	test( 'custom palette matches PHP Palette::resolve exactly', () => {
		Object.keys( PHP_CUSTOM ).forEach( ( color ) => {
			expect( api.customPalette( color, DATA ) ).toEqual(
				PHP_CUSTOM[ color ]
			);
		} );
	} );

	test( 'preview state from the colours form', () => {
		document.body.innerHTML = colorsForm();
		const state = api.previewState(
			document.querySelector( 'form' ),
			DATA
		);
		expect( state.vars[ '--qe-primary' ] ).toBe( '#0F875D' );
		expect( state.vars[ '--qe-surface-alt' ] ).toBe(
			api.mix( '#0F875D', '#FFFFFF', 0.94 )
		);
		expect( state.vars[ '--qe-hero-gradient' ] ).toContain( '#0F875D 0%' );
		expect( state.classes ).toEqual( {
			mode: 'qe-mode-dark',
			button: 'qe-btn-pill',
			underline: true,
		} );
		expect( state.fontUrl ).toBeNull();
	} );

	test( 'preview state with the custom swatch and no gradient', () => {
		document.body.innerHTML = colorsForm( 'custom' );
		document.querySelector(
			'[data-qe-field="colors.hero_gradient"] input'
		).checked = false;
		const state = api.previewState(
			document.querySelector( 'form' ),
			DATA
		);
		expect( state.vars[ '--qe-primary' ] ).toBe( '#3366FF' );
		expect( state.vars[ '--qe-hero-gradient' ] ).toBe( 'none' );
	} );

	test( 'preview state from the typography form', () => {
		document.body.innerHTML = TYPO_FORM();
		let state = api.previewState( document.querySelector( 'form' ), DATA );
		expect( state.vars ).toEqual( {
			'--qe-font-heading': 'H2',
			'--qe-font-body': 'B2',
			'--qe-base-size': '1.125rem',
			'--qe-h-scale': '1.12',
		} );
		expect( state.fontUrl ).toBe( 'https://g/2' );

		document.body.innerHTML = TYPO_FORM( 'montserrat-open-sans', true );
		state = api.previewState( document.querySelector( 'form' ), DATA );
		expect( state.fontUrl ).toBeNull();

		document.body.innerHTML = TYPO_FORM( 'montserrat-open-sans', false );
		state = api.previewState( document.querySelector( 'form' ), DATA );
		expect( state.fontUrl ).toBe( 'https://g/1' );
	} );

	test( 'apply preview sets variables, classes and fonts', () => {
		const target = previewDoc();
		target.body.className = 'qe-mode-light qe-btn-rounded';
		api.applyPreview( target, {
			vars: { '--qe-primary': '#0F875D' },
			classes: {
				mode: 'qe-mode-dark',
				button: 'qe-btn-pill',
				underline: true,
			},
			fontUrl: 'https://g/2',
		} );
		expect(
			target.documentElement.style.getPropertyValue( '--qe-primary' )
		).toBe( '#0F875D' );
		expect( target.body.className ).toBe(
			'qe-mode-dark qe-btn-pill qe-no-link-underline'
		);
		expect( target.getElementById( 'qe-edu-preview-fonts' ).href ).toBe(
			'https://g/2'
		);

		// Second call reuses the font link; no classes when absent.
		api.applyPreview( target, {
			vars: {},
			classes: {},
			fontUrl: 'https://g/1',
		} );
		expect(
			target.querySelectorAll( '#qe-edu-preview-fonts' )
		).toHaveLength( 1 );
		expect( target.getElementById( 'qe-edu-preview-fonts' ).href ).toBe(
			'https://g/1'
		);

		api.applyPreview( target, { vars: {}, classes: {}, fontUrl: null } );
		expect( () => api.applyPreview( null, {} ) ).not.toThrow();
		expect( () => api.applyPreview( {}, {} ) ).not.toThrow();
	} );

	test( 'contrast message', () => {
		expect( api.contrastMessage( '#3366FF', DATA ) ).toEqual( {
			ok: true,
			text: 'OK 4.7',
		} );
		expect( api.contrastMessage( '#777777', DATA ).ok ).toBe( false );
		expect( api.contrastMessage( '#777777', DATA ).text ).toMatch(
			/^Low /
		);
	} );

	test( 'initPreview wires form, hint and iframe', () => {
		document.body.innerHTML =
			colorsForm( 'custom' ) + '<iframe data-qe-preview></iframe>';
		const form = document.querySelector( 'form' );
		const iframe = document.querySelector( 'iframe' );
		const update = api.initPreview( form, DATA );
		const hint = document.querySelector( '[data-qe-contrast]' );
		expect( hint.textContent ).toBe( 'OK 4.7' );

		const custom = document.querySelector( '[data-qe-custom-color]' );
		custom.value = '#777777';
		form.dispatchEvent( new window.Event( 'input' ) );
		expect( hint.classList.contains( 'is-low' ) ).toBe( true );
		expect(
			iframe.contentDocument.documentElement.style.getPropertyValue(
				'--qe-primary'
			)
		).toBe( '#757575' );

		iframe.dispatchEvent( new window.Event( 'load' ) );
		expect( typeof update ).toBe( 'function' );
	} );

	test( 'initPreview without iframe or custom colour field', () => {
		document.body.innerHTML = TYPO_FORM();
		const update = api.initPreview(
			document.querySelector( 'form' ),
			DATA
		);
		expect( () => update() ).not.toThrow();
	} );

	function mediaMarkup( mime ) {
		return `<div data-qe-media data-mime="${ mime }">
			<input type="hidden" value="0" data-qe-media-id>
			<div data-qe-media-preview></div>
			<button type="button" data-qe-media-choose>Choose</button>
			<button type="button" data-qe-media-remove hidden>Remove</button>
		</div>`;
	}

	function fakeWp( file ) {
		const handlers = {};
		const frame = {
			on: jest.fn( ( event, cb ) => ( handlers[ event ] = cb ) ),
			open: jest.fn( () => handlers.select() ),
			state: () => ( {
				get: () => ( { first: () => ( { toJSON: () => file } ) } ),
			} ),
		};
		return { media: jest.fn( () => frame ), frame };
	}

	test( 'media picker for images', () => {
		document.body.innerHTML = mediaMarkup( 'image' );
		const wp = fakeWp( {
			id: 9,
			url: 'https://x/logo.png',
			filename: 'logo.png',
		} );
		const el = document.querySelector( '[data-qe-media]' );
		api.initMedia( el, wp, DATA );
		el.querySelector( '[data-qe-media-choose]' ).click();
		el.querySelector( '[data-qe-media-choose]' ).click();

		expect( wp.media ).toHaveBeenCalledTimes( 1 );
		expect( wp.media ).toHaveBeenCalledWith( {
			title: 'Choose',
			button: { text: 'Use' },
			library: { type: 'image' },
			multiple: false,
		} );
		expect( el.querySelector( '[data-qe-media-id]' ).value ).toBe( '9' );
		expect( el.querySelector( 'img' ).src ).toBe( 'https://x/logo.png' );
		expect( el.querySelector( '[data-qe-media-remove]' ).hidden ).toBe(
			false
		);

		el.querySelector( '[data-qe-media-remove]' ).click();
		expect( el.querySelector( '[data-qe-media-id]' ).value ).toBe( '0' );
		expect( el.querySelector( 'img' ) ).toBeNull();
		expect( el.querySelector( '[data-qe-media-remove]' ).hidden ).toBe(
			true
		);
	} );

	test( 'media picker for PDFs', () => {
		document.body.innerHTML = mediaMarkup( 'application/pdf' );
		const wp = fakeWp( {
			id: 3,
			url: 'https://x/p.pdf',
			filename: 'p.pdf',
		} );
		const el = document.querySelector( '[data-qe-media]' );
		api.initMedia( el, wp, DATA );
		el.querySelector( '[data-qe-media-choose]' ).click();
		expect( el.querySelector( 'a' ).textContent ).toBe( 'p.pdf' );
	} );

	test( 'redirect rows', () => {
		document.body.innerHTML = `<div><table data-qe-redirects><tbody>
			<tr><td><input name="r[0][from]" value="/a"></td><td><input name="r[0][to]" value="/b"></td></tr>
		</tbody></table><button type="button" data-qe-add-row>Add</button></div>`;
		const table = document.querySelector( 'table' );
		api.initRedirects( table );
		document.querySelector( '[data-qe-add-row]' ).click();
		const inputs = table.querySelectorAll( 'tbody tr:last-child input' );
		expect( inputs[ 0 ].name ).toBe( 'r[1][from]' );
		expect( inputs[ 0 ].value ).toBe( '' );
		expect( document.activeElement ).toBe( inputs[ 0 ] );

		document.body.innerHTML =
			'<div><table data-qe-redirects></table></div>';
		expect( () =>
			api.initRedirects( document.querySelector( 'table' ) )
		).not.toThrow();
	} );

	test( 'init wires everything or nothing', () => {
		delete window.qeEduAdmin;
		expect( api.init() ).toBeNull();

		window.qeEduAdmin = DATA;
		document.body.innerHTML = '<p>No form</p>';
		expect( api.init() ).toEqual( { update: null } );

		document.body.innerHTML =
			colorsForm() +
			mediaMarkup( 'image' ) +
			'<div><table data-qe-redirects><tbody><tr><td><input name="r[0][from]"></td></tr></tbody></table><button type="button" data-qe-add-row>Add</button></div>';
		window.wp = fakeWp( { id: 1, url: 'u', filename: 'f' } );
		const result = api.init();
		expect( typeof result.update ).toBe( 'function' );
		document.querySelector( '[data-qe-media-choose]' ).click();
		expect( window.wp.media ).toHaveBeenCalled();
		delete window.wp;
	} );

	test( 'waits for DOMContentLoaded while loading', () => {
		jest.isolateModules( () => {
			const spy = jest
				.spyOn( document, 'readyState', 'get' )
				.mockReturnValue( 'loading' );
			const add = jest.spyOn( document, 'addEventListener' );
			require( '../../plugin/qe-edu-core/assets/js/src/admin.js' );
			expect( add ).toHaveBeenCalledWith(
				'DOMContentLoaded',
				expect.any( Function )
			);
			spy.mockRestore();
			add.mockRestore();
		} );
	} );
} );
