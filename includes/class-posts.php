<?php
/**
 * Book post type and the "Not kept" and "To buy" statuses, and how a book is titled, slugged and dated.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Registers the book post type and the "Not kept" and "To buy" statuses, shared by the admin, the reading app and the site.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Posts {

	/**
	 * Meta keys the slug of a book is built from, after its title: its ISBN, or else its volume number.
	 *
	 * @var string[]
	 */
	public const array SLUG_KEYS = array( 'library_isbn', 'library_volume_number' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'after_setup_theme', array( $this, 'add_thumbnail_support' ), 11 );
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_post_statuses' ) );
		add_filter( 'wp_untrash_post_status', array( $this, 'restore_status' ), 10, 3 );
		// After the status of the post is known, which the "Library" menu of the publish box may change.
		add_filter( 'wp_insert_post_data', array( $this, 'build_slug' ), 11, 2 );
		add_filter( 'wp_insert_post_data', array( $this, 'set_bought_date' ), 11, 2 );
		add_filter( 'the_title', array( $this, 'show_full_title' ), 10, 2 );
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
		add_theme_support( 'post-thumbnails', array( 'library_book' ) );
	}

	/**
	 * Register the statuses of the books read but not kept, and of the books to buy.
	 *
	 * Core then leaves them out of the "All" view, its count, the site and search,
	 * and lists them in their own views of the books list.
	 *
	 * @return void
	 */
	public function register_post_statuses(): void {
		$args = array(
			'public'                    => false,
			'protected'                 => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => true,
		);

		foreach ( Status::get_others() as $status ) {
			register_post_status(
				$status->value,
				array(
					'label'       => $status->label(),
					'label_count' => $status->label_count(),
				) + $args
			);
		}
	}

	/**
	 * Restore a book from the trash in the status it had, in the library, not kept or to buy, rather than as a draft, as core
	 * restores a post since WordPress 5.6: a draft leaves the app, the stats and the achievements, and its status is lost.
	 *
	 * @param string $new_status      Status core restores the post in.
	 * @param int    $post_id         Post ID.
	 * @param string $previous_status Status of the post before it was trashed.
	 * @return string
	 */
	public function restore_status( string $new_status, int $post_id, string $previous_status ): string {
		return 'library_book' === get_post_type( $post_id ) ? $previous_status : $new_status;
	}

	/**
	 * Date a book leaving the books to buy from today: its post date is the date it was added to the library.
	 *
	 * @param array<string,mixed> $data    Slashed post data about to be saved.
	 * @param array<string,mixed> $postarr Slashed submitted data.
	 * @return array<string,mixed>
	 */
	public function set_bought_date( array $data, array $postarr ): array {
		$post_id = (int) ( $postarr['ID'] ?? 0 );

		if ( 'library_book' !== $data['post_type'] || ! $post_id || Status::To_Buy->value !== get_post_status( $post_id ) || in_array( $data['post_status'], array( Status::To_Buy->value, 'trash' ), true ) ) {
			return $data;
		}

		$data['post_date']     = current_time( 'mysql' );
		$data['post_date_gmt'] = current_time( 'mysql', true );

		return $data;
	}

	/**
	 * Build the slug from the title and the ISBN, or the volume number, such as the issue of a magazine, or else from the
	 * title alone: an ISBN removed leaves the slug too.
	 *
	 * Only the forms of a book send its ISBN, even empty: any other save, such as moving it to the trash, keeps the slug.
	 * Quick edit does not send the volume number, which is then the one of the book. The fields are taken as
	 * they are saved, an ISBN with its hyphens.
	 *
	 * Runs before the post is written, so no second update is needed.
	 *
	 * @param array<string,mixed> $data    Slashed post data about to be saved.
	 * @param array<string,mixed> $postarr Slashed submitted data, with the form fields.
	 * @return array<string,mixed>
	 */
	public function build_slug( array $data, array $postarr ): array {
		if ( 'library_book' !== $data['post_type'] || ! isset( $postarr['library_isbn'] ) ) {
			return $data;
		}

		$post_id = (int) ( $postarr['ID'] ?? 0 );
		$title   = sanitize_text_field( wp_unslash( $postarr['post_title'] ?? '' ) );
		$fields  = self::get_slug_fields( $post_id, wp_unslash( array_intersect_key( $postarr, array_flip( self::SLUG_KEYS ) ) ) );

		foreach ( $fields as $key => $value ) {
			$fields[ $key ] = sanitize_meta( $key, $value, 'post', 'library_book' );
		}

		if ( $fields['library_isbn'] ) {
			$parts = array( $title, $fields['library_isbn'] );
		} elseif ( $fields['library_volume_number'] ) {
			$parts = array( $title, $fields['library_volume_number'] );
		} else {
			$parts = array( $title );
		}

		$slug = sanitize_title( implode( '-', $parts ) );

		// Without a title nor an identifier, the slug core gives the book.
		if ( '' === $slug ) {
			return $data;
		}

		$data['post_name'] = wp_unique_post_slug( $slug, $post_id, $data['post_status'], 'library_book', $data['post_parent'] );

		return $data;
	}

	/**
	 * Fields the slug of a book is built from, those of SLUG_KEYS: the values sent, or else those of the book, as saved.
	 *
	 * @param int                 $post_id Post ID, 0 for a book to add.
	 * @param array<string,mixed> $values  Unslashed values sent, by meta key.
	 * @return array<string,mixed>
	 */
	public static function get_slug_fields( int $post_id, array $values ): array {
		$fields = array();

		foreach ( self::SLUG_KEYS as $key ) {
			$fields[ $key ] = $values[ $key ] ?? (string) get_post_meta( $post_id, $key, true );
		}

		return $fields;
	}

	/**
	 * Register the custom post type.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/register_post_type
	 *
	 * @return void
	 */
	public function register_post_type(): void {

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
			'uploaded_to_this_item' => __( 'Uploaded to this book', 'library' ),
			'items_list'            => __( 'Books list', 'library' ),
			'items_list_navigation' => __( 'Books list navigation', 'library' ),
			'filter_items_list'     => __( 'Filter books list', 'library' ),
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

		register_post_type( 'library_book', $args );
	}

	/**
	 * Whether a book has been read: whether it has a reading, of a known date or not.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_read( int $post_id ): bool {
		return (bool) Meta::get_read_dates( $post_id );
	}

	/**
	 * Whether a book is collective, such as a magazine or an anthology: its authors are contributors, none of them the
	 * author of the book.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function is_collective( int $post_id ): bool {
		return (bool) get_post_meta( $post_id, 'library_collective', true );
	}

	/**
	 * Show a book title with its series, volume and subtitle, as everywhere else.
	 *
	 * @param string $title   The post title.
	 * @param int    $post_id The post ID; some callers of the_title omit it.
	 * @return string
	 */
	public function show_full_title( string $title, int $post_id = 0 ): string {
		if ( ! $post_id || 'library_book' !== get_post_type( $post_id ) ) {
			return $title;
		}

		return Books::get_full_title( $post_id, $title );
	}
}
