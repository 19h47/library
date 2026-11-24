# Architecture

How the plugin is built. What it does is in `README.txt`, how to add to the reading app in `DESIGN-SYSTEM.md`, how it is tested in `TESTING.md`, how it is released in `RELEASING.md`.

## The plugin

`library.php` loads the classes of `includes`, `admin` and `app` through Composer, in the namespace `Library`; `Library\Plugin` lets each one register its hooks.

- **Data**: `Posts` registers the book post type, whose books are all published, the catalogue the readers share, `Taxonomies` the five taxonomies, `Meta` the book meta, those of the record the readers share. Every write, from the book form, quick edit or the REST API, goes through the meta registered there. An ISBN is the one of a single book of the catalogue: `Meta::check_isbn()` refuses one another book has, 409 with that book, which a reader puts on their shelves instead. A reading, `Reading`, its start, its end and the rating it ended with, is a row of JSON of the readings of a reader; whether a book is read, whether it is being read and its rating, the one of its latest reading rated, are worked out from their readings, never stored.
- **Roles**: `Roles` gives the books capabilities of their own, `library_book`, and the readers two roles: a reader, `library_reader`, shelves the books, adds the ones the catalogue lacks and changes their own; a librarian, `library_librarian`, changes every book and every name of the catalogue too; the editors and the administrators delete them besides. Shelving a book is `shelve_book`, with its ID; a visitor gets no reading of a book.
- **Shelves**: `Shelf` keeps where a book is for each reader, its `Status`, in the library, not kept or to buy, since when, and their copy, when it was printed, their readings, their notes and its photo, in meta of their own, the key followed by their user ID: `library_status_12`, `library_added_12`, `library_printed_date_12`, `library_reading_12`, `library_notes_12`, `library_cover_12`. The photo of their copy, an image they uploaded, shows instead of the cover of the edition, the featured image of the book; the book has no content, its notes being those of each reader. A book on none of their shelves has none of their rows, which taking it off deletes: a copy, a reading, a printed date, notes or a photo, puts it on one, among the books not kept unless they say otherwise, and a book read stays on one. The route `library/v1/books/<id>/shelf` writes them, which a reader may use on a book they cannot edit. `Query` lists the books of the shelves asked for, `shelf` in the REST API and `library_shelf` in the books list, by the date the reader added them.
- **History**: `History` keeps the record of a book the readers share in its revisions, each by the reader who saved it, the first as the book is added: its title and excerpt, its meta, which `Meta` registers with their revisions, and its names, whose term IDs each revision keeps in `library_terms`, which restoring it gives back. The comparison of two revisions shows the meta and the names. The route of a book fires the hooks after a post is written once its meta and terms are, so that its revision holds them; the shelves are never revisioned.
- **Books**: `Books::get_books()` reads every book of the catalogue once, as `Book` objects with their meta, their terms and their shelf for the reader, and `Books::get_shelved()` those on their shelves, and `Cache` keeps them in transients until a book changes, for each reader. The stats (`Stats`), the recap (`Recap`), the achievements (`Achievements`), the reading idea (`Reading_Idea`) and the REST routes read from it, never from the database again.
- **Search**: `Query` extends the book search to the meta and the terms, and reads its keywords, such as `series:SAS`.
- **REST API**: `Rest_Api` adds the fields of the books and the terms to the core REST API, and the routes of the plugin under `library/v1`, whose responses `Rest_Schemas` describes, as JSON schemas the index gives asked for help; a test checks each route gives what its schema says. `Rest_Locale` answers a request in the language its `Accept-Language` header prefers, among those of the plugin.
- **BnF**: `BnF` finds a book by its ISBN in the catalogue of the BnF, through its SRU API, and gives its values with the names the library already has.
- **ISBN**: `Isbn` places the hyphens of an ISBN from `includes/isbn-ranges.json`.
- **Updates**: `Updater` offers the latest GitHub release as an update, and `Readme` turns `README.txt` into the sections of the "View details" modal. It reads the sections Description, Installation, Frequently Asked Questions and Changelog, `= headings =`, paragraphs, lists, `**bold**`, `` `code` `` and links: nothing else.
- **Admin**: `admin` holds the books list, the terms lists, the metaboxes, the dashboard widgets and the achievements page.

## The reading app

The reading app is an admin page, `app/class-page.php`, rendered full screen instead of the admin interface. It reads the REST API, through `wp.apiFetch`.

Its login is the one of WordPress, which leads back to it: `app/class-login.php` then dresses it as the app, with its name, its icon, its theme and `app/css/library-login.css` instead of the styles of the login of WordPress, on the markup of core. Its links lead to the app too, so that the lost password and the screen that says to check the email are dressed in their turn; the screen of the link in the email, which the email gives without the app, stays the one of WordPress, as any other login. Logging out clears what the app kept on the device, with the header `Clear-Site-Data`: its service worker, whose caches are named after the reader, and its choices.

### Scripts

Its scripts are script modules, without a build:

- `app/js/lib`, the modules the others import, registered as `library-app/<name>`: the router, the store, the parts of its state, empty, `library-app/parts`, the API, the readings between the API and the forms, `library-app/readings`, the reference of the API, `library-app/reference`, the rendering, the lines of a book, `library-app/book-lines`, the base of the bricks and the one of the components, and what both share without importing the app: `library-app/format`, the formats of the language of the page, a function each, `library-app/dom`, which writes in the page, and `library-app/rows`, the rows of a list of inputs;
- `app/js/components`, the custom elements of the components that behave on their own, the root of their markup, each enqueued as `library-app/components/<name>`: they read no store, and their places set their API, attributes and properties, which `types/components.d.ts` gives by their tags;
- `app/js/bricks`, the custom elements of the app, which read the store, each enqueued as `library-app/<name>`;
- `app/js/library-app-theme.js`, a classic script that sets the theme before the first paint;
- `app/js/library-app-service-worker.js`, served from the URL of the app so that it controls its pages, which keeps the app and what it loaded for a visit without network.

The router follows the links on the Navigation API: the URL says what shows. The app needs it: Safari 26.2, Firefox 147 or Chrome 135 at least, for `NavigateEvent.sourceElement`. Each view shows in a view transition: the cover of a book opened goes from its card to its page and back, and fades only without motion.

The store holds the state the bricks read with `select()` and change through its actions; `library-app/api` fills it from the REST API.

### Styles

`app/css/app-style.css` is a copy of the [base shared by the author's small apps](https://gist.github.com/19h47/27df983fac74beb8d3e0a30688654498), with its revision in its header, in the cascade layer `app-base`, its utilities in the layer `app-utilities`, last. The styles of the app come in the layer `app` between them:

- `app/css/library-app.css` declares the order of its layers, `app.base`, `app.components` and `app.views`, and holds the tokens;
- `app/css/components`, a sheet per component;
- `app/css/views`, a sheet per view: what a view sets wins over a component, whatever their specificity;
- the utilities of the base, one property each (`.app-text-s`, `.app-text-muted`, `.app-font-ui`, `.app-uppercase`…), win over both: an element whose component sets the same property does not take one.

Every size is on the scale of the golden ratio: its steps, `--app-u0` to `--app-u3`, for sizes, space as `--app-space-xs` to `--app-space-xl`, text as `--app-text-s` to `--app-text-3xl`, half steps apart, corners as `--app-radius-xs` to `--app-radius-m`; `DESIGN-SYSTEM.md` says which takes what, and `pnpm lint:css` fails on a length off the scale.

The components are built as those of a design file, each level made of the ones before it:

- atoms: the styles of text (`.library-heading`, `.library-prose`, a meta line of utilities), `.app-pill`, `.app-control`, `.library-link`, `.library-tag`, `book-cover`, `.app-status`, `.library-input`, the control of a field, `offline-status`;
- molecules: `.library-title`, `pill-checkbox`, `pill-select`, `covers-toggle`, `back`, `remove-control`, `reading-row`, `.library-pills`, `.library-strip`, `cover-link`, a cover that opens its book, current a book being read, `.library-card-link`, `.library-fields`, `.library-notice`, `field`, `meta-field`, `image-field`, `search-form` (in an `.app-search`), `recap-link`, `catalogue-status`, `reading-period`, `partial-date`, `barcode-scan`, `.library-disclosure`;
- organisms: the cards, components of their own, `book-card`, `book-row`, `term-card` and `achievement-card`, each its root the class of its name, `.library-book-card`; `.library-section`, `cards-group`, which `fillCardsGroup()` fills, `term-header`, `views-nav`, `.app-controls`, `.library-sheet` with `dialog-close`, `filters-sheet`, the filters of the books and of the diary, `checkboxes-field`, its choices in a sheet or, inline, all shown, `fields-disclosure`, fields folded that its summary counts, `inputs-list`; the parts of the page of a book, `book-header` and `book-fields`, which `fillBookPage()` fills;
- screens: the views, in `app/css/views`.

The components that behave on their own have a custom element of their own as their root, in `app/js/components`: `image-field`, `reading-period`, `partial-date`, `barcode-scan`, `checkboxes-field`, its choices in a sheet or, inline, all shown, `fields-disclosure`, fields folded that its summary counts, `inputs-list`, `input-groups` and `offline-status`; the two lists of inputs share their rows through `library-app/rows`.

A component knows none of the places it shows in: a place that needs it otherwise sets its properties, as a card sets the width of its cover, `--library-cover-inline-size`, rather than the cover listing the cards. A class of a view names a part of that view; a style used in several places is a component of its own.

### Markup

The markup comes from the templates of `app/partials/library-app.php`, which the bricks fill, and from small PHP components, in `app/components`, printed with `Library\Markup::render_component()`: a component is markup alone, the brick that ties it to the store around it in the app, or its own element as its root when it behaves on its own, and a template of an item of a list holds one, `<li>` then a card. A component carries neither a margin nor a width of its own: its place spaces it and sizes it. The scripts reach the markup by data attributes, `data-field`, `data-list`, `data-action`, `data-part`…, or by what an element is, never by a class of its styles; ESLint fails on a script that does.

Searching `styleguide` then Enter in the app opens its styleguide, for development, in English: the foundations, then the atoms, the molecules, the organisms and the screens, each component under its name, in a frame with the markup the app renders, the PHP components and the templates of the app filled as the bricks fill them, its caption saying what it is made of, its variants and its properties.

Searching `api` then Enter opens the reference of the API, for development: every route of `library/v1`, then the routes of `wp/v2` the app reads, as the REST API describes itself (`library/v1?context=help`, `OPTIONS` on a route of core), with their arguments, what they give when their schema says it, and a GET request to try. It shows what the code registers: a description or a schema added to a route shows there.
