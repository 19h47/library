# Testing

The tests run on the test suite of WordPress: the core installed by Composer, the plugin loaded as a must-use plugin, and books added through the route of the book form, `POST library/v1/books`, then read as the plugin reads them: the achievements, the reading idea, the readings, the recap, the dashboard, the statuses, the names of the terms and their lists. The catalogue of the BnF answers with the records of `tests/fixtures/bnf`, by their ISBN, without the network.

## Database

The suite empties its database on each run: give it one of its own, never the database of a site.

```sh
mysql -uroot -proot -e 'CREATE DATABASE IF NOT EXISTS library_tests'
```

`tests/wp-tests-config.php` reads it from the environment, with these defaults:

| Variable | Default |
| --- | --- |
| `WP_TESTS_DB_NAME` | `library_tests` |
| `WP_TESTS_DB_USER` | `root` |
| `WP_TESTS_DB_PASSWORD` | `root` |
| `WP_TESTS_DB_HOST` | `127.0.0.1` |

With Local, the host is the socket of the site's MySQL, under `~/Library/Application Support/Local/run/<id>/mysql/mysqld.sock`:

```sh
export WP_TESTS_DB_HOST="localhost:$HOME/Library/Application Support/Local/run/<id>/mysql/mysqld.sock"
```

## Running

```sh
composer install
composer test
```

`composer test -- --filter Test_Reading_Idea` runs one class. A pull request runs them on MySQL 8.0, with PHP 8.4 and 8.5.

## Writing a test

A test class, in the namespace `Library\Tests`, extends `TestCase` (`tests/class-testcase.php`), in a file `tests/class-test-*.php`; Composer loads them, as it loads the classes of the plugin. Each test starts with no book, adds the ones it needs with `add_book()`, `add_read()` or `add_reads()`, the terms with `get_language()` or `get_form()`, then reads what it checks, such as the achievements with `get_earned()` and `get_close()`.

## Static analysis

[PHPStan](https://phpstan.org), with the stubs and the rules of WordPress, `szepeviktor/phpstan-wordpress`, reads the types of the code without running it:

```sh
composer analyse
```

Its level, in `phpstan.neon`, rises as the code takes its types. What the plugin knows of WordPress and its stubs cannot, such as its taxonomies being registered, is in `types/wordpress.stub`. A pull request runs it with the coding standards.

## The reading app

The tests of the reading app run in a browser, with [Playwright](https://playwright.dev) and the utilities of WordPress, `@wordpress/e2e-test-utils-playwright`, on a WordPress of their own: each test adds the books it needs through the route of the book form, and the site is emptied of its books first. A site with 100 books or more is never touched.

```sh
pnpm install
pnpm exec playwright install chromium webkit
pnpm wp-env start
pnpm test:e2e
```

`wp-env` needs Docker: it serves the latest WordPress with the plugin at `http://localhost:8889`, logged in as `admin` / `password`. Another WordPress of the tests, without Docker, is given by its URL and its login:

```sh
WP_BASE_URL=http://library-tests.local WP_USERNAME=admin WP_PASSWORD=password pnpm test:e2e
```

They run on a phone in Chrome, and the covers in the engine of Safari too, which lays them out its own way: `--project=safari` runs these alone. `pnpm test:e2e -- reading` runs one file of `tests/e2e`, and `--ui` shows the tests as they run. A pull request runs them on `wp-env`, and keeps the report and the traces of a test that failed.

## The styles of the components

`tests/e2e/styles.spec.js` reads the computed styles of each element of the styleguide, filled with its own samples, on a phone and on a wide screen, in both themes, and compares them with `tests/e2e/styles/styleguide.txt`: a change of the stylesheets that changes no component passes, one that does fails, saying which elements changed, and how. What changes on purpose is written again, and the diff of the file shows it in the pull request:

```sh
UPDATE_STYLES=1 pnpm test:e2e styles
```

The file holds each set of styles once, under a hash of it, then each element with the hash of its styles: a token changed changes a line of the styles. Only what the styles set is compared, its numbers to two decimals, not what the layout makes of it, such as a width, which depends on the fonts of the system.

## The scripts and the stylesheets

ESLint checks the scripts with the rules of WordPress, `@wordpress/eslint-plugin`, and formats them with `wp-prettier`; it fails as well on a script that reaches an element by a class of its styles, rather than by a data attribute or by what it is (see `DESIGN-SYSTEM.md`). TypeScript reads their types from their JSDoc, without building anything, with the types of the packages of WordPress they use and of `types/globals.d.ts`. `bin/check-css.mjs` fails on a stylesheet of the app off the rules of `DESIGN-SYSTEM.md`: a length off the scale, a color written as it is rather than a token, a margin or a width a component gives itself:

```sh
pnpm lint:js
pnpm lint:css
pnpm check:js
```

A pull request runs the three.
