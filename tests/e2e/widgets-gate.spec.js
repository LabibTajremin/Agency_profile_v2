/**
 * Phase 4 gate: every QE Education widget renders in the Elementor editor and
 * on the front end without console errors or horizontal overflow, forms
 * submit for real and create leads, sliders start and the mobile mega menu
 * works with mouse and keyboard.
 *
 * Setup:
 *   wp eval-file tests/e2e/fixtures/create-qe-widgets-page.php
 *   cp tests/e2e/fixtures/mu-plugin-e2e.php wp-content/mu-plugins/
 */
const { test, expect } = require( '@playwright/test' );
const { watchErrors } = require( './helpers' );

const SLUG = '/qe-widgets/';
const WIDGETS = 20;
const relevant = ( errors ) =>
	errors.filter(
		( e ) =>
			! /favicon|youtube|ytimg|gstatic|google|soundcloud|sndcdn/.test( e )
	);

async function settle( page ) {
	await page.waitForLoadState( 'load' );
	await page.waitForTimeout( 1200 );
}

test.describe( 'QE widgets gate', () => {
	test( 'front end at 1440 / 1024 / 390: no errors, no overflow, sliders ready', async ( {
		browser,
	} ) => {
		for ( const width of [ 1440, 1024, 390 ] ) {
			const page = await browser.newPage( {
				viewport: { width, height: 900 },
			} );
			const errors = watchErrors( page );
			await page.goto( SLUG );
			await settle( page );
			const overflow = await page.evaluate(
				() => document.documentElement.scrollWidth - window.innerWidth
			);
			expect( overflow, `overflow at ${ width }` ).toBeLessThanOrEqual(
				0
			);
			await expect(
				page.locator( '.qe-slider.is-ready' ).first()
			).toBeVisible();
			await expect( page.locator( '.qe-hero__title' ) ).toBeVisible();
			expect( relevant( errors ) ).toEqual( [] );
			await page.close();
		}
	} );

	test( 'editor: every widget renders without errors', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( SLUG );
		const id = await page.evaluate(
			() => document.body.className.match( /page-id-(\d+)/ )[ 1 ]
		);
		await page.setViewportSize( { width: 1600, height: 1000 } );
		await page.goto( `/wp-admin/post.php?post=${ id }&action=elementor` );
		const frame = page.frameLocator( '#elementor-preview-iframe' );
		await frame
			.locator( '.elementor-widget-qe-animated-hero' )
			.waitFor( { timeout: 120000 } );
		await page.waitForTimeout( 2000 );

		const heights = await page
			.frame( { url: /elementor-preview/ } )
			.evaluate( () =>
				Array.from(
					document.querySelectorAll(
						'[class*="elementor-widget-qe-"]'
					)
				).map( ( el ) => [
					el.getAttribute( 'data-widget_type' ),
					el.getBoundingClientRect().height,
				] )
			);
		expect( heights ).toHaveLength( WIDGETS );
		for ( const [ type, height ] of heights ) {
			expect(
				height,
				`${ type } has content in the editor`
			).toBeGreaterThan( 20 );
		}
		expect( relevant( errors ) ).toEqual( [] );
	} );

	test( 'registration form: two steps, validation, success, lead stored', async ( {
		page,
		browser,
	} ) => {
		const visitor = await browser.newPage( {
			storageState: { cookies: [], origins: [] },
		} );
		const errors = watchErrors( visitor );
		await visitor.goto( SLUG );
		await settle( visitor );
		const form = visitor
			.locator( '.qe-form-wrap form[data-qe-form="register"]' )
			.last();
		const email = `student${ Date.now() }@example.test`;

		// Step 1 validation.
		await form.locator( '[data-qe-next]' ).click();
		await expect( form.locator( '.qe-form__error' ).first() ).toBeVisible();

		await form.locator( '[name="qe_first_name"]' ).fill( 'Ada' );
		await form.locator( '[name="qe_last_name"]' ).fill( 'Lovelace' );
		await form.locator( '[name="qe_email"]' ).fill( email );
		await form.locator( '[name="qe_phone"]' ).fill( '+971 50 123 4567' );
		await form.locator( '[data-qe-next]' ).click();

		await expect( form.locator( '[data-step="2"]' ) ).toBeVisible();
		await form.locator( '[name="qe_dob"]' ).fill( '2004-05-01' );
		await form.locator( '[name="qe_country"]' ).selectOption( 'Ireland' );
		await form.locator( '[name="qe_consent"]' ).check();
		await form.locator( '[data-qe-submit]' ).click();

		await expect(
			form.locator( '.qe-form__message.is-success' )
		).toContainText( 'Thank you' );
		expect( relevant( errors ) ).toEqual( [] );
		await visitor.close();

		await page.goto( '/wp-admin/edit.php?post_type=qe_lead' );
		await expect( page.locator( '#the-list' ) ).toContainText( email );
	} );

	test( 'newsletter: invalid then valid email', async ( {
		page,
		browser,
	} ) => {
		const visitor = await browser.newPage( {
			storageState: { cookies: [], origins: [] },
		} );
		await visitor.goto( SLUG );
		await settle( visitor );
		const form = visitor.locator( 'form[data-qe-form="subscribe"]' );
		const email = `reader${ Date.now() }@example.test`;

		await form.locator( '[name="qe_email"]' ).fill( 'not-an-email' );
		await form.locator( '[data-qe-submit]' ).click();
		await expect( form.locator( '[name="qe_email"]' ) ).toHaveAttribute(
			'aria-invalid',
			'true'
		);

		await form.locator( '[name="qe_email"]' ).fill( email );
		await form.locator( '[data-qe-submit]' ).click();
		await expect(
			form.locator( '.qe-form__message.is-success' )
		).toBeVisible();
		await visitor.close();

		await page.goto( '/wp-admin/edit.php?post_type=qe_lead' );
		await expect( page.locator( '#the-list' ) ).toContainText( email );
	} );

	test( 'mobile mega menu: toggle, submenu, Escape', async ( {
		browser,
	} ) => {
		const page = await browser.newPage( {
			viewport: { width: 390, height: 844 },
			storageState: { cookies: [], origins: [] },
		} );
		await page.goto( SLUG );
		await settle( page );
		const nav = page.locator( '[data-qe-mega]' );
		await expect( nav ).toHaveClass( /is-mobile/ );
		const toggle = nav.locator( '.qe-mega__toggle' );
		await toggle.click();
		await expect( nav ).toHaveClass( /is-open/ );
		await expect( nav.locator( '.qe-mega__menu' ) ).toBeVisible();

		const sub = nav.locator( '.qe-mega__sub-toggle' ).first();
		await sub.click();
		await expect( sub ).toHaveAttribute( 'aria-expanded', 'true' );
		await expect( nav.locator( '.qe-mega__panel' ).first() ).toBeVisible();

		await page.keyboard.press( 'Escape' );
		await expect( nav ).not.toHaveClass( /is-open/ );
		await expect( toggle ).toBeFocused();
		await page.close();
	} );
} );
