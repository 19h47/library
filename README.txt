=== Library ===

Contributors: 19h47
Donate link: https://www.19h47.fr
Tags: books, library, custom post type, taxonomy, isbn, authors, publishers, reading
Requires at least: 7.1
Tested up to: 7.1
Stable tag: 1.1.0
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage a book library with a custom post type, authors and publishers taxonomies, book metadata (ISBN, series, reading status, rating) and a reading dashboard.

== Description ==

Library adds a **Book** custom post type and two taxonomies (**Authors** and **Publishers**) so you can catalogue and organize your books from the WordPress admin.

= Features =

* **Book post type** – Title, featured image (cover) and archive at `/books/`. Book titles show their series and volume number: “Series, Title, 3”.
* **Authors taxonomy** – Hierarchical: a real name as parent, pseudonyms as children. Filtering by an author includes their pseudonyms.
* **Publishers taxonomy** – One or more publishers per book.
* **Information metabox** – Series, ISBN, ISSN, volume number, date published, read state and read date, rating from 1 to 10 by half points, translators and book editions. Authors and publishers are edited in their own tag boxes, with autocomplete.
* **Reading state** – A book is read or not; the read date is optional. Setting a read date marks the book as read and marking it as unread clears the date; marking it as read without a date in the form or the books list sets today.
* **Books not kept** – A book read but not kept (borrowed, given away…) gets the “Not kept” status: it is left out of the “All” view, the site and the library stats, has its own view in the books list, and counts in the reading stats.
* **Books list** – Columns for the cover (with the rating), date published, ISBN, read state, read date and rating; date published and rating are sortable. The “Read” checkbox saves at once and updates the reading percentage shown above the list. Quick edit covers the ISBN, book editions, read date and rating.
* **Search** – Book searches also match the series, ISBN, ISSN, volume number, translators and book editions.
* **Dashboard** – Five widgets, each one can be hidden, moved or collapsed like any dashboard widget. Each chart has a data table for screen readers. The “At a Glance” widget shows the number of books.
    * **Reading** – Books read, this year against the same date last year, the number expected by the end of the year, and books read by year and by month.
    * **Ratings** – Number of rated books, average, distribution and average rating by year read.
    * **Reading pile** – Books to read, kept or not, the oldest one by publication date and the latest reads.
    * **Library** – Books, authors and publishers, share of translated books, the authors (pseudonyms counted for their real name), publishers and book editions with the most books, and publication years.
    * **Reading idea of the week** – A book not read yet, kept or not, with its cover, or a link to add one, and why it is suggested: the next volume of a series in progress, a book by an author rated 8 or more on average, or the rest of the reading pile. The idea changes every week; “Another idea” moves to the next one.
* **REST API** – Books, authors and publishers in the core REST API (`/wp-json/wp/v2/books`, etc.), with the book meta under `meta`. `/wp-json/library/v1/books/reading-stats` returns the reading stats and `/wp-json/library/v1/books/reading-idea` the reading idea of the week, to users who can edit posts.
* **Updates** – From the GitHub releases, see below.
* **Translatable** – French translation included (Domain Path: `/languages`).

= Technical =

* Registers the `book` post type with `show_in_rest`; books are edited in the classic screen, as the type has no editor
* Registers the `library-author` and `library-publisher` taxonomies and the book meta (`register_post_meta`)
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

= Why is no update offered? =

Updates come from the GitHub releases, see Updates above. No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder. Versions before 1.0.0 also blocked plugin updates: install 1.0.0 by hand once, later versions update as usual.

= How is a book marked as read? =

The “Read” checkbox, in the books list or the Information box, is the reading state; the read date is optional. Setting a read date marks the book as read and marking it as unread clears the date, wherever the change comes from. Marking a book as read without a date in the Information box or the books list sets today; the REST API and imports can save a book read without a date.

= How do I handle pseudonyms? =

Add the pseudonym as a child of the author's real name in **Books > Authors**. Filtering the books list by the real name then includes the books signed with the pseudonym, and the Library dashboard widget counts them for the real name.

= How do I get book data from the REST API? =

Books are at `/wp-json/wp/v2/books`, with the book fields under `meta`: `series`, `isbn`, `issn`, `volume_number`, `translators`, `book_editions`, `date_published` and `read_date` (`Y-m-d`), `rating` (1 to 10 by half points) and `read`. Authors and publishers are at `/wp-json/wp/v2/library-author` and `/wp-json/wp/v2/library-publisher`. `/wp-json/library/v1/books/reading-stats` returns the number of books, of books read and the percentage, to users who can edit posts. `/wp-json/library/v1/books/reading-idea` returns the reading idea of the week with its reason, to the same users: `?position=1` gives the next idea, `_embed` adds the book. Books not kept have the `library-not-kept` status: list them with `?status=library-not-kept` as a user who can edit posts.

= How do I log a book I read but did not keep? =

Add it as any book and check **Not kept** in the Information box, or pick the “Not kept” status in quick or bulk edit. The book is then hidden from the “All” view of the books list, the site and the Library widget, but its reading and rating count in the Reading and Ratings widgets. Find these books in the “Not kept” view of the books list. The count of an author or publisher only includes the books kept; their books not kept are in the “Not kept” column of **Books > Authors** and **Books > Publishers**.

= Can I import books from a WordPress export? =

Yes, with **Tools > Import > WordPress**. Read dates, ratings and reading states are saved as they are in the file. Install the plugin first, so that statuses like “Not kept” are known. The importer only adds books: it skips a book that already exists (same title and date) and never updates it; complete existing books through the REST API instead.

= Where is the former dashboard widget? =

Since 1.0.0 it is split into four widgets: Reading, which keeps its position, Ratings, Reading pile and Library. Hide the ones you do not need in **Screen Options** on the dashboard.

== Changelog ==

= 1.1.0 =
* Fixed: the featured image (cover) box was missing with themes that do not support featured images; the plugin now enables them for books.
* Reading idea of the week: a dashboard widget and the `library/v1/books/reading-idea` REST route suggest a book not read yet, with the reason: the next volume of a series in progress, a book by an author rated well, or the rest of the reading pile.

= 1.0.1 =
* No code change: release to check updates from GitHub on an installed 1.0.0.

= 1.0.0 =
* Updates from GitHub releases.
* The reading percentage in the books list is computed on display; the books list and the dashboard widget now count the same books (trash and auto-drafts excluded).
* Admin scripts no longer depend on jQuery.
* Breaking: requires WordPress 7.1 and PHP 8.2.
* Breaking: removed the `library/v1/settings/{name}` REST route, which let anyone read or update any site option.
* Breaking: removed the `library/v1/isbn/{isbn}` REST route and the “Fetch info” button (BnF lookup).
* Breaking: removed the `reading_percentage` option. Migration: delete it with `wp option delete reading_percentage`.
* The `library/v1/books/reading-stats` response now includes `percent`.
* Breaking: the `read` REST field is replaced by `meta.read`. The read date is optional and stored as `Y-m-d`, which fixes the date being lost after checking “Read” in the books list. Setting a read date marks the book as read and marking it as unread clears the date, from the form, quick edit or the REST API; marking it as read without a date in the form or the books list sets today. Imports are saved as they are. Existing books need no migration.
* Read checkboxes in the books list have an accessible name.
* Book meta are registered with their type and sanitization, and exposed in the REST API under `meta` (books now support `custom-fields`; the Custom Fields box is removed since the Information box edits them). Dates must be `Y-m-d`, ratings go from 1 to 10 by half points; an empty value deletes the meta.
* The form and quick edit save through the same code; quick edit requires its nonce.
* The slug is built before the book is saved, from quick edit too.
* Authors and publishers are edited in the core tag boxes, with autocomplete, instead of fields in the Information box. New authors typed there are created.
* Fixed PHP warnings when querying author terms by ID.
* Search only applies to book queries and matches the book text meta (series, ISBN, ISSN, volume number, translators, book editions) through a subquery. Other searches are no longer changed, and a search combined with a meta query no longer fails.
* Sorting by publication date uses the core meta ordering.
* The books list can be sorted by rating; unrated books come last in both orders.
* New “Not kept” status for the books read but not kept: hidden from the “All” view, the site and the library stats, counted in the reading stats. Set it with the checkbox of the Information box, or in quick and bulk edit. The authors and publishers lists show their books not kept in a column of their own, and the author and publisher links of the books list keep the current view.
* Each class registers its own hooks; `Library_Loader` is removed. The books list CSS and the dashboard widget were registered twice. Breaking for code using `Library_Loader` or the plugin class constructors, which no longer take a name and version.
* The series and volume number are added to book titles by a single `the_title` filter, which no longer fails when called without a post ID.
* The “At a Glance” dashboard widget shows the number of published books with a plain “%s books” string.
* Dashboard widget: one chart template for the five charts, run from three queries instead of seven. Each chart is a figure whose data screen readers get as a table; the bars are hidden from them. Years without readings are shown, month names and initials come from the site language, and counts exclude auto-drafts like the books list.
* The books list styles moved from an inline `<style>` to `admin/css/library-admin.css`; unused dashboard styles are removed.
* The dashboard widget is split into four widgets: Reading (it keeps the former widget position), Ratings, Reading pile and Library. New stats: books read this year against the same date last year and the projection to the end of the year, average rating by year read, books to read, the oldest unread book, the latest reads, the share of translated books, and the authors, publishers and book editions with the most books.
* Admin styles and scripts are versioned by their modification time, so browsers never keep an outdated file.
* Quick edit reads the book fields from the row inline data (`add_inline_data`), rendered once with core markup. The hidden “Book Editions” column, which only carried data and showed up in Screen Options, is removed. The form and quick edit share one ISBN pattern.

= 0.0.0 =
* Initial release.
* Book post type with authors and publishers taxonomies.
* Information metabox (series, ISBN, ISSN, volume, date published, editions, read status).
* Custom list table columns and quick edit.
* REST API support.
* Translation-ready.

== Upgrade Notice ==

= 1.0.0 =
Closes a REST route that let anyone read or update any site option: update now. Requires WordPress 7.1 and PHP 8.2. The `read` REST field is replaced by `meta.read`. Delete the unused `reading_percentage` option. Install this version by hand: earlier versions block plugin updates.

= 0.0.0 =
Initial release of Library.
