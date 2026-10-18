<?php
/**
 * Books data shared by the reading idea and the achievements.
 *
 * @link       https://github.com/19h47/library
 * @since      2.2.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Loads all the books once per request, with their meta and terms.
 *
 * @since      2.2.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Books {

	/**
	 * Books of the current request.
	 *
	 * @var array<int,array>|null
	 */
	private static ?array $books = null;

	/**
	 * All the books, kept or not.
	 *
	 * A book without series but with a volume number is a volume of the series named by its title, e.g. "City Hunter".
	 * Authors are counted for their real name. The date added is the post date.
	 *
	 * @return array<int,array{id:int,title:string,status:string,series:string,volume:int|null,read:bool,read_date:string,rating:float|null,pages:int|null,edition_date:string,published_date:string,added:string,authors:string[],author_ids:int[],translators:string[],collections:string[],collection_ids:int[],languages:string[]}>
	 */
	public static function all(): array {
		if ( null !== self::$books ) {
			return self::$books;
		}

		$posts = get_posts(
			array(
				'post_type'   => 'book',
				'post_status' => array_diff( get_post_stati(), array( 'trash', 'auto-draft' ) ),
				'numberposts' => -1,
			)
		);

		self::$books = array();

		foreach ( $posts as $post ) {
			$volume      = preg_match( '/\d+/', (string) get_post_meta( $post->ID, 'volume_number', true ), $match ) ? (int) $match[0] : null;
			$series      = (string) get_post_meta( $post->ID, 'series', true );
			$rating      = get_post_meta( $post->ID, 'rating', true );
			$pages       = (int) get_post_meta( $post->ID, 'pages', true );
			$authors     = self::get_real_authors( $post->ID );
			$collections = Library_Taxonomies::get_collections( $post->ID );

			if ( '' === $series && null !== $volume ) {
				$series = $post->post_title;
			}

			self::$books[ $post->ID ] = array(
				'id'             => $post->ID,
				'title'          => $post->post_title,
				'status'         => $post->post_status,
				'series'         => $series,
				'volume'         => $volume,
				'read'           => Library_Posts::is_read( $post->ID ),
				'read_date'      => (string) get_post_meta( $post->ID, 'read_date', true ),
				'rating'         => '' !== $rating ? (float) $rating : null,
				'pages'          => $pages ? $pages : null,
				'edition_date'   => (string) get_post_meta( $post->ID, 'edition_date', true ),
				'published_date' => (string) get_post_meta( $post->ID, 'published_date', true ),
				'added'          => $post->post_date,
				'authors'        => wp_list_pluck( $authors, 'name' ),
				'author_ids'     => wp_list_pluck( $authors, 'term_id' ),
				'translators'    => self::split( (string) get_post_meta( $post->ID, 'translators', true ) ),
				'collections'    => wp_list_pluck( $collections, 'name' ),
				'collection_ids' => wp_list_pluck( $collections, 'term_id' ),
				'languages'      => wp_get_post_terms( $post->ID, 'library-language', array( 'fields' => 'names' ) ),
			);
		}

		return self::$books;
	}

	/**
	 * Authors of a book, a pseudonym counted for its real name, each one once.
	 *
	 * @param int $book_id Book ID.
	 * @return WP_Term[]
	 */
	private static function get_real_authors( int $book_id ): array {
		$terms   = get_the_terms( $book_id, 'library-author' );
		$authors = array();

		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$author = $term->parent ? get_term( $term->parent, 'library-author' ) : $term;

			if ( $author instanceof WP_Term ) {
				$authors[ $author->term_id ] = $author;
			}
		}

		return array_values( $authors );
	}

	/**
	 * Values of a field separated with commas.
	 *
	 * @param string $value Field value.
	 * @return string[]
	 */
	private static function split( string $value ): array {
		return array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) );
	}
}
