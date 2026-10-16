# QE Edu — WordPress theme + Elementor companion plugin

A premium, fully editable education / study-abroad WordPress theme (`theme/qe-edu`)
and its companion plugin (`plugin/qe-edu-core`), built for Elementor.

> Full end-user documentation is written in Phase 7. See `BUILD_PROGRESS.md` for status.

## Development

```bash
composer install        # PHPCS (WordPress standards) + PHPUnit
npm install             # build tooling, Jest, Playwright
npm run build           # compile SCSS + minify JS
composer lint           # PHPCS
npm test                # Jest + PHPUnit with 100% coverage gates
npx wp-env start        # local WordPress (Docker) at http://localhost:8888
```
