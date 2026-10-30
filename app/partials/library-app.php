<?php
/**
 * The reading app page: the books with the home above them, the diary, the reading idea, the achievements, the books to buy,
 * the page of a person, the styleguide, the book open with the form of its reading, and the form of a book, to add or edit.
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
 * @var object                                        $person_labels    Labels of the people taxonomy.
 * @var object                                        $publisher_labels Labels of the publishers taxonomy.
 * @var object                                        $language_labels  Labels of the original languages taxonomy.
 * @var object                                        $form_labels      Labels of the forms taxonomy.
 * @var array<int,string>                             $form_choices     Forms the filter of the books and the form of a book offer, by term ID.
 * @var array<string,string>                          $rating_choices Ratings a form offers, by value, with their label, none first.
 * @var array<string,string>                          $status_choices Statuses the forms of a book and the filter of the books offer, with their label.
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
		<?php
		// The bricks are script modules, run once the page is read: their translations and the import map first, then the modules and their preloads.
		wp_script_modules()->print_script_module_translations();
		wp_script_modules()->print_import_map();
		wp_script_modules()->print_enqueued_script_modules();
		wp_script_modules()->print_script_module_preloads();
		?>
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
							<?php
							Library::render_component(
								'pill-select',
								array(
									'id'      => 'library-read-filter',
									'label'   => __( 'Reading state', 'library' ),
									'options' => array(
										''    => $labels->all_items,
										'yes' => __( 'Read', 'library' ),
										'no'  => __( 'To read', 'library' ),
									),
								)
							);
							?>
						</library-read-filter>
						<library-status-filter>
							<?php
							Library::render_component(
								'pill-select',
								array(
									'id'      => 'library-status-filter',
									'label'   => __( 'Library', 'library' ),
									'options' => array( '' => _x( 'Everywhere', 'books of any status', 'library' ) ) + $status_choices,
								)
							);
							?>
						</library-status-filter>
						<?php if ( $form_choices ) : ?>
							<library-form-filter>
								<?php
								Library::render_component(
									'pill-select',
									array(
										'id'      => 'library-form-filter',
										'label'   => $form_labels->singular_name,
										'options' => array( '' => $form_labels->all_items ) + $form_choices,
									)
								);
								?>
							</library-form-filter>
						<?php endif; ?>
					</div>

					<library-home>
						<library-year class="library-year">
							<p data-field="yearToDate"></p>
							<p data-field="projection"></p>
							<p data-field="toRead"></p>
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
						<p data-field="toRead"></p>
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

				<library-view name="to-buy" <?php echo 'to-buy' === $view ? '' : 'hidden'; ?>>
					<h2 class="library-title">
						<?php echo esc_html( $views['to-buy']['label'] ); ?>
						<library-count class="library-title__count" list="toBuy"></library-count>
					</h2>
					<library-status class="app-status" list="toBuy" hidden></library-status>
					<library-list list="toBuy">
						<ul class="app-list__items"></ul>
						<div class="library-list__more"></div>
					</library-list>
				</library-view>

				<library-view name="person" <?php echo Library_App::PERSON_VIEW === $view ? '' : 'hidden'; ?>>
					<library-person>
						<p class="app-status" data-field="status" hidden></p>
						<header class="library-person">
							<h2 class="library-person__name" data-field="name"></h2>
							<p data-field="pseudonyms"></p>
							<div class="library-person__description" data-html="description" hidden></div>
							<p data-field="summary"></p>
						</header>
						<ul class="app-list__items library-person__completion" data-list="completion" hidden></ul>
						<ul class="app-list__items" data-list="books"></ul>
						<div class="library-list__more"></div>
					</library-person>
				</library-view>

				<library-view name="styleguide" <?php echo Library_App::STYLEGUIDE_VIEW === $view ? '' : 'hidden'; ?>>
					<?php require __DIR__ . '/library-styleguide.php'; ?>
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
					class="app-control"
					type="button"
					aria-label="<?php echo esc_attr( $labels->add_new_item ); ?>"
					title="<?php echo esc_attr( $labels->add_new_item ); ?>"
				>+</button>
			</library-add-toggle>
			<a
				class="app-control"
				href="<?php echo esc_url( admin_url() ); ?>"
				aria-label="<?php esc_attr_e( 'Dashboard', 'library' ); ?>"
				title="<?php esc_attr_e( 'Dashboard', 'library' ); ?>"
			>⌂&#xFE0E;</a>
			<library-theme-toggle>
				<button
					class="app-control"
					type="button"
					aria-label="<?php esc_attr_e( 'Dark theme', 'library' ); ?>"
					title="<?php esc_attr_e( 'Light or dark theme', 'library' ); ?>"
				></button>
			</library-theme-toggle>
		</div>

		<library-book>
			<dialog class="library-book" aria-labelledby="library-book-title">
				<form class="app-toolbar" method="dialog">
					<button class="app-control" type="submit" aria-label="<?php esc_attr_e( 'Close', 'library' ); ?>" title="<?php esc_attr_e( 'Close', 'library' ); ?>">×</button>
				</form>
				<img class="library-book__cover" data-field="cover" alt="" hidden>
				<p class="library-book__meta" data-field="credits"></p>
				<h2 class="library-book__title" id="library-book-title" data-field="title"></h2>
				<p class="library-book__excerpt" data-field="excerpt"></p>
				<div class="library-book__content" data-html="content" hidden></div>
				<p class="library-book__translation" data-field="translation"></p>
				<library-reading>
					<form class="library-reading">
						<div class="library-reading__pills">
							<?php
							Library::render_component(
								'pill-checkbox',
								array(
									'id'      => 'library-reading-read',
									'label'   => Library_Meta::get_label( 'library_read' ),
									'control' => Library_Meta::get_control( 'library_read' ),
								)
							);
							Library::render_component(
								'pill-select',
								array(
									'id'         => 'library-reading-rating',
									'label'      => Library_Meta::get_label( 'library_rating' ),
									'options'    => $rating_choices,
									'attributes' => array( 'name' => 'library_rating' ),
								)
							);
							Library::render_component(
								'pill-select',
								array(
									'id'         => 'library-reading-status',
									'label'      => __( 'Library', 'library' ),
									'options'    => $status_choices,
									'attributes' => array( 'name' => 'status' ),
								)
							);
							?>
						</div>
						<?php
						Library::render_component(
							'input-list',
							array(
								'class'      => 'library-reading__dates',
								'legend'     => Library_Meta::get_label( 'library_read_date' ),
								'required'   => true,
								'add'        => __( 'Add a reading', 'library' ),
								'action'     => 'add',
								'attributes' => array(
									'hidden'   => true,
									'disabled' => true,
								),
							)
						);
						?>
						<p class="app-status" data-field="error" role="alert" hidden></p>
						<p>
							<button class="app-pill app-pill--primary" type="submit"><?php esc_html_e( 'Save', 'library' ); ?></button>
						</p>
					</form>
				</library-reading>
				<dl class="library-book__fields">
					<div data-row="authors">
						<dt><?php echo esc_html( $person_labels->name ); ?></dt>
						<dd data-field="authors"></dd>
					</div>
					<?php // The translators are named by the translation line. ?>
					<?php foreach ( array_diff( Library_Meta::ROLE_KEYS, array( 'library_translators' ) ) as $key ) : ?>
						<?php $role_field = str_replace( 'library_', '', $key ); ?>
						<div data-row="<?php echo esc_attr( $role_field ); ?>">
							<dt><?php echo esc_html( Library_Meta::get_label( $key ) ); ?></dt>
							<dd data-field="<?php echo esc_attr( $role_field ); ?>"></dd>
						</div>
					<?php endforeach; ?>
					<div data-row="isbn">
						<dt><?php echo esc_html( Library_Meta::get_label( 'library_isbn' ) ); ?></dt>
						<dd data-field="isbn"></dd>
					</div>
					<div data-row="pages">
						<dt><?php echo esc_html( Library_Meta::get_label( 'library_pages' ) ); ?></dt>
						<dd data-field="pages"></dd>
					</div>
					<div data-row="edition_date">
						<dt><?php echo esc_html( Library_Meta::get_label( 'library_edition_date' ) ); ?></dt>
						<dd data-field="edition_date"></dd>
					</div>
					<div data-row="published_date">
						<dt><?php echo esc_html( Library_Meta::get_label( 'library_published_date' ) ); ?></dt>
						<dd data-field="published_date"></dd>
					</div>
				</dl>
				<p class="library-book__actions">
					<button class="app-pill" type="button" data-action="edit"><?php echo esc_html( $labels->edit_item ); ?></button>
				</p>
			</dialog>
		</library-book>

		<library-edit>
			<dialog class="library-book library-edit" aria-labelledby="library-edit-heading">
				<form class="app-toolbar" method="dialog">
					<button class="app-control" type="submit" aria-label="<?php esc_attr_e( 'Close', 'library' ); ?>" title="<?php esc_attr_e( 'Close', 'library' ); ?>">×</button>
				</form>
				<h2
					class="library-book__title"
					id="library-edit-heading"
					data-add="<?php echo esc_attr( $labels->add_new_item ); ?>"
					data-edit="<?php echo esc_attr( $labels->edit_item ); ?>"
				></h2>
				<form class="library-edit__form">
					<?php
					Library::render_component(
						'image-field',
						array(
							'id'     => 'library-edit-cover',
							'name'   => 'cover',
							'label'  => __( 'Choose a cover', 'library' ),
							'remove' => __( 'Remove the cover', 'library' ),
						)
					);
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-title',
							'label'      => __( 'Title', 'library' ),
							'required'   => true,
							'attributes' => array(
								'name'         => 'title',
								'autocomplete' => 'off',
							),
						)
					);
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-excerpt',
							'label'      => __( 'Excerpt', 'library' ),
							'control'    => '<textarea></textarea>',
							'attributes' => array(
								'name' => 'excerpt',
								'rows' => 3,
							),
						)
					);
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-content',
							'label'      => __( 'Notes', 'library' ),
							'control'    => '<textarea></textarea>',
							'attributes' => array(
								'name' => 'content',
								'rows' => 3,
							),
						)
					);
					Library::render_component(
						'input-list',
						array(
							'legend' => $person_labels->name,
							'add'    => __( 'Add a person', 'library' ),
							'action' => 'add-person',
							'list'   => array( 'data-list' => 'people' ),
						)
					);
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-publisher',
							'label'      => $publisher_labels->singular_name,
							'attributes' => array(
								'name'         => 'publisher',
								'list'         => 'library-edit-publishers',
								'autocomplete' => 'off',
							),
						)
					);
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-collection',
							'label'      => __( 'Collection', 'library' ),
							'attributes' => array(
								'name'         => 'collection',
								'list'         => 'library-edit-collections',
								'autocomplete' => 'off',
								'disabled'     => true,
							),
						)
					);
					// An ISBN takes hyphens, which a numeric keyboard may not have.
					Library::render_component( 'meta-field', array( 'key' => 'library_isbn' ) );
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-form',
							'label'      => $form_labels->singular_name,
							'control'    => Library::get_select( array( '' => $form_labels->no_terms ) + $form_choices ),
							'attributes' => array( 'name' => 'form' ),
						)
					);
					Library::render_component(
						'field',
						array(
							'id'         => 'library-edit-status',
							'label'      => __( 'Library', 'library' ),
							'control'    => Library::get_select( $status_choices ),
							'attributes' => array( 'name' => 'status' ),
						)
					);
					?>
					<details class="library-edit__details">
						<summary><?php esc_html_e( 'More details', 'library' ); ?></summary>
						<?php
						foreach ( array( 'library_subtitle', 'library_series', 'library_volume_number', 'library_collection_number', 'library_issn', 'library_pages', 'library_edition_date', 'library_published_date' ) as $key ) {
							Library::render_component(
								'meta-field',
								array(
									'key'        => $key,
									// A number of pages is typed with the digits only; a date first published takes hyphens, which a numeric keyboard may not have.
									'attributes' => 'library_pages' === $key ? array( 'inputmode' => 'numeric' ) : array(),
								)
							);
						}

						Library::render_component(
							'pill-checkbox',
							array(
								'id'      => 'library-edit-collective',
								'label'   => Library_Meta::get_label( 'library_collective' ),
								'control' => Library_Meta::get_control( 'library_collective', array( 'data-meta' => true ) ),
							)
						);

						Library::render_component(
							'field',
							array(
								'id'         => 'library-edit-language',
								'label'      => $language_labels->singular_name,
								'control'    => Library::get_select( array( '' => $language_labels->no_terms ) ),
								'attributes' => array( 'name' => 'language' ),
							)
						);
						?>
					</details>
					<p class="app-status" data-field="error" role="alert" hidden></p>
					<p>
						<button class="app-pill app-pill--primary" type="submit"><?php esc_html_e( 'Save', 'library' ); ?></button>
					</p>
				</form>
				<p class="library-book__actions" hidden>
					<a data-link="admin" href=""><?php esc_html_e( 'Edit in the dashboard', 'library' ); ?></a>
				</p>
				<datalist id="library-edit-people"></datalist>
				<datalist id="library-edit-publishers"></datalist>
				<datalist id="library-edit-collections"></datalist>
			</dialog>
		</library-edit>

		<template id="library-item">
			<li class="app-item library-item">
				<img class="library-item__cover" data-field="cover" alt="" loading="lazy" hidden>
				<div class="library-item__body">
					<p class="app-item__meta" data-field="credits"></p>
					<h3 class="library-item__title">
						<button class="library-item__button" type="button" data-field="title"></button>
					</h3>
					<p class="library-item__excerpt" data-field="excerpt" hidden></p>
					<p class="app-item__meta">
						<span data-field="state"></span>
						<span class="library-item__status" data-field="status" hidden></span>
					</p>
				</div>
			</li>
		</template>

		<template id="library-read-date">
			<li class="library-input-list__item">
				<?php
				$control = Library_Meta::get_control(
					'library_read_date',
					array(
						'required'   => true,
						'aria-label' => __( 'Read date', 'library' ),
					)
				);

				echo $control; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
				?>
				<button
					class="app-control"
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
					<span class="library-cover__frame" data-row="title">
						<span class="library-cover__title" data-field="title"></span>
					</span>
				</button>
			</li>
		</template>

		<template id="library-edit-person">
			<li class="library-input-list__item">
				<input name="person" list="library-edit-people" autocomplete="off" aria-label="<?php echo esc_attr( $person_labels->singular_name ); ?>">
				<?php
				$role_select = Library::set_attributes(
					Library::get_select( Library_Meta::get_role_choices() ),
					array(
						'name'       => 'role',
						'aria-label' => __( 'Role', 'library' ),
					)
				);

				echo $role_select; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes.
				?>
				<button
					class="app-control"
					type="button"
					data-action="remove"
					aria-label="<?php esc_attr_e( 'Remove this person', 'library' ); ?>"
					title="<?php esc_attr_e( 'Remove this person', 'library' ); ?>"
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

	</body>
</html>
