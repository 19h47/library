# Releasing

WordPress updates the plugin from the latest [GitHub release](https://github.com/19h47/library/releases): the `Update URI` header of `library.php` routes the update check to GitHub, and the "View details" modal shows `README.txt` as it is in the release. The package it installs is `library.zip`, which the Package workflow builds and attaches to each release once it is published: the files of the repository but those listed `export-ignore` in `.gitattributes`, with the dependencies of Composer. A release is offered once its package is there, a minute or so after it is published, except to a plugin folder that is a Git working copy: see the Updates section of `README.txt`.

## Steps

1. Branch `release/x.y.z` from an up-to-date `main`.
2. Commit the changes, with a [gitmoji](https://gitmoji.dev).
3. Update the translations, with gettext and the dependencies of Composer, `composer install`, which hold the WP-CLI the files are written with:

   ```sh
   sh bin/update-translations.sh
   ```

   It writes `languages/library.pot` from the code, merges it into `languages/library-fr_FR.po`, then builds the files WordPress loads: the `.mo`, the `.l10n.php` and one JSON file per script with strings. It fails while a string is untranslated or fuzzy: translate it in `languages/library-fr_FR.po`, run it again, and commit every file of `languages/` it changed.
4. Update the ranges of the ISBN, from the range message of the International ISBN Agency, and commit `includes/isbn-ranges.json` if it changed:

   ```sh
   php bin/update-isbn-ranges.php
   ```

   The agency adds ranges as it gives prefixes to new publishers: the hyphens of an ISBN of a range missing from the file are not placed. It dates its message as it sends it: the script writes the file only when the ranges change.
5. Bump the version in a `:bookmark: x.y.z` commit:
   - `Version` in `library.php`;
   - `Stable tag` in `README.txt`, with an entry in its changelog: one line per change, without prefix for a feature, `* Fixed: …` for a fix, `* Changed: …` for a change, `* Breaking: …` for a change that breaks the REST API or the data, with its migration, and `* Code only: …` for a change the reader does not see;
   - the translations, updated again by the script of step 3, which puts the version in `languages/library.pot`.
6. Open a pull request titled `x.y.z`, then merge it once its checks pass: `main` takes a change through a pull request only, after the coding standards (`phpcs`), PHPStan (`phpstan`), the scripts (`scripts`), the tests (`phpunit (8.4)`, `phpunit (8.5)`, `playwright`) and the translations (`update-translations`):

   ```sh
   gh pr checks --watch
   ```
7. Publish the release from `main`, with the changelog entry as notes, then check that the Package workflow attached `library.zip` to it:

   ```sh
   gh release create vx.y.z --target main --title x.y.z --notes "…"
   gh run watch "$( gh run list --workflow package.yml --limit 1 --json databaseId --jq '.[0].databaseId' )"
   gh release view vx.y.z --json assets --jq '.assets[].name'
   ```

## Versions

- Patch, `x.y.Z`: a fix, or a change the reader does not see.
- Minor, `x.Y.0`: a new feature.
- Major, `X.0.0`: a breaking change to the REST API, the meta or the data. Migrate the data of each site before updating it, as its `* Breaking: …` line says, in SQL with the table prefix `wp_`.

While the plugin runs on a single site, a breaking change stays a minor version: its `* Breaking: …` line still gives the migration.
