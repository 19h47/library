<?php
/**
 * Book taxonomies: author, publisher and original language.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Registers library-author, library-publisher and library-language taxonomies.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Taxonomies {

	/**
	 * Book taxonomies.
	 *
	 * @var string[]
	 */
	public const TAXONOMIES = array( 'library-author', 'library-publisher', 'library-language' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );
		add_filter( 'wp_insert_term_data', array( $this, 'name_language' ), 10, 2 );
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
		$this->register_language();
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
			// The tag box lets authors be typed rather than picked from a checklist.
			'meta_box_cb'          => 'post_tags_meta_box',
			'meta_box_sanitize_cb' => array( self::class, 'sanitize_author_names' ),
		);

		register_taxonomy( 'library-author', 'book', $args );
	}

	/**
	 * Turn the author names typed in the tag box into term IDs, creating the missing authors.
	 *
	 * Core only resolves existing names: a hierarchical taxonomy is saved by IDs, so new names would be dropped.
	 * Quick and bulk edit post the checklist of a hierarchical taxonomy instead: term IDs, kept as they are.
	 *
	 * @param string          $taxonomy Taxonomy name.
	 * @param string|string[] $terms    Comma-separated names from the tag box, or term IDs from the checklist.
	 * @return int[]
	 */
	public static function sanitize_author_names( string $taxonomy, $terms ): array {
		// The helpers of the tag box are admin ones: a book added through the REST API needs them too.
		require_once ABSPATH . 'wp-admin/includes/post.php';

		if ( is_array( $terms ) ) {
			return taxonomy_meta_box_sanitize_cb_checkboxes( $taxonomy, $terms );
		}

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
	 * The term of a name under a parent, such as a collection under its publisher: the existing one, else a new one.
	 *
	 * The names of authors go through sanitize_author_names() instead, as in their tag box.
	 *
	 * @param string $taxonomy  Taxonomy name.
	 * @param string $name      Name of the term.
	 * @param int    $parent_id ID of its parent, 0 for a top-level term.
	 * @return int Term ID, or 0 for an empty name or a term that could not be added.
	 */
	public static function get_term_id( string $taxonomy, string $name, int $parent_id = 0 ): int {
		$name = trim( $name );

		if ( '' === $name ) {
			return 0;
		}

		$term = term_exists( $name, $taxonomy, $parent_id );

		if ( ! $term ) {
			$term = wp_insert_term( $name, $taxonomy, array( 'parent' => $parent_id ) );
		}

		return is_array( $term ) ? (int) $term['term_id'] : 0;
	}

	/**
	 * Register publisher
	 *
	 * @return void
	 */
	public function register_publisher() {
		$labels = array(
			'name'                     => _x( 'Publishers', 'library publishers general name', 'library' ),
			'singular_name'            => _x( 'Publisher', 'library publishers singular name', 'library' ),
			'search_items'             => __( 'Search Publishers', 'library' ),
			'all_items'                => __( 'All Publishers', 'library' ),
			'popular_items'            => __( 'Popular Publishers', 'library' ),
			'edit_item'                => __( 'Edit Publisher', 'library' ),
			'view_item'                => __( 'View Publisher', 'library' ),
			'update_item'              => __( 'Update Publisher', 'library' ),
			'add_new_item'             => __( 'Add New Publisher', 'library' ),
			'new_item_name'            => __( 'New Publisher Name', 'library' ),
			'add_or_remove_items'      => __( 'Add or remove publishers', 'library' ),
			'choose_from_most_used'    => __( 'Choose from the most used publishers', 'library' ),
			'not_found'                => __( 'No publishers found.', 'library' ),
			'no_terms'                 => __( 'No publishers', 'library' ),
			'items_list_navigation'    => __( 'Publishers list navigation', 'library' ),
			'items_list'               => __( 'Publishers list', 'library' ),
			/* translators: Publisher heading when selecting from the most used terms. */
			'most_used'                => _x( 'Most Used', 'editor', 'library' ),
			'back_to_items'            => __( '&larr; Back to Publishers', 'library' ),
			'parent_item'              => __( 'Parent Publisher', 'library' ),
			'parent_item_colon'        => __( 'Parent Publisher:', 'library' ),
			'parent_field_description' => __( 'Pick the publisher of a collection, such as Gallimard for Folio. A publisher has none.', 'library' ),
		);

		$args = array(
			'labels'             => $labels,
			'hierarchical'       => true, // Parent = publisher, children = collections, shown as a tree in the core checklist box.
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
		);

		register_taxonomy( 'library-publisher', 'book', $args );
	}

	/**
	 * Register original language
	 *
	 * A language is identified by its ISO 639 code, the slug of the term, e.g. "it". A book read in its original
	 * language is one in a foreign language, i.e. not the language of the admin, without translator.
	 *
	 * @return void
	 */
	public function register_language() {
		$labels = array(
			'name'                  => _x( 'Original languages', 'library languages general name', 'library' ),
			'singular_name'         => _x( 'Original language', 'library languages singular name', 'library' ),
			'menu_name'             => _x( 'Languages', 'library languages menu name', 'library' ),
			'search_items'          => __( 'Search Languages', 'library' ),
			'all_items'             => __( 'All Languages', 'library' ),
			'edit_item'             => __( 'Edit Language', 'library' ),
			'view_item'             => __( 'View Language', 'library' ),
			'update_item'           => __( 'Update Language', 'library' ),
			'add_new_item'          => __( 'Add New Language', 'library' ),
			'new_item_name'         => __( 'New Language Name', 'library' ),
			'not_found'             => __( 'No languages found.', 'library' ),
			'no_terms'              => __( 'No languages', 'library' ),
			'items_list_navigation' => __( 'Languages list navigation', 'library' ),
			'items_list'            => __( 'Languages list', 'library' ),
			'back_to_items'         => __( '&larr; Back to Languages', 'library' ),
		);

		$args = array(
			'labels'             => $labels,
			'hierarchical'       => true, // For the checklist box, in quick and bulk edit too.
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
		);

		register_taxonomy( 'library-language', 'book', $args );
	}

	/**
	 * Name a new language typed as its ISO 639 code, e.g. "it" becomes "Italien" in a French site; the code stays its slug.
	 *
	 * @param array  $data     Term data: name, slug and group.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public function name_language( array $data, string $taxonomy ): array {
		if ( 'library-language' !== $taxonomy || ! class_exists( 'Locale' ) || ! preg_match( '/^[a-z]{2,3}$/', $data['name'] ) ) {
			return $data;
		}

		$name = Locale::getDisplayLanguage( $data['name'], get_locale() );

		if ( $name !== $data['name'] ) {
			$data['name'] = mb_strtoupper( mb_substr( $name, 0, 1 ) ) . mb_substr( $name, 1 );
		}

		return $data;
	}

	/**
	 * Collections of a book, i.e. its publishers that have a parent.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term[]
	 */
	public static function get_collections( int $book_id ): array {
		$terms = get_the_terms( $book_id, 'library-publisher' );

		return array_values( wp_list_filter( is_array( $terms ) ? $terms : array(), array( 'parent' => 0 ), 'NOT' ) );
	}

	/**
	 * Terms as they count, each one once: a pseudonym for its real name, a collection for its publisher, i.e. their parent.
	 *
	 * @param WP_Term[] $terms Terms of a taxonomy.
	 * @return WP_Term[]
	 */
	public static function get_top_terms( array $terms ): array {
		$top_terms = array();

		foreach ( $terms as $term ) {
			$top_term = $term->parent ? get_term( $term->parent, $term->taxonomy ) : $term;

			if ( $top_term instanceof WP_Term ) {
				$top_terms[ $top_term->term_id ] = $top_term;
			}
		}

		return array_values( $top_terms );
	}

	/**
	 * Include children (pseudonyms, collections) in term counts.
	 *
	 * @param array    $args       Arguments passés à get_terms().
	 * @param string[] $taxonomies Taxonomies demandées.
	 * @return array
	 */
	public function get_terms_args( array $args, array $taxonomies ): array {
		// Counts only exist on full terms; padding other queries breaks the core cache.
		if ( ! array_intersect( self::TAXONOMIES, $taxonomies ) || 'all' !== $args['fields'] ) {
			return $args;
		}

		$args['pad_counts'] = true;

		return $args;
	}
}
