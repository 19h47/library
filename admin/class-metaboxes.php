<?php
/**
 * Edit screen of a book, the classic one: the information metabox, the "Not kept" box and the people tag box.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

namespace Library\Admin;

use Library\Meta;
use Library\Posts;
use Library\Reading;
use Library\Roles;
use Library\Shelf;
use Library\Status;
use Library\Taxonomies;
use WP_Post;
use WP_Term;

/**
 * Renders and saves the book information metabox and the "Not kept" box of the publish box, and types the people in a tag box.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Metaboxes {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes_library_book', array( $this, 'add_metabox' ) );
		add_action( 'add_meta_boxes_library_book', array( $this, 'register_person_box' ) );
		add_action( 'admin_action_editpost', array( $this, 'refuse_isbn' ) );
		add_action( 'admin_notices', array( $this, 'show_refused_isbn' ) );
		add_filter( 'removable_query_args', array( $this, 'remove_refused_isbn_arg' ) );
		add_action( 'save_post_library_book', array( $this, 'save_metabox' ) );
		add_action( 'add_meta_boxes_library_book', array( $this, 'add_shelf_box' ) );
		add_action( 'save_post_library_book', array( $this, 'save_shelf' ) );
		add_action( 'admin_notices', array( $this, 'show_kept_book' ) );
		add_filter( 'removable_query_args', array( $this, 'remove_kept_book_arg' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'keep_classic_editor' ), 10, 2 );
	}

	/**
	 * Keep the classic edit screen for books: the block editor shows the boxes of a book apart from it, and its notes
	 * stay text in paragraphs, which the reading app edits as typed.
	 *
	 * @param bool   $use_block_editor Whether the post type uses the block editor.
	 * @param string $post_type        Post type.
	 * @return bool
	 */
	public function keep_classic_editor( bool $use_block_editor, string $post_type ): bool {
		return 'library_book' === $post_type ? false : $use_block_editor;
	}

	/**
	 * Add the information metabox to the book edit screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_metabox() {
		add_meta_box(
			'library_book_information',
			__( 'Information', 'library' ),
			array( $this, 'render_metabox' ),
			'library_book',
			'normal',
			'default'
		);

		// Custom fields support only exposes the meta in the REST API; this form edits them.
		remove_meta_box( 'postcustom', 'library_book', 'normal' );
	}


	/**
	 * Render metabox content.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_metabox( WP_Post $post ): void {
		wp_nonce_field( 'library_metabox_nonce', 'library_metabox_nonce' );

		$values       = Meta::get_values( $post->ID );
		$own_language = get_term_by( 'slug', Taxonomies::get_own_language_code(), 'library_language' );

		// A book added is in the language of the reader, as in the form of the app.
		if ( 'auto-draft' === $post->post_status && $own_language instanceof WP_Term ) {
			$values['library_edition_languages'] = array( $own_language->term_id );
		}

		$languages = Taxonomies::get_term_choices( 'library_language' );
		$series    = Taxonomies::get_series_name( $post->ID );
		$paths     = Taxonomies::get_series_paths();
		$names     = Taxonomies::get_book_terms( $post->ID, 'library_person' );
		$roles     = Meta::get_roles( $post->ID );

		include plugin_dir_path( __FILE__ ) . 'partials/library-form.php';
	}

	/**
	 * Save metabox data.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_metabox( int $post_id ): void {
		$nonce = isset( $_POST['library_metabox_nonce'] ) ? sanitize_key( wp_unslash( $_POST['library_metabox_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'library_metabox_nonce' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Meta.
		$values = wp_unslash( $_POST );

		// A book without edition language sends none.
		$values['library_edition_languages'] ??= array();

		Meta::save( $post_id, $values );

		// A series typed after its cycles, as the form of the reading app sends it: a name not found adds it.
		if ( isset( $values['series'] ) ) {
			Taxonomies::set_series( $post_id, sanitize_text_field( $values['series'] ) );
		}

		// The names of the book are saved first: a name just added is an author, its role chosen once it is saved.
		if ( isset( $values['library_roles'] ) ) {
			Meta::save_roles( $post_id, array_map( 'sanitize_key', (array) $values['library_roles'] ) );
		}
	}

	/**
	 * Keep the ISBN of a book when the one typed in its form is mistyped, or another book has it, as Meta::check_isbn()
	 * tells, before core writes the book: the rest of it is saved, its slug with the ISBN kept, and a notice says why.
	 *
	 * The ISBN sent is replaced by the one saved, which edit_post(), the slug and save_metabox() then read.
	 *
	 * @return void
	 */
	public function refuse_isbn(): void {
		$nonce   = isset( $_POST['library_metabox_nonce'] ) ? sanitize_key( wp_unslash( $_POST['library_metabox_nonce'] ) ) : '';
		$post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0;

		if ( ! wp_verify_nonce( $nonce, 'library_metabox_nonce' ) || 'library_book' !== get_post_type( $post_id ) || ! isset( $_POST['library_isbn'] ) ) {
			return;
		}

		$isbn = sanitize_text_field( wp_unslash( $_POST['library_isbn'] ) );

		if ( ! Meta::check_isbn( $post_id, $isbn ) ) {
			return;
		}

		$_POST['library_isbn'] = wp_slash( (string) get_post_meta( $post_id, 'library_isbn', true ) );

		add_filter( 'redirect_post_location', fn( string $location ): string => add_query_arg( 'library_refused_isbn', rawurlencode( $isbn ), $location ) );
	}

	/**
	 * Say, on the screen of a book saved, that the ISBN typed in its form was not, and why.
	 *
	 * @return void
	 */
	public function show_refused_isbn(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Shows a message, changes nothing.
		$isbn = isset( $_GET['library_refused_isbn'] ) ? sanitize_text_field( wp_unslash( $_GET['library_refused_isbn'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Shows a message, changes nothing.
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		$error   = '' !== $isbn ? Meta::check_isbn( $post_id, $isbn ) : null;

		if ( ! $error || 'library_book' !== get_current_screen()?->post_type ) {
			return;
		}

		/* translators: %s: why, such as a check digit that does not match. */
		$message = sprintf( __( 'The ISBN was not saved. %s', 'library' ), $error->get_error_message() );

		wp_admin_notice( esc_html( $message ), array( 'type' => 'error' ) );
	}

	/**
	 * Take the ISBN refused out of the address once its notice shows, as core does with its messages.
	 *
	 * @param string[] $args Query arguments removed.
	 * @return string[]
	 */
	public function remove_refused_isbn_arg( array $args ): array {
		return array( ...$args, 'library_refused_isbn' );
	}

	/**
	 * Add the box of the shelves of the reader, beside the publish box, for a reader who shelves the books.
	 *
	 * @return void
	 */
	public function add_shelf_box(): void {
		if ( ! current_user_can( Roles::SHELVE ) ) {
			return;
		}

		add_meta_box( 'library_shelf', __( 'My shelves', 'library' ), array( $this, 'render_shelf_box' ), 'library_book', 'normal', 'high' );
	}

	/**
	 * Render the shelf of the book for the reader: in the library, not kept, to buy, or on none of their shelves, and their
	 * copy, as the form of their reading in the app. A book added goes in the library.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_shelf_box( WP_Post $post ): void {
		$status   = 'auto-draft' === $post->post_status ? Status::In_Library : Shelf::get_status( $post->ID, get_current_user_id() );
		$selected = $status->value ?? '';
		$values   = Shelf::get_values( $post->ID, get_current_user_id() );

		wp_nonce_field( 'library_shelf', 'library_shelf_nonce' );

		include plugin_dir_path( __FILE__ ) . 'partials/library-shelf.php';
	}

	/**
	 * Save the shelf of the book for the reader from its box, once it is in the catalogue, as the route of its shelf does:
	 * where it goes, then their copy, then off their shelves when they took it off, which a book read stays on, a notice
	 * saying so. A copy given to a book on none of them puts it on one: see Shelf::save().
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_shelf( int $post_id ): void {
		$nonce = isset( $_POST['library_shelf_nonce'] ) ? sanitize_key( wp_unslash( $_POST['library_shelf_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'library_shelf' ) || ! current_user_can( 'shelve_book', $post_id ) ) {
			return;
		}

		$reader = get_current_user_id();
		$before = Shelf::get_status( $post_id, $reader );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Shelf.
		$values = wp_unslash( $_POST );
		$status = Status::tryFrom( sanitize_key( (string) ( $values['library_shelf_status'] ?? '' ) ) );

		// The readings ended, a start, an end and a rating each, then the one in progress, since its start; a book whose
		// readings were all removed sends none.
		$values['library_reading'] = array_map(
			fn( $start, $end, $rating ) => array(
				'start'  => $start,
				'end'    => $end,
				'rating' => $rating,
			),
			(array) ( $values['library_reading_start'] ?? array() ),
			(array) ( $values['library_reading_end'] ?? array() ),
			(array) ( $values['library_reading_rating'] ?? array() )
		);

		if ( rest_sanitize_boolean( $values['library_in_progress'] ?? false ) ) {
			$values['library_reading'][] = new Reading( (string) ( $values['library_started'] ?? '' ), Reading::IN_PROGRESS )->to_array();
		}

		if ( $status ) {
			Shelf::set_status( $post_id, $reader, $status );
		}

		Shelf::save( $post_id, $reader, array_intersect_key( $values, array_flip( Shelf::NAMES ) ) );

		if ( ! $status && $before && ! Shelf::set_status( $post_id, $reader, null ) ) {
			add_filter( 'redirect_post_location', fn( string $location ): string => add_query_arg( 'library_kept_book', 1, $location ) );
		}
	}

	/**
	 * Say, on the screen of a book saved, that it stays on the shelves of the reader, who read it.
	 *
	 * @return void
	 */
	public function show_kept_book(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Shows a message, changes nothing.
		if ( ! isset( $_GET['library_kept_book'] ) || 'library_book' !== get_current_screen()?->post_type ) {
			return;
		}

		wp_admin_notice( esc_html__( 'A book you read stays on your shelves: say it is not kept instead.', 'library' ), array( 'type' => 'warning' ) );
	}

	/**
	 * Take the book kept out of the address once its notice shows, as core does with its messages.
	 *
	 * @param string[] $args Query arguments removed.
	 * @return string[]
	 */
	public function remove_kept_book_arg( array $args ): array {
		return array( ...$args, 'library_kept_book' );
	}

	/**
	 * Give the people tag box the id of a tag box.
	 *
	 * Core names the box of a hierarchical taxonomy "library_persondiv", and only starts the tag box
	 * script when a box id starts with "tagsdiv-": otherwise the tag box stays empty.
	 *
	 * @return void
	 */
	public function register_person_box(): void {
		$taxonomy = get_taxonomy( 'library_person' );

		if ( ! is_callable( $taxonomy->meta_box_cb ) ) {
			return;
		}

		remove_meta_box( 'library_persondiv', 'library_book', 'side' );
		add_meta_box( 'tagsdiv-library_person', $taxonomy->labels->name, $taxonomy->meta_box_cb, 'library_book', 'side', 'core', array( 'taxonomy' => 'library_person' ) );
	}
}
