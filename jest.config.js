const defaults = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaults,
	testEnvironment: 'jsdom',
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
	collectCoverageFrom: [
		'theme/qe-edu/assets/js/src/**/*.js',
		'plugin/qe-edu-core/assets/js/src/**/*.js',
	],
	coverageDirectory: 'coverage/js',
	coverageReporters: [ 'text', 'clover' ],
	coverageThreshold: {
		global: { branches: 100, functions: 100, lines: 100, statements: 100 },
	},
};
