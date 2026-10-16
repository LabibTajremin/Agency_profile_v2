/**
 * Playwright config for the Elementor / front-end gates.
 *
 * Env:
 *  WP_BASE_URL       WordPress URL (default http://localhost:8888, wp-env).
 *  WP_USER / WP_PASS Admin credentials (default admin / password, wp-env).
 *  PW_CHROMIUM_PATH  Optional Chromium executable.
 */
const { defineConfig } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests/e2e',
	timeout: 180000,
	expect: { timeout: 20000 },
	fullyParallel: false,
	workers: 1,
	reporter: [ [ 'list' ] ],
	globalSetup: require.resolve( './tests/e2e/global-setup.js' ),
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		storageState: 'test-results/admin-state.json',
		launchOptions: process.env.PW_CHROMIUM_PATH
			? { executablePath: process.env.PW_CHROMIUM_PATH }
			: {},
	},
} );
