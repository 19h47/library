=== Library ===

Contributors: 19h47
Donate link: https://www.19h47.fr
Tags: books, library, custom post type, taxonomy, isbn, authors, publishers, languages, reading
Requires at least: 7.1
Tested up to: 7.1
Stable tag: 9.45.0
Requires PHP: 8.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Catalogue your books and log your readings: people, series, publishers, ratings, a dashboard, achievements, the recap of each year and a phone app.

== Description ==

Library adds a **Book** post type and five taxonomies, **People**, **Series**, **Publishers**, **Languages** and **Forms**. Catalogue your books and log your readings from the WordPress admin, or from your phone.

= Features =

* **Books** – Cover, subtitle, series and volume, collection number, ISBN, pages, edition date, date first published, excerpt, notes, readings and a rating from 1 to 10 by half points. Its form, such as a novel or a comic, its original languages and the languages of its edition. An ISBN is saved with its hyphens: 9782070360246 becomes 978-2-07-036024-6. A book without ISBN takes its EAN-13.
* **People** – Every name of a book is a person, the author unless another role is chosen: translator, colorist, inker, letterer, publishing director or adapted author. A magazine or an anthology is a collective work: it counts for none of its people.
* **Cycles, collections and pseudonyms** – A series belongs to its cycle, a collection to its publisher, a sub-collection to its collection, as the BnF has them, and a pseudonym to the name the person is known by. Filtering by a cycle, a publisher or a person includes what belongs to them.
* **Readings** – Each reading has a start and an end, either one unknown if need be. A book read again keeps every reading. A book being read is in progress from the day it started.
* **Statuses** – A book is in the library, “Not kept”, borrowed or passed on, or “To buy”. A book not kept stays on the reading pile until it is read.
* **Books list** – Columns for the cover, edition date, ISBN, read state, latest read date and rating, with quick and bulk edit. The search covers every field, and keywords such as `series:SAS` or `read:2024` narrow it: see the Help tab.
* **Terms lists** – People, publishers, series, languages and forms count their books not kept and to buy, and list the terms without books in a view of their own.
* **Dashboard** – Six widgets: Reading, Ratings, Reading pile, Library, Reading idea of the week and Achievements. Each chart has a data table for screen readers.
* **Reading idea of the week** – A book to read, and why: the next volume of a series, an author rated well, an anniversary, a short book… and now and then a book to buy.
* **Achievements** – Computed from the books, nothing stored: authors, series and collections read in full, milestones, records, forms, languages and a few easter eggs. **Books > Achievements** lists them all.
* **Reading app** – The library on a phone, in **Books > App**: the books in progress, the search and its filters, cards or a grid of covers, the diary, the reading idea, the stats, the achievements, the recap of each year, and a page for each book, person, series and collection. Books are added and edited there, an ISBN typed or scanned from its barcode fills the form from the catalogue of the BnF. Without network, it shows what it loaded.
* **REST API** – Books and their taxonomies in the core REST API, and routes for the book form, the BnF, the stats, the readings, the reading idea, the achievements, the recap and the pages of the app: see the FAQ.
* **Translatable** – French translation included.

= Technical =

* Registers the `library_book` post type, edited in the classic screen, the `library_person`, `library_series`, `library_publisher`, `library_language` and `library_form` taxonomies and the book meta, `library_isbn`, `library_reading` and so on, all in the REST API.
* No shortcodes or front-end templates: display books with your theme or blocks.
* The hyphens of an ISBN come from the ranges of the [International ISBN Agency](https://www.isbn-international.org/range_file_generation), in `includes/isbn-ranges.json`. An ISBN outside them, or with a wrong check digit, is kept as typed.
* The reading app needs the Navigation API: Safari 26.2, Firefox 147 or Chrome 135 at least. How it is built: see [ARCHITECTURE.md](https://github.com/19h47/library/blob/main/ARCHITECTURE.md).

== Installation ==

1. Upload the `library` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. Use **Books** in the admin menu to add and manage books, people and publishers.

= Updates =

The plugin updates from the latest release of [19h47/library](https://github.com/19h47/library/releases), like any other plugin in **Dashboard > Updates**. No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder. To publish a release, see [RELEASING.md](https://github.com/19h47/library/blob/main/RELEASING.md).

== Frequently Asked Questions ==

= How do I use the app on my phone? =

Log in on the phone and open **Books > App**. On an iPhone, add it to the home screen with **Share > Add to Home Screen**; on Android, with **Install app** in the browser menu. It then opens full screen and keeps the login of the site. The page of a book edits its reading, and **Edit book** the rest. The **+** at the top adds a book.

= Can several readers share the library? =

No, the plugin has one reader per site. The readings, ratings and statuses belong to the book, not to a user: every user of the site sees and changes the same ones. Each reader keeps their library on their own site.

= How do I log a book read again? =

Add a reading in the Information box of the book, or in its reading in the app: one line per reading, from its start to its end. Leave a date empty when it is unknown. A book read again counts once per reading in the stats, and once in the achievements.

= How do I add a series to a cycle? =

Type the cycle, then the series, separated with a comma, in the Series field of the book: “Star Wars, Young Jedi Knights”. A name not found adds the series, and its cycle if needed. In **Books > Series**, write their description, or move a series to another cycle: its books follow.

= How do I add a pseudonym or a collection? =

In **Books > People**, add the pseudonym, or any other name of the person, as a child of the name they are known by. In **Books > Publishers**, add a collection as a child of its publisher, and a sub-collection as a child of its collection; on the book, check the narrowest one. In the form of the app, type a sub-collection after its collection: “Folio, Science-fiction”.

= Where does the app find a book by its ISBN? =

In the catalogue of the Bibliothèque nationale de France, through its [SRU API](https://api.bnf.fr/fr/api-sru-catalogue-general): the books published in France, an older one by its ISBN-10 too. Only the ISBN is sent, by the site, not by the phone, and the answer is kept a day.

The form fills its empty fields only: the title, the people and their roles, the publisher, the collection and its number, the series and the volume, the subtitle, the pages, the languages and the form. It keeps the names the library has: a person whatever their accents or the order of their name, “Tsukasa Hojo” for “Hōjō, Tsukasa”; a publisher by the publisher part of the ISBN; a collection or a series of the same name. Check it before saving: the catalogue writes its titles in sentence case, and counts the pages its own way.

= Can the app scan the barcode of a book? =

Yes, where the browser reads barcodes: **Scan the barcode**, under the ISBN of the form, takes a photo of the barcode on the back of the book, and its ISBN is typed as if by hand. In a bookshop, the form of a book to add tells at once whether the library has it already. Chrome on Android reads them. Safari, on iPhone too, reads them behind a feature flag: turn on **Shape Detection API** in **Settings > Apps > Safari > Advanced > Feature Flags**. Elsewhere, the pill does not show: type the ISBN, which Live Text of the iPhone reads from the camera too.

= Can I import books from a WordPress export? =

Yes, with **Tools > Import > WordPress**, once the plugin is active, so that statuses like “Not kept” are known. The importer only adds books: it skips a book that already exists (same title and date) and never updates it.

= Can I change the book slug? =

Yes. The post type rewrite slug is `books` by default: use the `library_post_type_args` filter to override the `rewrite` argument.

= How do I get book data from the REST API? =

Books are at `/wp-json/wp/v2/books`, their meta under `meta`. Besides the fields of core, a book gives:

* `credits` – the line shown under its title.
* `main_title` – its title but its subtitle, with its series and its volume, which its page shows above its subtitle, `meta.library_subtitle`; `title` gives them all.
* `readings` – its readings, from the first, as ISO 8601 intervals, either end empty when unknown and `..` for a reading in progress: `2026-10-20/2026-11-01T21:40`, `/2019-06-14`, `2026-11-08/..`. They are written as `meta.reading` through `POST /wp-json/library/v1/books/<id>`; `read` and `in_progress` are worked out from them.
* `names` – its people as named on it, each with its term ID and its role, the schema `library-book-person`, as `POST /wp-json/library/v1/books/<id>` takes and gives them: `{ "id": 12, "name": "Andy Chambers", "role": "author" }`; `people` gives their term IDs only, as core does for a taxonomy.
* `cover`, `series_name`, `language_codes`, `edition_language_codes` and `form_name` – what a list shows, without embedding.
* `publisher_names` – its publishers, each followed by the collections it is in under it, from the widest, each with its term ID, the schema `library-book-publisher`: `{ "id": 7, "name": "Gallimard" }, { "id": 31, "name": "Folio" }`.

The taxonomies are at `/wp-json/wp/v2/people`, `series`, `publishers`, `languages` and `forms`. A series of a cycle, or a collection, has a `parent`. A series gives its `path`, “Star Wars, Young Jedi Knights”, and the series of a magazine its ISSN, `meta.library_issn`; a collection its `path` after the collections it is part of, “Folio, Science-fiction”, and the ID of its publisher, `publisher_id`.

The books take these parameters, besides those of core:

* `forms=<id>` – the books of a form.
* `status=library-not-kept` or `status=library-to-buy` – the books not kept, or to buy.
* `search=series:SAS` – the search keywords. A series is listed in reading order, unless `orderby` is given.
* `orderby=rating`, `orderby=read` or `orderby=started` – by rating, by latest read date or by start of the reading in progress, the books without last.

The routes of the plugin, under `/wp-json/library/v1`, to users who can edit posts unless said otherwise:

* `POST /books` – adds a book as the book form does, kept, not kept or to buy as its `status` says, to users who can publish posts. It gives the book back as `wp/v2/books/<id>` does, `_fields` included.
* `GET /books/<id>` – a book as its form edits it, its names as typed, to users who can edit it; 404 for an ID no book has.
* `POST /books/<id>` – edits a book: only the fields sent change, and a `featured_media` of 0 removes the cover. It gives the book back.
* `GET /books/bnf?isbn=<isbn>` – the book of an ISBN in the catalogue of the BnF, as `GET /books/<id>` gives a book; 404 when the catalogue does not know it, 502 when it does not answer. To users who can publish posts.
* `POST /covers` – uploads the photo of a cover, in its `file` field, named after its book from the fields of its form sent with it, `title`, `people`, `series` and `meta`: `charlie-adlard-walking-dead-vol-25-blood-for-blood.jpg`. It gives the image back as `wp/v2/media` does. To users who can upload files.
* `GET /stats` – the books, the books read this year against last year, and the year the books to read would all be read at this pace.
* `GET /readings` – the readings by month, latest first: `page` and `per_page` count months.
* `GET /idea` – the idea of the week and its reason: `position=1` gives the next one.
* `GET /achievements` – the achievements earned and the closest ones, each with its `target`.
* `GET /recaps/<year>` – the recap of a year, as the screens of the app show it.
* `GET /people/<id>`, `GET /series/<id>` and `GET /publishers/<id>` – the page of a person, a series or a collection in the app: its other names, its cycle or its publisher, its description, its series, the `parts` of a cycle or its collections, its authors, its books in order, its reading, and the completion of a series or a collection.

The routes of the plugin name the meta of a book without the prefix of their keys, `isbn` for `library_isbn`, which the database needs among the meta of every plugin; `wp/v2/books` gives them by their keys, as core does.

A field that counts ends in `_count`, such as `read_count` or `book_count`, a field that holds one ID in `_id`, such as `book_id`, and a list of IDs is named after what it lists, such as `books`.

The REST API answers in the language the `Accept-Language` header of the request prefers, among English and the languages the plugin is translated into, French: its descriptions, its schemas, its messages and what the plugin writes, such as the titles of the achievements, but not what the library holds, such as the names of the forms. It says it in `Content-Language`. Without the header, or with none of these languages, it answers in the language of the site, as it does a request that writes, `POST`, `PUT`, `PATCH` or `DELETE`, so that what it saves, such as the name of a cover, does not depend on who sends it; `_locale=user`, which the reading app sends, in the language of the logged-in user.

== Changelog ==

= 9.45.0 =
* Changed: reading app: the message of a photo without a barcode shows under the pill **Scan the barcode**, rather than among what the ISBN tells.
* Code only: reading app: the components that behave on their own have a custom element of their own as their root, named as the component, without a prefix, in `app/js/components`, on `Component`: `reading-period`, `image-field`, `barcode-scan`, `checkboxes-field`, `inputs-list`, `input-groups` and `offline-status`. They read no store and import nothing of the app, which ESLint checks; their places set their API, their attributes and their properties, and read their events and their fields, rather than reaching inside them. A row removed from a list of inputs tells it with a change.
* Code only: reading app: a field carries its class, `.library-input`, rather than a sheet listing the places that hold fields.
* Code only: reading app: the formats, one level each: `library-app/format`, the language of the page, a function each; `library-app/book-lines`, the lines of a book; `library-app/dom`, what writes in the page.
* Code only: reading app: the shared base styles, revision d7cd1f8: a toolbar is a row of controls, which its place sets where it goes.
* Code only: TypeScript without its strict mode, said in `jsconfig.json`, so that an editor with TypeScript 6 checks what `pnpm check:js` checks.

= 9.44.0 =
* Reading app: the form of a book scans its barcode, where the browser reads EAN-13 barcodes: **Scan the barcode**, under the ISBN, takes a photo of the barcode on the back of the book, and its ISBN is typed as if by hand, which tells a book added already or fills the form from the catalogue of the BnF; a photo without a barcode says so. Chrome on Android reads them, Safari behind its **Shape Detection API** flag; elsewhere the pill does not show.

= 9.43.0 =
* Reading app: the books of the page of a person, a series or a collection come under their title, as on the home, with their number and the choice between their cards and a grid of their covers.
* Changed: reading app: the cards of the page of a person, a series or a collection, its authors or its series, sit side by side, two in the column of the app, one on a phone; four show before the others fold under a pill, rather than three.
* Code only: reading app: the component `cards-group`, the first cards then the others folded, its cards each at least `--library-cards-group-min` wide, filled by `fillCardsGroup()`, rather than `fillFolded()`; the section and its title around it are the page's. The details of the app share `.library-disclosure`, their summary then their contents a gap apart, `--library-disclosure-gap`, rather than a margin of each. `input-list` is `inputs-list`, `.library-inputs-list`, and `inputList()` is `inputsList()`. The page of a term spaces its parts with a gap.

= 9.42.0 =
* Changed: reading app: the arrow between the start and the end of a reading is an icon, drawn as the others whatever the system, rather than the character →.

= 9.41.0 =
* Changed: reading app: the covers of a recap take the width of their column and the height of their book, a soft line and small corners, as the covers of the app, rather than images cut to two thirds; the cover beside a record too.
* Changed: reading app: a textarea of the form of a book is made higher by hand only, as wide as the form.
* Fixed: reading app: in the grid of covers, a card no longer cuts the corners of its cover with its own; a book without a cover shows its title in the frame of its cover, as the rows of covers do.
* Code only: reading app: its components, one way. Each is markup alone, a component per content, `app/components/<name>.php`, its root the class of its name, `.library-book-card`, `.library-book-row`, `.library-term-card`, `.library-achievement-card`, `.library-book-header`, `.library-book-fields`, `.library-views-nav`, `.library-search`, its arguments the same words everywhere; the brick that gives it a behavior stays around it in the app. New components: `book-cover`, the image of a book or its title in a frame, wherever a cover shows, filled by `fillBookCover()`; `isbn-status`, what the ISBN typed tells; the cards, the page of a book, the views nav, the search and the link to the recap, which the styleguide renders as the app does. The scripts reach elements by a `data-*` attribute or their semantics, never a class of style. The filters open by a command of their dialog.
* Code only: reading app: its design system written down, `DESIGN-SYSTEM.md`, and checked: ESLint fails on a script that reaches an element by a class, `pnpm lint:css` on a length off the scale, a color written as it is, or a margin or a width a component gives itself, and `tests/e2e/styles.spec.js` on a change of the computed styles of the styleguide, on a phone and a wide screen, in both themes. The shared base styles, revision 53f8830: corners are `--app-radius-xs` to `--app-radius-m`, rather than `--app-radius` and `--library-radius-small`.

= 9.40.0 =
* Reading app: the page of a book reads as its title page: its cover, its title, its subtitle on a line of its own, smaller, as on its cover, its credits and its translation, then its excerpt. Its readings are summed up in a line, how many times it is read, the end of the latest reading and how long it took, "Read 2 times · March 21, 2025 · in 12 days", which opens them to change them; the row "Read in" is gone.
* Reading app: the fields of a book in three groups, each name beside its value: its work, with its people by role, its first publication, its original languages and its form; its edition, with its publisher and its collections, each linked to its page, its number in its collection, its edition date, its ISBN, its pages and the languages of its text; its copy, with its printed date and the day it was added.
* REST API: a book of `wp/v2/books` gives `main_title`, its title but its subtitle, with its series and its volume, and `publisher_names`, its publishers, each followed by the collections it is in under it, from the widest, each a `library-book-publisher` with its term ID; `publishers` stays the term IDs of core.
* Code only: reading app: the shared base styles, revision 9ea6527: space is `--app-space-xs` to `--app-space-xl`, `--app-space-m` at `--app-u1`, rather than `--app-gap-small`, `--app-gap` and a step of the scale; the steps `--app-u0` to `--app-u3` stay the scale of sizes. The fields side by side are `.library-fields--aside`; the styleguide shows the five spaces.

= 9.39.0 =
* Breaking: a book has three dates, each a year, a month or a day: the copy in hand printed, `library_printed_date`, "Printed", as its colophon says; the edition of its ISBN, `library_edition_date`, "Edition date", whatever the printing of the copy, as the catalogue of the BnF records it; and the work first published, `library_published_date`, as before. The edition date is no longer a full date only. In the REST API, `printed_date` in the routes of the plugin and `library_printed_date` in `wp/v2/books`. Migrate the data after updating, with your table prefix, for the books that are or were in hand, whose date was the one of their copy: `UPDATE wp_postmeta AS m INNER JOIN wp_posts AS p ON p.ID = m.post_id SET m.meta_key = 'library_printed_date' WHERE m.meta_key = 'library_edition_date' AND p.post_type = 'library_book' AND p.post_status <> 'library-to-buy'`, then, a 1 January standing for a year alone, `UPDATE wp_postmeta SET meta_value = LEFT( meta_value, 4 ) WHERE meta_key IN ( 'library_printed_date', 'library_edition_date' ) AND meta_value LIKE '____-01-01'`.
* The form of a book gives the printed date among the fields most books have, the edition date among the details; the catalogue of the BnF fills in the edition date, from its publication statement, such as "1989", "DL 2019" or "cop. 2002", but for "impr. 2015", the printing of its own copy. A book shows its three dates.
* Changed: the dashboard counts the books by the year their copy was printed, and the oldest book to read is the one printed the longest ago; the books list shows and sorts by the printed date. The credits of a book give the year of its edition, else of its printing; the date of a book, for the achievements and the recap, is the one of its work, else of its edition, else of its printing. The reading idea of an anniversary of the edition takes the edition date only.
* Code only: reading app: the shared base styles, revisions 4c2cbbe, ee476ba and 62d5a50: the layers app-base, app and app-utilities, the styles of the app in app.base, app.components and app.views; a list is `.app-list` and its items `.app-item`; a meta line is its utilities, `app-text-s app-text-muted app-font-ui app-uppercase`, rather than `.app-meta`; the sizes of the text are `--app-text-s` to `--app-text-3xl`. The recap sets the properties of its components, a cover link lays out what it holds, a book being read, `.library-current`, is a component, the action of a title is `.library-title__action`.
* Code only: `Meta::DATE_KEYS` holds the three dates, all partial; `Meta::sanitize_date()` and the date control are removed; `Books::format_partial_date()`.

= 9.38.0 =
* Reading app: the form of a book groups its people by role, a group each, the authors first, shown once it holds a name: a person goes in the role chosen beside the button that adds them, which stays chosen, so as many translators as a book has are added without choosing their role each time.
* Breaking: REST API: the `names` of a book of `wp/v2/books` give each name with its role, as the people of `library/v1/books/<id>`, which give their term IDs too: one schema, `library-book-person`, a book given sent back as it is. The role meta, `library_translators`, `library_colorists`…, are no longer in the REST API.
* Reading app: the reference of the API names a schema by its title, such as `library-book-person[]`, and gives the values of a property, such as the roles, and the properties of an argument, such as those of a person of a book.
* Code only: the component `input-groups`, an `input-list` in groups; `inputList()` takes `into`, the list a new input goes in. A book of the reading app gives its `people`, each with its role, which `getPeople( book, role )` reads, rather than its `authors`, `translators`…

= 9.37.0 =
* REST API: a request that reads is answered in the language its `Accept-Language` header prefers, among English and French, as `Content-Language` says: its descriptions, its schemas, its messages and what the plugin writes. Without the header or one of these languages, and for a request that writes, in the language of the site; `_locale=user`, which the app sends, first.
* Fixed: reading app: a date of a reading typed at the keyboard no longer empties at the next key in Chrome, and is saved once left, rather than on the way, such as an end with its former year.
* Fixed: reading app: the styleguide counts the books of the folded authors again.
* Code only: reading app: the components are built as those of a design file, each level made of the ones before it, atoms of their own rather than the classes of a view, such as `.library-heading` and `.library-cover`, sized by its place; the PHP components `back`, `term-header` and `dialog-close`, every dialog closed by a command; the styleguide ordered so, from the foundations to the atoms, the molecules, the organisms and the screens.

= 9.36.0 =
* Reading app: the reference of the API, for development, opened by searching `api` then Enter or the button of the search: every route of `library/v1`, then the routes of `wp/v2` the app reads, as the REST API describes itself, with their arguments, what they give, and a GET request to try.
* Reading app: the search is a search landmark, a form submitted by Enter or by its button, a magnifier: submitted, it searches at once, or opens the styleguide or the reference of the API typed. The styleguide no longer opens during the typing.
* Reading app: its icons are those of Material Symbols, as its own icon: add, the theme, close, back and search. The styleguide lists them.
* Reading app: the excerpt and the notes keep room for three lines, empty or made smaller by hand.
* REST API: every route of `library/v1` describes what it gives, as a JSON schema the index gives asked for help.
* Breaking: REST API: `POST /wp-json/library/v1/covers` uploads the photo of a cover, in its `file` field, named after its book from the fields of its form sent with it, and gives the image back as `wp/v2/media` does, rather than `GET books/cover-name` naming it first. No data to migrate, as for the changes below.
* Breaking: REST API: what is no book leaves `books/`: `library/v1/stats`, `readings`, `idea` and `achievements` replace `books/reading-stats`, `books/readings`, `books/reading-idea` and `books/achievements`.
* Breaking: REST API: a count ends in `_count`, one ID in `_id`. The stats give `read_count` and `book_count`, and in `year_to_date` `read_count`, `last_year_read_count`, `projected_read_count` and `to_read_count`; the idea `book_id` and `idea_count`; a reading of `readings` its `book_id`; the pages of a person, a series and a collection `read_count`, a person `page_count`, their series and authors `book_count` and `read_count`, a cycle its `parts`; the recap `day_count`, `reading_count`, `previous_reading_count`, `reread_count` and `added_count`, the `reading_count` of its forms, people, languages and series, and in `calendar` `read_day_count` and `dated_reading_count`.
* Breaking: REST API: `wp/v2/publishers` gives the `path` of a collection and the ID of its publisher, `publisher_id`, rather than `collection` and `publisher`.
* Breaking: REST API: the routes of the plugin take and give the meta of a book by their names, without the prefix of their keys: `isbn` for `library_isbn`. `wp/v2/books` keeps their keys, as core does.
* Changed: REST API: `GET` and `POST /wp-json/library/v1/books/<id>` answer 404 for an ID no book has, before any right is checked, rather than an invalid parameter.
* Code only: `Markup::render_icon()`, `Meta::get_name()`, `to_names()` and `from_names()`, and `Rest_Schemas`, whose schemas a test checks against what each route gives.

= 9.35.0 =
* Breaking: the ISSN of a magazine is the one of its series, as the catalogue of the BnF gives it to the serial rather than to each issue: `library_issn` is a term meta of `library_series`, in the format of the BnF, such as 1168-9188, edited on the screen of the series, given by the REST API in the `meta` of `wp/v2/series` and in `issn` of `library/v1/series/<id>`, and shown on the page of the series in the app. A book has no ISSN of its own any more. Migrate the data before updating, with your table prefix: `INSERT INTO wp_termmeta ( term_id, meta_key, meta_value ) SELECT tt.term_id, 'library_issn', UPPER( MIN( pm.meta_value ) ) FROM wp_postmeta pm JOIN wp_term_relationships tr ON tr.object_id = pm.post_id JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'library_series' WHERE pm.meta_key = 'library_issn' AND pm.meta_value REGEXP '^[0-9]{4}-[0-9]{3}[0-9Xx]$' GROUP BY tt.term_id`, then `DELETE FROM wp_postmeta WHERE meta_key = 'library_issn'`.
* A search for an ISSN finds the issues of its series.
* Changed: a book without ISBN is slugged by its title and volume number, such as the issue of a magazine: white-dwarf-56.
* Code only: the screen of a term edits the term meta registered on its taxonomy, `Admin\Term_Fields`.

= 9.34.0 =
* Reading app: its login is dressed as the app, when the app asks for it: its icon and its name, its theme, its fields, its pills and its controls, in a frame in the middle of the screen, the help of "Remember Me" over the width of the form. "Remember Me" is checked as the form first shows, the app being opened on a phone of one's own. The lost password and the screen that says to check the email are dressed in their turn and lead back to the view of the app the login came from. Any other login stays the one of WordPress, as the screen of the link in the email.
* Code only: the app and its login share their base styles, `Page::register_base_styles()`, and the key of localStorage, `Page::STORAGE_KEY`.

= 9.33.1 =
* Changed: reading app: its icon is the book of Material Symbols, like the icons of the other apps of the suite, rather than the one of Dashicons; still grey on dark on the home screen of a phone, following the theme in the browser tab.
* Code only: the series rules of the BnF are written once: a series statement is a series when the catalogue says so or the library has a series of that name, and the series of the library are loaded once per record.

= 9.33.0 =
* Reading app: the recap of a year has a screen of its days, Day by day: the days read, the most in a row, the day the most pages were read and the readings with their start known, over the calendar of the year. A day is read while a reading ended was open, or on its end alone when its start is unknown, as dark as the pages read that day; the memorable days, the biggest one and those a favourite was finished, pulse.
* Fixed: reading app: a date typed in the form of a reading is saved once left, rather than at each key: its error shows then, without taking the focus back, and goes as soon as it is valid, an end as soon as its start goes before it.
* Fixed: reading app: Chrome no longer lists issues on the app: the covers take the shape of their book from their width and height, a photo picked no longer keeping the size of the cover it replaces, and the search fields of the styleguide have an id.
* Code only: a book holds its readings, its read dates worked out from them.

= 9.32.2 =
* Fixed: reading app: the link to a book already added, under the ISBN typed in the form of a book to add, opens its page rather than the home.
* Changed: reading app: in the grid of covers, the title of a book without a cover no longer breaks its words with hyphens, as before 9.32.1: the comma sent to the next line followed a space typed before it.

= 9.32.1 =
* Fixed: reading app: in the grid of covers, the title of a book without a cover breaks its words with hyphens, its comma with its word, rather than at the start of the next line.

= 9.32.0 =
* Breaking: a reading has its start and its end, one row each in `library_reading`, an interval of ISO 8601, either left empty when it is unknown, the end of a reading in progress `..`: `2026-10-20/2026-11-02`, `/2019-06-14`, `2026-11-08/..`. It replaces `library_read_date` and `library_in_progress`: a book is read and being read from its readings, which the REST API gives in order in `readings`. Migrate the data before updating, with your table prefix: `UPDATE wp_postmeta SET meta_key = 'library_reading', meta_value = CONCAT( '/', meta_value ) WHERE meta_key = 'library_read_date'`, then `INSERT INTO wp_postmeta ( post_id, meta_key, meta_value ) SELECT post_id, 'library_reading', CONCAT( CURDATE(), '/..' ) FROM wp_postmeta WHERE meta_key = 'library_in_progress' AND meta_value = '1'`, then `DELETE FROM wp_postmeta WHERE meta_key = 'library_in_progress'`.
* Reading app: a book being read is in progress since the day it started, which its form shows and changes; Read, or a reading added, ends it from that start, and a book read again keeps each reading. Each reading shows from its start to its end, and the page of a book says how long its latest reading took. The books being read on the home come the latest started first.
* Changed: the Information box of a book and quick edit follow: In progress with the day it started, a line per reading, quick edit on the end of the latest one.
* Fixed: a book restored from the trash comes back in its status, in the library, not kept or to buy, rather than as a draft, which left the app and the stats.
* Changed: the publish box of a book shows its Library menu alone, without the status and Save Draft of core, which contradicted it.
* Code only: the tools of the checks come from the lockfiles, cached: WP-CLI is a dependency of Composer, for the translations here and on GitHub.

= 9.31.1 =
* Fixed: reading app: a book opened shows at once, in its transition, rather than seconds later without one: the transition waits a moment at most for the cover of the book, which a slow network held.
* Changed: reading app: a cover the app keeps shows at once, without waiting for the network, as on a slow network the first time the app opens.

= 9.31.0 =
* Changed: reading app: the languages of a book in its form show the ones chosen, a pill each that removes it, and a button that opens all of them in a sheet, a checkbox each, rather than a pill for every language of the library.
* Changed: reading app: the walls of covers of a recap keep the shape of each book, in masonry where the browser lays out grid lanes, as Safari 26.4.
* Changed: reading app: on a touch screen, a select, such as the rating of a book, opens the picker of the system, made for a finger, rather than the one of the app, which Safari 27 made hard to pick from on a phone.

= 9.30.1 =
* Fixed: reading app: nothing goes out of a phone of 320 px any more: a pill longer than the line, such as the name of a long series, goes on several lines, and the button that removes a read date stays in the form.

= 9.30.0 =
* Changed: reading app: the books being read on the home no longer have a button Finished: a book is read, rated and dated in its page, which its cover opens.
* Fixed: reading app: the styleguide no longer scrolls sideways on a phone: its largest sizes of the text break their words, hyphenated where the language allows it.

= 9.29.3 =
* Fixed: reading app: the cover of a book being read no longer stays blank in Safari once back from the book: its button Finished goes under its title, a pill, rather than over the cover, and nothing floating is blurred any more, the bars, the controls and the notices opaque; Safari laid out the cover wrongly under a blurred control once the view transition ended (WebKit bug 275698).
* Changed: reading app: the covers of the books being read load as the others do again, lazy, at the size they show.

= 9.29.2 =
* Changed: reading app: a view gives way to the next one rather than both crossing, the view left fading out before the one opened fades in; the header, the toolbar and the tab bar stay as they are; the cover of a book opened goes to its page at once, the one of its card lent until its own has loaded.

= 9.29.1 =
* Fixed: reading app: the cover of a book being read no longer stays blank in Safari once back from a book, until it went out of sight: the covers being read load at once, at the size they show.

= 9.29.0 =
* Reading app: the recap of a year, each screen as high as the screen, the page stopping at each one only: the wall of covers of the first one cut under its fade; the favourites, every book rated, in a wall of covers; the people read for the first time and the achievements in clouds of small pills; the records a line each with their cover and their figure; the last screen over the covers of the year in columns going by, still for a reader who asks for less motion. On a phone, the recap leaves the footer out; beyond, its screens end above it.
* Reading app: the name of the app on a line with the buttons of the toolbar, and the header a small step above what follows.
* Fixed: a reading added again the same day, or a book finished again the same day, is saved, and two readings of a day stay two.
* Fixed: the volumes of a series past the hundredth show, and are counted.
* Fixed: a search for a series finds it whatever its accents, and the form of a book suggests the collections of a publisher whatever its case and accents.
* Fixed: a language typed by another of its codes, such as fra, is the one the library has; the catalogue of the BnF numbers a volume "3-4" as 3.
* Fixed: the read date of the books list after its checkbox is in the format of the site; the form of a book saved unchanged sends nothing.
* Changed: a book is translated, for the dashboard as for the reading ideas, when a translator is credited or its text is in a language it was not written in; a book is dated by when it first came out, or else by its edition, for the reading ideas as for the achievements; a series of the recap is finished as the achievements finish it, and a collective book counts for none of its authors there either; Back to the start counts a volume without series as one of its title; the latest reads of the reading ideas have a date.
* Changed: the screen of the languages says languages, a book its original languages; its view of the languages without books leaves out the edition languages. A book added from the admin is in the language of the reader.
* Changed: REST API: library/v1/recaps/<year> gives every book rated of the year in best, rather than five; the series and collections of wp/v2 give their path; the page no longer preloads the volumes of the book shown.
* Code only: each rule written once, the readings, the stats of a year, the averages, the names and the codes among them; the readings of a request worked out once; the styles of the app in cascade layers, a file per component and per view; base styles from the gist, revision ba28065.

= 9.28.0 =
* Edition languages: the languages of the text of a book, several for a multilingual edition, in its form in the app and in the box of the admin. A book added is in the language of the reader, unless the catalogue of the BnF gives others.
* Changed: a book is read in its original language when its text is in its original languages only, none the one of the reader, rather than when it has no translator: the tag VO and the achievement In the original follow it. A translated book, for the reading ideas, has a translator credited or a text in a language it was not written in.
* Changed: reading app: the form of a book checks its original languages, a pill each, and keeps them all.
* Changed: the chart of the average rating by year read counts a book read again in a year once, as the recap does.
* Breaking: REST API: the route of the book form takes and gives languages, the term IDs of the original languages, rather than language, and its meta library_edition_languages; wp/v2 books give edition_language_codes.
* Code only: the readings, their counts by year and by month, the days of a year and the average rating are computed once, for the stats, the dashboard, the recap, the achievements and the reading ideas; the styleguide shows the two forms of a status together.

= 9.27.0 =
* Reading app: the recap of a year, a screen per subject in a frame as high as the screen: the books read against the year before, the pages read, the readings of each month, the forms, the people read the most and those read for the first time, the original languages, the series started and finished, the average rating with the books rated the highest, the records, the achievements earned and the books added. Its figures count up as their screen comes. The diary leads to the recap of each year gone by, the home to the one of the year just over, in January.
* REST API: library/v1/recaps/<year> gives the recap of a year.
* Code only: reading app: one row of pills and one strip that scrolls sideways for every list of pills and of covers; the sizes of the text named after the keywords of font-size, from the shared styles.

= 9.26.3 =
* Code only: reading app: the list of books and the Covers checkbox are components of their own, which the books and To buy views share.

= 9.26.2 =
* Fixed: reading app: the cards of a list keep their height in Safari from the grid of covers back to the list, rather than cut, and every cover shows in the grid.
* Fixed: reading app: the title of a book, a person, a series or a collection opened takes the focus without its outline on iOS.

= 9.26.1 =
* Fixed: reading app: the cover of the book open keeps its shape in Safari, rather than squeezed into a wide frame.

= 9.26.0 =
* Reading app: a book added is filled in from the catalogue of the BnF: its ISBN, unless a book of the library has it, fills in the fields left empty, its title, people with their roles, publisher, collection and number, series and volume, subtitle, pages, original language and form, with the names the library gives them.
* Reading app: a view gives way to the next one in a view transition, the cover of a book opened going from its card to its page and back; without motion, the views only fade. The app needs Chrome 135, Safari 26.2 or Firefox 147.
* Reading app: the books and the books to buy show as a grid of their covers with the Covers checkbox, at the end of their title, the choice kept, in masonry where the browser lays out grid lanes, as Safari 26.4.
* Reading app: a title with an action at its end, such as Another idea, goes on balanced lines, the action at the end of the last one, out of the heading, which names the section alone.
* REST API: `GET /wp-json/library/v1/books/bnf?isbn=<isbn>` gives the book of an ISBN in the catalogue of the BnF, as the form of a book takes it.

= 9.25.0 =
* Fixed: a series, a path of terms or the name of a language no longer fail on an error of WordPress.
* Code only: the classes of the plugin are in the namespaces `Library`, `Library\Admin` and `Library\App`, loaded by Composer; a book is a `Library\Book`, a readonly object; its status a `Library\Status` and the kinds of achievements a `Library\Achievement_Kind`, enums; PHPStan reads the plugin at level 8.

= 9.24.0 =
* Fixed: reading app: a book added or bought opens its page with no flash, rather than the home and the lists going and coming back.
* Fixed: reading app: the tabs fit on a phone in English, Achievements no longer running over To buy.
* Changed: WordPress updates the plugin from `library.zip`, the package each release now has, with the dependencies of Composer.
* Code only: the reading app has its tests in a browser, PHPStan reads the PHP, ESLint checks the scripts with the rules of WordPress and TypeScript their types from their JSDoc; a pull request runs them all.

= 9.23.1 =
* Fixed: reading app: a photo picked for the cover of a book shows in its shape, rather than squeezed into a wide frame.

= 9.23.0 =
* Changed: a book not kept and not read is on the reading pile, as a book passing through: the tsundoku and the archaeologist count it again, as the dashboard, the app and the reading ideas do; one rule for all of them.
* Changed: `POST /wp-json/library/v1/books` and `POST /wp-json/library/v1/books/<id>` give back the book, as `/wp-json/wp/v2/books/<id>` does, `_fields` included, rather than its ID alone.
* Changed: the tables of the charts of the dashboard, which assistive technologies read, are named by their chart.
* Code only: a book saved in the reading app or checked Read in the books list makes one request less; the route of the book form looks each name up once; the counts of a term are padded with its children on the lists of terms only; the page of a person comes with the reading app.

= 9.22.0 =
* Changed: reading app: the reading of a book saves each change at once, with Undo in the notice, as Finished does on the home; the button Save goes.
* Changed: reading app: on a phone, the bar of the views shows all of them, as the tabs of an app, rather than a row that scrolled past the edge of the screen.
* Changed: reading app: the page is named after what it shows, such as "Journal – Library", so that the history and the tabs tell the pages apart.
* Changed: reading app: the covers are sharp on a dense screen and no heavier in the lists: the REST API gives each cover its srcset, and each image takes the size it shows at.

= 9.21.0 =
* Breaking: `library_read` is no longer stored: a book is read once it has a read date, known or not. The forms and the REST API keep the field Read, which the route of the book form writes as read dates, as before; core's `/wp-json/wp/v2/books` no longer writes it. Its former rows are left unread and can be deleted, with your table prefix: `DELETE FROM wp_postmeta WHERE meta_key = 'library_read'`.
* Breaking: the reading idea no longer gives its `week`, and the readings by month, the reading idea and a book added or edited no longer link their books to embed with `_embed`.
* Changed: reading app: the cards, rows and covers of the books are links, which open in a new tab or show a preview on a long press; a book finished on the home leaves the focus to the next book being read.
* Changed: the checkbox Read of the books list goes back as it was when its book could not be saved, and the new percent read is said to assistive technologies; the cover of a row is out of the keyboard, its title linking to the book already.
* Changed: faster: the dashboard and the reading stats count from the books kept in the cache, the reading ideas and the achievements load their terms at once (723 and 326 queries down to 20 and 23 when they are computed again), the covers of the books list are their medium size rather than the full image, and a book saved in the reading app loads again only what shows.
* Changed: reading app: the service worker keeps the app apart from the covers and the data, so that it still opens without network after a long scroll.
* Fixed: reading app: the reading form keeps what was typed when the app comes back after a while; a resource that could not load loads again as it shows or once the network is back; a page of a person, a series or a collection loaded again shows its books as they are; the volumes of a book stay while they load again.
* Fixed: the average rating by year counts each rating once; the labels "Uploaded to this book" and "Filter books list" have no typos; the Rating column says the rating as the app does, translated.

= 9.20.2 =
* Fixed: reading app: the picker of a pill select opens above it where it fits there whole and not below, in Safari 27 too; it scrolls only where it fits on neither side.

= 9.20.1 =
* Fixed: reading app: the picker of a pill select stays within the screen in Safari 27, on the side of its select with the most room, and scrolls; the one of the rating slid over its select and off the screen.

= 9.20.0 =
* Changed: reading app: the select of a pill opens its options in a picker that floats as a panel, below or above it and within the screen, where the browser allows a customizable select; the button of the theme shows when the dark theme is on.

= 9.19.1 =
* Fixed: the terms lists show their counts, of the books in the library, not kept and to buy, as wide as one another, rather than the books to buy taking the room left; the lists of series, languages and forms included.

= 9.19.0 =
* Changed: the lists of people, publishers, series, languages and forms count the books to buy of each term in a column of their own, beside the books not kept, and list the terms without books, in any status, in a view of their own, Without books.

= 9.18.0 =
* Changed: reading app: what floats over the content lets it show through, blurred: the controls, the button Finished over a cover among them, the footer, the bar of the views on a phone and the notice; a control fades its icon and its border at rest, never its background. Text keeps 4.5:1 and an icon at rest 3:1, whatever passes under it.

= 9.17.0 =
* Changed: reading app: the books being read show as their covers, in a row that scrolls, each with its button Finished over its corner; the notice of a reading saved offers Undo, which puts the reading back as it was, and stays while the pointer or the focus is on it.
* Changed: reading app: on a phone, the views are in a bar at the bottom of the screen; the reading idea of the week shows as a row on the home, its next idea at the end of its title.
* Fixed: reading app: on a phone, a tap below the search right after typing, such as on Filters or on a book found, reaches what it is on.
* Code only: the sections and the reading idea space what they hold with a gap, as the frames of the styleguide do; the ranges of the ISBN are written again only when they change.

= 9.16.0 =
* Breaking: requires PHP 8.4.
* Code only: new without parentheses around it, array_find() and array_any(), typed class constants and #[\Override], as PHP 8.3 and 8.4 allow; the tests run on PHP 8.4 and 8.5.

= 9.15.0 =
* Changed: reading app: the home opens with the books being read, each with a button Finished that reads it today; the year shows on one line, the projection and the books to read in the achievements; the filters and the order sit under the title of the books they order; one achievement close to it shows, the others in the achievements.
* Changed: reading app: the dashboard is a link in words in the footer, rather than an icon in the toolbar.
* Fixed: reading app: a tap below the search, such as on Filters or on a book found, reaches what it is on, rather than what the keywords of the search moved under it as they hid.

= 9.14.0 =
* Changed: the Library widget of the dashboard lists the forms of the books at hand, every one of them, by number of books then of books read, each linked to its books.
* Fixed: the rankings of the dashboard agree "read" with the number of books read: "7 livres, 1 lu" rather than "7 livres, 1 lus".

= 9.13.2 =
* Fixed: a volume whose title is a series with a comma, such as "Talli, fille de la lune", is named after its series only, "Talli, fille de la lune, vol. 1", rather than "Talli, fille de la lune, vol. 1: Talli, fille de la lune".

= 9.13.1 =
* Fixed: a series or a collection whose name has a comma, such as "Talli, fille de la lune", is found whole when it exists, rather than split into a cycle and a series when its book is saved; the series: search finds it too. A new name with a comma is added in Books > Series or Books > Publishers first.

= 9.13.0 =
* Changed: a hat-trick takes three different books rated 10 in a row; namesakes are the latest two books read with the same title by different authors; a book not kept and not read is off the reading pile, as a book to buy is, for the tsundoku and the archaeologist.
* Changed: the reading idea suggests to buy the first volume of the next part of a cycle once a part is read in full, as it does for a book to read.
* Fixed: the tsundoku counted the books to buy.
* Fixed: an easter egg of books, such as a leap day or the Ultimate Question, counted a book read on two such days, or answering twice, as two books.
* Fixed: a go-between led to the first book of each language only, and hid the books kept when those were not kept.
* Fixed: a volume without series is a volume of the series named by its title again, for the reading idea and the reading order, as before 9.5.0.
* Fixed: the completion on the page of a person, a series or a collection in the reading app had no date.
* Fixed: a collection read in full was named with the collection it is part of, and one read by half counted twice, with it.
* Fixed: a book read again whose first reading has no date said "First read in" with no year.
* Code only: PHPUnit tests of the achievements and the reading idea, `composer test`, run on each pull request with the coding standards and the translations (TESTING.md); WPCS 3.4.1.

= 9.12.2 =
* Fixed: in the form of a book of the reading app, the original languages show at once, rather than "No languages" until the names to suggest had loaded; a book saved meanwhile no longer loses its language.

= 9.12.1 =
* Fixed: in the reading app, a book without reading, marked as read with an unknown date, kept today as its read date rather than the unknown one.

= 9.12.0 =
* Reading app: its links are links, as the browser has them: one opens in a new tab or window, from a long press on a phone too. Going back puts a view back where it was at once, rather than scrolling to it, and the page of a book gone back to gives the focus back to the link that left it. The link to the books keeps their search, the name of the app leads to the home without it. It needs the Navigation API: Safari 26.2, Firefox 147 or Chrome 105 at least.
* Code only: the reading app has a router, `library-app/router`, on the Navigation API: the URL says what shows, and `urlOf()` and `routeOf()` build and read it; the store shows it through `show( route, navigationType )` and no longer reads nor writes the URL or the history. The store is a module, `library-app/store`, on `library-app/api`, which asks the REST API and turns what comes back into plain text, and `library-app/state`, the observable store; its resources are described once, their empty state, their first load and how they load again in place. The pages of a person, a series and a collection stand on `TermPage`, `library-app/term-page`. `library-app/app` is the only module that reads what the page gives, `wp.apiFetch`, `wp.i18n`, `wp.url` and the config, and no longer gives the store: the bricks import it from `library-app/store`. `library-app-store.js` gives way to `library-app-theme.js`, the theme chosen before the first paint; the links lose their `data-view`, `data-person`, `data-series`, `data-publisher`, `data-book` and `data-search`, and the config its URLs but `appUrl`.

= 9.11.0 =
* Faster: the books are kept in the cache, as the achievements and the reading idea are, until a book changes: the page of Gallimard in the reading app took 492 ms and 48 queries, 22 ms and 9 once kept; the page of a person or a series as much. A new version of the plugin empties the cache, which no longer keeps what a request computed after it changed a book.
* Dashboard: the collection widget counts its rankings and its numbers from the books kept in the cache, 41 ms rather than 111; two books whose translator was deleted no longer count as translated.
* The achievements and the reading idea find the cycles of a series by its terms, no longer from its name: a series with a comma in its name counts in its cycle.
* Code only: `Library_Achievements::get_completion( $kind, $term_id )` replaces `get_author_completion()`, `get_series_completion()` and `get_collection_completion()`; `Library_Books::get_cycle()`, `Library_Taxonomies::build_top_level_table()` and `build_collections_table()` are removed; a book of `Library_Books::get_books()` gives `series_ids`, `publisher_ids`, its publishers under their widest ancestor as its authors, `language_ids` and `collection_number`; the search finds the names of terms through `get_terms()`, a translator under any of their names as an author. In the reading app, `store.openPage( view, id )` replaces `openPerson()`, `openSeries()` and `openPublisher()`, and `pageLink()` the links of each page; the completion, the authors and the pills of a page are filled by `fillCompletion()`, `fillAuthors()` and `fillPills()`.

= 9.10.0 =
* Reading app: the page of a collection, or of a publisher, opened from the achievements of a collection and from one collection to another: its name, its publisher and the collection it is part of, its description, how many of its books are read and the completion they count for, its collections, its authors, then its books by collection, each in the order of its number, “Folio no. 1, 2, 4…”.
* Publishers: collections at any depth, a sub-collection under its collection as the BnF has it, such as Science-fiction under Folio under Gallimard. The credits name it after its collection, “Gallimard, Folio, Science-fiction no. 271”; a search for a publisher or a collection finds the books of its sub-collections; the achievements, the reading idea and the dashboard count them in their collection; the form of the app types a sub-collection after its collection, “Folio, Science-fiction”.
* REST API: `GET /wp-json/library/v1/publishers/<id>` gives the page of a collection, or of a publisher; the `collection` of `library/v1/books` and `books/<id>` is typed after its collection.
* Code only: `Library_Taxonomies::get_path_id()` replaces `get_series_id()`, for a series as for a collection; the ranges of the ISBN from the message of 2 November 2026.

= 9.9.1 =
* Fixed: the dashboard runs faster on MySQL 5.7: the oldest unread book and the average rating by year read took 133 and 59 ms on production, and the achievements, computed again once a day or after a book changes, looked each series up by its name, one query each, 307 in all.

= 9.9.0 =
* Reading app: the link to the dashboard under the form of a book is muted, as the other small text of the interface.
* Code only: the small text of the interface is the class `.app-meta` of the shared base styles, revision a778521, rather than a list of selectors of the app; `.library-push-end` puts the link of a title at the end of its row.

= 9.8.0 =
* Reading app: the page of a person shows their series as cards, before their books, whatever their role, those with the most of their books first: its name after its cycles, which leads to its page, its description, clamped at three lines, and how many of their books it holds and are read, “9 books · 9 read”. Past four, three show and the others fold under a pill that counts them, “5 other series”.
* REST API: `GET /wp-json/library/v1/people/<id>` gives their `series`, with their description and how many of their books they hold and are read.

= 9.7.0 =
* Breaking: `GET /wp-json/library/v1/people/<id>` gives the `other_names` of a person, no longer `pseudonyms`.
* People: a person is named as they are known, “Hergé”, and their other names are its children, a pseudonym as well as their birth name or another spelling, “Georges Remi”. The page of a person in the reading app lists them as “Other names”, no longer “Pseudonyms”.

= 9.6.0 =
* Reading app: the page of a series shows its authors as cards, other roles left out, those who wrote the most first: their name, which leads to their page, their description, clamped at three lines, and how many of its books they wrote, unless it has only one. Past four, three show and the others fold under a pill that counts them, “56 other authors”.
* REST API: `GET /wp-json/library/v1/series/<id>` gives its `authors`, with their description and how many of its books they wrote.

= 9.5.1 =
* Reading app: the volumes of a series on the page of a book show their covers as the home does, without a caption under each one; their title counts the ones read, “21 books · 21 read”, and each cover says its reading state to assistive technologies only.
* Reading app: in the form of a book, a person takes a line for their name, then their role beside the button that removes them, rather than that button alone on a line on a phone.

= 9.5.0 =
* Breaking: series are terms of the hierarchical taxonomy `library_series`, a series a child of its cycle, rather than the `library_series` meta, which is no longer read. A book with a volume number but no series, as a manga, no longer counts as a series of its title: it needs a series of that name. Migration: give each book its series, the path of its meta, else its title, with `series` through `POST /wp-json/library/v1/books/<id>`, then delete the meta, with your table prefix: `wp db query "DELETE FROM wp_postmeta WHERE meta_key = 'library_series'"`.
* Series: a description, written in **Books > Series**, and a page in the reading app, with its cycle, its description, its reading and its completion, the series of a cycle, then its books in reading order. The volumes of a book and the achievements of a series lead to it. A volume whose title is its series is named after its series only: “City Hunter, vol. 1”.
* REST API: series at `/wp-json/wp/v2/series`, a book's in its `series` field, its path in `series_name`; `GET /wp-json/library/v1/series/<id>` gives the page of a series; the routes `library/v1/books`, `books/<id>` and `books/cover-name` take the `series` after its cycles.

= 9.4.0 =
* Reading app: the reading idea and the achievements come at once, kept until a book changes, as the reading order of the series, rather than computed from every book at each request.

= 9.3.0 =
* Reading app: faster. A list of books weighs 25 KB rather than 169 KB, the page brings the first data of the home or of a book with it, and the reading order of a series is kept until a book changes. A list loading again fades, a book not loaded yet opens at once, saying it loads, and a save button says it saves.
* Reading app: the form of a book shows first the fields most books have, its ISBN first, then its title, people, series and volume, publisher, collection and its number, form and edition date, and folds the others under “More details”, which counts the ones filled in. The Information box of the admin follows the same order.
* Readings: a book may have several readings whose date is unknown, such as a book read several times as a child, and a reading added on its day keeps its time, which orders the readings of a day.
* REST API: the books give `cover`, `names`, `language_codes` and `form_name`, so that a list needs not embed their image and terms. The read dates are written through `POST /wp-json/library/v1/books/<id>` only.
* Fixed: a book not found could say so in English.

= 9.2.0 =
* Reading app: a book opens as a page, as a person does, rather than in a dialog: its URL is `?view=book&book=<id>`, a button goes back where it was opened from, on the page of a person too, and going back puts each view back where it was, its focus included. Saving the reading stays on the book. The form of a book stays a dialog over the page.

= 9.1.0 =
* Reading app: the card of a book read more than once says how many times, “Read 2 times”, an unknown date included.
* Reading app: a reading whose date is unknown shows in the box of a field, as the read dates do, and the read dates say that such a reading has none.

= 9.0.0 =
* Breaking: every reading of a book is a read date, an unknown one left empty and first: a book read without date needs an empty read date, and the empty read dates of unread books go. A book read long ago and read again then shows the new reading as a reread. In the reading app, a reading whose date is unknown is added with its own button, as a date field fills in today once touched on an iPhone; in the admin, the read dates are a list with a button to add a reading and one to remove each.
* Books: a book is in progress, for the first time or again, until a read date ends it. The reading app shows the books in progress first on its home, filters them, and says so on their card; the search keyword is `progress:yes`.
* Reading app: the dialog of a book shows the volumes of its series in reading order, read or not, which open, and links to their list.
* Reading app: the filters of the books are in a panel, which counts the books found, and the filters chosen show as pills that remove them; the number of books found is in the title of the list, and the books are ordered by date added, title, rating or latest reading.
* Reading app: the search keywords show under the search while it is used; an ISBN typed for a book to add shows the book that has it already, and a book to buy is bought from there; a reading saved says so.
* Reading app: back to the app after a while, its data load again; without network, it shows what it loaded, the books to buy included, as in a bookshop; its name leads back to its home, and back and forward take back the search of their URL.
* REST API: `POST /wp-json/library/v1/books/<id>` takes a `status`, and the books are ordered by `orderby=rating` or `orderby=read`.

= 8.5.1 =
* French translation: the roles are functions, as the credits of a comic print them, such as “Couleurs” or “D’après” rather than “Coloriste” or “Auteur adapté”, and the other names of people say both genders, as WordPress does, such as “Auteur/autrice”, without middle dot, which screen readers read badly.

= 8.5.0 =
* Books: a name has a new role, adapted author, as the BnF names the author of the work a book is adapted from or continues, such as Goscinny and Uderzo for an Asterix by Ferri and Conrad, or Richard Stark for the Parker of Darwyn Cooke. An adapted author is not one of the authors of the book: they are left out of its credits and of the books counted for them, and the book is on their page and in a row of the book open. The REST API saves them in the meta `library_adapted_authors`.

= 8.4.0 =
* Reading app: the card of a book read in its original language says so in a tag, “Original”, after the one of its form: a book whose original language is not the one of the app, and that no one translated, as the achievement In the original counts it.

= 8.3.1 =
* Fixed: on a phone, the names of the book form of the reading app were squeezed to one letter by the select of their role. A name keeps room for itself, and its role goes under it once the row is too narrow.
* Fixed: in French, the notes of a book are “Notes de lecture” in its form, rather than “Notes”, which read as its rating.

= 8.3.0 =
* Achievements: the forms have their own: 10, 25, 50, 100, 250 and 500 books read of a form, such as 100 novels; Jack of all trades, 4 forms read in a month; and Tour of the library, a book read of each form of the library, which shows the forms left to read once three or fewer are. Their subject leads to the books of the form, read or left to read.

= 8.2.0 =
* Reading app: the card of a book shows its form, such as Novel or Comic, in a tag after the line of its reading, before the one of a book not kept or to buy.

= 8.1.1 =
* Fixed: the dashboard was slow on MySQL 5.7: the ranking and the count of the authors in the Library widget left out the names with another role and the collective books with a subquery run once per name of a book, over 50 ms each. The search keyword `author:` leaves them out the same way.
* Fixed: the dashboard counted the books to read twice, for the Reading and the Reading pile widgets.

= 8.1.0 =
* Books: a book has a form, such as a novel, a comic or a magazine, among those added in **Books > Forms**, chosen in its Forms box, quick and bulk edit, and the book form of the reading app; the books list shows it in a column. The search finds a form by its name, and the keyword `form:` narrows it to one, such as `form:novel`.
* Reading app: the books are filtered by form too, and a book added while they are takes their form.
* REST API: the forms are at `/wp-json/wp/v2/forms`, and the routes `library/v1/books` and `books/<id>` take and give the `form` of a book, its term ID.

= 8.0.0 =
* Breaking: the taxonomy `library_author` is `library_person`, as each name of a book is a person, whatever their role. Its terms are at `/wp-json/wp/v2/people` and their URLs under `library-person/`, a person at `/wp-json/library/v1/people/<id>`, and the routes `library/v1/books`, `books/<id>` and `books/cover-name` take and give `people`, a list of `{name, role}`, instead of `authors`.
* Breaking: the translators of a book are people with the role of translator, saved in the multiple meta `library_translators` as term IDs, rather than a text of names.
* Books: each name of a book has a role, author unless another one is chosen: translator, colorist, inker, letterer or publishing director, in the Roles row of the Information box and in the book form of the reading app. The credits, the cover name, the achievements, the reading idea and the dashboard count the authors only; the page of a person lists their other contributions.
* Books: a collective work, a magazine or an anthology, is checked as such in the Information box and the book form. It is credited to its publishing directors, “X (dir.)”, else to no one, and counts among the books of none of its people in the dashboard and the achievements, while the search and the page of a person still find it.
* Books list: the Authors column shows the people credited, as the credits do.

= 7.0.0 =
* Breaking: the names follow the WordPress naming. The post type is `library_book`, the taxonomies `library_author`, `library_publisher` and `library_language`, and the book meta take the `library_` prefix, such as `library_isbn` and `library_read_date`, under `meta` in the REST API too. The terms are at `/wp-json/wp/v2/authors`, `publishers` and `languages`, and `POST /wp-json/library/v1/books` takes the cover as `featured_media`. The URLs of the books and of the terms stay the same.
* Code only: the methods say what they give, such as `Library_Books::get_books()`, `Library_Books::build_full_title()` or `Library_Stats::get_results()`: `get_` for a value, `build_` for what is assembled from values, `format_` for a value as text.

= 6.18.0 =
* Reading app: the image of a cover is named after its book, its first author and its title as shown, such as `charlie-adlard-walking-dead-vol-25-blood-for-blood.jpg`, rather than after the photo, such as `IMG_1234.jpeg`. The REST API gives this name through `GET /wp-json/library/v1/books/cover-name`.

= 6.17.1 =
* Fixed: a draft, pending or private book counted as a book in the library, in the stats, the dashboard, the achievements, the reading idea and the reading app. A book now counts only once in the library, not kept or to buy; until then, it stays in the books list of the admin.

= 6.17.0 =
* Reading app: a search for a series lists its books in reading order, a series at the date of its first book with its volumes in order, so that the parts of a cycle follow one another, rather than latest added first. The REST API does the same when no `orderby` is given.
* Reading idea: once a part of a cycle is read in full, the first volume of the next part, in reading order, is suggested as the next one of the cycle: “Next in Les Seigneurs des runes: you have read 2 volumes”.

= 6.16.0 =
* Books: a series is typed after its cycle, if any, such as “Star Wars, Young Jedi Knights”, as the help of the field now says; the volume number goes with the series.
* Achievements: a cycle is a series finished once the books of all its series are read, and each of its series still counts. The same books count once, for their series first: an author whose books are all in one cycle counts as that cycle, as Hergé counts as The Adventures of Tintin.
* Search: `series:"Star Wars"` finds the books of its series too, at any depth. A cycle under another one, such as `series:"Advanced Dungeons & Dragons, Manuel des Joueurs"`, found none of its books.
* Books: the number of a book in its collection, such as 5667 in Pocket Science-fiction, is typed in the book form and the reading app, found by the search and shown after its collection: “Pocket, Pocket Science-fiction no. 5667”.

= 6.15.1 =
* Fixed: the book form and quick edit of the admin saved a new or changed ISBN whose check digit is wrong, which the reading app refuses. Quick edit now refuses it with the reason; the book form saves the rest of the book, keeps the ISBN it had and says why.

= 6.15.0 =
* Books: a book without ISBN, such as an army book of Warhammer, takes the EAN-13 of its barcode in the ISBN field, saved as its 13 digits, such as 5011921988754, and its check digit checked as the one of an ISBN, itself an EAN-13 of the prefixes 978 and 979. The field took such a code from the prefix 501 only, with made-up hyphens.

= 6.14.0 =
* Books: an ISBN typed with or without hyphens is saved with them, where the International ISBN Agency places its parts, for every country and language: 9782070360246 becomes 978-2-07-036024-6, in the reading app, the book form, quick edit and the REST API; its slug follows. An ISBN of a range not known yet, or whose check digit is wrong, is kept as typed.
* Reading app: a new or changed ISBN whose last digit, its check digit, does not match the others, such as two digits swapped, is refused with the reason, before anything is saved. A book saved with such an ISBN before is still edited as long as its ISBN does not change.
* Fixed: the example under the ISBN field, 978-2-07-036024-5, was an ISBN-10 prefixed with 978 without its check digit computed again: it is 978-2-07-036024-6.

= 6.13.0 =
* Original languages: a new language can be typed by its name, in the language of the admin or in English, whatever its case and accents, such as “Norvégien”, “norvegien” or “Norwegian”: it is added by its ISO 639 code, `no`, found among the names of languages of PHP intl. A code already taken is refused rather than made unique, such as `it-2`.
* Fixed: a language refused in the box of a book was left out without a word; the box now says why.
* Fixed: in the admin, a rating typed with a decimal comma, such as 7,5, was read as 75 in Chrome: the book form refused it and quick edit deleted the rating. The rating is now chosen in a list, by half points, as in the reading app; the books list shows it with the decimal separator of the site.
* Reading app: the ISBN of the form of a book is typed on the full keyboard of a phone, which has the hyphen, rather than on the numeric one.
* Fixed: in the reading app, the pill that saves a form had lost its fill in 6.10.0; it stands out again as the pill of the current page, with `.app-pill--primary` from the base styles, revision 19d84b2.
* Code only: the styles of the reading app say each shared look once, such as the small text of the interface or the covers, rather than per component, and the fields nest their states. `Library_Meta::input()` becomes `Library_Meta::control()`, as it gives a select for the rating.

= 6.12.0 =
* Reading app: the books are filtered by status too, beside their reading state: everywhere, in the library, not kept or to buy. “To read” leaves out the books to buy only everywhere, a status chosen wins; the **+** adds a book with the status filtered.
* Reading app and dashboard: the stats of the year say when the books to read would all be read at this pace, such as “571 books to read, all read in 2031 at this pace”.
* REST API: `year_to_date` of `reading-stats` also returns `to_read`, the books to read, and `all_read_in`, the year they would all be read in at this pace.

= 6.11.1 =
* Code only: the components of the reading app move from `app/partials/components` to `app/components`, beside `app/partials`, which keeps the pieces of the page; the styleguide names every component it shows.

= 6.11.0 =
* Reading app: every field of one line, the search too, is as high as the controls and the pills beside it, 41.9 px rather than 53; the date field was higher still. Its text stays at 16 px.
* Reading app: the styleguide shows the control of the theme, in light and in dark.
* Fixed: a language typed by its name, such as “Norvégien”, got a slug that is no code, “norvegien”, and a book in that language would not open in the reading app. A new language is now refused unless its name or its slug is its ISO 639 code; fix the slug of such a language under Books > Languages.
* Code only: the components of the app set their defaults with `wp_parse_args()`.

= 6.10.0 =
* Reading app: the control that removes an author or a read date is a square, as the one that closes a dialog, rather than a pill stretched into an oval; the two lists of inputs look the same.
* Reading app: the buttons that are pills are as high as the other pills, and an error of a form sits at the gap of the form from its button.
* Reading app: the markup of the forms comes from small PHP components, which the styleguide renders as the app does; it also shows the controls of the toolbar, the current pill and the parts of a book open, of an author and of an idea.
* Reading app: base styles from revision 2cc86a9 of the gist: a control of the toolbar is a component of its own, `.app-control`, and no component carries a margin of its own.

= 6.9.0 =
* Reading app: the fields of the forms show their state. A required field says so in its label; sent, a field in error takes the color of an error and the message of the browser under it, and the first one takes the focus; an error of the server shows in the same color.
* Reading app: the ISBN and the ISSN say their format under their field, and the selects of the forms draw the same chevron as the ones of the pills.
* Reading app: every size is on the scale of the golden ratio. Small text is 12.6 px rather than 9.9, which was too small to read; the titles, the paddings and the corners take its half steps.
* Reading app: the title of a book without a cover ends in an ellipsis after four lines, its words broken with hyphens.
* Reading app: the styleguide shows the sizes of the text, the colors of state and the states of a field.

= 6.8.0 =
* Reading app: a styleguide, for development, opened by searching `styleguide`: the tokens of the app, then each of its components in a frame, with its own markup alone. In English, as code is.
* Reading app: a click anywhere on a pill that holds a select opens it, rather than only focusing it on its edges; the select draws its own chevron, the same in every browser.
* Reading app: on a phone, a card is padded one step less, to leave its width to the text.
* Fixed: every label of a field of the app points to it with `for`, as RGAA 11.1.1 asks: the reading filter, the read state, rating and status of a book, and its cover.
* Code only: a section title, the year and the header of an author take their spacing from what holds them, and the search field its look from itself. Nothing moves on screen.

= 6.7.4 =
* Fixed: on a narrow screen, the title of the app leaves room for the toolbar beside it. In French, “Bibliothèque” no longer runs under the add button at 320 pixels wide, nor when a reader widens the spacing of the text (WCAG 1.4.12, RGAA 10.12).
* Fixed: on a narrow screen, the button that removes an author stays inside the form of a book.

= 6.7.3 =
* Code only: each name says what it holds, the same way from the server to the reading app. Nothing changes on screen.

= 6.7.2 =
* The books list sorts by edition date or rating without temporary tables on disk on MySQL 5.7, and the books of the same date and title keep their order from one page to the next.

= 6.7.1 =
* The dashboard counts the readings of the year once, rather than once for the reading widget and once for the reading idea.
* The authors, publishers and collections with the most books, in the Library widget of the dashboard, show three times faster.

= 6.7.0 =
* Reading app: its icon, on the home screen of a phone and in the browser tab, is the book of the Books menu, grey on dark; in the tab, it follows the theme.
* The books list names the view of the published books “In the library”, as the “Library” menu does, rather than “Published”.
* French: the books not kept are “De passage” and the books to buy “Envie” (“Envies” in the app), rather than “Pas gardé” and “À acheter”.

= 6.6.3 =
* Fixed: removing the ISBN of a book removes it from its slug, which is then its title alone. The slug of a book without an ISBN or an ISSN now follows its title, as the slug of the others does.
* Fixed: quick edit builds the slug of a book from its ISSN and volume number, as its form does, rather than from its ISBN.

= 6.6.2 =
* Fixed: with no book to read, the reading idea says “No book to read.” rather than “Every book has been read.”, and the diary without readings says “No readings yet.”

= 6.6.1 =
* Reading app: the views scroll sideways in one row, as the latest readings, rather than wrapping onto a second line; the current one shows whole.
* The first choice of the “Library” menu, “Kept”, is now “In the library”.
* Reading app: in the book open, the lines above its reading and its fields are centered between what they separate.
* French: non-breaking spaces before colons, semicolons, question marks and percent signs, inside quotation marks, and between a number and its unit, such as “3 tomes” or “t. 3”.

= 6.6.0 =
* New “To buy” status for the books not in the library yet: hidden from the “All” view, the site and the library stats, and left out of the reading pile. A book bought is added the day it leaves the books to buy.
* Admin: the “Library” menu of the Publish box sets a book kept, not kept or to buy, instead of the “Not kept” checkbox; quick and bulk edit offer both statuses.
* Reading app: the **To buy** view lists the books to buy, and its **+** adds one there; the book open is kept, not kept or to buy, and the “To read” filter leaves out the books to buy.
* Reading idea: now and then a book to buy, the next volume of a series in progress or a book by an author rated well.
* Achievements: a book to buy counts as a book to read in a series, an author or a collection, which then lacks it, but not on the pile.
* REST API: `POST /wp-json/library/v1/books` takes a `status`.

= 6.5.0 =
* Reading app: a book has notes, its content: shown under its excerpt in the book open, written in its form under the excerpt.
* Admin: the edit screen of a book has the content editor, and stays the classic one.
* REST API: `POST /wp-json/library/v1/books` and `POST /wp-json/library/v1/books/<id>` take a `content`, which `GET /wp-json/library/v1/books/<id>` gives as typed.

= 6.4.0 =
* Reading app: **Edit book** edits a book in the app, in the form that adds one, opened over the book: its cover, title, excerpt, authors, publisher and collection, ISBN and more details. Only what changed is saved, and the book then shows its new values; a link leads to the admin for the rest.
* Reading app: a book added takes its excerpt too, and the cover of a book can be removed.
* REST API: `GET /wp-json/library/v1/books/<id>` gives a book as its form edits it, and `POST /wp-json/library/v1/books/<id>` edits it, only what is sent changing. `POST /wp-json/library/v1/books` takes an `excerpt`.

= 6.3.2 =
* Reading app: the summary on a card is half a step of the scale below its title, instead of the size of the lines in capitals around it, at which a sentence in lowercase reads as small print.

= 6.3.1 =
* Reading app: the summary on a card is a size below its title, in the type of its other lines, and shows more of itself in its three lines.

= 6.3.0 =
* Reading app: a book has a summary in a few words, its excerpt, written in the Excerpt box of the book (shown from the Screen Options when hidden): cut to three lines on its card, whole in the book open.
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
