<?php
/**
 * Query modifications: search in book meta and terms.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Extends book searches to the book text meta (ISBN, series, translators…) and terms (authors, publishers, collections).
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_WP_Query {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'posts_search', array( $this, 'search' ), 10, 2 );
	}

	/**
	 * Also match books whose text meta or terms contain the search phrase; a term matches by its parent too,
	 * so searching for a real name finds its pseudonyms' books and a publisher its collections' books.
	 *
	 * @param string   $search Search SQL, starting with " AND ".
	 * @param WP_Query $query  Query instance.
	 * @return string
	 */
	public function search( string $search, WP_Query $query ): string {
		$post_types = (array) $query->get( 'post_type' );

		if ( '' === $search || ! in_array( 'book', $post_types, true ) ) {
			return $search;
		}

		global $wpdb;

		$like         = '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%';
		$meta_values  = array( ...Library_Meta::TEXT_KEYS, $like );
		$term_values  = array( ...Library_Taxonomies::TAXONOMIES, $like, $like );
		$meta_matches = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( " . implode( ', ', array_fill( 0, count( Library_Meta::TEXT_KEYS ), '%s' ) ) . ' ) AND meta_value LIKE %s',
			$meta_values
		);
		$term_matches = $wpdb->prepare(
			"SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->terms} AS t ON t.term_id = tt.term_id
			LEFT JOIN {$wpdb->terms} AS parent ON parent.term_id = tt.parent
			WHERE tt.taxonomy IN ( " . implode( ', ', array_fill( 0, count( Library_Taxonomies::TAXONOMIES ), '%s' ) ) . ' ) AND ( t.name LIKE %s OR parent.name LIKE %s )',
			$term_values
		);

		return " AND ( {$wpdb->posts}.ID IN ( $meta_matches ) OR {$wpdb->posts}.ID IN ( $term_matches ) OR ( 1 = 1 $search ) )";
	}
}
