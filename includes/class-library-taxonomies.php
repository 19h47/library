<?php
/**
 * Book taxonomies: person, publisher, series, original language and form.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Registers library_person, library_publisher, library_series, library_language and library_form taxonomies.
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
	public const TAXONOMIES = array( 'library_person', 'library_publisher', 'library_series', 'library_language', 'library_form' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_filter( 'get_terms_args', array( $this, 'count_children' ), 10, 2 );
		add_filter( 'pre_insert_term', array( $this, 'require_language_code' ), 10, 3 );
		add_filter( 'wp_insert_term_data', array( $this, 'name_language' ), 10, 2 );
		// Before core adds the languages typed in the box of a book, at 10.
		add_action( 'wp_ajax_add-library_language', array( $this, 'explain_refused_language' ), 9 );
	}

	/**
	 * Register taxonomies.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_taxonomies(): void {
		$this->register_person();
		$this->register_publisher();
		$this->register_series();
		$this->register_language();
		$this->register_form();
	}

	/**
	 * Register person
	 *
	 * @return void
	 */
	public function register_person() {
		$labels = array(
			'name'                       => _x( 'People', 'library people general name', 'library' ),
			'singular_name'              => _x( 'Person', 'library people singular name', 'library' ),
			'search_items'               => __( 'Search People', 'library' ),
			'all_items'                  => __( 'All People', 'library' ),
			'popular_items'              => __( 'Popular People', 'library' ),
			'edit_item'                  => __( 'Edit Person', 'library' ),
			'view_item'                  => __( 'View Person', 'library' ),
			'update_item'                => __( 'Update Person', 'library' ),
			'add_new_item'               => __( 'Add New Person', 'library' ),
			'new_item_name'              => __( 'New Person Name', 'library' ),
			'separate_items_with_commas' => __( 'Separate people with commas', 'library' ),
			'add_or_remove_items'        => __( 'Add or remove people', 'library' ),
			'choose_from_most_used'      => __( 'Choose from the most used people', 'library' ),
			'not_found'                  => __( 'No people found.', 'library' ),
			'no_terms'                   => __( 'No people', 'library' ),
			'items_list_navigation'      => __( 'People list navigation', 'library' ),
			'items_list'                 => __( 'People list', 'library' ),
			/* translators: Person heading when selecting from the most used terms. */
			'most_used'                  => _x( 'Most Used', 'person', 'library' ),
			'back_to_items'              => __( '&larr; Back to People', 'library' ),
		);

		$args = array(
			'labels'               => $labels,
			'hierarchical'         => true, // Parent = nom sous lequel la personne est connue, enfants = ses autres noms.
			'public'               => true,
			'publicly_queryable'   => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_nav_menus'    => true,
			'show_in_quick_edit'   => true,
			'show_admin_column'    => true,
			'show_in_rest'         => true,
			// As core names its own: tags under /wp/v2/tags. Each name of a book is a person, whatever their role, as the BnF has it.
			'rest_base'            => 'people',
			'rewrite'              => array( 'slug' => 'library-person' ),
			// The tag box lets people be typed rather than picked from a checklist.
			'meta_box_cb'          => 'post_tags_meta_box',
			'meta_box_sanitize_cb' => array( self::class, 'sanitize_person_names' ),
		);

		register_taxonomy( 'library_person', 'library_book', $args );
	}

	/**
	 * Turn the names typed in the tag box into term IDs, creating the missing people.
	 *
	 * Core only resolves existing names: a hierarchical taxonomy is saved by IDs, so new names would be dropped.
	 * Quick and bulk edit post the checklist of a hierarchical taxonomy instead: term IDs, kept as they are.
	 *
	 * @param string          $taxonomy Taxonomy name.
	 * @param string|string[] $terms    Comma-separated names from the tag box, or term IDs from the checklist.
	 * @return int[]
	 */
	public static function sanitize_person_names( string $taxonomy, $terms ): array {
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
	 * The names of people go through sanitize_person_names() instead, as in their tag box.
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
			'parent_field_description' => __( 'Pick the publisher of a collection, such as Gallimard for Folio, or the collection of a sub-collection, such as Folio for its Science-fiction. A publisher has none.', 'library' ),
		);

		$args = array(
			'labels'             => $labels,
			'hierarchical'       => true, // Parent = publisher, children = collections, their children sub-collections, shown as a tree in the core checklist box.
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'publishers',
			'rewrite'            => array( 'slug' => 'library-publisher' ),
		);

		register_taxonomy( 'library_publisher', 'library_book', $args );
	}

	/**
	 * Register series
	 *
	 * A series of a book, under its cycle, if any, as a collection is under its publisher: "Les Jeunes Chevaliers Jedi"
	 * under "Star Wars". A book has one series, the narrowest; its volume number is a meta of the book. Its description
	 * shows on the page of the series in the reading app.
	 *
	 * @return void
	 */
	public function register_series() {
		$labels = array(
			'name'                     => _x( 'Series', 'library series general name', 'library' ),
			'singular_name'            => _x( 'Series', 'library series singular name', 'library' ),
			'search_items'             => __( 'Search Series', 'library' ),
			'all_items'                => __( 'All Series', 'library' ),
			'edit_item'                => __( 'Edit Series', 'library' ),
			'view_item'                => __( 'View Series', 'library' ),
			'update_item'              => __( 'Update Series', 'library' ),
			'add_new_item'             => __( 'Add New Series', 'library' ),
			'new_item_name'            => __( 'New Series Name', 'library' ),
			'not_found'                => __( 'No series found.', 'library' ),
			'no_terms'                 => __( 'No series', 'library' ),
			'items_list_navigation'    => __( 'Series list navigation', 'library' ),
			'items_list'               => __( 'Series list', 'library' ),
			'back_to_items'            => __( '&larr; Back to Series', 'library' ),
			'parent_item'              => __( 'Cycle', 'library' ),
			'parent_item_colon'        => __( 'Cycle:', 'library' ),
			'parent_field_description' => __( 'Pick the cycle of a series, such as Star Wars for Young Jedi Knights. A series without cycle has none.', 'library' ),
		);

		$args = array(
			'labels'             => $labels,
			// Typed in the Information box of a book, from its cycle to its series, rather than ticked in a box.
			'description'        => __( 'From the cycle, if any, to the series, separated with commas, such as “Star Wars, Young Jedi Knights”; the volume number goes with the series.', 'library' ),
			'hierarchical'       => true, // Parent = cycle, children = its series.
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_quick_edit' => false,
			'show_admin_column'  => false,
			'meta_box_cb'        => false,
			'show_in_rest'       => true,
			'rest_base'          => 'series',
			'rewrite'            => array( 'slug' => 'library-series' ),
		);

		register_taxonomy( 'library_series', 'library_book', $args );
	}

	/**
	 * The series of a book, the narrowest one, or null.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term|null
	 */
	public static function get_series( int $book_id ): ?WP_Term {
		$terms = get_the_terms( $book_id, 'library_series' );

		return is_array( $terms ) ? $terms[0] : null;
	}

	/**
	 * A series from its cycle, as a book types it, in plain text: "Star Wars, Young Jedi Knights".
	 *
	 * @param WP_Term $series Series.
	 * @return string
	 */
	public static function get_series_path( WP_Term $series ): string {
		return implode( ', ', self::get_path_names( $series ) );
	}

	/**
	 * Every series and cycle, after its cycles, in plain text, in order, as a form suggests them.
	 *
	 * @return string[]
	 */
	public static function get_series_paths(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'library_series',
				'hide_empty' => false,
			)
		);
		$paths = array_map( array( self::class, 'get_series_path' ), is_array( $terms ) ? $terms : array() );

		natcasesort( $paths );

		return array_values( $paths );
	}

	/**
	 * Names of a term from its widest ancestor, in plain text, such as a series from its widest cycle or a collection
	 * from its publisher: the names of terms keep their & as &amp;.
	 *
	 * @param WP_Term $term Term of a hierarchical taxonomy.
	 * @return string[]
	 */
	private static function get_path_names( WP_Term $term ): array {
		$ancestors = array_map( fn( int $term_id ) => get_term( $term_id, $term->taxonomy ), array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) );

		return array_map( fn( WP_Term $item ) => wp_specialchars_decode( $item->name, ENT_QUOTES ), array( ...$ancestors, $term ) );
	}

	/**
	 * The series a search names, case aside, with the series of their cycles: "Star Wars" the cycle and its series, "Star
	 * Wars, Young Jedi Knights" this one, "Young Jedi Knights" any series of that name.
	 *
	 * @param string $value Name of a series, or of a series after its cycles, separated with commas.
	 * @return int[] Term IDs.
	 */
	public static function find_series_ids( string $value ): array {
		$names = array_map( 'mb_strtolower', Library_Books::split( $value ) );
		$terms = $names ? get_terms(
			array(
				'taxonomy'   => 'library_series',
				'name'       => end( $names ),
				'hide_empty' => false,
			)
		) : array();
		$ids   = array();

		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$path = array_map( 'mb_strtolower', self::get_path_names( $term ) );

			if ( array_slice( $path, -count( $names ) ) === $names ) {
				$ids = array( ...$ids, $term->term_id, ...get_term_children( $term->term_id, 'library_series' ) );
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * The term a book types as a path, each name under the one before: a series from its cycle, such as "Star Wars, Young
	 * Jedi Knights", or a collection from its parent collection under its publisher, such as "Folio, Science-fiction" under
	 * Gallimard. The existing term, else a new one when `$add`, else 0.
	 *
	 * @param string $taxonomy  Taxonomy name.
	 * @param string $path      Names, separated with commas.
	 * @param bool   $add       Whether to add the names missing.
	 * @param int    $parent_id ID of the term the path is under, 0 for the top level.
	 * @return int Term ID, or 0 for an empty path or a term not found.
	 */
	public static function get_path_id( string $taxonomy, string $path, bool $add = false, int $parent_id = 0 ): int {
		$names = Library_Books::split( $path );

		foreach ( $names as $name ) {
			$term      = term_exists( $name, $taxonomy, $parent_id );
			$parent_id = is_array( $term ) ? (int) $term['term_id'] : ( $add ? self::get_term_id( $taxonomy, $name, $parent_id ) : 0 );

			if ( ! $parent_id ) {
				return 0;
			}
		}

		return $names ? $parent_id : 0;
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
			'rest_base'          => 'languages',
			'rewrite'            => array( 'slug' => 'library-language' ),
		);

		register_taxonomy( 'library_language', 'library_book', $args );
	}

	/**
	 * Register form
	 *
	 * What a book is, such as a novel, a comic or a magazine, one for each book.
	 *
	 * @return void
	 */
	public function register_form() {
		$labels = array(
			'name'                  => _x( 'Forms', 'library forms general name', 'library' ),
			'singular_name'         => _x( 'Form', 'library forms singular name', 'library' ),
			'search_items'          => __( 'Search Forms', 'library' ),
			'all_items'             => __( 'All Forms', 'library' ),
			'edit_item'             => __( 'Edit Form', 'library' ),
			'view_item'             => __( 'View Form', 'library' ),
			'update_item'           => __( 'Update Form', 'library' ),
			'add_new_item'          => __( 'Add New Form', 'library' ),
			'new_item_name'         => __( 'New Form Name', 'library' ),
			'not_found'             => __( 'No forms found.', 'library' ),
			'no_terms'              => __( 'No forms', 'library' ),
			'items_list_navigation' => __( 'Forms list navigation', 'library' ),
			'items_list'            => __( 'Forms list', 'library' ),
			'back_to_items'         => __( '&larr; Back to Forms', 'library' ),
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
			'rest_base'          => 'forms',
			'rewrite'            => array( 'slug' => 'library-form' ),
		);

		register_taxonomy( 'library_form', 'library_book', $args );
	}

	/**
	 * Give a new language its ISO 639 code as its slug, and refuse it without one: the app names a language from its
	 * code, and an invalid one, such as "norvegien" for a language typed as "Norvégien", stops it.
	 *
	 * The code is its slug when given, else its name when it is one, else the one of its name, such as no for Norvégien
	 * or Norwegian: the language is then added as its code, which name_language() names. A code already taken is
	 * refused too, rather than made unique, such as it-2.
	 *
	 * @param string|WP_Error      $term     Name of the term, or the error of an earlier filter.
	 * @param string               $taxonomy Taxonomy slug.
	 * @param array<string, mixed> $args     Arguments of the term, its slug among them.
	 * @return string|WP_Error
	 */
	public function require_language_code( $term, string $taxonomy, array $args ) {
		if ( 'library_language' !== $taxonomy || is_wp_error( $term ) ) {
			return $term;
		}

		$typed = empty( $args['slug'] ) ? $term : $args['slug'];
		$code  = sanitize_title( $typed );

		if ( ! preg_match( '/^[a-z]{2,3}$/', $code ) ) {
			$code = empty( $args['slug'] ) ? self::get_language_code( $term ) : '';
			$term = $code;
		}

		if ( '' === $code ) {
			/* translators: %s: name or slug typed for a language, e.g. Norvégien. */
			$message = sprintf( __( '%s is neither the name of a language nor its ISO 639 code, such as Norwegian or no.', 'library' ), $typed );

			return new WP_Error( 'library_language_code', $message, array( 'status' => 400 ) );
		}

		$existing = get_term_by( 'slug', $code, $taxonomy );

		if ( $existing ) {
			/* translators: %s: name of a language, e.g. Norvégien. */
			return new WP_Error( 'term_exists', sprintf( __( '%s is already among the languages.', 'library' ), $existing->name ), $existing->term_id );
		}

		return $term;
	}

	/**
	 * ISO 639 code of a language from its name, in the language of the user, of the site or in English, whatever its case
	 * and accents, such as no for Norvégien, norvegien or Norwegian; empty when no language has that name.
	 *
	 * The names are the ones ICU holds, as the app names a language with them from its code.
	 *
	 * @param string $name Name of a language.
	 * @return string
	 */
	private static function get_language_code( string $name ): string {
		if ( ! class_exists( 'ResourceBundle' ) ) {
			return '';
		}

		$name = remove_accents( mb_strtolower( trim( $name ) ) );

		foreach ( array_unique( array( get_user_locale(), get_locale(), 'en' ) ) as $locale ) {
			$languages = ResourceBundle::create( $locale, 'ICUDATA-lang' )?->get( 'Languages' ) ?? array();

			foreach ( $languages as $code => $language ) {
				if ( preg_match( '/^[a-z]{2,3}$/', $code ) && remove_accents( mb_strtolower( $language ) ) === $name ) {
					return $code;
				}
			}
		}

		return '';
	}

	/**
	 * Say why a language added from the box of a book is refused, such as a name no language has: core leaves a refused
	 * term out of the box without a word. Its message shows under the box, as core shows its errors there.
	 *
	 * @return void
	 */
	public function explain_refused_language(): void {
		check_ajax_referer( 'add-library_language', '_ajax_nonce-add-library_language' );

		$names = explode( ',', sanitize_text_field( wp_unslash( $_POST['newlibrary_language'] ?? '' ) ) );

		foreach ( $names as $name ) {
			$term = '' !== sanitize_title( $name ) ? $this->require_language_code( trim( $name ), 'library_language', array() ) : $name;

			if ( is_wp_error( $term ) ) {
				$response = new WP_Ajax_Response(
					array(
						'what' => 'library_language',
						'data' => $term,
					)
				);

				$response->send();
			}
		}
	}

	/**
	 * Name a new language typed as its ISO 639 code, e.g. "it" becomes "Italien" in a French site; the code stays its slug.
	 *
	 * @param array  $data     Term data: name, slug and group.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array
	 */
	public function name_language( array $data, string $taxonomy ): array {
		if ( 'library_language' !== $taxonomy || ! class_exists( 'Locale' ) || ! preg_match( '/^[a-z]{2,3}$/', $data['name'] ) ) {
			return $data;
		}

		$name = Locale::getDisplayLanguage( $data['name'], get_locale() );

		if ( $name !== $data['name'] ) {
			$data['name'] = mb_strtoupper( mb_substr( $name, 0, 1 ) ) . mb_substr( $name, 1 );
		}

		return $data;
	}

	/**
	 * Authors of a book, i.e. its names without another role, such as a colorist.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term[]
	 */
	public static function get_authors( int $book_id ): array {
		$terms = get_the_terms( $book_id, 'library_person' );
		$roles = Library_Meta::get_roles( $book_id );

		return array_values( array_filter( is_array( $terms ) ? $terms : array(), fn( WP_Term $term ) => ! isset( $roles[ $term->term_id ] ) ) );
	}

	/**
	 * Contributors of a book, i.e. its names with another role than author, such as a colorist, or those of a role.
	 *
	 * @param int    $book_id Book ID.
	 * @param string $role    Role, such as translator, or empty for all.
	 * @return WP_Term[]
	 */
	public static function get_contributors( int $book_id, string $role = '' ): array {
		$terms = get_the_terms( $book_id, 'library_person' );
		$roles = Library_Meta::get_roles( $book_id );

		return array_values(
			array_filter(
				is_array( $terms ) ? $terms : array(),
				fn( WP_Term $term ) => isset( $roles[ $term->term_id ] ) && ( '' === $role || $role === $roles[ $term->term_id ] )
			)
		);
	}

	/**
	 * People credited for a book: its authors, or the publishing directors of a collective work, as catalogues have it,
	 * none of its authors.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term[]
	 */
	public static function get_credited_people( int $book_id ): array {
		return Library_Posts::is_collective( $book_id ) ? self::get_contributors( $book_id, 'director' ) : self::get_authors( $book_id );
	}

	/**
	 * Collections of a book, i.e. its publishers that have a parent: a collection is under its publisher, a sub-collection
	 * under its collection, such as Science-fiction under Folio under Gallimard.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term[]
	 */
	public static function get_collections( int $book_id ): array {
		$terms = get_the_terms( $book_id, 'library_publisher' );

		return array_values( wp_list_filter( is_array( $terms ) ? $terms : array(), array( 'parent' => 0 ), 'NOT' ) );
	}

	/**
	 * Collections of a book and the collections they are part of, each one once, as a series counts for its cycles: a book
	 * of Folio's Science-fiction is in Folio too.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term[]
	 */
	public static function get_collections_and_parents( int $book_id ): array {
		$collections = array();

		foreach ( self::get_collections( $book_id ) as $collection ) {
			for ( $term = $collection; $term instanceof WP_Term && $term->parent; $term = get_term( $term->parent, 'library_publisher' ) ) {
				$collections[ $term->term_id ] = $term;
			}
		}

		return array_values( $collections );
	}

	/**
	 * Name of a collection after the collections it is part of, without its publisher, in plain text: "Folio,
	 * Science-fiction"; a publisher by its name.
	 *
	 * @param WP_Term $publisher Term of the publishers.
	 * @return string
	 */
	public static function get_collection_name( WP_Term $publisher ): string {
		$names = self::get_path_names( $publisher );

		return implode( ', ', count( $names ) > 1 ? array_slice( $names, 1 ) : $names );
	}

	/**
	 * Terms as they count, each one once: another name for the name of its person, a collection for its publisher, i.e. their
	 * widest ancestor.
	 *
	 * @param WP_Term[] $terms Terms of a taxonomy.
	 * @return WP_Term[]
	 */
	public static function get_top_level_terms( array $terms ): array {
		$top_level_terms = array();

		foreach ( $terms as $term ) {
			$ancestors      = get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' );
			$top_level_term = $ancestors ? get_term( end( $ancestors ), $term->taxonomy ) : $term;

			if ( $top_level_term instanceof WP_Term ) {
				$top_level_terms[ $top_level_term->term_id ] = $top_level_term;
			}
		}

		return array_values( $top_level_terms );
	}

	/**
	 * SQL derived table of the terms under another one, each with its widest ancestor, term_id and top_id: another name
	 * counts for the name of its person, a collection for its publisher, at any depth, which MySQL 5.7 cannot follow.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	public static function build_top_level_table( string $taxonomy ): string {
		$parents = self::get_parents( $taxonomy );
		$rows    = array();

		foreach ( array_filter( $parents ) as $term_id => $top_id ) {
			while ( ! empty( $parents[ $top_id ] ) ) {
				$top_id = $parents[ $top_id ];
			}

			$rows[] = sprintf( 'SELECT %d AS term_id, %d AS top_id', $term_id, $top_id );
		}

		return '( ' . ( $rows ? implode( ' UNION ALL ', $rows ) : 'SELECT 0 AS term_id, 0 AS top_id' ) . ' )';
	}

	/**
	 * SQL derived table of the collections, each with itself and the collections it is part of, term_id and collection_id:
	 * a book of Folio's Science-fiction counts for Folio too.
	 *
	 * @return string
	 */
	public static function build_collections_table(): string {
		$parents = self::get_parents( 'library_publisher' );
		$rows    = array();

		foreach ( array_keys( array_filter( $parents ) ) as $term_id ) {
			for ( $collection_id = $term_id; ! empty( $parents[ $collection_id ] ); $collection_id = $parents[ $collection_id ] ) {
				$rows[] = sprintf( 'SELECT %d AS term_id, %d AS collection_id', $term_id, $collection_id );
			}
		}

		return '( ' . ( $rows ? implode( ' UNION ALL ', $rows ) : 'SELECT 0 AS term_id, 0 AS collection_id' ) . ' )';
	}

	/**
	 * Parent of each term of a taxonomy, by term ID, 0 for a top-level term.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int,int>
	 */
	private static function get_parents( string $taxonomy ): array {
		$parents = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'id=>parent',
			)
		);

		return is_array( $parents ) ? array_map( 'intval', $parents ) : array();
	}

	/**
	 * Include children (other names of a person, collections) in term counts.
	 *
	 * @param array    $args       Arguments of get_terms().
	 * @param string[] $taxonomies Taxonomies queried.
	 * @return array
	 */
	public function count_children( array $args, array $taxonomies ): array {
		// Counts only exist on full terms; padding other queries breaks the core cache.
		if ( ! array_intersect( self::TAXONOMIES, $taxonomies ) || 'all' !== $args['fields'] ) {
			return $args;
		}

		$args['pad_counts'] = true;

		return $args;
	}
}
