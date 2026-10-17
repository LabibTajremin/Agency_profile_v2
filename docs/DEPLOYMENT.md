# Deploying QE Edu to a live WordPress site

This guide takes you from "I have the two zip files" to a fast, secure, live website — with
the demo content seeded so you start from a finished-looking site instead of a blank page.

There are two routes. Pick one:

- **Route A — WordPress dashboard only** (no technical tools; works on any host).
- **Route B — WP-CLI / SSH** (for developers, staging pipelines and repeatable installs).

Both end in the same place. Seeding the demo is covered in [step 6](#6-seed-the-demo-data).

---

## Contents

1. [Before you start: hosting checklist](#1-before-you-start-hosting-checklist)
2. [Get the installable files](#2-get-the-installable-files)
3. [Install WordPress](#3-install-wordpress)
4. [Basic WordPress settings](#4-basic-wordpress-settings)
5. [Install the theme, Elementor and QE Edu Core](#5-install-the-theme-elementor-and-qe-edu-core)
6. [Seed the demo data](#6-seed-the-demo-data)
7. [Make it yours](#7-make-it-yours)
8. [Email (SMTP) so form notifications arrive](#8-email-smtp-so-form-notifications-arrive)
9. [HTTPS, caching and performance](#9-https-caching-and-performance)
10. [Security hardening](#10-security-hardening)
11. [Search engines and analytics](#11-search-engines-and-analytics)
12. [Backups](#12-backups)
13. [Staging → production (moving a site)](#13-staging--production-moving-a-site)
14. [Updating the theme and plugin](#14-updating-the-theme-and-plugin)
15. [Go-live checklist](#15-go-live-checklist)
16. [Troubleshooting deployment](#16-troubleshooting-deployment)

---

## 1. Before you start: hosting checklist

| Requirement | Minimum | Recommended |
|---|---|---|
| WordPress | 6.6 | latest (tested up to 7.1) |
| PHP | 8.1 | 8.2 or 8.3 with OPcache on |
| Database | MySQL 5.7 / MariaDB 10.4 | MySQL 8 / MariaDB 10.6+ |
| PHP memory limit | 128 MB | 256 MB (Elementor editor is happier) |
| `max_execution_time` | 60 s | 120 s (the demo import copies ~60 images) |
| `upload_max_filesize` | 8 MB | 32 MB (the zips are small; this is for your own media) |
| PHP extensions | `gd` or `imagick`, `mbstring`, `zip`, `curl`, `json` | + `intl` |
| HTTPS | required for a live site | free Let's Encrypt certificate from your host |

Any mainstream managed WordPress host meets these (most let you change PHP version and
limits in their control panel). You also need:

- a domain name pointed at the host (DNS `A`/`AAAA` record or the host's nameservers);
- an email account or SMTP service for outgoing mail (see [step 8](#8-email-smtp-so-form-notifications-arrive)).

> **Tip:** build on a **staging** copy first (most hosts create one with one click), then
> push it live. See [step 13](#13-staging--production-moving-a-site).

---

## 2. Get the installable files

You need two zips:

| File | What it is | Where it goes |
|---|---|---|
| `qe-edu.zip` | the theme | *Appearance → Themes* |
| `qe-edu-core.zip` | the companion plugin (widgets, settings, SEO, forms, **demo data**) | *Plugins* |

If you received them, skip ahead. To build them from this repository:

```bash
npm ci
composer install
npm run zip          # builds CSS/JS, then writes dist/qe-edu.zip and dist/qe-edu-core.zip
```

Do **not** use GitHub's "Download ZIP" of the whole repository — WordPress needs the two
separate packages above.

---

## 3. Install WordPress

**Route A:** use your host's "Install WordPress" button (one-click installer). Choose:

- the final domain with `https://`;
- a strong admin username (not `admin`) and password;
- the site language.

**Route B:**

```bash
wp core download --locale=en_GB
wp config create --dbname=DB_NAME --dbuser=DB_USER --dbpass='DB_PASS' --dbhost=localhost
wp core install --url=https://www.your-domain.com --title="Your Organisation" \
  --admin_user=your-admin --admin_password='a-long-random-password' \
  --admin_email=you@your-domain.com --skip-email
```

---

## 4. Basic WordPress settings

Do these once, before importing anything:

1. **Settings → General** — Site Title (used in page titles and SEO), Tagline, Timezone
   (event dates use it), date format.
2. **Settings → Permalinks** — choose **Post name** and **Save**. Pretty URLs are needed
   for `/destinations/canada/`, the sitemap and redirects.
3. **Settings → Reading** — leave *Search engine visibility* **unticked** on the live site
   (tick it only on staging).
4. Delete the sample "Hello world!" post and "Sample Page" if you like (optional).

Route B equivalent:

```bash
wp option update timezone_string "Europe/London"
wp rewrite structure '/%postname%/' --hard
wp option update blog_public 1        # 0 on staging
```

---

## 5. Install the theme, Elementor and QE Edu Core

### Route A — dashboard

1. **Appearance → Themes → Add New → Upload Theme** → choose `qe-edu.zip` → **Install
   Now** → **Activate**.
2. A notice appears listing the required plugins:
   - **Elementor** → click *Install*, then *Activate* (or *Plugins → Add New*, search
     "Elementor").
   - **QE Edu Core** → *Plugins → Add New → Upload Plugin* → choose `qe-edu-core.zip` →
     **Install Now** → **Activate Plugin**.
3. A **Theme Settings** item appears in the left menu.

### Route B — WP-CLI

```bash
wp plugin install elementor --activate
wp plugin install /path/to/qe-edu-core.zip --activate
wp theme install /path/to/qe-edu.zip --activate
```

Install order doesn't matter, but Elementor must be active **before** seeding the demo.

---

## 6. Seed the demo data

The demo turns the empty install into a complete, realistic education-agency website for
a fictional organisation, **Brightway Education Group**:

| Content | Amount |
|---|---|
| Pages built with Elementor (Home, About, What We Do, Study Abroad, International Pathways, Language Academy, ELP Test, Partner With Us, Partner Institutions, Recruitment Partners, Study Mobility Program, Careers, Events & Webinars, News, Contact, Privacy Policy, Payment Terms, Refund Policy) | 18 |
| Header, footer, 404, single-post and archive templates | 5 |
| Study destinations, each with cities, typical tuition, intakes and post-study work rights | 16 |
| Services | 4 |
| Partner universities and recruitment partners | 12 |
| Upcoming events (dated relative to the import day, so they are always in the future) | 3 |
| Team members with bios | 4 |
| Student testimonials | 3 |
| News articles with full text, categories and dates spread over the last weeks | 6 |
| Menus (main, top bar, three footer columns, legal) | 6 |
| Original images (no stock licences needed) + a sample company-profile PDF | 56 |

It also fills **Theme Settings** (logo, contact details, social links, header button,
SEO defaults) and sets the home and news pages.

**It is safe to run on a site that already has content:**

- nothing you already have is changed — pages with the same address are kept as they are,
  and settings you've already filled in are left alone;
- running it again never creates duplicates (each item is tagged and recognised);
- you can import the whole site or just one page.

### Option 1 — seed from the WordPress dashboard

1. Go to **Theme Settings → Tools**.
2. In the **Import the demo** card, under **What to import**, keep
   **The whole site (recommended)** — or pick a single page under *Or just one page*.
3. Click **Import demo**. It takes up to a minute (images are copied into the Media
   Library). Don't close the tab.
4. You'll see *"Demo imported: N items added, M already there"* and a **View your site**
   link.

![The "Import the demo" card](images/import-demo.png)

If the button is greyed out, Elementor isn't active yet — activate it and reload.

### Option 2 — seed with WP-CLI

```bash
# Whole site
wp qe-edu demo-import

# A single page (and the images/posts it needs)
wp qe-edu demo-import --page=contact
```

Page keys: `home about what-we-do study-abroad international-pathways language-academy
elp-test partner-with-us partner-institutions recruitment-partners study-mobility-program
careers events news contact privacy-policy payment-terms refund-policy`.

The command prints how many items were added and how many were already there, so it is
safe to keep in a deploy script — repeated runs add nothing.

### After seeding

- Visit the home page. If you use a caching plugin or host cache, **purge it** once.
- If a page looks unstyled, go to **Elementor → Tools → Regenerate CSS & Data** and
  purge the cache.
- Restore a demo page you deleted by importing again, or import just that page.
- The demo's **email address is a mailbox on your own domain** (`admissions@your-domain`),
  and the phone numbers (`+44 20 7946 0321`, `+44 7700 900482`) and London address are
  fictional placeholders from ranges reserved for drama/test use. **Replace them** in
  *Theme Settings → Contact & Social* before going live (step 7).

### Removing demo content later

Demo items are normal WordPress content. Delete what you don't need from *Pages*, *Posts*,
*Destinations*, *Partners*, etc. and the *Media Library* (demo images are named
`qe-demo-…`). Nothing else depends on them.

---

## 7. Make it yours

Work through these in **Theme Settings**:

1. **Brand** — organisation name, upload your **logo** (and a light version for dark
   backgrounds). Keep **"Use the logo as the site icon (favicon)"** ticked to get a
   favicon automatically, or upload a separate square icon.
2. **Contact & Social** — real phone, WhatsApp, email and address (used in the header, footer,
   mobile bar, contact page and SEO schema).
   Add your social profile links on the same tab (empty ones are hidden).
4. **Colours / Typography** — pick an accent palette or your brand colour; it syncs to
   Elementor's global colours.
5. **Header / Footer** — button text and link, sticky header, top bar.
6. Replace demo text and images page by page with **Edit with Elementor**, and edit the
   destinations, partners, team and testimonials under their own menu items.
7. **Settings → Privacy** — review the imported Privacy Policy, Payment Terms and Refund
   Policy with your legal adviser. They are templates, not legal advice.

The README's [How to edit your site](../README.md#how-to-edit-your-site) has a guide for
every common task.

---

## 8. Email (SMTP) so form notifications arrive

Registration and contact forms save every submission as a **Lead** (*Leads* menu, with
CSV export) and email a notification. Many hosts block or spam-folder PHP's default mail,
so send through a real mail service:

1. Install an SMTP plugin (for example *WP Mail SMTP* or *FluentSMTP*).
2. Connect it to your mailbox or a transactional service (Google Workspace, Microsoft 365,
   Amazon SES, Brevo, Mailgun, Postmark…).
3. Add the **SPF**, **DKIM** (and ideally **DMARC**) DNS records the service gives you.
4. Send a test email from the plugin, then submit the demo Contact form and check both the
   inbox and *Leads*.

---

## 9. HTTPS, caching and performance

1. **HTTPS** — enable the free certificate in your host panel, then make sure
   *Settings → General* uses `https://` for both addresses. Force HTTPS redirects at the
   host or in `.htaccess`/nginx.
2. **Page cache** — turn on your host's cache, or install one caching plugin (e.g.
   *WP Super Cache*, *W3 Total Cache*, *LiteSpeed Cache* on LiteSpeed servers). Use only one.
3. **Compression** — gzip or Brotli on (most hosts do this by default).
4. **PHP** — 8.2+ with OPcache.
5. **Theme Settings → Performance** — the built-in optimisations (deferred scripts, jQuery
   in the footer, hero image preload, lazy images) are on by default; leave them on unless
   another plugin already does the same thing.
6. **Elementor → Settings** — keep *CSS print method* on *External file*.
7. Optional: a CDN (Cloudflare or your host's) for images and static files.

With a page cache and compression the demo scores 93–98 performance and 100 for
accessibility and SEO in Lighthouse (mobile).

---

## 10. Security hardening

- Strong, unique passwords; enable two-factor login for administrators (a 2FA plugin or
  your host's feature).
- Keep WordPress, PHP, Elementor, the theme and plugins up to date.
- Remove plugins and themes you don't use.
- Give editors the *Editor* role, not *Administrator*.
- Add to `wp-config.php` on production:

  ```php
  define( 'DISALLOW_FILE_EDIT', true ); // no code editing from the dashboard
  define( 'WP_DEBUG', false );
  define( 'WP_DEBUG_DISPLAY', false );
  ```

- File permissions: folders `755`, files `644`, `wp-config.php` `600` (or `640`).
- Consider a firewall / login-limit plugin or your host's WAF.
- Leads contain personal data: limit who can see them, export and delete per your privacy
  policy.

---

## 11. Search engines and analytics

The built-in SEO module covers titles, meta descriptions, Open Graph, schema, XML sitemap,
robots.txt, redirects and a 404 log. (If you already use Yoast SEO or Rank Math, it steps
aside automatically.)

1. **Theme Settings → SEO** — home title and description, default social image.
2. Make sure *Settings → Reading → Discourage search engines* is **off**.
3. Submit `https://your-domain.com/wp-sitemap.xml` to Google
   Search Console and Bing Webmaster Tools; paste their verification codes in Theme
   Settings → SEO.
4. **Theme Settings → SEO** — add your GA4 *or* GTM ID (not both), and turn on the consent
   bar if your audience requires it.

---

## 12. Backups

Before going live, set up automatic backups of **files and database**:

- use your host's daily backups **and** an off-site copy (e.g. *UpdraftPlus* to cloud
  storage), keep at least 14 days;
- take a manual backup before every update;
- test a restore once on staging.

WP-CLI:

```bash
wp db export backup-$(date +%F).sql
tar -czf uploads-$(date +%F).tgz wp-content/uploads
```

Theme settings can also be exported separately: **Theme Settings → Tools → Export**
(a JSON file you can import on another site).

---

## 13. Staging → production (moving a site)

**With your host's staging tool:** build and seed on staging, then use "Push to live".

**With a migration plugin** (*Duplicator*, *All-in-One WP Migration*, *Migrate Guru*):
export from staging, import on production — they rewrite URLs for you.

**Manually / WP-CLI:**

```bash
# on staging
wp db export site.sql
tar -czf wp-content.tgz wp-content

# on production (WordPress installed, same table prefix)
tar -xzf wp-content.tgz
wp db import site.sql
wp search-replace 'https://staging.your-domain.com' 'https://www.your-domain.com' --all-tables --precise
wp option update blog_public 1
wp rewrite flush
wp elementor flush-css    # or Elementor → Tools → Regenerate CSS & Data
wp cache flush
```

Alternatively, on a fresh production site just install the zips and run
`wp qe-edu demo-import` again, then copy your settings with **Theme Settings → Tools →
Export / Import**.

After moving: re-save *Settings → Permalinks*, purge caches, and check forms send email.

---

## 14. Updating the theme and plugin

1. Back up (step 12).
2. Build or obtain the new `qe-edu.zip` / `qe-edu-core.zip`.
3. **Appearance → Themes → Add New → Upload Theme** with the new zip → choose
   **Replace current with uploaded**. Same for the plugin in *Plugins → Add New → Upload
   Plugin*.
   WP-CLI: `wp theme install qe-edu.zip --force` and
   `wp plugin install qe-edu-core.zip --force --activate`.
4. Purge caches and regenerate Elementor CSS.

Your content, settings and leads live in the database and are not touched by updates.
Put code customisations in a **child theme** (see the README) so updates never overwrite
them.

---

## 15. Go-live checklist

- [ ] HTTPS works, `http://` redirects to `https://`
- [ ] Permalinks set to *Post name*
- [ ] Demo seeded; demo contact details, people and logos replaced with yours
- [ ] Logo and favicon show in the browser tab
- [ ] Contact and registration forms: email received **and** lead saved
- [ ] Privacy / payment / refund pages reviewed
- [ ] *Discourage search engines* is **off**; sitemap submitted
- [ ] Analytics ID added (and consent bar if needed)
- [ ] Page cache + compression on; Lighthouse checked on a phone profile
- [ ] Backups scheduled and one restore tested
- [ ] Admin accounts use strong passwords + 2FA; `DISALLOW_FILE_EDIT` set
- [ ] Checked on a phone: menu, sticky Apply/WhatsApp bar, forms

---

## 16. Troubleshooting deployment

| Problem | Fix |
|---|---|
| "The link you followed has expired" when uploading a zip | Raise `upload_max_filesize` and `post_max_size` (host panel), or upload via SFTP to `wp-content/themes` / `wp-content/plugins` and unzip there. |
| Import demo button greyed out | Activate Elementor, then reload *Theme Settings → Tools*. |
| Demo import stops or times out | Raise `max_execution_time` to 120 and memory to 256M, then run it again — finished items are skipped, so it continues where it stopped. Or use `wp qe-edu demo-import`. |
| Pages show 404 | *Settings → Permalinks* → **Save** (flushes rewrite rules). On nginx make sure `try_files $uri $uri/ /index.php?$args;` is set. |
| Pages look unstyled after a move | *Elementor → Tools → Regenerate CSS & Data*, then purge every cache. |
| Mixed-content warnings (padlock missing) | Run the `search-replace` from step 13 from `http://` to `https://`. |
| Form emails don't arrive | Set up SMTP (step 8); leads are still saved under *Leads*. |
| Changes don't appear for visitors | Purge the page cache / CDN. |
| Images missing after a move | Copy `wp-content/uploads` too, not only the database. |

More fixes are in the README's [Troubleshooting](../README.md#troubleshooting) section.
