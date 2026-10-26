<?php
/**
 * Books data shared by the reading idea, the achievements and the REST API, and how a book, a number of books and a rating are said.
 *
 * @link       https://github.com/19h47/library
 * @since      2.2.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Loads all the books once per request, with their meta and terms, and says a book, a number of books and a rating the same way everywhere.
 *
 * @since      2.2.0
 * @package    Library
 * @subpackage Library/includes
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
	 * Statuses of the books: every one but the trash, kept or not.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array_values( array_diff( get_post_stati(), array( 'trash', 'auto-draft' ) ) );
	}

	/**
	 * All the books, kept or not.
	 *
	 * A book without series but with a volume number is a volume of the series named by its title, e.g. "City Hunter".
	 * Authors are counted for their real name. The read dates go from the first reading to the latest.
	 * The date a work first came out is its date first published, or else its edition date.
	 * The date added is the post date. The full title is the one to show.
	 * Original languages are their names keyed by their code, e.g. "it" => "Italien".
	 *
	 * @return array<int,array{id:int,title:string,full_title:string,status:string,series:string,volume:int|null,read:bool,read_dates:string[],rating:float|null,pages:int|null,edition_date:string,published_date:string,first_date:string,added:string,authors:string[],author_ids:int[],translators:string[],collections:string[],collection_ids:int[],languages:array<string,string>}>
	 */
	public static function all(): array {
		if ( null !== self::$books ) {
			return self::$books;
		}

		$posts = get_posts(
			array(
				'post_type'   => 'book',
				'post_status' => self::statuses(),
				'numberposts' => -1,
			)
		);

		self::$books = array();

		foreach ( $posts as $post ) {
			$volume         = preg_match( '/\d+/', (string) get_post_meta( $post->ID, 'volume_number', true ), $match ) ? (int) $match[0] : null;
			$series         = (string) get_post_meta( $post->ID, 'series', true );
			$rating         = get_post_meta( $post->ID, 'rating', true );
			$pages          = (int) get_post_meta( $post->ID, 'pages', true );
			$edition_date   = (string) get_post_meta( $post->ID, 'edition_date', true );
			$published_date = (string) get_post_meta( $post->ID, 'published_date', true );
			$author_terms   = get_the_terms( $post->ID, 'library-author' );
			$authors        = Library_Taxonomies::get_top_terms( is_array( $author_terms ) ? $author_terms : array() );
			$collections    = Library_Taxonomies::get_collections( $post->ID );
			$languages      = get_the_terms( $post->ID, 'library-language' );

			if ( '' === $series && null !== $volume ) {
				$series = $post->post_title;
			}

			self::$books[ $post->ID ] = array(
				'id'             => $post->ID,
				'title'          => $post->post_title,
				'full_title'     => self::title( $post->ID, $post->post_title ),
				'status'         => $post->post_status,
				'series'         => $series,
				'volume'         => $volume,
				'read'           => Library_Posts::is_read( $post->ID ),
				'read_dates'     => Library_Meta::get_read_dates( $post->ID ),
				'rating'         => '' !== $rating ? (float) $rating : null,
				'pages'          => $pages ? $pages : null,
				'edition_date'   => $edition_date,
				'published_date' => $published_date,
				'first_date'     => '' !== $published_date ? $published_date : $edition_date,
				'added'          => $post->post_date,
				'authors'        => wp_list_pluck( $authors, 'name' ),
				'author_ids'     => wp_list_pluck( $authors, 'term_id' ),
				'translators'    => self::split( (string) get_post_meta( $post->ID, 'translators', true ) ),
				'collections'    => wp_list_pluck( $collections, 'name' ),
				'collection_ids' => wp_list_pluck( $collections, 'term_id' ),
				'languages'      => wp_list_pluck( is_array( $languages ) ? $languages : array(), 'name', 'slug' ),
			);
		}

		return self::$books;
	}

	/**
	 * Title of a book to show, with its series, volume and subtitle: "Series, vol. 3: Title: Subtitle".
	 *
	 * A book without series but with a volume number is a volume of the series named by its title: "City Hunter, vol. 3".
	 * A volume typed "Tome 08" shows as "vol. 8", a number alone as "no. 8", anything else as typed, e.g. "Chant II".
	 *
	 * @param int    $book_id Book ID.
	 * @param string $title   Title of the post.
	 * @return string
	 */
	public static function title( int $book_id, string $title ): string {
		$series   = (string) get_post_meta( $book_id, 'series', true );
		$volume   = (string) get_post_meta( $book_id, 'volume_number', true );
		$subtitle = (string) get_post_meta( $book_id, 'subtitle', true );

		if ( '' !== $subtitle ) {
			/* translators: 1: title, 2: subtitle. */
			$title = sprintf( __( '%1$s: %2$s', 'library' ), $title, $subtitle );
		}

		if ( '' === $volume ) {
			/* translators: 1: series, 2: title. */
			return '' !== $series ? sprintf( __( '%1$s: %2$s', 'library' ), $series, $title ) : $title;
		}

		if ( preg_match( '/^(?:tome\s+)?0*(\w+)$/iu', $volume, $match ) ) {
			/* translators: %s: volume number. */
			$volume = str_starts_with( strtolower( $volume ), 'tome' ) ? sprintf( __( 'vol. %s', 'library' ), $match[1] ) : sprintf( __( 'no. %s', 'library' ), $match[1] );
		}

		/* translators: 1: series, or the title of a volume without series, 2: volume, e.g. "vol. 3". */
		$numbered = __( '%1$s, %2$s', 'library' );

		if ( '' === $series ) {
			return sprintf( $numbered, $title, $volume );
		}

		$series = sprintf( $numbered, $series, $volume );

		/* translators: 1: series, 2: title. */
		return sprintf( __( '%1$s: %2$s', 'library' ), $series, $title );
	}

	/**
	 * Credits of a book, to show under its title: "Authors · Publisher, Collection · edition year".
	 *
	 * @param int $book_id Book ID.
	 * @return string
	 */
	public static function credits( int $book_id ): string {
		$authors    = get_the_terms( $book_id, 'library-author' );
		$terms      = get_the_terms( $book_id, 'library-publisher' );
		$publishers = array();

		foreach ( Library_Taxonomies::get_top_terms( is_array( $terms ) ? $terms : array() ) as $publisher ) {
			$collections  = wp_list_filter( Library_Taxonomies::get_collections( $book_id ), array( 'parent' => $publisher->term_id ) );
			$publishers[] = implode( ', ', array( $publisher->name, ...wp_list_pluck( $collections, 'name' ) ) );
		}

		$parts = array(
			implode( ', ', wp_list_pluck( is_array( $authors ) ? $authors : array(), 'name' ) ),
			implode( ', ', $publishers ),
			substr( (string) get_post_meta( $book_id, 'edition_date', true ), 0, 4 ),
		);

		return implode( ' · ', array_filter( $parts, 'strlen' ) );
	}

	/**
	 * A number of books as text: "12 books".
	 *
	 * @param int $books Number of books.
	 * @return string
	 */
	public static function books_text( int $books ): string {
		/* translators: %s: number of books. */
		return sprintf( _n( '%s book', '%s books', $books, 'library' ), number_format_i18n( $books ) );
	}

	/**
	 * A rating or an average as text, with one decimal unless it is round: "9", "8,5".
	 *
	 * @param float $rating Rating, out of 10.
	 * @return string
	 */
	public static function rating_text( float $rating ): string {
		$rating   = round( $rating, 1 );
		$decimals = floor( $rating ) === $rating ? 0 : 1;

		return number_format_i18n( $rating, $decimals );
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
