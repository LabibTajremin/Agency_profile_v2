/**
 * Phase 2 gate (WP_THEME_BUILD_INSTRUCTIONS.md §6.5): open the "Elementor
 * Gate" page (every core Elementor widget) in the Elementor editor and on the
 * front end, then assert:
 *  - no console errors;
 *  - no horizontal overflow at 1440 / 1024 / 390;
 *  - every top-level container is taller than 40px;
 *  - editor preview vs front end differ by < 1.5% per section.
 *
 * Create the page first: wp eval-file tests/e2e/fixtures/create-widgets-page.php
 */
const { test, expect } = require( '@playwright/test' );
const { watchErrors, diffPercent } = require( './helpers' );

const crypto = require( 'crypto' );

const SLUG = process.env.QE_GATE_SLUG || 'elementor-gate';

// Container ids are md5( 'c' + widgetType ) (see the fixture).
const containerId = ( type ) =>
	crypto
		.createHash( 'md5' )
		.update( 'c' + type )
		.digest( 'hex' )
		.slice( 0, 7 );

// Excluded from the pixel comparison only (still checked for errors,
// overflow and collapse), because Elementor itself renders them differently
// inside the editor on purpose:
//  - WordPress widgets, menu anchor, sidebar, read-more, text-path: editor
//    placeholders / helper backgrounds;
//  - toggle: Elementor opens the first item in the editor;
//  - floating widgets: fixed-position overlays, not part of their section.
const PARITY_EXEMPT = new Set(
	[
		'wp-widget-pages',
		'wp-widget-calendar',
		'wp-widget-archives',
		'wp-widget-media_audio',
		'wp-widget-media_image',
		'wp-widget-media_gallery',
		'wp-widget-media_video',
		'wp-widget-meta',
		'wp-widget-search',
		'wp-widget-text',
		'wp-widget-categories',
		'wp-widget-recent-posts',
		'wp-widget-recent-comments',
		'wp-widget-rss',
		'wp-widget-tag_cloud',
		'wp-widget-nav_menu',
		'wp-widget-custom_html',
		'wp-widget-block',
		'menu-anchor',
		'sidebar',
		'read-more',
		'text-path',
		'toggle',
		'contact-buttons',
		'floating-bars-var-1',
	].map( containerId )
);

// Fixed overlays hidden while taking section screenshots.
const HIDE_OVERLAYS =
	'.qe-back-to-top,.e-contact-buttons,.e-floating-bars,[class*="e-contact-buttons"],[class*="e-floating-bars"]{visibility:hidden!important}';
const WIDTHS = [ 1440, 1024, 390 ];

// Third-party embeds used by core widgets (Video, Audio, Maps) and remote
// wordpress.org calls: their errors are outside the theme's control.
const IGNORE = [
	/favicon\.ico/,
	/google-analytics|googletagmanager/,
	/wp\.org|wordpress\.org|api\.w\.org/,
	/youtube|ytimg|vimeo|maps\.google|google\.com\/maps|gstatic/,
	/soundcloud|sndcdn/,
];
const relevant = ( errors ) =>
	errors.filter( ( e ) => ! IGNORE.some( ( re ) => re.test( e ) ) );

// Third-party embeds (Maps, YouTube) keep the network busy, so wait for the
// load event plus a short settle instead of "networkidle".
async function settle( page ) {
	await page.waitForLoadState( 'load' );
	await page.waitForTimeout( 1500 );
}

async function pageId( page ) {
	await page.goto( '/' + SLUG + '/' );
	return page.evaluate( () => {
		const m = document.body.className.match( /page-id-(\d+)/ );
		return m ? m[ 1 ] : null;
	} );
}

async function openEditor( page, id ) {
	await page.goto( `/wp-admin/post.php?post=${ id }&action=elementor` );
	await page.waitForFunction(
		() =>
			window.elementor &&
			window.elementor.loaded !== false &&
			document.querySelector( '#elementor-preview-iframe' ),
		null,
		{ timeout: 120000 }
	);
	const frame = page.frameLocator( '#elementor-preview-iframe' );
	await frame
		.locator( '.elementor-edit-area .e-con' )
		.first()
		.waitFor( { timeout: 120000 } );
	// Let widgets finish their editor render.
	await page.waitForTimeout( 2500 );
	return page.frame( { url: /elementor-preview/ } );
}

async function containerMetrics( frame ) {
	return frame.evaluate( () => {
		const root = document.querySelector(
			'.elementor-edit-area, [data-elementor-type="wp-page"]'
		);
		return Array.from(
			root.querySelectorAll(
				':scope > .e-con, :scope > .elementor-section-wrap > .e-con, :scope > div > .e-con'
			)
		).map( ( el ) => ( {
			id: el.getAttribute( 'data-id' ),
			height: el.getBoundingClientRect().height,
		} ) );
	} );
}

test.describe( 'Elementor gate', () => {
	test( 'front end: no errors, no overflow, nothing collapsed', async ( {
		page,
	} ) => {
		const errors = watchErrors( page );
		for ( const width of WIDTHS ) {
			await page.setViewportSize( { width, height: 900 } );
			await page.goto( '/' + SLUG + '/' );
			await settle( page );
			const overflow = await page.evaluate(
				() => document.documentElement.scrollWidth - window.innerWidth
			);
			expect(
				overflow,
				`horizontal overflow at ${ width }`
			).toBeLessThanOrEqual( 0 );
			const metrics = await containerMetrics( page.mainFrame() );
			expect( metrics.length ).toBeGreaterThan( 30 );
			for ( const m of metrics ) {
				expect(
					m.height,
					`container ${ m.id } at ${ width }`
				).toBeGreaterThan( 40 );
			}
		}
		expect( relevant( errors ) ).toEqual( [] );
	} );

	test( 'editor: no errors, no overflow, nothing collapsed, matches front end', async ( {
		page,
		browser,
	} ) => {
		const id = await pageId( page );
		expect( id ).not.toBeNull();
		const errors = watchErrors( page );

		await page.setViewportSize( { width: 1740, height: 1000 } );
		const frame = await openEditor( page, id );

		for ( const width of WIDTHS ) {
			// Resize the preview iframe to the target width, like the responsive mode.
			await page.evaluate( ( w ) => {
				const iframe = document.querySelector(
					'#elementor-preview-iframe'
				);
				iframe.style.width = w + 'px';
				iframe.style.maxWidth = 'none';
			}, width );
			await page.waitForTimeout( 800 );
			const overflow = await frame.evaluate(
				() => document.documentElement.scrollWidth - window.innerWidth
			);
			expect(
				overflow,
				`editor overflow at ${ width }`
			).toBeLessThanOrEqual( 0 );
			const metrics = await containerMetrics( frame );
			expect( metrics.length ).toBeGreaterThan( 30 );
			for ( const m of metrics ) {
				expect(
					m.height,
					`editor container ${ m.id } at ${ width }`
				).toBeGreaterThan( 40 );
			}
		}

		// Visual parity at desktop width: compare every section.
		const width = 1240;
		await page.evaluate( ( w ) => {
			const iframe = document.querySelector(
				'#elementor-preview-iframe'
			);
			iframe.style.width = w + 'px';
		}, width );
		await page.waitForTimeout( 800 );
		await page.addStyleTag( {
			content:
				'#elementor-navigator,.elementor-navigator{display:none!important}',
		} );
		await frame.evaluate( ( HIDE ) => {
			const style = document.createElement( 'style' );
			// Hide editor-only chrome (handles, add-section area, empty placeholders).
			style.textContent =
				HIDE +
				'.elementor-editor-element-settings,.elementor-add-section,#elementor-add-new-section,.elementor-element-overlay,.e-con > .elementor-empty-view{display:none!important}*{transition:none!important;animation:none!important;caret-color:transparent!important}';
			document.head.appendChild( style );
		}, HIDE_OVERLAYS );

		const front = await browser.newPage( {
			viewport: { width, height: 1000 },
		} );
		await front.goto( '/' + SLUG + '/' );
		await settle( front );
		await front.addStyleTag( {
			content:
				HIDE_OVERLAYS +
				'*{transition:none!important;animation:none!important;caret-color:transparent!important}',
		} );

		const ids = ( await containerMetrics( frame ) )
			.map( ( m ) => m.id )
			.filter( ( cid ) => ! PARITY_EXEMPT.has( cid ) );
		expect( ids.length ).toBeGreaterThan( 30 );
		const failures = [];
		for ( const cid of ids ) {
			const sel = `.e-con[data-id="${ cid }"]`;
			// Third-party iframes (YouTube, Maps) load remote frames at random
			// times: mask them so the comparison measures layout only.
			const editorShot = await frame
				.locator( sel )
				.screenshot( { mask: [ frame.locator( `${ sel } iframe` ) ] } );
			const frontShot = await front
				.locator( sel )
				.screenshot( { mask: [ front.locator( `${ sel } iframe` ) ] } );
			const pct = diffPercent( editorShot, frontShot );
			if ( process.env.QE_GATE_DEBUG && pct >= 1.5 ) {
				require( 'fs' ).writeFileSync(
					`test-results/${ cid }-editor.png`,
					editorShot
				);
				require( 'fs' ).writeFileSync(
					`test-results/${ cid }-front.png`,
					frontShot
				);
			}
			if ( pct >= 1.5 ) {
				failures.push( `${ cid }: ${ pct.toFixed( 2 ) }%` );
			}
		}
		await front.close();
		expect(
			failures,
			'sections differing between editor and front end'
		).toEqual( [] );
		expect( relevant( errors ) ).toEqual( [] );
	} );
} );
