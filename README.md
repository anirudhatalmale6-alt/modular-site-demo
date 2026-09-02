# A small, modular website with widgets

**▶ Click through the preview:**
<https://anirudhatalmale6-alt.github.io/modular-site-demo/>

That preview is a flat copy of the real pages, so the design, the layout and
every link are exactly what the running site produces — but with no PHP behind
it, posting a comment is switched off there. The working version (moderation
queue and all) is the code in this repository; it runs with one command, below.

A flat-file site: **About**, **Contact**, a **photo gallery**, a **memorandum
archive** and a **moderated comments section**, built so that new pages and new
widgets can be added later without touching any core code.

No database server, no framework, no build step, no plugin updates. PHP 8 and a
folder of files. It runs on any shared host (cPanel, Plesk, LAMP) and on a
laptop with one command.

---

## Run it locally

```bash
php -S localhost:8080 router.php
```

Then open <http://localhost:8080>. The admin is at
<http://localhost:8080/admin/> — the demo password is `demo1234`.

To fill the demo with example comments and messages:

```bash
php seed-demo-data.php
```

(Delete `seed-demo-data.php` before a real site goes live.)

## Put it on a host

1. Upload everything to the web root.
2. Make `data/` and `content/` writable by the web server (`chmod 775`).
3. Set your own admin password:
   ```bash
   php admin/make-password.php "your new password"
   ```
   Paste the hash it prints into `config.php`.
4. Edit the top of `config.php` — site name, tagline, address, opening hours.

Apache needs nothing further; `.htaccess` is included. For nginx:

```nginx
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ ^/(data|core)/ { deny all; }
location ~ \.(md|db|sqlite)$ { deny all; }
```

---

## How it is laid out

```
config.php              every setting, in one file
index.php               front controller: works out what was asked for
router.php              only used by PHP's built-in server

core/                   the engine — you should rarely need to open these
    bootstrap.php       loads everything, defines e() url() render_body()
    Content.php         reads pages, memoranda and albums off disk
    Markdown.php        a small Markdown subset
    Widgets.php         the widget registry — the extension point
    Comments.php        storing, listing and moderating comments
    ContactForm.php     validation and the admin inbox
    Security.php        CSRF, honeypot, time trap, rate limit, spam score
    Db.php              SQLite connection and schema

content/                YOUR CONTENT — this folder is the site
    pages/*.md          one file per page
    memoranda/*.md      one file per filed document
    gallery/<album>/    photographs + album.json for titles and captions

widgets/                one file per widget; drop one in, it exists
    comments.php  gallery.php  memoranda.php  contact-form.php  notice.php

themes/civic/           layout.php, style.css and one view per page type
admin/                  the back office (moderation, editing, uploads)
data/site.db            comments and messages (SQLite, created on first run)
```

Everything a normal week needs is in `content/`. Everything a redesign needs is
in `themes/`. `core/` is the part you can ignore.

---

## Adding a page

Through the admin: **Pages → New page**. Or by hand — create
`content/pages/history.md`:

```markdown
---
title: Our History
summary: Fifty years on the ridge.
nav: yes
order: 60
---

## The beginning

Text here, in Markdown.

[[comments]]
```

Save it. `/history` now exists, it is in the menu, and it has its own moderated
comments section. Nothing else was edited and nothing was rebuilt.

Front-matter fields: `title`, `summary`, `eyebrow`, `nav` (yes/no),
`order` (menu position), `draft` (yes hides it). Memoranda also take `ref`,
`date`, `author`, `tags` and `file`.

---

## Widgets

Type these anywhere in a page or memorandum body:

| Tag | What appears |
|---|---|
| `[[comments]]` | Moderated comments for that page |
| `[[comments thread="noticeboard"]]` | A named thread several pages can share |
| `[[gallery]]` | All albums, as cards |
| `[[gallery album="summer-fete" columns="4" limit="8"]]` | One album |
| `[[memoranda limit="3"]]` | The most recent filed documents |
| `[[memoranda tag="minutes"]]` | Filtered by subject |
| `[[contact-form title="Write to us"]]` | The contact form |
| `[[notice kind="alert" text="..."]]` | A highlighted notice |

### Writing a new one

Create `widgets/hours.php`:

```php
<?php
Widgets::register('hours', function (array $options): string {
    $days = htmlspecialchars($options['days'] ?? 'Tuesday and Thursday');
    return '<p class="notice">Office open ' . $days . '</p>';
});
```

`[[hours days="Monday to Friday"]]` now works on every page. There is no
registry to update and no core file to edit — the folder *is* the registry.

---

## The comments system

- Every comment is held for moderation. Nothing is public until it is approved.
- The admin queue has **Approve**, **Send back**, **Mark as spam** and
  **Delete**, plus a public reply marked "from the committee".
- Set `comments_auto_approve => true` in `config.php` to publish immediately
  instead (the spam filter still runs).

### Spam defence, in layers

| Layer | What it stops |
|---|---|
| Honeypot field | Bots that fill in every input they find |
| Time trap (3s minimum) | Anything that submits faster than a person can type |
| CSRF token | Posts from outside your own pages |
| Rate limit (5 per IP per 10 min) | Flooding |
| Content scoring | Links, BBCode, spam vocabulary, shouting, vowel-less names |
| Moderation | Everything else |

A submission scoring 5 or more is filed straight into the spam bin, and the bot
is shown the same polite thank-you a human sees — it is never told it was
caught, so it has nothing to tune against. The admin shows you exactly which
rules fired, so a false positive is one click to rescue.

No reCAPTCHA: nothing to sign up for, no third-party cookies on your visitors,
and nothing for anyone to squint at. A real captcha can be added alongside this
later if traffic ever justifies it.

---

## Uploads

Photographs are checked three ways before they are kept: the extension, the
real image dimensions (a file that is not an image has none), and the file
size. The filename is rewritten, so `shell.php.jpg` cannot survive as anything
executable. Anything over 1600px on the long edge is scaled down so pages stay
fast.

---

## The admin

Sign in at `/admin/`. Screenshots of every screen are in
[`screenshots/`](screenshots):

| Screen | What it does |
|---|---|
| [Dashboard](screenshots/admin-dashboard.png) | What needs attention, and approve/spam in one click |
| [Moderation queue](screenshots/admin-moderation-queue.png) | Approve, send back, mark as spam, delete, reply publicly |
| [Spam bin](screenshots/admin-spam-bin.png) | What was caught, and exactly which rule caught it |
| [Pages](screenshots/admin-pages.png) | Every page, its address and whether it is in the menu |
| [Page editor](screenshots/admin-page-editor.png) | Title, body, summary, menu position, draft switch |
| [Photographs](screenshots/admin-gallery.png) | Create albums, upload, caption, delete |
| [How to add things](screenshots/admin-help.png) | The widget reference, generated from what is installed |

## Backing it up

Copy the folder. That is the whole backup: `content/` is the site,
`data/site.db` is the comments. There is no database export step and no
migration to run when it is put back.
