<?php

/**
 * Metaboxes
 *
 * @link       https://www.19h47.fr
 * @since      0.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */


/**
 * Metaboxes
 *
 * @since      0.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Levron Jérémy <levronjeremy@19h47.fr>
 */
class Library_Metaboxes {

	/**
	 * The ID of this plugin.
	 *
	 * @since       1.0.0
	 * @access      private
	 * @var         string          $plugin_name        The ID of this plugin.
	 */
	private $plugin_name;


	/**
	 * The version of this plugin.
	 *
	 * @since       1.0.0
	 * @access      private
	 * @var         string          $version            The current version of this plugin.
	 */
	private $version;


	/**
	 * Constructor
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
	 * Meta box initialization
	 *
	 * @see https://generatewp.com/snippet/90jakpm/
	 */
	public function init_metabox() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );
	}

	/**
	 * Adds the meta box
	 *
	 * $id, $title, $callback, $page, $context, $priority, $callback_args
	 *
	 * @see  https://developer.wordpress.org/reference/functions/add_meta_box/
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
	 * Renders the meta box
	 */
	public function render_metabox( $post ) {
		// Add nonce for security and authentication.
		wp_nonce_field( 'custom_nonce_action', 'custom_nonce' );

		// Retrieve an existing value from the database
		$series         = get_post_meta( $post->ID, 'series', true );
		$authors        = wp_get_post_terms( $post->ID, 'library-author', array( 'fields' => 'names' ) );
		$isbn           = get_post_meta( $post->ID, 'isbn', true );
		$volume_number  = get_post_meta( $post->ID, 'volume_number', true );
		$date_published = get_post_meta( $post->ID, 'date_published', true );
		$translators    = get_post_meta( $post->ID, 'translators', true );
		$publishers     = wp_get_post_terms( $post->ID, 'library-publisher', array( 'fields' => 'names' ) );
		$book_editions  = get_post_meta( $post->ID, 'book_editions', true );

		// Set default values
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
	 * Handles saving the meta box
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return null
	 */
	public function save_metabox( $post_id, $post ) {
		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['custom_nonce'] ) ? $_POST['custom_nonce'] : '';
		$nonce_action = 'custom_nonce_action';

		// Check if nonce is set.
		if ( ! isset( $nonce_name ) ) {
			return;
		}

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Sanitize user input.
		$series         = isset( $_POST['series'] ) ? sanitize_text_field( $_POST['series'] ) : '';
		$authors        = isset( $_POST['authors'] ) ? sanitize_text_field( $_POST['authors'] ) : '';
		$isbn           = isset( $_POST['isbn'] ) ? sanitize_text_field( $_POST['isbn'] ) : '';
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
