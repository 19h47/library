<?php
/**
 * Book post type and the "Not kept" status, and how a book is titled and slugged.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Registers the book post type and the "Not kept" status, shared by the admin, the reading app and the site.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
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
		// After the status of the post is known, which the "Not kept" box may change.
		add_filter( 'wp_insert_post_data', array( $this, 'slug' ), 11, 2 );
		add_filter( 'the_title', array( $this, 'title' ), 10, 2 );
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
			'all_items'             => __( 'All books', 'library' ),
			'add_new_item'          => __( 'Add New Book', 'library' ),
			'add_new'               => __( 'Add New', 'library' ),
			'new_item'              => __( 'New Book', 'library' ),
			'edit_item'             => __( 'Edit book', 'library' ),
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

		// The excerpt is a summary of the book in a few words, the content its notes.
		$supports = array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' );

		$rewrite = array(
			'slug'       => 'books',
			'with_front' => false,
		);

		$args = array(
			'label'               => __( 'Books', 'library' ),
			'labels'              => $labels,
			'supports'            => $supports,
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
	 * Whether a book has been read; the read date is optional.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_read( int $post_id ): bool {
		return (bool) get_post_meta( $post_id, 'read', true );
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
}
