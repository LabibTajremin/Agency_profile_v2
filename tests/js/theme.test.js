/**
 * Tests for theme/qe-edu/assets/js/src/theme.js.
 */
require( '../../theme/qe-edu/assets/js/src/theme.js' );
const api = window.qeEduThemeApi;

function markup( withLabel = true ) {
	document.body.innerHTML = `
		<header data-qe-header>
			<button data-qe-menu-toggle aria-expanded="false">${
				withLabel ? '<span data-qe-menu-label>Open</span>' : ''
			}</button>
			<div data-qe-menu-panel>
				<ul>
					<li class="menu-item-has-children"><a href="#a">A</a><button class="qe-submenu-toggle" aria-expanded="false"></button></li>
					<li><a href="#b">B</a></li>
				</ul>
			</div>
		</header>
		<a href="#page" class="qe-back-to-top" hidden></a>`;
}

function key( name, shiftKey = false ) {
	const event = new window.KeyboardEvent( 'keydown', {
		key: name,
		shiftKey,
		bubbles: true,
		cancelable: true,
	} );
	document.dispatchEvent( event );
	return event;
}

describe( 'theme.js', () => {
	const win = {
		qeEduTheme: { menuOpen: 'Open menu', menuClose: 'Close menu' },
		scrollY: 0,
		addEventListener: jest.fn(),
	};

	beforeEach( () => {
		document.body.className = '';
		win.addEventListener.mockClear();
	} );

	test( 'ready runs immediately or on DOMContentLoaded', () => {
		const cb = jest.fn();
		api.ready( { readyState: 'complete' }, cb );
		expect( cb ).toHaveBeenCalledTimes( 1 );

		const doc = { readyState: 'loading', addEventListener: jest.fn() };
		api.ready( doc, cb );
		expect( doc.addEventListener ).toHaveBeenCalledWith(
			'DOMContentLoaded',
			cb
		);
	} );

	test( 'labels fall back to empty strings', () => {
		expect( api.labels( undefined ) ).toEqual( { open: '', close: '' } );
		expect( api.labels( {} ) ).toEqual( { open: '', close: '' } );
		expect( api.labels( win ) ).toEqual( {
			open: 'Open menu',
			close: 'Close menu',
		} );
	} );

	test( 'init returns nulls when markup is absent', () => {
		document.body.innerHTML = '';
		expect( api.init( document, win ) ).toEqual( {
			menu: null,
			backToTop: null,
		} );
	} );

	test( 'menu opens, closes, updates labels and locks scroll', () => {
		markup();
		const { menu } = api.init( document, win );
		const toggle = document.querySelector( '[data-qe-menu-toggle]' );
		const label = document.querySelector( '[data-qe-menu-label]' );

		toggle.click();
		expect( menu.isOpen() ).toBe( true );
		expect( toggle.getAttribute( 'aria-expanded' ) ).toBe( 'true' );
		expect( label.textContent ).toBe( 'Close menu' );
		expect( document.body.classList.contains( 'qe-menu-locked' ) ).toBe(
			true
		);
		expect( document.activeElement.getAttribute( 'href' ) ).toBe( '#a' );

		toggle.click();
		expect( menu.isOpen() ).toBe( false );
		expect( label.textContent ).toBe( 'Open menu' );
		expect( document.activeElement ).toBe( toggle );
	} );

	test( 'menu works without label or translations and with an empty panel', () => {
		markup( false );
		document.querySelector( '[data-qe-menu-panel]' ).innerHTML = '';
		const menu = api.initMenu( document, {} );
		menu.setOpen( true );
		expect( menu.isOpen() ).toBe( true );
	} );

	test( 'Escape closes, Tab is trapped, other keys ignored', () => {
		markup();
		const { menu } = api.init( document, win );
		const toggle = document.querySelector( '[data-qe-menu-toggle]' );
		const links = document.querySelectorAll(
			'[data-qe-menu-panel] a, [data-qe-menu-panel] button'
		);
		const last = links[ links.length - 1 ];

		// Closed: keys do nothing.
		expect( key( 'Escape' ).defaultPrevented ).toBe( false );

		menu.setOpen( true );
		expect( key( 'Enter' ).defaultPrevented ).toBe( false );

		toggle.focus();
		expect( key( 'Tab', true ).defaultPrevented ).toBe( true );
		expect( document.activeElement ).toBe( last );

		expect( key( 'Tab' ).defaultPrevented ).toBe( true );
		expect( document.activeElement ).toBe( toggle );

		links[ 0 ].focus();
		expect( key( 'Tab' ).defaultPrevented ).toBe( false );
		expect( key( 'Tab', true ).defaultPrevented ).toBe( false );

		key( 'Escape' );
		expect( menu.isOpen() ).toBe( false );
	} );

	test( 'submenu toggles open and closed', () => {
		markup();
		api.init( document, win );
		const button = document.querySelector( '.qe-submenu-toggle' );
		const item = button.parentElement;

		button.click();
		expect( item.classList.contains( 'is-open' ) ).toBe( true );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'true' );

		button.click();
		expect( item.classList.contains( 'is-open' ) ).toBe( false );
		expect( button.getAttribute( 'aria-expanded' ) ).toBe( 'false' );

		// Clicks elsewhere in the panel are ignored.
		document.querySelector( '[data-qe-menu-panel] a' ).click();
		expect( item.classList.contains( 'is-open' ) ).toBe( false );
	} );

	test( 'back to top shows after scrolling', () => {
		markup();
		const { backToTop } = api.init( document, win );
		const button = document.querySelector( '.qe-back-to-top' );
		expect( button.hidden ).toBe( false );
		expect( win.addEventListener ).toHaveBeenCalledWith(
			'scroll',
			backToTop.update,
			{ passive: true }
		);
		expect( button.classList.contains( 'is-visible' ) ).toBe( false );

		win.scrollY = 900;
		backToTop.update();
		expect( button.classList.contains( 'is-visible' ) ).toBe( true );
		win.scrollY = 0;
	} );

	test( 'loading the script exposes the api on window', () => {
		expect( typeof api.init ).toBe( 'function' );
	} );
} );
