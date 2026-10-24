<?php
/**
 * Book metabox: information fields.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Renders and saves the book information metabox.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Metaboxes {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'load-post.php', array( $this, 'init_metabox' ) );
		add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
	}

	/**
	 * Register metabox and save callback.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_metabox() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );
	}

	/**
	 * Add the information metabox to the book edit screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_metabox() {
		add_meta_box(
			'book_information',
			__( 'Information', 'library' ),
			array( $this, 'render_metabox' ),
			'book',
			'normal',
			'default'
		);

		// Custom fields support only exposes the meta in the REST API; this form edits them.
		remove_meta_box( 'postcustom', 'book', 'normal' );
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

		$values = array();

		foreach ( Library_Meta::KEYS as $key ) {
			$values[ $key ] = get_post_meta( $post->ID, $key, true );
		}

		$values['read_date'] = Library_Meta::get_read_dates( $post->ID );

		include plugin_dir_path( __FILE__ ) . 'partials/library-form.php';
	}

	/**
	 * Save metabox data.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_metabox( int $post_id, WP_Post $post ): void {
		if ( 'book' !== $post->post_type ) {
			return;
		}

		$nonce = isset( $_POST['library_metabox_nonce'] ) ? sanitize_key( wp_unslash( $_POST['library_metabox_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'library_metabox_nonce' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Library_Meta.
		$values = wp_unslash( $_POST );

		Library_Meta::save( $post_id, $values );
	}
}
