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
	 * Save
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 */
	public function save( int $post_ID, WP_Post $post, bool $update ): void {

		// Verify metabox nonce before processing form data from $_POST.
		$nonce = isset( $_POST['library_metabox_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['library_metabox_nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'library_metabox_nonce' ) ) {
			return;
		}

		$isbn          = isset( $_POST['isbn'] ) ? sanitize_text_field( wp_unslash( $_POST['isbn'] ) ) : false;
		$issn          = isset( $_POST['issn'] ) ? sanitize_text_field( wp_unslash( $_POST['issn'] ) ) : false;
		$volume_number = isset( $_POST['volume_number'] ) ? sanitize_text_field( wp_unslash( $_POST['volume_number'] ) ) : false;

		if ( $update ) {
			delete_option( 'reading_percentage' );
		}

		if ( $isbn ) {
			remove_action( 'save_post_book', array( $this, 'save' ), 10, 3 );

			wp_update_post(
				array(
					'ID'        => $post_ID,
					'post_name' => $post->post_title . '-' . $isbn,
				)
			);

			add_action( 'save_post_book', array( $this, 'save' ), 10, 3 );
		}

		if ( $issn && $volume_number ) {
			remove_action( 'save_post_book', array( $this, 'save' ), 10, 3 );

			wp_update_post(
				array(
					'ID'        => $post_ID,
					'post_name' => $post->post_title . '-' . $issn . '-' . $volume_number,
				)
			);

			add_action( 'save_post_book', array( $this, 'save' ), 10, 3 );
		}
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
				$read = get_post_meta( $post_id, 'read', true );

				if ( $read ) {
					$output .= '<input id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '" type="checkbox" checked class="js-library-checkbox" data-post-id="' . esc_attr( $post_id ) . '">';
				} else {
					$output .= '<input id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '" type="checkbox" class="js-library-checkbox" data-post-id="' . esc_attr( $post_id ) . '">';
				}

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
			case 'book_editions':
				$book_editions = get_post_meta( $post_id, 'book_editions', true );

				$output .= '<input id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '" name="' . $column_name . '" value="' . $book_editions . '" type="hidden">';

				break;

			case 'thumbnail':
				$thumbnail = get_the_post_thumbnail( $post_id );

				if ( $thumbnail ) {
					$output .= '<a href="' . esc_attr( get_edit_post_link( $post_id ) ) . '">';
					$output .= $thumbnail;
					$output .= '</a>';
				} else {
					$output .= '—';
				}

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
			),
			'span'  => array( 'id' => true ),
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
			),
		);
		echo wp_kses( $html, $allowed_quick_edit_html );
	}

	/**
	 * Save quick edit.
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public function save_quick_edit( int $post_ID, WP_Post $post, bool $update ) {
		unset( $update );

		if ( empty( $_POST ) ) {
			return $post_ID;
		}

		// Verify quick edit nonce.
		if ( isset( $_POST['_inline_edit'] ) && ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return $post_ID;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_ID;
		}

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			return $post_ID;
		}

		switch ( $post->post_type ) {

			case 'book':
				$fields = array( 'isbn', 'book_editions' );

				foreach ( $fields as $field ) {
					if ( array_key_exists( $field, $_POST ) ) {
						update_post_meta( $post_ID, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
					}
				}

				break;
		}
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

		$supports   = array( 'title', 'thumbnail' /* , 'custom-fields' */ );
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
	 * Register rest fields
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_rest_field/
	 */
	public function register_rest_fields(): void {
		register_rest_field(
			'book',
			'read',
			array(
				'get_callback'    => fn( array $data ) => (bool) get_post_meta( $data['id'], 'read', true ),
				'update_callback' => fn( $value, WP_Post $post ) => update_post_meta( $post->ID, 'read', (bool) $value ),
				'schema'          => null,
			)
		);
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
		if ( get_option( 'reading_percentage' ) ) {

			/* translators: %s: reading percentage. */
			$views[] = '<a class="js-library-read-percentage" data-loading-text="' . esc_html__( 'Loading percent read...', 'library' ) . '">' . sprintf( __( '%s%% read', 'library' ), get_option( 'reading_percentage' ) ) . '</a>';
		} else {
			$views[] = '<a class="js-library-read-percentage js-library-read-percentage-init" data-loading-text="' . esc_html__( 'Loading percent read...', 'library' ) . '">' . esc_html__( 'Loading percent read...', 'library' ) . '</a>';
		}

		return $views;
	}
}
