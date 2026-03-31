# Tasks to Prepare Plugin for WordPress.org Review

## 1. Scope and Goal
- This plan is based on the issues listed in `review.md`.
- The goal is to turn this plugin into a WordPress.org-safe build by removing policy blockers first, then refactoring the remaining snippets so they follow WordPress.org expectations for asset loading, security, escaping, and SQL handling.
- This is the wp.org-safe version of the plugin, so any feature that depends on changing core update behavior, defining global constants, or writing server config files should be removed instead of preserved.

## 2. Summary of Review Issues
- Policy blockers: the plugin currently includes features that disable WordPress updates, define `DISALLOW_FILE_EDIT`, and modify server/PHP settings via `ini_set()`, `.htaccess`, and `.user.ini`.
- Asset loading: several snippets print raw `<style>`, `<script>`, or `<link rel="stylesheet">` tags instead of using WordPress enqueue APIs.
- Core/bootstrap usage: one snippet directly loads `wp-login.php`.
- Security: several admin screens and upload flows read `$_GET`, `$_POST`, or `$_FILES` without the level of nonce, capability, validation, and sanitization the review expects.
- Escaping/output: some snippets echo generated CSS/HTML too early or without the proper context-aware escaping strategy.
- SQL: database-related snippets still build dynamic SQL fragments in ways the review team flagged as unsafe.
- Buffering/global behavior: one snippet uses `ob_start()` in a way the review team considers unsafe, and one snippet changes global WordPress behavior by defining a core constant at runtime.

## 3. Features/Snippets to Remove from the WordPress.org Version
- `includes/snippet-disable-all-updates.php`
  Reason: this snippet disables WordPress core/plugin/theme/translation updates and interferes with the built-in updater, which the review explicitly forbids.
- `includes/snippet-disable-file-editing.php`
  Reason: this snippet defines `DISALLOW_FILE_EDIT` at runtime and changes global site behavior. The review explicitly flagged this pattern.
- `includes/snippet-upload-limits.php`
  Reason: this snippet is too risky for the wp.org build because it combines multiple review blockers: global `ini_set()` calls, `ABSPATH`-based path handling, writing `.htaccess`, writing `.user.ini`, and trying to change server-level behavior from inside the plugin.
- `includes/snippet-login-page-designer.php` custom CSS freeform output
  Reason: the review specifically flagged raw output of user-controlled CSS. If strict CSS sanitization is not implemented, the safest wp.org path is to remove the arbitrary custom CSS subfeature while keeping the rest of the login designer.

## 4. Features/Snippets to Keep and Refactor
- `includes/snippet-custom-login-url.php`
  Keep the feature only if it is rewritten to stop loading `wp-login.php` directly. Replace the current bootstrap approach with normal WordPress routing/hooks.
- `includes/snippet-hide-admin-notices.php`
  Keep the notice panel idea, but move all CSS/JS output to registered/enqueued assets or `wp_add_inline_style()` / `wp_add_inline_script()`.
- `includes/snippet-show-template.php`
  Keep the admin bar helper, but move inline styles to a proper enqueue flow.
- `includes/snippet-maintenance-mode.php`
  Keep the feature, but replace direct `<link>` / `<style>` output with registered assets and inline CSS variables added through WordPress APIs.
- `includes/snippet-site-visibility.php`
  Keep the admin bar indicator, but stop printing raw `<style>` tags and move the styles into the enqueue system.
- `includes/snippet-media-size-column.php`
  Keep the feature, but move admin CSS to enqueued/inline styles.
- `includes/snippet-admin-menu-organizer.php`
  Keep the feature, but route generated CSS through a registered style handle and late escaping strategy.
- `includes/snippet-admin-notifications.php`
  Keep the feature only after redesigning the output-buffering flow so every buffer is opened and closed safely in the same logical execution path, and after tightening output escaping.
- `includes/snippet-media-replace.php`
  Keep the feature, but harden GET/POST handling, validate the uploaded file more strictly, and replace manual file-copy logic with WordPress file/media APIs as much as possible.
- `includes/snippet-content-order.php`
  Keep the feature, but harden all request handling and sanitize/validate every incoming ID and parameter before use.
- `includes/snippet-custom-taxonomy-filters.php`
  Keep the feature, but validate list-table query values more strictly and ensure no privileged action depends on untrusted request data.
- `includes/snippet-db-tables-manager.php`
  Keep only if all identifier handling and query building are rebuilt around strict whitelisting and prepared placeholders. This feature is one of the highest-risk refactors.
- `includes/snippet-post-duplicator.php`
  Keep the feature, but replace the current direct SQL copy strategy with a safer prepared or API-based meta duplication flow.
- `includes/snippet-login-page-designer.php`
  Keep the core design controls, but move generated CSS into the enqueue system and remove or strictly sanitize any arbitrary CSS input.

## 5. Execution Plan by Phase

### Phase 1: Remove Policy Blockers
- Purpose: eliminate the features most likely to cause immediate rejection on re-review.
- Exact work:
  Remove `includes/snippet-disable-all-updates.php`, `includes/snippet-disable-file-editing.php`, and `includes/snippet-upload-limits.php` from the wp.org build.
  Remove their entries from `includes/snippets/class-snippet-registry.php`.
  Remove any now-unused asset/page registrations related to the upload-limits admin screen from `includes/components/class-asset-manager.php`.
  Remove any settings UI references, cleanup hooks, or snippet toggles that still expose these features.
- Expected result: the wp.org package no longer ships features that disable updates, define `DISALLOW_FILE_EDIT`, or edit server/PHP config files.

### Phase 2: Refactor Asset Loading
- Purpose: replace all direct CSS/JS/stylesheet tag output with WordPress-native asset loading.
- Exact work:
  Refactor the review-cited snippets that print `<style>`, `<script>`, or `<link rel="stylesheet">`.
  Use `wp_register_style()`, `wp_enqueue_style()`, `wp_add_inline_style()`, `wp_register_script()`, `wp_enqueue_script()`, and `wp_add_inline_script()` as appropriate.
  Do a repository-wide sweep for remaining direct asset tags because the review email says there are 13 incidences and only shows examples.
- Expected result: no snippet outputs raw CSS/JS tags directly when WordPress enqueue APIs should be used instead.

### Phase 3: Remove Direct Core Loading and Other Bootstrap Shortcuts
- Purpose: stop loading WordPress core files directly or depending on brittle bootstrap shortcuts.
- Exact work:
  Rewrite `includes/snippet-custom-login-url.php` so it no longer uses `require_once ABSPATH . 'wp-login.php';`.
  Keep the login rewrite/custom URL behavior only if it can be handled through standard WordPress routing/hooks.
  If the refactor becomes brittle or invasive, remove this snippet from the wp.org build rather than keeping a direct-core-include approach.
- Expected result: no plugin file loads `wp-login.php`, `wp-load.php`, `wp-blog-header.php`, or similar core files directly.

### Phase 4: Security Hardening for Requests and Uploads
- Purpose: ensure every state-changing action has the expected nonce, capability check, validation, and safe request flow.
- Exact work:
  Audit all review-cited `$_GET`, `$_POST`, `$_REQUEST`, and `$_FILES` paths.
  Make sure every state-changing form or AJAX endpoint checks both nonce and capability before processing.
  For read-only screens that use query args, validate aggressively and make sure no state change happens before validation.
  Rework `includes/snippet-media-replace.php` so uploads go through WordPress media/file helpers instead of raw `copy()` plus manual temp-file handling.
- Expected result: request handling is explicit, capability-gated, nonce-protected where needed, and no upload flow trusts raw file arrays more than necessary.

### Phase 5: Escaping, Sanitization, Validation, and Output Cleanup
- Purpose: remove late-stage XSS/output risks and align all inputs/outputs with WordPress expectations.
- Exact work:
  Sanitize early, validate strictly, and escape late in every review-cited file.
  Replace raw or weak CSS/HTML output patterns with context-aware escaping.
  Remove or strictly sanitize user-entered CSS in `includes/snippet-login-page-designer.php`.
  Rework `includes/snippet-admin-notifications.php` so buffered notice HTML is handled safely.
- Expected result: output is escaped in the right context, input is validated against allowed shapes, and risky generated HTML/CSS is reduced or removed.

### Phase 6: SQL Hardening
- Purpose: eliminate reviewer-flagged dynamic SQL construction and reduce injection risk.
- Exact work:
  Refactor `includes/snippet-db-tables-manager.php` search/query builders so identifiers are strictly whitelisted and values are passed via placeholders.
  Refactor `includes/snippet-post-duplicator.php` to stop assembling an `INSERT ... UNION ALL` query string for post meta.
  Re-run a targeted SQL grep after refactoring to catch similar patterns the review may not have listed.
- Expected result: all remaining direct SQL is justified, prepared correctly, and limited to validated identifiers plus placeholder-bound values.

### Phase 7: Final Cleanup and Packaging
- Purpose: remove leftovers from the refactor and make sure the wp.org package is internally consistent.
- Exact work:
  Delete dead code left behind by removed snippets.
  Remove orphaned settings, menu items, assets, and labels tied to removed wp.org-blocked features.
  Re-check the plugin registry, snippet toggles, and admin navigation so no removed feature is still visible.
- Expected result: the wp.org build is clean, consistent, and does not expose non-functional or removed snippet entries.

## 6. File-by-File Task List

### `includes/snippet-disable-all-updates.php`
- Status: Remove
- What is wrong: this snippet disables core/plugin/theme/translation updates and interferes with WordPress.org-hosted update behavior.
- What needs to be changed: remove the snippet from the wp.org version and remove its registration/toggle references.

### `includes/snippet-disable-file-editing.php`
- Status: Remove
- What is wrong: it defines `DISALLOW_FILE_EDIT` at runtime and changes global WordPress behavior.
- What needs to be changed: remove the snippet from the wp.org version and remove its registration/toggle references.

### `includes/snippet-upload-limits.php`
- Status: Remove
- What is wrong: it uses `ini_set()`, `ABSPATH`, `.htaccess` writes, `.user.ini` writes, and raw file writing to modify server behavior.
- What needs to be changed: remove the entire snippet from the wp.org version instead of trying to salvage it for this build.

### `includes/snippet-custom-login-url.php`
- Status: Refactor
- What is wrong: it directly loads `wp-login.php` via `require_once ABSPATH . 'wp-login.php';`.
- What needs to be changed: replace the current direct-include approach with a hook/routing-based implementation; if that is not stable enough, remove the snippet from the wp.org build.

### `includes/snippet-hide-admin-notices.php`
- Status: Refactor
- What is wrong: it outputs raw `<style>` and `<script>` blocks directly in admin hooks.
- What needs to be changed: move styles/scripts to proper enqueued assets or inline assets attached to registered handles.

### `includes/snippet-show-template.php`
- Status: Refactor
- What is wrong: it prints raw CSS in `add_custom_styles()`.
- What needs to be changed: register/enqueue a style handle and attach the generated CSS through WordPress APIs.

### `includes/snippet-maintenance-mode.php`
- Status: Refactor
- What is wrong: it prints a raw stylesheet link and inline `<style>` block in the public maintenance response.
- What needs to be changed: load public CSS through registered/enqueued assets and move dynamic CSS variables into `wp_add_inline_style()` or an equivalent WordPress-safe flow.

### `includes/snippet-site-visibility.php`
- Status: Refactor
- What is wrong: it prints raw CSS in admin/frontend heads.
- What needs to be changed: move the indicator styling into an enqueue-based implementation.

### `includes/snippet-login-page-designer.php`
- Status: Refactor
- What is wrong: it prints raw CSS, echoes generated CSS fragments, and allows arbitrary custom CSS output.
- What needs to be changed: move styling to a registered handle plus inline CSS, validate all generated CSS inputs strictly, and remove the freeform custom CSS field if strict sanitization is not feasible.

### `includes/snippet-media-size-column.php`
- Status: Refactor
- What is wrong: it prints raw CSS directly into `admin_head`.
- What needs to be changed: move the media column CSS into an enqueue/inline-style flow.

### `includes/snippet-admin-menu-organizer.php`
- Status: Refactor
- What is wrong: it echoes a raw `<style>` block and generated CSS.
- What needs to be changed: build the CSS only after sanitizing/validating menu IDs, then pass it through a registered style handle with late escaping.

### `includes/snippet-admin-notifications.php`
- Status: Refactor
- What is wrong: the review flagged `ob_start()` as unsafe in its current flow, and the snippet echoes organized notice HTML directly.
- What needs to be changed: redesign the buffering flow so the buffer is always opened/closed safely in the same execution path or replace the buffering approach entirely; tighten escaping/HTML allowlisting for grouped notice output.

### `includes/snippet-media-replace.php`
- Status: Refactor
- What is wrong: review-cited request handling depends on raw `$_GET` / `$_POST` / `$_FILES`, and the replacement flow uses manual filename/path logic plus `copy()`/`wp_delete_file()`.
- What needs to be changed: validate attachment IDs before rendering, keep nonce/capability checks ahead of any write action, use WordPress upload/media helpers where possible, validate file types/extensions explicitly, and avoid deleting/replacing the original file until the new file is safely accepted.

### `includes/snippet-content-order.php`
- Status: Refactor
- What is wrong: the review flagged missing nonce coverage for request-driven admin UI logic and weak handling of `$_POST['post_ids']`.
- What needs to be changed: keep the AJAX nonce/capability protections, but sanitize every incoming ID, validate `post_type`, validate `parent`, and review whether page-level query args can be tightened further before use.

### `includes/snippet-custom-taxonomy-filters.php`
- Status: Refactor
- What is wrong: the review flagged request handling around taxonomy filter query values.
- What needs to be changed: validate the taxonomy key against registered taxonomies, validate the selected slug against available terms where practical, and make sure the value is only used for display/filtering and not treated as trusted input anywhere else.

### `includes/snippet-db-tables-manager.php`
- Status: Refactor
- What is wrong: the review flagged unsafely constructed SQL fragments, request-derived row data, and identifier interpolation inside queries.
- What needs to be changed: keep strict table/column whitelisting, rebuild search queries with safe placeholders for every value, avoid concatenated SQL fragments that mix identifiers and prepared values, and re-audit every AJAX endpoint in this file after the refactor.

### `includes/snippet-post-duplicator.php`
- Status: Refactor
- What is wrong: it builds a bulk `INSERT` query for post meta that the review team does not consider safely prepared.
- What needs to be changed: replace the SQL assembly with a safer duplication strategy, ideally using WordPress meta APIs or a more explicit prepared insert loop.

## 7. Risky Areas / Things to Double-Check
- Removed snippets may still be referenced in the snippet registry, asset manager, settings UI, or cleanup metadata.
- The review email says the asset-loading issue appears in 13 places, so the examples in the email are not the whole list.
- `includes/snippet-media-replace.php` is high-risk because it touches uploads, file paths, temp files, metadata regeneration, and replacement of existing media.
- `includes/snippet-db-tables-manager.php` is high-risk because even small identifier/query mistakes can trigger another rejection.
- `includes/snippet-login-page-designer.php` is high-risk if arbitrary CSS remains in the wp.org build.
- Read-only GET-based admin pages may still need extra validation even where a nonce is not naturally part of the request.
- Any remaining `ABSPATH` file path usage should be checked carefully for portability and policy issues.
- Any remaining `ob_start()` usage should be verified so buffers are definitely closed in the same logical flow.

## 8. Final QA Checklist
- Activate the plugin on a clean WordPress install and confirm there are no fatal errors or missing-file warnings.
- Enable `WP_DEBUG` and `WP_DEBUG_LOG`, then load the main plugin screen and every remaining snippet screen.
- Test each affected admin page after refactoring: custom login URL, media replace, content order, taxonomy filters, DB tables manager, maintenance mode, admin menu organizer, login page designer, hide notices, show template, site visibility, media size column, admin notifications.
- Test every form submission and AJAX flow with valid input, invalid input, missing nonce, and insufficient-permission scenarios.
- Test media replacement with allowed file types, mismatched extensions, oversized files, and failed upload cases.
- Run Plugin Check and fix anything still reported in the remaining wp.org build.
- Run PHPCS/WPCS against the plugin and fix warnings/errors that map to the review categories.
- Grep for risky patterns before packaging:
  `rg -n "<style|<script|<link rel=\"stylesheet\"|require_once ABSPATH|ini_set\\(|file_put_contents\\(|ob_start\\(|\\$_GET\\[|\\$_POST\\[|\\$_REQUEST\\[|\\$_FILES\\[|SELECT |INSERT INTO|DISALLOW_FILE_EDIT|wp_version_check" .`
- Verify that removed wp.org-blocked features no longer appear in the UI, registry, or saved options handling.
- Re-test plugin activation/deactivation after cleanup.

## 9. Definition of Done
- The wp.org package no longer includes update-disabling, runtime `DISALLOW_FILE_EDIT`, or upload-limit/server-config snippets.
- No remaining snippet loads WordPress core files directly.
- No remaining review-relevant screen prints raw CSS/JS tags when a WordPress enqueue API should be used.
- Review-cited request handlers have the required nonce/capability checks and strict validation.
- Review-cited output paths use context-appropriate escaping and no unsafe arbitrary CSS/HTML output remains.
- Review-cited SQL paths are rebuilt so identifiers are validated and values are placeholder-bound.
- Plugin Check, PHPCS/WPCS, and manual QA on a clean `WP_DEBUG` install are complete with no unresolved blockers.
- The plugin is ready to upload again and the reply email can honestly say the reported issues were fixed and the whole plugin was re-audited for similar patterns.
