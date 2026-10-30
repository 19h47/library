<?php
/**
 * Terms lists of the admin: the books not kept of a term, and the messages of a term saved.
 *
 * @link       https://github.com/19h47/library
 * @since      6.1.1
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Adds a "Not kept" column to the lists of people, publishers, original languages and forms, and names their messages.
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Terms_List {

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
		add_filter( 'term_updated_messages', array( $this, 'add_term_messages' ) );

		foreach ( Library_Taxonomies::TAXONOMIES as $taxonomy ) {
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'add_not_kept_column' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'render_not_kept_column' ), 10, 3 );
		}
	}

	/**
	 * Add the messages shown when a person, a publisher, a language or a form is added, updated or deleted.
	 *
	 * @param array $messages Array of arrays of messages to be displayed, keyed by taxonomy name.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/term_updated_messages/
	 *
	 * @return array
	 */
	public function add_term_messages( array $messages ): array {
		$messages['library_person'] = array(
			0 => '',
			1 => __( 'Person added.', 'library' ),
			2 => __( 'Person deleted.', 'library' ),
			3 => __( 'Person updated.', 'library' ),
			4 => __( 'Person not added.', 'library' ),
			5 => __( 'Person not updated.', 'library' ),
			6 => __( 'People deleted.', 'library' ),
		);

		$messages['library_publisher'] = array(
			0 => '',
			1 => __( 'Publisher added.', 'library' ),
			2 => __( 'Publisher deleted.', 'library' ),
			3 => __( 'Publisher updated.', 'library' ),
			4 => __( 'Publisher not added.', 'library' ),
			5 => __( 'Publisher not updated.', 'library' ),
			6 => __( 'Publishers deleted.', 'library' ),
		);

		$messages['library_language'] = array(
			0 => '',
			1 => __( 'Language added.', 'library' ),
			2 => __( 'Language deleted.', 'library' ),
			3 => __( 'Language updated.', 'library' ),
			4 => __( 'Language not added.', 'library' ),
			5 => __( 'Language not updated.', 'library' ),
			6 => __( 'Languages deleted.', 'library' ),
		);

		$messages['library_form'] = array(
			0 => '',
			1 => __( 'Form added.', 'library' ),
			2 => __( 'Form deleted.', 'library' ),
			3 => __( 'Form updated.', 'library' ),
			4 => __( 'Form not added.', 'library' ),
			5 => __( 'Form not updated.', 'library' ),
			6 => __( 'Forms deleted.', 'library' ),
		);

		return $messages;
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
				'post_type'     => 'library_book',
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
					INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id AND p.post_type = 'library_book' AND p.post_status = %s
					GROUP BY 1",
					Library_Posts::NOT_KEPT
				)
			);
			// phpcs:enable

			$this->not_kept_counts = array_replace( $this->not_kept_counts, array_map( 'intval', wp_list_pluck( $rows, 'books', 'term_id' ) ) );
		}

		return $this->not_kept_counts;
	}
}
