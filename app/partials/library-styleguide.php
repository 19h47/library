<?php
/**
 * The styleguide: the tokens and the components of the app, for development, opened by searching "styleguide".
 *
 * Each component shows with its own markup alone, in a frame: one that needs its place in the app to look right
 * shows it here. The components of PHP render with Markup::render_component(), as in the app; the [data-sample] lists are
 * filled by library-styleguide, with the functions the app renders with. Written in English, as code is: nothing here
 * is translated, but what the components add, such as the mark of a required field.
 *
 * @link       https://github.com/19h47/library
 * @since      6.8.0
 *
 * @package    Library
 * @subpackage Library/app/partials
 */

use Library\Markup;

$colors = array( '--app-bg', '--app-text', '--app-text-muted', '--app-line', '--app-line-soft', '--app-fill', '--app-bg-floating', '--library-status', '--library-error', '--library-success', '--library-warning' );
$steps  = array( '--app-u0', '--app-u1', '--app-u2', '--app-u3' );
$fonts  = array( '--app-font-mono', '--app-font-ui' );
// The sizes of the text, as the styles write them, from the smallest: their family, and what they set.
$sizes = array(
	array( 'var(--app-font-small)', 'var(--app-font-ui)', 'Pills, meta lines, labels, the excerpt of a card: half a step below the text' ),
	array( 'var(--app-u1)', 'var(--app-font-mono)', 'Text, the titles of the cards and of the sections' ),
	array( 'var(--app-font-large)', 'var(--app-font-mono)', 'The year, the title of a book, the name of a person, the title of the page on a phone: half a step above' ),
	array( 'calc(var(--app-u2) * sqrt(var(--app-phi)))', 'var(--app-font-mono)', 'The title of the page from 42rem: half a step above --app-u2' ),
);
?>
<library-styleguide lang="en">
	<h2 class="library-title">Styleguide</h2>
	<p class="library-styleguide__intro">The tokens and the components of the app, each in a frame with its own markup alone: a component should look the same here as in the app.</p>

	<section class="library-section">
		<h3 class="library-title">Tokens</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption>Colors</figcaption>
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
				<figcaption>Scale, by the golden ratio</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( $steps as $step ) : ?>
						<li>
							<span class="library-styleguide__step" style="--library-token: var(<?php echo esc_attr( $step ); ?>)"></span>
							<code><?php echo esc_html( $step ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>Sizes of the text, at this width, on lines of <code>--app-line-height</code> (φ)</figcaption>
				<ul class="library-styleguide__sizes">
					<?php foreach ( $sizes as list( $size, $family, $use ) ) : ?>
						<li>
							<span class="library-styleguide__size" style="--library-token: <?php echo esc_attr( $size ); ?>; --library-family: <?php echo esc_attr( $family ); ?>">Reading 2026</span>
							<span class="library-styleguide__use"><code><?php echo esc_html( $size ); ?></code> · <span data-size></span> · <?php echo esc_html( $use ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
			<figure>
				<figcaption>Fonts</figcaption>
				<ul class="library-styleguide__tokens">
					<?php foreach ( $fonts as $font ) : ?>
						<li>
							<span class="library-styleguide__font" style="--library-token: var(<?php echo esc_attr( $font ); ?>)">Aa 0123</span>
							<code><?php echo esc_html( $font ); ?></code>
						</li>
					<?php endforeach; ?>
				</ul>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Text</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption><code>.library-title</code></figcaption>
				<div class="library-styleguide__frame">
					<h4 class="library-title">
						A section title
						<span class="app-meta">12 books</span>
						<a class="app-meta library-push-end" href="#top">A link</a>
					</h4>
				</div>
			</figure>
			<figure>
				<figcaption><code>.app-status</code>, such as no book found, or the app offline</figcaption>
				<div class="library-styleguide__frame">
					<p class="app-status">A message, such as no book found.</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-notice</code>, what an action done says, such as a reading saved, with its action Undo, at the bottom of the screen in the app</figcaption>
				<div class="library-styleguide__frame">
					<p class="library-notice app-pill"><span>Reading saved.</span> <button class="library-link" type="button">Undo</button></p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-year</code></figcaption>
				<div class="library-styleguide__frame">
					<div class="library-year">
						<p>103 read in 2026, 75 by this date last year</p>
						<p>About 125 by the end of the year at this pace</p>
						<p>571 books to read, all read in 2031 at this pace</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-person</code>, with <code>__description</code></figcaption>
				<div class="library-styleguide__frame">
					<header class="library-person">
						<h4 class="library-person__name">A Person</h4>
						<p>Other names: A Pseudonym, A Birth Name</p>
						<div class="library-person__description">
							<p>Their description, as written in the admin.</p>
							<p>A second paragraph, with <em>a word in italics</em>.</p>
						</div>
						<p>12 books, 8 read, rated 7.5/10 on average</p>
					</header>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-section__pills</code>, the series of a cycle or the collections of a publisher on its page, under its <code>.library-person</code> header</figcaption>
				<div class="library-styleguide__frame">
					<header class="library-person">
						<h4 class="library-person__name">A Cycle</h4>
						<p>20 books · 14 read</p>
					</header>
					<ul class="library-section__pills">
						<li><a class="app-pill" href="#top">A Series</a></li>
						<li><a class="app-pill" href="#top">Another Series, with a longer name</a></li>
					</ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-idea__reason</code>, <code>.library-idea__actions</code></figcaption>
				<div class="library-styleguide__frame">
					<p class="library-idea__reason">Added 2 weeks ago.</p>
					<p class="library-idea__actions">
						<button class="app-pill" type="button">Another idea</button>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-book__*</code>, the book open: its cover, its meta line, its title, its excerpt, its notes, its translation, its fields, its actions</figcaption>
				<div class="library-styleguide__frame">
					<img class="library-book__cover" alt="" loading="lazy" hidden>
					<p class="app-meta library-book__meta">An Author · A Publisher, A Collection · 2024</p>
					<h4 class="library-book__title">The title of a book</h4>
					<p class="library-book__excerpt">Its excerpt, as written on its back cover.</p>
					<div class="library-book__content">
						<p>Its notes, written in its form.</p>
						<p>A second paragraph.</p>
					</div>
					<p class="library-book__translation">Translated from Italian by A Translator</p>
					<dl class="library-book__fields">
						<div>
							<dt class="app-meta">ISBN</dt>
							<dd>978-2-07-036024-6</dd>
						</div>
						<div>
							<dt class="app-meta">Pages</dt>
							<dd>320</dd>
						</div>
					</dl>
					<p>
						<button class="app-pill" type="button">Edit book</button>
					</p>
					<p class="app-meta">
						<a href="#top">Edit in the dashboard</a>
					</p>
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Controls</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption><code>.app-pill</code>: a link, the current one, buttons, a checkbox with <code>pill-checkbox</code>, a select with <code>pill-select</code>, a count; the main action of a form, <code>.app-pill--primary</code></figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<a class="app-pill" href="#top">A link</a>
					<a class="app-pill" href="#top" aria-current="page">The current one</a>
					<button class="app-pill" type="button">A button</button>
					<button class="app-pill" type="button" disabled>Disabled</button>
					<?php
					Markup::render_component(
						'pill-checkbox',
						array(
							'id'    => 'library-styleguide-pill-checkbox',
							'label' => 'A checkbox',
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
					<p class="app-pill">A count <strong>12</strong></p>
					<button class="app-pill app-pill--primary" type="button">Save</button>
				</div>
			</figure>
			<figure>
				<figcaption><code>.app-control</code>, the controls of the toolbar and of a row: add, finished, the theme in light then in dark, close</figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<button class="app-control" type="button" aria-label="Add">+</button>
					<button class="app-control" type="button" aria-label="Finished">✓&#xFE0E;</button>
					<button class="app-control" type="button" aria-label="Dark theme" aria-pressed="false">☽&#xFE0E;</button>
					<button class="app-control" type="button" aria-label="Dark theme" aria-pressed="true">☀&#xFE0E;</button>
					<button class="app-control" type="button" aria-label="Close">×</button>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-nav</code>, the views, a row of pills that scrolls sideways: under the title, or on a phone in a bar at the bottom of the screen</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-nav">
						<li><a class="app-pill" href="#top" aria-current="page">Books</a></li>
						<li><a class="app-pill" href="#top">Diary</a></li>
						<li><a class="app-pill" href="#top">Idea</a></li>
						<li><a class="app-pill" href="#top">Achievements</a></li>
						<li><a class="app-pill" href="#top">To buy</a></li>
					</ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-back</code>, above the page of a book, of a person or of a series</figcaption>
				<div class="library-styleguide__frame">
					<p class="library-back">
						<button class="app-pill" type="button"><span aria-hidden="true">←</span> Back</button>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.app-search</code></figcaption>
				<div class="library-styleguide__frame">
					<div class="app-search">
						<input type="search" placeholder="Title, author, series, ISBN…" aria-label="A search">
					</div>
				</div>
			</figure>
			<figure>
				<figcaption><code>.app-controls</code>: the search on its own line, its keywords under it while it is used; under the title of the books, the button of the filters, the order and the filters chosen, which remove them</figcaption>
				<div class="library-styleguide__frame">
					<div class="app-controls">
						<div class="app-search">
							<input type="search" placeholder="Title, author, series, ISBN…" aria-label="Another search">
							<p class="library-search__keywords">
								<button class="app-pill" type="button">Series</button>
								<button class="app-pill" type="button">Author</button>
								<button class="app-pill" type="button">Publisher</button>
								<button class="app-pill" type="button">Read in 2026</button>
								<button class="app-pill" type="button">Rated 9 or more</button>
							</p>
						</div>
					</div>
					<h4 class="library-title library-books__title">
						Books
						<span class="app-meta">15 books</span>
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
						<p class="library-filters__chosen">
							<button class="app-pill" type="button">To read <span aria-hidden="true">×</span></button>
							<button class="app-pill" type="button">A form <span aria-hidden="true">×</span></button>
						</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-filters__dialog</code>, the filters, a sheet from the bottom of the screen on a phone, a dialog beyond, drawn here in its place</figcaption>
				<div class="library-styleguide__frame">
					<dialog class="library-filters__dialog" open>
						<p class="app-toolbar">
							<button class="app-control" type="button" aria-label="Close">×</button>
						</p>
						<h4 class="library-book__title">Filters</h4>
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
				<figcaption><code>.library-reading__pills</code></figcaption>
				<div class="library-styleguide__frame">
					<div class="library-reading__pills">
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
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Lists</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption><code>.app-item</code>, the card of the apps</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items">
						<li class="app-item">
							<p class="app-item__meta">A meta line · 2026</p>
							<p class="app-item__text">The text of an item, kept as it was written,
with its line breaks.</p>
						</li>
					</ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-item</code>: with a cover, without, with an excerpt and a form, not kept with a form in its original language, to buy, long, bare</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items" data-sample="books"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-current</code>, a book being read, as the home shows it: with a cover, not kept without, with a long title, each with its button Finished over its cover</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="current"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-row</code>, a book as the reading idea on the home shows it: with a cover, not kept without, with a long title</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items" data-sample="rows"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-section__more</code>: past four cards, the others folded under a pill, as the authors of a series and the series of a person</figcaption>
				<div class="library-styleguide__frame">
					<section class="library-section" data-folded="authors">
						<ul class="app-list__items" data-list="shown"></ul>
						<details class="library-section__more" data-row="more" hidden>
							<summary class="app-pill" data-field="more"></summary>
							<ul class="app-list__items" data-list="more"></ul>
						</details>
					</section>
				</div>
			</figure>
			<figure>
				<figcaption><code>#library-month</code>, a month of the diary</figcaption>
				<div class="library-styleguide__frame" data-sample="month"></div>
			</figure>
			<figure>
				<figcaption><code>.library-cover</code>: with a cover, without, with a long title, as the latest readings show them</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="covers"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-cover</code>, the volumes of a series: read, being read, to read, which only assistive technologies hear, the book open framed</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="volumes"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-term</code>, the authors of a series, the series of a person: with a description, clamped, without</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items" data-sample="terms"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-achievement</code>, earned</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items" data-sample="achievements"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-achievement</code>, close to it</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items" data-sample="close"></ul>
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Forms</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption><code>field</code>: a text with a description, a select, a textarea, disabled</figcaption>
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
				<figcaption><code>field</code>: required, in error with <code>.library-error</code>; pointed at and focused, live</figcaption>
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
				<figcaption><code>.app-status[role="alert"]</code>, an error of the server at the end of a form</figcaption>
				<div class="library-styleguide__frame">
					<p class="app-status" role="alert">The book could not be saved.</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>image-field</code>, with an image</figcaption>
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
				<figcaption><code>input-list</code>, the people of a book</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'input-list',
						array(
							'legend' => 'People',
							'add'    => 'Add a person',
							'action' => 'add-person',
							'list'   => array( 'data-sample' => 'people' ),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption><code>input-list</code>, the read dates of a book, with a description, the first one unknown, the last one in error</figcaption>
				<div class="library-styleguide__frame">
					<?php
					Markup::render_component(
						'input-list',
						array(
							'id'          => 'library-styleguide-dates',
							'legend'      => 'Read dates',
							'description' => 'One date per reading; a reading whose date is unknown has none.',
							'add'         => 'Add a reading',
							'action'      => 'add',
							'actions'     => array( 'add-unknown' => 'Add a reading with an unknown date' ),
							'list'        => array( 'data-sample' => 'dates' ),
						)
					);
					?>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-edit__duplicate</code>, under the ISBN of a book to add that a book has already; a book to buy is bought from there</figcaption>
				<div class="library-styleguide__frame">
					<p class="library-edit__duplicate">
						<span>Already added: <a href="#top">A title</a> <button class="app-pill" type="button" data-action="buy">I bought it</button></span>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-edit__details</code></figcaption>
				<div class="library-styleguide__frame">
					<details class="library-edit__details">
						<summary class="app-meta">More details</summary>
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
		</div>
	</section>
</library-styleguide>
