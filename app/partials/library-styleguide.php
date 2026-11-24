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
					<p>The text, <code>.library-heading</code>, the heading of a page or a dialog, <code>--large</code> the title of a book, a meta line, the utilities <code>.app-text-s .app-text-muted .app-font-ui .app-uppercase</code>, <code>.app-font-normal</code> in a heading, <code>.library-excerpt</code>, the excerpt of a card, three lines at most, and <code>.library-prose</code>, text written in the admin, its paragraphs a step apart</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<p>The text of the app, in its font of code.</p>
					<p class="library-heading">The heading of a page</p>
					<p class="library-heading library-heading--large">The title of a book</p>
					<p class="app-text-s app-text-muted app-font-ui app-uppercase">A meta line · 2026</p>
					<p class="library-excerpt">The excerpt of a card, small and muted: a longer one ends in an ellipsis at the end of its third line, as this one does on a phone, where it goes on and on, well past the room of three lines of a card.</p>
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
					<h4 class="library-styleguide__name"><code>book-cover</code></h4>
					<p>The cover of a book, its image, or without one its title in a frame of the shape of a cover; its place sets its height, <code>--library-cover-block-size</code>, the row of the latest readings by default, or its width, <code>--library-cover-inline-size</code>, and hides the frame where the title shows already, <code>--library-cover-frame: none</code>; <code>large</code>, the one of the page of a book, standing out of the page</p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<?php
					Markup::render_component( 'book-cover', array( 'attributes' => array( 'data-sample-cover' => true ) ) );
					Markup::render_component( 'book-cover', array( 'attributes' => array( 'data-sample-frame' => true ) ) );
					Markup::render_component(
						'book-cover',
						array(
							'attributes' => array(
								'style'             => '--library-cover-block-size: calc(var(--library-cover-height) * var(--app-phi))',
								'data-sample-cover' => true,
							),
						)
					);
					Markup::render_component(
						'book-cover',
						array(
							'attributes' => array(
								'style'             => '--library-cover-inline-size: var(--library-cover-width); --library-cover-block-size: auto',
								'data-sample-cover' => true,
							),
						)
					);
					?>
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
					<h4 class="library-styleguide__name"><code>.library-input</code></h4>
					<p>The control of a field, which each one carries, as a pill its class: an input, a select and a textarea, as high as a control but the textarea</p>
				</figcaption>
				<div class="library-styleguide__frame library-form">
					<input class="library-input" aria-label="An input" value="An input">
					<select class="library-input" aria-label="A select"><option>A select</option></select>
					<textarea class="library-input" aria-label="A textarea">A textarea</textarea>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>offline-status</code></h4>
					<p>The status of the app offline, an <code>.app-status</code>; its root is its custom element, <code>offline-status</code>, which shows it while the browser says it is offline, shown here whatever the network</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="offline-status">
					<?php Markup::render_component( 'offline-status' ); ?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>search-form</code></h4>
					<p>The field of a search, as high as a control, and its button; its keywords show under it while it is used. Its root is its custom element, <code>search-form</code>, its <code>value</code> the query and its <code>open</code> its keywords shown, which sends the input of its field, a keyword taken too, and the submit of its form</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="app-search">
						<?php Markup::render_component( 'search-form', array( 'id' => 'library-styleguide-search' ) ); ?>
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
					<h4 class="library-styleguide__name"><code>progress-bar</code></h4>
					<p>How far a thing is, such as an achievement close to be earned: its bar, the room left, then its count, which the bar reads in words; its place sets its <code>value</code>, its <code>max</code> and <code>labelledBy</code>, what names it</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="progress-bar">
					<p id="library-styleguide-progress-bar">Read the books of a series</p>
					<?php Markup::render_component( 'progress-bar' ); ?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>covers-toggle</code></h4>
					<p>The Covers <code>pill-checkbox</code> at the end of a title, which shows the lists of books as a grid of their covers, or as cards; the brick <code>library-covers-toggle</code> around it in the app ties it to the store</p>
				</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<?php Markup::render_component( 'covers-toggle', array( 'id' => 'library-styleguide-covers-toggle' ) ); ?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>back</code></h4>
					<p>A pill with its arrow, above the page of a book, of a person, of a series or of a collection, and the recap</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<library-back class="library-back">
						<?php Markup::render_component( 'back' ); ?>
					</library-back>
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
					<h4 class="library-styleguide__name"><code>cover-link</code></h4>
					<p>A cover that opens its book, the component <code>cover-link</code>, its state for assistive technologies only: read, being read, to read, and the book open, <code>[aria-current="page"]</code>, its line as firm as the text</p>
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
					<h4 class="library-styleguide__name"><code>.library-fields--columns</code></h4>
					<p>Fields side by side, as many a row as fit, the values lined up whatever lines a name takes, as the figures of the calendar of the diary; its place may set <code>--library-fields-value-size</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<dl class="library-fields library-fields--columns">
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">Readings</dt>
							<dd>67</dd>
						</div>
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">Pages</dt>
							<dd>22 600</dd>
						</div>
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">Days of reading</dt>
							<dd>201</dd>
						</div>
						<div>
							<dt class="app-text-s app-text-muted app-font-ui app-uppercase">Days in a row</dt>
							<dd>20</dd>
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
					<h4 class="library-styleguide__name"><code>meta-field</code></h4>
					<p>A <code>field</code> of a book meta in the form of a book, its label, its input and its description as the meta is registered, named as the REST API names it: here the ISBN</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'meta-field',
						array(
							'key' => 'library_isbn',
							'id'  => 'library-styleguide-meta-field',
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>image-field</code></h4>
					<p>A <code>book-cover</code> and the pills that pick it and remove it, Remove shown while it shows an image; its root is its custom element, <code>image-field</code>, which shows the photo picked at once and, removed, checks its checkbox, <code>&lt;name&gt;_removed</code>, which the reset of the form unchecks</p>
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
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>reading-period</code></h4>
					<p>The period of a reading, two date fields: its start above its end, on one line beyond a phone, an arrow between them; its root is its custom element, <code>reading-period</code>, which keeps the end after the start, both up to today</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="reading-period">
					<?php Markup::render_component( 'reading-period' ); ?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>partial-date</code></h4>
					<p>A date often known by its year only, such as the first publication of a book: its legend, then its day, its month in words and its year side by side, then its description; its root is its custom element, <code>partial-date</code>, named as its field, whose form sends its value, <code>1972</code>, <code>1972-11</code> or <code>1972-11-08</code>. The day waits for a month and lists its days; the year is required once a month is chosen. Here empty, then a month of a year</p>
				</figcaption>
				<div class="library-styleguide__frame library-form" data-sample="partial-date">
					<?php
					foreach ( array( 'empty', 'month' ) as $variant ) {
						Markup::render_component(
							'partial-date',
							array(
								'id'          => "library-styleguide-date-$variant",
								'label'       => 'Edition date',
								'description' => 'When this edition first came out.',
								'name'        => "date_$variant",
							)
						);
					}
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>barcode-scan</code></h4>
					<p>The scan of the barcode of a book, under the field it types, <code>for</code>: the pill that takes its photo, then what the photo told, a status; its root is its custom element, <code>barcode-scan</code>, which shows it where the browser reads EAN-13 barcodes, here a photo without one</p>
				</figcaption>
				<div class="library-styleguide__frame library-form" data-sample="barcode-scan">
					<?php
					Markup::render_component(
						'field',
						array(
							'id'    => 'library-styleguide-isbn',
							'label' => 'ISBN',
						)
					);
					Markup::render_component(
						'barcode-scan',
						array(
							'id'  => 'library-styleguide-scan',
							'for' => 'library-styleguide-isbn',
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
					<h4 class="library-styleguide__name"><code>book-card</code></h4>
					<p>The card of a book, the component <code>book-card</code>, an <code>article</code> and an <code>.app-item</code>, its heading level <code>level</code>, 3 by default: a <code>book-cover</code> as wide as <code>--library-cover-width</code>, its meta, its title, its excerpt and its tags; with a cover, without, with an excerpt and a form, not kept in its original language, to buy, long, bare</p>
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
					<h4 class="library-styleguide__name"><code>book-row</code></h4>
					<p>A book in a row, the component <code>book-row</code>, its heading level <code>level</code>, 4 by default, as the reading idea on the home: a <code>book-cover</code>, its title on two lines, its authors and its status; with a cover, not kept without, with a long title</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="rows"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>cover-link</code>, current</h4>
					<p>A book being read, as the home shows it, the component <code>cover-link</code>, its <code>current</code>: its cover a step higher, its title and its status under it; with a cover, not kept without, with a long title</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="current"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>term-card</code></h4>
					<p>The card of an author of a series or of a series of a person, the component <code>term-card</code>, its heading level <code>level</code>, 4 by default: its name, its description, clamped, and its line; with a description, without</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="terms"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>reading-idea</code></h4>
					<p>The reading idea of the week, the component <code>reading-idea</code>, its heading level <code>level</code>, 2 by default: its reason, its book as a <code>book-card</code>, then a pill for another idea; <code>compact</code>, as on the home, its book as a <code>book-row</code>, the action a link at the end of its title</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="reading-idea">
					<?php
					Markup::render_component( 'reading-idea', array( 'level' => 4 ) );
					Markup::render_component(
						'reading-idea',
						array(
							'compact' => true,
							'level'   => 4,
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>achievement-card</code></h4>
					<p>The component <code>achievement-card</code>, earned, its subject a link when it leads to its books</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="achievements"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>achievement-card</code>, close</h4>
					<p>The component <code>achievement-card</code> with <code>close</code>, close to be earned, with its progress</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list" data-sample="close"></ul>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>cards-group</code></h4>
					<p>A group of cards, the component <code>cards-group</code>: its cards side by side, as many as fit, each at least <code>--library-cards-group-min</code> wide, a third of the page by default, two in the column of the app; past five, a <code>.library-disclosure</code> folds the others under a pill, as the authors of a series, which a section of the page and its title hold</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="cards-group">
					<?php Markup::render_component( 'cards-group' ); ?>
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
					<h4 class="library-styleguide__name"><code>views-nav</code></h4>
					<p>The views, a <code>.library-strip</code> of pills, the current one marked; <code>tabs</code>, on a phone, the tabs of an app, as the bar at the bottom of the screen holds them: the app shows them, wider than a frame</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'views-nav',
						array(
							'views'   => array(
								'books'        => array(
									'label' => 'Books',
									'url'   => '#top',
								),
								'diary'        => array(
									'label' => 'Diary',
									'url'   => '#top',
								),
								'idea'         => array(
									'label' => 'Idea',
									'url'   => '#top',
								),
								'achievements' => array(
									'label' => 'Achievements',
									'url'   => '#top',
								),
								'to-buy'       => array(
									'label' => 'To buy',
									'url'   => '#top',
								),
							),
							'current' => 'books',
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.app-controls</code></h4>
					<p>The controls of a list: the search, its keywords, a <code>.library-strip</code>, under it while it is used; under the title of the books, the button of the filters, the order and the filters chosen, a pill each, on one line while it holds them</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="app-controls">
						<div class="app-search" data-sample="keywords">
							<?php Markup::render_component( 'search-form', array( 'id' => 'library-styleguide-search-keywords' ) ); ?>
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
						<button class="app-pill" type="button">To read <?php Markup::render_icon( 'close' ); ?></button>
						<button class="app-pill" type="button">All but Novel <?php Markup::render_icon( 'close' ); ?></button>
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
						Markup::render_component(
							'checkboxes-field',
							array(
								'inline'  => true,
								'id'      => 'library-styleguide-sheet-forms',
								'label'   => 'Forms',
								'name'    => 'forms',
								'options' => array(
									'novel' => 'Novel',
									'comic' => 'Comic',
								),
								'checked' => array( 'comic' ),
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
					<h4 class="library-styleguide__name"><code>filters-sheet</code></h4>
					<p>The filters of a list, in an <code>.app-controls</code>, its custom element its root: the pill that opens their <code>.library-sheet</code>, a <code>field</code> with a select for a filter of one choice, an inline <code>checkboxes-field</code> for one of several, such as the forms, then the filters chosen, a pill each that says its choice, all but the few values left unchecked, or its label and how many values are checked, and removes it, after the other controls, on the same line while it holds them; its place sets its <code>value</code>, the <code>counts</code> of the values and what the list <code>found</code>, here the brick <code>library-filters</code> around it in the app, its <code>list</code> the one it counts, the books found or the readings of the diary</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="app-controls">
						<?php
						Markup::render_component(
							'filters-sheet',
							array(
								'id'      => 'library-styleguide-filters',
								'filters' => array(
									'forms' => array(
										'label'    => 'Forms',
										'options'  => array(
											'novel' => 'Novel',
											'comic' => 'Comic',
										),
										'multiple' => true,
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
					<h4 class="library-styleguide__name"><code>checkboxes-field</code></h4>
					<p>The choices made, a pill each that removes it, then the pill that opens all of them in a <code>.library-sheet</code>, a <code>pill-checkbox</code> each; with a description. Inline, <code>inline</code>, few choices shown all at once, a <code>pill-checkbox</code> each, with how many each has, <code>counts</code>, such as the forms of the filters and their books, then, <code>toggle_all</code>, a pill that checks them all, or unchecks them once they are: two checked. Its root is its custom element, <code>checkboxes-field</code>, named as its field, its <code>value</code> the values checked</p>
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
							'options'     => array(
								'en' => 'English',
								'fr' => 'French',
								'de' => 'German',
								'ja' => 'Japanese',
							),
							'checked'     => array( 'en', 'fr' ),
							'description' => 'Its description, under its choices.',
						)
					);
					Markup::render_component(
						'checkboxes-field',
						array(
							'inline'     => true,
							'id'         => 'library-styleguide-forms',
							'label'      => 'Forms',
							'name'       => 'forms',
							'toggle_all' => true,
							'options'    => array(
								'novel' => 'Novel',
								'comic' => 'Comic',
								'essay' => 'Essay',
								'poems' => 'Poetry',
							),
							'counts'     => array(
								'novel' => 653,
								'comic' => 579,
								'essay' => 50,
							),
							'checked'    => array( 'novel', 'comic' ),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>inputs-list</code></h4>
					<p>A list of inputs, a row each with the <code>.app-control</code> that removes it, then the pills that add one: the readings of a book, from their starts to their ends, each with the rating it ended with, under its dates on a phone, with a description; the first one of unknown dates, the second one rated, the last one in error</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'inputs-list',
						array(
							'id'          => 'library-styleguide-readings',
							'label'       => 'Readings',
							'description' => 'From its start to its end, each one left empty when it is unknown.',
							'template'    => 'library-reading-row',
							'add'         => 'Add a reading',
							'variants'    => array(
								array(
									'label' => 'Add a reading with an unknown date',
									'value' => '/',
								),
							),
							'attributes'  => array( 'data-sample' => 'readings' ),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>input-groups</code></h4>
					<p>An <code>inputs-list</code> in groups, each a fieldset shown once it holds a row, then a <code>pill-select</code> of the group a row goes in beside the pill that adds it: the people of a book by role, its colorists none</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'input-groups',
						array(
							'id'            => 'library-styleguide-role',
							'label'         => 'People',
							'groups'        => array(
								'author'     => 'Authors',
								'translator' => 'Translators',
								'colorist'   => 'Colorists',
							),
							'options'       => array(
								'author'     => 'Author',
								'translator' => 'Translator',
								'colorist'   => 'Colorist',
							),
							'options_label' => 'Role',
							'add'           => 'Add a person',
							'template'      => 'library-edit-person',
							'attributes'    => array( 'data-sample' => 'people' ),
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
					<p>The book open, filled by <code>fillBookPage()</code>: its title page, the component <code>book-header</code>; its notes, a <code>.library-prose</code>; its reading, <code>pill-checkbox</code> and <code>pill-select</code>, its readings folded under the line that sums them up; its fields, the component <code>book-fields</code>, a group each for its work, its edition and its copy; its action</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="library-book" data-sample="book">
						<?php Markup::render_component( 'book-header', array( 'level' => 4 ) ); ?>
						<div class="library-prose" data-html="notes" hidden></div>
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
										'id'      => 'library-styleguide-status',
										'label'   => 'Library',
										'options' => array( 'publish' => 'In the library' ),
									)
								);
								?>
							</div>
							<details class="library-disclosure library-reading__details">
								<summary class="app-text-s app-text-muted app-font-ui app-uppercase">Read 2 times · April 4, 2025 · in 12 days</summary>
								<p>Its readings, from their starts to their ends, each with the rating it ended with.</p>
							</details>
						</div>
						<?php Markup::render_component( 'book-fields', array( 'level' => 5 ) ); ?>
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
					<h4 class="library-styleguide__name"><code>catalogue-status</code></h4>
					<p>Under the ISBN of a book to add, a status: the book of the catalogue that has its ISBN or its title already, added, a book to buy bought from there, or put in the library from there; else what the catalogue of the BnF answered</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div data-sample="catalogue-status-duplicate">
						<?php Markup::render_component( 'catalogue-status' ); ?>
					</div>
					<div data-sample="catalogue-status-catalog">
						<?php Markup::render_component( 'catalogue-status' ); ?>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>fields-disclosure</code></h4>
					<p>Fields folded under a summary, such as the ones few books have in the form of a book: its summary says its label and how many of its fields are filled in, here one of two; its root is its custom element, <code>fields-disclosure</code>, which counts them as they change, its <code>open</code> and its <code>recount()</code> for its place</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'fields-disclosure',
						array(
							'label'  => 'More details',
							'fields' => function (): void {
								Markup::render_component(
									'field',
									array(
										'id'         => 'library-styleguide-detail',
										'label'      => 'A field',
										'attributes' => array(
											'name'  => 'detail',
											'value' => 'Filled in',
										),
									)
								);
								Markup::render_component(
									'field',
									array(
										'id'         => 'library-styleguide-detail-empty',
										'label'      => 'Another field',
										'attributes' => array( 'name' => 'other_detail' ),
									)
								);
							},
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>.library-recap__screen</code></h4>
					<p>A screen of the recap of a year, as high as the screen in the app: its <code>__title</code>, its <code>__figure</code>, a <code>count-up</code> that counts up to its value as it comes on the screen, its lines</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="count-up">
					<section class="library-recap__screen">
						<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal">Books read</h3>
						<p class="library-recap__figure"><?php Markup::render_component( 'count-up' ); ?></p>
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
					<h4 class="library-styleguide__name"><code>months-chart</code></h4>
					<p>The readings of each month of a year, a <code>.library-bar--vertical</code> each, which assistive technologies read as the name of the month and its books; its place sets <code>year</code> and <code>months</code>, and may set <code>--library-months-chart-height</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php Markup::render_component( 'months-chart' ); ?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>shares-chart</code></h4>
					<p>The forms or the languages, a <code>.library-bar</code> each, as long as the share of the first one; <code>ranked</code>, the people or the series, a link each, a name on one line; its place sets <code>shares</code></p>
				</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component( 'shares-chart' );
					Markup::render_component( 'shares-chart', array( 'ranked' => true ) );
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>month-calendar</code></h4>
					<p>The days of a month, a <code>.library-day</code> each, in weeks from Monday, the days to come outlined; <code>covers</code>, as the diary shows a month, each day its number and the book whose reading ended then, a link to it, its cover or its title, the others counted; its place sets <code>value</code>, and the colors of the levels, <code>--library-day-1</code> to <code>--library-day-4</code></p>
				</figcaption>
				<div class="library-styleguide__frame library-calendar" data-sample="month-calendar">
					<?php
					Markup::render_component( 'month-calendar' );
					Markup::render_component( 'month-calendar', array( 'covers' => true ) );
					?>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>days-calendar</code></h4>
					<p>The days of a year, in weeks from Monday, as many weeks a row as the shape of the container of its place makes it; its place sets <code>value</code>, the year, the level of each day gone by and the memorable ones, pulsing</p>
				</figcaption>
				<div class="library-styleguide__frame">
					<div class="library-styleguide__calendar">
						<?php Markup::render_component( 'days-calendar' ); ?>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption>
					<h4 class="library-styleguide__name"><code>recap-link</code></h4>
					<p>The recap of a year: gone by, on the home in January, an <code>.app-item</code>; <code>pill</code>, in the diary before the latest month of a year gone by; <code>fillRecapLink()</code> fills both</p>
				</figcaption>
				<div class="library-styleguide__frame" data-sample="recap-link">
					<?php
					Markup::render_component( 'recap-link' );
					Markup::render_component( 'recap-link', array( 'pill' => true ) );
					?>
				</div>
			</figure>
		</div>
	</section>
</library-styleguide>
