<?php
/**
 * The reading app page: the books with the home above them, the diary, the reading idea, the achievements, the books to buy,
 * the page of a person, the recap of a year, the styleguide, the reference of the API, the book open with the form of its reading, and the
 * form of a book, to add or edit.
 *
 * The bricks (custom elements) fill the markup from the store; the templates hold the translated labels.
 * The view asked for in the URL shows from the first paint; the router then switches views without reloading.
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
 * @var object                                        $language_labels  Labels of the languages taxonomy.
 * @var object                                        $form_labels      Labels of the forms taxonomy.
 * @var array<int,string>                             $form_choices     Forms the filter of the books and the form of a book offer, by term ID.
 * @var array<int,string>                             $language_choices Languages the form of a book offers, original or of the edition, by term ID.
 * @var int                                           $own_language_id  Term ID of the language of the reader, 0 when the library has none.
 * @var array<string,string>                          $rating_choices Ratings a form offers, by value, with their label, none first.
 * @var array<string,string>                          $status_choices Statuses the forms of a book and the filter of the books offer, with their label.
 * @var array<string,array{label:string,url:string}> $views          Views of the app.
 * @var string                                        $view           View shown.
 */

use Library\App\Page;
use Library\Markup;
use Library\Meta;

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
		<?php wp_print_styles( 'library-app-sheets' ); ?>
		<?php wp_print_scripts( 'library-app-theme' ); ?>
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
					<library-app-name>
						<h1><a href="<?php echo esc_url( $views['books']['url'] ); ?>"><?php esc_html_e( 'Library', 'library' ); ?></a></h1>
					</library-app-name>
					<library-nav>
						<nav>
							<ul class="library-strip library-nav">
								<?php foreach ( $views as $name => $item ) : ?>
									<li>
										<a
											class="app-pill"
											href="<?php echo esc_url( $item['url'] ); ?>"
											<?php echo $name === $view ? 'aria-current="page"' : ''; ?>
										><?php echo esc_html( $item['label'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</nav>
					</library-nav>
				</header>

				<library-offline class="app-status" role="status" hidden><?php esc_html_e( 'Offline: the books shown are the ones loaded before, and nothing can be saved.', 'library' ); ?></library-offline>

				<library-view name="books" <?php echo 'books' === $view ? '' : 'hidden'; ?>>
					<h2 class="sr-only"><?php echo esc_html( $labels->name ); ?></h2>
					<div class="app-controls">
						<library-search class="app-search">
							<?php // A search landmark: the search follows the typing; submitted, it searches at once, or opens the styleguide or the reference of the API typed. ?>
							<form class="library-search__form" role="search">
								<label class="sr-only" for="library-search"><?php esc_html_e( 'Search the library', 'library' ); ?></label>
								<?php // The field, then its button, which the keyboard of a phone does not always show: Enter or the button submits. ?>
								<p class="library-search__row">
									<input
										id="library-search"
										type="search"
										placeholder="<?php esc_attr_e( 'Title, author, series, ISBN…', 'library' ); ?>"
										autocomplete="off"
										enterkeyhint="search"
									>
									<?php // A control, as those of the toolbar. ?>
									<button
										class="app-control"
										type="submit"
										aria-label="<?php echo esc_attr_x( 'Search', 'verb', 'library' ); ?>"
										title="<?php echo esc_attr_x( 'Search', 'verb', 'library' ); ?>"
									>
										<?php Markup::render_icon( 'search' ); ?>
									</button>
								</p>
								<?php
								// Search keywords, as the help of the books list explains them; a name between quotes, which keep its spaces.
								$search_keywords = array(
									'series:""'    => get_taxonomy( 'library_series' )->labels->singular_name,
									'author:""'    => Meta::get_role_choices()['author'],
									'publisher:""' => $publisher_labels->singular_name,
									/* translators: %s: current year. */
									'read:' . wp_date( 'Y' ) => sprintf( __( 'Read in %s', 'library' ), wp_date( 'Y' ) ),
									'rating:9'     => __( 'Rated 9 or more', 'library' ),
								);
								?>
								<p class="library-strip library-search__keywords" hidden>
									<span class="sr-only"><?php esc_html_e( 'Narrow the search to:', 'library' ); ?></span>
									<?php foreach ( $search_keywords as $search_keyword => $label ) : ?>
										<button class="app-pill" type="button" data-keyword="<?php echo esc_attr( $search_keyword ); ?>"><?php echo esc_html( $label ); ?></button>
									<?php endforeach; ?>
								</p>
							</form>
						</library-search>
					</div>

					<library-home class="library-sections">
						<?php // In January only, the recap of the year just gone by. ?>
						<library-recap-link hidden>
							<a class="app-item library-recap-link" href="">
								<span class="library-recap-link__year" data-field="year"></span>
								<span><?php esc_html_e( 'The recap of the year', 'library' ); ?></span>
							</a>
						</library-recap-link>
						<library-in-progress class="library-section" hidden>
							<h3 class="library-title">
								<?php echo esc_html( Meta::get_label( 'library_in_progress' ) ); ?>
								<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="count"></span>
							</h3>
							<ul class="library-strip"></ul>
						</library-in-progress>
						<library-year class="library-year library-section">
							<p data-field="yearToDate"></p>
						</library-year>
						<library-latest class="library-section" hidden>
							<div class="library-title">
								<h3 class="library-title__heading"><?php esc_html_e( 'Latest readings', 'library' ); ?></h3>
								<a class="app-text-s app-text-muted app-font-ui app-uppercase library-title__action" href="<?php echo esc_url( $views['diary']['url'] ); ?>"><?php echo esc_html( $views['diary']['label'] ); ?></a>
							</div>
							<ul class="library-strip"></ul>
						</library-latest>
						<?php // As a row, its next idea at the end of its title, as the view of the idea has it in full. ?>
						<library-idea class="library-section" compact hidden>
							<div class="library-title">
								<h3 class="library-title__heading"><?php esc_html_e( 'Reading idea of the week', 'library' ); ?></h3>
								<span class="app-text-s app-text-muted app-font-ui app-uppercase library-title__action" data-row="next"><button class="library-link" type="button" data-action="next-idea"><?php esc_html_e( 'Another idea', 'library' ); ?></button></span>
							</div>
							<p class="app-status" data-field="status" hidden></p>
							<p class="library-idea__reason" data-field="reason"></p>
							<ul class="app-list" data-field="book"></ul>
						</library-idea>
						<library-close class="library-section" hidden>
							<div class="library-title">
								<h3 class="library-title__heading"><?php esc_html_e( 'Close to it', 'library' ); ?></h3>
								<a class="app-text-s app-text-muted app-font-ui app-uppercase library-title__action" href="<?php echo esc_url( $views['achievements']['url'] ); ?>"><?php echo esc_html( $views['achievements']['label'] ); ?></a>
							</div>
							<ul class="app-list"></ul>
						</library-close>
					</library-home>
					<div class="library-title library-books__title">
						<h3 class="library-title__heading">
							<?php echo esc_html( $labels->name ); ?>
							<library-count class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" role="status"><?php esc_html_e( 'Loading…', 'library' ); ?></library-count>
						</h3>
						<?php Markup::render_component( 'covers-toggle', array( 'id' => 'library-covers-books' ) ); ?>
					</div>
					<div class="app-controls library-books__controls">
						<?php
						// The filters of the books and their choices: their fields in a dialog, the choices made as pills that remove them.
						$book_filters = array(
							'read'   => array(
								'label'   => __( 'Reading state', 'library' ),
								'options' => array(
									''         => $labels->all_items,
									'yes'      => __( 'Read', 'library' ),
									'no'       => __( 'To read', 'library' ),
									// Apart from the read state: the keyword progress:.
									'progress' => Meta::get_label( 'library_in_progress' ),
								),
							),
							'status' => array(
								'label'   => __( 'Library', 'library' ),
								'options' => array( '' => _x( 'Everywhere', 'books of any status', 'library' ) ) + $status_choices,
							),
						);

						if ( $form_choices ) {
							$book_filters['form'] = array(
								'label'   => $form_labels->singular_name,
								'options' => array( '' => $form_labels->all_items ) + $form_choices,
							);
						}
						?>
						<library-filters>
							<button class="app-pill" type="button" data-action="open-filters" aria-haspopup="dialog">
								<span data-field="label"><?php esc_html_e( 'Filters', 'library' ); ?></span>
							</button>
							<p class="library-filters__chosen library-pills" hidden>
								<?php foreach ( $book_filters as $book_filter => $filter_args ) : ?>
									<?php foreach ( array_filter( $filter_args['options'], 'strlen', ARRAY_FILTER_USE_KEY ) as $value => $label ) : ?>
										<button class="app-pill" type="button" data-filter="<?php echo esc_attr( $book_filter ); ?>" data-value="<?php echo esc_attr( $value ); ?>" hidden>
											<span class="sr-only"><?php esc_html_e( 'Remove the filter:', 'library' ); ?></span>
											<?php echo esc_html( $label ); ?>
											<?php Markup::render_icon( 'close' ); ?>
										</button>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</p>
							<dialog class="library-sheet" id="library-filters-dialog" aria-labelledby="library-filters-title">
								<?php Markup::render_component( 'dialog-close', array( 'dialog' => 'library-filters-dialog' ) ); ?>
								<h2 class="library-heading" id="library-filters-title"><?php esc_html_e( 'Filters', 'library' ); ?></h2>
								<?php foreach ( $book_filters as $book_filter => $filter_args ) : ?>
									<library-filter data-filter="<?php echo esc_attr( $book_filter ); ?>">
										<?php
										Markup::render_component(
											'field',
											array(
												'id'      => "library-{$book_filter}-filter",
												'label'   => $filter_args['label'],
												'control' => Markup::get_select( $filter_args['options'] ),
											)
										);
										?>
									</library-filter>
								<?php endforeach; ?>
								<p>
									<button class="app-pill app-pill--primary" type="button" commandfor="library-filters-dialog" command="close" data-field="show"></button>
								</p>
							</dialog>
						</library-filters>
						<library-filter data-filter="sort">
							<?php
							Markup::render_component(
								'pill-select',
								array(
									'id'         => 'library-sort',
									'label'      => __( 'Order', 'library' ),
									'options'    => array(
										''       => __( 'Latest added', 'library' ),
										'title'  => __( 'Title', 'library' ),
										'rating' => Meta::get_label( 'library_rating' ),
										'read'   => __( 'Latest read', 'library' ),
									),
									// The order of a search for a series, which lists it in reading order.
									'attributes' => array( 'data-series' => __( 'Reading order', 'library' ) ),
								)
							);
							?>
						</library-filter>
					</div>

					<?php Markup::render_component( 'book-list' ); ?>
				</library-view>

				<library-view name="diary" <?php echo 'diary' === $view ? '' : 'hidden'; ?>>
					<library-diary>
						<p class="app-status" data-field="status" hidden></p>
						<div class="library-sections library-diary__months"></div>
						<div class="library-list__more"></div>
					</library-diary>
				</library-view>

				<library-view name="idea" <?php echo 'idea' === $view ? '' : 'hidden'; ?>>
					<library-idea>
						<h2 class="library-title"><?php esc_html_e( 'Reading idea of the week', 'library' ); ?></h2>
						<p class="app-status" data-field="status" hidden></p>
						<p class="library-idea__reason" data-field="reason"></p>
						<ul class="app-list" data-field="book"></ul>
						<p class="library-idea__actions" data-row="next">
							<button class="app-pill" type="button" data-action="next-idea"><?php esc_html_e( 'Another idea', 'library' ); ?></button>
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
					<library-achievements class="library-sections">
						<p class="app-status" data-field="status" hidden></p>
						<section class="library-section" data-row="close">
							<h2 class="library-title">
								<?php esc_html_e( 'Close to it', 'library' ); ?>
								<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="closeCount"></span>
							</h2>
							<ul class="app-list" data-list="close"></ul>
						</section>
						<section class="library-section" data-row="earned">
							<h2 class="library-title">
								<?php esc_html_e( 'Achievements', 'library' ); ?>
								<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="earnedCount"></span>
							</h2>
							<ul class="app-list" data-list="earned"></ul>
						</section>
					</library-achievements>
				</library-view>

				<library-view name="to-buy" <?php echo 'to-buy' === $view ? '' : 'hidden'; ?>>
					<div class="library-title">
						<h2 class="library-title__heading">
							<?php echo esc_html( $views['to-buy']['label'] ); ?>
							<library-count class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" list="toBuy"></library-count>
						</h2>
						<?php Markup::render_component( 'covers-toggle', array( 'id' => 'library-covers-to-buy' ) ); ?>
					</div>
					<?php Markup::render_component( 'book-list', array( 'list' => 'toBuy' ) ); ?>
				</library-view>

				<library-view name="person" <?php echo Page::PERSON_VIEW === $view ? '' : 'hidden'; ?>>
					<?php Markup::render_component( 'back' ); ?>
					<library-person>
						<p class="app-status" data-field="status" hidden></p>
						<?php Markup::render_component( 'term-header', array( 'lines' => array( array( 'field' => 'otherNames' ) ) ) ); ?>
						<ul class="app-list library-term-completion" data-list="completion" hidden></ul>
						<?php // The first series, then the others folded, once there are enough of them. ?>
						<section class="library-section" data-row="series" hidden>
							<h3 class="library-title"><?php echo esc_html( get_taxonomy( 'library_series' )->labels->name ); ?></h3>
							<ul class="app-list" data-list="shown"></ul>
							<details class="library-section__more" data-row="more" hidden>
								<summary class="app-pill" data-field="more"></summary>
								<ul class="app-list" data-list="more"></ul>
							</details>
						</section>
						<ul class="app-list" data-list="books"></ul>
						<div class="library-list__more"></div>
					</library-person>
				</library-view>

				<library-view name="series" <?php echo Page::SERIES_VIEW === $view ? '' : 'hidden'; ?>>
					<?php Markup::render_component( 'back' ); ?>
					<library-series>
						<p class="app-status" data-field="status" hidden></p>
						<?php
						Markup::render_component(
							'term-header',
							array(
								'lines' => array(
									array(
										'field' => 'cycle',
										'label' => get_taxonomy( 'library_series' )->labels->parent_item_colon,
										'link'  => true,
									),
									array(
										'field' => 'issn',
										'label' => get_registered_meta_keys( 'term', 'library_series' )['library_issn']['label'],
									),
								),
							)
						);
						?>
						<ul class="app-list library-term-completion" data-list="completion" hidden></ul>
						<section class="library-section" data-row="parts" hidden>
							<h3 class="library-title"><?php echo esc_html( get_taxonomy( 'library_series' )->labels->name ); ?></h3>
							<ul class="library-pills" data-list="parts"></ul>
						</section>
						<?php // The first authors, then the others folded, once there are enough of them. ?>
						<section class="library-section" data-row="authors" hidden>
							<h3 class="library-title"><?php esc_html_e( 'Authors', 'library' ); ?></h3>
							<ul class="app-list" data-list="shown"></ul>
							<details class="library-section__more" data-row="more" hidden>
								<summary class="app-pill" data-field="more"></summary>
								<ul class="app-list" data-list="more"></ul>
							</details>
						</section>
						<ul class="app-list" data-list="books"></ul>
						<div class="library-list__more"></div>
					</library-series>
				</library-view>

				<library-view name="publisher" <?php echo Page::PUBLISHER_VIEW === $view ? '' : 'hidden'; ?>>
					<?php Markup::render_component( 'back' ); ?>
					<library-publisher>
						<p class="app-status" data-field="status" hidden></p>
						<?php
						Markup::render_component(
							'term-header',
							array(
								'lines' => array(
									array(
										'field' => 'publisher',
										'label' => __( 'Publisher:', 'library' ),
										'link'  => true,
									),
									array(
										'field' => 'collection',
										'label' => __( 'Collection:', 'library' ),
										'link'  => true,
									),
								),
							)
						);
						?>
						<ul class="app-list library-term-completion" data-list="completion" hidden></ul>
						<section class="library-section" data-row="collections" hidden>
							<h3 class="library-title"><?php esc_html_e( 'Collections', 'library' ); ?></h3>
							<ul class="library-pills" data-list="collections"></ul>
						</section>
						<?php // The first authors, then the others folded, once there are enough of them. ?>
						<section class="library-section" data-row="authors" hidden>
							<h3 class="library-title"><?php esc_html_e( 'Authors', 'library' ); ?></h3>
							<ul class="app-list" data-list="shown"></ul>
							<details class="library-section__more" data-row="more" hidden>
								<summary class="app-pill" data-field="more"></summary>
								<ul class="app-list" data-list="more"></ul>
							</details>
						</section>
						<ul class="app-list" data-list="books"></ul>
						<div class="library-list__more"></div>
					</library-publisher>
				</library-view>

				<?php // A screen per subject, each as high as the screen, those without anything to say hidden. ?>
				<library-view name="recap" data-immersive <?php echo Page::RECAP_VIEW === $view ? '' : 'hidden'; ?>>
					<library-recap>
						<section class="library-recap__screen library-recap__opening">
							<?php Markup::render_component( 'back' ); ?>
							<hgroup class="library-recap__heading">
								<h2 class="library-recap__year" data-field="year" tabindex="-1"></h2>
								<p><?php esc_html_e( 'A year of reading', 'library' ); ?></p>
							</hgroup>
							<p class="app-status" data-field="status" hidden></p>
							<ul class="library-recap__mosaic" data-list="covers" aria-hidden="true"></ul>
						</section>

						<section class="library-recap__screen" data-screen="readings" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Books read', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="readings"></p>
							<p data-field="previous"></p>
							<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="rereads"></p>
						</section>

						<section class="library-recap__screen" data-screen="pages" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Pages read', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="pages"></p>
							<p data-field="perDay"></p>
							<p data-field="perBook"></p>
							<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="counted"></p>
						</section>

						<section class="library-recap__screen" data-screen="months" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Month by month', 'library' ); ?></h3>
							<ol class="library-recap__months" data-list="months"></ol>
							<p data-field="bestMonth"></p>
						</section>

						<?php // Over its days, a backdrop: those read, as dark as the pages read, the memorable ones pulsing. ?>
						<section class="library-recap__screen library-recap__days" data-screen="days" hidden>
							<div class="library-recap__calendar" aria-hidden="true">
								<ol data-list="calendar"></ol>
							</div>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Day by day', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="daysRead"></p>
							<p data-field="daysReadLabel"></p>
							<p data-field="streak"></p>
							<p data-field="bestDay"></p>
							<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="dated"></p>
						</section>

						<section class="library-recap__screen" data-screen="forms" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php echo esc_html( $form_labels->name ); ?></h3>
							<ul class="library-recap__shares" data-list="forms"></ul>
						</section>

						<section class="library-recap__screen" data-screen="people" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Read the most', 'library' ); ?></h3>
							<ol class="library-recap__ranking" data-list="people"></ol>
						</section>

						<section class="library-recap__screen" data-screen="discoveries" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Discoveries', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="discoveries"></p>
							<p data-field="discovered"></p>
							<ul class="library-pills library-recap__cloud library-recap__cut" data-row="discoveries" data-list="discoveries"></ul>
						</section>

						<section class="library-recap__screen" data-screen="languages" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php echo esc_html( $language_labels->name_for_book ); ?></h3>
							<p class="library-recap__figure" data-field="languages"></p>
							<ul class="library-recap__shares" data-list="languages"></ul>
						</section>

						<section class="library-recap__screen" data-screen="series" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php echo esc_html( get_taxonomy( 'library_series' )->labels->name ); ?></h3>
							<p class="library-recap__figure" data-field="series"></p>
							<p data-field="seriesState"></p>
							<ol class="library-recap__ranking" data-list="series"></ol>
						</section>

						<section class="library-recap__screen" data-screen="rating" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Favourites', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="rating"></p>
							<p><?php esc_html_e( 'Average rating', 'library' ); ?></p>
							<ul class="library-recap__wall library-recap__cut" data-list="best"></ul>
						</section>

						<section class="library-recap__screen" data-screen="records" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Records', 'library' ); ?></h3>
							<ul class="library-recap__records" data-list="records"></ul>
						</section>

						<section class="library-recap__screen" data-screen="achievements" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Achievements', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="achievements"></p>
							<ul class="library-pills library-recap__cloud library-recap__cut" data-list="achievements"></ul>
						</section>

						<section class="library-recap__screen" data-screen="pile" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'The pile', 'library' ); ?></h3>
							<p class="library-recap__figure" data-field="added"></p>
							<p data-field="addedBooks"></p>
							<p data-field="pile"></p>
						</section>

						<section class="library-recap__screen library-recap__closing" data-screen="closing" hidden>
							<div class="library-recap__marquee" data-list="marquee" aria-hidden="true"></div>
							<p class="library-recap__figure" data-field="closing"></p>
							<nav class="library-pills" aria-label="<?php esc_attr_e( 'Other years', 'library' ); ?>">
								<a class="app-pill" data-field="previousYear" href=""></a>
								<a class="app-pill" data-field="nextYear" href=""></a>
							</nav>
						</section>
					</library-recap>
				</library-view>

				<library-view name="book" <?php echo Page::BOOK_VIEW === $view ? '' : 'hidden'; ?>>
					<?php Markup::render_component( 'back' ); ?>
					<library-book class="library-book">
						<p class="app-status" data-field="status" role="status" hidden></p>
						<img class="library-cover library-book__cover" data-field="cover" alt="" loading="lazy" hidden>
						<p class="app-text-s app-text-muted app-font-ui app-uppercase library-book__meta" data-field="credits"></p>
						<h2 class="library-heading" data-field="title" tabindex="-1"></h2>
						<p class="library-book__excerpt" data-field="excerpt"></p>
						<div class="library-prose" data-html="content" hidden></div>
						<p class="library-book__translation" data-field="translation"></p>
						<library-reading>
							<form class="library-form library-reading">
								<div class="library-pills">
									<?php
									Markup::render_component(
										'pill-checkbox',
										array(
											'id'      => 'library-reading-read',
											'label'   => Meta::get_label( 'library_read' ),
											'control' => Meta::get_control( 'library_read', array( 'name' => Meta::get_name( 'library_read' ) ) ),
										)
									);
									Markup::render_component(
										'pill-checkbox',
										array(
											'id'      => 'library-reading-in-progress',
											'label'   => Meta::get_label( 'library_in_progress' ),
											'control' => Meta::get_control( 'library_in_progress', array( 'name' => Meta::get_name( 'library_in_progress' ) ) ),
										)
									);
									Markup::render_component(
										'pill-select',
										array(
											'id'         => 'library-reading-rating',
											'label'      => Meta::get_label( 'library_rating' ),
											'options'    => $rating_choices,
											'attributes' => array( 'name' => Meta::get_name( 'library_rating' ) ),
										)
									);
									Markup::render_component(
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
								<?php // The day the reading in progress started, while there is one. ?>
								<div class="library-reading__started" hidden>
									<?php
									Markup::render_component(
										'field',
										array(
											'id'          => 'library-reading-started',
											'label'       => __( 'Started', 'library' ),
											'control'     => '<input type="date">',
											'description' => Meta::get_description( 'library_in_progress' ),
											'attributes'  => array(
												'name'     => 'started',
												'disabled' => true,
											),
										)
									);
									?>
								</div>
								<?php
								Markup::render_component(
									'input-list',
									array(
										'id'          => 'library-reading-readings',
										'class'       => 'library-reading__readings',
										'legend'      => Meta::get_label( 'library_reading' ),
										'description' => Meta::get_description( 'library_reading' ),
										'add'         => __( 'Add a reading', 'library' ),
										'action'      => 'add',
										// A date input fills in today once touched, as on an iPhone: a reading whose date is unknown has none.
										'actions'     => array( 'add-unknown' => __( 'Add a reading with an unknown date', 'library' ) ),
										'attributes'  => array(
											'hidden'   => true,
											'disabled' => true,
										),
									)
								);
								?>
								<p class="app-status" data-field="error" role="alert" hidden></p>
							</form>
						</library-reading>
						<library-volumes class="library-book__series" hidden>
							<h3 class="library-title">
								<a data-field="name" href=""></a>
								<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="count"></span>
							</h3>
							<ul class="library-strip"></ul>
						</library-volumes>
						<dl class="library-fields">
							<div data-row="authors">
								<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $person_labels->name ); ?></dt>
								<dd data-field="authors"></dd>
							</div>
							<?php // The translators are named by the translation line. ?>
							<?php foreach ( array_diff_key( Meta::ROLE_KEYS, array( 'translator' => true ) ) as $role_field => $key ) : ?>
								<div data-row="<?php echo esc_attr( $role_field ); ?>">
									<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( Meta::get_label( $key ) ); ?></dt>
									<dd data-field="<?php echo esc_attr( $role_field ); ?>"></dd>
								</div>
							<?php endforeach; ?>
							<div data-row="isbn">
								<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( Meta::get_label( 'library_isbn' ) ); ?></dt>
								<dd data-field="isbn"></dd>
							</div>
							<div data-row="pages">
								<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( Meta::get_label( 'library_pages' ) ); ?></dt>
								<dd data-field="pages"></dd>
							</div>
							<?php // The copy in hand, its edition, then the work. ?>
							<?php foreach ( array( 'printed_date', 'edition_date', 'published_date' ) as $date_name ) : ?>
								<div data-row="<?php echo esc_attr( $date_name ); ?>">
									<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( Meta::get_label( "library_$date_name" ) ); ?></dt>
									<dd data-field="<?php echo esc_attr( $date_name ); ?>"></dd>
								</div>
							<?php endforeach; ?>
							<?php // How long the latest reading took, from its start to its end. ?>
							<div data-row="reading_time">
								<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php esc_html_e( 'Read in', 'library' ); ?></dt>
								<dd data-field="reading_time"></dd>
							</div>
						</dl>
						<p>
							<button class="app-pill" type="button" data-action="edit"><?php echo esc_html( $labels->edit_item ); ?></button>
						</p>
					</library-book>
				</library-view>

				<library-view name="styleguide" <?php echo Page::STYLEGUIDE_VIEW === $view ? '' : 'hidden'; ?>>
					<?php require __DIR__ . '/library-styleguide.php'; ?>
				</library-view>

				<library-view name="api" <?php echo Page::API_VIEW === $view ? '' : 'hidden'; ?>>
					<?php require __DIR__ . '/library-api.php'; ?>
				</library-view>
			</div>

			<footer class="app-footer">
				<library-progress class="app-progress"></library-progress>
				<p class="app-footer__right">
					<a href="<?php echo esc_url( admin_url() ); ?>"><?php esc_html_e( 'Dashboard', 'library' ); ?></a>
					<a href="#top"><?php esc_html_e( 'Top', 'library' ); ?></a>
				</p>
			</footer>
		</main>

		<div class="app-toolbar">
			<library-add-toggle>
				<button
					class="app-control"
					type="button"
					aria-label="<?php echo esc_attr( $labels->add_new_item ); ?>"
					title="<?php echo esc_attr( $labels->add_new_item ); ?>"
				><?php Markup::render_icon( 'add' ); ?></button>
			</library-add-toggle>
			<library-theme-toggle>
				<button
					class="app-control"
					type="button"
					aria-label="<?php esc_attr_e( 'Dark theme', 'library' ); ?>"
					title="<?php esc_attr_e( 'Light or dark theme', 'library' ); ?>"
				>
					<?php
					Markup::render_icon( 'dark_mode' );
					Markup::render_icon( 'light_mode' );
					?>
				</button>
			</library-theme-toggle>
		</div>

		<library-notice class="library-notice app-pill" tabindex="-1">
			<span role="status" data-field="text"></span>
			<button class="library-link" type="button" hidden></button>
		</library-notice>

		<library-edit>
			<dialog class="library-dialog library-edit" id="library-edit-dialog" aria-labelledby="library-edit-heading">
				<?php Markup::render_component( 'dialog-close', array( 'dialog' => 'library-edit-dialog' ) ); ?>
				<h2
					class="library-heading"
					id="library-edit-heading"
					data-add="<?php echo esc_attr( $labels->add_new_item ); ?>"
					data-edit="<?php echo esc_attr( $labels->edit_item ); ?>"
				></h2>
				<form class="library-form library-edit__form">
					<?php
					// The fields a book has most, in the order a book in hand gives them: its ISBN first, which tells a book added already.
					Markup::render_component(
						'image-field',
						array(
							'id'     => 'library-edit-cover',
							'name'   => 'cover',
							'label'  => __( 'Choose a cover', 'library' ),
							'remove' => __( 'Remove the cover', 'library' ),
						)
					);
					// An ISBN takes hyphens, which a numeric keyboard may not have.
					Markup::render_component( 'meta-field', array( 'key' => 'library_isbn' ) );
					?>
					<p class="library-edit__duplicate" role="status">
						<span data-row="duplicate" hidden>
							<?php esc_html_e( 'Already added:', 'library' ); ?>
							<a data-field="duplicate" href=""></a>
							<button class="app-pill" type="button" data-action="buy" hidden><?php esc_html_e( 'I bought it', 'library' ); ?></button>
						</span>
						<span data-field="catalog" hidden></span>
					</p>
					<?php
					Markup::render_component(
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
					// The people by role, the authors first, as the page of a book names them.
					$role_groups = array( 'author' => __( 'Authors', 'library' ) );

					foreach ( Meta::ROLE_KEYS as $role_group => $key ) {
						$role_groups[ $role_group ] = Meta::get_label( $key );
					}

					Markup::render_component(
						'input-groups',
						array(
							'id'         => 'library-edit-role',
							'legend'     => $person_labels->name,
							'groups'     => $role_groups,
							'choices'    => Meta::get_role_choices(),
							'label'      => __( 'Role', 'library' ),
							'add'        => __( 'Add a person', 'library' ),
							'action'     => 'add-person',
							'attributes' => array( 'data-list' => 'people' ),
						)
					);
					Markup::render_component(
						'field',
						array(
							'id'          => 'library-edit-series',
							'label'       => get_taxonomy( 'library_series' )->labels->singular_name,
							'description' => get_taxonomy( 'library_series' )->description,
							'attributes'  => array(
								'name'         => 'series',
								'list'         => 'library-edit-series-paths',
								'autocomplete' => 'off',
							),
						)
					);
					Markup::render_component( 'meta-field', array( 'key' => 'library_volume_number' ) );
					Markup::render_component(
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
					Markup::render_component(
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
					Markup::render_component( 'meta-field', array( 'key' => 'library_collection_number' ) );
					Markup::render_component(
						'field',
						array(
							'id'         => 'library-edit-form',
							'label'      => $form_labels->singular_name,
							'control'    => Markup::get_select( array( '' => $form_labels->no_terms ) + $form_choices ),
							'attributes' => array( 'name' => 'form' ),
						)
					);
					Markup::render_component( 'meta-field', array( 'key' => 'library_printed_date' ) );
					Markup::render_component(
						'field',
						array(
							'id'         => 'library-edit-status',
							'label'      => __( 'Library', 'library' ),
							'control'    => Markup::get_select( $status_choices ),
							'attributes' => array( 'name' => 'status' ),
						)
					);
					?>
					<?php // The fields few books have, closed: its summary counts the ones filled in. ?>
					<details class="library-edit__details">
						<summary class="app-text-s app-text-muted app-font-ui app-uppercase" data-label="<?php esc_attr_e( 'More details', 'library' ); ?>"><?php esc_html_e( 'More details', 'library' ); ?></summary>
						<?php
						foreach ( array( 'library_subtitle', 'library_published_date', 'library_edition_date', 'library_pages' ) as $key ) {
							Markup::render_component(
								'meta-field',
								array(
									'key'        => $key,
									// A number of pages is typed with the digits only; a date takes hyphens, which a numeric keyboard may not have.
									'attributes' => 'library_pages' === $key ? array( 'inputmode' => 'numeric' ) : array(),
								)
							);
						}

						Markup::render_component(
							'checkboxes-field',
							array(
								'id'      => 'library-edit-languages',
								'label'   => $language_labels->name_for_book,
								'name'    => 'languages',
								'add'     => __( 'Add a language', 'library' ),
								'choices' => $language_choices,
							)
						);
						// A book added is in the language of the reader, unless the catalogue says otherwise.
						Markup::render_component(
							'checkboxes-field',
							array(
								'id'          => 'library-edit-edition-languages',
								'label'       => Meta::get_label( 'library_edition_languages' ),
								'name'        => Meta::get_name( 'library_edition_languages' ),
								'add'         => __( 'Add a language', 'library' ),
								'choices'     => $language_choices,
								'checked'     => array( $own_language_id ),
								'description' => Meta::get_description( 'library_edition_languages' ),
							)
						);
						Markup::render_component(
							'pill-checkbox',
							array(
								'id'      => 'library-edit-collective',
								'label'   => Meta::get_label( 'library_collective' ),
								'control' => Meta::get_control(
									'library_collective',
									array(
										'name'      => Meta::get_name( 'library_collective' ),
										'data-meta' => true,
									)
								),
							)
						);
						Markup::render_component(
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
						Markup::render_component(
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
						?>
					</details>
					<p class="app-status" data-field="error" role="alert" hidden></p>
					<p>
						<button class="app-pill app-pill--primary" type="submit" data-saving="<?php esc_attr_e( 'Saving…', 'library' ); ?>"><?php esc_html_e( 'Save', 'library' ); ?></button>
					</p>
				</form>
				<p class="app-text-s app-text-muted app-font-ui app-uppercase" hidden>
					<a data-link="admin" href=""><?php esc_html_e( 'Edit in the dashboard', 'library' ); ?></a>
				</p>
				<datalist id="library-edit-people"></datalist>
				<datalist id="library-edit-publishers"></datalist>
				<datalist id="library-edit-collections"></datalist>
				<datalist id="library-edit-series-paths"></datalist>
			</dialog>
		</library-edit>

		<template id="library-item">
			<li class="app-item library-item">
				<img class="library-cover" data-field="cover" alt="" loading="lazy" hidden>
				<div class="library-item__body">
					<p class="app-item__meta" data-field="credits"></p>
					<h3 class="library-item__title">
						<a class="library-item__link" data-field="title"></a>
					</h3>
					<p class="library-item__excerpt" data-field="excerpt" hidden></p>
					<p class="app-item__meta">
						<span data-field="state"></span>
						<span class="library-tag" data-field="form" hidden></span>
						<span class="library-tag" data-field="original" hidden></span>
						<span class="library-tag library-tag--status" data-field="status" hidden></span>
					</p>
				</div>
			</li>
		</template>

		<template id="library-row">
			<li class="app-item library-row">
				<img class="library-cover" data-field="cover" alt="" loading="lazy" hidden>
				<div class="library-row__body">
					<h4 class="library-row__title">
						<a class="library-item__link">
							<span class="library-row__name" data-field="title"></span>
						</a>
					</h4>
					<p class="app-item__meta">
						<span data-field="authors"></span>
						<span class="library-tag library-tag--status" data-field="status" hidden></span>
					</p>
				</div>
			</li>
		</template>

		<template id="library-current">
			<li class="library-current">
				<a class="library-cover-link">
					<img class="library-cover" data-field="cover" alt="" loading="lazy" hidden>
					<span class="library-cover library-cover--frame" data-row="frame">
						<span class="library-cover__title" data-field="frame"></span>
					</span>
					<span class="library-current__title" data-field="title"></span>
					<span class="app-text-s app-font-ui app-uppercase library-tag library-tag--status" data-field="status" hidden></span>
				</a>
			</li>
		</template>

		<template id="library-reading-ended">
			<li class="library-input-list__item library-reading__item">
				<input type="date" name="reading_start" aria-label="<?php esc_attr_e( 'Start of the reading', 'library' ); ?>">
				<span class="library-reading__arrow" aria-hidden="true">→</span>
				<input type="date" name="reading_end" aria-label="<?php esc_attr_e( 'Read date', 'library' ); ?>">
				<button
					class="app-control"
					type="button"
					data-action="remove"
					aria-label="<?php esc_attr_e( 'Remove this reading', 'library' ); ?>"
					title="<?php esc_attr_e( 'Remove this reading', 'library' ); ?>"
				><?php Markup::render_icon( 'close' ); ?></button>
			</li>
		</template>

		<template id="library-reading-unknown">
			<li class="library-input-list__item">
				<input type="hidden" name="reading_start" value="">
				<input type="hidden" name="reading_end" value="">
				<span class="library-input-list__text"><?php esc_html_e( 'Unknown date', 'library' ); ?></span>
				<button
					class="app-control"
					type="button"
					data-action="remove"
					aria-label="<?php esc_attr_e( 'Remove this reading', 'library' ); ?>"
					title="<?php esc_attr_e( 'Remove this reading', 'library' ); ?>"
				><?php Markup::render_icon( 'close' ); ?></button>
			</li>
		</template>

		<template id="library-cover">
			<li>
				<a class="library-cover-link">
					<img class="library-cover" data-field="cover" alt="" loading="lazy" hidden>
					<span class="library-cover library-cover--frame" data-row="title">
						<span class="library-cover__title" data-field="title"></span>
					</span>
					<span class="sr-only" data-field="state" hidden></span>
				</a>
			</li>
		</template>

		<template id="library-edit-person">
			<li class="library-input-list__item">
				<input name="person" list="library-edit-people" autocomplete="off" aria-label="<?php echo esc_attr( $person_labels->singular_name ); ?>">
				<button
					class="app-control"
					type="button"
					data-action="remove"
					aria-label="<?php esc_attr_e( 'Remove this person', 'library' ); ?>"
					title="<?php esc_attr_e( 'Remove this person', 'library' ); ?>"
				><?php Markup::render_icon( 'close' ); ?></button>
			</li>
		</template>

		<template id="library-diary-recap">
			<p class="library-diary__recap">
				<a class="app-pill" data-field="recap" href=""></a>
			</p>
		</template>

		<template id="library-month">
			<section class="library-section">
				<h2 class="library-title">
					<span data-field="month"></span>
					<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="count"></span>
				</h2>
				<ul class="app-list"></ul>
			</section>
		</template>

		<template id="library-term">
			<li class="app-item library-term">
				<h4 class="library-term__name"></h4>
				<div class="library-term__description" data-html="description" hidden></div>
				<p class="app-item__meta" data-field="line"></p>
			</li>
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
