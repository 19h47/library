# Releasing

WordPress updates the plugin from the latest [GitHub release](https://github.com/19h47/library/releases): the `Update URI` header of `library.php` routes the update check to GitHub, and the "View details" modal shows `README.txt` as it is in the release. The files listed `export-ignore` in `.gitattributes` are left out of the archive.

No update is offered when the plugin folder is a Git working copy, since updating replaces the whole folder.

## Steps

1. Branch `release/x.y.z` from an up-to-date `main`.
2. Commit the changes, with a [gitmoji](https://gitmoji.dev).
3. Update the translations, if strings changed:

   ```sh
   wp i18n make-pot . languages/library.pot --exclude=vendor
   msgmerge -q --no-fuzzy-matching --previous -U languages/library-fr_FR.po languages/library.pot
   ```

   Translate the new strings in `languages/library-fr_FR.po`, then build the files WordPress loads:

   ```sh
   msgfmt -c -o languages/library-fr_FR.mo languages/library-fr_FR.po
   wp i18n make-php languages/library-fr_FR.po languages
   wp i18n make-json languages/library-fr_FR.po languages --no-purge
   ```

   `make-json` writes one JSON file per script with strings: commit them too.
4. Update the ranges of the ISBN, from the range message of the International ISBN Agency, and commit `includes/isbn-ranges.json` if it changed:

   ```sh
   php bin/update-isbn-ranges.php
   ```

   The agency adds ranges as it gives prefixes to new publishers: the hyphens of an ISBN of a range missing from the file are not placed.
5. Bump the version in a `:bookmark: x.y.z` commit:
   - `Version` in `library.php`;
   - `Stable tag` in `README.txt`, with an entry in its changelog: one line per change, `* Fixed: …` for a fix, `* Changed: …` or `* Breaking: …` for a change that breaks the REST API or the data;
   - `languages/library.pot`, regenerated as above.
6. Open a pull request titled `x.y.z`, then merge it.
7. Publish the release from `main`, with the changelog entry as notes:

   ```sh
   gh release create vx.y.z --target main --title x.y.z --notes "…"
   ```

## Versions

- Patch, `x.y.Z`: a fix, or a change the reader does not see.
- Minor, `x.Y.0`: a new feature.
- Major, `X.0.0`: a breaking change to the REST API, the meta or the data. Migrate the data of each site before updating it: the changelog names the change, not the migration.
