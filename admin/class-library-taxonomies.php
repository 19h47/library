<?php
/**
 * Book taxonomies: author and publisher.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers library-author and library-publisher taxonomies.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Taxonomies {

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
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register taxonomies.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		$this->register_author();
		$this->register_publisher();
	}


	/**
	 * Register author
	 *
	 * @return void
	 */
	public function register_author() {
		$labels = array(
			'name'                       => _x( 'Authors', 'library authors general name', 'library' ),
			'singular_name'              => _x( 'Author', 'library authors singular name', 'library' ),
			'search_items'               => __( 'Search Authors', 'library' ),
			'all_items'                  => __( 'All Authors', 'library' ),
			'popular_items'              => __( 'Popular Authors', 'library' ),
			'edit_item'                  => __( 'Edit Author', 'library' ),
			'view_item'                  => __( 'View Author', 'library' ),
			'update_item'                => __( 'Update Author', 'library' ),
			'add_new_item'               => __( 'Add New Author', 'library' ),
			'new_item_name'              => __( 'New Author Name', 'library' ),
			'separate_items_with_commas' => __( 'Separate authors with commas', 'library' ),
			'add_or_remove_items'        => __( 'Add or remove authors', 'library' ),
			'choose_from_most_used'      => __( 'Choose from the most used authors', 'library' ),
			'not_found'                  => __( 'No authors found.', 'library' ),
			'no_terms'                   => __( 'No authors', 'library' ),
			'items_list_navigation'      => __( 'Authors list navigation', 'library' ),
			'items_list'                 => __( 'Authors list', 'library' ),
			/* translators: Author heading when selecting from the most used terms. */
			'most_used'                  => _x( 'Most Used', 'author', 'library' ),
			'back_to_items'              => __( '&larr; Back to Authors', 'library' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => true, // Parent = nom réel, enfants = pseudonymes.
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_quick_edit'  => true,
			'show_admin_column'   => true,
			'show_in_rest'        => true,
			'show_in_graphql'     => true,
			'meta_box_cb'         => false,
			'graphql_single_name' => 'author',
			'graphql_plural_name' => 'authors',
		);

		register_taxonomy( 'library-author', 'book', $args );
	}


	/**
	 * Register publisher
	 *
	 * @return void
	 */
	public function register_publisher() {
		$labels = array(
			'name'                       => _x( 'Publishers', 'library publishers general name', 'library' ),
			'singular_name'              => _x( 'Publisher', 'library publishers singular name', 'library' ),
			'search_items'               => __( 'Search Publishers', 'library' ),
			'all_items'                  => __( 'All Publishers', 'library' ),
			'popular_items'              => __( 'Popular Publishers', 'library' ),
			'edit_item'                  => __( 'Edit Publisher', 'library' ),
			'view_item'                  => __( 'View Publisher', 'library' ),
			'update_item'                => __( 'Update Publisher', 'library' ),
			'add_new_item'               => __( 'Add New Publisher', 'library' ),
			'new_item_name'              => __( 'New Publisher Name', 'library' ),
			'separate_items_with_commas' => __( 'Separate publishers with commas', 'library' ),
			'add_or_remove_items'        => __( 'Add or remove publishers', 'library' ),
			'choose_from_most_used'      => __( 'Choose from the most used publishers', 'library' ),
			'not_found'                  => __( 'No publishers found.', 'library' ),
			'no_terms'                   => __( 'No publishers', 'library' ),
			'items_list_navigation'      => __( 'Publishers list navigation', 'library' ),
			'items_list'                 => __( 'Publishers list', 'library' ),
			/* translators: Publisher heading when selecting from the most used terms. */
			'most_used'                  => _x( 'Most Used', 'editor', 'library' ),
			'back_to_items'              => __( '&larr; Back to Publishers', 'library' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => false,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_quick_edit'  => true,
			'show_admin_column'   => true,
			'show_in_rest'        => true,
			'show_in_graphql'     => true,
			'meta_box_cb'         => false,
			'graphql_single_name' => 'publisher',
			'graphql_plural_name' => 'publishers',
		);

		register_taxonomy( 'library-publisher', 'book', $args );
	}


	/**
	 * Messages
	 *
	 * @param array $messages Array of arrays of messages to be displayed, keyed by taxonomy name.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/term_updated_messages/
	 *
	 * @return $message
	 */
	public function messages( array $messages ): array {
		$messages['library-author'] = array(
			0 => '',
			1 => __( 'Author added.', 'library' ),
			2 => __( 'Author deleted.', 'library' ),
			3 => __( 'Author updated.', 'library' ),
			4 => __( 'Author not added.', 'library' ),
			5 => __( 'Author not updated.', 'library' ),
			6 => __( 'Authors deleted.', 'library' ),
		);

		$messages['library-publisher'] = array(
			0 => '',
			1 => __( 'Publisher added.', 'library' ),
			2 => __( 'Publisher deleted.', 'library' ),
			3 => __( 'Publisher updated.', 'library' ),
			4 => __( 'Publisher not added.', 'library' ),
			5 => __( 'Publisher not updated.', 'library' ),
			6 => __( 'Publishers deleted.', 'library' ),
		);

		return $messages;
	}


	/**
	 * In admin, when filtering books by author, include children (pseudonyms).
	 *
	 * @param \WP_Query $query The WP_Query instance.
	 * @return void
	 */
	public function pre_get_books( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== 'book' ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin filter on books list.
		$author_param = isset( $_GET['library-author'] ) ? sanitize_text_field( wp_unslash( $_GET['library-author'] ) ) : '';
		if ( '' === $author_param ) {
			return;
		}

		$term = is_numeric( $author_param )
			? get_term( (int) $author_param, 'library-author' )
			: get_term_by( 'slug', $author_param, 'library-author' );

		if ( ! $term instanceof \WP_Term ) {
			return;
		}

		$tax_query = $query->get( 'tax_query' );
		if ( ! is_array( $tax_query ) ) {
			$tax_query = array();
		}

		$tax_query[] = array(
			'taxonomy'         => 'library-author',
			'field'            => 'term_id',
			'terms'            => $term->term_id,
			'include_children' => true,
		);

		$query->set( 'tax_query', $tax_query );
	}


	/**
	 * Include children (pseudonyms) in term counts for author taxonomy.
	 *
	 * @param array    $args       Arguments passés à get_terms().
	 * @param string[] $taxonomies Taxonomies demandées.
	 * @return array
	 */
	public function get_terms_args( array $args, array $taxonomies ): array {
		if ( ! in_array( 'library-author', $taxonomies, true ) ) {
			return $args;
		}

		$args['pad_counts'] = true;

		return $args;
	}
}
