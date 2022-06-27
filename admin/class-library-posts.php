<?php
/**
 * Post types
 *
 * @link       https://www.19h47.fr
 * @since      0.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Register post types
 *
 * @since      0.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Posts {

	/**
	 * Post type name
	 *
	 * @since  0.0.0
	 * @access   private
	 * @var string
	 */
	protected $post_type = 'book';


	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;


	/**
	 * The version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $plugin_version;


	/**
	 * init
	 */
	public function __construct( $plugin_name, $plugin_version ) {
		$this->plugin_name    = $plugin_name;
		$this->plugin_version = $plugin_version;

		// Add the Run post type
		add_action( 'init', array( $this, 'register_post_type' ) );
		// add_action( 'init', array( $this, 'register_post_status' ) );
		add_filter( 'dashboard_glance_items', array( $this, 'at_a_glance' ) );
		add_action( 'admin_head', array( $this, 'css' ) );

		// add_action( 'admin_footer-post.php', array( $this, 'my_custom_status_add_in_post_page' ) );
		// add_action( 'admin_footer-post-new.php', array( $this, 'my_custom_status_add_in_post_page' ) );
		// add_action( 'admin_footer-edit.php', array( $this, 'my_custom_status_add_in_quick_edit' ) );
	}

	// public function my_custom_status_add_in_quick_edit() {
	// echo "<script>
	// jQuery(document).ready( function() {
	// jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"read\">Read</option>' );
	// });
	// </script>";
	// }
	// public function my_custom_status_add_in_post_page() {
	// echo "<script>
	// jQuery(document).ready( function() {
	// jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"read\">Read</option>' );
	// });
	// </script>";
	// }


	// /**
	// * Register the post type status
	// *
	// * @since  0.0.0
	// *
	// * @access public
	// */
	// public function register_post_status() {
	// register_post_status(
	// __( 'Read', 'library' ),
	// array(
	// 'label'                     => _x( 'Read', 'library' ),
	// 'label_count'               => _n_noop( 'Read <span class="count">(%s)</span>', 'Read <span class="count">(%s)</span>' ),
	// 'public'                    => true,
	// 'exclude_from_search'       => false,
	// 'show_in_admin_all_list'    => true,
	// 'show_in_admin_status_list' => true,
	// )
	// );
	// }


	/**
	 * Save
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 */
	public function save( int $post_ID, WP_Post $post, bool $update ) {
		$isbn = isset( $_POST['isbn'] ) ? sanitize_text_field( $_POST['isbn'] ) : false;

		delete_option( 'reading_percentage' );

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
	}


	/**
	 * Add custom columns
	 *
	 * @param array $columns Array of columns.
	 * @link https://developer.wordpress.org/reference/hooks/manage_post_type_posts_columns/
	 *
	 * @return array
	 */
	public function add_custom_columns( array $columns ) : array {
		unset( $columns['date'] );

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
	public function render_custom_columns( string $column_name, int $post_id ) : void {
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
					$output .= '<span id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '"></span>' . '—';
				}

				break;

			case 'book_editions':
				$book_editions = get_post_meta( $post_id, 'book_editions', true );

				$output .= '<input id="' . $this->plugin_name . '-' . $column_name . '-' . $post_id . '" name="' . $column_name . '" value="' . $book_editions . '" type="hidden">';

				break;
		}

		echo $output;
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
	function render_quick_edit( string $column_name, string $post_type, string $taxonomy ) : void {
		$html = '';

		switch ( $column_name ) {
			case 'isbn': {
				$html .= '<fieldset class="inline-edit-col"><div class="inline-edit-col">';
				$html .= '<label class="inline-edit-isbn">';
				$html .= '<span class="title">' . esc_html__( 'ISBN', 'library' ) . '</span>';
				$html .= '<input pattern="(?:(?=.{17}$)97[89][ -](?:[0-9]+[ -]){2}[0-9]+[ -][0-9]|97[89][0-9]{10}|(?=.{13}$)(?:[0-9]+[ -]){2}[0-9]+[ -][0-9Xx]|[0-9]{9}[0-9Xx])" name="isbn" class="inline-edit-isbn-input js-library-isbn" type="text" placeholder="' . esc_html__( 'ISBN', 'library' ) . '" value="">';
				$html .= '</label>';
				$html .= '</div></fieldset>';

				break;
			}

			case 'book_editions': {
				$html .= '<fieldset class="inline-edit-col"><div class="inline-edit-col">';
				$html .= '<label class="inline-edit-book-editions">';
				$html .= '<span class="title">' . esc_html__( 'Book editions', 'library' ) . '</span>';
				$html .= '<input name="book_editions" class="inline-edit-book-editions-input js-library-book-editions" type="text" placeholder="' . esc_html__( 'Book editions', 'library' ) . '" value="">';
				$html .= '</label>';
				$html .= '</div></fieldset>';

				break;
			}
		}

		echo $html;
	}

	/**
	 * Save quick edit
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 */
	function save_quick_edit( int $post_ID, WP_Post $post, bool $update ) {
		// pointless if $_POST is empty (this happens on bulk edit)
		if ( empty( $_POST ) ) {
			return $post_ID;
		}

		// Verify quick edit nonce.
		if ( isset( $_POST['_inline_edit'] ) && ! wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) {
			return $post_ID;
		}

		// don't save for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_ID;
		}

		// dont save for revisions
		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			return $post_ID;
		}

		switch ( $post->post_type ) {

			case 'book':
				$fields = array( 'isbn', 'book_editions' );

				foreach ( $fields as $field ) {
					if ( array_key_exists( $field, $_POST ) ) {
						update_post_meta( $post_ID, $field, $_POST[ $field ] );
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

		$supports   = array( 'title' /* , 'custom-fields' */ );
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

		$text = sprintf(
			/* translators: %1$s: number posts %2$s: singular name %3$s: name %4$s: pending */
			_n( '%1$s %4$s%2$s', '%1$s %4$s%3$s', $num_posts->{ $post_status } ), // phpcs:ignore
			number_format_i18n( $num_posts->{ $post_status } ),
			strtolower( $object->labels->singular_name ),
			strtolower( $object->labels->name ),
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
		</style>
		<?php

		return true;
	}

	/**
	 * Register rest fields
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_rest_field/
	 */
	function register_rest_fields() {
		register_rest_field(
			'book',
			'read',
			array(
				'get_callback'    => fn( array $object, $field_name, $request ) => (bool) get_post_meta( $object['id'], 'read', true ),
				'update_callback' => fn( $value, WP_Post $object, $field_name ) => update_post_meta( $object->ID, 'read', (bool) $value ),
				'schema'          => null,
			)
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @param  array $sortable_columns
	 *
	 * @return array
	 */
	public function sortable_columns( array $sortable_columns ) : array {
		$sortable_columns['date_published'] = array( 'date_published', true );

		return $sortable_columns;
	}


	function pre_get_books( $query ) {

		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		switch ( $orderby ) {
			case 'date_published':
				$query->set( 'meta_key', 'date_published' );
				$query->set( 'orderby', 'meta_value' );
				break;
			default:
				break;
		}
	}


	/**
	 * Add volume number to book title.
	 *
	 * @param string $title The post title.
	 * @param int    $id The post ID.
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
	 * @param int    $id The post ID.
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
	public function add_list_table_views( array $views ) : array {
		if ( get_option( 'reading_percentage' ) ) {
			$views[] = '<a class="js-library-read-percentage" data-loading-text="' . esc_html__( 'Loading percent read...', 'library' ) . '">' . sprintf( __( '%s%% read', 'library' ), get_option( 'reading_percentage' ) ) . '</a>';
		} else {
			$views[] = '<a class="js-library-read-percentage js-library-read-percentage-init" data-loading-text="' . esc_html__( 'Loading percent read...', 'library' ) . '">' . esc_html__( 'Loading percent read...', 'library' ) . '</a>';
		}

		return $views;

	}
}
