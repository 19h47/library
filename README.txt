=== Library ===

Contributors: 19h47
Donate link: https://www.19h47.fr
Tags: books, library, custom post type, taxonomy, isbn, authors, publishers, reading
Requires at least: 7.1
Tested up to: 7.1
Stable tag: 0.0.0
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage a book library with a custom post type, authors and publishers taxonomies, and book metadata (ISBN, series, reading status).

== Description ==

Library adds a **Book** custom post type and two taxonomies (**Authors** and **Publishers**) so you can catalogue and organize your books from the WordPress admin.

= Features =

* **Book post type** – Custom post type with title, featured image (cover), and archive at `/books/`
* **Authors taxonomy** – Attach one or more authors to each book
* **Publishers taxonomy** – Attach one or more publishers to each book
* **Information metabox** – Fields for series, ISBN, ISSN, volume number, date published, read state and date, rating, translators and book editions
* **List table** – Custom columns (thumbnail, read, date published, ISBN, book editions) with sortable date published and quick edit for ISBN and book editions
* **REST API** – Books, authors, and publishers are exposed via the REST API (`/wp-json/wp/v2/books`, etc.) for use in themes or external apps
* **Translatable** – Ready for translation (Domain Path: `/languages`)

= Technical =

* Registers the `book` post type with `show_in_rest` for block editor support
* Registers `library-author` and `library-publisher` taxonomies
* Loads the text domain at `init`
* No shortcodes or front-end templates included; use your theme or blocks to display books

== Installation ==

1. Upload the `library` folder to `/wp-content/plugins/` or install via the WordPress plugin installer.
2. Activate the plugin through the **Plugins** screen.
3. Use **Books** in the admin menu to add and manage books, authors, and publishers.

= Optional =

* Add translations in `wp-content/plugins/library/languages/` (e.g. `library-fr_FR.po` / `.mo`) or use a translation plugin.

= Updates =

The plugin updates from the latest release of [19h47/library](https://github.com/19h47/library/releases), like any other plugin in **Dashboard > Updates**. The `Update URI` header routes the check to GitHub instead of WordPress.org.

To publish a release:

1. Bump `Version` in `library.php` and `Stable tag` in this file.
2. Commit, push, then create the release: `gh release create v1.0.0 --notes "…"`. The tag may start with `v`; the notes appear in the plugin details.

No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder.

== Frequently Asked Questions ==

= Can I change the book slug? =

Yes. The post type rewrite slug is `books` by default. Use the `library_post_type_args` filter to override the `rewrite` argument.

= Does the plugin include a front-end display? =

No. The plugin only provides the post type, taxonomies, and admin UI. Use your theme, blocks, or custom templates to display books on the front end.

== Changelog ==

= Unreleased =
* Updates from GitHub releases.
* The reading percentage in the books list is computed on display; the books list and the dashboard widget now count the same books (trash and auto-drafts excluded).
* Admin scripts no longer depend on jQuery.
* Breaking: requires WordPress 7.1 and PHP 8.2.
* Breaking: removed the `library/v1/settings/{name}` REST route, which let anyone read or update any site option.
* Breaking: removed the `library/v1/isbn/{isbn}` REST route and the “Fetch info” button (BnF lookup).
* Breaking: removed the `reading_percentage` option. Migration: delete it with `wp option delete reading_percentage`.
* The `library/v1/books/reading-stats` response now includes `percent`.
* Breaking: the `read` REST field is replaced by `meta.read`. The read date is optional and stored as `Y-m-d`, which fixes the date being lost after checking “Read” in the books list. Setting a read date marks the book as read, marking it as read without a date sets today, marking it as unread clears the date, from the form, quick edit or the REST API. Existing books need no migration.
* Read checkboxes in the books list have an accessible name.
* Book meta are registered with their type and sanitization, and exposed in the REST API under `meta` (books now support `custom-fields`; the Custom Fields box is removed since the Information box edits them). Dates must be `Y-m-d`, ratings go from 1 to 10 by half points; an empty value deletes the meta.
* The form and quick edit save through the same code; quick edit requires its nonce.
* The slug is built before the book is saved, from quick edit too.
* Authors and publishers are edited in the core tag boxes, with autocomplete, instead of fields in the Information box. New authors typed there are created.
* Fixed PHP warnings when querying author terms by ID.

= 0.0.0 =
* Initial release.
* Book post type with authors and publishers taxonomies.
* Information metabox (series, ISBN, ISSN, volume, date published, editions, read status).
* Custom list table columns and quick edit.
* REST API support.
* Translation-ready.

== Upgrade Notice ==

= 0.0.0 =
Initial release of Library.
