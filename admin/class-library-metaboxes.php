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
		add_action( 'post_submitbox_misc_actions', array( $this, 'render_status' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'status' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'use_block_editor' ), 10, 2 );
	}

	/**
	 * Keep the classic edit screen for books: the block editor has no publish box for the "Library" menu,
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
	 * Render the "Library" menu in the publish box, under the status: in the library, not kept or to buy.
	 *
	 * The status menu of the publish box only offers core statuses and has no filter.
	 *
	 * @param WP_Post $post Post.
	 * @return void
	 */
	public function render_status( WP_Post $post ): void {
		if ( 'book' !== $post->post_type ) {
			return;
		}

		$statuses = Library_Posts::get_statuses();
		$selected = isset( $statuses[ $post->post_status ] ) ? $post->post_status : 'publish';
		?>
		<div class="misc-pub-section misc-pub-library-status">
			<?php wp_nonce_field( 'library_status', 'library_status_nonce' ); ?>
			<label for="library-status"><?php esc_html_e( 'Library', 'library' ); ?></label>
			<select id="library-status" name="library_status" aria-describedby="library-status-description">
				<?php foreach ( $statuses as $status => $label ) : ?>
					<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status, $selected ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<p class="description" id="library-status-description">
				<?php esc_html_e( 'Not kept: read but not in my library. To buy: not in my library yet. Both are hidden from the books list and the site.', 'library' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Apply the "Library" menu of the publish box to the book status.
	 *
	 * The menu decides over the status menu and the button pressed; in the library publishes a book not kept or to buy.
	 *
	 * @param array $data    Slashed post data to insert.
	 * @param array $postarr Slashed submitted data, with the form fields.
	 * @return array
	 */
	public function status( array $data, array $postarr ): array {
		$nonce = isset( $postarr['library_status_nonce'] ) ? sanitize_key( wp_unslash( $postarr['library_status_nonce'] ) ) : '';

		if ( 'book' !== $data['post_type'] || ! wp_verify_nonce( $nonce, 'library_status' ) || in_array( $data['post_status'], array( 'trash', 'auto-draft' ), true ) ) {
			return $data;
		}

		$status   = sanitize_key( wp_unslash( $postarr['library_status'] ?? '' ) );
		$statuses = array( Library_Posts::NOT_KEPT, Library_Posts::TO_BUY );

		if ( in_array( $status, $statuses, true ) ) {
			$data['post_status'] = $status;
		} elseif ( in_array( $data['post_status'], $statuses, true ) ) {
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
