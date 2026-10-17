/**
 * Phase 6 gate: on a fresh site, Theme Settings → Tools → "Import demo"
 * builds every page, template and menu; each page renders the structure the
 * demo defines (every widget, in order) at 1440 / 1280 / 1100 / 1024 / 390 / 360 with no errors,
 * no overflow, no broken images and exactly one <h1>; importing again adds
 * nothing; a single page can be imported on its own.
 *
 * Needs a FRESH site with the theme, QE Edu Core and Elementor active:
 *   wp-env: the "tests" site (http://localhost:8889), reset with `npx wp-env clean tests`
 *   local:  DEMO_BASE_URL=http://127.0.0.1:8081 DEMO_WP_PATH=/path/to/fresh/wordpress
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const { watchErrors } = require( './helpers' );

const BASE = process.env.DEMO_BASE_URL || 'http://localhost:8889';
const DEMO = path.resolve( __dirname, '../../plugin/qe-edu-core/demo' );
const manifest = JSON.parse(
	fs.readFileSync( path.join( DEMO, 'manifest.json' ), 'utf8' )
);

function cli( args ) {
	const command = process.env.DEMO_WP_PATH
		? `wp --allow-root --path=${ process.env.DEMO_WP_PATH } ${ args }`
		: `npx wp-env run tests-cli wp ${ args }`;
	return execSync( command, { encoding: 'utf8', stdio: 'pipe' } ).trim();
}

/** Demo items by kind (image, post, page, template) plus menus, from the database. */
function inventory() {
	const out = cli(
		`eval 'global $wpdb; $c = array(); foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = \\"_qe_demo\\"" ) as $v ) { $k = strtok( $v, ":" ); $c[ $k ] = ( isset( $c[ $k ] ) ? $c[ $k ] : 0 ) + 1; } $c["menu"] = count( wp_get_nav_menus() ); $c["pages"] = (int) wp_count_posts( "page" )->publish; $c["attachments"] = (int) wp_count_posts( "attachment" )->inherit; echo wp_json_encode( $c );'`
	);
	return JSON.parse( out.split( '\n' ).pop() );
}

/**
 * Widget types of an Elementor tree, depth first.
 *
 * @param {Object[]} elements Elementor elements.
 * @param {string[]} list     Accumulator.
 * @return {string[]} Widget types.
 */
function widgets( elements, list = [] ) {
	for ( const el of elements ) {
		if ( el.elType === 'widget' ) {
			list.push( el.widgetType );
		}
		widgets( el.elements || [], list );
	}
	return list;
}

async function login( page ) {
	await page.goto( BASE + '/wp-login.php' );
	await page.fill( '#user_login', process.env.WP_USER || 'admin' );
	await page.fill( '#user_pass', process.env.WP_PASS || 'password' );
	await Promise.all( [
		page.waitForURL( /wp-admin/ ),
		page.click( '#wp-submit' ),
	] );
}

async function importFromTools( page, scope ) {
	await page.goto(
		BASE + '/wp-admin/admin.php?page=qe-edu-settings&tab=tools'
	);
	await page.selectOption( '#qe-demo-scope', scope );
	await Promise.all( [
		page.waitForURL( /qe_demo=done/, { timeout: 240000 } ),
		page.getByRole( 'button', { name: 'Import demo' } ).click(),
	] );
	const notice = page.locator( '.qe-demo-import .notice-success' );
	await expect( notice ).toBeVisible();
	const [ , added, kept ] = ( await notice.innerText() ).match(
		/(\d+) items added, (\d+) already there/
	);
	return { added: Number( added ), kept: Number( kept ) };
}

/**
 * Scroll through the page so lazy images load, then measure.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<Object>} Measurements.
 */
async function inspect( page ) {
	await page.evaluate( async () => {
		for ( let y = 0; y < document.body.scrollHeight; y += 400 ) {
			window.scrollTo( 0, y );
			await new Promise( ( r ) => setTimeout( r, 120 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await page.waitForTimeout( 600 );
	return page.evaluate( () => {
		const content = document.querySelector(
			'[data-elementor-type="wp-page"]'
		);
		return {
			overflow: document.documentElement.scrollWidth - window.innerWidth,
			h1: document.querySelectorAll( 'h1' ).length,
			widgets: content
				? Array.from(
						content.querySelectorAll( '[data-widget_type]' )
				  ).map(
						( el ) =>
							el
								.getAttribute( 'data-widget_type' )
								.split( '.' )[ 0 ]
				  )
				: [],
			header: !! document.querySelector(
				'[data-elementor-type="section"] .qe-mega'
			),
			footer: !! document.querySelector(
				'[data-elementor-type="section"] .qe-newsletter'
			),
			// Header/footer widgets cut off at the edges (overflow is clipped,
			// so scrollWidth alone would not show it).
			clipped: Array.from(
				document.querySelectorAll(
					'[data-elementor-type="section"] .elementor-widget'
				)
			)
				.filter( ( el ) => {
					const box = el.getBoundingClientRect();
					return (
						box.width > 0 &&
						( box.right > window.innerWidth + 1 || box.left < -1 )
					);
				} )
				.map( ( el ) => el.getAttribute( 'data-widget_type' ) ),
			broken: Array.from( document.images )
				.filter( ( img ) => img.complete && img.naturalWidth === 0 )
				.map( ( img ) => img.currentSrc || img.src ),
		};
	} );
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'Demo import gate', () => {
	test.use( { storageState: { cookies: [], origins: [] } } );

	let before;

	test( 'fresh site → import the whole demo from Tools', async ( {
		page,
	} ) => {
		test.setTimeout( 300000 );
		before = inventory();
		expect(
			before.page || 0,
			'Run this gate on a fresh site (see the file header).'
		).toBe( 0 );

		await login( page );
		const result = await importFromTools( page, 'all' );
		expect( result.added ).toBeGreaterThan( 100 );

		const after = inventory();
		expect( after.page ).toBe( manifest.pages.length );
		expect( after.template ).toBe( manifest.templates.length );
		expect( after.post ).toBe( manifest.posts.length );
		expect( after.image ).toBe( Object.keys( manifest.images ).length );
		expect( after.menu - ( before.menu || 0 ) ).toBe(
			Object.keys( manifest.menus ).length
		);
	} );

	for ( const width of [ 1440, 1280, 1100, 1024, 390, 360 ] ) {
		test( `every page matches the demo structure at ${ width }px`, async ( {
			browser,
		} ) => {
			test.setTimeout( 300000 );
			const page = await browser.newPage( {
				viewport: { width, height: 900 },
			} );
			const problems = [];
			for ( const entry of manifest.pages ) {
				const errors = watchErrors( page );
				const url =
					BASE + ( entry.key === 'home' ? '/' : `/${ entry.slug }/` );
				await page.goto( url );
				const info = await inspect( page );
				const label = `${ entry.key } @ ${ width }`;

				if ( entry.file ) {
					const expected = widgets(
						JSON.parse(
							fs.readFileSync(
								path.join( DEMO, entry.file ),
								'utf8'
							)
						)
					);
					expect( info.widgets, label ).toEqual( expected );
				} else {
					// The news page: Archive banner + post grid.
					await expect(
						page.locator( '.qe-banner__title' )
					).toHaveText( entry.title );
					expect(
						await page.locator( '.qe-post-grid article' ).count(),
						label
					).toBeGreaterThanOrEqual( 6 );
				}
				expect( info.header, `${ label } header` ).toBe( true );
				expect( info.footer, `${ label } footer` ).toBe( true );
				expect(
					info.overflow,
					`${ label } overflow`
				).toBeLessThanOrEqual( 0 );
				expect( info.h1, `${ label } <h1>` ).toBe( 1 );
				expect( info.clipped, `${ label } clipped` ).toEqual( [] );
				expect( info.broken, `${ label } images` ).toEqual( [] );
				const relevant = errors.filter(
					( e ) => ! /google|gstatic|maps|favicon/.test( e )
				);
				if ( relevant.length ) {
					problems.push( `${ label }: ${ relevant.join( ' | ' ) }` );
				}
				page.removeAllListeners();
			}
			expect( problems ).toEqual( [] );

			// Mobile header: menu button instead of the full menu.
			await page.goto( BASE + '/' );
			if ( width <= 1024 ) {
				await expect(
					page.locator( '.qe-mega__toggle' )
				).toBeVisible();
			} else {
				await expect( page.locator( '.qe-mega__toggle' ) ).toBeHidden();
			}
			await page.close();
		} );
	}

	test( 'templates: single post, archive and 404', async ( { page } ) => {
		const errors = watchErrors( page );
		await page.goto( BASE + '/news/' );
		const post = page.locator( '.qe-post-grid article a' ).first();
		const title = (
			await page.locator( '.qe-post-grid article h2' ).first().innerText()
		).trim();
		await post.click();
		await expect( page.locator( 'h1.qe-banner__title' ) ).toHaveText(
			title
		);
		await expect( page.locator( 'h1' ) ).toHaveCount( 1 );

		await page.goto( BASE + '/events/' );
		await expect( page.locator( 'h1.qe-banner__title' ) ).toHaveText(
			'Events'
		);

		await page.goto( BASE + '/no-such-page-qe/' );
		await expect(
			page.getByRole( 'heading', { name: 'We could not find that page' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'link', { name: 'Contact us', exact: true } )
		).toHaveAttribute( 'href', /\/contact\/$/ );
		expect(
			errors.filter(
				( e ) => ! /404|google|gstatic|maps|favicon/.test( e )
			)
		).toEqual( [] );
	} );

	test( 'importing again adds nothing', async ( { page } ) => {
		test.setTimeout( 300000 );
		const first = inventory();
		await login( page );
		const result = await importFromTools( page, 'all' );
		expect( result.added ).toBe( 0 );
		expect( result.kept ).toBeGreaterThan( 0 );
		expect( inventory() ).toEqual( first );
	} );

	test( 'one page on its own', async ( { page } ) => {
		const id = cli(
			`post list --post_type=page --name=careers --field=ID`
		);
		cli( `post delete ${ id } --force` );
		const first = inventory();

		await login( page );
		const result = await importFromTools( page, 'careers' );
		expect( result.added ).toBe( 1 );
		await expect(
			page.getByRole( 'link', { name: 'View the page' } )
		).toBeVisible();
		const after = inventory();
		expect( after.page ).toBe( first.page + 1 );
		expect( after.post ).toBe( first.post );
		expect( after.menu ).toBe( first.menu );
	} );
} );
