/**
 * Phase 7 accessibility gate: axe-core finds no serious or critical
 * WCAG 2.2 A/AA problems on any demo page (desktop and phone), on a post, a
 * 404, the opened mobile menu, and on the Theme Settings screen.
 *
 * Needs the demo imported (see demo-gate.spec.js); same DEMO_BASE_URL.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );
const AxeBuilder = require( '@axe-core/playwright' ).default;

const BASE = process.env.DEMO_BASE_URL || 'http://localhost:8889';
const manifest = JSON.parse(
	fs.readFileSync(
		path.resolve(
			__dirname,
			'../../plugin/qe-edu-core/demo/manifest.json'
		),
		'utf8'
	)
);
const TAGS = [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa' ];

/**
 * Serious and critical violations, summarised.
 *
 * @param {import('@playwright/test').Page} page    Page.
 * @param {string[]}                        include Optional selectors to limit the scan.
 * @return {Promise<string[]>} Problems.
 */
async function problems( page, include = [] ) {
	let builder = new AxeBuilder( { page } ).withTags( TAGS );
	for ( const selector of include ) {
		builder = builder.include( selector );
	}
	const { violations } = await builder.analyze();
	return violations
		.filter( ( v ) => [ 'serious', 'critical' ].includes( v.impact ) )
		.map(
			( v ) =>
				`${ v.id }: ${ v.nodes
					.slice( 0, 3 )
					.map( ( n ) => n.target.join( ' ' ) )
					.join( ' | ' ) }`
		);
}

const urls = [
	...manifest.pages.map( ( p ) =>
		p.key === 'home' ? '/' : `/${ p.slug }/`
	),
	'/10-scholarships-international-students-often-miss/',
	'/events/',
	'/?s=study',
	'/no-such-page-qe/',
];

test.describe( 'Accessibility gate (axe)', () => {
	test.use( { storageState: { cookies: [], origins: [] } } );

	for ( const [ label, viewport ] of [
		[ 'desktop', { width: 1440, height: 900 } ],
		[ 'phone', { width: 390, height: 844 } ],
	] ) {
		test( `no serious issues on any page (${ label })`, async ( {
			browser,
		} ) => {
			test.setTimeout( 600000 );
			const context = await browser.newContext( {
				viewport,
				reducedMotion: 'reduce',
			} );
			const page = await context.newPage();
			const found = [];
			for ( const url of urls ) {
				await page.goto( BASE + url );
				await page.waitForLoadState( 'load' );
				for ( const p of await problems( page ) ) {
					found.push( `${ url }  ${ p }` );
				}
			}
			expect( found ).toEqual( [] );
			await context.close();
		} );
	}

	test( 'mobile menu open', async ( { browser } ) => {
		const context = await browser.newContext( {
			viewport: { width: 390, height: 844 },
		} );
		const page = await context.newPage();
		await page.goto( BASE + '/' );
		await page.locator( '.qe-mega__toggle' ).click();
		await page.locator( '.qe-mega__sub-toggle' ).first().click();
		expect( await problems( page, [ '.qe-mega' ] ) ).toEqual( [] );
		await context.close();
	} );

	test( 'Theme Settings screen', async ( { page } ) => {
		await page.goto( BASE + '/wp-login.php' );
		await page.fill( '#user_login', process.env.WP_USER || 'admin' );
		await page.fill( '#user_pass', process.env.WP_PASS || 'password' );
		await Promise.all( [
			page.waitForURL( /wp-admin/ ),
			page.click( '#wp-submit' ),
		] );
		for ( const tab of [ 'brand', 'colors', 'seo', 'texts', 'tools' ] ) {
			await page.goto(
				`${ BASE }/wp-admin/admin.php?page=qe-edu-settings&tab=${ tab }`
			);
			expect( await problems( page, [ '.qe-settings' ] ), tab ).toEqual(
				[]
			);
		}
	} );
} );
