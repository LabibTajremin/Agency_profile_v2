/**
 * Phase 5 gate: exactly one <title> and one set of SEO tags on every kind of
 * page, valid JSON-LD (Organization, WebSite, BreadcrumbList, Course, Event,
 * FAQPage), sitemap / robots.txt / redirects / 404 log, the SEO box, and a
 * clean step-aside (no duplicate tags + notice + import) when Yoast SEO is
 * active.
 *
 * Setup: the QE widgets page (tests/e2e/fixtures/create-qe-widgets-page.php).
 * The SEO fixture and Yoast are handled by the spec through WP-CLI
 * (WP_PATH=/path/to/wordpress for a local install, otherwise wp-env).
 */
const { test, expect } = require( '@playwright/test' );
const { watchErrors, wp, fixture } = require( './helpers' );

let fx;
let site;

test.beforeAll( () => {
	try {
		wp( 'plugin deactivate wordpress-seo' );
	} catch ( e ) {
		// Not installed or already inactive.
	}
	fx = fixture( 'create-seo-fixtures.php' );
	site = wp( 'option get blogname' ).split( '\n' ).pop();
} );

/**
 * Everything SEO-related in the document head, plus every JSON-LD node.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<Object>} Summary.
 */
function inspect( page ) {
	return page.evaluate( () => {
		const count = ( selector ) =>
			document.head.querySelectorAll( selector ).length;
		const nodes = [];
		const invalid = [];
		document
			.querySelectorAll( 'script[type="application/ld+json"]' )
			.forEach( ( script ) => {
				try {
					const data = JSON.parse( script.textContent );
					( data[ '@graph' ] || [ data ] ).forEach( ( node ) =>
						nodes.push( node )
					);
				} catch ( e ) {
					invalid.push( script.textContent.slice( 0, 80 ) );
				}
			} );
		return {
			titles:
				document.querySelectorAll( 'title' ).length -
				document.querySelectorAll( 'svg title' ).length,
			title: document.title,
			descriptions: count( 'meta[name="description"]' ),
			canonicals: count( 'link[rel="canonical"]' ),
			ogTitles: count( 'meta[property="og:title"]' ),
			robots: Array.from(
				document.head.querySelectorAll( 'meta[name="robots"]' )
			).map( ( m ) => m.content ),
			ours: document.head.innerHTML.includes( 'QE Edu SEO' ),
			nodes,
			invalid,
		};
	} );
}

const ofType = ( nodes, type ) =>
	nodes.filter( ( n ) => n[ '@type' ] === type );

test.describe( 'SEO gate', () => {
	test( 'one title and one set of tags on every kind of page', async ( {
		page,
	} ) => {
		const urls = [
			'/',
			fx.pageUrl,
			fx.parentUrl,
			fx.eventUrl,
			'/qe-widgets/',
			'/?s=study',
			'/events/',
			'/no-such-page-qe/',
		];
		for ( const url of urls ) {
			const errors = watchErrors( page );
			await page.goto( url );
			const info = await inspect( page );
			expect( info.titles, `${ url } <title> count` ).toBe( 1 );
			expect( info.title.trim(), url ).not.toBe( '' );
			expect( info.descriptions, url ).toBeLessThanOrEqual( 1 );
			expect( info.canonicals, url ).toBeLessThanOrEqual( 1 );
			expect( info.ogTitles, url ).toBe( 1 );
			expect( info.robots, url ).toHaveLength( 1 );
			expect( info.ours, url ).toBe( true );
			expect( info.invalid, url ).toEqual( [] );
			expect(
				ofType( info.nodes, 'EducationalOrganization' ),
				url
			).toHaveLength( 1 );
			expect( ofType( info.nodes, 'WebSite' ), url ).toHaveLength( 1 );
			expect(
				errors.filter(
					( e ) =>
						! /favicon|google|gstatic|youtube|ytimg|soundcloud|sndcdn/.test(
							e
						) &&
						! ( url === '/no-such-page-qe/' && /404/.test( e ) )
				),
				url
			).toEqual( [] );
			page.removeAllListeners();
		}

		await page.goto( '/?s=study' );
		expect( ( await inspect( page ) ).robots[ 0 ] ).toContain( 'noindex' );
		await page.goto( '/no-such-page-qe/' );
		expect( ( await inspect( page ) ).robots[ 0 ] ).toContain( 'noindex' );
		await page.goto( fx.hiddenUrl );
		expect( ( await inspect( page ) ).robots[ 0 ] ).toContain( 'noindex' );
		await page.goto( fx.pageUrl );
		expect( ( await inspect( page ) ).robots[ 0 ] ).not.toContain(
			'noindex'
		);
	} );

	test( 'per-page values and valid schema', async ( { page } ) => {
		await page.goto( fx.pageUrl );
		const info = await inspect( page );
		expect( info.title ).toBe( `Custom SEO Title | ${ site }` );
		await expect(
			page.locator( 'meta[name="description"]' )
		).toHaveAttribute(
			'content',
			'Plan your studies in Canada with expert help.'
		);
		await expect( page.locator( 'link[rel="canonical"]' ) ).toHaveAttribute(
			'href',
			fx.pageUrl
		);
		await expect(
			page.locator( 'meta[property="og:title"]' )
		).toHaveAttribute( 'content', 'Study in Canada with us' );

		const org = ofType( info.nodes, 'EducationalOrganization' )[ 0 ];
		expect( org.name ).toBeTruthy();
		expect( org.url ).toMatch( /^https?:\/\// );

		const webpage = ofType( info.nodes, 'WebPage' )[ 0 ];
		expect( webpage.url ).toBe( fx.pageUrl );
		expect( webpage.isPartOf[ '@id' ] ).toMatch( /#website$/ );

		const crumbs = ofType( info.nodes, 'BreadcrumbList' )[ 0 ];
		expect( webpage.breadcrumb[ '@id' ] ).toBe( crumbs[ '@id' ] );
		expect( crumbs.itemListElement.map( ( i ) => i.name ) ).toEqual( [
			'Home',
			'Study Abroad Guide',
			'Study in Canada',
		] );
		crumbs.itemListElement.forEach( ( item, i ) => {
			expect( item[ '@type' ] ).toBe( 'ListItem' );
			expect( item.position ).toBe( i + 1 );
			if ( i < crumbs.itemListElement.length - 1 ) {
				expect( item.item ).toMatch( /^https?:\/\// );
			}
		} );

		const course = ofType( info.nodes, 'Course' )[ 0 ];
		expect( course.name ).toBe( 'Study in Canada' );
		expect( course.description ).toBeTruthy();
		expect( course.provider[ '@id' ] ).toBe( org[ '@id' ] );

		await page.goto( fx.eventUrl );
		const event = ofType( ( await inspect( page ) ).nodes, 'Event' )[ 0 ];
		expect( event.name ).toBe( 'Education Fair' );
		expect( event.startDate ).toMatch(
			/^2026-12-01T10:00:00[+-]\d\d:\d\d$/
		);
		expect( event.location[ '@type' ] ).toBe( 'Place' );
		expect( event.location.address ).toBe( 'Dhaka' );
		expect( event.organizer.name ).toBeTruthy();

		await page.goto( '/qe-widgets/' );
		const faq = ofType( ( await inspect( page ) ).nodes, 'FAQPage' )[ 0 ];
		expect( faq.mainEntity.length ).toBeGreaterThan( 0 );
		faq.mainEntity.forEach( ( q ) => {
			expect( q[ '@type' ] ).toBe( 'Question' );
			expect( q.name ).toBeTruthy();
			expect( q.acceptedAnswer[ '@type' ] ).toBe( 'Answer' );
			expect( q.acceptedAnswer.text ).toBeTruthy();
		} );
	} );

	test( 'sitemap, robots.txt, redirects and the 404 log', async ( {
		page,
		request,
	} ) => {
		const robots = await ( await request.get( '/robots.txt' ) ).text();
		expect( robots ).toMatch(
			/User-agent: \*\n(?:[^\n]+\n)*Disallow: \/private\/\n\nSitemap: \S+\/wp-sitemap\.xml/
		);
		expect( robots.match( /Sitemap:/g ) ).toHaveLength( 1 );

		const index = await ( await request.get( '/wp-sitemap.xml' ) ).text();
		expect( index ).toContain( 'wp-sitemap-posts-page-1.xml' );
		expect( index ).toContain( 'wp-sitemap-posts-qe_event-1.xml' );
		expect( index ).not.toContain( 'qe_lead' );
		const pages = await (
			await request.get( '/wp-sitemap-posts-page-1.xml' )
		).text();
		expect( pages ).toContain( fx.pageUrl );
		expect( pages ).not.toContain( fx.hiddenUrl );

		const moved = await request.get( '/old-seo/', { maxRedirects: 0 } );
		expect( moved.status() ).toBe( 301 );
		expect( moved.headers().location ).toBe( fx.pageUrl );

		const missing = `/qe-missing-${ Date.now() }/`;
		expect( ( await request.get( missing ) ).status() ).toBe( 404 );
		await page.goto( '/wp-admin/admin.php?page=qe-edu-settings&tab=seo' );
		await expect(
			page.locator( 'code', { hasText: missing.replace( /\/$/, '' ) } )
		).toHaveCount( 1 );
	} );

	test( 'SEO box: snippet preview, meters and keyword checks', async ( {
		page,
	} ) => {
		const errors = watchErrors( page );
		await page.goto( `/wp-admin/post.php?post=${ fx.page }&action=edit` );
		const box = page.locator( '[data-qe-seo]' );
		await expect( box ).toHaveCount( 1 );
		await expect( box.locator( '[data-qe-seo-preview-title]' ) ).toHaveText(
			`Custom SEO Title | ${ site }`
		);
		await expect(
			box.locator( '[data-qe-seo-meter="description"]' )
		).toHaveAttribute( 'data-status', 'short' );
		// Close the block editor's first-run guide if it shows.
		await page.evaluate( () =>
			window.wp.data
				.dispatch( 'core/preferences' )
				.set( 'core/edit-post', 'welcomeGuide', false )
		);
		await expect(
			page.getByRole( 'dialog', { name: 'Welcome to the editor' } )
		).toHaveCount( 0 );
		// The block editor keeps meta boxes in a collapsible pane.
		const pane = page.getByRole( 'button', { name: 'Meta Boxes' } );
		if (
			( await pane.count() ) &&
			( await pane.getAttribute( 'aria-expanded' ) ) === 'false'
		) {
			// The resize handle overlaps the toggle, so use the keyboard.
			await pane.focus();
			await page.keyboard.press( 'Enter' );
		}
		await expect( box.locator( '#qe-seo-title' ) ).toBeVisible();
		const checks = box.locator( '[data-qe-seo-checks] li' );
		await expect( checks ).toHaveCount( 4 );
		// Keyword "canada": in the description and the page title only.
		await expect( checks ).toHaveClass( [
			'is-missing',
			'is-ok',
			'is-missing',
			'is-ok',
		] );

		await box
			.locator( '#qe-seo-title' )
			.fill( 'Study in Canada {sep} {site}' );
		await expect( box.locator( '[data-qe-seo-preview-title]' ) ).toHaveText(
			`Study in Canada | ${ site }`
		);
		await expect( checks.first() ).toHaveClass( 'is-ok' );
		await expect(
			box.locator( '[data-qe-seo-meter="title"]' )
		).toHaveAttribute( 'data-status', 'short' );
		expect(
			errors.filter( ( e ) => /qe-edu|seo\.min|admin\.min/.test( e ) )
		).toEqual( [] );
	} );

	test( 'steps aside when Yoast SEO is active', async ( { page } ) => {
		try {
			wp( 'plugin is-installed wordpress-seo' );
		} catch ( e ) {
			wp( 'plugin install wordpress-seo' );
		}
		wp( 'plugin activate wordpress-seo' );
		try {
			for ( const url of [ '/', fx.pageUrl, fx.eventUrl ] ) {
				await page.goto( url );
				const info = await inspect( page );
				expect( info.ours, url ).toBe( false );
				expect( info.titles, url ).toBe( 1 );
				expect( info.descriptions, url ).toBeLessThanOrEqual( 1 );
				expect( info.canonicals, url ).toBeLessThanOrEqual( 1 );
				expect( info.ogTitles, url ).toBeLessThanOrEqual( 1 );
				expect( info.robots, url ).toHaveLength( 1 );
				expect( info.invalid, url ).toEqual( [] );
				expect(
					await page.locator( 'head' ).innerHTML(),
					url
				).not.toContain( 'googletagmanager.com/gtag' );
			}

			try {
				wp( `post meta delete ${ fx.page } _yoast_wpseo_metadesc` );
			} catch ( e ) {
				// Nothing copied yet.
			}
			await page.goto( '/wp-admin/index.php' );
			const notice = page.locator( '.notice', {
				hasText: 'Yoast SEO is active',
			} );
			await expect( notice ).toBeVisible();
			await notice
				.getByRole( 'button', { name: 'Copy my SEO values' } )
				.click();
			await expect(
				page.locator( '.notice-success', {
					hasText: /Copied \d+ SEO values\./,
				} )
			).toBeVisible();
			expect(
				wp( `post meta get ${ fx.page } _yoast_wpseo_metadesc` )
			).toBe( 'Plan your studies in Canada with expert help.' );
		} finally {
			wp( 'plugin deactivate wordpress-seo' );
		}

		await page.goto( fx.pageUrl );
		expect( ( await inspect( page ) ).ours ).toBe( true );
	} );
} );
