<?php
/**
 * The styleguide: the tokens and the components of the app, for development, opened by searching "styleguide" then Enter.
 *
 * Ordered as a design file, from the tokens to the screens, each level made of the ones before it: the foundations, the
 * atoms, the molecules, the organisms, then the screens. Each component shows in a frame, with the markup the app renders:
 * the components of PHP render with Markup::render_component(), the [data-sample] parts are filled by library-styleguide
 * with the templates and the functions the app renders with. Its caption names it, says what it is made of and its
 * variants, as the properties of a component of a design file. Written in English, as code is: nothing here is
 * translated, but what the components add, such as the mark of a required field.
 *
 * @link       https://github.com/19h47/library
 * @since      6.8.0
 *
 * @package    Library
 * @subpackage Library/app/partials
 */

use Library\Markup;

$colors = array( '--app-bg', '--app-text', '--app-text-muted', '--app-line', '--app-line-soft', '--app-fill', '--library-status', '--library-error', '--library-success', '--library-warning' );
$spaces = array( '--app-space-xs', '--app-space-s', '--app-space-m', '--app-space-l', '--app-space-xl' );
$radii  = array( '--app-radius-xs', '--app-radius-s', '--app-radius-m' );
$fonts  = array( '--app-font-mono', '--app-font-ui' );
// The sizes of the text, as the styles write them, from the smallest, and their family.
$sizes = array(
	array( 'var(--app-text-s)', 'var(--app-font-ui)' ),
	array( 'var(--app-text-m)', 'var(--app-font-mono)' ),
	array( 'var(--app-text-l)', 'var(--app-font-mono)' ),
	array( 'var(--app-text-xl)', 'var(--app-font-mono)' ),
	array( 'var(--app-text-2xl)', 'var(--app-font-mono)' ),
	array( 'var(--app-text-3xl)', 'var(--app-font-mono)' ),
);?>
<library-styleguide class="library-sections" lang="en">
	<header class="library-styleguide__header">
		<h2 class="library-title">Styleguide</h2>
		<p class="library-styleguide__intro">The components of the app, from its tokens to its screens, each in a frame with the markup the app renders. How to add one: DESIGN-SYSTEM.md.</p>
	</header>

	<section class="library-section">
		<h3 class="library-title">Foundations</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Colors</h4>
				</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( $colors as $color ) : ?>
						<li>
							<span class="library-styleguide__swatch" style="--library-token: var(<?php echo esc_attr( $color ); ?>)"></span>
							<code><?php echo esc_html( $color ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Space</h4>
				</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( $spaces as $space ) : ?>
						<li>
							<span class="library-styleguide__step" style="--library-token: var(<?php echo esc_attr( $space ); ?>)"></span>
							<code><?php echo esc_html( $space ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Corners</h4>
				</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( $radii as $radius ) : ?>
						<li>
							<span class="library-styleguide__radius" style="--library-token: var(<?php echo esc_attr( $radius ); ?>)"></span>
							<code><?php echo esc_html( $radius ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Sizes of the text</h4>
				</figcaption>
				<ul class="library-styleguide__sizes" data-list="sizes">
					<?php foreach ( $sizes as list( $size, $family ) ) : ?>
						<li>
							<span class="library-styleguide__size" data-part="size" style="--library-token: <?php echo esc_attr( $size ); ?>; --library-family: <?php echo esc_attr( $family ); ?>">Reading 2026</span>
							<span class="library-styleguide__use"><code><?php echo esc_html( $size ); ?></code> · <span data-size></span></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Fonts</h4>
				</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( $fonts as $font ) : ?>
						<li>
							<span class="library-styleguide__font" style="--library-token: var(<?php echo esc_attr( $font ); ?>)">Aa 0123</span>
							<code><?php echo esc_html( $font ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Styles of text</h4>
					<p>The text, <code>.library-heading</code>, the heading of a page or a dialog, a meta line, the utilities <code>.app-text-s .app-text-muted .app-font-ui .app-uppercase</code>, <code>.app-font-normal</code> in a heading, and <code>.library-prose</code>, text written in the admin, its paragraphs a step apart</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p>The text of the app, in its font of code.</p>
					<p class="library-heading">The heading of a page</p>
					<p class="app-text-s app-text-muted app-font-ui app-uppercase">A meta line · 2026</p>
					<div class="library-prose">
						<p>Text written in the admin, such as the notes of a book.</p>
						<p>A second paragraph, with <em>a word in italics</em>.</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name">Icons</h4>
					<p>From Material Symbols, outlined, at 24 px: <code>Markup::render_icon( $name )</code>, the size of the text, in its color</p>
				</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( array_keys( Markup::ICONS ) as $icon ) : ?>
						<li>
							<span class="library-styleguide__icon"><?php Markup::render_icon( $icon ); ?></span>
							<code><?php echo esc_html( $icon ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Atoms</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-pill</code></h4>
					<p>An action or a link in words: a link, the current one, a button, disabled, a count, small, and the main action of a form, <code>.app-pill--primary</code></p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<a class="app-pill" href="#top">A link</a>
					<a class="app-pill" href="#top" aria-current="page">The current one</a>
					<button class="app-pill" type="button">A button</button>
					<button class="app-pill" type="button" disabled>Disabled</button>
					<p class="app-pill">A count <strong>12</strong></p>
					<button class="app-pill app-pill--small" type="button">Small</button>
					<button class="app-pill app-pill--primary" type="button">Save</button>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-control</code></h4>
					<p>An action as an icon, its name for assistive technologies: add, the theme in light then in dark, close</p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<button class="app-control" type="button" aria-label="Add"><?php Markup::render_icon( 'add' ); ?></button>
					<button class="app-control" type="button" aria-label="Dark theme" aria-pressed="false"><?php Markup::render_icon( 'dark_mode' ); ?><?php Markup::render_icon( 'light_mode' ); ?></button>
					<button class="app-control" type="button" aria-label="Dark theme" aria-pressed="true"><?php Markup::render_icon( 'dark_mode' ); ?><?php Markup::render_icon( 'light_mode' ); ?></button>
					<button class="app-control" type="button" aria-label="Close"><?php Markup::render_icon( 'close' ); ?></button>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-link</code></h4>
					<p>A button in words, as a link, its text the one around it: in the text, or in a meta line</p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<button class="library-link" type="button">Undo</button>
					<span class="app-text-s app-text-muted app-font-ui app-uppercase"><button class="library-link" type="button">Another idea</button></span>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-tag</code></h4>
					<p>A word in a tint of its color: the form of a book, its original language, and its status, <code>.library-tag--status</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p class="app-item__meta">
						<span class="library-tag">A form</span>
						<span class="library-tag">VO</span>
						<span class="library-tag library-tag--status">Not kept</span>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-cover</code></h4>
					<p>The image of a book, or <code>.library-cover--frame</code>, its title in a frame of the shape of a cover; its place sets its height, <code>--library-cover-block-size</code>, the row of the latest readings by default, or its width, <code>--library-cover-inline-size</code></p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<img class="library-cover" data-sample-cover alt="" loading="lazy" hidden>
					<span class="library-cover library-cover--frame">
						<span class="library-cover__title">A book without its cover, and a title long enough to be cut</span>
					</span>
					<img class="library-cover" style="--library-cover-block-size: calc(var(--library-cover-height) * var(--app-phi))" data-sample-cover alt="" loading="lazy" hidden>
					<img class="library-cover" style="--library-cover-inline-size: var(--library-cover-width); --library-cover-block-size: auto" data-sample-cover alt="" loading="lazy" hidden>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-status</code></h4>
					<p>The message of a view, such as no book found, or the app offline; <code>[role="alert"]</code>, an error of the server at the end of a form</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p class="app-status">A message, such as no book found.</p>
					<p class="app-status" role="alert">The book could not be saved.</p>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-search</code></h4>
					<p>The field of a search, as high as a control</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="app-search">
						<input id="library-styleguide-search" type="search" placeholder="Title, author, series, ISBN…" aria-label="A search">
					</div>
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Molecules</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-title</code></h4>
					<p>The title of a section: its <code>__heading</code> on balanced lines, its count, a meta line, and its action at the end of the last line, <code>__action</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="library-title">
						<h4 class="library-title__heading">
							A section title long enough to go on two lines
							<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal">12 books</span>
						</h4>
						<a class="app-text-s app-text-muted app-font-ui app-uppercase library-title__action" href="#top">A link</a>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>pill-checkbox</code></h4>
					<p>And <code>pill-select</code>, a pill with its control: a checkbox, checked or not, and a select</p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<?php
					Markup::render_component(
						'pill-checkbox',
						array(
							'id'    => 'library-styleguide-pill-checkbox',
							'label' => 'A checkbox',
						)
					);
					Markup::render_component(
						'pill-checkbox',
						array(
							'id'      => 'library-styleguide-pill-checked',
							'label'   => 'Checked',
							'control' => '<input type="checkbox" checked>',
						)
					);
					Markup::render_component(
						'pill-select',
						array(
							'id'      => 'library-styleguide-pill-select',
							'label'   => 'A choice',
							'options' => array(
								'a' => 'A select',
								'b' => 'Another choice, longer',
							),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>back</code></h4>
					<p>A pill with its arrow, above the page of a book, of a person, of a series or of a collection, and the recap</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php Markup::render_component( 'back' ); ?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-pills</code></h4>
					<p>A row of pills on as many lines as it needs, such as the series of a cycle or the filters chosen, which remove them</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-pills">
						<li><a class="app-pill" href="#top">A Series</a></li>
						<li><a class="app-pill" href="#top">Another Series, with a longer name</a></li>
						<li><button class="app-pill" type="button">To read <?php Markup::render_icon( 'close' ); ?></button></li>
					</ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-strip</code></h4>
					<p>A row of pills or covers that scrolls sideways across the viewport, the counterpart of <code>.library-pills</code>: its first one starts where the content does</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p class="library-strip">
						<button class="app-pill" type="button">Series</button>
						<button class="app-pill" type="button">Author</button>
						<button class="app-pill" type="button">Publisher</button>
						<button class="app-pill" type="button">Read in 2026</button>
						<button class="app-pill" type="button">Rated 9 or more</button>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-cover-link</code></h4>
					<p>A cover that opens its book, its state for assistive technologies only: read, being read, to read, and the book open, <code>[aria-current="page"]</code>, its line as firm as the text</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="volumes"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-fields</code></h4>
					<p>The name of each field, a meta line, above its value</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<dl class="library-fields">
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">ISBN</dt>
							<dd>978-2-07-036024-6</dd>
						</div>
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">Pages</dt>
							<dd>320</dd>
						</div>
					</dl>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-fields--aside</code></h4>
					<p>The name of each field beside its value, the names in the smaller part of the golden ratio, as the fields of a book</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<dl class="library-fields library-fields--aside">
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">ISBN</dt>
							<dd>978-2-07-036024-6</dd>
						</div>
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">Printed</dt>
							<dd>April 23, 2014</dd>
						</div>
					</dl>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-notice</code></h4>
					<p>What an action done says, a pill with its text and its action, a <code>.library-link</code>: at the bottom of the screen in the app</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p class="library-notice app-pill"><span>Reading saved.</span> <button class="library-link" type="button">Undo</button></p>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>field</code></h4>
					<p>A field of a form, its label above its control: a text with a description, a select, a textarea growing with its text, disabled</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'field',
						array(
							'id'          => 'library-styleguide-text',
							'label'       => 'A text',
							'description' => 'Its description, under it.',
						)
					);
					Markup::render_component(
						'field',
						array(
							'id'      => 'library-styleguide-select',
							'label'   => 'A select',
							'control' => Markup::get_select( array( 'a' => 'A choice' ) ),
						)
					);
					Markup::render_component(
						'field',
						array(
							'id'         => 'library-styleguide-textarea',
							'label'      => 'A textarea, growing with its text',
							'control'    => '<textarea></textarea>',
							'attributes' => array( 'rows' => 3 ),
						)
					);
					Markup::render_component(
						'field',
						array(
							'id'         => 'library-styleguide-disabled',
							'label'      => 'Disabled',
							'attributes' => array( 'disabled' => true ),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>field</code>, its states</h4>
					<p>Required, in error with <code>.library-error</code> under it; pointed at and focused, live</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'field',
						array(
							'id'       => 'library-styleguide-required',
							'label'    => 'A field',
							'required' => true,
						)
					);
					Markup::render_component(
						'field',
						array(
							'id'          => 'library-styleguide-invalid',
							'label'       => 'A field in error',
							'description' => 'Its description, under its message.',
							'attributes'  => array(
								'value'      => '978-2',
								'data-error' => 'Its message, the one of the browser.',
							),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>image-field</code></h4>
					<p>A <code>.library-cover</code> and the pills that pick it and remove it</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'image-field',
						array(
							'id'     => 'library-styleguide-cover',
							'name'   => 'cover',
							'label'  => 'Choose a cover',
							'remove' => 'Remove the cover',
						)
					);
					?>
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Organisms</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-item</code></h4>
					<p>The card of a book, the component <code>book-card</code>, an <code>article</code> and an <code>.app-item</code>, its heading level <code>level</code>, 3 by default: a <code>.library-cover</code> as wide as <code>--library-cover-width</code>, its meta, its title, its excerpt and its tags; with a cover, without, with an excerpt and a form, not kept in its original language, to buy, long, bare</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="books"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-list[data-covers]</code></h4>
					<p>The cards as a grid of their covers, as the Covers checkbox shows them: with a cover, without, with a long title</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-covers data-sample="grid"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-row</code></h4>
					<p>A book in a row, the component <code>book-row</code>, its heading level <code>level</code>, 4 by default, as the reading idea on the home: a <code>.library-cover</code>, its title on two lines, its authors and its status; with a cover, not kept without, with a long title</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="rows"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-current</code></h4>
					<p>A book being read, as the home shows it: a <code>.library-cover-link</code> a step higher, its title and its status under it; with a cover, not kept without, with a long title</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="current"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-term</code></h4>
					<p>The card of an author of a series or of a series of a person, the component <code>term-card</code>, its heading level <code>level</code>, 4 by default: its name, its description, clamped, and its line; with a description, without</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="terms"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-achievement</code></h4>
					<p>The component <code>achievement-card</code>, earned, its subject a link when it leads to its books</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="achievements"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-achievement</code>, close</h4>
					<p>The component <code>achievement-card</code> with <code>close</code>, close to be earned, with its progress</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="close"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-section</code></h4>
					<p>A <code>.library-title</code> then its cards; past four, <code>.library-section__more</code> folds the others under a pill, as the authors of a series</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<section class="library-section" data-folded="authors">
						<h4 class="library-title">Authors</h4>
						<ul class="app-list" data-list="shown"></ul>
						<details class="library-section__more" data-row="more" hidden>
							<summary class="app-pill" data-field="more"></summary>
							<ul class="app-list" data-list="more"></ul>
						</details>
					</section>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>term-header</code></h4>
					<p>The header of the page of a person, of a series or of a collection: a <code>.library-heading</code>, the lines that place it, a <code>.library-prose</code> and its reading; a person, then a series of a cycle</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div data-sample-header="person">
						<?php Markup::render_component( 'term-header', array( 'lines' => array( array( 'field' => 'otherNames' ) ) ) ); ?>
					</div>
					<div data-sample-header="series">
						<?php
						Markup::render_component(
							'term-header',
							array(
								'lines' => array(
									array(
										'field' => 'cycle',
										'label' => 'Cycle:',
										'link'  => true,
									),
								),
							)
						);
						?>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-nav</code></h4>
					<p>The views, a <code>.library-strip</code> of pills under the title, or on a phone the tabs in a bar at the bottom of the screen</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-strip library-nav">
						<li><a class="app-pill" href="#top" aria-current="page">Books</a></li>
						<li><a class="app-pill" href="#top">Diary</a></li>
						<li><a class="app-pill" href="#top">Idea</a></li>
						<li><a class="app-pill" href="#top">Achievements</a></li>
						<li><a class="app-pill" href="#top">To buy</a></li>
					</ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-controls</code></h4>
					<p>The controls of a list: the search, its keywords, a <code>.library-strip</code>, under it while it is used; under the title of the books, the button of the filters, the order and the filters chosen, <code>.library-pills</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="app-controls">
						<div class="app-search">
							<input id="library-styleguide-search-keywords" type="search" placeholder="Title, author, series, ISBN…" aria-label="Another search">
							<p class="library-strip library-search__keywords">
								<button class="app-pill" type="button">Series</button>
								<button class="app-pill" type="button">Author</button>
								<button class="app-pill" type="button">Publisher</button>
							</p>
						</div>
					</div>
					<h4 class="library-title library-books__title">
						Books
						<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal">15 books</span>
					</h4>
					<div class="app-controls library-books__controls">
						<button class="app-pill" type="button">Filters · 2</button>
						<?php
						Markup::render_component(
							'pill-select',
							array(
								'id'      => 'library-styleguide-controls-select',
								'label'   => 'An order',
								'options' => array( '' => 'Latest added' ),
							)
						);
						?>
						<p class="library-filters__chosen library-pills">
							<button class="app-pill" type="button">To read <?php Markup::render_icon( 'close' ); ?></button>
							<button class="app-pill" type="button">A form <?php Markup::render_icon( 'close' ); ?></button>
						</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-sheet</code></h4>
					<p>A dialog over the page, the filters or the choices of a field: <code>dialog-close</code> at its corner, a <code>.library-heading</code>, its fields and its main action; a sheet from the bottom of the screen on a phone, a dialog beyond, drawn here in its place</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<dialog class="library-sheet" id="library-styleguide-sheet" aria-labelledby="library-styleguide-sheet-title" open>
						<?php Markup::render_component( 'dialog-close', array( 'dialog' => 'library-styleguide-sheet' ) ); ?>
						<h4 class="library-heading" id="library-styleguide-sheet-title">Filters</h4>
						<?php
						Markup::render_component(
							'field',
							array(
								'id'      => 'library-styleguide-filter',
								'label'   => 'Reading state',
								'control' => Markup::get_select( array( 'no' => 'To read' ) ),
							)
						);
						?>
						<p>
							<button class="app-pill app-pill--primary" type="button">Show 15 books</button>
						</p>
					</dialog>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>checkboxes-field</code></h4>
					<p>The choices made, a pill each that removes it, then the pill that opens all of them in a <code>.library-sheet</code>, a <code>pill-checkbox</code> each; with a description</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'checkboxes-field',
						array(
							'id'          => 'library-styleguide-choices',
							'label'       => 'Choices',
							'name'        => 'choices',
							'add'         => 'Add a choice',
							'choices'     => array(
								'en' => 'English',
								'fr' => 'French',
								'de' => 'German',
								'ja' => 'Japanese',
							),
							'checked'     => array( 'en', 'fr' ),
							'description' => 'Its description, under its choices.',
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>input-list</code></h4>
					<p>A list of inputs, a row each with the <code>.app-control</code> that removes it, then the pills that add one: the readings of a book, from their starts to their ends, with a description; the first one of unknown dates, the last one in error</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'input-list',
						array(
							'id'          => 'library-styleguide-readings',
							'legend'      => 'Readings',
							'description' => 'From its start to its end, each one left empty when it is unknown.',
							'add'         => 'Add a reading',
							'action'      => 'add',
							'actions'     => array( 'add-unknown' => 'Add a reading with an unknown date' ),
							'list'        => array( 'data-sample' => 'readings' ),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>input-groups</code></h4>
					<p>An <code>input-list</code> in groups, each a fieldset shown once it holds a row, then a <code>pill-select</code> of the group a row goes in beside the pill that adds it: the people of a book by role, its colorists none</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'input-groups',
						array(
							'id'         => 'library-styleguide-role',
							'legend'     => 'People',
							'groups'     => array(
								'author'     => 'Authors',
								'translator' => 'Translators',
								'colorist'   => 'Colorists',
							),
							'choices'    => array(
								'author'     => 'Author',
								'translator' => 'Translator',
								'colorist'   => 'Colorist',
							),
							'label'      => 'Role',
							'add'        => 'Add a person',
							'action'     => 'add-person',
							'attributes' => array( 'data-sample' => 'people' ),
						)
					);
					?>
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Screens</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-book</code></h4>
					<p>The book open: its title page, a <code>.library-book__header</code>, a <code>.library-cover</code> as high as the screen allows, its <code>.library-book__heading</code>, a <code>.library-heading</code> with its subtitle, a <code>.library-book__subtitle</code>, its meta and its translation, then its excerpt; its notes, a <code>.library-prose</code>; its reading, <code>pill-checkbox</code> and <code>pill-select</code>, its readings folded under the line that sums them up; its fields, a <code>.library-book__group</code> each for its work, its edition and its copy, <code>.library-fields--aside</code>, and its action</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="library-book">
						<header class="library-book__header">
							<img class="library-cover library-book__cover" data-sample-cover alt="" loading="lazy" hidden>
							<div class="library-book__heading">
								<h4 class="library-heading library-book__title">
									<span>The title of a book</span>
									<span class="library-book__subtitle">Its subtitle</span>
								</h4>
								<p class="app-text-s app-text-muted app-font-ui app-uppercase library-book__meta">An Author · A Publisher, A Collection · 2024</p>
								<p class="library-book__translation">Translated from Italian by A Translator</p>
							</div>
							<p class="library-book__excerpt">Its excerpt, as written on its back cover.</p>
						</header>
						<div class="library-prose">
							<p>Its notes, written in its form.</p>
							<p>A second paragraph.</p>
						</div>
						<div class="library-form library-reading">
							<div class="library-pills">
								<?php
								Markup::render_component(
									'pill-checkbox',
									array(
										'id'      => 'library-styleguide-read',
										'label'   => 'Read',
										'control' => '<input type="checkbox" checked>',
									)
								);
								Markup::render_component(
									'pill-select',
									array(
										'id'      => 'library-styleguide-rating',
										'label'   => 'Rating',
										'options' => array( '8' => '8/10' ),
									)
								);
								Markup::render_component(
									'pill-select',
									array(
										'id'      => 'library-styleguide-status',
										'label'   => 'Library',
										'options' => array( 'publish' => 'In the library' ),
									)
								);
								?>
							</div>
							<details class="library-reading__details">
								<summary class="app-text-s app-text-muted app-font-ui app-uppercase">Read 2 times · April 4, 2025 · in 12 days</summary>
								<p>Its readings, from their starts to their ends.</p>
							</details>
						</div>
						<section class="library-book__group">
							<h5 class="library-title">Edition</h5>
							<dl class="library-fields library-fields--aside">
								<div data-row="isbn">
									<dt class="app-text-s app-text-muted app-font-ui app-uppercase">ISBN</dt>
									<dd>978-2-07-036024-6</dd>
								</div>
							</dl>
						</section>
						<p>
							<button class="app-pill" type="button">Edit book</button>
						</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-year</code></h4>
					<p>The year of reading, its first line large on the achievements</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="library-year">
						<p>103 read in 2026, 75 by this date last year</p>
						<p>About 125 by the end of the year at this pace</p>
						<p>571 books to read, all read in 2031 at this pace</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>#library-month</code></h4>
					<p>A month of the diary, a <code>.library-section</code> of cards; <code>#library-diary-recap</code>, the recap of a year gone by, before its latest month</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="month"></div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-edit__duplicate</code></h4>
					<p>Under the ISBN of a book to add: the book that has it already, a book to buy bought from there; else what the catalogue of the BnF answered. <code>.library-edit__details</code>, the fields few books have, folded</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p class="library-edit__duplicate">
						<span>Already added: <a href="#top">A title</a> <button class="app-pill" type="button" data-action="buy">I bought it</button></span>
					</p>
					<p class="library-edit__duplicate">
						<span>Filled in from the catalogue of the BnF: check what it gives.</span>
					</p>
					<details class="library-edit__details">
						<summary class="app-text-s app-text-muted app-font-ui app-uppercase">More details</summary>
						<?php
						Markup::render_component(
							'field',
							array(
								'id'    => 'library-styleguide-detail',
								'label' => 'A field',
							)
						);
						?>
					</details>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-recap__screen</code></h4>
					<p>A screen of the recap of a year, as high as the screen in the app: its <code>__title</code>, its <code>__figure</code>, its lines</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<section class="library-recap__screen">
						<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal">Books read</h3>
						<p class="library-recap__figure">82</p>
						<p>19 more than in 2024</p>
						<p class="app-text-s app-text-muted app-font-ui app-uppercase">Including 2 read again</p>
					</section>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-recap__mosaic</code></h4>
					<p>The covers of the books read, under the year on the first screen</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-recap__mosaic" data-sample="mosaic"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-recap__months</code></h4>
					<p>The readings of each month, which assistive technologies read as the name of the month and its books</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ol class="library-recap__months" data-sample="months"></ol>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-recap__shares</code></h4>
					<p>The forms or the languages, and <code>.library-recap__ranking</code>, the people or the series, a link each: a bar as long as the share of the first one</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-recap__shares" data-sample="shares"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-recap-link</code></h4>
					<p>The recap of the year gone by on the home, in January, an <code>.app-item</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<a class="app-item library-recap-link" href="">
						<span class="library-recap-link__year">2025</span>
						<span>The recap of the year</span>
					</a>
				</div>
			</figure>
		</div>
	</section>
</library-styleguide>
