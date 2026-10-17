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
	 * Post type name.
	 *
	 * @since  1.0.0
	 * @var    string $post_type
	 */
	protected $post_type = 'book';

	/**
	 * Plugin identifier.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_name
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_version
	 */
	private $plugin_version;

	/**
	 * Meta key used when sorting by date_published (avoids duplicate postmeta join).
	 *
	 * @since  1.0.0
	 * @var    string|null $orderby_meta_key
	 */
	private $orderby_meta_key = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name    Plugin identifier.
	 * @param string $plugin_version Plugin version.
	 */
	public function __construct( $plugin_name, $plugin_version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_filter( 'posts_join', array( $this, 'posts_join_book_date_published' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'posts_orderby_book_date_published' ), 10, 2 );
		add_filter( 'dashboard_glance_items', array( $this, 'at_a_glance' ) );
		add_action( 'admin_head', array( $this, 'css' ) );
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

		$columns['date_published'] = __( 'Date published', 'library' );
		$columns['isbn']           = __( 'ISBN', 'library' );
		$columns['read']           = __( 'Read', 'library' );
		$columns['read_date']      = __( 'Read date', 'library' );
		$columns['rating']         = __( 'Rating', 'library' );
		$columns['book_editions']  = __( 'Book Editions', 'library' );

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
					'<input id="%1$s-%2$s-%3$d" type="checkbox" class="js-library-checkbox" data-post-id="%3$d" aria-label="%4$s"%5$s>',
					$this->plugin_name,
					$column_name,
					$post_id,
					esc_attr( $label ),
					checked( self::is_read( $post_id ), true, false )
				);

				break;
			case 'date_published':
				$date_published = get_post_meta( $post_id, 'date_published', true );

				if ( $date_published ) {
					$output .= date_i18n( get_option( 'date_format' ), strtotime( $date_published ) );
				} else {
					$output .= '—';
				}

				break;
			case 'isbn':
				$isbn = get_post_meta( $post_id, 'isbn', true );

				if ( $isbn ) {
					$output .= '<span id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '">' . $isbn . '</span>';
				} else {
					$output .= '<span id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '"></span>—';
				}

				break;
			case 'read_date':
				$read_date = get_post_meta( $post_id, 'read_date', true );
				$output   .= '<span id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '" class="hidden">' . esc_html( (string) $read_date ) . '</span>';
				$output   .= '<span class="js-library-read-date-label">' . ( $read_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $read_date ) ) ) : '—' ) . '</span>';
				break;
			case 'rating':
				$rating_val = get_post_meta( $post_id, 'rating', true );
				$output    .= '<span id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '">' . esc_html( (string) $rating_val ) . '</span>';
				$output    .= ( '' !== $rating_val && null !== $rating_val ) ? ' / 10' : '—';
				break;
			case 'book_editions':
				$book_editions = get_post_meta( $post_id, 'book_editions', true );

				$output .= '<input id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '" name="' . $column_name . '" value="' . $book_editions . '" type="hidden">';

				break;

			case 'thumbnail':
				$rating_val = get_post_meta( $post_id, 'rating', true );
				$has_rating = ( '' !== $rating_val && null !== $rating_val && is_numeric( $rating_val ) && (float) $rating_val >= 1 && (float) $rating_val <= 10 );

				if ( $has_rating ) {
					$rating_display = (string) ( (float) $rating_val === (int) (float) $rating_val ? (int) (float) $rating_val : (float) $rating_val );
					$output        .= '<span class="library-rating-dot" title="' . esc_attr__( 'Rated', 'library' ) . ' ' . esc_attr( $rating_display ) . '/10" aria-hidden="true">' . esc_html( $rating_display ) . '</span>';
				}

				$output .= '<span class="library-thumbnail-cell">';
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
				'id'           => true,
				'type'         => true,
				'name'         => true,
				'value'        => true,
				'class'        => true,
				'checked'      => true,
				'data-post-id' => true,
				'aria-label'   => true,
			),
			'span'  => array(
				'id'            => true,
				'class'         => true,
				'style'         => true,
				'title'         => true,
				'aria-hidden'   => true,
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
	 * Render quick edit
	 *
	 * @param string $column_name Name of the column to edit.
	 * @param string $post_type   The post type slug, or current screen name if this is a taxonomy list table.
	 * @param string $taxonomy    The taxonomy name, if any.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/quick_edit_custom_box/
	 */
	public function render_quick_edit( string $column_name, string $post_type, string $taxonomy ): void {
		unset( $post_type, $taxonomy );

		$html = '';

		switch ( $column_name ) {
			case 'isbn':
				$html .= '<fieldset class="inline-edit-col"><div class="inline-edit-col">';
				$html .= '<label class="inline-edit-isbn">';
				$html .= '<span class="title">' . esc_html__( 'ISBN', 'library' ) . '</span>';
				$html .= '<input pattern="(?:(?=.{17}$)97[89][ -](?:[0-9]+[ -]){2}[0-9]+[ -][0-9]|97[89][0-9]{10}|(?=.{13}$)(?:[0-9]+[ -]){2}[0-9]+[ -][0-9Xx]|[0-9]{9}[0-9Xx])" name="isbn" class="inline-edit-isbn-input js-library-isbn" type="text" placeholder="' . esc_html__( 'ISBN', 'library' ) . '" value="">';
				$html .= '</label>';
				$html .= '</div></fieldset>';

				break;
			case 'book_editions':
				$html .= '<fieldset class="inline-edit-col"><div class="inline-edit-col">';
				$html .= '<label class="inline-edit-book-editions">';
				$html .= '<span class="title">' . esc_html__( 'Book editions', 'library' ) . '</span>';
				$html .= '<input name="book_editions" class="inline-edit-book-editions-input js-library-book-editions" type="text" placeholder="' . esc_html__( 'Book editions', 'library' ) . '" value="">';
				$html .= '</label>';
				$html .= '</div></fieldset>';

				break;
			case 'read_date':
				$html .= '<fieldset class="inline-edit-col"><div class="inline-edit-col">';
				$html .= '<label class="inline-edit-read-date">';
				$html .= '<span class="title">' . esc_html__( 'Read date', 'library' ) . '</span>';
				$html .= '<input name="read_date" class="inline-edit-read-date-input js-library-read-date" type="date" value="">';
				$html .= '</label>';
				$html .= '</div></fieldset>';

				break;
			case 'rating':
				$html .= '<fieldset class="inline-edit-col"><div class="inline-edit-col">';
				$html .= '<label class="inline-edit-rating">';
				$html .= '<span class="title">' . esc_html__( 'Rating', 'library' ) . '</span>';
				$html .= '<input name="rating" class="inline-edit-rating-input js-library-rating" type="number" min="1" max="10" step="0.5" placeholder="1–10" value="">';
				$html .= '</label>';
				$html .= '</div></fieldset>';

				break;
		}

		$allowed_quick_edit_html = array(
			'fieldset' => array( 'class' => true ),
			'div'      => array( 'class' => true ),
			'label'    => array( 'class' => true ),
			'span'     => array( 'class' => true ),
			'input'    => array(
				'name'        => true,
				'class'       => true,
				'type'        => true,
				'placeholder' => true,
				'value'       => true,
				'pattern'     => true,
				'min'         => true,
				'max'         => true,
				'step'        => true,
			),
		);
		echo wp_kses( $html, $allowed_quick_edit_html );
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

		$args = apply_filters( 'library_post_type_args', $args );

		register_post_type( $this->post_type, $args );
	}

	/**
	 * "At a glance" items (dashboard widget): add the testimony.
	 *
	 * @param arr $items Items.
	 */
	public function at_a_glance( $items ) {
		$post_status = 'publish';
		$object      = get_post_type_object( $this->post_type );

		$num_posts = wp_count_posts( $this->post_type );

		if ( ! $num_posts || ! isset( $num_posts->{ $post_status } ) || 0 === (int) $num_posts->{ $post_status } ) {
			return $items;
		}

		$count = (int) $num_posts->{ $post_status };
		$label = 1 === $count
		? strtolower( $object->labels->singular_name )
		: strtolower( $object->labels->name );
		$text  = sprintf(
		/* translators: %1$s: number, %2$s: post type name (singular or plural), %3$s: pending prefix */
			_n( '%1$s %3$s%2$s', '%1$s %3$s%2$s', $count, 'library' ),
			number_format_i18n( $count ),
			$label,
			'pending' === $post_status ? 'Pending ' : ''
		);

		if ( current_user_can( $object->cap->edit_posts ) ) {
			$items[] = sprintf(
				'<a class="%1$s-count" href="edit.php?post_status=%2$s&post_type=%1$s">%3$s</a>',
				$this->post_type,
				$post_status,
				$text
			);
		} else {
			$items[] = sprintf( '<span class="%1$s-count">%s</span>', $text );
		}

		return $items;
	}

	/**
	 * CSS
	 */
	public function css() {
		global $typenow;

		echo '<style>#dashboard_right_now .book-count:before { content: "\f330"; }</style>';

		if ( $this->post_type !== $typenow ) {
			return false;
		}

		?>
		<style>
			.fixed .column-thumbnail {
				position: relative;
			}

			.fixed .column-thumbnail::before {
				display: none;
			}

			.fixed .column-thumbnail .library-thumbnail-cell {
				display: inline-block;
			}

			.fixed .column-thumbnail .library-rating-dot {
				position: absolute;
				top: 4px;
				left: 4px;
				display: flex;
				align-items: center;
				justify-content: center;
				min-width: 18px;
				height: 18px;
				padding: 0 4px;
				box-sizing: border-box;
				font-size: 11px;
				font-weight: 600;
				line-height: 1;
				color: #fff;
				background: #2271b1;
				border-radius: 50%;
			}

			.fixed .column-read {
				width: 80px;
				text-align: right;
			}

			.fixed .column-read input {
				margin: 0;
			}

			.fixed .column-book_editions {
				display: none;
			}

			.fixed .column-thumbnail {
				vertical-align: top;
				width: 80px;
			}

			.column-thumbnail a {
				display: block;
			}
			.column-thumbnail a img {
				display: inline-block;
				vertical-align: middle;
				width: 80px;
				height: 80px;
				object-fit: contain;
				object-position: center;
				overflow: hidden;
			}
		</style>
		<?php

		return true;
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
	 * Sortable columns.
	 *
	 * @param array $sortable_columns Sortable columns.
	 *
	 * @return array
	 */
	public function sortable_columns( array $sortable_columns ): array {
		$sortable_columns['date_published'] = array( 'date_published', true );

		return $sortable_columns;
	}


	/**
	 * Pre-get books.
	 *
	 * @param WP_Query $query Query.
	 * @return WP_Query
	 */
	public function pre_get_books( WP_Query $query ): WP_Query {

		if ( ! ( is_admin() && $query->is_main_query() ) ) {
			return $query;
		}

		if ( $query->get( 'post_type' ) !== 'book' ) {
			return $query;
		}

		$this->orderby_meta_key = null;

		$orderby = $query->get( 'orderby' );

		if ( 'date_published' === $orderby ) {
			$this->orderby_meta_key = 'date_published';
			$query->set( 'orderby', 'date' );
		}

		return $query;
	}


	/**
	 * Join postmeta with unique alias for date_published sort.
	 *
	 * Using only meta_key + orderby meta_value in pre_get_posts would let WP_Query
	 * add its own postmeta join. With filters (e.g. year, search) or other plugins,
	 * that produces a second join on the same table → "Not unique table/alias".
	 * Same approach as in Run plugin (posts_join + posts_orderby with dedicated alias).
	 *
	 * @param string   $join  Clause JOIN.
	 * @param WP_Query $query Query.
	 * @return string
	 */
	public function posts_join_book_date_published( $join, WP_Query $query ) {
		if ( ! is_admin() || $query->get( 'post_type' ) !== 'book' || ! $this->orderby_meta_key ) {
			return $join;
		}

		global $wpdb;

		$join .= $wpdb->prepare(
			" INNER JOIN {$wpdb->postmeta} AS book_meta_sort ON ({$wpdb->posts}.ID = book_meta_sort.post_id AND book_meta_sort.meta_key = %s) ",
			$this->orderby_meta_key
		);

		return $join;
	}


	/**
	 * ORDER BY book_meta_sort.meta_value for date_published.
	 *
	 * @param string   $orderby Clause ORDER BY.
	 * @param WP_Query $query   Query.
	 * @return string
	 */
	public function posts_orderby_book_date_published( $orderby, WP_Query $query ) {
		if ( ! is_admin() || $query->get( 'post_type' ) !== 'book' || ! $this->orderby_meta_key ) {
			return $orderby;
		}

		$order = strtoupper( $query->get( 'order' ) );
		if ( 'DESC' !== $order ) {
			$order = 'ASC';
		}

		return 'book_meta_sort.meta_value ' . $order;
	}


	/**
	 * Add volume number to book title.
	 *
	 * @param string $title The post title.
	 * @param int    $post_id The post ID.
	 *
	 * @return string
	 */
	public function add_volume_number_to_title( string $title, int $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( $this->post_type === $post_type ) {
			$volume_number = get_post_meta( $post_id, 'volume_number', true );

			if ( $volume_number ) {
				$title = $title . ', ' . $volume_number;
			}

			return $title;
		}

		return $title;
	}


	/**
	 * Add series to book title.
	 *
	 * @param string $title The post title.
	 * @param int    $post_id The post ID.
	 *
	 * @return string
	 */
	public function add_series_to_title( string $title, int $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( $this->post_type === $post_type ) {
			$series = get_post_meta( $post_id, 'series', true );

			if ( $series ) {
				$title = $series . ', ' . $title;
			}

			return $title;
		}

		return $title;
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
	 * Reading stats: books read out of all books, trash and auto-drafts excluded.
	 *
	 * @return array{read:int,total:int,percent:int}
	 */
	public static function get_reading_stats(): array {
		$counts = (array) wp_count_posts( 'book' );
		unset( $counts['trash'], $counts['auto-draft'] );

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
