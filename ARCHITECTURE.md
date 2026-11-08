# Architecture

How the plugin is built. What it does is in `README.txt`, how it is tested in `TESTING.md`, how it is released in `RELEASING.md`.

## The plugin

`library.php` loads the classes of `includes`, `admin` and `app` through Composer, in the namespace `Library`; `Library\Plugin` lets each one register its hooks.

- **Data**: `Posts` registers the book post type and its statuses, `Taxonomies` the five taxonomies, `Meta` the book meta. Every write, from the book form, quick edit or the REST API, goes through the meta registered there.
- **Books**: `Books::get_books()` reads every book once, as `Book` objects with their meta and terms, and `Cache` keeps them in transients until a book changes. The stats (`Stats`), the recap (`Recap`), the achievements (`Achievements`), the reading idea (`Reading_Idea`) and the REST routes read from it, never from the database again.
- **Search**: `Query` extends the book search to the meta and the terms, and reads its keywords, such as `series:SAS`.
- **REST API**: `Rest_Api` adds the fields of the books and the terms to the core REST API, and the routes of the plugin under `library/v1`.
- **BnF**: `BnF` finds a book by its ISBN in the catalogue of the BnF, through its SRU API, and gives its values with the names the library already has.
- **ISBN**: `Isbn` places the hyphens of an ISBN from `includes/isbn-ranges.json`.
- **Updates**: `Updater` offers the latest GitHub release as an update, and `Readme` turns `README.txt` into the sections of the "View details" modal. It reads the sections Description, Installation, Frequently Asked Questions and Changelog, `= headings =`, paragraphs, lists, `**bold**`, `` `code` `` and links: nothing else.
- **Admin**: `admin` holds the books list, the terms lists, the metaboxes, the dashboard widgets and the achievements page.

## The reading app

The reading app is an admin page, `app/class-page.php`, rendered full screen instead of the admin interface. It reads the REST API, through `wp.apiFetch`.

### Scripts

Its scripts are script modules, without a build:

- `app/js/lib`, the modules the others import, registered as `library-app/<name>`: the router, the store, the API, the formats, the rendering and the base of the bricks;
- `app/js/bricks`, one custom element per file, each enqueued as `library-app/<name>`;
- `app/js/library-app-theme.js`, a classic script that sets the theme before the first paint;
- `app/js/library-app-service-worker.js`, served from the URL of the app so that it controls its pages, which keeps the app and what it loaded for a visit without network.

The router follows the links on the Navigation API: the URL says what shows. The app needs it: Safari 26.2, Firefox 147 or Chrome 135 at least, for `NavigateEvent.sourceElement`. Each view shows in a view transition: the cover of a book opened goes from its card to its page and back, and fades only without motion.

The store holds the state the bricks read with `select()` and change through its actions; `library-app/api` fills it from the REST API.

### Styles

`app/css/app-style.css` is a copy of the [base shared by the author's small apps](https://gist.github.com/19h47/27df983fac74beb8d3e0a30688654498), with its revision in its header, in the cascade layer `app-base`. The styles of the app come in the layers after it:

- `app/css/library-app.css` declares the order of the layers, `library.base`, `library.components` and `library.views`, and holds the tokens;
- `app/css/components`, a sheet per component;
- `app/css/views`, a sheet per view: what a view sets wins over a component, whatever their specificity.

Every size is on the scale of the golden ratio: its steps for space, half steps for text.

### Markup

The markup comes from the templates of `app/partials/library-app.php`, which the bricks fill, and from small PHP components, in `app/components`, printed with `Library\Markup::render_component()`. A component carries no margin of its own: its place spaces it.

Searching `styleguide` in the app opens its styleguide, for development: the tokens, then each component with its markup alone, in English.
