<?php
/**
 * Query modifications: search in book meta.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Extends book searches to the book text meta (ISBN, series, translators…).
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
	 * Also match books whose text meta contains the search phrase.
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
		$values       = array( ...Library_Meta::TEXT_KEYS, $like );
		$meta_matches = $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( " . implode( ', ', array_fill( 0, count( Library_Meta::TEXT_KEYS ), '%s' ) ) . ' ) AND meta_value LIKE %s',
			$values
		);

		return " AND ( {$wpdb->posts}.ID IN ( $meta_matches ) OR ( 1 = 1 $search ) )";
	}
}
