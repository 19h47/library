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

/**
 * Adds the book columns to the books list, sorts it, edits books in quick edit, names the view of the books in the library
 * and shows the read percentage.
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Books_List {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'manage_book_posts_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_book_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_filter( 'post_column_taxonomy_links', array( $this, 'link_column_terms' ), 10, 3 );
		add_filter( 'manage_edit-book_sortable_columns', array( $this, 'add_sortable_columns' ) );
		add_filter( 'posts_clauses', array( $this, 'sort_by_meta' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit' ) );
		add_action( 'add_inline_data', array( $this, 'add_inline_data' ) );
		add_action( 'save_post_book', array( $this, 'save_quick_edit' ) );
		add_filter( 'views_edit-book', array( $this, 'add_list_table_views' ) );
		add_action( 'current_screen', array( $this, 'label_published_view' ) );
	}

	/**
	 * Add custom columns
	 *
	 * @param array $columns Array of columns.
	 * @link https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
	 *
	 * @return array
	 */
	public function add_custom_columns( array $columns ): array {
		unset( $columns['date'] );

		$columns = array( 'thumbnail' => __( 'Thumbnail', 'library' ) ) + $columns;

		$columns['edition_date'] = Library_Meta::label( 'edition_date' );
		$columns['isbn']         = Library_Meta::label( 'isbn' );
		$columns['read']         = Library_Meta::label( 'read' );
		// The latest one.
		$columns['read_date'] = __( 'Read date', 'library' );
		$columns['rating']    = Library_Meta::label( 'rating' );

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

		switch ( $column_name ) {

			case 'read':
				/* translators: %s: book title. */
				$label = sprintf( __( 'Read: %s', 'library' ), get_the_title( $post_id ) );

				$output .= sprintf(
					'<input type="checkbox" class="js-library-checkbox" data-post-id="%1$d" aria-label="%2$s"%3$s>',
					$post_id,
					esc_attr( $label ),
					checked( Library_Posts::is_read( $post_id ), true, false )
				);

				break;
			case 'edition_date':
				$edition_date = get_post_meta( $post_id, 'edition_date', true );

				if ( $edition_date ) {
					$output .= date_i18n( get_option( 'date_format' ), strtotime( $edition_date ) );
				} else {
					$output .= '—';
				}

				break;
			case 'isbn':
				$isbn = get_post_meta( $post_id, 'isbn', true );

				$output .= $isbn ? esc_html( $isbn ) : '—';

				break;
			case 'read_date':
				$read_dates = Library_Meta::get_read_dates( $post_id );
				$readings   = count( $read_dates );

				if ( ! $read_dates ) {
					$output .= '—';
					break;
				}

				$output .= esc_html( date_i18n( get_option( 'date_format' ), strtotime( end( $read_dates ) ) ) );

				if ( $readings > 1 ) {
					/* translators: %s: number of readings of a book. */
					$output .= ' · ' . esc_html( sprintf( _n( '%s reading', '%s readings', $readings, 'library' ), number_format_i18n( $readings ) ) );
				}

				break;
			case 'rating':
				$rating = get_post_meta( $post_id, 'rating', true );

				$output .= '' !== $rating ? esc_html( Library_Books::rating_text( (float) $rating ) ) . ' / 10' : '—';

				break;
			case 'thumbnail':
				$rating = get_post_meta( $post_id, 'rating', true );

				if ( '' !== $rating ) {
					$rating_text = Library_Books::rating_text( (float) $rating );
					$output     .= '<span class="library-rating-dot" title="' . esc_attr__( 'Rated', 'library' ) . ' ' . esc_attr( $rating_text ) . '/10" aria-hidden="true">' . esc_html( $rating_text ) . '</span>';
				}

				$output   .= '<span class="library-thumbnail-cell">';
				$thumbnail = get_the_post_thumbnail( $post_id );

				if ( $thumbnail ) {
					$output .= '<a href="' . esc_attr( get_edit_post_link( $post_id ) ) . '">';
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
			'a'     => array( 'href' => true ),
			'img'   => array(
				'src'      => true,
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
		if ( 'book' !== $post->post_type ) {
			return;
		}

		$read_dates = Library_Meta::get_read_dates( $post->ID );

		foreach ( Library_Meta::QUICK_EDIT_KEYS as $key ) {
			// Quick edit edits the latest reading.
			$value = 'read_date' === $key ? (string) end( $read_dates ) : (string) get_post_meta( $post->ID, $key, true );

			printf( '<div class="library_%1$s">%2$s</div>', esc_attr( $key ), esc_textarea( $value ) );
		}
	}

	/**
	 * Render the book fields of quick edit, once, with the ISBN column.
	 *
	 * @param string $column_name Name of the column to edit.
	 * @see https://developer.wordpress.org/reference/hooks/quick_edit_custom_box/
	 */
	public function render_quick_edit( string $column_name ): void {
		if ( 'isbn' !== $column_name ) {
			return;
		}

		// Quick edit edits the latest reading.
		$labels = array( 'read_date' => __( 'Last read date', 'library' ) );
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<?php foreach ( Library_Meta::QUICK_EDIT_KEYS as $key ) : ?>
					<label>
						<span class="title"><?php echo esc_html( $labels[ $key ] ?? Library_Meta::label( $key ) ); ?></span>
						<span class="input-text-wrap"><?php echo Library_Meta::control( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</fieldset>
		<?php
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Library_Meta.
		$values = wp_unslash( $_POST );

		if ( isset( $values['read_date'] ) ) {
			$values['read_date'] = Library_Meta::replace_latest_read_date( $post_id, (string) $values['read_date'] );
		}

		Library_Meta::save( $post_id, $values );
	}

	/**
	 * Add the sortable columns; the books list is sorted by edition date by default.
	 *
	 * @param array $sortable_columns Sortable columns.
	 *
	 * @return array
	 */
	public function add_sortable_columns( array $sortable_columns ): array {
		$sortable_columns['edition_date'] = array( 'edition_date', true, '', __( 'Table ordered by edition date.', 'library' ), 'desc' );
		$sortable_columns['rating']       = array( 'rating', true, '', __( 'Table ordered by rating.', 'library' ) );

		return $sortable_columns;
	}

	/**
	 * Sort the books list by edition date, by default, or by rating; books without them come last.
	 *
	 * The post date is the date added. A core meta query cannot keep the books without the meta
	 * without mixing them in: its OR clauses join every other meta of these books and sort on one of them.
	 *
	 * @param string[] $clauses Query clauses.
	 * @param WP_Query $query   Query.
	 * @return string[]
	 */
	public function sort_by_meta( array $clauses, WP_Query $query ): array {
		if ( ! is_admin() || ! $query->is_main_query() || 'book' !== $query->get( 'post_type' ) ) {
			return $clauses;
		}

		$orderby = $query->get( 'orderby' ) ? $query->get( 'orderby' ) : 'edition_date';
		$values  = array(
			'edition_date' => 'library_sort.meta_value',
			'rating'       => 'CAST( library_sort.meta_value AS DECIMAL(3,1) )',
		);

		if ( ! isset( $values[ $orderby ] ) ) {
			return $clauses;
		}

		global $wpdb;

		$order = 'ASC' === strtoupper( $query->get( 'order' ) ) ? 'ASC' : 'DESC';

		// One row per book, even with the meta saved twice. The values and the titles are sorted on their first 191 characters,
		// more than a date, a rating or a title needs: MySQL 5.7 writes on disk a temporary table with a TEXT column.
		// The ID comes last, for the books of the same date and title to keep their place from one page to the next.
		$clauses['join']   .= $wpdb->prepare( " LEFT JOIN ( SELECT post_id, MAX( LEFT( meta_value, 191 ) ) AS meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY post_id ) AS library_sort ON library_sort.post_id = {$wpdb->posts}.ID", $orderby );
		$clauses['orderby'] = "library_sort.meta_value IS NULL, {$values[ $orderby ]} $order, LEFT( {$wpdb->posts}.post_title, 191 ) ASC, {$wpdb->posts}.ID ASC";

		return $clauses;
	}

	/**
	 * Add list table views
	 *
	 * @param array $views An array of available list table views.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/views_this-screen-id/
	 *
	 * @return array
	 */
	public function add_list_table_views( array $views ): array {
		$stats = Library_Stats::get_reading_stats();

		/* translators: %s: reading percentage. */
		$label = sprintf( __( '%s%% read', 'library' ), number_format_i18n( $stats['percent'] ) );

		$views['library-read-percentage'] = sprintf(
			'<span class="js-library-read-percentage" data-loading-text="%1$s">%2$s</span>',
			esc_attr__( 'Loading percent read...', 'library' ),
			esc_html( $label )
		);

		return $views;
	}

	/**
	 * Name the view of the published books after their status in the forms of a book: in the library.
	 *
	 * The publish status belongs to core, which shares it with the posts and the pages: its label changes on the books list only.
	 *
	 * @param WP_Screen $screen Current screen.
	 * @return void
	 */
	public function label_published_view( WP_Screen $screen ): void {
		if ( 'edit-book' !== $screen->id ) {
			return;
		}

		/* translators: %s: number of books. */
		get_post_status_object( 'publish' )->label_count = _n_noop( 'In the library <span class="count">(%s)</span>', 'In the library <span class="count">(%s)</span>', 'library' );
	}

	/**
	 * Links of the authors, publishers and languages columns of the books list.
	 *
	 * A collection shows as its publisher, once. The links keep the status of the current view:
	 * core links to the "All" view, which leaves out the books not kept, so from their view they listed nothing.
	 *
	 * @param string[]  $term_links Term links.
	 * @param string    $taxonomy   Taxonomy.
	 * @param WP_Term[] $terms      Terms.
	 * @return string[]
	 */
	public function link_column_terms( array $term_links, string $taxonomy, array $terms ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
		$status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';

		if ( ! in_array( $taxonomy, Library_Taxonomies::TAXONOMIES, true ) ) {
			return $term_links;
		}

		if ( 'library-publisher' === $taxonomy ) {
			$terms = Library_Taxonomies::get_top_level_terms( $terms );
		} elseif ( '' === $status ) {
			return $term_links;
		}

		return array_map(
			function ( WP_Term $term ) use ( $taxonomy, $status ) {
				$query_args = array_filter(
					array(
						'post_type'   => 'book',
						'post_status' => $status,
						$taxonomy     => $term->slug,
					)
				);
				$url        = add_query_arg( $query_args, admin_url( 'edit.php' ) );

				return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $term->name ) );
			},
			$terms
		);
	}
}
