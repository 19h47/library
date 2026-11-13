<?php
/**
 * REST API: what the routes of the plugin give, as JSON schemas, which the index of the API shows asked for help.
 *
 * @link       https://github.com/19h47/library
 * @since      9.36.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * The schemas of the routes of the plugin, by route: the parts they share, a term linked, an author, an achievement, are
 * written once, and the meta of a book come from their registration, as core describes its own.
 *
 * @since      9.36.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Rest_Schemas {

	/**
	 * The schema of each route of the plugin, by its pattern, as register_rest_route() takes it.
	 *
	 * @return array<non-falsy-string,callable():array<string,mixed>>
	 */
	public static function get_schemas(): array {
		return array(
			'books'                  => array( self::class, 'get_book' ),
			'books/(?P<id>\d+)'      => array( self::class, 'get_book_form' ),
			'books/bnf'              => array( self::class, 'get_book_form' ),
			'covers'                 => array( self::class, 'get_cover' ),
			'stats'                  => array( self::class, 'get_stats' ),
			'readings'               => array( self::class, 'get_readings' ),
			'idea'                   => array( self::class, 'get_idea' ),
			'achievements'           => array( self::class, 'get_achievements' ),
			'recaps/(?P<year>\d{4})' => array( self::class, 'get_recap' ),
			'people/(?P<id>\d+)'     => array( self::class, 'get_person' ),
			'series/(?P<id>\d+)'     => array( self::class, 'get_series' ),
			'publishers/(?P<id>\d+)' => array( self::class, 'get_publisher' ),
		);
	}

	/**
	 * A schema of the plugin: its title, and its properties.
	 *
	 * @param string              $title      Title, the name of what it describes.
	 * @param array<string,mixed> $properties Properties, by name.
	 * @return array<string,mixed>
	 */
	private static function build( string $title, array $properties ): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $title,
			'type'       => 'object',
			'properties' => $properties,
		);
	}

	/**
	 * A property: its type, and its description.
	 *
	 * @param string|string[]     $type        Type, or types.
	 * @param string              $description Description.
	 * @param array<string,mixed> $more        More of its schema, such as its items or its properties.
	 * @return array<string,mixed>
	 */
	private static function property( string|array $type, string $description, array $more = array() ): array {
		return array(
			'type'        => $type,
			'description' => $description,
		) + $more;
	}

	/**
	 * A list of IDs of books, in an order.
	 *
	 * @param string $description Description.
	 * @return array<string,mixed>
	 */
	private static function book_ids( string $description ): array {
		return self::property( 'array', $description, array( 'items' => array( 'type' => 'integer' ) ) );
	}

	/**
	 * A term a page links to: its ID and its name, none for no term.
	 *
	 * @param string $description Description.
	 * @return array<string,mixed>
	 */
	private static function term_link( string $description ): array {
		return self::property(
			array( 'object', 'null' ),
			$description,
			array(
				'properties' => array(
					'id'   => self::property( 'integer', __( 'Term ID.', 'library' ) ),
					'name' => self::property( 'string', __( 'Name, in plain text.', 'library' ) ),
				),
			)
		);
	}

	/**
	 * An achievement, earned or close: what it is, what it says, what it leads to, and how far it is for a close one.
	 *
	 * @param string $description Description.
	 * @return array<string,mixed>
	 */
	private static function achievement( string $description ): array {
		return self::property(
			array( 'object', 'null' ),
			$description,
			array(
				'properties' => array(
					'kind'       => self::property( 'string', __( 'Kind of the achievement, such as streak or series.', 'library' ) ),
					'title'      => self::property( 'string', __( 'Title, as shown.', 'library' ) ),
					'subject'    => self::property( 'string', __( 'What it is about, such as an author, or empty.', 'library' ) ),
					'detail'     => self::property( 'string', __( 'Detail, as shown under its title.', 'library' ) ),
					'target'     => self::property(
						array( 'object', 'null' ),
						__( 'What it leads to: a book, a search, or an author, a series or a collection, by its type; none for nothing.', 'library' ),
						array(
							'properties' => array(
								'type'     => self::property( 'string', __( 'Type: book, search, author, series or collection.', 'library' ) ),
								'id'       => self::property( 'integer', __( 'ID of the book, or term ID.', 'library' ) ),
								'search'   => self::property( 'string', __( 'Search of the books, for a search.', 'library' ) ),
								'not_kept' => self::property( 'boolean', __( 'Whether its books include books not kept.', 'library' ) ),
							),
						)
					),
					'date'       => self::property( 'string', __( 'Date it was earned, Y-m-d, empty for a close one.', 'library' ) ),
					'easter_egg' => self::property( 'boolean', __( 'Whether it is hidden until earned.', 'library' ) ),
					'done'       => self::property( 'integer', __( 'Books done, for a close one.', 'library' ) ),
					'total'      => self::property( 'integer', __( 'Books to do, for a close one.', 'library' ) ),
				),
			)
		);
	}

	/**
	 * Authors, who wrote the most first, with their description and how many of the books they wrote.
	 *
	 * @return array<string,mixed>
	 */
	private static function authors(): array {
		return self::property(
			'array',
			__( 'Authors, under the name they are known by, who wrote the most first.', 'library' ),
			array(
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => self::property( 'integer', __( 'Term ID.', 'library' ) ),
						'name'        => self::property( 'string', __( 'Name, in plain text.', 'library' ) ),
						'description' => self::property( 'string', __( 'Description, HTML.', 'library' ) ),
						'book_count'  => self::property( 'integer', __( 'How many of these books they wrote.', 'library' ) ),
					),
				),
			)
		);
	}

	/**
	 * What the pages of a person, a series and a collection share.
	 *
	 * @return array<string,mixed>
	 */
	private static function page(): array {
		return array(
			'id'          => self::property( 'integer', __( 'Term ID.', 'library' ) ),
			'name'        => self::property( 'string', __( 'Name, in plain text.', 'library' ) ),
			'description' => self::property( 'string', __( 'Description, HTML.', 'library' ) ),
			'books'       => self::book_ids( __( 'IDs of its books, in order.', 'library' ) ),
			'read_count'  => self::property( 'integer', __( 'How many of its books are read.', 'library' ) ),
			'completion'  => self::achievement( __( 'Completion its books count for, earned or close, if any.', 'library' ) ),
		);
	}

	/**
	 * A person of a book, as named on it, with their role: what a book of `wp/v2/books` gives in its names, and what the
	 * route of the book form gives and takes in its people. Their term ID is given, never needed: a name sent is found by
	 * its name, so that a book given can be sent back as it is.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_book_person(): array {
		return array(
			'title'                => 'library-book-person',
			'type'                 => 'object',
			'properties'           => array(
				'id'   => self::property( 'integer', __( 'Term ID, given, never needed: a name not found adds a person.', 'library' ) ),
				'name' => self::property( 'string', __( 'Name, as named on the book, or another name of the person.', 'library' ), array( 'required' => true ) ),
				'role' => self::property(
					'string',
					__( 'Role, such as author, translator or colorist.', 'library' ),
					array(
						'enum'     => array_keys( Meta::get_role_choices() ),
						'required' => true,
					)
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * A publisher of a book, or a collection it is in, with its term ID: what a book of `wp/v2/books` gives in its
	 * publisher names, from the publisher down.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_book_publisher(): array {
		return array(
			'title'                => 'library-book-publisher',
			'type'                 => 'object',
			'properties'           => array(
				'id'   => self::property( 'integer', __( 'Term ID, of a publisher or of a collection: its page in the app.', 'library' ) ),
				'name' => self::property( 'string', __( 'Name, HTML, as saved.', 'library' ) ),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * A book, as `wp/v2/books` gives it: the routes that add or edit a book give it back.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_book(): array {
		return get_post_type_object( 'library_book' )->get_rest_controller()?->get_public_item_schema() ?? array();
	}

	/**
	 * A book as its form edits it: its names as typed, each with its role, its terms and its meta by their names.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_book_form(): array {
		$registered = get_registered_meta_keys( 'post', 'library_book' );
		$meta       = array();

		// The readings are written through the route, but edited by their own form: the book form gives none.
		foreach ( array_diff( array( ...Meta::KEYS, 'library_edition_languages' ), array( 'library_reading' ) ) as $key ) {
			$meta[ Meta::get_name( $key ) ] = array_filter(
				array(
					'type'        => $registered[ $key ]['type'] ?? 'string',
					'description' => $registered[ $key ]['label'] ?? '',
				)
			);
		}

		return self::build(
			'library-book-form',
			array(
				'id'         => self::property( 'integer', __( 'ID of the book, none for a book of the catalogue.', 'library' ) ),
				'title'      => self::property( 'string', __( 'Title of the book.', 'library' ) ),
				'excerpt'    => self::property( 'string', __( 'Summary of the book in a few words, its excerpt.', 'library' ) ),
				'content'    => self::property( 'string', __( 'Notes on the book, its content.', 'library' ) ),
				'people'     => self::property(
					'array',
					__( 'People of the book, by their name or another one, each with its role, such as colorist; a name not found adds a person.', 'library' ),
					array( 'items' => self::get_book_person() )
				),
				'publisher'  => self::property( 'string', __( 'Name of the publisher; a name not found adds a publisher.', 'library' ) ),
				'collection' => self::property( 'string', __( 'Collection, under the publisher and sent with it, a sub-collection after its collection, separated with commas, such as “Folio, Science-fiction”; a name not found adds a collection.', 'library' ) ),
				'series'     => self::property( 'string', __( 'Series, after its cycles, separated with commas, such as “Star Wars, Young Jedi Knights”; a name not found adds a series, or a cycle.', 'library' ) ),
				'languages'  => self::property( 'array', __( 'Term IDs of the original languages.', 'library' ), array( 'items' => array( 'type' => 'integer' ) ) ),
				'form'       => self::property( 'integer', __( 'Term ID of the form, such as a novel or a comic.', 'library' ) ),
				'meta'       => self::property( 'object', __( 'Book meta by their names, without the prefix of their keys, such as isbn, as the book form saves them.', 'library' ), array( 'properties' => $meta ) ),
			)
		);
	}

	/**
	 * The image of a cover, as `wp/v2/media` gives it.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_cover(): array {
		return get_post_type_object( 'attachment' )?->get_rest_controller()?->get_public_item_schema() ?? array();
	}

	/**
	 * The reading stats.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_stats(): array {
		return self::build(
			'library-stats',
			array(
				'read_count'   => self::property( 'integer', __( 'Books read, of those in the library.', 'library' ) ),
				'book_count'   => self::property( 'integer', __( 'Books in the library.', 'library' ) ),
				'percent'      => self::property( 'integer', __( 'Percentage of the books read.', 'library' ) ),
				'year_to_date' => self::property(
					'object',
					__( 'This year to date, against the same date last year, and the pace.', 'library' ),
					array(
						'properties' => array(
							'year'                 => self::property( 'integer', __( 'This year.', 'library' ) ),
							'read_count'           => self::property( 'integer', __( 'Books read this year.', 'library' ) ),
							'last_year_read_count' => self::property( 'integer', __( 'Books read by this date last year.', 'library' ) ),
							'projected_read_count' => self::property( array( 'integer', 'null' ), __( 'Books read by the end of the year at this pace, none without a pace.', 'library' ) ),
							'to_read_count'        => self::property( 'integer', __( 'Books to read.', 'library' ) ),
							'all_read_in'          => self::property( array( 'integer', 'null' ), __( 'Year they would all be read in at this pace, none without a pace.', 'library' ) ),
						),
					)
				),
			)
		);
	}

	/**
	 * A month of the readings, latest first.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_readings(): array {
		return self::build(
			'library-readings-month',
			array(
				'month'    => self::property( 'string', __( 'Month, Y-m.', 'library' ) ),
				'readings' => self::property(
					'array',
					__( 'Readings of the month, latest first: a book read again comes once per reading.', 'library' ),
					array(
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'date'    => self::property( 'string', __( 'Read date, Y-m-d.', 'library' ) ),
								'book_id' => self::property( 'integer', __( 'ID of the book.', 'library' ) ),
							),
						),
					)
				),
			)
		);
	}

	/**
	 * A reading idea of the week's list.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_idea(): array {
		return self::build(
			'library-idea',
			array(
				'book_id'    => self::property( 'integer', __( 'ID of the book.', 'library' ) ),
				'reason'     => self::property( 'string', __( 'Why it is an idea.', 'library' ) ),
				'position'   => self::property( 'integer', __( 'Position in the week\'s list of ideas, 0 for the idea of the week.', 'library' ) ),
				'idea_count' => self::property( 'integer', __( 'Ideas of the week.', 'library' ) ),
			)
		);
	}

	/**
	 * The achievements, earned and close.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_achievements(): array {
		return self::build(
			'library-achievements',
			array(
				'earned' => self::property( 'array', __( 'Achievements earned, latest first.', 'library' ), array( 'items' => self::achievement( __( 'An achievement earned.', 'library' ) ) ) ),
				'close'  => self::property( 'array', __( 'Achievements close to be earned, closest first.', 'library' ), array( 'items' => self::achievement( __( 'An achievement close to be earned.', 'library' ) ) ) ),
			)
		);
	}

	/**
	 * The recap of a year: a reading is a read date of the year, a book read again counting once per reading.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_recap(): array {
		$named = fn( string $description ): array => self::property(
			'array',
			$description,
			array(
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'            => self::property( 'integer', __( 'Term ID.', 'library' ) ),
						'name'          => self::property( 'string', __( 'Name, in plain text.', 'library' ) ),
						'reading_count' => self::property( 'integer', __( 'Readings in the year.', 'library' ) ),
					),
				),
			)
		);

		return self::build(
			'library-recap',
			array(
				'year'                   => self::property( 'integer', __( 'Year of the recap.', 'library' ) ),
				'years'                  => self::property( 'array', __( 'Years with a reading, the earliest first.', 'library' ), array( 'items' => array( 'type' => 'integer' ) ) ),
				'day_count'              => self::property( 'integer', __( 'Days gone by: all of them for a year gone.', 'library' ) ),
				'reading_count'          => self::property( 'integer', __( 'Readings of the year.', 'library' ) ),
				'previous_reading_count' => self::property( 'integer', __( 'Readings of the year before.', 'library' ) ),
				'reread_count'           => self::property( 'integer', __( 'Readings of a book read again in the year.', 'library' ) ),
				'books'                  => self::book_ids( __( 'IDs of the books read, in the order they were read.', 'library' ) ),
				'pages'                  => self::property( 'object', __( 'Pages read, and the readings of a book with its number of pages.', 'library' ) ),
				'months'                 => self::property( 'array', __( 'Readings of each month, from January.', 'library' ), array( 'items' => array( 'type' => 'integer' ) ) ),
				'forms'                  => $named( __( 'Forms read, the most read first.', 'library' ) ),
				'people'                 => $named( __( 'People read the most, by the name they are known by.', 'library' ) ),
				'discoveries'            => self::property( 'array', __( 'People read for the first time, in the order they were read.', 'library' ), array( 'items' => self::term_link( __( 'A person.', 'library' ) ) ) ),
				'languages'              => self::property( 'array', __( 'Original languages, as ISO 639 codes, the most read first, each with its readings.', 'library' ) ),
				'series'                 => $named( __( 'Series read, the most read first, each started in the year or finished, or not.', 'library' ) ),
				'rating'                 => self::property( array( 'number', 'null' ), __( 'Average rating of the books read, none without one.', 'library' ) ),
				'best'                   => self::book_ids( __( 'IDs of the books read with a rating, the highest first.', 'library' ) ),
				'records'                => self::property( 'object', __( 'IDs of the longest, the shortest and the oldest books read, 0 for none.', 'library' ) ),
				'achievements'           => self::property( 'array', __( 'Achievements earned in the year.', 'library' ), array( 'items' => self::achievement( __( 'An achievement earned.', 'library' ) ) ) ),
				'added_count'            => self::property( 'integer', __( 'Books added in the year, but those to buy.', 'library' ) ),
				'calendar'               => self::property( 'object', __( 'Days of reading: the level of each day, the memorable ones, the days read, the longest streak, the best day and the readings dated.', 'library' ) ),
			)
		);
	}

	/**
	 * The page of a person, under the name they are known by.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_person(): array {
		return self::build(
			'library-person',
			self::page() + array(
				'other_names' => self::property( 'array', __( 'Other names, such as a pseudonym or their birth name.', 'library' ), array( 'items' => array( 'type' => 'string' ) ) ),
				'series'      => self::property(
					'array',
					__( 'Series of their books, those with the most first.', 'library' ),
					array(
						'items' => array(
							'type'       => 'object',
							'properties' => array(
								'id'          => self::property( 'integer', __( 'Term ID.', 'library' ) ),
								'name'        => self::property( 'string', __( 'Name, after its cycles.', 'library' ) ),
								'description' => self::property( 'string', __( 'Description, HTML.', 'library' ) ),
								'book_count'  => self::property( 'integer', __( 'How many of their books it holds.', 'library' ) ),
								'read_count'  => self::property( 'integer', __( 'How many of these are read.', 'library' ) ),
							),
						),
					)
				),
				'rating'      => self::property( array( 'number', 'null' ), __( 'Average rating of their books, none without one.', 'library' ) ),
				'page_count'  => self::property( 'integer', __( 'Pages of their books, those with a number of pages.', 'library' ) ),
			)
		);
	}

	/**
	 * The page of a series, or of a cycle.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_series(): array {
		return self::build(
			'library-series',
			self::page() + array(
				'cycle'   => self::term_link( __( 'Cycle of the series, if any.', 'library' ) ),
				'issn'    => self::property( 'string', __( 'ISSN of the magazine, or empty.', 'library' ) ),
				'parts'   => self::property( 'array', __( 'Series of a cycle.', 'library' ), array( 'items' => self::term_link( __( 'A series.', 'library' ) ) ) ),
				'authors' => self::authors(),
			)
		);
	}

	/**
	 * The page of a collection, or of a publisher.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_publisher(): array {
		return self::build(
			'library-publisher',
			self::page() + array(
				'publisher'   => self::term_link( __( 'Publisher of a collection.', 'library' ) ),
				'collection'  => self::term_link( __( 'Collection a sub-collection is part of.', 'library' ) ),
				'collections' => self::property( 'array', __( 'Its collections.', 'library' ), array( 'items' => self::term_link( __( 'A collection.', 'library' ) ) ) ),
				'authors'     => self::authors(),
			)
		);
	}
}
