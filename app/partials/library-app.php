<?php
/**
 * The reading app page: the books with the home above them, the diary, the reading idea, the achievements, the page of an author,
 * and the forms of a book open and of a book to add.
 *
 * The bricks (custom elements) fill the markup from the store; the templates hold the translated labels.
 * The view asked for in the URL shows from the first paint; the store then switches views without reloading.
 *
 * @link       https://github.com/19h47/library
 * @since      4.4.0
 *
 * @package    Library
 * @subpackage Library/app/partials
 *
 * @var string                                        $manifest_url   URL of the web app manifest.
 * @var string                                        $favicon_url    URL of the icon.
 * @var string                                        $touch_icon_url URL of the home screen icon of an iPhone, which takes no SVG.
 * @var object                                        $labels         Labels of the book post type.
 * @var object                                        $author_labels    Labels of the authors taxonomy.
 * @var object                                        $publisher_labels Labels of the publishers taxonomy.
 * @var object                                        $language_labels  Labels of the original languages taxonomy.
 * @var array<string,string>                          $ratings        Ratings a book can take, by value, with their label.
 * @var array<string,array{label:string,url:string}> $views          Views of the app.
 * @var string                                        $view           View shown.
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="color-scheme" content="light dark">
		<meta name="theme-color" content="#ffffff">
		<title><?php esc_html_e( 'Library', 'library' ); ?></title>
		<link rel="manifest" href="<?php echo esc_url( $manifest_url ); ?>" crossorigin="use-credentials">
		<link rel="icon" type="image/svg+xml" href="<?php echo esc_url( $favicon_url ); ?>">
		<link rel="apple-touch-icon" href="<?php echo esc_url( $touch_icon_url ); ?>">
		<?php wp_print_styles( 'library-app' ); ?>
		<?php wp_print_scripts( 'library-app-store' ); ?>
	</head>
	<body>
		<main class="app-page" id="top">
			<div class="app-content">
				<header class="app-header">
					<h1><?php esc_html_e( 'Library', 'library' ); ?></h1>
					<library-nav>
						<nav>
							<ul class="library-nav">
								<?php foreach ( $views as $name => $item ) : ?>
									<li>
										<a
											class="app-pill"
											href="<?php echo esc_url( $item['url'] ); ?>"
											data-view="<?php echo esc_attr( $name ); ?>"
											<?php echo $name === $view ? 'aria-current="page"' : ''; ?>
										><?php echo esc_html( $item['label'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</nav>
					</library-nav>
				</header>

				<library-view name="books" <?php echo 'books' === $view ? '' : 'hidden'; ?>>
					<h2 class="sr-only"><?php echo esc_html( $labels->name ); ?></h2>
					<div class="app-controls">
						<library-search class="app-search">
							<label class="sr-only" for="library-search"><?php esc_html_e( 'Search the library', 'library' ); ?></label>
							<input
								id="library-search"
								type="search"
								placeholder="<?php esc_attr_e( 'Title, author, series, ISBN…', 'library' ); ?>"
								autocomplete="off"
								enterkeyhint="search"
							>
						</library-search>
						<library-count class="app-pill" role="status"><?php esc_html_e( 'Loading…', 'library' ); ?></library-count>
						<library-read-filter>
							<label class="app-pill">
								<span class="sr-only"><?php esc_html_e( 'Reading state', 'library' ); ?></span>
								<select>
									<option value=""><?php echo esc_html( $labels->all_items ); ?></option>
									<option value="yes"><?php esc_html_e( 'Read', 'library' ); ?></option>
									<option value="no"><?php esc_html_e( 'To read', 'library' ); ?></option>
								</select>
							</label>
						</library-read-filter>
					</div>

					<library-home>
						<library-year class="library-year">
							<p data-field="yearToDate"></p>
							<p data-field="projection"></p>
						</library-year>
						<library-latest class="library-section" hidden>
							<h3 class="library-title">
								<?php esc_html_e( 'Latest readings', 'library' ); ?>
								<a class="library-title__link" href="<?php echo esc_url( $views['diary']['url'] ); ?>" data-view="diary"><?php echo esc_html( $views['diary']['label'] ); ?></a>
							</h3>
							<ul class="library-covers"></ul>
						</library-latest>
						<library-idea class="library-section" hidden>
							<h3 class="library-title"><?php esc_html_e( 'Reading idea of the week', 'library' ); ?></h3>
							<p class="app-status" data-field="status" hidden></p>
							<p class="library-idea__reason" data-field="reason"></p>
							<ul class="app-list__items" data-field="book"></ul>
							<p class="library-idea__actions">
								<button class="app-pill" type="button"><?php esc_html_e( 'Another idea', 'library' ); ?></button>
							</p>
						</library-idea>
						<library-close class="library-section" hidden>
							<h3 class="library-title">
								<?php esc_html_e( 'Close to it', 'library' ); ?>
								<a class="library-title__link" href="<?php echo esc_url( $views['achievements']['url'] ); ?>" data-view="achievements"><?php echo esc_html( $views['achievements']['label'] ); ?></a>
							</h3>
							<ul class="app-list__items"></ul>
						</library-close>
						<h3 class="library-title library-section"><?php echo esc_html( $labels->name ); ?></h3>
					</library-home>

					<library-status class="app-status" hidden></library-status>
					<library-list>
						<ul class="app-list__items"></ul>
						<div class="library-list__more"></div>
					</library-list>
				</library-view>

				<library-view name="diary" <?php echo 'diary' === $view ? '' : 'hidden'; ?>>
					<library-diary>
						<p class="app-status" data-field="status" hidden></p>
						<div class="library-diary__months"></div>
						<div class="library-list__more"></div>
					</library-diary>
				</library-view>

				<library-view name="idea" <?php echo 'idea' === $view ? '' : 'hidden'; ?>>
					<library-idea>
						<h2 class="library-title"><?php esc_html_e( 'Reading idea of the week', 'library' ); ?></h2>
						<p class="app-status" data-field="status" hidden></p>
						<p class="library-idea__reason" data-field="reason"></p>
						<ul class="app-list__items" data-field="book"></ul>
						<p class="library-idea__actions">
							<button class="app-pill" type="button"><?php esc_html_e( 'Another idea', 'library' ); ?></button>
						</p>
					</library-idea>
				</library-view>

				<library-view name="achievements" <?php echo 'achievements' === $view ? '' : 'hidden'; ?>>
					<library-year class="library-year">
						<p data-field="summary"></p>
						<p data-field="yearToDate"></p>
						<p data-field="projection"></p>
					</library-year>
					<library-achievements>
						<p class="app-status" data-field="status" hidden></p>
						<section class="library-section" data-row="close">
							<h2 class="library-title">
								<?php esc_html_e( 'Close to it', 'library' ); ?>
								<span class="library-title__count" data-field="closeCount"></span>
							</h2>
							<ul class="app-list__items" data-list="close"></ul>
						</section>
						<section class="library-section" data-row="earned">
							<h2 class="library-title">
								<?php esc_html_e( 'Achievements', 'library' ); ?>
								<span class="library-title__count" data-field="earnedCount"></span>
							</h2>
							<ul class="app-list__items" data-list="earned"></ul>
						</section>
					</library-achievements>
				</library-view>

				<library-view name="author" <?php echo Library_App::AUTHOR_VIEW === $view ? '' : 'hidden'; ?>>
					<library-author>
						<p class="app-status" data-field="status" hidden></p>
						<header class="library-author">
							<h2 class="library-author__name" data-field="name"></h2>
							<p data-field="pseudonyms"></p>
							<p data-field="summary"></p>
						</header>
						<ul class="app-list__items library-author__completion" data-list="completion" hidden></ul>
						<ul class="app-list__items" data-list="books"></ul>
						<div class="library-list__more"></div>
					</library-author>
				</library-view>
			</div>

			<footer class="app-footer">
				<library-progress class="app-progress"></library-progress>
				<a href="#top"><?php esc_html_e( 'Top', 'library' ); ?></a>
			</footer>
		</main>

		<div class="app-toolbar">
			<library-add-toggle>
				<button
					type="button"
					aria-label="<?php echo esc_attr( $labels->add_new_item ); ?>"
					title="<?php echo esc_attr( $labels->add_new_item ); ?>"
				>+</button>
			</library-add-toggle>
			<a
				href="<?php echo esc_url( admin_url() ); ?>"
				aria-label="<?php esc_attr_e( 'Dashboard', 'library' ); ?>"
				title="<?php esc_attr_e( 'Dashboard', 'library' ); ?>"
			>⌂&#xFE0E;</a>
			<library-theme-toggle>
				<button
					type="button"
					aria-label="<?php esc_attr_e( 'Dark theme', 'library' ); ?>"
					title="<?php esc_attr_e( 'Light or dark theme', 'library' ); ?>"
				></button>
			</library-theme-toggle>
		</div>

		<library-book>
			<dialog class="library-book" aria-labelledby="library-book-title">
				<form class="app-toolbar" method="dialog">
					<button type="submit" aria-label="<?php esc_attr_e( 'Close', 'library' ); ?>" title="<?php esc_attr_e( 'Close', 'library' ); ?>">×</button>
				</form>
				<img class="library-book__cover" data-field="cover" alt="" hidden>
				<p class="library-book__meta" data-field="credits"></p>
				<h2 class="library-book__title" id="library-book-title" data-field="title"></h2>
				<library-reading>
					<form class="library-reading">
						<div class="library-reading__pills">
							<label class="app-pill">
								<input type="checkbox" name="read">
								<?php esc_html_e( 'Read', 'library' ); ?>
							</label>
							<label class="app-pill">
								<span class="sr-only"><?php esc_html_e( 'Rating', 'library' ); ?></span>
								<select name="rating">
									<option value=""><?php esc_html_e( 'No rating', 'library' ); ?></option>
									<?php foreach ( $ratings as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="app-pill">
								<input type="checkbox" name="not_kept">
								<?php echo esc_html_x( 'Not kept', 'book status', 'library' ); ?>
							</label>
						</div>
						<fieldset class="library-reading__dates" hidden disabled>
							<legend><?php esc_html_e( 'Read dates', 'library' ); ?></legend>
							<ul></ul>
							<p>
								<button class="app-pill" type="button" data-action="add"><?php esc_html_e( 'Add a reading', 'library' ); ?></button>
							</p>
						</fieldset>
						<p class="app-status" data-field="error" role="alert" hidden></p>
						<p>
							<button class="app-pill library-save" type="submit"><?php esc_html_e( 'Save', 'library' ); ?></button>
						</p>
					</form>
				</library-reading>
				<dl class="library-book__fields">
					<div data-row="authors">
						<dt><?php echo esc_html( $author_labels->name ); ?></dt>
						<dd data-field="authors"></dd>
					</div>
					<div data-row="isbn">
						<dt><?php esc_html_e( 'ISBN', 'library' ); ?></dt>
						<dd data-field="isbn"></dd>
					</div>
					<div data-row="pages">
						<dt><?php esc_html_e( 'Pages', 'library' ); ?></dt>
						<dd data-field="pages"></dd>
					</div>
					<div data-row="edition_date">
						<dt><?php esc_html_e( 'Edition date', 'library' ); ?></dt>
						<dd data-field="edition_date"></dd>
					</div>
					<div data-row="published_date">
						<dt><?php esc_html_e( 'First published', 'library' ); ?></dt>
						<dd data-field="published_date"></dd>
					</div>
					<div data-row="translators">
						<dt><?php esc_html_e( 'Translators', 'library' ); ?></dt>
						<dd data-field="translators"></dd>
					</div>
					<div data-row="languages">
						<dt><?php echo esc_html( $language_labels->name ); ?></dt>
						<dd data-field="languages"></dd>
					</div>
				</dl>
				<p class="library-book__actions">
					<a data-field="edit" href=""><?php echo esc_html( $labels->edit_item ); ?></a>
				</p>
			</dialog>
		</library-book>

		<library-add>
			<dialog class="library-book library-add" aria-labelledby="library-add-heading">
				<form class="app-toolbar" method="dialog">
					<button type="submit" aria-label="<?php esc_attr_e( 'Close', 'library' ); ?>" title="<?php esc_attr_e( 'Close', 'library' ); ?>">×</button>
				</form>
				<h2 class="library-book__title" id="library-add-heading"><?php echo esc_html( $labels->add_new_item ); ?></h2>
				<form class="library-add__form">
					<div class="library-add__cover">
						<img class="library-add__preview" alt="" hidden>
						<label class="app-pill">
							<input class="sr-only" type="file" name="cover" accept="image/*">
							<?php esc_html_e( 'Add a cover', 'library' ); ?>
						</label>
					</div>
					<p class="library-field">
						<label for="library-add-title"><?php esc_html_e( 'Title', 'library' ); ?></label>
						<input id="library-add-title" name="title" required autocomplete="off">
					</p>
					<fieldset class="library-field">
						<legend><?php echo esc_html( $author_labels->name ); ?></legend>
						<ul class="library-add__authors" data-list="authors"></ul>
						<p>
							<button class="app-pill" type="button" data-action="add-author"><?php esc_html_e( 'Add an author', 'library' ); ?></button>
						</p>
					</fieldset>
					<p class="library-field">
						<label for="library-add-publisher"><?php echo esc_html( $publisher_labels->singular_name ); ?></label>
						<input id="library-add-publisher" name="publisher" list="library-add-publishers" autocomplete="off">
					</p>
					<p class="library-field">
						<label for="library-add-collection"><?php esc_html_e( 'Collection', 'library' ); ?></label>
						<input id="library-add-collection" name="collection" list="library-add-collections" autocomplete="off" disabled>
					</p>
					<p class="library-field">
						<label for="library-add-isbn"><?php esc_html_e( 'ISBN', 'library' ); ?></label>
						<input id="library-add-isbn" name="isbn" data-meta pattern="<?php echo esc_attr( Library_Meta::ISBN_PATTERN ); ?>" inputmode="numeric" autocomplete="off">
					</p>
					<details class="library-add__details">
						<summary><?php esc_html_e( 'More details', 'library' ); ?></summary>
						<p class="library-field">
							<label for="library-add-subtitle"><?php esc_html_e( 'Subtitle', 'library' ); ?></label>
							<input id="library-add-subtitle" name="subtitle" data-meta autocomplete="off">
						</p>
						<p class="library-field">
							<label for="library-add-series"><?php esc_html_e( 'Series', 'library' ); ?></label>
							<input id="library-add-series" name="series" data-meta autocomplete="off" aria-describedby="library-add-series-description">
							<small id="library-add-series-description"><?php esc_html_e( 'Separate series with commas', 'library' ); ?></small>
						</p>
						<p class="library-field">
							<label for="library-add-volume-number"><?php esc_html_e( 'Volume Number', 'library' ); ?></label>
							<input id="library-add-volume-number" name="volume_number" data-meta autocomplete="off">
						</p>
						<p class="library-field">
							<label for="library-add-issn"><?php esc_html_e( 'ISSN', 'library' ); ?></label>
							<input id="library-add-issn" name="issn" data-meta pattern="<?php echo esc_attr( Library_Meta::ISSN_PATTERN ); ?>" autocomplete="off">
						</p>
						<p class="library-field">
							<label for="library-add-pages"><?php esc_html_e( 'Pages', 'library' ); ?></label>
							<input id="library-add-pages" name="pages" data-meta type="number" min="1" inputmode="numeric">
						</p>
						<p class="library-field">
							<label for="library-add-edition-date"><?php esc_html_e( 'Edition date', 'library' ); ?></label>
							<input id="library-add-edition-date" name="edition_date" data-meta type="date">
						</p>
						<p class="library-field">
							<label for="library-add-published-date"><?php esc_html_e( 'First published', 'library' ); ?></label>
							<input
								id="library-add-published-date"
								name="published_date"
								data-meta
								inputmode="numeric"
								pattern="\d{4}(-\d{2}(-\d{2})?)?"
								autocomplete="off"
								aria-describedby="library-add-published-date-description"
							>
							<small id="library-add-published-date-description"><?php esc_html_e( 'Optional. When the work first came out, whatever the edition: a year, a year and month, or a date, such as 1929, 1929-11 or 1929-11-08.', 'library' ); ?></small>
						</p>
						<p class="library-field">
							<label for="library-add-translators"><?php esc_html_e( 'Translators', 'library' ); ?></label>
							<input id="library-add-translators" name="translators" data-meta autocomplete="off" aria-describedby="library-add-translators-description">
							<small id="library-add-translators-description"><?php esc_html_e( 'Separate translators with commas', 'library' ); ?></small>
						</p>
						<p class="library-field">
							<label for="library-add-language"><?php echo esc_html( $language_labels->singular_name ); ?></label>
							<select id="library-add-language" name="language">
								<option value=""><?php echo esc_html( $language_labels->no_terms ); ?></option>
							</select>
						</p>
					</details>
					<p class="app-status" data-field="error" role="alert" hidden></p>
					<p>
						<button class="app-pill library-save" type="submit"><?php esc_html_e( 'Save', 'library' ); ?></button>
					</p>
				</form>
				<datalist id="library-add-authors"></datalist>
				<datalist id="library-add-publishers"></datalist>
				<datalist id="library-add-collections"></datalist>
			</dialog>
		</library-add>

		<template id="library-item">
			<li class="app-item library-item">
				<img class="library-item__cover" data-field="cover" alt="" loading="lazy" hidden>
				<div class="library-item__body">
					<p class="app-item__meta" data-field="credits"></p>
					<h3 class="library-item__title">
						<button class="library-item__button" type="button" data-field="title"></button>
					</h3>
					<p class="app-item__meta" data-field="state"></p>
				</div>
			</li>
		</template>

		<template id="library-read-date">
			<li class="library-reading__date">
				<input type="date" name="read_date" required aria-label="<?php esc_attr_e( 'Read date', 'library' ); ?>">
				<button
					class="app-pill"
					type="button"
					data-action="remove"
					aria-label="<?php esc_attr_e( 'Remove this reading', 'library' ); ?>"
					title="<?php esc_attr_e( 'Remove this reading', 'library' ); ?>"
				>×</button>
			</li>
		</template>

		<template id="library-cover">
			<li class="library-cover">
				<button class="library-cover__button" type="button">
					<img class="library-cover__image" data-field="cover" alt="" loading="lazy" hidden>
					<span class="library-cover__title" data-field="title"></span>
				</button>
			</li>
		</template>

		<template id="library-add-author">
			<li class="library-add__author">
				<input name="author" list="library-add-authors" autocomplete="off" aria-label="<?php echo esc_attr( $author_labels->singular_name ); ?>">
				<button
					class="app-pill"
					type="button"
					data-action="remove"
					aria-label="<?php esc_attr_e( 'Remove this author', 'library' ); ?>"
					title="<?php esc_attr_e( 'Remove this author', 'library' ); ?>"
				>×</button>
			</li>
		</template>

		<template id="library-month">
			<section class="library-section">
				<h2 class="library-title">
					<span data-field="month"></span>
					<span class="library-title__count" data-field="count"></span>
				</h2>
				<ul class="app-list__items"></ul>
			</section>
		</template>

		<template id="library-achievement">
			<li class="app-item library-achievement">
				<p data-field="title"></p>
				<p data-field="subject"></p>
				<p class="app-item__meta" data-field="meta"></p>
			</li>
		</template>

		<template id="library-achievement-close">
			<li class="app-item library-achievement">
				<p data-field="name"></p>
				<p class="app-item__meta" data-field="meta"></p>
				<p class="library-achievement__progress">
					<progress></progress>
					<span class="app-item__meta" data-field="count" aria-hidden="true"></span>
				</p>
			</li>
		</template>

		<?php wp_print_scripts( 'library-app' ); ?>
	</body>
</html>
