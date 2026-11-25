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
 * @var array<int,string>                             $form_choices     Forms the filters of the books and of the diary and the form of a book offer, by term ID.
 * @var array{form_book_counts: array<int,int>, form_reading_counts: array<int,int>} $form_counts Books of each form on the shelves of the reader and readings of each form, by term ID: see Stats::get_form_counts().
 * @var array<int,string>                             $language_choices Languages the form of a book offers, original or of the edition, by term ID.
 * @var int                                           $own_language_id  Term ID of the language of the reader, 0 when the library has none.
 * @var array<string,string>                          $status_choices Statuses the forms of a book and the filter of the shelves offer, with their label.
 * @var array<string,array{label:string,url:string}> $views          Views of the app.
 * @var string                                        $view           View shown.
 */

use Library\App\Page;
use Library\Markup;
use Library\Meta;

// The forms of the books and of the readings shown, several at once, any of them while none is chosen; each with how many
// books of the shelves, or readings, it has.
$form_filter = array(
	'label'    => $form_labels->name,
	'options'  => $form_choices,
	'multiple' => true,
);
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
						<?php
						Markup::render_component(
							'views-nav',
							array(
								'views'   => $views,
								// The calendar, opened from the diary, is part of it.
								'current' => 'calendar' === $view ? 'diary' : $view,
								'tabs'    => true,
							)
						);
						?>
					</library-nav>
				</header>

				<?php Markup::render_component( 'offline-status' ); ?>

				<library-view name="books" <?php echo 'books' === $view ? '' : 'hidden'; ?>>
					<h2 class="sr-only"><?php echo esc_html( $labels->name ); ?></h2>
					<div class="app-controls">
						<library-search class="app-search">
							<?php Markup::render_component( 'search-form', array( 'id' => 'library-search' ) ); ?>
						</library-search>
					</div>

					<library-home class="library-sections">
						<?php // In January only, the recap of the year just gone by. ?>
						<library-recap-link hidden>
							<?php Markup::render_component( 'recap-link' ); ?>
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
						<library-idea class="library-section" hidden>
							<?php
							Markup::render_component(
								'reading-idea',
								array(
									'compact' => true,
									'level'   => 3,
								)
							);
							?>
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
						<library-covers-toggle class="library-title__action">
							<?php Markup::render_component( 'covers-toggle', array( 'id' => 'library-covers-books' ) ); ?>
						</library-covers-toggle>
					</div>
					<div class="app-controls library-books__controls">
						<?php
						// The filters of the books and their choices: their fields in a dialog, the choices made as pills that remove them.
						$book_filters = array(
							'read'  => array(
								'label'   => __( 'Reading state', 'library' ),
								'options' => array(
									''         => $labels->all_items,
									'yes'      => __( 'Read', 'library' ),
									'no'       => __( 'To read', 'library' ),
									// Apart from the read state: the keyword progress:.
									'progress' => Meta::get_label( 'library_in_progress' ),
								),
							),
							// The shelves of the reader, or each of them: the app lists their books, never the whole catalogue.
							'shelf' => array(
								'label'   => __( 'Library', 'library' ),
								'options' => array( '' => __( 'My shelves', 'library' ) ) + $status_choices,
							),
						);

						if ( $form_choices ) {
							$book_filters['forms'] = $form_filter + array( 'counts' => $form_counts['form_book_counts'] );
						}
						?>
						<library-filters>
							<?php
							Markup::render_component(
								'filters-sheet',
								array(
									'id'      => 'library-filters-dialog',
									'filters' => $book_filters,
								)
							);
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
									'attributes' => array(
										'name'        => 'sort',
										'data-series' => __( 'Reading order', 'library' ),
									),
								)
							);
							?>
						</library-filters>
					</div>

					<?php // The books found, as cards or as a grid of their covers, the next ones loading as the end of the list comes, and what it says while it loads or finds none. ?>
					<library-status class="app-status" hidden></library-status>
					<library-list>
						<ul class="app-list"></ul>
						<div class="library-list__more" data-part="more"></div>
					</library-list>
				</library-view>

				<library-view name="diary" <?php echo 'diary' === $view ? '' : 'hidden'; ?>>
					<?php // Its title, with how many readings, then the link to the calendar of its days at the end of its line. ?>
					<div class="library-title">
						<h2 class="library-title__heading">
							<?php echo esc_html( $views['diary']['label'] ); ?>
							<library-count class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" list="diary" role="status"><?php esc_html_e( 'Loading…', 'library' ); ?></library-count>
						</h2>
						<a class="app-text-s app-text-muted app-font-ui app-uppercase library-title__action" href="<?php echo esc_url( add_query_arg( 'view', 'calendar', $views['books']['url'] ) ); ?>"><?php esc_html_e( 'Calendar', 'library' ); ?></a>
					</div>
					<?php if ( $form_choices ) : ?>
						<div class="app-controls">
							<library-filters list="diary">
								<?php
								Markup::render_component(
									'filters-sheet',
									array(
										'id'      => 'library-diary-filters-dialog',
										'filters' => array( 'forms' => $form_filter + array( 'counts' => $form_counts['form_reading_counts'] ) ),
									)
								);
								?>
							</library-filters>
						</div>
					<?php endif; ?>
					<library-diary>
						<p class="app-status" data-field="status" hidden></p>
						<div class="library-sections library-diary__months" data-list="months"></div>
						<div class="library-list__more" data-part="more"></div>
					</library-diary>
				</library-view>

				<?php // The days of reading of a year, of one of its months or of one of its days, which the forms chosen filter too: opened from the diary. ?>
				<library-view name="calendar" <?php echo 'calendar' === $view ? '' : 'hidden'; ?>>
					<library-calendar class="library-calendar">
						<div class="library-calendar__bar">
							<div class="library-pills">
								<?php
								Markup::render_component(
									'pill-select',
									array(
										'id'         => 'library-calendar-scale',
										'label'      => __( 'Calendar', 'library' ),
										'options'    => array(
											'year'  => __( 'Year', 'library' ),
											'month' => __( 'Month', 'library' ),
											'day'   => __( 'Day', 'library' ),
										),
										'attributes' => array( 'name' => 'scale' ),
									)
								);
								?>
								<?php if ( $form_choices ) : ?>
									<library-filters list="calendar">
										<?php
										Markup::render_component(
											'filters-sheet',
											array(
												'id'      => 'library-calendar-filters-dialog',
												'filters' => array( 'forms' => $form_filter + array( 'counts' => $form_counts['form_reading_counts'] ) ),
											)
										);
										?>
									</library-filters>
								<?php endif; ?>
							</div>
							<p class="app-toolbar">
								<a class="app-control" data-part="previous" aria-label="<?php esc_attr_e( 'Before', 'library' ); ?>" title="<?php esc_attr_e( 'Before', 'library' ); ?>"><?php Markup::render_icon( 'arrow_back' ); ?></a>
								<a class="app-control" data-part="next" aria-label="<?php esc_attr_e( 'After', 'library' ); ?>" title="<?php esc_attr_e( 'After', 'library' ); ?>"><?php Markup::render_icon( 'arrow_forward' ); ?></a>
							</p>
						</div>
						<h2 class="library-capitalized library-calendar__period" data-field="period" tabindex="-1"></h2>
						<?php // The months of the year, each a link to it at the scale of a month. ?>
						<ol class="library-calendar__year" data-part="year">
							<?php for ( $calendar_month = 0; $calendar_month < 12; $calendar_month++ ) : ?>
								<li>
									<a class="library-link app-text-s app-font-ui app-uppercase" data-part="month-link" href=""></a>
									<?php Markup::render_component( 'month-calendar' ); ?>
								</li>
							<?php endfor; ?>
						</ol>
						<div data-part="month" hidden>
							<?php Markup::render_component( 'month-calendar', array( 'covers' => true ) ); ?>
						</div>
						<?php // What its days count: after the year or the month, before the books read that day. ?>
						<dl class="library-fields library-fields--aside">
							<?php
							$calendar_stats = array(
								'readings' => __( 'Readings', 'library' ),
								'pages'    => __( 'Pages', 'library' ),
							);
							?>
							<?php foreach ( $calendar_stats as $calendar_field => $calendar_label ) : ?>
								<div data-row="<?php echo esc_attr( $calendar_field ); ?>">
									<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $calendar_label ); ?></dt>
									<dd data-field="<?php echo esc_attr( $calendar_field ); ?>"></dd>
								</div>
							<?php endforeach; ?>
						</dl>
						<?php // The books finished that day. ?>
						<div data-part="day" hidden>
							<ul class="app-list" data-list="day"></ul>
							<p class="app-status" data-part="none" hidden><?php esc_html_e( 'No book finished that day.', 'library' ); ?></p>
						</div>
						<p class="app-status" data-field="status" hidden></p>
					</library-calendar>
				</library-view>

				<library-view name="idea" <?php echo 'idea' === $view ? '' : 'hidden'; ?>>
					<library-idea>
						<?php Markup::render_component( 'reading-idea' ); ?>
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
						<library-covers-toggle class="library-title__action">
							<?php Markup::render_component( 'covers-toggle', array( 'id' => 'library-covers-to-buy' ) ); ?>
						</library-covers-toggle>
					</div>
					<library-status class="app-status" list="toBuy" hidden></library-status>
					<library-list list="toBuy">
						<ul class="app-list"></ul>
						<div class="library-list__more" data-part="more"></div>
					</library-list>
				</library-view>

				<?php
				/*
				 * The page of a term, a person, a series or a collection, each its brick, library-<name>, which TermPage
				 * bases: the lines of its header, then its own sections, between its wishes and its books, each a row
				 * hidden until it has something to show, either term cards, the first ones then the others folded once
				 * there are enough of them, or pills.
				 */
				$series_labels = get_taxonomy( 'library_series' )->labels;
				$term_pages    = array(
					Page::PERSON_VIEW    => array(
						'lines'    => array( array( 'field' => 'otherNames' ) ),
						'sections' => array(
							'series' => array( $series_labels->name, 'cards' ),
						),
					),
					Page::SERIES_VIEW    => array(
						'lines'    => array(
							array(
								'field' => 'cycle',
								'label' => $series_labels->parent_item_colon,
								'link'  => true,
							),
							array(
								'field' => 'issn',
								'label' => get_registered_meta_keys( 'term', 'library_series' )['library_issn']['label'],
							),
						),
						'sections' => array(
							'parts'   => array( $series_labels->name, 'pills' ),
							'authors' => array( __( 'Authors', 'library' ), 'cards' ),
						),
					),
					Page::PUBLISHER_VIEW => array(
						'lines'    => array(
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
						'sections' => array(
							'collections' => array( __( 'Collections', 'library' ), 'pills' ),
							'authors'     => array( __( 'Authors', 'library' ), 'cards' ),
						),
					),
				);
				?>
				<?php foreach ( $term_pages as $term_view => $term_page ) : ?>
					<library-view name="<?php echo esc_attr( $term_view ); ?>" <?php echo $term_view === $view ? '' : 'hidden'; ?>>
						<library-back class="library-back">
							<?php Markup::render_component( 'back' ); ?>
						</library-back>
						<<?php echo esc_html( "library-$term_view" ); ?>>
							<p class="app-status" data-field="status" hidden></p>
							<?php Markup::render_component( 'term-header', array( 'lines' => $term_page['lines'] ) ); ?>
							<ul class="app-list library-term-completion" data-list="completion" hidden></ul>
							<?php // Its wishes, books to buy without a reading, apart from its books: few, all at once. ?>
							<section class="library-section" data-row="wishes" hidden>
								<h3 class="library-title">
									<?php esc_html_e( 'To buy', 'library' ); ?>
									<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="wishes"></span>
								</h3>
								<ul class="app-list" data-list="wishes"></ul>
							</section>
							<?php foreach ( $term_page['sections'] as $row => [ $section_title, $kind ] ) : ?>
								<section class="library-section" data-row="<?php echo esc_attr( $row ); ?>" hidden>
									<h3 class="library-title"><?php echo esc_html( $section_title ); ?></h3>
									<?php if ( 'cards' === $kind ) : ?>
										<?php Markup::render_component( 'cards-group' ); ?>
									<?php else : ?>
										<ul class="library-pills" data-list="<?php echo esc_attr( $row ); ?>"></ul>
									<?php endif; ?>
								</section>
							<?php endforeach; ?>
							<?php // Its books, under their title, as the home has them: their number, and their cards or their covers, as chosen. ?>
							<section class="library-section">
								<div class="library-title">
									<h3 class="library-title__heading">
										<?php echo esc_html( $labels->name ); ?>
										<library-count class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" list="<?php echo esc_attr( $term_view ); ?>"></library-count>
									</h3>
									<library-covers-toggle class="library-title__action">
										<?php Markup::render_component( 'covers-toggle', array( 'id' => "library-covers-$term_view" ) ); ?>
									</library-covers-toggle>
								</div>
								<ul class="app-list" data-list="books"></ul>
								<div class="library-list__more" data-part="more"></div>
							</section>
						</<?php echo esc_html( "library-$term_view" ); ?>>
					</library-view>
				<?php endforeach; ?>

				<?php // A screen per subject, each as high as the screen, those without anything to say hidden. ?>
				<library-view name="recap" data-immersive <?php echo Page::RECAP_VIEW === $view ? '' : 'hidden'; ?>>
					<library-recap>
						<section class="library-recap__screen library-recap__opening">
							<library-back class="library-back">
								<?php Markup::render_component( 'back' ); ?>
							</library-back>
							<hgroup class="library-recap__heading">
								<h2 class="library-recap__year" data-field="year" tabindex="-1"></h2>
								<p><?php esc_html_e( 'A year of reading', 'library' ); ?></p>
							</hgroup>
							<p class="app-status" data-field="status" hidden></p>
							<ul class="library-recap__mosaic" data-list="covers" aria-hidden="true"></ul>
						</section>

						<section class="library-recap__screen" data-screen="readings" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Books read', 'library' ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'readings' ) ) ); ?></p>
							<p data-field="previous"></p>
							<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="rereads"></p>
						</section>

						<section class="library-recap__screen" data-screen="pages" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Pages read', 'library' ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'pages' ) ) ); ?></p>
							<p data-field="perDay"></p>
							<p data-field="perBook"></p>
							<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="counted"></p>
						</section>

						<section class="library-recap__screen" data-screen="months" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Month by month', 'library' ); ?></h3>
							<?php Markup::render_component( 'months-chart' ); ?>
							<p data-field="bestMonth"></p>
						</section>

						<?php // Over its days, a backdrop: those a book was finished, as dark as the pages finished, the memorable ones pulsing. ?>
						<section class="library-recap__screen library-recap__days" data-screen="days" hidden>
							<div class="library-recap__calendar">
								<?php Markup::render_component( 'days-calendar' ); ?>
							</div>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Day by day', 'library' ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'daysFinished' ) ) ); ?></p>
							<p data-field="daysFinishedLabel"></p>
							<p data-field="bestDay"></p>
						</section>

						<section class="library-recap__screen" data-screen="forms" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php echo esc_html( $form_labels->name ); ?></h3>
							<?php Markup::render_component( 'shares-chart' ); ?>
						</section>

						<section class="library-recap__screen" data-screen="people" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Read the most', 'library' ); ?></h3>
							<?php Markup::render_component( 'shares-chart', array( 'ranked' => true ) ); ?>
						</section>

						<section class="library-recap__screen" data-screen="discoveries" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'Discoveries', 'library' ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'discoveries' ) ) ); ?></p>
							<p data-field="discovered"></p>
							<ul class="library-pills library-recap__cloud library-recap__cut" data-row="discoveries" data-list="discoveries"></ul>
						</section>

						<section class="library-recap__screen" data-screen="languages" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php echo esc_html( $language_labels->name_for_book ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'languages' ) ) ); ?></p>
							<?php Markup::render_component( 'shares-chart' ); ?>
						</section>

						<section class="library-recap__screen" data-screen="series" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php echo esc_html( get_taxonomy( 'library_series' )->labels->name ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'series' ) ) ); ?></p>
							<p data-field="seriesState"></p>
							<?php Markup::render_component( 'shares-chart', array( 'ranked' => true ) ); ?>
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
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'achievements' ) ) ); ?></p>
							<ul class="library-pills library-recap__cloud library-recap__cut" data-list="achievements"></ul>
						</section>

						<section class="library-recap__screen" data-screen="pile" hidden>
							<h3 class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal"><?php esc_html_e( 'The pile', 'library' ); ?></h3>
							<p class="library-recap__figure"><?php Markup::render_component( 'count-up', array( 'attributes' => array( 'data-field' => 'added' ) ) ); ?></p>
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
					<library-back class="library-back">
						<?php Markup::render_component( 'back' ); ?>
					</library-back>
					<library-book class="library-book">
						<p class="app-status" data-field="status" role="status" hidden></p>
						<?php Markup::render_component( 'book-header' ); ?>
						<div class="library-prose" data-html="notes" hidden></div>
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
									?>
									<?php // While a reading is in progress: it ends today, abandoned, as Read ends it finished. ?>
									<button class="app-pill" type="button" data-action="abandon" hidden><?php echo esc_html_x( 'Abandon', 'a reading', 'library' ); ?></button>
									<?php
									Markup::render_component(
										'pill-select',
										array(
											'id'         => 'library-reading-status',
											'label'      => __( 'Library', 'library' ),
											'options'    => $status_choices + array( '' => __( 'On none of my shelves', 'library' ) ),
											'attributes' => array( 'name' => 'status' ),
										)
									);
									?>
								</div>
								<?php // The day the reading in progress started, while there is one. ?>
								<div class="library-reading__started" data-part="started" hidden>
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
								<?php // The readings ended, folded under the line that sums them up, which opens them to change them. ?>
								<details class="library-disclosure library-reading__details" data-part="readings" hidden>
									<summary class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="readings"></summary>
									<?php
									Markup::render_component(
										'inputs-list',
										array(
											'id'          => 'library-reading-readings',
											'label'       => Meta::get_label( 'library_reading' ),
											'description' => Meta::get_description( 'library_reading' ),
											'template'    => 'library-reading-row',
											'add'         => __( 'Add a reading', 'library' ),
											// A date input fills in today once touched, as on an iPhone: a reading whose date is unknown has none.
											'variants'    => array(
												array(
													'label' => __( 'Add a reading with an unknown date', 'library' ),
													'value' => '/',
												),
											),
											'attributes'  => array( 'disabled' => true ),
										)
									);
									?>
								</details>
								<?php
								// The copy of the reader, theirs: the same book, another printing, their notes, and its photo, which shows instead
								// of the cover of its edition.
								Markup::render_component(
									'partial-date',
									array(
										'id'          => 'library-reading-printed-date',
										'label'       => Meta::get_label( 'library_printed_date' ),
										'description' => Meta::get_description( 'library_printed_date' ),
										'name'        => Meta::get_name( 'library_printed_date' ),
									)
								);
								Markup::render_component(
									'field',
									array(
										'id'         => 'library-reading-notes',
										'label'      => Meta::get_label( 'library_notes' ),
										'control'    => '<textarea></textarea>',
										'attributes' => array(
											'name' => Meta::get_name( 'library_notes' ),
											'rows' => 3,
										),
									)
								);
								Markup::render_component(
									'image-field',
									array(
										'id'     => 'library-reading-cover',
										'name'   => Meta::get_name( 'library_cover' ),
										'label'  => __( 'Photograph my copy', 'library' ),
										'remove' => __( 'Remove the photo of my copy', 'library' ),
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
						<?php Markup::render_component( 'book-fields' ); ?>
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
			<button class="library-link" type="button" data-action="notice" hidden></button>
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
				<form class="library-form library-edit__form" data-part="form">
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
					// Where the browser reads EAN-13 barcodes: a photo of the barcode of the book types its ISBN.
					Markup::render_component(
						'barcode-scan',
						array(
							'id'  => 'library-edit-scan',
							'for' => 'library-edit-library-isbn',
						)
					);
					Markup::render_component( 'catalogue-status' );
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
							'id'            => 'library-edit-role',
							'label'         => $person_labels->name,
							'groups'        => $role_groups,
							'options'       => Meta::get_role_choices(),
							'options_label' => __( 'Role', 'library' ),
							'add'           => __( 'Add a person', 'library' ),
							'template'      => 'library-edit-person',
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
					// Every book has a form, as soon as the library has some: the filters of the forms leave out a book of none.
					Markup::render_component(
						'field',
						array(
							'id'         => 'library-edit-form',
							'label'      => $form_labels->singular_name,
							'required'   => (bool) $form_choices,
							'control'    => Markup::get_select( array( '' => $form_choices ? __( 'Choose a form', 'library' ) : $form_labels->no_terms ) + $form_choices ),
							'attributes' => array( 'name' => 'form' ),
						)
					);
					?>
					<?php // Where the book goes, for a book to add only: one edited has its reading form. ?>
					<div data-part="status">
						<?php
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
					</div>
					<?php

					// The fields few books have, closed: its summary counts the ones filled in.
					Markup::render_component(
						'fields-disclosure',
						array(
							'label'  => __( 'More details', 'library' ),
							'class'  => 'library-edit__details',
							'fields' => function () use ( $language_labels, $language_choices, $own_language_id ): void {
								Markup::render_component( 'meta-field', array( 'key' => 'library_subtitle' ) );

								// A date by its day, its month in words and its year, rather than typed in the order of ISO 8601.
								foreach ( array( 'library_published_date', 'library_edition_date' ) as $key ) {
									Markup::render_component(
										'partial-date',
										array(
											'id'          => 'library-edit-' . str_replace( '_', '-', $key ),
											'label'       => Meta::get_label( $key ),
											'description' => Meta::get_description( $key ),
											'name'        => Meta::get_name( $key ),
											'attributes'  => array( 'data-meta' => true ),
										)
									);
								}

								// A number of pages is typed with the digits only.
								Markup::render_component(
									'meta-field',
									array(
										'key'        => 'library_pages',
										'attributes' => array( 'inputmode' => 'numeric' ),
									)
								);

								Markup::render_component(
									'checkboxes-field',
									array(
										'id'      => 'library-edit-languages',
										'label'   => $language_labels->name_for_book,
										'name'    => 'languages',
										'add'     => __( 'Add a language', 'library' ),
										'options' => $language_choices,
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
										'options'     => $language_choices,
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
												'name' => Meta::get_name( 'library_collective' ),
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
							},
						)
					);
					?>
					<p class="app-status" data-field="error" role="alert" hidden></p>
					<p>
						<button class="app-pill app-pill--primary" type="submit" data-saving="<?php esc_attr_e( 'Saving…', 'library' ); ?>"><?php esc_html_e( 'Save', 'library' ); ?></button>
					</p>
				</form>
				<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-part="admin" hidden>
					<a href=""><?php esc_html_e( 'Edit in the dashboard', 'library' ); ?></a>
				</p>
				<datalist id="library-edit-people"></datalist>
				<datalist id="library-edit-publishers"></datalist>
				<datalist id="library-edit-collections"></datalist>
				<datalist id="library-edit-series-paths"></datalist>
			</dialog>
		</library-edit>

		<template id="library-book-card">
			<li><?php Markup::render_component( 'book-card' ); ?></li>
		</template>

		<template id="library-book-row">
			<li><?php Markup::render_component( 'book-row' ); ?></li>
		</template>

		<template id="library-current">
			<li><?php Markup::render_component( 'cover-link', array( 'current' => true ) ); ?></li>
		</template>

		<template id="library-reading-row">
			<li class="library-row">
				<?php Markup::render_component( 'reading-row' ); ?>
				<?php Markup::render_component( 'remove-control', array( 'label' => __( 'Remove this reading', 'library' ) ) ); ?>
			</li>
		</template>

		<?php // A record of a recap: its cover, its figure, what it counts, which record it is, and its book, which it opens. ?>
		<template id="library-recap-record">
			<li class="library-recap__record">
				<?php Markup::render_component( 'book-cover', array( 'attributes' => array( 'aria-hidden' => 'true' ) ) ); ?>
				<div>
					<p class="library-recap__record-figure">
						<?php Markup::render_component( 'count-up' ); ?>
						<span data-field="year"></span>
						<span class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="unit"></span>
					</p>
					<p class="app-text-s app-text-muted app-font-ui app-uppercase" data-field="label"></p>
					<a data-field="title" href=""></a>
				</div>
			</li>
		</template>

		<template id="library-tile">
			<li><?php Markup::render_component( 'book-cover', array( 'attributes' => array( 'aria-hidden' => 'true' ) ) ); ?></li>
		</template>

		<template id="library-cover">
			<li><?php Markup::render_component( 'cover-link' ); ?></li>
		</template>

		<template id="library-edit-person">
			<li class="library-row">
				<input class="library-input" name="person" list="library-edit-people" autocomplete="off" aria-label="<?php echo esc_attr( $person_labels->singular_name ); ?>">
				<?php Markup::render_component( 'remove-control', array( 'label' => __( 'Remove this person', 'library' ) ) ); ?>
			</li>
		</template>

		<template id="library-diary-recap">
			<p class="library-diary__recap">
				<?php Markup::render_component( 'recap-link', array( 'pill' => true ) ); ?>
			</p>
		</template>

		<template id="library-month">
			<section class="library-section">
				<h3 class="library-title">
					<span class="library-capitalized" data-field="month"></span>
					<span class="app-text-s app-text-muted app-font-ui app-uppercase app-font-normal" data-field="count"></span>
				</h3>
				<ul class="app-list"></ul>
			</section>
		</template>

		<template id="library-term-card">
			<li><?php Markup::render_component( 'term-card' ); ?></li>
		</template>

		<template id="library-achievement-card">
			<li><?php Markup::render_component( 'achievement-card' ); ?></li>
		</template>

		<template id="library-achievement-card-close">
			<li><?php Markup::render_component( 'achievement-card', array( 'close' => true ) ); ?></li>
		</template>

	</body>
</html>
