=== Library ===

Contributors: 19h47
Donate link: https://www.19h47.fr
Tags: books, library, custom post type, taxonomy, isbn, authors, publishers, reading
Requires at least: 5.9
Tested up to: 6.7
Stable tag: 0.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage a book library with a custom post type, authors and publishers taxonomies, and book metadata (ISBN, series, reading status).

== Description ==

Library adds a **Book** custom post type and two taxonomies (**Authors** and **Publishers**) so you can catalogue and organize your books from the WordPress admin.

= Features =

* **Book post type** – Custom post type with title, featured image (cover), and archive at `/books/`
* **Authors taxonomy** – Attach one or more authors to each book
* **Publishers taxonomy** – Attach one or more publishers to each book
* **Information metabox** – Fields for series, ISBN, ISSN, volume number, date published, book editions, and a “Read” checkbox
* **List table** – Custom columns (thumbnail, read, date published, ISBN, book editions) with sortable date published and quick edit for ISBN and book editions
* **REST API** – Books, authors, and publishers are exposed via the REST API (`/wp-json/wp/v2/books`, etc.) for use in themes or external apps
* **Translatable** – Ready for translation (Domain Path: `/languages`)

= Technical =

* Registers the `book` post type with `show_in_rest` for block editor support
* Registers `library-author` and `library-publisher` taxonomies
* Loads the text domain at `init` (WordPress 6.7+ compatible)
* No shortcodes or front-end templates included; use your theme or blocks to display books

== Installation ==

1. Upload the `library` folder to `/wp-content/plugins/` or install via the WordPress plugin installer.
2. Activate the plugin through the **Plugins** screen.
3. Use **Books** in the admin menu to add and manage books, authors, and publishers.

= Optional =

* Add translations in `wp-content/plugins/library/languages/` (e.g. `library-fr_FR.po` / `.mo`) or use a translation plugin.

== Frequently Asked Questions ==

= Can I change the book slug? =

Yes. The post type rewrite slug is `books` by default. Use the `library_post_type_args` filter to override the `rewrite` argument.

= Does the plugin include a front-end display? =

No. The plugin only provides the post type, taxonomies, and admin UI. Use your theme, blocks, or custom templates to display books on the front end.

== Changelog ==

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
