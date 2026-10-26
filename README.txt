=== Library ===

Contributors: 19h47
Donate link: https://www.19h47.fr
Tags: books, library, custom post type, taxonomy, isbn, authors, publishers, languages, reading
Requires at least: 7.1
Tested up to: 7.1
Stable tag: 6.2.1
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Catalogue your books and log your readings: authors, publishers, original languages, ratings, a reading dashboard, achievements and a phone app.

== Description ==

Library adds a **Book** post type and three taxonomies, **Authors**, **Publishers** and **Original languages**, to catalogue your books and log your readings from the WordPress admin, or from your phone.

= Features =

* **Books** – Cover, subtitle, series and volume, ISBN, ISSN, number of pages, edition date and date first published, translators, read dates and rating from 1 to 10. A title shows its series, volume and subtitle the same way everywhere: “Series, vol. 3: Title: Subtitle”.
* **Authors, publishers and languages** – Pseudonyms are children of an author's real name, collections children of their publisher: filtering by an author or a publisher includes them. An original language is added by its ISO 639 code and named in the site language.
* **Readings** – A book is read or not, with a date per reading: a book read again keeps each one. A book read but not kept, borrowed or given away, gets the “Not kept” status: it leaves the library, its readings still count.
* **Books list** – Columns for the cover, edition date, ISBN, read state, latest read date and rating, with quick and bulk edit. The search matches every field of a book, and keywords such as `series:SAS` or `read:2024` narrow it to one: see the Help tab.
* **Dashboard** – Six widgets: Reading, Ratings, Reading pile, Library, Reading idea of the week and Achievements. Each chart has a data table for screen readers.
* **Reading idea of the week** – A book to read, and why: the next volume of a series in progress, an author rated well, an anniversary, a short book to catch up… “Another idea” moves to the next one.
* **Achievements** – Computed from the books, nothing stored: authors, series and collections read in full, milestones of books and pages, records, languages, and a few easter eggs to find. **Books > Achievements** lists them all.
* **Reading app** – The library on a phone, in **Books > App**: the search, the diary of the readings, the reading idea, the stats of the year, the achievements and the page of each author. The reading of a book is edited there, and a book is added with a photo of its cover.
* **REST API** – Books and their taxonomies in the core REST API, and routes of their own for the reading stats, the readings, the reading idea, the achievements and the authors: see the FAQ.
* **Translatable** – French translation included.

= Technical =

* Registers the `book` post type, edited in the classic screen, the `library-author`, `library-publisher` and `library-language` taxonomies and the book meta, all in the REST API.
* No shortcodes or front-end templates: display books with your theme or blocks.
* The reading app is an admin page rendered full screen that reads the REST API. Its bricks are script modules, one per custom element, without a build. Its styles start from `app/css/app-style.css`, a copy of the [base shared by the author's small apps](https://gist.github.com/19h47/27df983fac74beb8d3e0a30688654498), with its revision in its header.

== Installation ==

1. Upload the `library` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Use **Books** in the admin menu to add and manage books, authors and publishers.

= Updates =

The plugin updates from the latest release of [19h47/library](https://github.com/19h47/library/releases), like any other plugin in **Dashboard > Updates**. No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder. To publish a release, see `RELEASING.md`.

== Frequently Asked Questions ==

= How do I use the app on my phone? =

Log in on the phone and open **Books > App**. On an iPhone, add it to the home screen with **Share > Add to Home Screen**; on Android, with **Install app** in the browser menu. It then opens full screen and keeps the login of the site. A book edits its reading: read, read dates, rating and not kept; **Edit book** opens the admin for the rest. The **+** at the top adds a book.

= Can several readers share the library? =

No, the plugin has one reader per site. The readings, ratings and “Not kept” status belong to the book, not to a user: every user of the site sees and changes the same ones. Each reader keeps their library on their own site.

= How do I log a book read again? =

In the Information box, fill in the empty field under the read dates: each date is a reading, and a field emptied removes its reading. A book read again counts once per reading in the reading stats, and once, by its first reading, in the achievements that count books.

= How do I add a pseudonym or a collection? =

Add the pseudonym as a child of the author's real name in **Books > Authors**, and the collection as a child of its publisher in **Books > Publishers** or with **Add New Publisher** in the Publishers box of a book. Check the collection, not its publisher, on the book. The books list and the dashboard count them for their parent.

= Can I import books from a WordPress export? =

Yes, with **Tools > Import > WordPress**, once the plugin is active, so that statuses like “Not kept” are known. The importer only adds books: it skips a book that already exists (same title and date) and never updates it.

= Can I change the book slug? =

Yes. The post type rewrite slug is `books` by default: use the `library_post_type_args` filter to override the `rewrite` argument.

= How do I get book data from the REST API? =

Books are at `/wp-json/wp/v2/books`, with their fields under `meta`, described by the schema of the route, and the line shown under their title in `credits`. Authors, publishers and original languages are at `/wp-json/wp/v2/library-author`, `library-publisher` and `library-language`; a collection is a publisher with a `parent`. Books not kept are listed with `?status=library-not-kept`, and the search keywords work too: `?search=series:SAS`.

The routes of the plugin, to users who can edit posts:

* `GET /wp-json/library/v1/books/reading-stats` – books, books read, and this year against last year.
* `GET /wp-json/library/v1/books/readings` – the readings by month, latest first: `page` and `per_page` count months, `_embed` adds the books.
* `GET /wp-json/library/v1/books/reading-idea` – the idea of the week and its reason: `?position=1` gives the next one.
* `GET /wp-json/library/v1/books/achievements` – the achievements earned and those close to it, each with the `target` its subject leads to.
* `GET /wp-json/library/v1/authors/<id>` – an author, their pseudonyms, their description, their books in the order they first came out and their reading.
* `POST /wp-json/library/v1/books` – adds a book as the book form does, to users who can publish posts.

== Changelog ==

= 6.2.1 =
* Reading app: the description of an author breathes, with a line between its paragraphs and some space around it.

= 6.2.0 =
* Reading app: the page of an author shows their description, written in the Description field of the author, its paragraphs and line breaks kept, with the few tags WordPress keeps, such as italics and links. `/wp-json/library/v1/authors/<id>` gives it as `description`.
* Reading app: the book open says who translated it and from which language, “Translated from Italian by A and B”, instead of the rows of its translators and its original languages. A book without translator, read in its original language, says nothing.

= 6.1.3 =
* Fixed: Reading app: a cover the server made no size of, as an AVIF on a server that cannot resize it, did not show; the image itself shows.

= 6.1.2 =
* Reading app: a book not kept shows a tag on its card, in a tinted grey, in the books list, the diary, the page of an author and the reading idea, instead of the words at the end of its line.
* A shorter readme; the release steps are in RELEASING.md, outside the plugin archive. The date of a book comes from one method. Nothing else changes for the reader.

= 6.1.1 =
* What the admin and the reading app share is in `includes/`, the book post type, its taxonomies, the reading idea and the achievements included, and the screens of the admin in `admin/`: the books list, the terms lists, the edit screen of a book, the reading idea widget and the achievements page. Nothing changes for the reader.

= 6.1.0 =
* Reading app: the page of an author gives the pages of their books, read or not, those with a number of pages; nothing when none has one. `/wp-json/library/v1/authors/<id>` gives them as `pages`.

= 6.0.0 =
* Changed: in `/wp-json/library/v1/books/achievements`, each achievement gives its `target`, what its subject leads to: a book, a search, an author or a collection, instead of `url`, a link of the admin, and `author`. The admin and the reading app build their own links from it.
* Reading app: the subject of an achievement opens in the app, a book in its dialog, an author on their page, a series or a translator as a search of the books, read or not, instead of the books list or the edit screen of the admin. A collection stays unlinked for now.
* The scripts of the books list and of the reading app take their strings from the translations of WordPress, JSON files made with `wp i18n make-json`, instead of strings passed by the server; a plural follows the rules of the language.
* The fields of a book, in the Information box, quick edit and the form of the reading app, take their label, description and constraints from the registration of the book meta: the REST API describes them too, with their `title` and `description`, and checks the ISBN and the ISSN with the pattern of the forms. The form of the reading app shows the description of the subtitle.
* Fixed: the date first published of a book to add in the reading app took no hyphen on an iPhone, whose numeric keyboard has none.
* The Reading widget takes the ID `library_dashboard_reading`: it goes back once to its default place on the dashboard, move it again where you want it.
* The book post type, its taxonomies and the achievements lose arguments, labels and links that WordPress never used or showed, such as the WPGraphQL arguments of the taxonomies.

= 5.0.0 =
* Changed: `/wp-json/library/v1/books/readings` returns the readings by month, latest first: each month with its readings, a page holding whole months. `page`, `per_page` (3 by default, 12 at most) and the totals in the headers count months; `_embed` adds the books of each month.
* Reading app: the diary shows the number of books of every month at once, a month that goes on in the next page included.
* The reading stats of the dashboard and the readings of the REST API count the readings with one definition: a reading stored twice can no longer count twice, as it could in the average rating by year read.

= 4.6.0 =
* Reading app: a book is added by hand, from the + at the top: a photo of its cover, its title, its authors, publisher and collection, suggested among those of the library or added, its ISBN and more details. It then opens, for its reading.
* Reading app: the book open is in the URL, as `&book=ID` on top of the view: going back closes it, a reload or a link opens it, and going back from its author opens it again.
* Reading app: every cover has the border of the app, and the cover of an open book keeps its proportions.
* REST API: `POST /wp-json/library/v1/books` adds a book as the book form does: its authors, publisher and collection by name, a name not found added, its meta and its cover.
* Fixed: the ISBN and ISSN fields of the book form, and the ISBN of quick edit, checked nothing: browsers compile a pattern with the v flag, where theirs were invalid.

= 4.5.0 =
* Reading app: the reading of a book is edited in its dialog: read, read dates, rating and not kept. Saving closes the book; its card, the diary and the page of its author follow, and a book the search no longer finds leaves the list, such as a book read with the “To read” filter.
* Reading app: a home above the books while the search is empty: the books read this year, the covers of the latest readings, the reading idea of the week and the achievements closest to be earned.
* Reading app: the page of an author, opened from the authors of a book or a “Complete author” achievement: their books in the order they first came out, with their pseudonyms, the books read, the average rating and their progress to complete them.
* REST API: `/wp-json/library/v1/authors/<id>` returns an author, their books and their reading. In `achievements`, the completions of authors give the term ID of the author.

= 4.4.1 =
* Fixed: a reading stored twice, the same book on the same date, showed twice in the diary of the reading app and counted twice in `/wp-json/library/v1/books/readings`.

= 4.4.0 =
* Reading app, in **Books > App**: the library on a phone, to add to the home screen. Search the books with the keywords of the books list and filter them by reading state; the diary of the readings by month; the reading idea of the week, and the next ones; the reading stats of the year and the achievements. A book opens in a dialog with its cover and details. Reading only for now.
* REST API: `/wp-json/library/v1/books/readings` lists the readings, latest first, a book read again once per reading. `reading-stats` also returns `year_to_date`, the books read this year against the same date last year. Books have a `credits` field, the line shown under their title.
* Fixed: the kind of the first reading idea of the week changed during the week when another kind emptied or filled, such as when the last book added in the last 30 days got older than that.

= 4.3.1 =
* Fixed: a keyword with a value in quotes, such as `series:"SAS"`, found no book in the books list, which receives the search with its quotes slashed. The links of the series and translators of the achievements, which quote their value, found none either.

= 4.3.0 =
* Search keywords: `series:`, `author:`, `publisher:`, `translator:`, `language:`, `isbn:`, `read:` and `rating:` narrow a book search to a field, such as `series:SAS` for the books of the series SAS only. They add up with the rest of the search; a comma gives a choice, a hyphen excludes and quotes keep the spaces of a value. The Help tab of the books list explains them.
* Achievements: the links of the series and the translators search with a keyword, so they lead to their books only.

= 4.2.0 =
* Achievements: books read and pages read are one achievement each, at the highest number reached, instead of one per number. The next number shows among those close to be earned, with its progress, such as 825 / 1,000 books read; for the pages, once a book read has its number of pages.

= 4.1.0 =
* Achievements: “Read again”, in the milestones, once a book is read again. A single achievement whose level goes up with the most times a book was read again, once, twice and so on, by the first book to reach it.
* Achievements: complete authors, series and collections finished, collections read by half or more and translators with 10 books read are a single achievement each, whose level is their number, such as “38 complete authors”, with the latest one; instead of one achievement for each author, series, collection or translator. Those close to be earned keep one entry each.
* Achievements: the three quarters of a collection are no longer a step of their own; a collection counts from half.

= 4.0.0 =
* Rereads: a book has a read date per reading. Fill in the empty field under the read dates of the Information box to log a book read again; a field emptied removes its reading.
* Books list: the read date is the latest one, with the number of readings of a book read again; quick edit changes the latest one.
* Reading widget: books read this year, the projection, the pages read and the charts by year and by month count a book read again once per reading. The latest reads list each reading.
* Reading idea: a book rated well is suggested to read again 10 years after its latest reading, and the opposite of the latest reads counts each reading.
* Achievements: a book read again counts once, by its first reading, in those that count books, such as books read, completions, translators, languages, the tour of the 20th century or back to the start; and once per reading in those about readings over time, such as pages, records, months in a row, languages in a month or a week, and the easter eggs about days and habits.
* Breaking: `meta.read_date` in the REST API is an array of `Y-m-d` dates, one per reading, instead of a date. The read dates already saved need no migration.

= 3.1.0 =
* Achievements: a page under Books lists them all by family, completions, milestones, discoveries, languages and translators, and easter eggs, after all those close to be earned. The widget keeps the latest and the closest ones, with a link to the page instead of the list of all achievements.
* Achievements: books that complete a series, authors or collections count once, for their series, else for their authors together, else for their collection. Hergé and The Adventures of Tintin were two completions of the same books.
* Achievements: a series or a translator typed with different cases, such as “Le Cycle de Dune” and “Le cycle de Dune”, is one.
* Achievements: the tour of the 20th century and the twins take the year a work came out like the other achievements, its date first published or else its edition date, instead of the date first published only.
* Achievements: the record month, the new year, the last of the year and seven days a week have their own icon.
* Fixed: the dashboard and the achievements page ran a database query per book to get its original languages.

= 3.0.0 =
* Original languages are identified by their ISO 639 code, their slug: a language added by typing its code is named in the site language. Your own language, for the “In the original” achievement, is the one of your profile, in any language, and no longer the name the French translation gives.
* Breaking: the slug of an original language is its code, “fr” for Français.

= 2.3.4 =
* The plugin has an icon on the updates screen.

= 2.3.3 =
* The plugin details have a banner.
* Fixed: the plugin details and the updates screen warned that the plugin was not tested with a patch release of WordPress, such as 7.1.3 with “Tested up to: 7.1”.

= 2.3.2 =
* The plugin details show the readme of the release, with its description, installation, FAQ and changelog, as on WordPress.org, instead of the raw release notes.

= 2.3.1 =
* Fixed: the link of an achievement led to the “All” view, empty when none of its books is kept, such as a borrowed series. It leads to the “Not kept” view then.

= 2.3.0 =
* Original languages: a new taxonomy, in the books list, quick and bulk edit, the search, the Library widget and the achievements.
* Achievements: a book read in its original language, 5 original languages in a month, 3 in a week, a translator read from two languages or more, and new easter eggs to find.
* Subtitle: a new field, shown after the title and matched by the search (`meta.subtitle` in the REST API).
* A book is named the same way everywhere, achievements included: “Series, vol. 3: Title: Subtitle”, instead of “Series, Title, Tome 03”. The reading idea shows the credits of the book: authors, publisher and collection, edition year.

= 2.2.0 =
* Achievements: a dashboard widget and the `library/v1/books/achievements` REST route list the achievements earned, latest first, and those close to it: completions of authors, series and collections, milestones of books and pages read, records, discoveries and easter eggs. Nothing is stored, they are computed from the books.

= 2.1.0 =
* The date first published may be a year alone or a year and month, such as 1929 or 1929-11, in the Information box and the REST API (`meta.published_date`). The reading idea only names its anniversary when the month is known.

= 2.0.3 =
* The Publishers column of the books list shows the publisher of a collection, such as 10/18 for Domaine étranger, once.
* Fixed: quick and bulk edit replaced the authors checked with new authors named after their ID.

= 2.0.2 =
* The Publishers box is a checklist showing publishers and their collections as a tree, where a collection is added under its publisher, instead of a tag box.

= 2.0.1 =
* The “Not kept” checkbox moves from the Information box to the Publish box, under the status.
* Fixed: “Another idea” cut the top of the dashboard widgets, its link to the widget scrolling the widgets area.

= 2.0.0 =
* Breaking: the `date_published` book meta is renamed `edition_date`, as it holds when the edition came out, in the REST API (`meta.edition_date`) and the books list (`orderby=edition_date`). Migration, with your table prefix: `wp db query "UPDATE wp_postmeta AS m INNER JOIN wp_posts AS p ON p.ID = m.post_id SET m.meta_key = 'edition_date' WHERE m.meta_key = 'date_published' AND p.post_type = 'book'"`.
* Breaking: the `book_editions` book meta is removed. It held collections, such as Folio at Gallimard: the Publishers taxonomy is now hierarchical, a collection being a child of its publisher, given to the book instead of the publisher. The field leaves the Information box, quick edit and the REST API (`meta.book_editions`); the Library widget ranks the collections from the taxonomy and counts them for their publisher.
* Book searches also match the authors, publishers and collections.
* Fixed: the Library widget counted pseudonyms apart from their real name in the authors with the most books and the number of authors.
* New optional date first published (`published_date`), when the work first came out, whatever the edition.
* New number of pages (`pages`), in the Information box, quick edit and the REST API, shown in the reading idea. The Reading widget shows the pages read this year, once a book read this year has its number of pages.
* The post date is the date the book was added to the library: the reading idea relies on it. Books whose post date was set to another date, such as the edition date, should get back their date added.
* The books list is sorted by edition date by default. Books without edition date or rating are no longer left out when sorting by them, and come last.
* Reading idea of the week: new kinds of ideas, from translators and collections rated well, authors with many books and none read, anniversaries, the opposite of the latest reads, short books to catch up, books just added and books to read again. A volume of a series is only suggested when it is the first one not read yet, and the kind of the idea of the week changes every week.
* The Library and Reading pile widgets show edition years.

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
