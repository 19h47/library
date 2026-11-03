#!/bin/sh
#
# Update the translations, from the strings of the code: languages/library.pot, then the French files WordPress loads,
# the .mo, the .l10n.php and one JSON per script with strings.
#
# Run from anywhere, with WP-CLI and gettext: sh bin/update-translations.sh. It fails while a string is untranslated
# or fuzzy: translate it in languages/library-fr_FR.po, then run it again.
#
# The .pot is written again only when more than its date changed, so that a run on code without new strings leaves
# every file as it was. The JSON files are built again from none, so that none is left for a script that was renamed
# or lost its strings.

set -eu

cd "$(dirname "$0")/../languages"

pot=$(mktemp)
trap 'rm -f "$pot"' EXIT

wp i18n make-pot .. "$pot" --exclude=vendor --quiet

if ! diff -q -I '^"POT-Creation-Date: ' "$pot" library.pot > /dev/null; then
	cp "$pot" library.pot
fi

msgmerge -q --no-fuzzy-matching --previous --backup=off -U library-fr_FR.po library.pot
msgfmt -c -o library-fr_FR.mo library-fr_FR.po
wp i18n make-php library-fr_FR.po . --quiet

rm -f library-fr_FR-*.json
wp i18n make-json library-fr_FR.po . --no-purge --quiet

statistics=$(msgfmt --statistics -o /dev/null library-fr_FR.po 2>&1)

case $statistics in
	*fuzzy* | *untranslated*)
		msgattrib --untranslated --no-obsolete library-fr_FR.po >&2
		msgattrib --only-fuzzy --no-obsolete library-fr_FR.po >&2
		echo "$statistics Translate the strings above in languages/library-fr_FR.po." >&2
		exit 1
		;;
esac
