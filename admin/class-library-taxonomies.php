<?php
/**
 * Book taxonomies: author, publisher and original language.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers library-author, library-publisher and library-language taxonomies.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
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
	 * Books not kept by term ID, with the pseudonyms counted for their real name.
	 *
	 * @var array<int,int>|null
	 */
	private ?array $not_kept_counts = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		add_filter( 'term_updated_messages', array( $this, 'messages' ) );
		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 10, 2 );
		add_filter( 'post_column_taxonomy_links', array( $this, 'column_term_links' ), 10, 3 );
		add_filter( 'wp_insert_term_data', array( $this, 'name_language' ), 10, 2 );
		add_action( 'add_meta_boxes_book', array( $this, 'register_author_box' ) );

		foreach ( self::TAXONOMIES as $taxonomy ) {
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'add_not_kept_column' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'render_not_kept_column' ), 10, 3 );
		}
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
			'labels'              => $labels,
			'hierarchical'        => true, // Parent = publisher, children = collections, shown as a tree in the core checklist box.
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
			'labels'              => $labels,
			'hierarchical'        => true, // For the checklist box, in quick and bulk edit too.
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_quick_edit'  => true,
			'show_admin_column'   => true,
			'show_in_rest'        => true,
			'show_in_graphql'     => true,
			'graphql_single_name' => 'language',
			'graphql_plural_name' => 'languages',
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

		$messages['library-language'] = array(
			0 => '',
			1 => __( 'Language added.', 'library' ),
			2 => __( 'Language deleted.', 'library' ),
			3 => __( 'Language updated.', 'library' ),
			4 => __( 'Language not added.', 'library' ),
			5 => __( 'Language not updated.', 'library' ),
			6 => __( 'Languages deleted.', 'library' ),
		);

		return $messages;
	}


	/**
	 * Links of the authors, publishers and languages columns of the books list.
	 *
	 * A collection shows as its publisher, once. The links keep the status of the current view:
	 * core links to the "All" view, which leaves out the books not kept, so from their view they listed nothing.
	 *
	 * @param string[]  $term_links Term links.
	 * @param string    $taxonomy   Taxonomy.
	 * @param WP_Term[] $terms      Terms.
	 * @return string[]
	 */
	public function column_term_links( array $term_links, string $taxonomy, array $terms ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view filter.
		$status = isset( $_GET['post_status'] ) ? sanitize_key( wp_unslash( $_GET['post_status'] ) ) : '';

		if ( ! in_array( $taxonomy, self::TAXONOMIES, true ) ) {
			return $term_links;
		}

		if ( 'library-publisher' === $taxonomy ) {
			$terms = self::get_publishers( $terms );
		} elseif ( '' === $status ) {
			return $term_links;
		}

		return array_map(
			function ( WP_Term $term ) use ( $taxonomy, $status ) {
				$query_args = array_filter(
					array(
						'post_type'   => 'book',
						'post_status' => $status,
						$taxonomy     => $term->slug,
					)
				);
				$url        = add_query_arg( $query_args, admin_url( 'edit.php' ) );

				return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $term->name ) );
			},
			$terms
		);
	}

	/**
	 * Publishers of publisher terms: a collection stands for its parent, each publisher once.
	 *
	 * @param WP_Term[] $terms Publisher terms.
	 * @return WP_Term[]
	 */
	public static function get_publishers( array $terms ): array {
		$publishers = array();

		foreach ( $terms as $term ) {
			$publisher = $term->parent ? get_term( $term->parent, 'library-publisher' ) : $term;

			if ( $publisher instanceof WP_Term ) {
				$publishers[ $publisher->term_id ] = $publisher;
			}
		}

		return array_values( $publishers );
	}

	/**
	 * Add a "Not kept" column after the count, which only counts the published books.
	 *
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public function add_not_kept_column( array $columns ): array {
		$columns['library_not_kept'] = _x( 'Not kept', 'book status', 'library' );

		return $columns;
	}

	/**
	 * Number of books not kept of a term, linked to their view.
	 *
	 * @param string $content     Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public function render_not_kept_column( string $content, string $column_name, int $term_id ): string {
		if ( 'library_not_kept' !== $column_name ) {
			return $content;
		}

		$books = $this->get_not_kept_counts()[ $term_id ] ?? 0;
		$term  = get_term( $term_id );

		if ( ! $books || ! $term instanceof WP_Term ) {
			return number_format_i18n( $books );
		}

		$url = add_query_arg(
			array(
				'post_type'     => 'book',
				'post_status'   => Library_Posts::NOT_KEPT,
				$term->taxonomy => $term->slug,
			),
			admin_url( 'edit.php' )
		);

		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( number_format_i18n( $books ) ) );
	}

	/**
	 * Count the books not kept of every term in one query; a real name also counts its pseudonyms' books.
	 *
	 * @return array<int,int>
	 */
	private function get_not_kept_counts(): array {
		if ( null !== $this->not_kept_counts ) {
			return $this->not_kept_counts;
		}

		global $wpdb;

		$this->not_kept_counts = array();

		// Each term, then each real name over itself and its pseudonyms, which overrides its own count.
		foreach ( array( 'tt.term_id', 'COALESCE( NULLIF( tt.parent, 0 ), tt.term_id )' ) as $group ) {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $group is one of the two constant expressions above.
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin terms list only.
				$wpdb->prepare(
					"SELECT $group AS term_id, COUNT( DISTINCT p.ID ) AS books
					FROM {$wpdb->term_relationships} AS tr
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id AND p.post_type = 'book' AND p.post_status = %s
					GROUP BY 1",
					Library_Posts::NOT_KEPT
				)
			);
			// phpcs:enable

			$this->not_kept_counts = array_replace( $this->not_kept_counts, array_map( 'intval', wp_list_pluck( $rows, 'books', 'term_id' ) ) );
		}

		return $this->not_kept_counts;
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
