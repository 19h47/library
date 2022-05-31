<?php
/**
 * Taxonomies of the plugin.
 *
 * @link       https://github.com/19h47/sellsy-clients/
 * @since      0.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Taxonomies
 */
class Library_Taxonomies {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}


	/**
	 * Register
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_taxonomy/
	 *
	 * @return void
	 */
	public function register() : void {
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
	public function messages( array $messages ) : array {
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
}
