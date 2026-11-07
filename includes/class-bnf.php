<?php
/**
 * A book as the catalogue of the Bibliothèque nationale de France describes it, for the form of a book to add.
 *
 * @link       https://github.com/19h47/library
 * @since      9.26.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use DOMDocument;
use DOMElement;
use Locale;
use WP_Error;
use WP_Term;

/**
 * Finds a book by its ISBN in the catalogue of the BnF, through its SRU API, and gives its values as the form of a book
 * takes them, with the names of the library: a person, a publisher, a collection or a series the library has already
 * keeps its name, else takes the one of the catalogue. The BnF describes the books published in France, as their legal
 * deposit gives them, in UNIMARC.
 *
 * @since      9.26.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-type Field array{ind1:string,ind2:string,subfields:array<int|string,list<string>>}
 * @phpstan-type Record array<int|string,list<Field>>
 * @phpstan-type Book_Values array{title:string,people:list<array{name:string,role:string}>,publisher:string,collection:string,series:string,languages:list<int>,form:int,meta:array<string,string|list<int>>}
 */
class BnF {

	/**
	 * SRU API of the catalogue.
	 *
	 * @var string
	 */
	private const string API = 'https://catalogue.bnf.fr/api/SRU';

	/**
	 * Roles of the library by UNIMARC relator code: the author, the illustrator, who draws a comic, the translator, the
	 * author of the work adapted or continued and the publishing director. Another role is left out, such as the author
	 * of a preface, or the editor, who presents a classic as well as gathers texts.
	 *
	 * @var array<int|string,string>
	 */
	private const array ROLES = array(
		'070' => 'author',
		'440' => 'author',
		'730' => 'translator',
		'100' => 'adapted_author',
		'651' => 'director',
	);

	/**
	 * Codes of ISO 639-2 the catalogue gives as bibliographic codes, by their terminological codes, which ICU knows.
	 *
	 * @var array<string,string>
	 */
	private const array LANGUAGE_CODES = array(
		'alb' => 'sqi',
		'arm' => 'hye',
		'baq' => 'eus',
		'bur' => 'mya',
		'chi' => 'zho',
		'cze' => 'ces',
		'dut' => 'nld',
		'fre' => 'fra',
		'geo' => 'kat',
		'ger' => 'deu',
		'gre' => 'ell',
		'ice' => 'isl',
		'mac' => 'mkd',
		'mao' => 'mri',
		'may' => 'msa',
		'per' => 'fas',
		'rum' => 'ron',
		'slo' => 'slk',
		'tib' => 'bod',
		'wel' => 'cym',
	);

	/**
	 * Names a form of the library may have, by what the catalogue tells: a comic by its type of illustrations, else a
	 * literary form by the code of the record or by the words after the title, such as "roman" or "nouvelles".
	 *
	 * @var array<string,string[]>
	 */
	private const array FORMS = array(
		'comics'  => array( 'bande dessinee', 'bandes dessinees', 'bd', 'comics', 'comic' ),
		'novel'   => array( 'roman', 'romans', 'novel', 'novels' ),
		'stories' => array( 'nouvelles', 'short stories', 'stories' ),
		'drama'   => array( 'theatre', 'drama', 'plays' ),
		'poetry'  => array( 'poesie', 'poetry', 'poems' ),
	);

	/**
	 * The words after a title that tell its form rather than a subtitle, such as "roman policier" or "[nouvelles]".
	 *
	 * @var string
	 */
	private const string FORM_WORDS = '/^\[?(roman|nouvelle|recit|conte|essai|poeme|poesie|theatre|piece|bande dessinee|manga|comedie|tragedie|drame|chronique|journal)s?\b/';

	/**
	 * The values of the book of an ISBN as the form of a book takes them, with the names of the library, empty when the
	 * catalogue has none; null when the catalogue does not know the ISBN, an error when it does not answer.
	 *
	 * @param string $isbn ISBN, with or without hyphens.
	 * @return Book_Values|null|WP_Error
	 */
	public static function get_book( string $isbn ): array|null|WP_Error {
		$record = self::get_record( $isbn );

		if ( ! is_array( $record ) ) {
			return $record;
		}

		// The title of a volume, else of a book, which a volume without a title of its own shares with its series.
		$title        = self::get_first( $record, '200', 'i' );
		$title        = '' !== $title ? $title : self::get_first( $record, '200', 'a' );
		$people       = self::find_people( $record );
		$imprint      = self::get_first( $record, '210', 'c' );
		$publisher    = self::find_publisher( $isbn, '' !== $imprint ? $imprint : self::get_first( $record, '214', 'c' ) );
		$series       = self::get_series( $record );
		$series_term  = $series['name'] ? self::find_series( $series['name'] ) : null;
		$collection   = self::find_collection( $record, $publisher, $series['name'] );
		$volume_digit = (int) preg_replace( '/\D+/', '', $series['number'] );

		// A volume titled as its series, such as a manga, takes the name the library gives the series.
		if ( $series_term && self::normalize( $title ) === self::normalize( $series['name'] ) ) {
			$title = wp_specialchars_decode( $series_term->name, ENT_QUOTES );
		}

		return array(
			'title'      => $title,
			'people'     => $people,
			'publisher'  => $publisher,
			'collection' => $collection['name'],
			'series'     => $series_term ? Taxonomies::get_series_path( $series_term ) : $series['name'],
			'languages'  => array_filter( array( self::find_language( $record, $people ) ) ),
			'form'       => self::find_form( $record, $series_term ),
			'meta'       => array(
				'library_subtitle'          => self::get_subtitle( $record ),
				// The numbers of the volumes of the library: "Tome 05".
				'library_volume_number'     => $volume_digit ? sprintf( 'Tome %02d', $volume_digit ) : '',
				'library_collection_number' => $collection['number'],
				'library_pages'             => self::get_pages( $record ),
				'library_edition_languages' => self::find_edition_languages( $record ),
			),
		);
	}

	/**
	 * The record of an ISBN in the catalogue, cached for a day, as none; null when the catalogue does not know it, an
	 * error when it does not answer. An ISBN-13 of 978 is searched as its ISBN-10 too, which the books of before 2007
	 * have alone.
	 *
	 * @param string $isbn ISBN, with or without hyphens.
	 * @return Record|null|WP_Error
	 */
	private static function get_record( string $isbn ): array|null|WP_Error {
		$digits = strtoupper( (string) preg_replace( '/[^\dX]+/i', '', $isbn ) );
		$key    = "library_bnf_$digits";
		/**
		 * The record cached, an empty array for an ISBN the catalogue does not know, false for none.
		 *
		 * @var Record|false $cached
		 */
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached ? $cached : null;
		}

		$codes    = array_filter( array( $digits, self::to_isbn10( $digits ) ) );
		$queries  = array_map( fn( string $code ) => sprintf( 'bib.isbn all "%s"', $code ), $codes );
		$url      = add_query_arg(
			array(
				'version'        => '1.2',
				'operation'      => 'searchRetrieve',
				'query'          => rawurlencode( implode( ' or ', $queries ) ),
				'recordSchema'   => 'unimarcXchange',
				'maximumRecords' => 5,
			),
			self::API
		);
		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		// A catalogue that does not answer is asked again the next time.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'library_bnf_unreachable', __( 'The catalogue of the BnF does not answer: try again later.', 'library' ), array( 'status' => 502 ) );
		}

		// The catalogue finds an ISBN in other fields too, such as the one of another edition: the record of the book has it as its own.
		$record = array_find(
			self::parse( wp_remote_retrieve_body( $response ) ),
			fn( array $item ) => (bool) array_intersect( $codes, array_map( fn( string $code ) => strtoupper( (string) preg_replace( '/[^\dX]+/i', '', $code ) ), array_merge( ...array_column( array_column( $item['010'] ?? array(), 'subfields' ), 'a' ) ) ) )
		);

		set_transient( $key, $record ?? array(), DAY_IN_SECONDS );

		return $record;
	}

	/**
	 * The records of an answer of the SRU API in UNIMARC, each as its fields by their tags, each field with its indicators
	 * and its subfields by their codes; none for an answer that is not one.
	 *
	 * @param string $xml Answer of the SRU API, its records in the schema unimarcXchange.
	 * @return list<Record>
	 */
	public static function parse( string $xml ): array {
		$document = new DOMDocument();

		if ( ! $xml || ! $document->loadXML( $xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING ) ) {
			return array();
		}

		$records = array();

		foreach ( $document->getElementsByTagNameNS( 'info:lc/xmlns/marcxchange-v2', 'record' ) as $record ) {
			$records[] = self::read_record( $record );
		}

		return $records;
	}

	/**
	 * The fields of a record by their tags, each with its indicators and its subfields by their codes.
	 *
	 * @param DOMElement $record Record of an answer of the SRU API.
	 * @return Record
	 */
	private static function read_record( DOMElement $record ): array {
		$fields = array();

		foreach ( $record->getElementsByTagNameNS( 'info:lc/xmlns/marcxchange-v2', 'datafield' ) as $datafield ) {
			$subfields = array();

			foreach ( $datafield->getElementsByTagNameNS( 'info:lc/xmlns/marcxchange-v2', 'subfield' ) as $subfield ) {
				// The catalogue marks the words left out of the sorting, such as an article, with control characters.
				$subfields[ $subfield->getAttribute( 'code' ) ][] = trim( (string) preg_replace( '/[\x{0080}-\x{009F}]/u', '', $subfield->textContent ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- A property of DOMNode.
			}

			$fields[ $datafield->getAttribute( 'tag' ) ][] = array(
				'ind1'      => trim( $datafield->getAttribute( 'ind1' ) ),
				'ind2'      => trim( $datafield->getAttribute( 'ind2' ) ),
				'subfields' => $subfields,
			);
		}

		return $fields;
	}

	/**
	 * The first value of a subfield of a record, in its first field that has it; empty for none.
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @param string                        $tag    Tag of the field, such as 200.
	 * @param string                        $code   Code of the subfield, such as a.
	 * @return string
	 */
	private static function get_first( array $record, string $tag, string $code ): string {
		foreach ( $record[ $tag ] ?? array() as $field ) {
			if ( isset( $field['subfields'][ $code ][0] ) ) {
				return $field['subfields'][ $code ][0];
			}
		}

		return '';
	}

	/**
	 * The subtitle of a record, the first words after its title, unless they tell its form, such as "roman" or
	 * "[nouvelles]".
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @return string
	 */
	private static function get_subtitle( array $record ): string {
		$subtitle = self::get_first( $record, '200', 'e' );

		return preg_match( self::FORM_WORDS, self::normalize( $subtitle ) ) ? '' : $subtitle;
	}

	/**
	 * The number of pages of a record, as its collation gives them, the unnumbered ones included: 282 for "1 vol.
	 * (282 p.)", 212 for "1 vol. (204-[8] p.)", 226 for "1 vol. (non paginé [ca 226] p.)"; empty for none.
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @return string
	 */
	private static function get_pages( array $record ): string {
		if ( ! preg_match( '/\(([^()]*)\bp\.\)/', self::get_first( $record, '215', 'a' ), $matches ) ) {
			return '';
		}

		// Each sequence of pages: those numbered in Roman numerals are front matter, left out.
		preg_match_all( '/\d+/', $matches[1], $numbers );

		$pages = array_sum( array_map( 'intval', $numbers[0] ) );

		return $pages ? (string) $pages : '';
	}

	/**
	 * The people of a record with a role of the library, by the names the library gives them: the authors, then the
	 * other people, each once.
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @return list<array{name:string,role:string}>
	 */
	private static function find_people( array $record ): array {
		$names  = get_terms(
			array(
				'taxonomy'   => 'library_person',
				'hide_empty' => false,
				'fields'     => 'names',
			)
		);
		$names  = is_array( $names ) ? array_map( fn( $name ) => wp_specialchars_decode( (string) $name, ENT_QUOTES ), $names ) : array();
		$people = array();

		foreach ( array( '700', '701', '702' ) as $tag ) {
			foreach ( $record[ $tag ] ?? array() as $field ) {
				$role    = self::ROLES[ $field['subfields']['4'][0] ?? '' ] ?? null;
				$surname = $field['subfields']['a'][0] ?? '';

				if ( ! $role || ! $surname ) {
					continue;
				}

				$name = self::find_person( $surname, $field['subfields']['b'][0] ?? '', $names );

				$people[ "$role $name" ] = array(
					'name' => $name,
					'role' => $role,
				);
			}
		}

		return array_values( $people );
	}

	/**
	 * The name of a person as the library gives it, whatever its accents and its order: "Tsukasa Hojo" for "Hōjō,
	 * Tsukasa", "Dai Sijie" for "Dai, Sijie"; else, when one name of the library is their surname alone, or their surname
	 * after the initial of their forename, that one: "Gatignol" for "Gatignol, Bertrand", "Philip K. Dick" for "Dick,
	 * Philip Kindred"; else the name of the catalogue, the forename first.
	 *
	 * @param string   $surname  Surname, or the one name of a person known by one.
	 * @param string   $forename Forename, empty for none.
	 * @param string[] $names    Names of the people of the library.
	 * @return string
	 */
	private static function find_person( string $surname, string $forename, array $names ): string {
		$name    = trim( "$forename $surname" );
		$keys    = array( self::normalize( $name ), self::normalize( "$surname $forename" ) );
		$found   = array_find( $names, fn( string $term ) => in_array( self::normalize( $term ), $keys, true ) );
		$last    = self::normalize( $surname );
		$initial = mb_substr( self::normalize( $forename ), 0, 1 );
		$similar = array_filter(
			$names,
			fn( string $term ) => self::normalize( $term ) === $last || ( str_ends_with( self::normalize( $term ), " $last" ) && str_starts_with( self::normalize( $term ), $initial ) )
		);

		return $found ?? ( 1 === count( $similar ) ? current( $similar ) : $name );
	}

	/**
	 * The publisher of a book: the one of the books of the library of the same publisher by their ISBN, the registrant
	 * of the agency, such as 978-2-07 for Gallimard; else the one of the library with the name of the catalogue, its
	 * imprint aside, "Panini" for "Panini manga"; else that name.
	 *
	 * @param string $isbn ISBN, with or without hyphens.
	 * @param string $name Name of the publisher in the catalogue.
	 * @return string
	 */
	private static function find_publisher( string $isbn, string $name ): string {
		$parts = explode( '-', ISBN::format( $isbn ) );

		if ( count( $parts ) >= 4 ) {
			// The ISBN-10 of an older book, the same without 978.
			$registrant = '978' === $parts[0] ? sprintf( '^(978-)?%s-%s-', $parts[1], $parts[2] ) : sprintf( '^%s-%s-%s-', ...$parts );
			$books      = get_posts(
				array(
					'post_type'      => 'library_book',
					'post_status'    => Status::get_values(),
					'posts_per_page' => 20,
					'fields'         => 'ids',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- A search typed once, on one meta.
						array(
							'key'     => 'library_isbn',
							'value'   => $registrant,
							'compare' => 'REGEXP',
						),
					),
				)
			);
			$names      = array();

			foreach ( $books as $book_id ) {
				$terms = get_the_terms( $book_id, 'library_publisher' );

				foreach ( Taxonomies::get_top_level_terms( is_array( $terms ) ? $terms : array() ) as $publisher ) {
					$names[] = wp_specialchars_decode( $publisher->name, ENT_QUOTES );
				}
			}

			if ( $names ) {
				$counts = array_count_values( $names );

				arsort( $counts );

				return (string) array_key_first( $counts );
			}
		}

		$publishers = get_terms(
			array(
				'taxonomy'   => 'library_publisher',
				'hide_empty' => false,
				'parent'     => 0,
				'fields'     => 'names',
			)
		);
		$key        = self::normalize_publisher( $name );
		$found      = array_find(
			is_array( $publishers ) ? $publishers : array(),
			fn( $publisher ) => '' !== $key && ( self::normalize_publisher( (string) $publisher ) === $key || str_starts_with( $key, self::normalize_publisher( (string) $publisher ) . ' ' ) )
		);

		return null !== $found ? wp_specialchars_decode( (string) $found, ENT_QUOTES ) : $name;
	}

	/**
	 * The series of a record and the number of the book in it, as the catalogue names them: its link to its series, a
	 * series the catalogue gives as such, one the library has, else the title of a volume with its number; empty for none.
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @return array{name:string,number:string}
	 */
	private static function get_series( array $record ): array {
		$series = array_map(
			fn( array $field ) => array(
				'name'   => self::clean( $field['subfields']['t'][0] ?? '' ),
				'number' => $field['subfields']['v'][0] ?? '',
			),
			$record['461'] ?? array()
		);

		foreach ( $record['225'] ?? array() as $field ) {
			$name = self::clean( $field['subfields']['a'][0] ?? '' );

			if ( '9' === $field['ind2'] || self::find_series( $name ) ) {
				$series[] = array(
					'name'   => $name,
					'number' => $field['subfields']['v'][0] ?? '',
				);
			}
		}

		if ( self::get_first( $record, '200', 'h' ) ) {
			$series[] = array(
				'name'   => self::get_first( $record, '200', 'a' ),
				'number' => self::get_first( $record, '200', 'h' ),
			);
		}

		return array_find( $series, fn( array $item ) => '' !== $item['name'] ) ?? array(
			'name'   => '',
			'number' => '',
		);
	}

	/**
	 * The collection of a record and the number of the book in it, by the name the library gives it among the collections
	 * of the publisher: the collection followed by its sub-collection, as the library names them, "Folio essais" for
	 * "Folio, Essais", or the sub-collection, or the one of the library that ends with it, "Pocket Science-fiction" for
	 * "Presses pocket, Science-fiction", or the collection; else the names of the catalogue. A collection named after the
	 * publisher, such as 10/18, gives its number alone; a series is left out. An older record may name the collection in
	 * its link to it only: "Mango poche (Paris)", "Folio. Science-fiction".
	 *
	 * @param array<int|string,list<Field>> $record    Record.
	 * @param string                        $publisher Name of the publisher, as the library gives it.
	 * @param string                        $series    Name of the series of the record in the catalogue, empty for none.
	 * @return array{name:string,number:string}
	 */
	private static function find_collection( array $record, string $publisher, string $series ): array {
		$parent      = get_terms(
			array(
				'taxonomy'   => 'library_publisher',
				'hide_empty' => false,
				'parent'     => 0,
				'name'       => $publisher,
			)
		);
		$collections = is_array( $parent ) && $parent ? get_terms(
			array(
				'taxonomy'   => 'library_publisher',
				'hide_empty' => false,
				'child_of'   => $parent[0]->term_id,
			)
		) : array();
		$collections = is_array( $collections ) ? $collections : array();
		$named       = fn( string $name ) => array_find( $collections, fn( WP_Term $term ) => self::compact( $term->name ) === self::compact( $name ) );
		$links       = array_map(
			function ( array $field ) {
				$parts = explode( '. ', (string) preg_replace( '/\s*\([^)]*\)/', '', $field['subfields']['t'][0] ?? '' ), 2 );

				return array(
					'ind2'      => '',
					'subfields' => array(
						'a' => array( $parts[0] ),
						'i' => array( $parts[1] ?? '' ),
						'v' => $field['subfields']['v'] ?? array(),
					),
				);
			},
			$record['410'] ?? array()
		);
		$found       = null;

		foreach ( array( ...$record['225'] ?? array(), ...$links ) as $field ) {
			$name = self::clean( (string) preg_replace( '/^collection\s+/i', '', $field['subfields']['a'][0] ?? '' ) );
			$sub  = self::clean( $field['subfields']['i'][0] ?? '' );
			// An older record numbers the book in the link to its collection only.
			$number = $field['subfields']['v'][0] ?? self::get_first( $record, '410', 'v' );

			// A series, or a director named in place of a collection.
			if ( '9' === $field['ind2'] || ! $name || self::compact( $name ) === self::compact( $series ) || self::find_series( $name ) || str_starts_with( self::normalize( $name ), 'dirigee par' ) ) {
				continue;
			}

			$is_publisher = self::normalize_publisher( $name ) === self::normalize_publisher( $publisher );
			$ending       = $sub ? array_filter( $collections, fn( WP_Term $term ) => str_ends_with( ' ' . self::normalize( $term->name ), ' ' . self::normalize( $sub ) ) ) : array();
			$term         = ( $sub ? $named( "$name $sub" ) ?? $named( $sub ) : null ) ?? ( 1 === count( $ending ) ? current( $ending ) : null ) ?? ( $is_publisher ? null : $named( $name ) );

			if ( $term ) {
				return array(
					'name'   => Taxonomies::get_collection_name( $term ),
					'number' => $number,
				);
			}

			$found ??= array(
				'name'   => $is_publisher ? $sub : trim( "$name $sub" ),
				'number' => $number,
			);
		}

		return $found ?? array(
			'name'   => '',
			'number' => '',
		);
	}

	/**
	 * The series of the library of a name, whatever its case, accents and spaces, its cycles aside: "Lastman" for "Last
	 * Man"; null for none.
	 *
	 * @param string $name Name of the series in the catalogue.
	 * @return WP_Term|null
	 */
	private static function find_series( string $name ): ?WP_Term {
		$series = get_terms(
			array(
				'taxonomy'   => 'library_series',
				'hide_empty' => false,
			)
		);

		return array_find( is_array( $series ) ? $series : array(), fn( WP_Term $term ) => self::compact( $term->name ) === self::compact( $name ) );
	}

	/**
	 * The original language of a record as a language of the library, by its code: the language translated from, else
	 * the language of the text, unless it is a translation, as an older record may tell only by its translator; 0 when
	 * the library has none of that code.
	 *
	 * @param array<int|string,list<Field>>        $record Record.
	 * @param list<array{name:string,role:string}> $people People of the book.
	 * @return int Term ID.
	 */
	private static function find_language( array $record, array $people ): int {
		$field      = $record['101'][0] ?? array(
			'ind1'      => '',
			'ind2'      => '',
			'subfields' => array(),
		);
		$statements = implode( ' ', array( ...$record['200'][0]['subfields']['f'] ?? array(), ...$record['200'][0]['subfields']['g'] ?? array() ) );
		$translated = '1' === $field['ind1'] || in_array( 'translator', array_column( $people, 'role' ), true ) || 1 === preg_match( '/\btrad/i', $statements );
		$code       = $field['subfields']['c'][0] ?? ( $translated ? '' : $field['subfields']['a'][0] ?? '' );

		return self::find_language_term( $code );
	}

	/**
	 * The languages of the text of a record, its edition languages, as languages of the library: those the library has.
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @return list<int> Term IDs.
	 */
	private static function find_edition_languages( array $record ): array {
		$codes = $record['101'][0]['subfields']['a'] ?? array();

		return array_values( array_unique( array_filter( array_map( array( self::class, 'find_language_term' ), $codes ) ) ) );
	}

	/**
	 * A language of the library from a code of the catalogue, ISO 639-2 such as fre: 0 when the library has none of it.
	 *
	 * @param string $code Code of the catalogue, or empty.
	 * @return int Term ID.
	 */
	private static function find_language_term( string $code ): int {
		$code = $code ? Locale::getPrimaryLanguage( Locale::canonicalize( self::LANGUAGE_CODES[ $code ] ?? $code ) ?? '' ) ?? '' : '';
		$term = $code ? get_term_by( 'slug', $code, 'library_language' ) : false;

		return $term instanceof WP_Term ? $term->term_id : 0;
	}

	/**
	 * The form of a book as a form of the library: the one most books of its series have, else the one the catalogue
	 * tells, when the library has a form of that name; 0 for none.
	 *
	 * @param array<int|string,list<Field>> $record Record.
	 * @param WP_Term|null                  $series Series of the library of the book.
	 * @return int Term ID.
	 */
	private static function find_form( array $record, ?WP_Term $series ): int {
		if ( $series ) {
			$books = get_posts(
				array(
					'post_type'      => 'library_book',
					'post_status'    => Status::get_values(),
					'posts_per_page' => 20,
					'fields'         => 'ids',
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- The books of one series.
						array(
							'taxonomy'         => 'library_series',
							'terms'            => $series->term_id,
							'include_children' => false,
						),
					),
				)
			);
			$forms = array();

			foreach ( $books as $book_id ) {
				$terms = wp_get_post_terms( $book_id, 'library_form', array( 'fields' => 'ids' ) );
				$forms = array( ...$forms, ...( is_array( $terms ) ? $terms : array() ) );
			}

			if ( $forms ) {
				$counts = array_count_values( array_map( 'intval', $forms ) );

				arsort( $counts );

				return (int) array_key_first( $counts );
			}
		}

		// Its type of illustrations, then its literary form, told after its title, or else coded.
		$coded = self::get_first( $record, '105', 'a' );
		$words = self::normalize( self::get_first( $record, '200', 'e' ) );
		$kind  = match ( true ) {
			str_contains( substr( $coded, 4, 4 ), 't' ) => 'comics',
			(bool) preg_match( '/^\[?nouvelles\b/', $words ) => 'stories',
			(bool) preg_match( '/^\[?roman\b/', $words ) => 'novel',
			'b' === substr( $coded, 11, 1 ) => 'drama',
			'g' === substr( $coded, 11, 1 ) => 'poetry',
			'a' === substr( $coded, 11, 1 ) => 'novel',
			default => '',
		};

		if ( ! $kind ) {
			return 0;
		}

		$forms = get_terms(
			array(
				'taxonomy'   => 'library_form',
				'hide_empty' => false,
			)
		);
		$found = array_find( is_array( $forms ) ? $forms : array(), fn( WP_Term $term ) => in_array( self::normalize( $term->name ), self::FORMS[ $kind ], true ) );

		return $found ? $found->term_id : 0;
	}

	/**
	 * The ISBN-10 of an ISBN-13 of 978, which the catalogue holds for the books of before 2007; empty for another code.
	 *
	 * @param string $digits Digits of an ISBN.
	 * @return string
	 */
	private static function to_isbn10( string $digits ): string {
		if ( ! preg_match( '/^978(\d{9})\d$/', $digits, $matches ) ) {
			return '';
		}

		$sum = 0;

		foreach ( str_split( $matches[1] ) as $index => $digit ) {
			$sum += (int) $digit * ( 10 - $index );
		}

		$check = ( 11 - $sum % 11 ) % 11;

		return $matches[1] . ( 10 === $check ? 'X' : $check );
	}

	/**
	 * A name of the catalogue without its punctuation of catalogue and its additions in brackets: "Les Derynis" for "Les
	 * Derynis.", "10-18" for "10-18 [Dix-dix-huit]".
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private static function clean( string $name ): string {
		return trim( (string) preg_replace( array( '/\s*\[[^\]]*\]/', '/[\s.,;:]+$/' ), '', $name ) );
	}

	/**
	 * A name to compare, whatever its case, accents and punctuation: "hojo tsukasa" for "Hōjō, Tsukasa".
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private static function normalize( string $name ): string {
		$name = remove_accents( mb_strtolower( wp_specialchars_decode( $name, ENT_QUOTES ) ) );

		return trim( (string) preg_replace( '/[^a-z0-9\[\]]+/', ' ', $name ) );
	}

	/**
	 * A name to compare, whatever its spaces too: "lastman" for "Last Man".
	 *
	 * @param string $name Name.
	 * @return string
	 */
	private static function compact( string $name ): string {
		return str_replace( ' ', '', self::normalize( $name ) );
	}

	/**
	 * The name of a publisher to compare, without "Éditions" or "Éd.": "rouergue" for "Éditions du Rouergue".
	 *
	 * @param string $name Name of a publisher.
	 * @return string
	 */
	private static function normalize_publisher( string $name ): string {
		return (string) preg_replace( '/^(les )?(editions|edition|ed)( (du|de la|de l|des|d))? /', '', self::normalize( $name ) );
	}
}
