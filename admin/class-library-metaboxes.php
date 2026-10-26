<?php
/**
 * Edit screen of a book, the classic one: the information metabox, the "Not kept" box and the authors tag box.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Renders and saves the book information metabox and the "Not kept" box of the publish box, and types the authors in a tag box.
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
		add_action( 'add_meta_boxes_book', array( $this, 'add_metabox' ) );
		add_action( 'add_meta_boxes_book', array( $this, 'register_author_box' ) );
		add_action( 'save_post_book', array( $this, 'save_metabox' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_not_kept' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'not_kept' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'use_block_editor' ), 10, 2 );
	}

	/**
	 * Keep the classic edit screen for books: the block editor has no publish box for the "Not kept" box,
	 * and its notes stay text in paragraphs, which the reading app edits as typed.
	 *
	 * @param bool   $use_block_editor Whether the post type uses the block editor.
	 * @param string $post_type        Post type.
	 * @return bool
	 */
	public function use_block_editor( bool $use_block_editor, string $post_type ): bool {
		return 'book' === $post_type ? false : $use_block_editor;
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
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_metabox( int $post_id ): void {
		$nonce = isset( $_POST['library_metabox_nonce'] ) ? sanitize_key( wp_unslash( $_POST['library_metabox_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'library_metabox_nonce' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by Library_Meta.
		$values = wp_unslash( $_POST );

		Library_Meta::save( $post_id, $values );
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
				<?php checked( Library_Posts::NOT_KEPT === $post->post_status ); ?>
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
			$data['post_status'] = Library_Posts::NOT_KEPT;
		} elseif ( Library_Posts::NOT_KEPT === $data['post_status'] ) {
			$data['post_status'] = 'publish';
		}

		return $data;
	}

	/**
	 * Give the authors tag box the id of a tag box.
	 *
	 * Core names the box of a hierarchical taxonomy "library-authordiv", and only starts the tag box
	 * script when a box id starts with "tagsdiv-": otherwise the tag box stays empty.
	 *
	 * @return void
	 */
	public function register_author_box(): void {
		$taxonomy = get_taxonomy( 'library-author' );

		remove_meta_box( 'library-authordiv', 'book', 'side' );
		add_meta_box( 'tagsdiv-library-author', $taxonomy->labels->name, $taxonomy->meta_box_cb, 'book', 'side', 'core', array( 'taxonomy' => 'library-author' ) );
	}
}
