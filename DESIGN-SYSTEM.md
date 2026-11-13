# Design system

How to add to the reading app: a component, a view, a style. How the app is built is in `ARCHITECTURE.md`; what each component looks like is in the styleguide, searching `styleguide` then Enter in the app, each component in a frame with the markup the app renders and a caption saying what it is made of. This document says which token, which name, which file and which rule, so that a component can be made without a question.

## Foundations

Every size is on a scale of the golden ratio, φ: steps for space, half steps (√φ) for text, a step and a half between corners. The steps, `--app-u0` (≈ 9.9 px) to `--app-u3` (≈ 41.9 px), around `--app-u1`, 1rem, are the scale itself, which sizes take, such as the side of a cover, of an icon or of a control; space, text and corners take their tokens, named as the sizes of clothes, `m` the one of their family most things take.

### Space

| Token | Value | Takes it |
|---|---|---|
| `--app-space-xs` | u0 / φ ≈ 6.1 px | between the parts of a card, `.app-item`; the rows of a meta line; the controls of the toolbar, `--app-control-gap` |
| `--app-space-s` | u0 ≈ 9.9 px | the parts of a component, such as a title and its credits; the pills of a row, `.library-pills`; the cards of a list, `.app-list` |
| `--app-space-m` | u1 = 16 px | the padding of a card on a phone; a title and what follows it, `.library-section` |
| `--app-space-l` | u2 ≈ 25.9 px | the gutters of the page; the padding of a card beyond a phone; the parts of a page, such as the cover and the heading of a book |
| `--app-space-xl` | u3 ≈ 41.9 px | between the sections of a page, `.library-sections` |

A space that the browser cannot take from a custom property, such as the width of a media query, is in rem: `42rem` is the one breakpoint, a phone below it.

### Text

| Token | Value | Takes it |
|---|---|---|
| `--app-text-s` | u1 / √φ ≈ 12.6 px | the interface: pills, meta lines, labels, captions |
| `--app-text-m` | u1 = 16 px | the text, the titles of the cards and of the sections |
| `--app-text-l` | u1 × √φ ≈ 20.4 px | a heading, `.library-heading`, the title of the page on a phone |
| `--app-text-xl` | u2 × √φ ≈ 32.9 px | the title of the page beyond a phone, the title of a book |
| `--app-text-2xl` | u3 × φ ≈ 67.8 px | a figure alone, such as a figure of the recap |
| `--app-text-3xl` | u3 × φ² ≈ 109.7 px | the year of the recap |

Each size is a utility too, `.app-text-s` to `.app-text-3xl`. The text is in the font of code, `--app-font-mono`; the interface in the one of the system, `--app-font-ui`, through `.app-font-ui`. A line of text is `--app-line-height`, φ, high.

### Corners

| Token | Value | Takes it |
|---|---|---|
| `--app-radius-xs` | m / φ³ ≈ 2.3 px | a tag |
| `--app-radius-s` | m / φ^1.5 ≈ 4.8 px | a cover |
| `--app-radius-m` | u0 ≈ 9.9 px | a card, a field, a pill, a sheet |

A corner inside another, such as an option inside a picker, is the one outside less the space between them: `calc(var(--app-radius-m) - var(--app-space-xs))`.

### Colors

`--app-bg`, `--app-text`, `--app-text-muted`, `--app-line`, `--app-line-soft` and `--app-fill` are grey, each with its value in both themes, through `light-dark()`. The app has one color, `--library-status`, a tinted grey for a book not in the library; the states of a field have theirs, `--library-error`, `--library-success` and `--library-warning`, as low in chroma. Each is at least 4.5:1 on the background, and on its tint, in both themes: a color added keeps it.

### Others

- `--app-control-size`, u3: a control, a field of one line, the square of an icon button; the target of a finger.
- `--app-max-width`: the column of the app, the page; its content is the column less its gutters.
- `--app-focus-outline` and `--app-focus-outline-offset`: the focus, everywhere the same.
- `--library-cover-width` and `--library-cover-height`: a cover in a card, and in the row of the latest readings.

### Lengths written as they are

None, but what is no size of the scale: a line of 1 to 3 px, a length in `em`, which follows its text (a padding is in em of its text: 1em inline, 1em / √φ block), a percentage or a length of a container (`cqi`), the whole or the half of the screen (`100dvh`, `50vw`), and a length in rem in a media or container query or in the definition of a token. `pnpm lint:css` fails on any other.

## Names

- `app-`: what the apps share, the base of `app/css/app-style.css`, a copy of [the gist](https://gist.github.com/19h47/27df983fac74beb8d3e0a30688654498): tokens, `.app-pill`, `.app-control`, `.app-list`, `.app-item`, the utilities. It changes in the gist, then in every app, never in its copy alone.
- `library-`: what the reading app adds, its tokens, its components and its custom elements.
- A class names a block, an element of it or a variant, as BEM does: `.library-item`, `.library-item__title`, `.library-fields--aside`. A class of a view names a part of that view; a style used in several places is a component of its own.
- A utility sets one property, and wins over the components: `.app-text-s`, `.app-text-muted`, `.app-font-ui`, `.app-font-normal`, `.app-uppercase`, `.sr-only`. A meta line is `app-text-s app-text-muted app-font-ui app-uppercase`.
- A brick is a custom element, `library-<name>`, in `app/js/bricks/library-<name>.js`, which the page loads by its name.
- A PHP component is `app/components/<name>.php`, printed by `Markup::render_component( '<name>', $args )`; its sheet, `app/css/components/<name>.css`, if it has styles of its own.

## Components

The components are built as those of a design file, each level made of the ones before it: atoms, molecules, organisms, then the screens, the views. `ARCHITECTURE.md` lists them; the styleguide shows them in that order.

- **One component per content.** A component is a file of its own, never a generic one with options: the same content in another state is a variant, an argument of the component (`achievement-card`, `close`), another content is another file (`book-card`, `book-row`, `term-card`). What they share is a class: `.app-item`, the box of a card, its border, its padding and its corners.
- **No margin, no width.** A component fills the place its parent gives it: the parent spaces it, with a gap or a margin, and sizes it, a column, the page or a frame. But an atom of a size of its own keeps it, such as an icon or a control, or takes it from a property its place sets, as a cover does, and a layer fixed to the screen sizes itself from the screen, such as a notice or a sheet.
- **Its place sets its properties.** A place that needs a component otherwise sets the custom properties the component reads, rather than a selector of its own reaching into it: a card gives its cover `--library-cover-inline-size`, the last screen of the recap gives its pills `--app-pill-background`, the styleguide gives its strips `--library-bleed`. The component lists its properties in its comment and in its caption in the styleguide.
- **Its container is not its own.** A component is what it shows, whatever holds it: a card is an `article`, and the item of a list holds it, `<li><?php Markup::render_component( 'book-card' ); ?></li>`, the list being the one to know it is a list.
- **Its heading level is an argument.** A component that has a heading takes its level, `level`, as its place sets it.

### Data attributes

A script reaches an element by a data attribute, or by what it is, a custom element (`library-reading`), a native one (`summary`, `dialog`, `form`) or a state of ARIA (`[aria-current="page"]`): never by a class, which belongs to its styles, and never by a class of its own, `.js-*`. ESLint fails on a script that does.

| Attribute | What it is |
|---|---|
| `data-field="name"` | a field a brick fills with text, through `fill()`; empty, it is hidden, with its row |
| `data-html="name"` | a field filled with HTML the API renders, through `fillHtml()`, such as the notes of a book |
| `data-row="name"` | a row, hidden while its field is empty: a label and its field |
| `data-list="name"` | a list a brick fills with its items, or reads, such as the months of the diary |
| `data-action="name"` | a button, by what it does: `edit`, `remove`, `next-idea` |
| `data-part="name"` | a part of a component a script needs, which is neither of the above: a card, its link, the end of a list, the cover of the book open |
| `data-id` | the book of an element, which `linkBook()` sets on the item a template clones: the keys of the lists, the books `followBooks()` follows |
| `data-sample…` | what the styleguide fills, its samples |

A value a script reads from the markup, such as a label of the form of a book once saved, is an attribute named after it: `data-saving`, `data-add`, `data-edit`. A state the styles read is an attribute too: `data-covers` on a list shown as covers, `data-theme` on the page, `data-immersive` on a view that takes the screen.

## Starting points

A PHP component, `app/components/<name>.php`:

```php
<?php
/**
 * What it is, what it shows, in order, and who fills it: renderThing() fills its fields; the place that holds it sets
 * where it goes.
 *
 * @link       https://github.com/19h47/library
 * @since      x.y.z
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{level?: int} $args
 */

$args    = wp_parse_args( $args, array( 'level' => 3 ) );
$heading = 'h' . (int) $args['level'];
?>
<article class="app-item library-thing" data-part="card">
	<<?php echo esc_html( $heading ); ?> class="library-thing__title">
		<a data-part="link" data-field="title"></a>
	</<?php echo esc_html( $heading ); ?>>
	<p class="app-item__meta" data-field="line"></p>
</article>
```

Its strings in `__()`, its attributes escaped, or set by `Markup::set_attributes()`; an icon by `Markup::render_icon( 'name' )`.

Its sheet, `app/css/components/<name>.css`:

```css
/**
 * The thing, a molecule: what it is made of, and the properties its place sets, --library-thing-size.
 */

@layer app.components {
	/* What this rule does, in a sentence */
	.library-thing {
		display: grid;
		gap: var(--app-space-s);
	}
}
```

A view's sheet, `app/css/views/<view>.css`, is in `@layer app.views`, which wins over the components whatever their specificity.

A brick, `app/js/bricks/library-<name>.js`:

```js
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fill } from 'library-app/render';

/** What it is, and what it does. */
class LibraryThingElement extends Brick {
	connect( signal ) {
		this.querySelector( '[data-action="do"]' ).addEventListener(
			'click',
			() => store.doThing(),
			{ signal }
		);

		// What it shows of the store, drawn again as it changes.
		return [
			store.select(
				( state ) => state.thing,
				( thing ) => fill( this, { title: thing.title } )
			),
		];
	}
}
customElements.define( 'library-thing', LibraryThingElement );
```

Its listeners take `signal`, which aborts as the brick leaves the page; it returns what it selects of the store, unsubscribed then. No brick knows another. A list of items clones a template of `app/partials/library-app.php`, which holds the translated labels, and fills it.

## Finishing a component

1. Its markup in its component, its styles in its sheet, with tokens only: `pnpm lint:css`.
2. Its scripts reach it by data attributes: `pnpm lint:js`, `pnpm check:js`.
3. In the styleguide, at its level, in a frame, with its variants: its caption says what it is, its component, what it is made of, its variants and the properties its place sets. A column of the styleguide is at least as wide as the column of the app: a component that does not fill it, or goes past it, breaks a rule above. Its heading, the name of the sample, gives it its place in the contents.
4. Accessible: a name for each control, its focus seen (`--app-focus-outline`), a control as large as `--app-control-size`, its heading level from its place, its colors among the tokens, a motion only under `prefers-reduced-motion: no-preference`.
5. Its strings in French too, with the spaces of French typography, no-break before `:`, `;`, `?`, `!` and `%`, inside « », between a number and its unit: `sh bin/update-translations.sh`.
6. A behavior, a test in `tests/e2e`; a route or a rule of PHP, a test in `tests`. See `TESTING.md`.
7. `ARCHITECTURE.md` lists it at its level.
