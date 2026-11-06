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

namespace Library;

/**
 * Loads all the books, with their meta and terms, kept until a book changes, and says a book, a number of books and a rating the same way everywhere.
 *
 * @since      2.2.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Books {

	/**
	 * Books of the current request, from the cache.
	 *
	 * @var array<int,Book>|null
	 */
	private static ?array $books = null;

	/**
	 * All the books, kept, not kept or to buy.
	 *
	 * A book without series but with a volume number is a volume of the series named by its title, e.g. "City Hunter".
	 * Authors are counted under the name they are known by, and so are its contributors, its names with another role, such as a
	 * colorist. The read dates go from the first reading to the latest.
	 * The date added is the post date. The full title is the one to show.
	 * Original languages are their names keyed by their code, e.g. "it" => "Italien"; forms are their names, e.g. "Roman".
	 * Collections are those of the book and the ones they are part of, named after them: "Folio, Science-fiction", "Folio".
	 * Series IDs are those of its series and of the cycles it is part of, from the series to the widest cycle. Publishers are
	 * counted as authors are, a collection under its publisher, i.e. their widest ancestor: with the collections, they are
	 * every term of the book and every one it is under.
	 *
	 * Kept until a book changes, as they take every book: see Cache.
	 *
	 * @return array<int,Book>
	 */
	public static function get_books(): array {
		self::$books ??= Cache::remember( 'books', fn() => self::compute_books() );

		return self::$books;
	}

	/**
	 * All the books, from their posts: see get_books().
	 *
	 * @return array<int,Book>
	 */
	private static function compute_books(): array {
		$books = array();
		$posts = get_posts(
			array(
				'post_type'   => 'library_book',
				'post_status' => Status::get_values(),
				'numberposts' => -1,
			)
		);

		foreach ( $posts as $post ) {
			$read_dates     = Meta::get_read_dates( $post->ID );
			$volume         = preg_match( '/\d+/', (string) get_post_meta( $post->ID, 'library_volume_number', true ), $match ) ? (int) $match[0] : null;
			$series         = Taxonomies::get_series( $post->ID );
			$series_name    = $series ? Taxonomies::get_series_path( $series ) : ( null !== $volume ? $post->post_title : '' );
			$rating         = get_post_meta( $post->ID, 'library_rating', true );
			$pages          = (int) get_post_meta( $post->ID, 'library_pages', true );
			$edition_date   = (string) get_post_meta( $post->ID, 'library_edition_date', true );
			$published_date = (string) get_post_meta( $post->ID, 'library_published_date', true );
			$authors        = Taxonomies::get_top_level_terms( Taxonomies::get_authors( $post->ID ) );
			$contributors   = Taxonomies::get_top_level_terms( Taxonomies::get_contributors( $post->ID ) );
			$translators    = Taxonomies::get_top_level_terms( Taxonomies::get_contributors( $post->ID, 'translator' ) );
			$collections    = Taxonomies::get_collections_and_parents( $post->ID );
			$publishers     = get_the_terms( $post->ID, 'library_publisher' );
			$publishers     = Taxonomies::get_top_level_terms( is_array( $publishers ) ? $publishers : array() );
			$languages      = get_the_terms( $post->ID, 'library_language' );
			$forms          = get_the_terms( $post->ID, 'library_form' );

			$books[ $post->ID ] = new Book(
				id:                 $post->ID,
				title:              $post->post_title,
				full_title:         self::get_full_title( $post->ID, $post->post_title ),
				status:             Status::from( $post->post_status ),
				// From its cycle, as typed: "Star Wars, Young Jedi Knights"; a volume without series is one of its title.
				series:             $series_name,
				series_id:          $series ? $series->term_id : 0,
				series_ids:         Taxonomies::get_term_ids_and_ancestors( $post->ID, 'library_series' ),
				volume:             $volume,
				collective:         Posts::is_collective( $post->ID ),
				read_dates:         $read_dates,
				rating:             '' !== $rating ? (float) $rating : null,
				pages:              $pages ? $pages : null,
				edition_date:       $edition_date,
				published_date:     $published_date,
				added:              $post->post_date,
				authors:            wp_list_pluck( $authors, 'name' ),
				author_ids:         wp_list_pluck( $authors, 'term_id' ),
				contributor_ids:    wp_list_pluck( $contributors, 'term_id' ),
				translators:        wp_list_pluck( $translators, 'name' ),
				collections:        array_map( array( Taxonomies::class, 'get_collection_name' ), $collections ),
				collection_ids:     wp_list_pluck( $collections, 'term_id' ),
				publisher_ids:      wp_list_pluck( $publishers, 'term_id' ),
				collection_number:  (string) get_post_meta( $post->ID, 'library_collection_number', true ),
				languages:          wp_list_pluck( is_array( $languages ) ? $languages : array(), 'name', 'slug' ),
				language_ids:       wp_list_pluck( is_array( $languages ) ? $languages : array(), 'term_id' ),
				forms:              wp_list_pluck( is_array( $forms ) ? $forms : array(), 'name' ),
				form_ids:           wp_list_pluck( is_array( $forms ) ? $forms : array(), 'term_id' ),
			);
		}

		return $books;
	}

	/**
	 * Date of a book: when the work first came out, or else its edition date.
	 *
	 * @param Book $book Book, from get_books().
	 * @return string
	 */
	public static function get_date( Book $book ): string {
		return '' !== $book->published_date ? $book->published_date : $book->edition_date;
	}

	/**
	 * Books in reading order: in the order they first came out, a series at the date of its first book with its volumes in
	 * their order, so that the parts of a cycle follow one another; those without date last, then by title.
	 *
	 * @param array<int,Book> $books Books, from get_books().
	 * @return array<int,Book>
	 */
	public static function sort_in_reading_order( array $books ): array {
		$series_dates = array();

		foreach ( $books as $book ) {
			$date = self::get_date( $book );

			if ( '' !== $book->series && '' !== $date ) {
				$series_dates[ $book->series ] = min( $date, $series_dates[ $book->series ] ?? $date );
			}
		}

		$place = function ( Book $book ) use ( $series_dates ): array {
			$date = '' !== $book->series ? $series_dates[ $book->series ] ?? '' : self::get_date( $book );

			return array( '' === $date, $date, $book->series, $book->volume ?? PHP_INT_MAX, self::get_date( $book ) );
		};

		usort(
			$books,
			function ( Book $a, Book $b ) use ( $place ): int {
				$order = $place( $a ) <=> $place( $b );

				return 0 !== $order ? $order : strnatcasecmp( $a->full_title, $b->full_title );
			}
		);

		return $books;
	}

	/**
	 * Title of a book to show, from its series, volume and subtitle saved: see build_full_title().
	 *
	 * @param int    $book_id Book ID.
	 * @param string $title   Title of the post.
	 * @return string
	 */
	public static function get_full_title( int $book_id, string $title ): string {
		$series = Taxonomies::get_series( $book_id );
		$meta   = array();

		foreach ( array( 'library_volume_number', 'library_subtitle' ) as $key ) {
			$meta[ $key ] = get_post_meta( $book_id, $key, true );
		}

		return self::build_full_title( $title, $series ? Taxonomies::get_series_path( $series ) : '', $meta );
	}

	/**
	 * Title of a book to show, with its series, volume and subtitle: "Series, vol. 3: Title: Subtitle".
	 *
	 * A volume whose title is its series, as a volume of a manga, shows it once: "City Hunter, vol. 3", "Talli, fille de
	 * la lune, vol. 1" for a series with a comma; so does a volume without series. A volume typed "Tome 08" shows as
	 * "vol. 8", a number alone as "no. 8", anything else as typed, e.g. "Chant II".
	 *
	 * @param string              $title  Title of the book.
	 * @param string              $series Series of the book, from its cycle, or empty.
	 * @param array<string,mixed> $meta   Meta of the book, saved or as its form sends them: volume_number and subtitle.
	 * @return string
	 */
	public static function build_full_title( string $title, string $series, array $meta ): string {
		$volume   = (string) ( $meta['library_volume_number'] ?? '' );
		$subtitle = (string) ( $meta['library_subtitle'] ?? '' );
		$parts    = self::split( $series );
		// A series may have a comma in its name, such as "Talli, fille de la lune": the title takes as many names of the path.
		$title_slugs  = array_map( 'sanitize_title', self::split( $title ) );
		$series_slugs = array_map( 'sanitize_title', array_slice( $parts, -count( $title_slugs ) ) );

		// The series, from its cycle, stands for the title, the same as the slugs of terms tell, case, accents and apostrophes aside.
		if ( '' !== $volume && $title_slugs && $series_slugs === $title_slugs ) {
			$title  = implode( ', ', $parts );
			$series = '';
		}

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
	 * kept: "charlie-adlard-walking-dead-vol-25-blood-for-blood.jpg"; the title alone for a book without author or
	 * collective.
	 *
	 * A name keeps letters and digits without accents only: a sign a slug keeps encoded, such as the dot of "René⸱e", is
	 * left out. A book named in another script keeps the name of its photo.
	 *
	 * @param string              $title   Title of the book.
	 * @param string[]            $authors Names of its authors.
	 * @param string              $series  Series of the book, from its cycle, or empty.
	 * @param array<string,mixed> $meta    Meta of the book: volume_number, subtitle and collective.
	 * @param string              $photo   File name of the photo.
	 * @return string
	 */
	public static function build_cover_name( string $title, array $authors, string $series, array $meta, string $photo ): string {
		$slug = fn( string $text ): string => trim( (string) preg_replace( array( '/%[0-9a-f]{2}/', '/-{2,}/' ), array( '', '-' ), sanitize_title( $text ) ), '-' );

		// By name, case and accents aside, as the books list their authors.
		$authors = array_filter( array_map( $slug, $authors ) );
		sort( $authors );

		$author    = rest_sanitize_boolean( $meta['library_collective'] ?? false ) ? '' : ( $authors[0] ?? '' );
		$name      = $slug( implode( ' ', array( $author, self::build_full_title( $title, $series, $meta ) ) ) );
		$extension = strtolower( pathinfo( $photo, PATHINFO_EXTENSION ) );

		if ( '' === $name ) {
			return $photo;
		}

		return '' !== $extension ? "$name.$extension" : $name;
	}

	/**
	 * The publishing directors of a collective work as credited: "Benoît Mouchart (dir.)".
	 *
	 * @param string $names Their names, as listed.
	 * @return string
	 */
	public static function format_directors( string $names ): string {
		/* translators: %s: names of the publishing directors of a collective work. */
		return sprintf( __( '%s (dir.)', 'library' ), $names );
	}

	/**
	 * Credits of a book, to show under its title: "Authors · Publisher, Collection no. 5667 · edition year", a sub-collection
	 * after its collection: "Gallimard, Folio, Science-fiction no. 271".
	 *
	 * A collective work is credited to its publishing directors, as catalogues have it, and to none of its authors:
	 * "Benoît Mouchart (dir.) · Casterman · 2020", or "Casterman · 2020" without director.
	 *
	 * @param int $book_id Book ID.
	 * @return string
	 */
	public static function get_credits( int $book_id ): string {
		$terms             = get_the_terms( $book_id, 'library_publisher' );
		$collection_number = (string) get_post_meta( $book_id, 'library_collection_number', true );
		$publishers        = array();

		foreach ( Taxonomies::get_top_level_terms( is_array( $terms ) ? $terms : array() ) as $publisher ) {
			$names = array( $publisher->name );

			foreach ( Taxonomies::get_collections( $book_id ) as $collection ) {
				if ( in_array( $publisher->term_id, get_ancestors( $collection->term_id, 'library_publisher', 'taxonomy' ), true ) ) {
					$names = array( ...$names, ...self::split( Taxonomies::get_collection_name( $collection ) ) );
				}
			}

			// A collection given with its sub-collection shows once.
			$publishers[] = implode( ', ', array_unique( $names ) );
		}

		// The number goes after the collection, the last one, as the volume after the series; none without a publisher.
		if ( $publishers && '' !== $collection_number ) {
			/* translators: %s: number of the book in its collection. */
			$publishers[ array_key_last( $publishers ) ] .= ' ' . sprintf( __( 'no. %s', 'library' ), $collection_number );
		}

		$names = implode( ', ', wp_list_pluck( Taxonomies::get_credited_people( $book_id ), 'name' ) );

		if ( '' !== $names && Posts::is_collective( $book_id ) ) {
			$names = self::format_directors( $names );
		}

		$parts = array(
			$names,
			implode( ', ', $publishers ),
			substr( (string) get_post_meta( $book_id, 'library_edition_date', true ), 0, 4 ),
		);

		return implode( ' · ', array_filter( $parts, fn( string $part ) => '' !== $part ) );
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
	 * A rating as text, out of 10: "8,5/10".
	 *
	 * @param float $rating Rating, out of 10.
	 * @return string
	 */
	public static function format_rating_out_of_ten( float $rating ): string {
		/* translators: %s: rating, e.g. 7.5. */
		return sprintf( __( '%s/10', 'library' ), self::format_rating( $rating ) );
	}

	/**
	 * Values of a field separated with commas.
	 *
	 * @param string $value Field value.
	 * @return string[]
	 */
	public static function split( string $value ): array {
		return array_values( array_filter( array_map( 'trim', explode( ',', $value ) ), fn( string $name ) => '' !== $name ) );
	}
}
