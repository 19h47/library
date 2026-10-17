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
* **Information metabox** – Fields for series, ISBN, ISSN, volume number, date published, book editions, and a “Read” checkbox
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
* Breaking: the read date is the only reading state. A book is read when it has a read date, stored as `Y-m-d`; the `read` meta is removed. Checking “Read” sets the date to today, unchecking clears it. The `read` REST field is still there, computed from the date.
* Read checkboxes in the books list have an accessible name.

Migration of the read state: books marked as read without a read date get their publication date, books with a read date but unchecked lose it, dates are cut to `Y-m-d`, and the `read` meta is deleted:

    wp eval 'foreach ( get_posts( array( "post_type" => "book", "post_status" => array_keys( get_post_stati() ), "numberposts" => -1 ) ) as $book ) {
        $date = substr( (string) get_post_meta( $book->ID, "read_date", true ), 0, 10 );
        $date = get_post_meta( $book->ID, "read", true ) ? ( $date ?: get_the_date( "Y-m-d", $book ) ) : "";
        update_post_meta( $book->ID, "read_date", $date );
        delete_post_meta( $book->ID, "read" );
    }'

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
