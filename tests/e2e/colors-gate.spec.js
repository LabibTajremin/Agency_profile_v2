/**
 * Phase 3 gate: switching each of the 12 accent swatches in Theme Settings
 * updates the front end (CSS variables) and Elementor's Global Colors.
 * Also checks the live preview updates before saving.
 *
 * Needs the "Elementor Gate" page (tests/e2e/fixtures/create-widgets-page.php)
 * so a page renders the Elementor Kit CSS.
 */
const { test, expect } = require( '@playwright/test' );
const { watchErrors } = require( './helpers' );

const SWATCHES = {
	'qe-crimson': '#A41E34',
	'royal-indigo': '#363D97',
	'electric-violet': '#6440FB',
	'midnight-navy': '#140342',
	'ocean-blue': '#005A94',
	emerald: '#0F875D',
	'teal-lagoon': '#0E7C86',
	'champagne-gold': '#B8913A',
	'sunset-coral': '#E2574C',
	'amber-saffron': '#E89B12',
	'rose-quartz': '#C2477A',
	graphite: '#2F3542',
};

const cssVar = ( page, name ) =>
	page.evaluate(
		( n ) =>
			window
				.getComputedStyle( document.body )
				.getPropertyValue( n )
				.trim()
				.toUpperCase(),
		name
	);

test( 'every swatch updates the front end and Elementor globals', async ( {
	page,
	browser,
} ) => {
	test.setTimeout( 600000 );
	const errors = watchErrors( page );
	const front = await browser.newPage();

	for ( const [ slug, hex ] of Object.entries( SWATCHES ) ) {
		await page.goto(
			'/wp-admin/admin.php?page=qe-edu-settings&tab=colors'
		);
		await page
			.locator( `input[data-qe-accent][value="${ slug }"]` )
			.check( {
				force: true,
			} );

		// Live preview before saving.
		const preview = page.frameLocator( '[data-qe-preview]' );
		await expect
			.poll( () =>
				preview
					.locator( 'body' )
					.evaluate( ( el ) =>
						el.style
							.getPropertyValue( '--qe-primary' )
							.toUpperCase()
					)
			)
			.toBe( hex );

		await Promise.all( [
			page.waitForURL( /qe_notice=saved/ ),
			page.click( '#submit' ),
		] );

		await front.goto( '/elementor-gate/' );
		expect( await cssVar( front, '--qe-primary' ), slug ).toBe( hex );
		expect( await cssVar( front, '--e-global-color-primary' ), slug ).toBe(
			hex
		);
	}

	// Back to the default.
	await page.goto( '/wp-admin/admin.php?page=qe-edu-settings&tab=colors' );
	await page
		.locator( 'input[data-qe-accent][value="qe-crimson"]' )
		.check( { force: true } );
	await Promise.all( [
		page.waitForURL( /qe_notice=saved/ ),
		page.click( '#submit' ),
	] );
	await front.close();
	expect( errors ).toEqual( [] );
} );
