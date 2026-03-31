=== Lukic Code Snippets ===
Contributors: wplukic
Donate link: https://www.paypal.com/paypalme/lukicwebdesign
Tags: snippets, performance, admin, security, seo
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 2.9.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modular performance toolkit: 40+ code snippets you toggle on and off — zero bloat when inactive.

== Description ==

**Lukic Code Snippets** replaces a dozen single-purpose plugins with one lightweight dashboard. Every snippet is loaded conditionally — if it is toggled off, its file is never included, giving you **zero performance impact** for inactive features.

= What's Included =

**Admin Interface**
* Admin Bar Site Visibility Indicator
* Wider Admin Menu
* Show IDs in Admin Tables
* Show Featured Images in Admin Tables (with inline AJAX upload)
* Clean Dashboard
* Hide Admin Bar on Frontend
* Hide Admin Notices (collapsible panel)
* Custom Admin Footer Text
* Show ACF Fields in Admin Tables
* Show Custom Taxonomy Filters
* Show Current Template
* Admin Menu Organizer (drag-and-drop)
* Show Active Plugins First
* Word Counter
* Login Page Designer (logo, background, colors, button, custom CSS, live preview)

**Content Management**
* Enable Classic Editor
* Post & Page Duplicator
* Content Order (drag-and-drop reordering)
* Search Posts by Slug

**Media Management**
* SVG Upload Support (with sanitization)
* Media Replacement (same ID, filename, and links)
* Media Size Column
* Image Sizes Panel
* User Profile Image (local avatar upload)

**SEO & Performance**
* Hide WP Version
* Disable XML-RPC
* Meta Tags Editor (title & description per post/page/taxonomy)
* Redirect Manager (301/302/307/308 with hit tracking and wildcard patterns)
* Fluid Typography Calculator

**Security**
* Custom Login URL
* Hide Author Slugs
* Security Headers Manager (CSP, HSTS, X-Frame-Options, etc.)

**Utility & Development**
* Maintenance Mode (customizable page with live preview)
* Limit Revisions (per post type)
* Custom Database Tables Manager (view, search, edit, export CSV)
* Image Attributes Editor (bulk edit alt text, title, caption)
* Last Login User (column in Users table)
* Disable Comments (preserves WooCommerce reviews)

= Key Design Principles =

* **Truly modular** — inactive snippets are never loaded (`require_once` inside toggle check)
* **Auto-save** — toggles save via AJAX instantly; no manual save required
* **Zero external requests** — all CSS/JS assets are bundled locally (DataTables, Magnific Popup)
* **One text domain** — every string uses `lukic-code-snippets` for full translation readiness
* **Clean uninstall** — choose to preserve or delete all data on plugin removal

== Installation ==

1. Upload the `lukic-code-snippets` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress Plugins screen.
2. Activate the plugin through the **Plugins** screen.
3. Navigate to **Code Snippets** in the admin sidebar.
4. Toggle any snippet on or off. Settings auto-save instantly.

== Frequently Asked Questions ==

= Will inactive snippets slow down my site? =

No. Each snippet file is loaded with `require_once` inside a conditional check. If a snippet is toggled off, its PHP file is never included — the overhead is a single `isset()` check per snippet.

= Will my settings be lost if I deactivate or delete the plugin? =

By default, all settings and custom tables are **preserved** when you deactivate or delete the plugin. If you want a clean removal, go to **Code Snippets → Settings** and select "Delete all plugin data when plugin is deleted."

= Does this plugin work with multisite? =

The plugin activates per-site in a multisite network. Each site has its own snippet toggles and settings.

= Is the database safe with the DB Tables Manager? =

Yes. The manager is read-only by default (view, search, export). Row editing is available for tables with a primary key, and all queries use `$wpdb->prepare()` with validated table and column names. There are no "Drop Table" or "Delete Row" buttons exposed.

= Can I translate this plugin? =

Yes. All UI strings use the `lukic-code-snippets` text domain with proper `__()` and `_e()` wrappers. A `/languages` directory is included and `load_plugin_textdomain()` is called on `init`.

== Screenshots ==

1. Main snippets dashboard with toggle switches and tag filtering.
2. Login Page Designer with live preview panel.
3. Redirect Manager with hit tracking and wildcard patterns.
4. Database Tables Manager showing table structure and data.
5. Maintenance Mode settings with customizable page and live preview.

== Changelog ==

= 2.9.1 =
* Architecture: Refactored Maintenance Mode CSS to completely separate static layout into `maintenance-public.css` and use native CSS custom properties for dynamic configuration values.

= 2.9.0 =
* Architecture: Migrated 40+ inline CSS/JS snippets to `wp_add_inline_style()` and `wp_add_inline_script()` for CSP compliance.
* Security: Hardened all AJAX handlers with `sanitize_text_field()` and proper nonce verification.
* Security: Fixed `PHP_INI_PERDIR` restriction on upload limits using WordPress native filters.
* Compliance: Updated admin dashboard CTAs (Guideline 11) for repository standard.
* Performance: Fixed documentation search logic and AJAX button response classes.
* Fix: Resolved self-signed certificate issues in internal diagnostic tools.

= 2.8.1 =
* Fix: Redirect Manager settings now save properly when toggled off.
* Fix: Prevent WordPress canonical redirects from exposing the hidden Custom Login URL.
* Fix: Maintenance Mode IP exclusions now correctly handle local loopback URLs (::1 and 127.0.0.1) and varied string formats.
* Fix: Add missing transients to the clean uninstallation routine.
* Docs: Clarify Redirect Manager trailing slash URL usage rules.

= 2.8.0 =
* New: Login Page Designer snippet — full visual customization of the WordPress login page (logo, background, form card, colors, button, custom CSS) with a real-time live preview panel.
* Security: Added `$wpdb->prepare()` for all LIMIT clauses in the Database Tables Manager.
* Security: Fixed redirect delete bug caused by table name case mismatch.
* Security: Escaped all dynamic class and `number_format()` output.
* Fix: `Lukic_Helpers::is_plugin_admin_page()` now correctly matches lowercase page slugs.

= 2.7.6 =
* New: AJAX-powered featured image uploading and editing in admin tables.

= 2.7.5 =
* Performance: Fixed translation loading warning; added registry caching; deferred admin class init.
* Fix: Standardized text domain globally.

= 2.7.0 =
* New: Disable Comments snippet (preserves WooCommerce reviews).
* New: Show Active Plugins First snippet.
* New: User Profile Image snippet.
* New: Admin Menu Organizer and Hide Author Slugs snippets.

= 2.3.0 =
* New: Centralized snippet registry; Maintenance Mode on/off switch.
* Improvement: Asset Manager, Security Headers UI, DataTables unification.

= 2.2.0 =
* New: Database Tables Manager with search, pagination, row editing, CSV export.

= 2.1.0 =
* New: Auto-save for snippet toggles with intelligent page refresh.

= 2.0.0 =
* Major: Architectural overhaul — CSS framework, Asset Manager, reusable header component, Helpers class.

= 1.6.0 =
* New: Security Headers, Limit Revisions, Image Sizes Panel, Redirect Manager, DB Tables Manager.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 2.8.0 =
Security hardening: SQL injection vectors fixed in DB Tables Manager, redirect delete bug fixed. Recommended update for all users.
