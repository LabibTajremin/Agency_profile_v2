# Maapkathi Studio — Build Instructions for Claude Code

> **How to use this file:** drop it at the repo root as `BUILD_INSTRUCTIONS.md`, open the folder in VS Code, run `claude`, and say:
> *"Read BUILD_INSTRUCTIONS.md and build Phase 0 and Phase 1. Stop after Phase 1 and show me the verification output."*
> Build **one phase at a time**. Each phase ends with a verification gate — do not start the next phase until the gate passes.

---

## 0. What we are building

A **white-label portfolio & showcase platform for interior / architectural design studios.**

Two distribution modes from **one codebase**:

| Mode | Description |
|---|---|
| **SaaS** | We host it. Multiple studios, each on a subdomain or custom domain, billed monthly/yearly. |
| **Self-hosted (one-time licence)** | We deploy a single-tenant copy onto the client's own hosting/domain. They edit it themselves. Licence key validated offline-tolerantly. |

The public site showcases **projects** (photos + videos + copy) and **team members**. Everything on the public site is editable from an **admin panel** — content, branding, theme, SEO, contact details, navigation.

**Non-negotiables (from the product owner):**

1. Clean architecture, layered, repository pattern, real design patterns where they earn their keep.
2. Indexed database schema, no N+1 queries, no raw DB access outside repositories.
3. Storage driver chosen by a **numeric code in config** (1 = S3, 2 = Google Drive, …), fully pluggable.
4. Admin ↔ Editor roles, with an admin-controlled **verification toggle** gating editor content.
5. Admin bootstrap credentials from env/config.
6. Dark/light mode, 20+ accent colours, 20+ background patterns, pattern opacity control, plus further premium theming controls.
7. **Mobile must look as premium as desktop.** This is a hard acceptance criterion, not a nice-to-have.
8. A real `README.md` and a real `.gitignore`.
9. **Visual bar:** match the structure of the reference site in §9, then clearly exceed it on typography, spacing, imagery, motion, and mobile craft.

---

## 1. Stack decision (and why)

Chosen for cost at low traffic, readability, hireability, and 5-year longevity.

| Layer | Choice | Why |
|---|---|---|
| Framework | **Next.js 15 (App Router) + TypeScript (strict)** | One deployable for public site + admin + API. RSC keeps the public site fast and SEO-strong. Huge hiring pool. |
| Styling | **Tailwind CSS v4** + **shadcn/ui** (Radix primitives) | Tokens map cleanly to runtime CSS variables — required for admin-controlled theming. Accessible primitives out of the box. |
| Motion | **Framer Motion**, used sparingly | Premium feel without jank. Must respect `prefers-reduced-motion`. |
| DB | **PostgreSQL** — Neon (SaaS) / any Postgres (self-host) | Free tier, branch-per-PR for client preview links, standard SQL, no lock-in. |
| ORM | **Drizzle ORM + drizzle-kit** | SQL-first, explicit index declarations, tiny runtime, trivial to read in review. Migrations are plain SQL files a client's DBA can audit. |
| Auth | **Auth.js v5** (credentials provider, JWT session) | No external identity vendor cost; self-host friendly; works on Vercel edge/node. |
| Validation | **Zod** | One schema shared by API boundary, forms, and config parsing. |
| Images | **sharp** at upload → derivatives + blurhash/LQIP | Fast LCP on mobile; no per-image CDN transform bill. |
| Tests | **Vitest** (unit/integration) + **Playwright** (e2e smoke) | Fast, boring, well-supported. |
| Package manager | **pnpm** | Deterministic, disk-cheap. |

### Two decisions to confirm before Phase 1

> **Claude Code: surface these to the user, then proceed with the stated default if there is no answer.**

- **DEC-1 — Self-host target.** Next.js needs a Node runtime. Most interior studios own cPanel/shared PHP hosting, which **cannot** run this. Default assumption: self-host deployments go to a **Docker container on a small VPS** (Hetzner/DigitalOcean, ~$5/mo) or a **client-owned Vercel project**, with the client's domain pointed at it. Shared cPanel hosting is explicitly out of scope. A `Dockerfile` + `docker-compose.yml` ship in the repo for this reason.
- **DEC-2 — Video delivery.** Google Drive works as *storage* but is a poor *streaming origin* (quota throttling, redirect churn, no HLS, breaks on viral traffic). Default: Drive is supported as requested for archival/small-scale delivery, **and** a Bunny Stream / Cloudflare Stream driver is included as the recommended production path for video. Warn the user in the README, not in a modal.

---

## 2. Architecture

Dependency rule: **nothing in `domain/` or `application/` may import from `infrastructure/`, `app/`, or any npm framework package.** Enforce with ESLint `import/no-restricted-paths`.

```
src/
  domain/                     # Pure TypeScript. Entities, value objects, domain errors, repository INTERFACES.
    entities/                 #   Project, MediaAsset, Member, User, ThemeSettings, SiteSettings, Tenant, Licence
    value-objects/            #   Slug, ImageVariantSet, HexColor, PatternId, Opacity
    errors/                   #   DomainError hierarchy — never leak DB/HTTP concepts
    repositories/             #   ProjectRepository.ts etc. — INTERFACES ONLY
    services/                 #   ContentVisibilityPolicy, RolePolicy — pure business rules

  application/                # Use cases. Orchestration only, no SQL, no fetch, no fs.
    use-cases/
      projects/               #   CreateProject, UpdateProject, ReorderProjects, PublishProject, SubmitProjectForReview, ApproveSubmission
      media/                  #   UploadMedia, AttachMediaToProject, ReorderGallery, DeleteMedia
      members/                #   CreateMember, UpdateMember, ReorderMembers
      users/                  #   InviteEditor, SetEditorVerificationRequired, ToggleUserActive
      settings/               #   UpdateThemeSettings, UpdateSiteSettings, UpdateSeoSettings
    ports/                    #   StoragePort, ImageProcessorPort, MailerPort, ClockPort, IdGeneratorPort, CachePort
    dto/                      #   Zod schemas for use-case input/output

  infrastructure/             # The only layer that knows about the outside world.
    db/
      schema/                 #   Drizzle tables + indexes
      migrations/             #   generated SQL
      repositories/           #   DrizzleProjectRepository implements ProjectRepository
      mappers/                #   row <-> entity
    storage/
      StorageFactory.ts       #   numeric driver code -> adapter (see §4)
      adapters/               #   S3StorageAdapter, GoogleDriveStorageAdapter, LocalStorageAdapter,
                              #   R2StorageAdapter, BunnyStreamAdapter, SupabaseStorageAdapter
    image/SharpImageProcessor.ts
    auth/                     #   Auth.js config, password hashing (argon2id), session helpers
    licence/                  #   LicenceVerifier (Ed25519 signature check, offline grace)
    cache/                    #   In-memory LRU by default; Redis adapter behind the same port
    logging/                  #   pino logger

  app/                        # Next.js App Router — thin. Reads request, calls a use case, renders.
    (public)/                 #   /, /projects, /projects/[slug], /team, /about, /contact
    (admin)/admin/            #   dashboard, projects, media, members, users, settings, appearance
    api/                      #   route handlers: upload, revalidate, licence, health

  presentation/
    components/ui/            #   shadcn primitives
    components/blocks/        #   Hero, ProjectGrid, ProjectGallery, MemberGrid, ContactBlock
    components/admin/         #   DataTable, MediaDropzone, SortableList, ColorPicker, PatternPicker
    theme/                    #   accent palette registry, pattern registry, CSS var injection
    hooks/ lib/

  config/
    app.config.ts             #   THE config file (see §3)
    theme.constants.ts

tests/  unit/ integration/ e2e/
```

### Composition root
`src/infrastructure/container.ts` — a small manual DI container (no framework). Use cases receive dependencies via constructor. **Do not use a service-locator or import repositories directly inside use cases.**

### Patterns to actually use
- **Repository** — all persistence.
- **Adapter + Factory** — storage drivers, image processing, mailer.
- **Strategy** — `ContentVisibilityPolicy`: `VerificationRequiredStrategy` vs `AutoPublishStrategy`, selected from settings.
- **Unit of Work** — one Drizzle transaction per use case that writes more than one aggregate.
- **Result type** — `Result<T, DomainError>` for expected failures; throw only for programmer errors.
- **Specification** — composable filters for project queries (status, category, featured, year).

Do **not** add: event sourcing, CQRS read models, microservices, a message bus. Overkill here.

---

## 3. The config file

`config/app.config.ts` — parses `process.env` through Zod, exports a frozen typed object, **throws at boot with a readable message if invalid**. Every numeric-coded option carries a comment block listing what each value means.

```ts
// ─────────────────────────────────────────────────────────────────────────────
// FILE STORAGE DRIVER — set STORAGE_DRIVER in .env
//   1 = Amazon S3            (also any S3-compatible: MinIO, Wasabi, Backblaze B2)
//   2 = Google Drive         (service account; good for archival, weak for streaming)
//   3 = Local filesystem     (self-hosted VPS with a persistent disk; NOT for Vercel)
//   4 = Cloudflare R2        (S3 API, zero egress fees — best value for image-heavy sites)
//   5 = Supabase Storage     (easiest if the DB is already Supabase)
//   6 = Bunny.net Storage    (cheap CDN-attached object storage)
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// VIDEO DELIVERY DRIVER — set VIDEO_DRIVER in .env
//   0 = Same as STORAGE_DRIVER (serve the raw file)
//   1 = Bunny Stream         (HLS + CDN, ~$5/mo — RECOMMENDED for production)
//   2 = Cloudflare Stream
//   3 = Mux
//   4 = External embed only  (YouTube / Vimeo URLs pasted in the admin panel)
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// DEPLOYMENT MODE — set DEPLOYMENT_MODE in .env
//   1 = Single-tenant (self-hosted licence build — one studio, no tenant column filtering)
//   2 = Multi-tenant SaaS (host/subdomain resolves the tenant)
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// LICENCE MODE — set LICENCE_MODE in .env
//   1 = None (our own instance)
//   2 = One-time perpetual key (Ed25519-signed, verified offline)
//   3 = Subscription (periodic phone-home, 14-day offline grace before read-only)
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// CACHE DRIVER — set CACHE_DRIVER in .env
//   1 = In-memory LRU (default; fine for single-instance self-host)
//   2 = Redis / Upstash (required if running more than one instance)
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// MAIL DRIVER — set MAIL_DRIVER in .env
//   0 = Disabled (no contact-form email, no invites — invite links shown in admin UI)
//   1 = SMTP
//   2 = Resend
// ─────────────────────────────────────────────────────────────────────────────
```

Also ship `.env.example` with **every** key, grouped by the same headings, each with the same comments. `.env.example` is committed; `.env*` (except `.env.example`) is git-ignored.

### Admin bootstrap credentials
```
ADMIN_EMAIL=owner@example.com
ADMIN_PASSWORD=change-this-on-first-login    # hashed with argon2id at seed time, never stored plain
ADMIN_NAME=Studio Owner
```
On boot, if no admin user exists, create one from these. If the env password is still the default sentinel value, force a password change on first login and show a persistent banner in the admin panel until it is changed.

---

## 4. Storage abstraction

```ts
// application/ports/StoragePort.ts
export interface StoragePort {
  put(input: { key: string; body: Buffer | ReadableStream; contentType: string; visibility: 'public' | 'private' }): Promise<StoredObject>;
  delete(key: string): Promise<void>;
  getUrl(key: string, opts?: { expiresInSeconds?: number }): Promise<string>;
  createMultipartUpload?(input: { key: string; contentType: string }): Promise<MultipartTicket>;
}
```

- `StorageFactory.create(config)` switches on the numeric code and returns the adapter. Adding a driver = one new file + one switch case + one comment block. Nothing else in the app changes.
- **Google Drive adapter:** service-account JSON (base64 in env), uploads into a configured folder ID, sets `anyoneWithLink` reader permission, returns the `uc?export=view&id=` form for images and a resumable-upload path for large video. Cache the file→URL mapping in the DB; do **not** hit the Drive API on every page render.
- Large uploads use **presigned/multipart direct-to-storage from the browser** where the driver supports it (S3/R2/Supabase). The Node process must never buffer a 500 MB video.
- Every uploaded asset gets a row in `media_assets` with `storage_driver` recorded, so a studio that migrates drivers later doesn't lose existing files.

---

## 5. Data model (Drizzle) — with the indexes

All tables: `id uuid pk default gen_random_uuid()`, `created_at`, `updated_at`, and — when `DEPLOYMENT_MODE=2` — `tenant_id uuid not null`. In single-tenant mode a single fixed tenant row is used so the schema never forks.

| Table | Key columns | Indexes |
|---|---|---|
| `tenants` | slug, custom_domain, plan, status | `uniq(slug)`, `uniq(custom_domain)` |
| `users` | email, password_hash, role(`admin`\|`editor`), is_active, last_login_at | `uniq(tenant_id, lower(email))`, `idx(tenant_id, role)` |
| `projects` | slug, title, summary, body, category_id, status(`draft`\|`pending_review`\|`published`\|`archived`), is_featured, sort_order, completed_at, location, client_name, area_sqft, cover_media_id, created_by, reviewed_by, reviewed_at | `uniq(tenant_id, slug)`, `idx(tenant_id, status, sort_order)`, `idx(tenant_id, is_featured) where status='published'`, `idx(tenant_id, category_id)`, GIN on `to_tsvector(title||summary)` for search |
| `categories` | name, slug, sort_order | `uniq(tenant_id, slug)`, `idx(tenant_id, sort_order)` |
| `media_assets` | project_id (nullable), type(`image`\|`video`), storage_driver, storage_key, url, width, height, duration_s, blurhash, alt_text, sort_order, status | `idx(tenant_id, project_id, sort_order)`, `idx(tenant_id, type)`, `idx(status)` |
| `members` | name, role_title, bio, photo_media_id, socials jsonb, sort_order, status | `idx(tenant_id, status, sort_order)` |
| `site_settings` | 1 row/tenant: studio name, logo, tagline, contact, address, socials, nav config jsonb, footer | `uniq(tenant_id)` |
| `theme_settings` | 1 row/tenant: mode, accent_id, custom_accent_hex, pattern_id, pattern_opacity, font_pair_id, radius, density, motion_level, grain, glass, hero_style | `uniq(tenant_id)` |
| `seo_settings` | 1 row/tenant: default title/description, OG image, GA/GTM/Meta Pixel ids, robots | `uniq(tenant_id)` |
| `hero_slides` | eyebrow, headline, kicker_word, body, image_media_id, video_media_id, cta_label, cta_href, sort_order, is_active | `idx(tenant_id, is_active, sort_order)` |
| `services` | title, slug, icon_media_id, excerpt, body, parent_id (self-FK for sub-services), sort_order, status | `uniq(tenant_id, slug)`, `idx(tenant_id, parent_id, sort_order)` |
| `stats` | label, value_number, suffix, sort_order | `idx(tenant_id, sort_order)` |
| `values` | title, body, icon, sort_order | `idx(tenant_id, sort_order)` |
| `clients` | name, logo_media_id, website, sort_order, is_featured | `idx(tenant_id, sort_order)` |
| `testimonials` | author_name, author_role, company, quote, avatar_media_id, rating, sort_order, status | `idx(tenant_id, status, sort_order)` |
| `awards` | title, issuer, year, media_id, link, sort_order | `idx(tenant_id, year desc)` |
| `process_steps` | step_no, title, body, icon, sort_order | `idx(tenant_id, sort_order)` |
| `faqs` | question, answer, group, sort_order, status | `idx(tenant_id, status, sort_order)` |
| `posts` | slug, title, excerpt, body, cover_media_id, author_id, tags[], published_at, status | `uniq(tenant_id, slug)`, `idx(tenant_id, status, published_at desc)`, GIN on tags |
| `inquiries` | name, email, phone, message, source, is_read | `idx(tenant_id, is_read, created_at desc)` |
| `audit_log` | actor_id, action, entity, entity_id, diff jsonb | `idx(tenant_id, created_at desc)`, `idx(entity, entity_id)` |
| `licences` | key_fingerprint, plan, issued_at, expires_at, last_checked_at | `uniq(key_fingerprint)` |

Rules: no `SELECT *`; list endpoints are keyset-paginated (`created_at, id` cursor), never `OFFSET` on large tables; every foreign key gets an index; gallery reordering uses a fractional `sort_order` (float) so a drag never rewrites the whole table.

---

## 6. Roles, verification, and content flow

Two roles: **admin** and **editor**. `site_settings.editor_verification_required` is a boolean the admin toggles.

`ContentVisibilityPolicy` (Strategy):

| Actor | Verification ON | Verification OFF |
|---|---|---|
| Admin creates/edits | Publishes immediately | Publishes immediately |
| Editor creates/edits | `pending_review` → invisible publicly until an admin approves | `published` immediately |
| Editor edits an already-published item | Change is stored as a **pending revision**; the live version stays up until approved | Applied live |

- Admin panel shows a **Pending Approvals** queue with a side-by-side diff and Approve / Request changes / Reject. Rejection carries a note back to the editor.
- Flipping verification OFF does **not** auto-publish the existing pending queue — it asks: *"Publish the N items currently awaiting review?"*
- Every approve/reject writes to `audit_log`.
- Authorisation is enforced in the **use case layer**, not only in the UI. Every use case takes an `ActorContext { userId, role, tenantId }` and asserts permission first. Add a test that an editor cannot call `PublishProject` directly.

---

## 7. Theming system — the premium layer

All theming is DB-backed and injected as CSS custom properties on `<html>` during SSR, so there is **no flash and no client-side theme flicker**. Theme mode uses a tiny inline script + `cookie` to avoid FOUC.

### 7.1 Accent colours (registry of 24 — ship all)
Each entry defines `{ id, name, light: {50…950}, dark: {50…950}, onAccent }`, tuned so text on the accent passes **WCAG AA in both modes**.

`Obsidian Gold`, `Terracotta`, `Sage`, `Deep Teal`, `Midnight Blue`, `Burnt Sienna`, `Olive`, `Plum`, `Copper`, `Slate`, `Sand`, `Forest`, `Oxblood`, `Cobalt`, `Champagne`, `Charcoal Rose`, `Emerald`, `Amber`, `Indigo`, `Clay`, `Moss`, `Bronze`, `Ink Violet`, `Warm Graphite`.

Plus a **custom hex** picker with a live contrast checker that blocks unreadable choices.

### 7.2 Background patterns (registry of 22 — ship all)
Pure CSS/SVG, no raster images, tinted from the accent or a neutral, all with a single `--pattern-opacity` variable (admin slider, 0–100%, default 6%):

`None (blank)`, `Fine Grid`, `Blueprint Grid`, `Dot Matrix`, `Diagonal Hatch`, `Cross Hatch`, `Isometric`, `Subtle Noise/Grain`, `Topographic Lines`, `Concentric Arcs`, `Herringbone`, `Terrazzo`, `Linen Weave`, `Marble Veins`, `Art Deco Fan`, `Moroccan Trellis`, `Hexagon Mesh`, `Vertical Pinstripe`, `Wave Contour`, `Blueprint Corner Rules`, `Soft Radial Glow`, `Mesh Gradient`.

Patterns render in a `position: fixed` layer behind content with `pointer-events: none`, and are **disabled or simplified on low-power devices** (`prefers-reduced-motion`, `navigator.deviceMemory < 4`).

### 7.3 Additional premium controls (all admin-editable, all live-previewed)
- **Font pairing** — 8 curated display/body pairs (e.g. Cormorant + Inter, Playfair + Source Sans, Fraunces + Manrope, Instrument Serif + Geist). Self-hosted via `next/font`, no external font CDN.
- **Corner radius** — sharp / subtle / soft / pill.
- **Density** — compact / comfortable / spacious (drives spacing scale).
- **Motion level** — off / subtle / expressive. Always capped by `prefers-reduced-motion`.
- **Grain overlay** toggle + intensity.
- **Glass / frosted surfaces** toggle for nav and cards.
- **Hero style** — full-bleed image / split / video loop / minimal type-led / slider.
- **Project card style** — editorial / masonry / uniform grid / overlap.
- **Image aspect ratio** for grids — 4:5 / 1:1 / 3:2 / 16:9.
- **Cursor** — default / minimal custom dot (desktop only, auto-off on touch).
- **Section dividers** — none / hairline / accent rule.
- **Logo variants** — light logo, dark logo, favicon, OG image.
- **Watermark** on gallery images — off / subtle, position configurable.
- **Live Preview mode** — the appearance page renders the real homepage in an iframe and updates on every control change before saving. Save / Reset to defaults / Export theme JSON / Import theme JSON (this is how we ship presets to new clients).
- **3 built-in presets**: *Editorial Warm*, *Architect Mono*, *Luxe Dark*.

---

## 7.4 Animation system — admin-controlled

Motion is a **first-class, admin-editable setting**, not something hardcoded in components. No component may define its own timing, easing, or distance — every animated element reads from CSS variables and a shared motion config, so changing one admin control restyles the entire site instantly.

### 7.4.1 Data model
Add to `theme_settings`:

```
motion_preset          text     -- 'off' | 'subtle' | 'refined' | 'expressive' | 'custom'
scroll_reveal_style    text     -- see registry below
hero_animation         text     -- see registry below
image_hover_style      text     -- see registry below
card_hover_style       text     -- see registry below
page_transition        text     -- see registry below
text_reveal_style      text     -- see registry below
cursor_style           text     -- 'none' | 'dot' | 'ring' | 'magnetic' | 'blend'
loader_style           text     -- 'none' | 'wordmark' | 'counter' | 'curtain' | 'accent-wipe'
scroll_progress        boolean
smooth_scroll          boolean  -- Lenis; auto-off on touch and on reduced-motion
parallax_intensity     smallint -- 0-100, 0 disables
motion_speed           smallint -- 50-150, a global multiplier on every duration (%)
stagger_ms             smallint -- 0-200
animate_once           boolean  -- default true
motion_on_mobile       text     -- 'full' | 'reduced' | 'off'  (default 'reduced')
```

`motion_preset` other than `custom` sets all of the above at once; touching any individual control flips it to `custom`. Global CSS vars: `--motion-duration`, `--motion-ease`, `--motion-distance`, `--motion-stagger`.

### 7.4.2 Registries (ship all of these)

**Scroll reveal — 12 options**
`None` · `Fade` · `Fade Up` · `Fade Up Soft` (12 px, the default) · `Slide In From Side` · `Scale In` · `Blur In` · `Clip Reveal (bottom-up mask)` · `Curtain Wipe` · `Line Draw` (for rules and dividers) · `Stagger Grid` (children cascade) · `Perspective Tilt` (subtle 3D, desktop only).

**Hero animation — 8 options**
`Static` · `Ken Burns Drift` · `Crossfade Slides` · `Split Curtain` · `Masked Word Swap` (the rotating kicker word) · `Zoom Out On Load` · `Video Loop` · `Layered Parallax` (foreground text drifts slower than the image).

**Image hover — 8 options**
`None` · `Zoom` · `Zoom + Darken` · `Duotone → Colour` · `Grayscale → Colour` · `Reveal Caption Slide-Up` · `Accent Border Draw` · `Tilt Follow Cursor` (desktop only).

**Card hover — 6 options**
`None` · `Lift + Shadow` · `Accent Underline Draw` · `Border Trace` · `Inner Glow` · `Content Shift` (text nudges, image scales).

**Text reveal — 6 options**
`None` · `Fade` · `Mask Slide-Up (per line)` · `Word Stagger` · `Character Stagger` (headings only, ≤ 40 chars) · `Blur Focus`.

**Page transition — 6 options**
`None` · `Fade` · `Accent Wipe` · `Curtain Up` · `Slide Push` · `Logo Mask`.

**Section-level override:** the homepage section builder (§9.3) gives each section an optional reveal override, so a studio can make the hero expressive and keep the FAQ still.

### 7.4.3 Non-negotiable guardrails
These are enforced in code and **cannot be overridden by an admin setting** — a client who cranks every slider to maximum must still get a fast, accessible, non-nauseating site.

- `prefers-reduced-motion: reduce` wins over every setting. It collapses everything to instant or a plain opacity fade. Test this.
- Animate **`transform` and `opacity` only.** Never `width`, `height`, `top`, `left`, `margin`, or `filter` in a loop. Zero CLS contribution — reserve space before reveal.
- Reveal animations never delay content: elements render at `opacity: 0` **only after** JS confirms it loaded; if JS fails, content is visible. No blank page on a broken bundle.
- `IntersectionObserver` with `animate_once` default true. No scroll listeners in React state.
- Character-stagger and cursor effects are **desktop + fine-pointer only**, auto-disabled on `(pointer: coarse)`.
- Smooth scroll (Lenis) is off by default, off on touch, and off under reduced-motion — it breaks native scrolling and anchor jumps more often than it helps.
- Parallax uses a single rAF loop shared by all elements, capped at 60 fps, disabled below 768 px.
- On mobile, `motion_on_mobile: 'reduced'` (the default) keeps fades and hero motion but drops parallax, tilt, character stagger, and page transitions.
- Total animation JS on the public site stays inside the 180 KB budget in §8. Framer Motion is imported per-component with `LazyMotion` + `domAnimation`, never the full bundle.
- Every animated element is keyboard-reachable and focus-visible without needing hover.

### 7.4.4 Admin UX for this
The Appearance page gets a **Motion** tab: preset cards at the top (Off / Subtle / Refined / Expressive), then the individual dropdowns and sliders, each with a **small looping live preview tile** showing that exact effect — the admin should never have to guess what "Clip Reveal" means. A "Preview on homepage" button opens the live-preview iframe with the pending settings applied before saving. Reset to preset, and Export/Import motion JSON alongside the theme JSON.

---

## 8. Mobile is a first-class target

This is the acceptance criterion the product owner cares about most. Build mobile-first, then scale up.

Hard rules:
- Design at 390 px first. Every component must be reviewed at **390 / 768 / 1280 / 1920**.
- Tap targets ≥ 44×44 px. No hover-only interactions anywhere.
- Type scale uses `clamp()`; never let a display heading overflow on a 360 px screen.
- Respect safe-area insets (`env(safe-area-inset-*)`) — notches and home indicators.
- Admin panel on mobile: tables collapse into cards, actions move into a bottom sheet, forms are single-column with a sticky save bar. It must be genuinely usable one-handed, because owners will edit from a phone on-site.
- Gallery/lightbox: swipe, pinch-zoom, momentum, and a real close affordance.
- Serve responsive `srcset` + AVIF/WebP; LQIP blurhash placeholder on every image.
- Never autoplay video with sound; use a muted poster-first `<video>` with an explicit play control.
- **Budgets (enforced in CI via Lighthouse CI):** mobile LCP < 2.5 s, CLS < 0.05, INP < 200 ms, initial JS < 180 KB gzipped on the public site.

Accessibility: keyboard navigable admin, visible focus rings, semantic landmarks, alt text required on every uploaded image (admin cannot publish without it — with a "decorative" opt-out checkbox).

---

## 9. Visual reference and section blueprint

**Reference site:** `https://zeroinchinteriorsltd.com/` — a Dhaka interior studio. Study its **information architecture**, which is proven for this market, and then beat it on execution. It is a WordPress/Slider-Revolution build: the structure is right, the craft is not.

> **Do not clone it.** Do not copy its copy, images, logo, colours, or markup. We rebuild the same *sections* with our own components, our own type scale, and our own motion language. Everything below is content-managed — no hardcoded text or images anywhere in the components.

### 9.1 What the reference does well (keep)
Full-bleed hero slider with a rotating one-word kicker · a personal note from the principal architect with portrait · a long, SEO-rich studio introduction · client logo wall · three big portfolio *category* tiles numbered 01/02/03 · three-up services block with line icons · animated stat counters · featured-projects grid with category labels · a values/principles list · team grid with social links · latest blog posts · FAQ accordion · clear "get in touch" band.

### 9.2 Where it falls short (fix these — this is where "premium" is won)
| Problem in the reference | What we do |
|---|---|
| Generic WordPress theme feel; spacing is cramped and inconsistent | One 8-pt spacing scale, generous whitespace, editorial rhythm. Sections breathe. |
| Type is small, uniform, and unremarkable | Curated display/body pair, `clamp()` fluid scale, oversized editorial headings (up to ~clamp(2.5rem, 7vw, 6rem)), tight tracking on display, generous line-height on body. |
| Images are inconsistent crops at inconsistent ratios | Enforced aspect ratios per block, `object-fit: cover` with an admin-set focal point, AVIF/WebP, blurhash LQIP so nothing pops in. |
| Slider is heavy, laggy, plugin-driven | Lightweight custom hero: one poster image (or muted looping video), Ken-Burns drift, crossfade, preloaded next slide, no plugin bundle. |
| Hover-only interactions, weak on touch | Every interaction has a touch equivalent; cards reveal metadata by default on mobile. |
| No dark mode, no theming | Full dark/light + accent + pattern system from §7. |
| Stat counters and reveals fire on every scroll | `IntersectionObserver`, animate once, disabled under `prefers-reduced-motion`. |
| Mobile is a shrunken desktop | Mobile-first layouts that are genuinely redesigned per breakpoint — see §8. |
| Deep, confusing 3-level mega menu | Two levels max in the primary nav, with a full-screen overlay menu on mobile and an optional mega-panel on desktop. Nav structure is admin-editable. |

### 9.3 Homepage section order (all sections toggleable and reorderable from the admin panel)

1. **Announcement bar** *(optional)* — thin, dismissible, admin text + link.
2. **Header** — transparent over the hero, solidifies to a frosted bar on scroll. Logo, nav, theme toggle, phone/WhatsApp CTA. Mobile: full-screen overlay menu with staggered link reveal.
3. **Hero** — full-viewport (`100svh`, not `100vh` — avoids the mobile URL-bar jump). Slides from `hero_slides`. Split headline where a single kicker word (`design` / `quality` / `studio`) swaps with a masked slide-up transition. Subtle dark scrim so text always passes contrast over any client photo. Scroll-hint indicator. Layout variants selectable in Appearance (§7.3 *Hero style*).
4. **Principal's note** — portrait + short signed statement + name/role. Quiet, editorial, lots of margin.
5. **Studio introduction** — long-form SEO copy in a constrained measure (65–75ch) beside a tall image; the image parallaxes slightly on desktop, static on mobile.
6. **Client logo wall** — grayscale logos that colourise on hover; auto-scrolling marquee on mobile, static grid on desktop, marquee pauses on hover/focus.
7. **Portfolio categories** — three (or N) large tiles with numeric index `01 / 02 / 03`, image zoom-on-hover behind a gradient, title and count. On mobile: full-width stacked cards with a persistent label.
8. **Services** — 3–6 cards with line icons, hairline dividers rather than boxes, hover lifts the card and draws the accent rule.
9. **Stats band** — animated counters on an accent or image background, count-once.
10. **Featured projects** — the centrepiece. Editorial/masonry/uniform grid per the Appearance setting. Card = image, project title, category chip, year. Hover: slow image scale + caption slide-up + accent underline. Filter chips by category with an animated layout transition; keyset "Load more" rather than pagination numbers.
11. **Full-bleed CTA band** — big statement line + "Get in touch", over an image with a duotone accent treatment.
12. **Core values** — numbered list, two columns on desktop, revealed in sequence.
13. **Team** — see §9.5.
14. **Testimonials** — quote-led slider, one at a time, large serif quote marks, swipeable on mobile.
15. **Awards & publications** — logo/date rows.
16. **Latest journal posts** — three cards with date, read-time, tag.
17. **FAQ** — accordion, single-open, animated height, `FAQPage` JSON-LD.
18. **Contact strip + footer** — address, map link, phone, WhatsApp, socials, newsletter, and a compact enquiry form. Footer: large wordmark, three link columns, legal row.

### 9.4 Routes
`/` · `/work` (filterable, keyset-paginated) · `/work/[category]` · `/work/[slug]` · `/services` · `/services/[slug]` (supports sub-services) · `/about` · `/team` · `/process` · `/clients` · `/awards` · `/journal` · `/journal/[slug]` · `/faqs` · `/contact` · `/privacy`, `/terms` · `/sitemap.xml`, `/robots.txt`, `/opensearch` optional.

### 9.5 Project detail page — make this the showpiece
Full-bleed hero image with the title overlaid in large display type · a spec sheet rail (client, location, area, year, category, services rendered, status) that sticks on desktop scroll · long-form narrative in a constrained measure · masonry gallery opening into a **lightbox** with keyboard arrows, swipe, pinch-zoom, counter, caption, and preloading of the neighbouring image · optional before/after slider · video section (HLS or embed) with poster-first, muted, no autoplay-with-sound · pull-quote block · related projects · large prev/next project links with background image preview on hover · sticky mobile "Enquire about this project" bar that prefills the contact form with the project name.

### 9.6 Team page
Grid of portraits with a duotone accent treatment that resolves to full colour on hover/tap · name, role in brackets (a nice touch the reference uses), socials · click opens a detail panel with bio · a wide team group photo band.

### 9.7 Motion language (defaults — all overridable per §7.4)
These are the values the *Refined* preset ships with, and what every component must read from CSS variables rather than hardcode: fade-and-rise reveals of 12–16 px over 500–700 ms with `cubic-bezier(0.16, 1, 0.3, 1)` · stagger of 60–80 ms within a group · image scale 1.0 → 1.04 over 700 ms on hover · masked text reveals for headings only, never body copy · page transitions with a brief accent wipe. If a component contains a literal duration, easing, or translate distance, it is a bug — it belongs in the motion config. Guardrails in §7.4.3 apply regardless of settings.

Rendering: static/ISR for public pages, revalidated by tag on publish. Admin is fully dynamic and `noindex`.

---

## 10. Security

Argon2id passwords · rate limiting on login, contact form, upload · CSRF on all mutating routes · file uploads validated by **magic bytes**, not extension · strip EXIF GPS from photos · signed URLs for private assets · CSP with nonce, `X-Frame-Options: DENY`, HSTS · admin session idle timeout · every tenant-scoped query filtered by `tenant_id` in the repository layer, with an integration test proving cross-tenant reads return nothing · no secrets in the client bundle — audit `NEXT_PUBLIC_*` usage.

---

## 11. Build phases and verification gates

Do not skip a gate. **Run the tests. Do not report "done" without pasting real command output.**

| Phase | Deliverable | Gate |
|---|---|---|
| **0** | Repo scaffold, pnpm, TS strict, ESLint (incl. layer-boundary rule), Prettier, Vitest, Playwright, Husky, `.gitignore`, `.env.example`, `README.md`, `Dockerfile`, `docker-compose.yml`, `.vscode/settings.json` | `pnpm lint && pnpm typecheck` clean; layer-boundary rule fails on a deliberate bad import |
| **1** | `config/app.config.ts` with all numeric drivers + comments; Zod validation; boot-time failure messages | Unit tests: valid config parses; each invalid case throws the right message |
| **2** | Domain layer + repository interfaces + `Result` type + policies | Unit tests for `ContentVisibilityPolicy` and `RolePolicy`, all branches |
| **3** | Drizzle schema + migrations + indexes + seed (admin from env, demo tenant, 6 demo projects) | `pnpm db:migrate && pnpm db:seed` succeeds; `EXPLAIN` on the project-list query shows an index scan |
| **4** | Drizzle repositories + mappers + Unit of Work | Integration tests against a throwaway Postgres (testcontainers or a Neon branch), including a cross-tenant isolation test |
| **5** | Storage ports + factory + adapters (local, S3, R2, Supabase, Google Drive, Bunny Stream) | Contract test suite run against every adapter; local + S3 (MinIO) run in CI, remote ones behind flags |
| **6** | Auth.js, admin bootstrap, roles, sessions, rate limiting | e2e: admin logs in, editor cannot reach `/admin/users`, editor cannot publish |
| **7** | Use cases + API routes / server actions, all with Zod validation and `ActorContext` | Integration tests per use case incl. authorisation failures |
| **8** | Theme engine: accent registry (24), pattern registry (22), opacity, SSR CSS-var injection, no-FOUC script, **motion engine + all §7.4 registries as CSS vars and shared config** | Visual snapshot of every accent × light/dark; contrast assertion test on all accents; test that `prefers-reduced-motion` neutralises every preset; grep test that no component hardcodes a duration/easing/translate |
| **9** | Public site: all §9.3 homepage sections, all §9.4 routes, project detail (§9.5), team (§9.6), motion system (§9.7), ISR, SEO, JSON-LD, sitemap | Lighthouse CI mobile ≥ 95 perf / 100 a11y / 100 SEO on `/`, `/work`, `/work/[slug]`; screenshots at 390/768/1280/1920 for every homepage section |
| **10** | Admin panel: dashboard, projects CRUD, media upload + drag-reorder, hero slides, services, stats, values, clients, testimonials, awards, process, FAQs, journal posts, members, categories, users/invites, inquiries, nav builder, **homepage section builder** (toggle + reorder), settings, **Appearance with live preview** | e2e happy path: create project → upload 3 images → set cover → publish → appears on the public site; reorder a homepage section and see it move |
| **11** | Verification workflow + approvals queue + diff view + audit log | e2e: editor submits → invisible publicly → admin approves → visible; toggle OFF → editor publishes directly |
| **12** | Licence + deployment mode + multi-tenant host resolution + billing hooks (Stripe/Paddle, SaaS mode only) | Tests: expired perpetual key → admin read-only, public site still serves; unknown host → 404 not a leak |
| **13** | Mobile polish pass + a11y audit + docs (`README`, `DEPLOYMENT.md`, `CLIENT_HANDOVER.md`, `THEMING.md`) | Manual review at 390/768/1280/1920 with screenshots; axe-core clean; full `pnpm test && pnpm test:e2e` green |

---

## 12. README requirements

The `README.md` must include: what the product is · screenshots placeholder · stack table · quick start (`pnpm i` → copy `.env.example` → `pnpm db:migrate` → `pnpm db:seed` → `pnpm dev`) · **full env var table with the numeric driver legends repeated** · how to switch storage drivers · how to add a new storage adapter · deployment guides for Vercel and Docker/VPS · how to point a client domain · backup & restore · how to change admin credentials · the Google Drive video caveat from DEC-2 · troubleshooting · licence.

Also ship `CLIENT_HANDOVER.md` — a **non-technical** 2-page guide for the studio owner: how to log in, add a project, upload photos, add a team member, approve an editor's work, change the accent colour and pattern.

## 13. .gitignore requirements

`node_modules/`, `.next/`, `out/`, `build/`, `dist/`, `.env*` (but **not** `!.env.example`), `*.log`, `.DS_Store`, `Thumbs.db`, `coverage/`, `playwright-report/`, `test-results/`, `.turbo/`, `.vercel/`, `drizzle/meta/_journal.lock`, `uploads/` (local storage driver output), `*.pem`, `*-service-account.json`, `.idea/`, `.vscode/*` except `settings.json` / `extensions.json`.

---

## 14. Working agreement for Claude Code

- Build phase by phase; stop at each gate and report.
- **No comment clutter.** Comment only non-obvious *why* — the config file driver legends are the deliberate exception.
- Never leave a `TODO` in place of working code. If something can't be finished, say so explicitly in the phase report.
- If a requirement here conflicts with something you discover while building, **stop and ask** rather than silently substituting.
- Prefer boring, readable code over clever code. Someone else will maintain this.
- Every phase report includes: what was built, the commands you ran, their real output, and anything you assumed.
