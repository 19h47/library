# Testing

The tests run on the test suite of WordPress: the core installed by Composer, the plugin loaded as a must-use plugin, and books added through the route of the book form, `POST library/v1/books`, then read through `Library_Books`, as the achievements and the reading idea read them.

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

`composer test -- --filter Test_Library_Reading_Idea` runs one class. A pull request runs them on MySQL 8.0, with PHP 8.4 and 8.5.

## Writing a test

A test class extends `Library_TestCase` (`tests/class-library-testcase.php`), in a file `tests/class-test-*.php`. Each test is a request of its own: it starts with no book, adds the ones it needs with `add_book()` or `add_reads()`, then reads the achievements with `get_earned()` and `get_close()`, or the reading idea.
