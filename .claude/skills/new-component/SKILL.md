---
name: new-component
description: Add a component, a view or a style to the reading app of the library plugin, following its design system. Use when asked to create, add or change a component, a card, a field, a sheet, a view or a token of the reading app (app/), or to restyle one.
---

# New component

The rules, the tokens, the names and the starting points are in `DESIGN-SYSTEM.md`, at the root of the plugin: read it first, whole, and follow it. This skill gives the order of the work; it repeats none of the rules.

## Before writing

1. Read `DESIGN-SYSTEM.md`, then the part of `ARCHITECTURE.md` on the reading app.
2. Find the nearest component in the styleguide (`app/partials/library-styleguide.php`) and in `app/components` / `app/css/components`: a new one only if none shows this content. The same content in another state is a variant, an argument of the existing component; another content is another file.
3. Place it: its level (atom, molecule, organism, screen) and its files, `app/components/<name>.php`, `app/css/components/<name>.css`, its element `app/js/components/<name>.js` if it has a behavior of its own, which reads no store, or a brick `app/js/bricks/library-<name>.js` around it if the store fills it. A part of one view only is a class of that view, in `app/css/views/<view>.css`.
4. If a token is missing, say so before adding one: a token of the scale belongs to the gist, shared by every app, not to the library alone.

## Writing

5. Start from the skeletons of `DESIGN-SYSTEM.md`: component, sheet, component element or brick.
6. Sizes from tokens only: space `--app-space-*`, text `--app-text-*`, corners `--app-radius-*`, the steps `--app-u*` for a size; no margin and no width of its own; what its place may change, a custom property it reads.
7. Data attributes for every element a script reaches, `data-field`, `data-list`, `data-action`, `data-part`…; never a class.
8. Its strings in `__()`, in PHP and in JS.

## Finishing

9. A frame in the styleguide, at its level, with its variants and a caption: what it is, its component, what it is made of, its variants, the properties its place sets.
10. Translations: translate the new strings in `languages/library-fr_FR.po`, with the spaces of French typography, then `sh bin/update-translations.sh`.
11. A behavior gets a test in `tests/e2e`. Write the styles of the styleguide again, `UPDATE_STYLES=1 pnpm test:e2e styles`, and read the diff of `tests/e2e/styles/styleguide.txt`: only the components meant to change.
12. Checks, all of them: `pnpm lint:js`, `pnpm lint:css`, `pnpm check:js`, `vendor/bin/phpcs`, `composer analyse`; the tests as `TESTING.md` says.
13. Look at it: the styleguide and the view it lives in, on a phone width and on a wide screen, in both themes.
14. Add it to its level in `ARCHITECTURE.md`.

Say what was done, and anything left undone, with the output of the checks.
