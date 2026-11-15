<?php
/**
 * Books list of the admin: columns, sorting, quick edit and the read percentage.
 *
 * @link       https://github.com/19h47/library
 * @since      6.1.1
 *
 * @package    Library
 * @subpackage Library/admin
 */

namespace Library\Admin;

use Library\Books;
use Library\Meta;
use Library\Posts;
use Library\Query;
use Library\Readings;
use Library\Shelf;
use Library\Stats;
use Library\Status;
use Library\Taxonomies;
use WP_HTML_Tag_Processor;
use WP_Post;
use WP_Query;
use WP_Term;

/**
 * Adds the book columns to the books list, sorts it, edits books in quick edit, names the view of the books in the library
 * and shows the read percentage.
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Books_List {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'manage_library_book_posts_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_library_book_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_filter( 'post_column_taxonomy_links', array( $this, 'link_column_terms' ), 10, 3 );
		add_filter( 'manage_edit-library_book_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_filter( 'posts_clauses', array( $this, 'sort_by_meta' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit' ) );
		add_action( 'add_inline_data', array( $this, 'add_inline_data' ) );
		// Before core saves a quick edit, at 1.
		add_action( 'wp_ajax_inline-save', array( $this, 'refuse_isbn' ), 0 );
		add_action( 'save_post_library_book', array( $this, 'save_quick_edit' ) );
		add_filter( 'views_edit-library_book', array( $this, 'add_list_table_views' ) );
		add_filter( 'the_posts', array( $this, 'load_covers' ), 10, 2 );
	}

	/**
	 * Load the covers of the books of the list at once, with their image meta, rather than one by one as each row shows
	 * its own: core does so for the REST API, not for a list table of posts.
	 *
	 * @param WP_Post[] $posts Posts found.
	 * @param WP_Query  $query Query.
	 * @return WP_Post[]
	 */
	public function load_covers( array $posts, WP_Query $query ): array {
		if ( is_admin() && $query->is_main_query() && 'library_book' === $query->get( 'post_type' ) && $posts ) {
			update_post_thumbnail_cache( $query );
		}

		return $posts;
	}

	/**
	 * Add custom columns
	 *
	 * @param array<string,string> $columns Array of columns.
	 * @link https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
	 *
	 * @return array<string,string>
	 */
	public function add_custom_columns( array $columns ): array {
		unset( $columns['date'] );

		$columns = array( 'thumbnail' => __( 'Thumbnail', 'library' ) ) + $columns;

		// The column of the people shows the ones credited, as the credits of a book do.
		$columns['taxonomy-library_person'] = __( 'Authors', 'library' );
		$columns['library_shelf']           = __( 'Shelf', 'library' );
		$columns['library_edition_date']    = Meta::get_label( 'library_edition_date' );
		// The columns of the copy of the reader, on their shelf.
		$columns['library_printed_date'] = Meta::get_label( 'library_printed_date' );
		$columns['library_isbn']         = Meta::get_label( 'library_isbn' );
		$columns['library_read']         = Meta::get_label( 'library_read' );
		// The latest one.
		$columns['library_read_date'] = __( 'Read date', 'library' );
		$columns['library_rating']    = Meta::get_label( 'library_rating' );

		return $columns;
	}

	/**
	 * Render custom columns
	 *
	 * @param string $column_name The column name.
	 * @param int    $post_id The ID of the post.
	 * @link https://developer.wordpress.org/reference/hooks/manage_post-post_type_posts_custom_column/
	 *
	 * @return void
	 */
	public function render_custom_columns( string $column_name, int $post_id ): void {
		$output = '';
		$reader = get_current_user_id();

		switch ( $column_name ) {

			case 'library_shelf':
				$status  = Shelf::get_status( $post_id, get_current_user_id() );
				$output .= $status ? esc_html( $status->label() ) : '—';

				break;
			case 'library_read':
				/* translators: %s: book title. */
				$label = sprintf( __( 'Read: %s', 'library' ), get_the_title( $post_id ) );

				$output .= sprintf(
					'<input type="checkbox" data-part="read" data-post-id="%1$d" aria-label="%2$s"%3$s>',
					$post_id,
					esc_attr( $label ),
					checked( (bool) Shelf::get_read_dates( $post_id, $reader ), true, false )
				);

				break;
			case 'library_edition_date':
			case 'library_printed_date':
				$date = 'library_printed_date' === $column_name ? Shelf::get_printed_date( $post_id, $reader ) : (string) get_post_meta( $post_id, $column_name, true );

				$output .= '' !== $date ? Books::format_partial_date( $date ) : '—';

				break;
			case 'library_isbn':
				$isbn = get_post_meta( $post_id, 'library_isbn', true );

				$output .= $isbn ? esc_html( $isbn ) : '—';

				break;
			case 'library_read_date':
				$read_dates = Shelf::get_read_dates( $post_id, $reader );
				$readings   = count( $read_dates );

				if ( ! $read_dates ) {
					$output .= '—';
					break;
				}

				$read_date = end( $read_dates );
				$output   .= esc_html( '' !== $read_date ? date_i18n( get_option( 'date_format' ), strtotime( $read_date ) ) : __( 'Unknown date', 'library' ) );

				if ( $readings > 1 ) {
					/* translators: %s: number of readings of a book. */
					$output .= ' · ' . esc_html( sprintf( _n( '%s reading', '%s readings', $readings, 'library' ), number_format_i18n( $readings ) ) );
				}

				break;
			case 'library_rating':
				$rating = Readings::get_rating( Shelf::get_readings( $post_id, $reader ) );

				$output .= null !== $rating ? esc_html( Books::format_rating_out_of_ten( $rating ) ) : '—';

				break;
			case 'thumbnail':
				$rating = Readings::get_rating( Shelf::get_readings( $post_id, $reader ) );

				if ( null !== $rating ) {
					/* translators: %s: rating out of 10, e.g. 7.5/10. */
					$title   = sprintf( __( 'Rated %s', 'library' ), Books::format_rating_out_of_ten( $rating ) );
					$output .= '<span class="library-rating-dot" title="' . esc_attr( $title ) . '" aria-hidden="true">' . esc_html( Books::format_rating( $rating ) ) . '</span>';
				}

				$output .= '<span class="library-thumbnail-cell">';
				// The medium size, not cropped, as the cover shows whole: the full image would be a few megabytes a row.
				$thumbnail = get_the_post_thumbnail( $post_id, 'medium', array( 'alt' => '' ) );

				// The title of the row links to the book already: the cover is a larger target for the pointer only.
				if ( $thumbnail ) {
					$output .= '<a href="' . esc_url( (string) get_edit_post_link( $post_id ) ) . '" tabindex="-1" aria-hidden="true">';
					$output .= $thumbnail;
					$output .= '</a>';
				} else {
					$output .= '—';
				}
				$output .= '</span>';

				break;

		}

		$allowed_column_html = array(
			'input' => array(
				'type'         => true,
				'class'        => true,
				'checked'      => true,
				'data-post-id' => true,
				'aria-label'   => true,
			),
			'span'  => array(
				'class'       => true,
				'title'       => true,
				'aria-hidden' => true,
			),
			'a'     => array(
				'href'        => true,
				'tabindex'    => true,
				'aria-hidden' => true,
			),
			'img'   => array(
				'src'      => true,
				'srcset'   => true,
				'sizes'    => true,
				'alt'      => true,
				'class'    => true,
				'width'    => true,
				'height'   => true,
				'loading'  => true,
				'decoding' => true,
			),
		);
		echo wp_kses( $output, $allowed_column_html );
	}

	/**
	 * Add the quick edit values to the hidden inline data of a book row.
	 *
	 * @param WP_Post $post Post.
	 * @see https://developer.wordpress.org/reference/hooks/add_inline_data/
	 */
	public function add_inline_data( WP_Post $post ): void {
		if ( 'library_book' !== $post->post_type ) {
			return;
		}

		foreach ( Meta::QUICK_EDIT_KEYS as $key ) {
			printf( '<div class="%1$s">%2$s</div>', esc_attr( $key ), esc_textarea( (string) get_post_meta( $post->ID, $key, true ) ) );
		}
	}

	/**
	 * Render the book fields of quick edit, once, with the ISBN column.
	 *
	 * @param string $column_name Name of the column to edit.
	 * @see https://developer.wordpress.org/reference/hooks/quick_edit_custom_box/
	 */
	public function render_quick_edit( string $column_name ): void {
		if ( 'library_isbn' !== $column_name ) {
			return;
		}

		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<?php foreach ( Meta::QUICK_EDIT_KEYS as $key ) : ?>
					<label>
						<span class="title"><?php echo esc_html( Meta::get_label( $key ) ); ?></span>
						<span class="input-text-wrap"><?php echo Meta::get_control( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Refuse the quick edit of a book whose ISBN is mistyped, or another book has, as Meta::check_isbn() tells, before
	 * core saves anything: quick edit shows the message in its row, which stays open on the values typed.
	 *
	 * @return void
	 */
	public function refuse_isbn(): void {
		check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

		$post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;

		if ( 'library_book' !== get_post_type( $post_id ) || ! isset( $_POST['library_isbn'] ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$error = Meta::check_isbn( $post_id, sanitize_text_field( wp_unslash( $_POST['library_isbn'] ) ) );

		if ( $error ) {
			wp_die( esc_html( $error->get_error_message() ) );
		}
	}

	/**
	 * Save quick edit.
	 *
	 * Core checks the capability before saving; the nonce tells a quick edit
	 * apart from any other save.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_quick_edit( int $post_id ): void {
		if ( ! check_ajax_referer( 'inlineeditnonce', '_inline_edit', false ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Meta.
		$values = wp_unslash( $_POST );

		Meta::save( $post_id, array_intersect_key( $values, array_flip( Meta::QUICK_EDIT_KEYS ) ) );
	}

	/**
	 * Add the sortable columns; the books list is sorted by edition date by default, the newest editions first.
	 *
	 * @param array<string,string|array<int,string|bool>> $sortable_columns Sortable columns, each a query var or its arguments.
	 *
	 * @return array<string,string|array<int,string|bool>>
	 */
	public function add_sortable_columns( array $sortable_columns ): array {
		$sortable_columns['library_edition_date'] = array( 'library_edition_date', true, '', __( 'Table ordered by edition date.', 'library' ), 'desc' );
		$sortable_columns['library_printed_date'] = array( 'library_printed_date', true, '', __( 'Table ordered by printed date.', 'library' ) );
		$sortable_columns['library_rating']       = array( 'library_rating', true, '', __( 'Table ordered by rating.', 'library' ) );

		return $sortable_columns;
	}

	/**
	 * Sort the books list by edition date, by default, or by the printed date or the rating of the copy of the reader;
	 * books without them come last.
	 *
	 * A core meta query cannot keep the books without the meta without mixing them in: its OR clauses join every other
	 * meta of these books and sort on one of them.
	 *
	 * @param string[] $clauses Query clauses.
	 * @param WP_Query $query   Query.
	 * @return string[]
	 */
	public function sort_by_meta( array $clauses, WP_Query $query ): array {
		if ( ! is_admin() || ! $query->is_main_query() || 'library_book' !== $query->get( 'post_type' ) ) {
			return $clauses;
		}

		$orderby = $query->get( 'orderby' ) ? $query->get( 'orderby' ) : 'library_edition_date';
		$keys    = array(
			'library_edition_date' => 'library_edition_date',
			'library_printed_date' => Shelf::key( 'library_printed_date', get_current_user_id() ),
			'library_rating'       => 'library_rating',
		);

		if ( ! isset( $keys[ $orderby ] ) ) {
			return $clauses;
		}

		global $wpdb;

		$order = 'ASC' === strtoupper( $query->get( 'order' ) ) ? 'ASC' : 'DESC';

		// One row per book, even with the meta saved twice. The values and the titles are sorted on their first 191 characters,
		// more than a date, a rating or a title needs: MySQL 5.7 writes on disk a temporary table with a TEXT column.
		// The ID comes last, for the books of the same date and title to keep their place from one page to the next.
		// The rating of a book is the one of its latest reading rated, no meta of its own.
		$sort               = 'library_rating' === $orderby
			? 'SELECT post_id, rating AS meta_value FROM ( ' . Query::get_ratings_sql() . ' ) AS library_ratings'
			: $wpdb->prepare( "SELECT post_id, MAX( LEFT( meta_value, 191 ) ) AS meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY post_id", $keys[ $orderby ] );
		$clauses['join']   .= " LEFT JOIN ( $sort ) AS library_sort ON library_sort.post_id = {$wpdb->posts}.ID";
		$clauses['orderby'] = "library_sort.meta_value IS NULL, library_sort.meta_value $order, LEFT( {$wpdb->posts}.post_title, 191 ) ASC, {$wpdb->posts}.ID ASC";

		return $clauses;
	}

	/**
	 * Add list table views
	 *
	 * @param array<string,string> $views An array of available list table views.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/views_this-screen-id/
	 *
	 * @return array<string,string>
	 */
	public function add_list_table_views( array $views ): array {
		// Every book of the catalogue is published: the view of core lists them all again.
		unset( $views['publish'] );

		$current = Query::get_shelf( $GLOBALS['wp_query'] );
		$counts  = self::count_shelved();
		$shelves = array(
			implode( ',', Status::get_values() ) => array( __( 'My shelves', 'library' ), array_sum( $counts ) ),
		);

		foreach ( Status::cases() as $status ) {
			$shelves[ $status->value ] = array( $status->label(), $counts[ $status->value ] ?? 0 );
		}

		if ( $current ) {
			$views['all'] = str_replace( ' class="current" aria-current="page"', '', $views['all'] ?? '' );
		}

		foreach ( $shelves as $shelf => [ $label, $count ] ) {
			$is_current = explode( ',', $shelf ) === $current;

			$views[ "library-shelf-$shelf" ] = sprintf(
				'<a href="%1$s"%2$s>%3$s <span class="count">(%4$s)</span></a>',
				esc_url( add_query_arg( 'library_shelf', $shelf, admin_url( 'edit.php?post_type=library_book' ) ) ),
				$is_current ? ' class="current" aria-current="page"' : '',
				esc_html( $label ),
				esc_html( number_format_i18n( $count ) )
			);
		}

		$stats = Stats::get_reading_stats();

		/* translators: %s: reading percentage. */
		$label = sprintf( __( '%s%% read', 'library' ), number_format_i18n( $stats['percent'] ) );

		$views['library-read-percentage'] = sprintf(
			'<span data-part="read-percentage" data-loading-text="%1$s">%2$s</span>',
			esc_attr__( 'Loading percent read...', 'library' ),
			esc_html( $label )
		);

		return $views;
	}

	/**
	 * Books on the shelves of the current reader, by status, in one query.
	 *
	 * @return array<string,int>
	 */
	private static function count_shelved(): array {
		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin books list only.
			$wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT( * ) AS books
				FROM {$wpdb->postmeta} AS pm
				INNER JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id AND p.post_type = 'library_book' AND p.post_status = 'publish'
				WHERE pm.meta_key = %s
				GROUP BY pm.meta_value",
				Shelf::key( Shelf::STATUS, get_current_user_id() )
			)
		);

		return array_map( 'intval', wp_list_pluck( $rows, 'books', 'status' ) );
	}

	/**
	 * URL of the books of a term in the books list, on the shelves of a view, or else in the whole catalogue.
	 *
	 * @param WP_Term $term  Term of a book taxonomy.
	 * @param string  $shelf Shelves of the view, statuses separated with commas, empty for the whole catalogue.
	 * @return string
	 */
	public static function get_term_url( WP_Term $term, string $shelf = '' ): string {
		$query_args = array_filter(
			array(
				'post_type'     => 'library_book',
				'library_shelf' => $shelf,
				$term->taxonomy => $term->slug,
			)
		);

		return add_query_arg( $query_args, admin_url( 'edit.php' ) );
	}

	/**
	 * Links of the people, publishers and languages columns of the books list.
	 *
	 * The people are those credited for the book, as its credits name them: its authors, or the publishing directors of
	 * a collective work, else none. A collection shows as its publisher, once. The links keep the shelves of the current
	 * view: core links to the whole catalogue.
	 *
	 * @param string[]  $term_links Term links.
	 * @param string    $taxonomy   Taxonomy.
	 * @param WP_Term[] $terms      Terms.
	 * @return string[]
	 */
	public function link_column_terms( array $term_links, string $taxonomy, array $terms ): array {
		$shelf = implode( ',', Query::get_shelf( $GLOBALS['wp_query'] ) );

		if ( ! in_array( $taxonomy, Taxonomies::TAXONOMIES, true ) ) {
			return $term_links;
		}

		if ( 'library_publisher' === $taxonomy ) {
			$terms = Taxonomies::get_top_level_terms( $terms );
		} elseif ( 'library_person' === $taxonomy ) {
			$terms = Taxonomies::get_credited_people( (int) get_the_ID() );
		} elseif ( '' === $shelf ) {
			return $term_links;
		}

		if ( ! $terms ) {
			// As core shows a column without terms.
			$no_terms = get_taxonomy( $taxonomy )->labels->no_terms;

			return array( '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . esc_html( $no_terms ) . '</span>' );
		}

		$links = array_map( fn( WP_Term $term ) => sprintf( '<a href="%s">%s</a>', esc_url( self::get_term_url( $term, $shelf ) ), esc_html( $term->name ) ), $terms );

		if ( 'library_person' === $taxonomy && Posts::is_collective( (int) get_the_ID() ) ) {
			$last_link = array_key_last( $links );

			$links[ $last_link ] = Books::format_directors( $links[ $last_link ] );
		}

		return $links;
	}
}
