# Architecture

How the plugin is built. What it does is in `README.txt`, how to add to the reading app in `DESIGN-SYSTEM.md`, how it is tested in `TESTING.md`, how it is released in `RELEASING.md`.

## The plugin

`library.php` loads the classes of `includes`, `admin` and `app` through Composer, in the namespace `Library`; `Library\Plugin` lets each one register its hooks.

- **Data**: `Posts` registers the book post type and its statuses, `Taxonomies` the five taxonomies, `Meta` the book meta. Every write, from the book form, quick edit or the REST API, goes through the meta registered there.
- **Books**: `Books::get_books()` reads every book once, as `Book` objects with their meta and terms, and `Cache` keeps them in transients until a book changes. The stats (`Stats`), the recap (`Recap`), the achievements (`Achievements`), the reading idea (`Reading_Idea`) and the REST routes read from it, never from the database again.
- **Search**: `Query` extends the book search to the meta and the terms, and reads its keywords, such as `series:SAS`.
- **REST API**: `Rest_Api` adds the fields of the books and the terms to the core REST API, and the routes of the plugin under `library/v1`, whose responses `Rest_Schemas` describes, as JSON schemas the index gives asked for help; a test checks each route gives what its schema says. `Rest_Locale` answers a request in the language its `Accept-Language` header prefers, among those of the plugin.
- **BnF**: `BnF` finds a book by its ISBN in the catalogue of the BnF, through its SRU API, and gives its values with the names the library already has.
- **ISBN**: `Isbn` places the hyphens of an ISBN from `includes/isbn-ranges.json`.
- **Updates**: `Updater` offers the latest GitHub release as an update, and `Readme` turns `README.txt` into the sections of the "View details" modal. It reads the sections Description, Installation, Frequently Asked Questions and Changelog, `= headings =`, paragraphs, lists, `**bold**`, `` `code` `` and links: nothing else.
- **Admin**: `admin` holds the books list, the terms lists, the metaboxes, the dashboard widgets and the achievements page.

## The reading app

The reading app is an admin page, `app/class-page.php`, rendered full screen instead of the admin interface. It reads the REST API, through `wp.apiFetch`.

Its login is the one of WordPress, which leads back to it: `app/class-login.php` then dresses it as the app, with its name, its icon, its theme and `app/css/library-login.css` instead of the styles of the login of WordPress, on the markup of core. Its links lead to the app too, so that the lost password and the screen that says to check the email are dressed in their turn; the screen of the link in the email, which the email gives without the app, stays the one of WordPress, as any other login.

### Scripts

Its scripts are script modules, without a build:

- `app/js/lib`, the modules the others import, registered as `library-app/<name>`: the router, the store, the API, the formats, the rendering and the base of the bricks;
- `app/js/bricks`, one custom element per file, each enqueued as `library-app/<name>`;
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

- atoms: the styles of text (`.library-heading`, `.library-prose`, a meta line of utilities), `.app-pill`, `.app-control`, `.library-link`, `.library-tag`, `.library-cover`, `.app-status`;
- molecules: `.library-title`, `pill-checkbox`, `pill-select`, `back`, `.library-pills`, `.library-strip`, `.library-cover-link`, `.library-fields`, `.library-notice`, `field`, `image-field`;
- organisms: the cards, components of their own, `book-card` (`.library-item`), `book-row` (`.library-row`), `term-card` (`.library-term`) and `achievement-card` (`.library-achievement`), and `.library-current`; `.library-section`, `term-header`, `.library-nav`, `.app-controls`, `.library-sheet` with `dialog-close`, `checkboxes-field`, `input-list`;
- screens: the views, in `app/css/views`.

A component knows none of the places it shows in: a place that needs it otherwise sets its properties, as a card sets the width of its cover, `--library-cover-inline-size`, rather than the cover listing the cards. A class of a view names a part of that view; a style used in several places is a component of its own.

### Markup

The markup comes from the templates of `app/partials/library-app.php`, which the bricks fill, and from small PHP components, in `app/components`, printed with `Library\Markup::render_component()`: a template of an item of a list holds a component, `<li>` then a card. A component carries neither a margin nor a width of its own: its place spaces it and sizes it. The scripts reach the markup by data attributes, `data-field`, `data-list`, `data-action`, `data-part`…, or by what an element is, never by a class of its styles; ESLint fails on a script that does.

Searching `styleguide` then Enter in the app opens its styleguide, for development, in English: the foundations, then the atoms, the molecules, the organisms and the screens, its contents first, a link to each level and to each of its samples, then the samples in columns at least as wide as the column of the app, as many as the screen holds, each component in a frame, under its name with the markup the app renders, the PHP components and the templates of the app filled as the bricks fill them, its caption saying what it is made of, its variants and its properties.

Searching `api` then Enter opens the reference of the API, for development: every route of `library/v1`, then the routes of `wp/v2` the app reads, as the REST API describes itself (`library/v1?context=help`, `OPTIONS` on a route of core), with their arguments, what they give when their schema says it, and a GET request to try. It shows what the code registers: a description or a schema added to a route shows there.
