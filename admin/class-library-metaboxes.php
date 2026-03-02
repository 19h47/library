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
	 * Plugin identifier.
	 *
	 * @since  1.0.0
	 * @var    string $plugin_name
	 */
	private $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @var    string $version
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name Plugin identifier.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		if ( is_admin() ) {
			add_action( 'load-post.php', array( $this, 'init_metabox' ) );
			add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
		}
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
	}


	/**
	 * Render metabox content.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public function render_metabox( $post ) {
		wp_nonce_field( 'library_metabox_nonce', 'library_metabox_nonce' );

		// Retrieve existing values from the database.
		$series         = get_post_meta( $post->ID, 'series', true );
		$authors        = wp_get_post_terms( $post->ID, 'library-author', array( 'fields' => 'names' ) );
		$isbn           = get_post_meta( $post->ID, 'isbn', true );
		$issn           = get_post_meta( $post->ID, 'issn', true );
		$volume_number  = get_post_meta( $post->ID, 'volume_number', true );
		$date_published = get_post_meta( $post->ID, 'date_published', true );
		$translators    = get_post_meta( $post->ID, 'translators', true );
		$publishers     = wp_get_post_terms( $post->ID, 'library-publisher', array( 'fields' => 'names' ) );
		$book_editions  = get_post_meta( $post->ID, 'book_editions', true );

		// Set default values.
		if ( empty( $series ) ) {
			$series = '';
		}

		if ( empty( $authors ) ) {
			$authors = '';
		} else {
			$authors = implode( ', ', $authors );
		}

		if ( empty( $isbn ) ) {
			$isbn = '';
		}

		if ( empty( $issn ) ) {
			$issn = '';
		}

		if ( empty( $volume_number ) ) {
			$volume_number = '';
		}

		if ( empty( $date_published ) ) {
			$date_published = '';
		}

		if ( empty( $translators ) ) {
			$translators = '';
		}

		if ( empty( $publishers ) ) {
			$publishers = '';
		} else {
			$publishers = implode( ', ', $publishers );
		}

		if ( empty( $book_editions ) ) {
			$book_editions = '';
		}

		include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-form.php';
	}

	/**
	 * Save metabox data.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_metabox( $post_id, $post ) {
		$nonce = isset( $_POST['library_metabox_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['library_metabox_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'library_metabox_nonce' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Sanitize user input.
		$series         = isset( $_POST['series'] ) ? sanitize_text_field( $_POST['series'] ) : '';
		$authors        = isset( $_POST['authors'] ) ? sanitize_text_field( $_POST['authors'] ) : '';
		$isbn           = isset( $_POST['isbn'] ) ? sanitize_text_field( $_POST['isbn'] ) : '';
		$issn           = isset( $_POST['issn'] ) ? sanitize_text_field( $_POST['issn'] ) : '';
		$volume_number  = isset( $_POST['volume_number'] ) ? sanitize_text_field( $_POST['volume_number'] ) : '';
		$date_published = isset( $_POST['date_published'] ) ? sanitize_text_field( $_POST['date_published'] ) : '';
		$translators    = isset( $_POST['translators'] ) ? sanitize_text_field( $_POST['translators'] ) : '';
		$publishers     = isset( $_POST['publishers'] ) ? sanitize_text_field( $_POST['publishers'] ) : array();
		$book_editions  = isset( $_POST['book_editions'] ) ? sanitize_text_field( $_POST['book_editions'] ) : array();

		// Update the meta field in the database.
		update_post_meta( $post_id, 'series', $series );

		wp_delete_object_term_relationships( $post_id, 'library-author' );

		if ( ! empty( $authors ) ) {
			wp_set_object_terms( $post_id, explode( ', ', $authors ), 'library-author' );
		}

		update_post_meta( $post_id, 'isbn', $isbn );
		update_post_meta( $post_id, 'issn', $issn );
		update_post_meta( $post_id, 'volume_number', $volume_number );
		update_post_meta( $post_id, 'date_published', $date_published );
		update_post_meta( $post_id, 'translators', $translators );
		update_post_meta( $post_id, 'book_editions', $book_editions );

		wp_delete_object_term_relationships( $post_id, 'library-publisher' );

		if ( ! empty( $publishers ) ) {
			wp_set_object_terms( $post_id, explode( ', ', $publishers ), 'library-publisher' );
		}
	}
}
