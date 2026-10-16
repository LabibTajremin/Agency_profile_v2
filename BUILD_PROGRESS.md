# BUILD_PROGRESS

Source of truth for resuming work (see `WP_THEME_BUILD_INSTRUCTIONS.md` §12/§15).
A new session reads this file and continues from the first unchecked item. Never redo committed work.

Local verification environment used during the build (not part of the repo):
WordPress (latest) + SQLite Database Integration + Elementor (latest), served with `php -S`,
theme and plugin symlinked in. `npx wp-env start` is the supported Docker path for contributors.

## Phase 0 — Scaffold
- [x] Repo layout (`theme/qe-edu`, `plugin/qe-edu-core`, `tests/`, `bin/`)
- [x] `.wp-env.json`, `package.json`, `composer.json`, `phpcs.xml.dist`, `phpunit.xml.dist`
- [x] `.gitignore`, `README.md` skeleton, `BUILD_PROGRESS.md`
- [x] Gate: theme + plugin activate with no notices; `composer lint` clean

## Phase 1 — Theme core
- [x] setup / enqueue / theme.json / page templates / fallback header+footer
- [x] self-hosted fonts, colour CSS variables
- [x] Gate: Theme Check 0 required errors; blank Full Width page has 0px theme padding

## Phase 2 — Elementor compat
- [x] §6 locations, defaults on activation, Kit sync, editor stylesheet, CSS rules
- [x] Gate: Playwright §6.5 on a page containing every core Elementor widget

Gate run: `wp eval-file tests/e2e/fixtures/create-widgets-page.php` then `npm run test:e2e -- tests/e2e/elementor-gate.spec.js` (62 core widgets, passed 4/4 runs). Colour Kit sync is completed with the palette in Phase 3.

## Phase 3 — Settings panel + colours
- [x] 10 tabs (spec's 9 + "Texts" for every theme string), live preview, export/import, reset section
- [x] 12 accent palettes + custom, Elementor globals sync, contrast test
- [x] Gate: every swatch updates front end + Elementor globals; contrast passes

Gate run: `tests/e2e/colors-gate.spec.js` (12/12 swatches) + `PaletteTest::test_every_palette_meets_contrast`.
Spec deviations (contrast): Emerald primary #0F875D (was #0F8A5F, 4.36:1); Sunset Coral text #111111 on primary; link/hover colours computed to reach 4.5:1.

## Phase 4 — CPTs + widgets
- [ ] CPTs (Destination, Service, Partner, Event, Team, Testimonial, Lead)
- [ ] Widgets (§5), registration form + leads CSV
- [ ] Gate: every widget renders, every text control editable, no console errors

## Phase 5 — SEO module
- [ ] Global + per-post SEO, schema, sitemap, robots, redirects, 404 log, Yoast/Rank Math step-aside
- [ ] Gate: one `<title>`, valid schema, disables with Yoast active

## Phase 6 — Demo content
- [ ] Pages, templates, menus, idempotent one-click importer
- [ ] Gate: fresh import + re-import no duplicates; structure at 1440/1024/390

## Phase 7 — Polish & QA
- [ ] Mobile, a11y, performance, `.pot`, screenshot, README, `npm run zip`
- [ ] Gate: Lighthouse / axe / PHPCS / zips
