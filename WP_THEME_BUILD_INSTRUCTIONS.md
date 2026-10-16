# QE-Style Education Theme — WordPress + Elementor Build Instructions for Claude Code

> **How to use this file:** put it at the repo root, run `claude`, and say:
> *"Read WP_THEME_BUILD_INSTRUCTIONS.md and build Phase 0 and Phase 1. Stop after Phase 1 and show me the verification output."*
> Build **one phase at a time**. Every phase ends with a **verification gate**; do not start the next phase until the gate passes.
> **Resume rule:** commit and push at the end of every phase (and mid-phase whenever a sub-task is done). If the session hits a usage limit, the next session starts by reading `BUILD_PROGRESS.md` and continuing from the first unchecked item. Never redo committed work.

---

## 0. What we are building

A **premium WordPress theme** (`qe-edu`) plus a **companion plugin** (`qe-edu-core`) that recreates the look, structure and behaviour of **https://qegroup.ae/** — an international-education / study-abroad group website — and makes **every part of it editable by a non-technical person in Elementor**.

Reference analysis of the source site (done on 2026-10-16):

| Area | What the reference has |
|---|---|
| Top bar | Small utility links: News, Events, Careers, Study Mobility Program, Contact Us |
| Header / mega menu | Logo · Home · About Us (About QE Group, What We Do, Quantum International Pathways, Company Profile, Vision & Values, Why Work With Us, Affiliated Entities, Accreditation & Institution Partners, Global Network) · ELP Test · Partner With Us · Study Abroad · International Pathways · Language Academy · Partners (Partner Institutions, Recruitment Partners) |
| 1. Hero ("masthead") | Animated headline "Celebrating **15 Years of Excellence** in **Global Education Pathways**" with a typing cursor, two CTAs ("Start Your Journey", "Find Courses"), mouse-move parallax images |
| 2. International Students | Heading, intro paragraph, 3 benefit bullets (Apply with Ease / Vast Program Selection 45,000+ / End-to-End Support) beside a **student registration form** (first/last name, email, phone, DOB, home country, password, multi-step "Back / Sign Up") |
| 3. Services slider | 4 service cards with image + title + text + "Learn More": Direct Entry Student Recruitment · Quantum International Pathways · In-country University Preparation · QE Group Managed Campus |
| 4. Destinations | "Make the world your classroom" — grid/slider of 16 "Study in {Country}" cards with flag/photo |
| 5. Second slider | "Expand Your Horizons — Choose your dream career through countless Universities" image/news slider |
| 6. Universities / logos | "Trusted by the leaders, experts, and thought leaders across the globe!" — partner logo carousel |
| 7. Company profile | "Company Profile" block with **Download PDF** button |
| Footer | Newsletter subscribe · columns Resources (LMS, ELP Test, Career, Events & Webinars) · Study (Study Destination, International Students, Global Network, Study News) · About · legal links (Privacy Policy, Payment Terms, Refund Policy) · copyright |
| Inner pages | about-qe-group, career, contact-us, elp-test, event, news, international-pathways, language-academy, partner-institutions, recruitment-partners, study-abroad, study-mobility-program, privacy-policy |
| Brand | Deep navy `#140342` / `#2B1C55`, crimson `#A41E34`, indigo `#363D97`, violet `#6440FB`, light greys `#EDEDED`/`#E4E4E4`; fonts **Montserrat** (headings) + **Open Sans** (body); Swiper/Slick sliders; Font Awesome icons |

**Non-negotiables (from the product owner):**

1. **Easily editable by a non-tech person.** No code, no shortcodes to remember, no hidden PHP strings. Plain-language labels everywhere.
2. **Every part and every text is editable** — header, mega menu, top bar, hero words, form labels, footer, copyright, 404, search, archive titles, button labels. Zero hard-coded user-facing strings in templates.
3. **SEO settings** built in (works standalone; steps aside cleanly if Yoast / Rank Math is active).
4. **Minimum 10 premium accent colours** (we ship **12**), switchable in one click, synced to Elementor Global Colors.
5. **Optimised for Elementor editing — no "page breaks" in the editor.** Sections must not jump, collapse, double-pad, overflow, or split the canvas; the editor preview must match the live page pixel-for-pixel.
6. Mobile looks as premium as desktop (hard acceptance criterion).
7. Real `README.md`, real `.gitignore`, and a `BUILD_PROGRESS.md` checklist for resumable work.

Out of scope: copying QE Group's logo, photos, trademarks or copy verbatim. Ship **neutral demo content** (placeholder brand "Your Education Group", royalty-free images) with the same structure.

---

## 1. Stack decision

| Concern | Choice | Why |
|---|---|---|
| CMS | WordPress 6.6+ , PHP 8.1+ | Target hosting |
| Page builder | **Elementor (free) 3.24+** required; Elementor Pro optional | Theme must work fully with free Elementor; Pro adds Theme Builder header/footer which we also support |
| Layout model | **Elementor Flexbox Containers** (not legacy Section/Column) | Fewer DOM wrappers, no column breakage, proper responsive |
| Theme base | Own lightweight theme (no Hello-Elementor child) | Full control of wrappers; < 30 KB CSS |
| Custom widgets / CPTs / SEO / settings | Companion plugin `qe-edu-core` | Content survives theme switching (WP.org guideline) |
| Sliders | Swiper 11 (Elementor already bundles it — reuse `elementor-frontend` swiper, don't enqueue a second copy) | No double libraries |
| Forms | Built-in lightweight form widget (stores to CPT `qe_lead` + email) ; Contact Form 7 / Elementor Pro Forms also styled | Works without paid plugins |
| Icons | Elementor's Font Awesome + custom SVG | No extra icon font |
| Fonts | Montserrat + Open Sans, **self-hosted** (`/assets/fonts`), toggleable | Performance + GDPR |
| Build tooling | `npm` + `@wordpress/scripts` for SCSS/JS, `composer` for PHPCS (WPCS) | Standard |
| Dev env | `@wordpress/env` (Docker) | Reproducible |

---

## 2. Repository layout

```
/
├── theme/qe-edu/                 # the theme (zip this for clients)
│   ├── style.css                 # theme header only
│   ├── functions.php             # loads /inc only
│   ├── theme.json                # palette, typography, spacing (block editor parity)
│   ├── inc/
│   │   ├── setup.php             # supports, menus, image sizes
│   │   ├── enqueue.php
│   │   ├── elementor-compat.php  # all Elementor integration (§6)
│   │   ├── customizer.php        # quick settings mirror (§4)
│   │   ├── template-tags.php
│   │   └── tgmpa.php             # "Required plugins" installer prompt
│   ├── templates/                # page templates (§6.2)
│   ├── template-parts/           # fallback header/footer (used only if no Elementor template)
│   ├── assets/{scss,js,fonts,img}
│   ├── languages/qe-edu.pot
│   └── screenshot.png
├── plugin/qe-edu-core/
│   ├── qe-edu-core.php
│   ├── src/
│   │   ├── Settings/             # "Theme Settings" admin panel (§4)
│   │   ├── Seo/                  # SEO module (§8)
│   │   ├── Colors/               # accent palette registry + Elementor kit sync (§7)
│   │   ├── PostTypes/            # Destinations, Services, Partners, Events, Testimonials, Leads, Team
│   │   ├── Elementor/Widgets/    # custom widgets (§5)
│   │   ├── Forms/                # registration + newsletter handlers
│   │   └── DemoImport/           # one-click demo (§9)
│   └── demo/                     # Elementor JSON templates + XML content + images
├── BUILD_PROGRESS.md
├── README.md
├── .gitignore
├── package.json  composer.json  .wp-env.json  phpcs.xml.dist
```

Rules: namespaced PHP (`QeEdu\Core\...`), PSR-4 autoload, every output escaped (`esc_html`, `esc_attr`, `wp_kses_post`), every input sanitized, every string through `__()` with text-domain `qe-edu`.

---

## 3. Editability contract (non-tech user)

The client must be able to do all of this **without touching code**:

| Task | Where they do it |
|---|---|
| Change any text, image, button, link on any page | Elementor → click the element |
| Change logo, favicon, site name | Theme Settings → Brand (also Customizer → Site Identity) |
| Change header / mega menu / top bar | Elementor → Templates → "Header" (or Appearance → Menus for links) |
| Change footer columns, newsletter text, legal links, copyright | Elementor → Templates → "Footer" |
| Switch accent colour | Theme Settings → Colours → click a swatch (live preview) |
| Light / dark mode | Theme Settings → Colours → Mode |
| Fonts | Theme Settings → Typography (dropdown of 12 curated pairs) |
| Add a destination country / service / partner logo / event | Left menu "Destinations", "Services", etc. — simple form with title, image, short text, link. Widgets update automatically |
| SEO title/description/share image per page | "SEO" box under the editor on every page/post + Theme Settings → SEO for global defaults |
| Contact details, social links, WhatsApp button, map | Theme Settings → Contact (used by widgets via dynamic tags) |
| Company Profile PDF | Theme Settings → Brand → "Company profile PDF" (media picker) |
| 404 page, search page, blog archive layout | Elementor templates shipped in demo, editable like any page |
| Reset to demo | Theme Settings → Tools → "Re-import demo page" per page (never wipes other pages) |

UX rules for the admin panel:
- Plain English labels + one-line help text under every field ("Shown at the very top of every page").
- Grouped tabs, max ~8 fields visible at once.
- Every colour/typography change has **live preview**.
- **Export / Import settings** (JSON) and **Reset section to default** buttons.
- An "Edit this section" pencil on the front end (logged-in admins only) that deep-links into Elementor.

**No hard-coded strings rule:** a CI check greps `theme/` and `plugin/` templates for literal user-facing text outside `__()`/settings and fails the build.

---

## 4. Theme Settings panel (plugin, `Settings/`)

Top-level admin menu **"Theme Settings"** (dashicons-admin-appearance), built with the Settings API + a small React UI (`@wordpress/components`) for live preview. Values stored in a single option `qe_edu_settings` (autoload) — also exposed as **Elementor Dynamic Tags** so widgets can pull them.

Tabs:
1. **Brand** – logo (light/dark), favicon, company profile PDF, organisation name.
2. **Colours** – 12 accent swatches (§7), light/dark/auto mode, background tint, button style (rounded / pill / square), link underline on/off.
3. **Typography** – heading/body font pair, base size, heading scale, self-host on/off.
4. **Header** – sticky on/off, transparent-over-hero on/off, top bar on/off, CTA button text + link, mobile menu style (slide / full-screen).
5. **Footer** – newsletter on/off, copyright text (supports `{year}` and `{site}`), back-to-top button.
6. **Contact & Social** – phone(s), email, WhatsApp, address, map embed, 10 social networks.
7. **SEO** – see §8.
8. **Performance** – lazy-load, preload hero image, disable emojis/embeds, defer JS.
9. **Tools** – demo import, settings export/import, reset.

---

## 5. Elementor widgets (category "QE Education")

Each widget: every text = a control; every colour/typography = Elementor style controls defaulting to **Global Colors/Fonts** (never hard-coded hex); responsive controls for spacing/columns; works inside the editor without JS errors (re-init on `elementor/frontend/init` + `frontend/element_ready/{widget}`).

| Widget | Recreates | Key controls |
|---|---|---|
| **Animated Hero** | Masthead | Pre-text, rotating highlighted words (repeater), post-text, typing speed, cursor on/off, 2 buttons, background (image/video/gradient), parallax floating images (repeater, mouse-move strength, disabled on touch) |
| **Benefits + Form** | International Students | Heading, text, benefit repeater (icon, bold lead, text), form source (built-in / shortcode), layout left/right |
| **Registration Form** | Student sign-up | Fields repeater (label, type, required, placeholder), country list (editable, default ISO list), multi-step toggle, button labels, success message, email recipient, GDPR checkbox text. Stores to `qe_lead` CPT, nonce + honeypot + rate-limit |
| **Service Cards Slider** | 4 service cards | Source: Services CPT or manual repeater; image, title, text, button label/link; slides per view per breakpoint; autoplay; arrows/dots |
| **Destinations Grid** | "Make the world your classroom" | Source: Destinations CPT; title template "Study in {name}" (editable); grid or slider; hover effect; columns per breakpoint |
| **Image/News Slider** | "Expand Your Horizons" | Source: posts / events / manual; title, subtitle |
| **Logo Carousel** | Trusted-by partners | Source: Partners CPT; grayscale-on-idle toggle; continuous marquee or step |
| **CTA Download** | Company profile | Heading, text, button label, file (defaults to Theme Settings PDF) |
| **Stats Counter** | "15 years", "45,000+ programs" | Number, prefix/suffix, label, count-up on scroll |
| **Mega Menu** | Header nav | Uses WP menu; per-item column count + optional image/description; mobile accordion |
| **Top Bar** | Utility links | Menu or repeater + contact/social from settings |
| **Newsletter** | Footer subscribe | Heading, placeholder, button, provider (built-in list / Mailchimp API key / shortcode) |
| **Event List**, **Team Grid**, **Testimonials**, **FAQ (with schema)**, **Breadcrumbs**, **Page Title Banner** | Inner pages | Standard |

---

## 6. Elementor optimisation — "no page breaks in the editor"

This is the hardest requirement; implement **all** of the following in `inc/elementor-compat.php` and verify in Phase 2.

### 6.1 Theme declares Elementor support correctly
- Register theme locations (`elementor/theme/register_locations`) and call `elementor_theme_do_location('header'|'footer'|'single'|'archive')` so Pro Theme Builder works; fall back to our own Elementor header/footer templates (stored as `elementor_library` posts and rendered with `\Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $id )`) for free-Elementor users.
- Set Elementor defaults on activation: `elementor_container_experiment = active`, `elementor_disable_color_schemes = yes`, `elementor_disable_typography_schemes = yes`, content width **1240px**, widget space 20px, breakpoints mobile 767 / tablet 1024 / laptop 1366 (custom breakpoints on).
- Register the theme's palette and fonts **into the active Elementor Kit** (Global Colors/Fonts) so the user edits colours in one place.

### 6.2 Page templates with zero wrapper interference
- `templates/elementor-canvas-full.php` — **"Full Width (Header + Footer)"**: `<main id="content" class="site-main">` + `the_content()` only. **No** `.container`, no `max-width`, no padding, no margins, no `article` card, no title, no comments, no sidebar.
- `templates/canvas.php` — **"Blank Canvas"** (no header/footer) for landing pages.
- Default template for pages built with Elementor (`_elementor_edit_mode === 'builder'`) is auto-switched to Full Width so users never pick wrong.
- Page title hidden by default on Elementor pages (`hide_title` via Kit setting `page_title_selector`).

### 6.3 CSS rules that prevent breaking/jumping
- Theme CSS is **scoped**: global element styles live under `:where()` (zero specificity) so Elementor widget styles always win.
- No theme styles on `.elementor-section`, `.e-con`, `.elementor-widget-*`, `.elementor-column`.
- `html, body { overflow-x: clip; }` (not `hidden`, which breaks sticky + the editor).
- No `100vw` widths (cause horizontal scrollbar in editor); use `100%` + container "Full width".
- Images: `max-width:100%; height:auto;` only — never fixed heights on wrappers.
- Hero/sliders reserve height with `aspect-ratio` / `min-height` so the editor canvas doesn't collapse to 0 before JS runs (that collapse is the main "page break" look).
- Sticky header uses Elementor's sticky motion or `position: sticky`; **disabled inside the editor** (`body.elementor-editor-active`) so it never covers sections being edited.
- Animations (AOS-like entrance) disabled in the editor and when `prefers-reduced-motion`.
- No `page-break-*` / `break-before` properties anywhere; no `<hr>` separators injected by the theme; no automatic `wpautop` on Elementor content.
- Swiper sliders initialise in editor with `observer:true, observeParents:true` and re-init on control changes; in editor they render statically (first N slides) if `isEditMode()` and the user toggles "Static preview in editor".

### 6.4 Editor UX niceties
- Custom editor stylesheet (`elementor/editor/after_enqueue_styles`) so the panel shows the theme's fonts.
- All shipped sections saved as **Elementor templates** ("Hero", "Services", "Destinations"…) under Templates → Saved, so users can drag a whole section into any page.
- Widget icons and clear names; no widget requires a Pro feature.

### 6.5 Elementor gate (automated, Phase 2)
Playwright script opens every demo page in `/wp-admin/post.php?post=ID&action=elementor`, waits for `elementor:init`, then asserts:
- no console errors;
- `document.documentElement.scrollWidth <= innerWidth` (no horizontal overflow) at 1440 / 1024 / 390;
- every top-level `.e-con` has height > 40px (nothing collapsed);
- visual diff between editor preview iframe and front end < 1.5% per section.

---

## 7. Colours — 12 premium accent palettes

Registry in `Colors/Palette.php`; each accent defines `primary`, `primary-dark`, `primary-soft` (8% tint), `on-primary` (text colour with ≥ 4.5:1 contrast). Selecting a swatch updates CSS variables **and** the Elementor Kit "Accent"/"Primary" globals.

| # | Name | Primary | Dark | On-primary |
|---|---|---|---|---|
| 1 | **QE Crimson** (default, from reference) | `#A41E34` | `#7E1426` | `#FFFFFF` |
| 2 | Royal Indigo | `#363D97` | `#262B70` | `#FFFFFF` |
| 3 | Electric Violet | `#6440FB` | `#4A2BD1` | `#FFFFFF` |
| 4 | Midnight Navy | `#140342` | `#0B0127` | `#FFFFFF` |
| 5 | Ocean Blue | `#005A94` | `#003E65` | `#FFFFFF` |
| 6 | Emerald | `#0F8A5F` | `#0A6345` | `#FFFFFF` |
| 7 | Teal Lagoon | `#0E7C86` | `#095961` | `#FFFFFF` |
| 8 | Champagne Gold | `#B8913A` | `#8C6B24` | `#111111` |
| 9 | Sunset Coral | `#E2574C` | `#B83E35` | `#FFFFFF` |
| 10 | Amber Saffron | `#E89B12` | `#B4760A` | `#111111` |
| 11 | Rose Quartz | `#C2477A` | `#962F5B` | `#FFFFFF` |
| 12 | Graphite | `#2F3542` | `#1C2029` | `#FFFFFF` |

Neutral system (fixed, mode-aware): ink `#140342`/`#2B1C55` headings, body `#3D3A4F`, muted `#6B6880`, surface `#FFFFFF`, surface-alt `#F6F5FB`, border `#E4E4E4`. Dark mode: surface `#0E0B1F`, surface-alt `#17132E`, text `#ECEAF6`.

Also: **"Custom" swatch** with colour picker (contrast warning if < 4.5:1), and gradient toggle for hero (primary → navy).

---

## 8. SEO module (`Seo/`)

Global (Theme Settings → SEO):
- Title template (`{title} | {site}`), separator, homepage title & meta description.
- Default social share image; Facebook App ID; Twitter/X handle.
- Organisation schema: name, logo, address, phone, social profiles (`EducationalOrganization` type selectable).
- XML sitemap on/off (extends core `wp-sitemaps`, adds CPTs, excludes `qe_lead`).
- Robots.txt editor; "Discourage search engines" warning banner.
- Verification codes: Google, Bing, Yandex, Pinterest.
- Google Analytics 4 / Tag Manager ID (loaded after consent if consent toggle on).
- Breadcrumbs on/off + BreadcrumbList schema.
- Redirects (301) table and 404 log (lightweight, capped at 500 rows).

Per page/post/CPT ("SEO" metabox + Elementor page-settings panel tab):
- SEO title, meta description (with live Google snippet preview + length meters), focus keyword (simple density/heading checks).
- Canonical URL, noindex / nofollow.
- OG/Twitter title, description, image.
- Schema type: WebPage / AboutPage / ContactPage / FAQPage / Event / Course.

Technical: one `<title>` via `document_title_parts`; meta output in `wp_head` priority 1; **auto-disables its front-end output if Yoast SEO or Rank Math is active** (shows a notice and offers to import our values). Lighthouse SEO ≥ 95.

---

## 9. Content model (CPTs) & demo import

CPTs (all `show_in_rest`, Gutenberg-free simple edit screens with featured image + a few meta fields): **Destination** (country, flag, hero image, intro), **Service**, **Partner** (logo, URL, type: university/recruitment/accreditation), **Event** (date, venue, online link — Event schema), **Team Member**, **Testimonial**, **Lead** (private, admin-only, CSV export).

Demo pages (Elementor JSON, built only from our widgets + core Elementor widgets): Home, About, What We Do, Study Abroad, International Pathways, Language Academy, ELP Test, Partner With Us, Partner Institutions, Recruitment Partners, Study Mobility Program, Careers, Events, News (blog), Contact, Privacy Policy, Payment Terms, Refund Policy, 404. Plus Header, Footer, Single Post, Archive templates. Menus pre-built and assigned.

One-click import: Theme Settings → Tools → "Import demo" — choose full site or single page; images downloaded to media library; idempotent (re-run doesn't duplicate).

---

## 10. Mobile — first-class

- Designed at 390px first. Hero headline uses `clamp()`; rotating words never wrap mid-word.
- Header: logo + hamburger + CTA icon; full-screen menu with accordion submenus, 48px tap targets, focus-trapped, closes on Esc.
- Registration form becomes single-column, step-by-step with progress dots; native date & country pickers.
- Sliders → swipe with peek of next card; destinations grid → 2 columns.
- Sticky bottom "Apply / WhatsApp" bar (toggleable).
- Gate: Lighthouse mobile Performance ≥ 90, CLS < 0.05, no horizontal scroll at 360px.

---

## 11. Accessibility, performance, security

- WCAG 2.2 AA: skip link, landmarks, visible focus ring in accent colour, alt text required on widget images, reduced-motion respected, sliders pausable.
- Assets only on pages that use the widget (Elementor `get_script_depends`/`get_style_depends`). Theme CSS < 30 KB gz, JS < 15 KB gz. Fonts preloaded with `font-display: swap`.
- Security: nonces on all forms/AJAX, capability checks (`manage_options` for settings, `edit_posts` for content), `wp_kses` on rich fields, rate-limit + honeypot on forms, no `eval`, no remote code, escaped output everywhere. PHPCS (WordPress-Extra + WordPress-Docs) and Theme Check must pass.

---

## 12. Build phases and verification gates

| Phase | Build | Gate (must pass before next phase) |
|---|---|---|
| **0. Scaffold** | Repo layout, `.wp-env.json`, `package.json`, `composer.json`, PHPCS config, `.gitignore`, `README.md` skeleton, `BUILD_PROGRESS.md` | `npx wp-env start` works; theme + plugin activate with no notices; `composer lint` clean |
| **1. Theme core** | setup, enqueue, theme.json, page templates (§6.2), fallback header/footer, self-hosted fonts, colour CSS variables | Theme Check plugin: 0 required errors; blank Full Width page has 0px theme padding |
| **2. Elementor compat** | Everything in §6, Kit sync, editor stylesheet | Playwright gate §6.5 passes on a test page containing every core Elementor widget |
| **3. Settings panel + colours** | §4 + §7 with live preview, export/import | Switching each of 12 swatches updates front end and Elementor globals; contrast test passes for all |
| **4. CPTs + widgets** | §5 + §9 CPTs, registration form + leads | Each widget: renders in editor & front end, every text editable (automated: change every text control → appears on page), no console errors |
| **5. SEO module** | §8 | Validator: schema passes Google Rich Results test sample; one `<title>`; disables cleanly with Yoast active |
| **6. Demo content** | All pages/templates/menus, one-click importer | Fresh site → import → visual match to reference structure at 1440/1024/390; re-import creates no duplicates |
| **7. Polish & QA** | Mobile pass, a11y, performance, translations `.pot`, screenshots, README | Lighthouse mobile ≥ 90 perf / 100 a11y / ≥ 95 SEO; axe 0 serious; PHPCS clean; `npm run zip` produces installable theme + plugin zips |

At the end of each phase: update `BUILD_PROGRESS.md`, commit (`phase N: …`), push.

---

## 13. README requirements
Install (upload theme zip + plugin zip, or "Required plugins" prompt), demo import steps with screenshots, **"How to edit" guide for non-technical users** (one short section per task in §3), changing colours, SEO setup, child-theme notes, development commands, troubleshooting (Elementor "content area not found", regenerate CSS, clear cache).

## 14. .gitignore requirements
`node_modules/`, `vendor/`, `build/`, `dist/`, `*.zip`, `.wp-env.override.json`, `wp-content/uploads/`, `.env*` (except `.env.example`), `*.log`, `.DS_Store`, `Thumbs.db`, `.idea/`, `.vscode/*` (keep `extensions.json`), `playwright-report/`, `test-results/`.

## 15. Working agreement for Claude Code
- One phase per run; show gate output before stopping.
- Never hard-code user-facing text, colours or fonts in templates.
- Prefer Elementor-native features over custom JS; never fight Elementor's CSS.
- Small commits, pushed often; `BUILD_PROGRESS.md` is the source of truth for resuming after a limit reset or a new session.
- If a requirement here conflicts with Elementor behaviour, stop and ask rather than hacking around it.
