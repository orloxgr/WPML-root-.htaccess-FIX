WPML Root Rewrite Fix

Forces the WordPress .htaccess block to stay root-based (RewriteBase / and RewriteRule . /index.php [L]) even when WPML adds a language directory (e.g. /el) to home_url().
Works with “Default language in directory” enabled in WPML (so your URLs can remain /el/...) without breaking Apache routing.

If your site is on Apache and WPML sometimes causes .htaccess to contain /el/index.php, this plugin keeps it correct.

Why this exists

When WPML’s “default language in directory” is ON, it filters home_url() to include /el/. During programmatic permalinks flushes (cron, CLI, plugin updates), WordPress can then write:

RewriteBase /el/
RewriteRule . /el/index.php [L]


…which often yields 500 errors.
This plugin intercepts and corrects the generated rules and, as a safeguard, fixes the file on disk right after WordPress writes it.

What it does

Hooks mod_rewrite_rules (final text WP uses) and rewrites only the # BEGIN/END WordPress block to:

RewriteBase /
RewriteRule . /index.php [L]


After a hard flush, re-opens .htaccess on shutdown and force-corrects the block on disk (belt & suspenders).

Leaves any custom rules above/below the WordPress block untouched.

Compatible with WPML and “Default language in directory = ON” (e.g., /el/).

Requirements

Apache (or compatible) using .htaccess

WordPress installed at domain root (see configuration for subdirectory installs)

.htaccess writable by PHP when rules are flushed (for the post-write fix to apply)

Installation

Create folder:
wp-content/plugins/wpml-root-rewrite-fix/

Add file wpml-root-rewrite-fix.php with the plugin code.

Activate WPML Root Rewrite Fix in Plugins.

No settings screen. It just works.

Usage

Trigger any rewrite flush (any of the following):

Settings → Permalinks → Save Changes

wp rewrite flush --hard

Any plugin that calls flush_rewrite_rules(true)

Then open .htaccess and verify the WordPress block is root-based:

RewriteBase /
RewriteRule . /index.php [L]


Your front-end URLs can still be /el/... — WPML handles those; Apache should route to /index.php at root.

Configuration (optional)

If WordPress is physically installed in a subdirectory (not a WPML language dir), set the base:

// In the plugin file, before hooks:
if (!defined('WPRRF_BASE')) define('WPRRF_BASE', '/subdir/');


Default is '/'.

Before / After

Problematic (from programmatic flush with WPML):

RewriteBase /el/
RewriteRule . /el/index.php [L]


Correct (what this plugin enforces):

RewriteBase /
RewriteRule . /index.php [L]

FAQ

Q: I want default language under /el/. Is that okay?
Yes. Leave WPML → Languages → “Use directory for default language” ON. This plugin only normalizes Apache’s WordPress block to root; WPML still serves /el/... URLs.

Q: Will this affect custom rules I placed in .htaccess?
No. Only the core WordPress block between # BEGIN WordPress and # END WordPress is touched.

Q: Nginx/LSWS without .htaccess?
This is for Apache-style .htaccess. Nginx users should adjust server config; this plugin won’t apply.

Q: Multisite?
Designed and tested for single-site at domain root. For multisite/subdirectory networks, set WPRRF_BASE appropriately and test.

Troubleshooting

Ensure .htaccess is writable by PHP during a flush; otherwise the post-write pass can’t fix it.

Confirm WordPress General → Site Address (URL) is the root (e.g., https://example.com), not …/el.

If something else rewrites .htaccess after shutdown (very rare), trigger another flush; the filter also corrects the generated text.

Changelog

1.1.0 — Intercept rules text + post-write hardening on shutdown.

1.0.0 — Initial release (text interception).its

Built by Byron Iniotakis to keep .htaccess sane when WPML likes your default language a little too much.
