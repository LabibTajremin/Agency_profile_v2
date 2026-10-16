/**
 * Log in once and save the admin session for all tests.
 */
const { chromium } = require( '@playwright/test' );
const fs = require( 'fs' );

module.exports = async ( config ) => {
	const { baseURL, launchOptions } = config.projects[ 0 ].use;
	fs.mkdirSync( 'test-results', { recursive: true } );
	const browser = await chromium.launch( launchOptions );
	const page = await browser.newPage();
	await page.goto( baseURL + '/wp-login.php' );
	await page.fill( '#user_login', process.env.WP_USER || 'admin' );
	await page.fill( '#user_pass', process.env.WP_PASS || 'password' );
	await Promise.all( [
		page.waitForURL( /wp-admin/ ),
		page.click( '#wp-submit' ),
	] );
	await page
		.context()
		.storageState( { path: 'test-results/admin-state.json' } );
	await browser.close();
};
