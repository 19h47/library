<?php
/**
 * Book post type, columns, quick edit, and list views.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers book post type and admin UI (columns, quick edit, save).
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Posts {

	/**
	 * Status of the books read but not kept: hidden from the books list and the site, counted in the reading stats.
	 *
	 * @var string
	 */
	public const NOT_KEPT = 'library-not-kept';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'add_thumbnail_support' ), 11 );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_post_status' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_not_kept' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'not_kept' ), 10, 2 );
		add_filter( 'dashboard_glance_items', array( $this, 'at_a_glance' ) );
		add_action( 'manage_book_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'render_quick_edit' ) );
		add_action( 'add_inline_data', array( $this, 'add_inline_data' ) );
		add_filter( 'manage_book_posts_columns', array( $this, 'add_custom_columns' ) );
		add_filter( 'manage_edit-book_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'posts_clauses', array( $this, 'sort_by_meta' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'slug' ), 10, 2 );
		add_action( 'save_post_book', array( $this, 'save_quick_edit' ) );
		add_filter( 'the_title', array( $this, 'title' ), 10, 2 );
		add_filter( 'views_edit-book', array( $this, 'add_list_table_views' ) );
	}

	/**
	 * Enable featured images, i.e. covers, for books whatever the theme supports.
	 *
	 * The featured image box needs both the post type and the theme support. Core merges this
	 * with the theme support, and keeps it when the theme supports every post type.
	 *
	 * @return void
	 */
	public function add_thumbnail_support(): void {
		add_theme_support( 'post-thumbnails', array( 'book' ) );
	}

	/**
	 * Register the status of the books read but not kept.
	 *
	 * Core then leaves them out of the "All" view, its count, the site and search,
	 * and lists them in their own view of the books list.
	 *
	 * @return void
	 */
	public function register_post_status(): void {
		register_post_status(
			self::NOT_KEPT,
			array(
				'label'                     => _x( 'Not kept', 'book status', 'library' ),
				/* translators: %s: number of books. */
				'label_count'               => _n_noop( 'Not kept <span class="count">(%s)</span>', 'Not kept <span class="count">(%s)</span>', 'library' ),
				'public'                    => false,
				'protected'                 => true,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => true,
			)
		);
	}

	/**
	 * Render the "Not kept" checkbox in the publish box, under the status.
	 *
	 * The status menu of the publish box only offers core statuses and has no filter.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_not_kept( WP_Post $post ): void {
		if ( 'book' !== $post->post_type ) {
			return;
		}
		?>
		<div class="misc-pub-section misc-pub-library-not-kept">
			<?php wp_nonce_field( 'library_not_kept', 'library_not_kept_nonce' ); ?>
			<input
				type="checkbox"
				id="library-not-kept"
				name="not_kept"
				value="1"
				aria-describedby="library-not-kept-description"
				<?php checked( self::NOT_KEPT === $post->post_status ); ?>
			>
			<label for="library-not-kept"><?php echo esc_html_x( 'Not kept', 'book status', 'library' ); ?></label>
			<p class="description" id="library-not-kept-description">
				<?php esc_html_e( 'Read but not in my library: hidden from the books list and the site, counted in the reading stats.', 'library' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Apply the "Not kept" checkbox of the publish box to the book status.
	 *
	 * The checkbox decides over the status menu and the button pressed; unchecking publishes the book.
	 *
	 * @param array $data    Slashed post data to insert.
	 * @param array $postarr Slashed submitted data, with the form fields.
	 * @return array
	 */
	public function not_kept( array $data, array $postarr ): array {
		$nonce = isset( $postarr['library_not_kept_nonce'] ) ? sanitize_key( wp_unslash( $postarr['library_not_kept_nonce'] ) ) : '';

		if ( 'book' !== $data['post_type'] || ! wp_verify_nonce( $nonce, 'library_not_kept' ) || in_array( $data['post_status'], array( 'trash', 'auto-draft' ), true ) ) {
			return $data;
		}

		$not_kept = ! empty( $postarr['not_kept'] );

		if ( $not_kept ) {
			$data['post_status'] = self::NOT_KEPT;
		} elseif ( self::NOT_KEPT === $data['post_status'] ) {
			$data['post_status'] = 'publish';
		}

		return $data;
	}

	/**
	 * Build the slug from the title and the ISBN, or the ISSN and volume number.
	 *
	 * Runs before the post is written, so no second update is needed.
	 *
	 * @param array $data    Slashed post data about to be saved.
	 * @param array $postarr Slashed submitted data, with the form fields.
	 * @return array
	 */
	public function slug( array $data, array $postarr ): array {
		if ( 'book' !== $data['post_type'] ) {
			return $data;
		}

		$fields = array_map(
			fn( string $key ) => sanitize_text_field( wp_unslash( $postarr[ $key ] ?? '' ) ),
			array(
				'title'         => 'post_title',
				'isbn'          => 'isbn',
				'issn'          => 'issn',
				'volume_number' => 'volume_number',
			)
		);

		if ( $fields['issn'] && $fields['volume_number'] ) {
			$parts = array( $fields['title'], $fields['issn'], $fields['volume_number'] );
		} elseif ( $fields['isbn'] ) {
			$parts = array( $fields['title'], $fields['isbn'] );
		} else {
			return $data;
		}

		$slug    = sanitize_title( implode( '-', $parts ) );
		$post_id = (int) ( $postarr['ID'] ?? 0 );

		$data['post_name'] = wp_unique_post_slug( $slug, $post_id, $data['post_status'], 'book', $data['post_parent'] );

		return $data;
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

		$columns['edition_date'] = __( 'Edition date', 'library' );
		$columns['isbn']         = __( 'ISBN', 'library' );
		$columns['read']         = __( 'Read', 'library' );
		$columns['read_date']    = __( 'Read date', 'library' );
		$columns['rating']       = __( 'Rating', 'library' );

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
					checked( self::is_read( $post_id ), true, false )
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
				$read_date = get_post_meta( $post_id, 'read_date', true );

				$output .= $read_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $read_date ) ) ) : '—';

				break;
			case 'rating':
				$rating = get_post_meta( $post_id, 'rating', true );

				$output .= '' !== $rating ? esc_html( $rating ) . ' / 10' : '—';

				break;
			case 'thumbnail':
				$rating = get_post_meta( $post_id, 'rating', true );

				if ( '' !== $rating ) {
					$output .= '<span class="library-rating-dot" title="' . esc_attr__( 'Rated', 'library' ) . ' ' . esc_attr( $rating ) . '/10" aria-hidden="true">' . esc_html( $rating ) . '</span>';
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

		foreach ( Library_Meta::QUICK_EDIT_KEYS as $key ) {
			$value = (string) get_post_meta( $post->ID, $key, true );

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

		$fields = array(
			'isbn'      => sprintf( '<input type="text" name="isbn" pattern="%s">', esc_attr( Library_Meta::ISBN_PATTERN ) ),
			'pages'     => '<input type="number" name="pages" min="1">',
			'read_date' => '<input type="date" name="read_date">',
			'rating'    => '<input type="number" name="rating" min="1" max="10" step="0.5">',
		);
		$labels = array(
			'isbn'      => __( 'ISBN', 'library' ),
			'pages'     => __( 'Pages', 'library' ),
			'read_date' => __( 'Read date', 'library' ),
			'rating'    => __( 'Rating', 'library' ),
		);
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<?php foreach ( $fields as $key => $input ) : ?>
					<label>
						<span class="title"><?php echo esc_html( $labels[ $key ] ); ?></span>
						<span class="input-text-wrap"><?php echo $input; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, attributes escaped above. ?></span>
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
		Library_Meta::save( $post_id, wp_unslash( $_POST ) );
	}

	/**
	 * Register the custom post type.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 */
	public function register_post_type() {

		$labels = array(
			'name'                  => __( 'Books', 'library' ),
			'singular_name'         => __( 'Book', 'library' ),
			'menu_name'             => __( 'Books', 'library' ),
			'name_admin_bar'        => __( 'Book', 'library' ),
			'archives'              => __( 'Book Archives', 'library' ),
			'attributes'            => __( 'Item Attributes', 'library' ),
			'parent_item_colon'     => __( 'Parent Book:', 'library' ),
			'all_items'             => __( 'All books', 'library' ),
			'add_new_item'          => __( 'Add New Book', 'library' ),
			'add_new'               => __( 'Add New', 'library' ),
			'new_item'              => __( 'New Book', 'library' ),
			'edit_item'             => __( 'Edit book', 'library' ),
			'update_item'           => __( 'Update book', 'library' ),
			'view_item'             => __( 'View Book', 'library' ),
			'view_items'            => __( 'View Books', 'library' ),
			'search_items'          => __( 'Search Book', 'library' ),
			'not_found'             => __( 'Not found', 'library' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'library' ),
			'featured_image'        => __( 'Featured Image', 'library' ),
			'set_featured_image'    => __( 'Set featured image', 'library' ),
			'remove_featured_image' => __( 'Remove featured image', 'library' ),
			'use_featured_image'    => __( 'Use as featured image', 'library' ),
			'insert_into_item'      => __( 'Insert into book', 'library' ),
			'uploaded_to_this_item' => __( 'Updloaded to this book', 'library' ),
			'items_list'            => __( 'Books list', 'library' ),
			'items_list_navigation' => __( 'Books list navigation', 'library' ),
			'filter_items_list'     => __( 'Filtrer books list', 'library' ),
		);

		$supports   = array( 'title', 'thumbnail', 'custom-fields' );
		$taxonomies = array( 'library-author', 'library-publisher' );

		$rewrite = array(
			'slug'       => 'books',
			'with_front' => false,
		);

		$args = array(
			'label'               => __( 'Books', 'library' ),
			'description'         => __( 'Book description', 'library' ),
			'labels'              => $labels,
			'supports'            => $supports,
			'taxonomies'          => $taxonomies,
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 40,
			'menu_icon'           => 'dashicons-book',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'rewrite'             => $rewrite,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rest_base'           => 'books',
		);

		/**
		 * Filters the arguments of the book post type, before it is registered.
		 *
		 * @since 0.0.0
		 *
		 * @param array $args Arguments for register_post_type(), with the `books` rewrite slug and the `books` REST base.
		 */
		$args = apply_filters( 'library_post_type_args', $args );

		register_post_type( 'book', $args );
	}

	/**
	 * Add the number of published books to the "At a Glance" dashboard widget.
	 *
	 * @param string[] $items Items.
	 * @return string[]
	 */
	public function at_a_glance( array $items ): array {
		$count = (int) wp_count_posts( 'book' )->publish;

		if ( ! $count ) {
			return $items;
		}

		/* translators: %s: number of books. */
		$text = sprintf( _n( '%s book', '%s books', $count, 'library' ), number_format_i18n( $count ) );

		if ( ! current_user_can( get_post_type_object( 'book' )->cap->edit_posts ) ) {
			$items[] = sprintf( '<span class="book-count">%s</span>', esc_html( $text ) );

			return $items;
		}

		$items[] = sprintf(
			'<a class="book-count" href="%1$s">%2$s</a>',
			esc_url( admin_url( 'edit.php?post_type=book' ) ),
			esc_html( $text )
		);

		return $items;
	}

	/**
	 * Whether a book has been read; the read date is optional.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_read( int $post_id ): bool {
		return (bool) get_post_meta( $post_id, 'read', true );
	}

	/**
	 * Sortable columns; the books list is sorted by edition date by default.
	 *
	 * @param array $sortable_columns Sortable columns.
	 *
	 * @return array
	 */
	public function sortable_columns( array $sortable_columns ): array {
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

		// One row per book, even with the meta saved twice.
		$clauses['join']   .= $wpdb->prepare( " LEFT JOIN ( SELECT post_id, MAX( meta_value ) AS meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s GROUP BY post_id ) AS library_sort ON library_sort.post_id = {$wpdb->posts}.ID", $orderby );
		$clauses['orderby'] = "library_sort.meta_value IS NULL, {$values[ $orderby ]} $order, {$wpdb->posts}.post_title ASC";

		return $clauses;
	}

	/**
	 * Show a book title with its series, volume and subtitle, as everywhere else.
	 *
	 * @param string $title   The post title.
	 * @param int    $post_id The post ID; some callers of the_title omit it.
	 * @return string
	 */
	public function title( string $title, int $post_id = 0 ): string {
		if ( ! $post_id || 'book' !== get_post_type( $post_id ) ) {
			return $title;
		}

		return Library_Books::title( $post_id, $title );
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
		$stats = self::get_reading_stats();

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
	 * Reading stats: books read out of the books in the library, i.e. without trash, auto-drafts and books not kept.
	 *
	 * @return array{read:int,total:int,percent:int}
	 */
	public static function get_reading_stats(): array {
		$counts = (array) wp_count_posts( 'book' );
		unset( $counts['trash'], $counts['auto-draft'], $counts[ self::NOT_KEPT ] );

		$total = (int) array_sum( $counts );

		// The list table sort filters must not alter this count.
		$read_query = new WP_Query(
			array(
				'post_type'        => 'book',
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'meta_key'         => 'read', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Single count on a small dataset.
				'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Same.
			)
		);

		$read    = (int) $read_query->found_posts;
		$percent = $total > 0 ? (int) round( $read / $total * 100 ) : 0;

		return array(
			'read'    => $read,
			'total'   => $total,
			'percent' => $percent,
		);
	}
}
