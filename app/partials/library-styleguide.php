<?php
/**
 * The styleguide: the tokens and the components of the app, for development, opened by searching "styleguide".
 *
 * Each component shows with its own markup alone, in a frame: one that needs its place in the app to look right
 * shows it here. The [data-sample] lists are filled by library-styleguide, with the functions
 * the app renders with. Written in English, as code is: nothing here is translated.
 *
 * @link       https://github.com/19h47/library
 * @since      6.8.0
 *
 * @package    Library
 * @subpackage Library/app/partials
 */

$colors = array( '--app-bg', '--app-text', '--app-text-muted', '--app-line', '--app-line-soft', '--app-fill', '--library-status', '--library-error', '--library-success', '--library-warning' );
$steps  = array( '--app-u0', '--app-u1', '--app-u2', '--app-u3' );
$fonts  = array( '--app-font-mono', '--app-font-ui' );
// The sizes of the text, as the styles write them, from the smallest: their family, and what they set.
$sizes = array(
	array( 'var(--app-font-small)', 'var(--app-font-ui)', 'Pills, meta lines, labels, the excerpt of a card: half a step below the text' ),
	array( 'var(--app-u1)', 'var(--app-font-mono)', 'Text, the titles of the cards and of the sections' ),
	array( 'var(--app-font-large)', 'var(--app-font-mono)', 'The year, the title of a book, the name of an author, the title of the page on a phone: half a step above' ),
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
						<span class="library-title__count">12 books</span>
						<a class="library-title__link" href="#top">A link</a>
					</h4>
				</div>
			</figure>
			<figure>
				<figcaption><code>.app-status</code></figcaption>
				<div class="library-styleguide__frame">
					<p class="app-status">A message, such as no book found.</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-year</code></figcaption>
				<div class="library-styleguide__frame">
					<div class="library-year">
						<p>103 read in 2026, 75 by this date last year</p>
						<p>About 125 by the end of the year at this pace</p>
					</div>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-author</code></figcaption>
				<div class="library-styleguide__frame">
					<header class="library-author">
						<h4 class="library-author__name">An Author</h4>
						<p>Also known as A Pseudonym</p>
						<p>12 books, 8 read, rated 7.5/10 on average</p>
					</header>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-book__*</code>, the book open</figcaption>
				<div class="library-styleguide__frame library-styleguide__stack">
					<p class="library-book__meta">An Author · A Publisher, A Collection · 2024</p>
					<h4 class="library-book__title">The title of a book</h4>
					<p class="library-book__excerpt">Its excerpt, as written on its back cover.</p>
					<p class="library-book__translation">Translated from Italian by A Translator</p>
					<dl class="library-book__fields">
						<div>
							<dt>ISBN</dt>
							<dd>978-2-07-036024-5</dd>
						</div>
						<div>
							<dt>Pages</dt>
							<dd>320</dd>
						</div>
					</dl>
				</div>
			</figure>
		</div>
	</section>

	<section class="library-section">
		<h3 class="library-title">Controls</h3>
		<div class="library-styleguide__samples">
			<figure>
				<figcaption><code>.app-pill</code>: a link, buttons, a checkbox, a select, a count; <code>.library-save</code></figcaption>
				<div class="library-styleguide__frame library-styleguide__row">
					<a class="app-pill" href="#top">A link</a>
					<button class="app-pill" type="button">A button</button>
					<button class="app-pill" type="button" disabled>Disabled</button>
					<label class="app-pill" for="library-styleguide-pill-checkbox">
						<input id="library-styleguide-pill-checkbox" type="checkbox">
						A checkbox
					</label>
					<label class="app-pill" for="library-styleguide-pill-select">
						<span class="sr-only">A choice</span>
						<select id="library-styleguide-pill-select">
							<option>A select</option>
							<option>Another choice, longer</option>
						</select>
					</label>
					<p class="app-pill">A count <strong>12</strong></p>
					<button class="app-pill library-save" type="button">Save</button>
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
				<figcaption><code>.library-reading__pills</code></figcaption>
				<div class="library-styleguide__frame">
					<div class="library-reading__pills">
						<label class="app-pill" for="library-styleguide-read">
							<input id="library-styleguide-read" type="checkbox" checked>
							Read
						</label>
						<label class="app-pill" for="library-styleguide-rating">
							<span class="sr-only">Rating</span>
							<select id="library-styleguide-rating">
								<option>8/10</option>
							</select>
						</label>
						<label class="app-pill" for="library-styleguide-status">
							<span class="sr-only">Library</span>
							<select id="library-styleguide-status">
								<option>In the library</option>
							</select>
						</label>
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
				<figcaption><code>.library-item</code>: with a cover, without, with an excerpt, not kept, to buy, long, bare</figcaption>
				<div class="library-styleguide__frame">
					<ul class="app-list__items" data-sample="books"></ul>
				</div>
			</figure>
			<figure>
				<figcaption><code>#library-month</code>, a month of the diary</figcaption>
				<div class="library-styleguide__frame" data-sample="month"></div>
			</figure>
			<figure>
				<figcaption><code>.library-cover</code>: with a cover, without, with a long title</figcaption>
				<div class="library-styleguide__frame">
					<ul class="library-styleguide__row" data-sample="covers"></ul>
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
				<figcaption><code>.library-field</code>: a text, a description, a select, a textarea, disabled</figcaption>
				<div class="library-styleguide__frame library-styleguide__stack">
					<p class="library-field">
						<label for="library-styleguide-text">A text</label>
						<input id="library-styleguide-text" aria-describedby="library-styleguide-text-description">
						<small id="library-styleguide-text-description">Its description, under it.</small>
					</p>
					<p class="library-field">
						<label for="library-styleguide-select">A select</label>
						<select id="library-styleguide-select">
							<option>A choice</option>
						</select>
					</p>
					<p class="library-field">
						<label for="library-styleguide-textarea">A textarea</label>
						<textarea id="library-styleguide-textarea" rows="3">It grows with its text.</textarea>
					</p>
					<p class="library-field">
						<label for="library-styleguide-disabled">Disabled</label>
						<input id="library-styleguide-disabled" disabled>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-field</code>: required, in error with <code>.library-error</code>; pointed at and focused, live</figcaption>
				<div class="library-styleguide__frame library-styleguide__stack">
					<p class="library-field">
						<label for="library-styleguide-required">A field (required)</label>
						<input id="library-styleguide-required" required>
					</p>
					<p class="library-field">
						<label for="library-styleguide-invalid">A field in error</label>
						<input id="library-styleguide-invalid" value="978-2" aria-describedby="library-styleguide-invalid-description" data-error="Its message, the one of the browser.">
						<small id="library-styleguide-invalid-description">Its description, under its message.</small>
					</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.app-status[role="alert"]</code>, an error of the server at the end of a form</figcaption>
				<div class="library-styleguide__frame">
					<p class="app-status" role="alert">The book could not be saved.</p>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-edit__authors</code>, a list of inputs</figcaption>
				<div class="library-styleguide__frame">
					<fieldset class="library-field">
						<legend>Authors</legend>
						<ul class="library-edit__authors" data-sample="authors"></ul>
						<p>
							<button class="app-pill" type="button">Add an author</button>
						</p>
					</fieldset>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-reading__dates</code>, the second one in error</figcaption>
				<div class="library-styleguide__frame">
					<fieldset class="library-reading__dates">
						<legend>Read dates (required)</legend>
						<ul data-sample="dates"></ul>
						<p>
							<button class="app-pill" type="button">Add a reading</button>
						</p>
					</fieldset>
				</div>
			</figure>
			<figure>
				<figcaption><code>.library-edit__details</code></figcaption>
				<div class="library-styleguide__frame">
					<details class="library-edit__details">
						<summary>More details</summary>
						<p class="library-field">
							<label for="library-styleguide-detail">A field</label>
							<input id="library-styleguide-detail">
						</p>
					</details>
				</div>
			</figure>
		</div>
	</section>
</library-styleguide>
