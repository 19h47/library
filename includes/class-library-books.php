<?php
/**
 * Books data shared by the reading idea, the achievements and the REST API, how a book, a number of books and a rating are said, and how a cover is named.
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
	 * Statuses of the books, those their forms offer: kept, not kept or to buy. A book of another status, such as a draft,
	 * is left out until it is published.
	 *
	 * @return string[]
	 */
	public static function get_statuses(): array {
		return array_keys( Library_Posts::get_status_choices() );
	}

	/**
	 * All the books, kept, not kept or to buy.
	 *
	 * A book without series but with a volume number is a volume of the series named by its title, e.g. "City Hunter".
	 * Authors are counted for their real name. The read dates go from the first reading to the latest.
	 * The date added is the post date. The full title is the one to show.
	 * Original languages are their names keyed by their code, e.g. "it" => "Italien".
	 *
	 * @return array<int,array{id:int,title:string,full_title:string,status:string,series:string,volume:int|null,read:bool,read_dates:string[],rating:float|null,pages:int|null,edition_date:string,published_date:string,added:string,authors:string[],author_ids:int[],translators:string[],collections:string[],collection_ids:int[],languages:array<string,string>}>
	 */
	public static function get_books(): array {
		if ( null !== self::$books ) {
			return self::$books;
		}

		$posts = get_posts(
			array(
				'post_type'   => 'library_book',
				'post_status' => self::get_statuses(),
				'numberposts' => -1,
			)
		);

		self::$books = array();

		foreach ( $posts as $post ) {
			$volume         = preg_match( '/\d+/', (string) get_post_meta( $post->ID, 'library_volume_number', true ), $match ) ? (int) $match[0] : null;
			$series         = (string) get_post_meta( $post->ID, 'library_series', true );
			$rating         = get_post_meta( $post->ID, 'library_rating', true );
			$pages          = (int) get_post_meta( $post->ID, 'library_pages', true );
			$edition_date   = (string) get_post_meta( $post->ID, 'library_edition_date', true );
			$published_date = (string) get_post_meta( $post->ID, 'library_published_date', true );
			$author_terms   = get_the_terms( $post->ID, 'library_author' );
			$authors        = Library_Taxonomies::get_top_level_terms( is_array( $author_terms ) ? $author_terms : array() );
			$collections    = Library_Taxonomies::get_collections( $post->ID );
			$languages      = get_the_terms( $post->ID, 'library_language' );

			if ( '' === $series && null !== $volume ) {
				$series = $post->post_title;
			}

			self::$books[ $post->ID ] = array(
				'id'             => $post->ID,
				'title'          => $post->post_title,
				'full_title'     => self::get_full_title( $post->ID, $post->post_title ),
				'status'         => $post->post_status,
				'series'         => $series,
				'volume'         => $volume,
				'read'           => Library_Posts::is_read( $post->ID ),
				'read_dates'     => Library_Meta::get_read_dates( $post->ID ),
				'rating'         => '' !== $rating ? (float) $rating : null,
				'pages'          => $pages ? $pages : null,
				'edition_date'   => $edition_date,
				'published_date' => $published_date,
				'added'          => $post->post_date,
				'authors'        => wp_list_pluck( $authors, 'name' ),
				'author_ids'     => wp_list_pluck( $authors, 'term_id' ),
				'translators'    => self::split( (string) get_post_meta( $post->ID, 'library_translators', true ) ),
				'collections'    => wp_list_pluck( $collections, 'name' ),
				'collection_ids' => wp_list_pluck( $collections, 'term_id' ),
				'languages'      => wp_list_pluck( is_array( $languages ) ? $languages : array(), 'name', 'slug' ),
			);
		}

		return self::$books;
	}

	/**
	 * Date of a book: when the work first came out, or else its edition date.
	 *
	 * @param array $book Book, from get_books().
	 * @return string
	 */
	public static function get_date( array $book ): string {
		return '' !== $book['published_date'] ? $book['published_date'] : $book['edition_date'];
	}

	/**
	 * Books in reading order: in the order they first came out, a series at the date of its first book with its volumes in
	 * their order, so that the parts of a cycle follow one another; those without date last, then by title.
	 *
	 * @param array<int,array> $books Books, from get_books().
	 * @return array<int,array>
	 */
	public static function sort_in_reading_order( array $books ): array {
		$series_dates = array();

		foreach ( $books as $book ) {
			$date = self::get_date( $book );

			if ( '' !== $book['series'] && '' !== $date ) {
				$series_dates[ $book['series'] ] = min( $date, $series_dates[ $book['series'] ] ?? $date );
			}
		}

		$place = function ( array $book ) use ( $series_dates ): array {
			$date = '' !== $book['series'] ? $series_dates[ $book['series'] ] ?? '' : self::get_date( $book );

			return array( '' === $date, $date, $book['series'], $book['volume'] ?? PHP_INT_MAX, self::get_date( $book ) );
		};

		usort(
			$books,
			function ( array $a, array $b ) use ( $place ): int {
				$order = $place( $a ) <=> $place( $b );

				return 0 !== $order ? $order : strnatcasecmp( $a['full_title'], $b['full_title'] );
			}
		);

		return $books;
	}

	/**
	 * Cycle of a series, the names before its last one: "Star Wars" for "Star Wars, Young Jedi Knights", empty for a series
	 * without cycle.
	 *
	 * @param string $series Series, after its cycles.
	 * @return string
	 */
	public static function get_cycle( string $series ): string {
		return implode( ', ', array_slice( self::split( $series ), 0, -1 ) );
	}

	/**
	 * Title of a book to show, from its series, volume and subtitle saved: see build_full_title().
	 *
	 * @param int    $book_id Book ID.
	 * @param string $title   Title of the post.
	 * @return string
	 */
	public static function get_full_title( int $book_id, string $title ): string {
		$meta = array();

		foreach ( array( 'library_series', 'library_volume_number', 'library_subtitle' ) as $key ) {
			$meta[ $key ] = get_post_meta( $book_id, $key, true );
		}

		return self::build_full_title( $title, $meta );
	}

	/**
	 * Title of a book to show, with its series, volume and subtitle: "Series, vol. 3: Title: Subtitle".
	 *
	 * A book without series but with a volume number is a volume of the series named by its title: "City Hunter, vol. 3".
	 * A volume typed "Tome 08" shows as "vol. 8", a number alone as "no. 8", anything else as typed, e.g. "Chant II".
	 *
	 * @param string $title Title of the book.
	 * @param array  $meta  Meta of the book, saved or as its form sends them: series, volume_number and subtitle.
	 * @return string
	 */
	public static function build_full_title( string $title, array $meta ): string {
		$series   = (string) ( $meta['library_series'] ?? '' );
		$volume   = (string) ( $meta['library_volume_number'] ?? '' );
		$subtitle = (string) ( $meta['library_subtitle'] ?? '' );

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
	 * Name of the image of a cover, after its first author by name and the title to show, the extension of the photo
	 * kept: "charlie-adlard-walking-dead-vol-25-blood-for-blood.jpg"; the title alone for a book without author.
	 *
	 * A name keeps letters and digits without accents only: a sign a slug keeps encoded, such as the dot of "René⸱e", is
	 * left out. A book named in another script keeps the name of its photo.
	 *
	 * @param string   $title   Title of the book.
	 * @param string[] $authors Names of its authors.
	 * @param array    $meta    Meta of the book: series, volume_number and subtitle.
	 * @param string   $photo   File name of the photo.
	 * @return string
	 */
	public static function build_cover_name( string $title, array $authors, array $meta, string $photo ): string {
		$slug = fn( string $text ): string => trim( preg_replace( array( '/%[0-9a-f]{2}/', '/-{2,}/' ), array( '', '-' ), sanitize_title( $text ) ), '-' );

		// By name, case and accents aside, as the books list their authors.
		$authors = array_filter( array_map( $slug, $authors ) );
		sort( $authors );

		$name      = $slug( implode( ' ', array( $authors[0] ?? '', self::build_full_title( $title, $meta ) ) ) );
		$extension = strtolower( pathinfo( $photo, PATHINFO_EXTENSION ) );

		if ( '' === $name ) {
			return $photo;
		}

		return '' !== $extension ? "$name.$extension" : $name;
	}

	/**
	 * Credits of a book, to show under its title: "Authors · Publisher, Collection no. 5667 · edition year".
	 *
	 * @param int $book_id Book ID.
	 * @return string
	 */
	public static function get_credits( int $book_id ): string {
		$authors           = get_the_terms( $book_id, 'library_author' );
		$terms             = get_the_terms( $book_id, 'library_publisher' );
		$collection_number = (string) get_post_meta( $book_id, 'library_collection_number', true );
		$publishers        = array();

		foreach ( Library_Taxonomies::get_top_level_terms( is_array( $terms ) ? $terms : array() ) as $publisher ) {
			$collections  = wp_list_filter( Library_Taxonomies::get_collections( $book_id ), array( 'parent' => $publisher->term_id ) );
			$publishers[] = implode( ', ', array( $publisher->name, ...wp_list_pluck( $collections, 'name' ) ) );
		}

		// The number goes after the collection, the last one, as the volume after the series; none without a publisher.
		if ( $publishers && '' !== $collection_number ) {
			/* translators: %s: number of the book in its collection. */
			$publishers[ array_key_last( $publishers ) ] .= ' ' . sprintf( __( 'no. %s', 'library' ), $collection_number );
		}

		$parts = array(
			implode( ', ', wp_list_pluck( is_array( $authors ) ? $authors : array(), 'name' ) ),
			implode( ', ', $publishers ),
			substr( (string) get_post_meta( $book_id, 'library_edition_date', true ), 0, 4 ),
		);

		return implode( ' · ', array_filter( $parts, 'strlen' ) );
	}

	/**
	 * A number of books as text: "12 books".
	 *
	 * @param int $books Number of books.
	 * @return string
	 */
	public static function format_books( int $books ): string {
		/* translators: %s: number of books. */
		return sprintf( _n( '%s book', '%s books', $books, 'library' ), number_format_i18n( $books ) );
	}

	/**
	 * A rating or an average as text, with one decimal unless it is round: "9", "8,5".
	 *
	 * @param float $rating Rating, out of 10.
	 * @return string
	 */
	public static function format_rating( float $rating ): string {
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
	public static function split( string $value ): array {
		return array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) );
	}
}
