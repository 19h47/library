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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'term_updated_messages', array( $this, 'messages' ) );
		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );
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
			'labels'               => $labels,
			'hierarchical'         => true, // Parent = nom réel, enfants = pseudonymes.
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_nav_menus'    => true,
			'show_in_quick_edit'   => true,
			'show_admin_column'    => true,
			'show_in_rest'         => true,
			'show_in_graphql'      => true,
			// The tag box lets authors be typed rather than picked from a checklist.
			'meta_box_cb'          => 'post_tags_meta_box',
			'meta_box_sanitize_cb' => array( self::class, 'sanitize_author_names' ),
			'graphql_single_name'  => 'author',
			'graphql_plural_name'  => 'authors',
		);

		register_taxonomy( 'library-author', 'book', $args );
	}

	/**
	 * Turn the author names typed in the tag box into term IDs, creating the missing authors.
	 *
	 * Core only resolves existing names: a hierarchical taxonomy is saved by IDs, so new names would be dropped.
	 *
	 * @param string          $taxonomy Taxonomy name.
	 * @param string|string[] $terms    Comma-separated names, or names.
	 * @return int[]
	 */
	public static function sanitize_author_names( string $taxonomy, $terms ): array {
		$term_ids = array();

		foreach ( taxonomy_meta_box_sanitize_cb_input( $taxonomy, $terms ) as $term ) {
			if ( is_int( $term ) ) {
				$term_ids[] = $term;
				continue;
			}

			$inserted = wp_insert_term( $term, $taxonomy );

			if ( ! is_wp_error( $inserted ) ) {
				$term_ids[] = $inserted['term_id'];
			}
		}

		return $term_ids;
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
	 * Include children (pseudonyms) in term counts for author taxonomy.
	 *
	 * @param array    $args       Arguments passés à get_terms().
	 * @param string[] $taxonomies Taxonomies demandées.
	 * @return array
	 */
	public function get_terms_args( array $args, array $taxonomies ): array {
		// Counts only exist on full terms; padding other queries breaks the core cache.
		if ( ! in_array( 'library-author', $taxonomies, true ) || 'all' !== $args['fields'] ) {
			return $args;
		}

		$args['pad_counts'] = true;

		return $args;
	}
}
