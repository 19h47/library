# Releasing

WordPress updates the plugin from the latest [GitHub release](https://github.com/19h47/library/releases): the `Update URI` header of `library.php` routes the update check to GitHub, and the "View details" modal shows `README.txt` as it is in the release. The files listed `export-ignore` in `.gitattributes` are left out of the archive.

No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder.

## Steps

1. Branch `release/x.y.z` from an up-to-date `main`.
2. Commit the changes, with a [gitmoji](https://gitmoji.dev).
3. Update the translations, with WP-CLI and gettext:

   ```sh
   sh bin/update-translations.sh
   ```

   It writes `languages/library.pot` from the code, merges it into `languages/library-fr_FR.po`, then builds the files WordPress loads: the `.mo`, the `.l10n.php` and one JSON file per script with strings. It fails while a string is untranslated or fuzzy: translate it in `languages/library-fr_FR.po`, run it again, and commit every file of `languages/` it changed.
4. Update the ranges of the ISBN, from the range message of the International ISBN Agency, and commit `includes/isbn-ranges.json` if it changed:

   ```sh
   php bin/update-isbn-ranges.php
   ```

   The agency adds ranges as it gives prefixes to new publishers: the hyphens of an ISBN of a range missing from the file are not placed.
5. Bump the version in a `:bookmark: x.y.z` commit:
   - `Version` in `library.php`;
   - `Stable tag` in `README.txt`, with an entry in its changelog: one line per change, `* Fixed: …` for a fix, `* Changed: …` or `* Breaking: …` for a change that breaks the REST API or the data;
   - the translations, updated again by the script of step 3, which puts the version in `languages/library.pot`.
6. Open a pull request titled `x.y.z`, wait for its check of the coding standards, then merge it:

   ```sh
   gh pr checks --watch
   ```
7. Publish the release from `main`, with the changelog entry as notes:

   ```sh
   gh release create vx.y.z --target main --title x.y.z --notes "…"
   ```

## Versions

- Patch, `x.y.Z`: a fix, or a change the reader does not see.
- Minor, `x.Y.0`: a new feature.
- Major, `X.0.0`: a breaking change to the REST API, the meta or the data. Migrate the data of each site before updating it: the changelog names the change, not the migration.
