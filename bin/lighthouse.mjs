/**
 * Lighthouse gate (mobile): runs Lighthouse on each URL and fails when a
 * score is below the Phase 7 targets (performance ≥ 90, accessibility 100,
 * SEO ≥ 95, CLS < 0.05).
 *
 * Usage: node bin/lighthouse.mjs http://localhost:8889/ http://localhost:8889/about/ …
 * Env:   CHROME_PATH (Chromium executable), LH_RUNS (best of N runs, default 1).
 */
/* eslint-disable no-console -- command-line report. */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

const TARGETS = { performance: 90, accessibility: 100, seo: 95 };
const MAX_CLS = 0.05;
const runs = Number( process.env.LH_RUNS || 1 );
const urls = process.argv.slice( 2 );
const dir = mkdtempSync( join( tmpdir(), 'qe-lh-' ) );

if ( ! urls.length ) {
	console.error( 'Usage: node bin/lighthouse.mjs <url> [url…]' );
	process.exit( 2 );
}

function audit( url, run ) {
	const out = join( dir, `${ encodeURIComponent( url ) }-${ run }.json` );
	execFileSync(
		'npx',
		[
			'lighthouse',
			url,
			'--quiet',
			'--chrome-flags=--headless=new --no-sandbox',
			'--only-categories=performance,accessibility,seo',
			'--output=json',
			`--output-path=${ out }`,
		],
		{ stdio: 'ignore' }
	);
	const report = JSON.parse( readFileSync( out, 'utf8' ) );
	const scores = {};
	for ( const [ key, category ] of Object.entries( report.categories ) ) {
		scores[ key ] = Math.round( category.score * 100 );
	}
	scores.cls = report.audits[ 'cumulative-layout-shift' ].numericValue;
	scores.failing = [];
	for ( const category of Object.values( report.categories ) ) {
		for ( const ref of category.auditRefs ) {
			const result = report.audits[ ref.id ];
			if (
				ref.weight > 0 &&
				result.score !== null &&
				result.score < 0.9
			) {
				scores.failing.push(
					`${ ref.id } (${ result.displayValue || result.score })`
				);
			}
		}
	}
	return scores;
}

let failed = false;
for ( const url of urls ) {
	// Best of N runs: a busy machine should not fail the gate by chance.
	let best = null;
	for ( let run = 0; run < runs; run++ ) {
		const scores = audit( url, run );
		if ( ! best || scores.performance > best.performance ) {
			best = scores;
		}
	}
	const ok =
		Object.entries( TARGETS ).every(
			( [ key, min ] ) => best[ key ] >= min
		) && best.cls < MAX_CLS;
	failed = failed || ! ok;
	console.log(
		`${ ok ? 'PASS' : 'FAIL' } ${ url }  performance ${
			best.performance
		}  accessibility ${ best.accessibility }  seo ${
			best.seo
		}  CLS ${ best.cls.toFixed( 3 ) }` +
			( ok ? '' : `\n     ${ best.failing.join( ', ' ) }` )
	);
}
process.exit( failed ? 1 : 0 );
