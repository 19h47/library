<?php
/**
 * Book meta: registration, sanitization and saving.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use DateTimeImmutable;
use WP_Error;

/**
 * Registers book meta so every write (form, quick edit, REST) goes through
 * the same sanitize callbacks.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Meta {

	/**
	 * Text meta keys.
	 *
	 * @var string[]
	 */
	public const array TEXT_KEYS = array( 'library_subtitle', 'library_isbn', 'library_issn', 'library_volume_number', 'library_collection_number' );

	/**
	 * Date meta keys, stored as Y-m-d.
	 *
	 * @var string[]
	 */
	private const array DATE_KEYS = array( 'library_edition_date' );

	/**
	 * Format of the start or the end of a reading with its time, which sorts after the day alone: 2026-11-01T21:40. A
	 * reading started or ended today takes its time, so that the readings of a day follow one another.
	 *
	 * @var string
	 */
	public const string READING_TIME_FORMAT = 'Y-m-d\TH:i';

	/**
	 * The end of a reading in progress, which has none yet, as an interval of ISO 8601-2 left open: 2026-11-08/..
	 *
	 * @var string
	 */
	public const string IN_PROGRESS = '..';

	/**
	 * Pattern of a reading, an interval of ISO 8601 from its start to its end, either unknown, empty, its end open while
	 * it is in progress: 2026-10-20/2026-11-02, /2026-11-02, /, 2026-11-08T09:15/..
	 *
	 * @var string
	 */
	private const string READING_PATTERN = '^(\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?)?/(\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?|\.\.)?$';

	/**
	 * Pattern of an ISBN-10 or ISBN-13, with or without hyphens or spaces, or of another EAN-13, the barcode of a book
	 * without ISBN, as its 13 digits.
	 *
	 * Browsers compile the pattern of an input with the v flag, where a hyphen in a class is escaped: an invalid pattern checks nothing.
	 *
	 * @var string
	 */
	private const string ISBN_PATTERN = '^(?:(?=.{17}$)97[89][ \\-](?:[0-9]+[ \\-]){2}[0-9]+[ \\-][0-9]|[0-9]{13}|(?=.{13}$)(?:[0-9]+[ \\-]){2}[0-9]+[ \\-][0-9Xx]|[0-9]{9}[0-9Xx])$';

	/**
	 * Pattern of an ISSN, such as 1234-5678.
	 *
	 * @var string
	 */
	private const string ISSN_PATTERN = '^\\S{4}-\\S{4}$';

	/**
	 * Limits of a rating, which its schema states and sanitize_rating() keeps: from 1 to 10, by half points.
	 *
	 * @var array{minimum:int,maximum:int,multipleOf:float}
	 */
	private const array RATING_LIMITS = array(
		'minimum'    => 1,
		'maximum'    => 10,
		'multipleOf' => 0.5,
	);

	/**
	 * Fewest pages of a book, which the schema of its pages states and sanitize_pages() keeps.
	 *
	 * @var int
	 */
	private const int MIN_PAGES = 1;

	/**
	 * Meta of the names of a book with another role than author, by role, such as its colorists: the term IDs of these
	 * names in the people taxonomy, as catalogues give each name its function. A name of a book without role is one of
	 * its authors.
	 *
	 * @var array<string,string>
	 */
	public const array ROLE_KEYS = array(
		'translator'     => 'library_translators',
		'colorist'       => 'library_colorists',
		'inker'          => 'library_inkers',
		'letterer'       => 'library_letterers',
		'director'       => 'library_directors',
		'adapted_author' => 'library_adapted_authors',
	);

	/**
	 * Book meta edited in quick edit, and the read date, the end of the latest reading, which is no meta of its own: see
	 * replace_latest_read_date().
	 *
	 * @var string[]
	 */
	public const array QUICK_EDIT_KEYS = array( 'library_isbn', 'library_pages', 'library_read_date', 'library_rating' );

	/**
	 * All book meta keys, its readings among them, and whether it is read and being read, which are no meta of their own
	 * but its readings: see get_reading_state() and save().
	 *
	 * @var string[]
	 */
	public const array KEYS = array( ...self::TEXT_KEYS, ...self::DATE_KEYS, 'library_published_date', 'library_pages', 'library_rating', 'library_collective', 'library_reading', 'library_read', 'library_in_progress' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_filter( 'get_post_metadata', array( $this, 'get_reading_state' ), 10, 4 );
	}

	/**
	 * Register book meta, with the label, the description and the schema that the forms of the admin and of the reading app
	 * take their fields from.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		$labels       = array(
			'library_subtitle'          => __( 'Subtitle', 'library' ),
			'library_isbn'              => __( 'ISBN', 'library' ),
			'library_issn'              => __( 'ISSN', 'library' ),
			'library_volume_number'     => __( 'Volume Number', 'library' ),
			'library_collection_number' => __( 'Collection Number', 'library' ),
			'library_translators'       => __( 'Translators', 'library' ),
			'library_edition_date'      => __( 'Edition date', 'library' ),
			'library_reading'           => __( 'Readings', 'library' ),
			'library_published_date'    => __( 'First published', 'library' ),
			'library_pages'             => __( 'Pages', 'library' ),
			'library_rating'            => __( 'Rating', 'library' ),
			'library_collective'        => __( 'Collective work', 'library' ),
			'library_read'              => __( 'Read', 'library' ),
			'library_in_progress'       => __( 'In progress', 'library' ),
			'library_colorists'         => __( 'Colorists', 'library' ),
			'library_inkers'            => __( 'Inkers', 'library' ),
			'library_letterers'         => __( 'Letterers', 'library' ),
			'library_directors'         => __( 'Publishing directors', 'library' ),
			'library_adapted_authors'   => __( 'Adapted authors', 'library' ),
			'library_edition_languages' => __( 'Edition languages', 'library' ),
		);
		$descriptions = array(
			'library_subtitle'          => __( 'Shown after the title, such as “The Knife: Meditations After an Attempted Murder”.', 'library' ),
			'library_isbn'              => __( '10 or 13 digits, with or without hyphens, such as 9782070360246, which becomes 978-2-07-036024-6; for a book without ISBN, the 13 digits of its barcode.', 'library' ),
			'library_issn'              => __( 'Two groups of four characters, such as 0395-2649.', 'library' ),
			'library_collection_number' => __( 'Shown after the collection, such as “Pocket Science-fiction no. 5667”.', 'library' ),
			'library_published_date'    => __( 'When the work first came out, whatever the edition: a year, a year and month, or a date, such as 1929, 1929-11 or 1929-11-08.', 'library' ),
			'library_reading'           => __( 'From its start to its end, each one left empty when it is unknown.', 'library' ),
			'library_in_progress'       => __( 'Being read, for the first time or again, since the day it started: a reading added ends it.', 'library' ),
			'library_rating'            => __( 'Rating from 1 to 10.', 'library' ),
			'library_collective'        => __( 'A magazine or an anthology, whose authors each give a part: it does not count among the books of any of them.', 'library' ),
			'library_edition_languages' => __( 'The languages of its text, several for a multilingual edition: it is read in its original language when they all are original languages of the book.', 'library' ),
		);
		$patterns     = array(
			'library_isbn' => self::ISBN_PATTERN,
			'library_issn' => self::ISSN_PATTERN,
		);
		$sanitizers   = array( 'library_isbn' => array( self::class, 'sanitize_isbn' ) );

		foreach ( self::TEXT_KEYS as $key ) {
			register_post_meta(
				'library_book',
				$key,
				array(
					'type'              => 'string',
					'label'             => $labels[ $key ],
					'description'       => $descriptions[ $key ] ?? '',
					'single'            => true,
					'show_in_rest'      => isset( $patterns[ $key ] ) ? array( 'schema' => array( 'pattern' => $patterns[ $key ] ) ) : true,
					'sanitize_callback' => $sanitizers[ $key ] ?? 'sanitize_text_field',
				)
			);
		}

		foreach ( self::DATE_KEYS as $key ) {
			register_post_meta(
				'library_book',
				$key,
				array(
					'type'              => 'string',
					'label'             => $labels[ $key ],
					'single'            => true,
					'show_in_rest'      => array(
						'schema' => array(
							'pattern' => '^\d{4}-\d{2}-\d{2}$',
							'format'  => 'date',
						),
					),
					'sanitize_callback' => array( self::class, 'sanitize_date' ),
				)
			);
		}

		// One row per reading, read again or not. The readings are written at once, through Meta::save(), as the book route
		// does: the core REST API removes a value by value, and two readings of unknown dates, the same, would go together.
		register_post_meta(
			'library_book',
			'library_reading',
			array(
				'type'              => 'string',
				'label'             => $labels['library_reading'],
				'description'       => $descriptions['library_reading'],
				'single'            => false,
				'show_in_rest'      => array(
					'schema' => array( 'pattern' => self::READING_PATTERN ),
				),
				'sanitize_callback' => array( self::class, 'sanitize_reading' ),
				'auth_callback'     => '__return_false',
			)
		);

		// Often known by its year only.
		register_post_meta(
			'library_book',
			'library_published_date',
			array(
				'type'              => 'string',
				'label'             => $labels['library_published_date'],
				'description'       => $descriptions['library_published_date'],
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array( 'pattern' => '^\d{4}(-\d{2}(-\d{2})?)?$' ),
				),
				'sanitize_callback' => array( self::class, 'sanitize_partial_date' ),
			)
		);

		register_post_meta(
			'library_book',
			'library_pages',
			array(
				'type'              => 'integer',
				'label'             => $labels['library_pages'],
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array( 'minimum' => self::MIN_PAGES ),
				),
				'sanitize_callback' => array( self::class, 'sanitize_pages' ),
			)
		);

		register_post_meta(
			'library_book',
			'library_rating',
			array(
				'type'              => 'number',
				'label'             => $labels['library_rating'],
				'description'       => $descriptions['library_rating'],
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => self::RATING_LIMITS,
				),
				'sanitize_callback' => array( self::class, 'sanitize_rating' ),
			)
		);

		// A magazine or an anthology, as catalogues tell a compilation from a work its authors made together.
		register_post_meta(
			'library_book',
			'library_collective',
			array(
				'type'              => 'boolean',
				'label'             => $labels['library_collective'],
				'description'       => $descriptions['library_collective'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);

		// Read from the readings, never stored: the forms send it, and save() writes the readings it means.
		register_post_meta(
			'library_book',
			'library_read',
			array(
				'type'              => 'boolean',
				'label'             => $labels['library_read'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => '__return_false',
			)
		);

		// Apart from the read state, a book read may be read again: read from its reading in progress, never stored either.
		register_post_meta(
			'library_book',
			'library_in_progress',
			array(
				'type'              => 'boolean',
				'label'             => $labels['library_in_progress'],
				'description'       => $descriptions['library_in_progress'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => '__return_false',
			)
		);

		// Languages of the library, as the original languages are: their term IDs, which save() writes at once.
		register_post_meta(
			'library_book',
			'library_edition_languages',
			array(
				'type'              => 'integer',
				'label'             => $labels['library_edition_languages'],
				'description'       => $descriptions['library_edition_languages'],
				'single'            => false,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
			)
		);

		foreach ( self::ROLE_KEYS as $key ) {
			register_post_meta(
				'library_book',
				$key,
				array(
					'type'              => 'integer',
					'label'             => $labels[ $key ],
					'single'            => false,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
				)
			);
		}
	}

	/**
	 * Roles a form offers for a name of a book, by value, with their label: author, then the other roles, those of ROLE_KEYS,
	 * in their order.
	 *
	 * @return array<string,string>
	 */
	public static function get_role_choices(): array {
		$labels  = array(
			'translator'     => _x( 'Translator', 'role', 'library' ),
			'colorist'       => _x( 'Colorist', 'role', 'library' ),
			'inker'          => _x( 'Inker', 'role', 'library' ),
			'letterer'       => _x( 'Letterer', 'role', 'library' ),
			'director'       => _x( 'Publishing director', 'role', 'library' ),
			/* translators: Role of the author of the work a book is adapted from or continues, as the BnF names it: Goscinny for an Asterix by Ferri. */
			'adapted_author' => _x( 'Adapted author', 'role', 'library' ),
		);
		$choices = array( 'author' => _x( 'Author', 'role', 'library' ) );

		foreach ( array_keys( self::ROLE_KEYS ) as $role ) {
			$choices[ $role ] = $labels[ $role ];
		}

		return $choices;
	}

	/**
	 * Roles of the names of a book with another role than author, by term ID, such as 519 => "colorist".
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,string>
	 */
	public static function get_roles( int $post_id ): array {
		$roles = array();

		foreach ( self::ROLE_KEYS as $role => $key ) {
			foreach ( get_post_meta( $post_id, $key, false ) as $term_id ) {
				$roles[ (int) $term_id ] = $role;
			}
		}

		return $roles;
	}

	/**
	 * Replace the roles of the names of a book: the names of the book only, author or an unknown role kept as none.
	 *
	 * @param int               $post_id Post ID.
	 * @param array<int,string> $roles   Roles by term ID.
	 * @return void
	 */
	public static function save_roles( int $post_id, array $roles ): void {
		$term_ids = wp_get_post_terms( $post_id, 'library_person', array( 'fields' => 'ids' ) );
		$term_ids = is_array( $term_ids ) ? $term_ids : array();

		foreach ( self::ROLE_KEYS as $role => $key ) {
			$role_ids = array_intersect( array_keys( $roles, $role, true ), $term_ids );

			delete_post_meta( $post_id, $key );

			foreach ( $role_ids as $term_id ) {
				add_post_meta( $post_id, $key, $term_id );
			}
		}
	}

	/**
	 * SQL derived table of the names with another role than author, such as a colorist: one row per book and name, its
	 * post_id and term_id, to join by both. Distinct, so that MySQL 5.7 builds it once, rather than reading the meta of each
	 * book again for each of its names, as it does for a subquery on them.
	 *
	 * @return string
	 */
	public static function build_roles_table(): string {
		global $wpdb;

		$keys = "'" . implode( "', '", self::ROLE_KEYS ) . "'";

		return "( SELECT DISTINCT post_id, CAST( meta_value AS UNSIGNED ) AS term_id FROM {$wpdb->postmeta} WHERE meta_key IN ( {$keys} ) )";
	}

	/**
	 * Label of a book meta, as registered.
	 *
	 * @param string $key Meta key.
	 * @return string
	 */
	public static function get_label( string $key ): string {
		return get_registered_meta_keys( 'post', 'library_book' )[ $key ]['label'];
	}

	/**
	 * Description of a book meta, as registered, or empty.
	 *
	 * @param string $key Meta key.
	 * @return string
	 */
	public static function get_description( string $key ): string {
		return get_registered_meta_keys( 'post', 'library_book' )[ $key ]['description'];
	}

	/**
	 * Ratings a form offers, by value, with their label: none, then the ones within RATING_LIMITS, from 1 to 10 by half
	 * points.
	 *
	 * @return array<int|string,string> Labels by rating, a whole rating keyed as an integer, as PHP keys "7".
	 */
	public static function get_rating_choices(): array {
		$ratings = array( '' => __( 'No rating', 'library' ) );

		for ( $rating = self::RATING_LIMITS['minimum']; $rating <= self::RATING_LIMITS['maximum']; $rating += self::RATING_LIMITS['multipleOf'] ) {
			$ratings[ (string) $rating ] = Books::format_rating_out_of_ten( $rating );
		}

		return $ratings;
	}

	/**
	 * The control of a book meta, as its registration says: a select of its choices for the rating, a checkbox for a
	 * boolean, a number with its minimum, maximum and step, a date, or a text with its pattern, anchored in the schema
	 * and anchored already by HTML.
	 *
	 * A rating is chosen rather than typed: a number field reads 7,5 as 75 in Chrome.
	 *
	 * @param string              $key        Meta key.
	 * @param array<string,mixed> $attributes More attributes, such as its ID or value, or ones that replace those of the registration;
	 *                           true gives an attribute without value, null leaves it out.
	 * @return string HTML.
	 */
	public static function get_control( string $key, array $attributes = array() ): string {
		if ( 'library_rating' === $key ) {
			$select = Markup::get_select( self::get_rating_choices(), $attributes['value'] ?? null );

			unset( $attributes['value'] );

			return Markup::set_attributes( $select, $attributes + array( 'name' => $key ) );
		}

		$meta   = get_registered_meta_keys( 'post', 'library_book' )[ $key ];
		$schema = $meta['show_in_rest']['schema'] ?? array();
		$type   = match ( true ) {
			'boolean' === $meta['type'] => 'checkbox',
			in_array( $meta['type'], array( 'integer', 'number' ), true ) => 'number',
			'date' === ( $schema['format'] ?? '' ) => 'date',
			default => 'text',
		};

		return Markup::set_attributes(
			'<input>',
			$attributes + array(
				'type'    => $type,
				'name'    => $key,
				'pattern' => 'text' === $type && isset( $schema['pattern'] ) ? trim( $schema['pattern'], '^$' ) : null,
				'min'     => $schema['minimum'] ?? null,
				'max'     => $schema['maximum'] ?? null,
				'step'    => $schema['multipleOf'] ?? null,
			)
		);
	}

	/**
	 * Whether a book is read and whether it is being read, from its readings: read once it has a reading ended, of a known
	 * date or not, being read while it has one in progress. Its readings are its one record: library_read and
	 * library_in_progress are never stored.
	 *
	 * @param mixed  $value     Value, null to read the meta stored.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key, empty for every meta.
	 * @param bool   $single    Whether one value is asked for.
	 * @return mixed
	 */
	public function get_reading_state( $value, int $object_id, string $meta_key, bool $single ) {
		if ( ! in_array( $meta_key, array( 'library_read', 'library_in_progress' ), true ) || 'library_book' !== get_post_type( $object_id ) ) {
			return $value;
		}

		$readings = self::get_readings( $object_id );
		$state    = 'library_read' === $meta_key ? (bool) self::get_ended( $readings ) : null !== self::get_in_progress( $readings );

		return $single ? $state : array( $state );
	}

	/**
	 * Readings of a book, from the first to the latest, as intervals: the ones ended, by their end, the unknown ones first,
	 * then the one in progress.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function get_readings( int $post_id ): array {
		return self::sort_readings( get_post_meta( $post_id, 'library_reading', false ) );
	}

	/**
	 * Read dates of a book, the ends of its readings ended, from the first to the latest: the unknown ones, empty, come first.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function get_read_dates( int $post_id ): array {
		return array_map( fn( string $reading ) => self::split_reading( $reading )[1], self::get_ended( self::get_readings( $post_id ) ) );
	}

	/**
	 * Values of a book as its forms edit them, by meta key, in the order of KEYS, then its edition languages: one value
	 * each, a boolean as a boolean, as a checkbox gives it back, the readings and the edition languages as lists.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public static function get_values( int $post_id ): array {
		$types  = wp_list_pluck( get_registered_meta_keys( 'post', 'library_book' ), 'type' );
		$values = array();

		foreach ( self::KEYS as $key ) {
			$value          = 'library_reading' === $key ? self::get_readings( $post_id ) : get_post_meta( $post_id, $key, true );
			$values[ $key ] = 'boolean' === ( $types[ $key ] ?? '' ) ? rest_sanitize_boolean( $value ) : $value;
		}

		$values['library_edition_languages'] = self::get_edition_languages( $post_id );

		return $values;
	}

	/**
	 * Edition languages of a book, the languages of its text: their term IDs in the languages taxonomy.
	 *
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public static function get_edition_languages( int $post_id ): array {
		return array_map( 'intval', get_post_meta( $post_id, 'library_edition_languages', false ) );
	}

	/**
	 * The start and the end of a reading, either empty when unknown, its end IN_PROGRESS while it is read.
	 *
	 * @param string $reading A reading, as an interval: 2026-10-20/2026-11-02.
	 * @return array{0:string,1:string}
	 */
	public static function split_reading( string $reading ): array {
		$parts = explode( '/', $reading, 2 );

		return array( $parts[0], $parts[1] ?? '' );
	}

	/**
	 * The readings ended, the one in progress left out.
	 *
	 * @param string[] $readings Readings.
	 * @return string[]
	 */
	private static function get_ended( array $readings ): array {
		return array_values( array_filter( $readings, fn( string $reading ) => self::IN_PROGRESS !== self::split_reading( $reading )[1] ) );
	}

	/**
	 * The reading in progress, null without.
	 *
	 * @param string[] $readings Readings.
	 * @return string|null
	 */
	private static function get_in_progress( array $readings ): ?string {
		return array_find( $readings, fn( string $reading ) => self::IN_PROGRESS === self::split_reading( $reading )[1] );
	}

	/**
	 * Readings from the first to the latest: the ones ended by their end, then by their start, each one of an unknown end a
	 * reading of its own, first, and one of a known end once, as one saved twice by an old import; then the one in progress,
	 * the latest started alone.
	 *
	 * @param string[] $readings Readings.
	 * @return string[]
	 */
	private static function sort_readings( array $readings ): array {
		$ended       = self::get_ended( $readings );
		$unknown     = array_filter( $ended, fn( string $reading ) => '' === self::split_reading( $reading )[1] );
		$ended       = array( ...$unknown, ...array_unique( array_diff( $ended, $unknown ) ) );
		$in_progress = array_diff( $readings, self::get_ended( $readings ) );

		usort( $ended, fn( string $a, string $b ) => array_reverse( self::split_reading( $a ) ) <=> array_reverse( self::split_reading( $b ) ) );

		return $in_progress ? array( ...$ended, max( $in_progress ) ) : $ended;
	}

	/**
	 * Readings of a book with the end of its latest one ended replaced by the given date, empty when it is unknown, its start
	 * kept: quick edit edits the read date of the latest reading. A book without reading ended gets one only when a date is
	 * given, which ends the one in progress.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $read_date Date of the latest reading, Y-m-d, or empty.
	 * @return string[]
	 */
	public static function replace_latest_read_date( int $post_id, string $read_date ): array {
		$readings    = self::get_readings( $post_id );
		$ended       = self::get_ended( $readings );
		$in_progress = array_diff( $readings, $ended );

		if ( ! $ended ) {
			return array( ...( '' !== $read_date ? array( "/$read_date" ) : array() ), ...$in_progress );
		}

		[ $start ] = self::split_reading( (string) array_pop( $ended ) );

		return array( ...$ended, "$start/$read_date", ...$in_progress );
	}

	/**
	 * Save the given meta values; an empty value deletes the meta, the readings replace the former ones.
	 *
	 * Keys that are not book meta are ignored, so a whole form submission can be passed.
	 * Values are saved in the order of KEYS, whatever the order of the form fields, then the edition languages, a list.
	 * Whether the book is read and being read, library_read and library_in_progress, are written as readings: see
	 * apply_reading_state().
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $values  Unslashed values keyed by meta key.
	 * @return void
	 */
	public static function save( int $post_id, array $values ): void {
		$before = self::get_readings( $post_id );

		foreach ( self::KEYS as $key ) {
			if ( ! array_key_exists( $key, $values ) || in_array( $key, array( 'library_reading', 'library_read', 'library_in_progress' ), true ) ) {
				continue;
			}

			$value = sanitize_meta( $key, $values[ $key ], 'post', 'library_book' );

			if ( '' === $value || false === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}

			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}

		if ( array_key_exists( 'library_edition_languages', $values ) ) {
			self::save_edition_languages( $post_id, (array) $values['library_edition_languages'] );
		}

		$readings = array_key_exists( 'library_reading', $values ) ? self::keep_times( $before, (array) $values['library_reading'] ) : $before;
		$readings = self::sort_readings( self::apply_reading_state( $before, $readings, $values ) );

		if ( $readings === $before ) {
			return;
		}

		delete_post_meta( $post_id, 'library_reading' );

		foreach ( $readings as $reading ) {
			add_post_meta( $post_id, 'library_reading', $reading );
		}
	}

	/**
	 * Replace the edition languages of a book: the languages of the library among the term IDs given, each once.
	 *
	 * @param int          $post_id  Post ID.
	 * @param array<mixed> $term_ids Term IDs.
	 * @return void
	 */
	private static function save_edition_languages( int $post_id, array $term_ids ): void {
		$term_ids = array_filter( array_unique( array_map( 'absint', $term_ids ) ), fn( int $term_id ) => is_array( term_exists( $term_id, 'library_language' ) ) );

		delete_post_meta( $post_id, 'library_edition_languages' );

		foreach ( $term_ids as $term_id ) {
			add_post_meta( $post_id, 'library_edition_languages', $term_id );
		}
	}

	/**
	 * Readings given, sanitized, with the times of the ones saved: a form gives the day of a start or an end, and one of a
	 * day saved with its time keeps it, once, while one of today takes the time now, which orders the readings of a day.
	 * An invalid start or end is an unknown one.
	 *
	 * @param string[]     $saved Readings saved.
	 * @param array<mixed> $given Readings given, their starts and ends by their days, or with their times.
	 * @return string[]
	 */
	private static function keep_times( array $saved, array $given ): array {
		$given = array_map( fn( $reading ): string => self::sanitize_reading( $reading ), $given );
		// The starts and the ends saved that a day given alone may stand for: not the ones given whole, with their time.
		$halves = array_map(
			fn( int $index ): array => array_diff( array_column( array_map( array( self::class, 'split_reading' ), $saved ), $index ), array_column( array_map( array( self::class, 'split_reading' ), $given ), $index ) ),
			array( 0, 1 )
		);
		$today  = current_time( 'Y-m-d' );
		$now    = current_time( self::READING_TIME_FORMAT );

		return array_map(
			function ( string $reading ) use ( &$halves, $today, $now ): string {
				$parts = self::split_reading( $reading );

				foreach ( $parts as $index => $part ) {
					if ( 10 !== strlen( $part ) ) {
						continue;
					}

					$found = array_find_key( $halves[ $index ], fn( string $saved ) => str_starts_with( $saved, $part ) );

					if ( null !== $found ) {
						$parts[ $index ] = $halves[ $index ][ $found ];
						unset( $halves[ $index ][ $found ] );
					} elseif ( $today === $part ) {
						$parts[ $index ] = $now;
					}
				}

				return self::sanitize_reading( implode( '/', $parts ) );
			},
			$given
		);
	}

	/**
	 * Readings as whether the book is read and being read say, when given: marked as unread, a book loses its readings
	 * ended; marked as read without one, it is read now, from the start of the one in progress; read already and its
	 * readings all removed, it has one of an unknown date, unless it is marked as unread. Marked as being read, it starts a
	 * reading now, unless one is in progress; marked as not, the one in progress goes. A reading added that ends after the
	 * one in progress started, such as one ended today, ends it, which gives it its start when it has none: an older one,
	 * added to the ones before, leaves it.
	 *
	 * @param string[]            $before   Readings saved.
	 * @param string[]            $readings Readings given, or saved.
	 * @param array<string,mixed> $values   Values given, library_read and library_in_progress among them.
	 * @return string[]
	 */
	private static function apply_reading_state( array $before, array $readings, array $values ): array {
		$now         = current_time( self::READING_TIME_FORMAT );
		$ended       = self::get_ended( $readings );
		$in_progress = self::get_in_progress( $readings );
		$ends        = fn( array $readings ): array => array_map( fn( string $reading ) => self::split_reading( $reading )[1], self::get_ended( $readings ) );
		$started     = null !== $in_progress ? self::split_reading( $in_progress )[0] : '';
		$ending      = array_filter( self::get_added( $ends( $before ), $ends( $readings ) ), fn( string $end ) => '' !== $end && substr( $end, 0, 10 ) >= substr( $started, 0, 10 ) );

		if ( null !== $in_progress && $ending ) {
			$end   = max( $ending );
			$index = array_find_key( $ended, fn( string $reading ) => array( '', $end ) === self::split_reading( $reading ) );

			if ( null !== $index ) {
				$ended[ $index ] = "$started/$end";
			}

			$in_progress = null;
		}

		$was_read = (bool) self::get_ended( $before );
		$is_read  = array_key_exists( 'library_read', $values ) ? rest_sanitize_boolean( $values['library_read'] ) : $was_read || $ended;

		if ( ! $is_read ) {
			$ended = array();
		} elseif ( ! $ended && $was_read ) {
			$ended = array( '/' );
		} elseif ( ! $ended ) {
			$ended       = array( "$started/$now" );
			$in_progress = null;
		}

		if ( array_key_exists( 'library_in_progress', $values ) ) {
			$is_in_progress = rest_sanitize_boolean( $values['library_in_progress'] );

			if ( ! $is_in_progress ) {
				$in_progress = null;
			} elseif ( null === $in_progress ) {
				$in_progress = "$now/" . self::IN_PROGRESS;
			}
		}

		return null !== $in_progress ? array( ...$ended, $in_progress ) : $ended;
	}

	/**
	 * Read dates one list has and the other has not: a date more, known or not, such as a second unknown one.
	 *
	 * @param string[] $before Read dates before.
	 * @param string[] $after  Read dates after.
	 * @return string[]
	 */
	private static function get_added( array $before, array $after ): array {
		$counts = array_count_values( $before );
		$added  = array();

		foreach ( $after as $read_date ) {
			if ( ( $counts[ $read_date ] ?? 0 ) > 0 ) {
				--$counts[ $read_date ];
				continue;
			}

			$added[] = $read_date;
		}

		return $added;
	}

	/**
	 * Keep an ISBN with its hyphens where its parts end, such as 978-2-07-036024-6 typed 9782070360246, and another
	 * EAN-13 as its 13 digits; a code ISBN cannot format, such as one with a wrong check digit, as typed.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_isbn( $value ): string {
		$value = sanitize_text_field( (string) $value );
		$isbn  = ISBN::format( $value );

		return '' !== $isbn ? $isbn : $value;
	}

	/**
	 * Refuse an ISBN typed with a mistake, which its check digit tells, such as two digits swapped: a new one, or one
	 * that changes, so that a book saved with a wrong ISBN before can still be edited. The forms of a book, in the
	 * reading app and the admin, check it before anything is written.
	 *
	 * @param int    $post_id Book ID, 0 for a book to add.
	 * @param string $isbn    ISBN sent.
	 * @return WP_Error|null
	 */
	public static function check_isbn( int $post_id, string $isbn ): ?WP_Error {
		if ( ! ISBN::has_wrong_check_digit( $isbn ) || get_post_meta( $post_id, 'library_isbn', true ) === $isbn ) {
			return null;
		}

		/* translators: %s: ISBN typed, e.g. 978-2-07-036042-6. */
		$message = sprintf( __( 'The last digit of the ISBN %s, its check digit, does not match the others: check them.', 'library' ), $isbn );

		return new WP_Error( 'library_isbn_check_digit', $message, array( 'status' => 400 ) );
	}

	/**
	 * Keep a valid Y-m-d date, anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_date( $value ): string {
		$value = (string) $value;

		return self::is_date( $value, 'Y-m-d' ) ? $value : '';
	}

	/**
	 * Keep a valid reading, an interval from its start to its end, as READING_PATTERN: a start or an end invalid is an
	 * unknown one, a start after the end, mistyped, too, and anything else a reading of unknown dates.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_reading( $value ): string {
		[ $start, $end ] = self::split_reading( (string) $value );
		$start           = self::sanitize_reading_date( $start );
		$end             = self::IN_PROGRESS === $end ? $end : self::sanitize_reading_date( $end );

		if ( '' !== $start && '' !== $end && self::IN_PROGRESS !== $end && substr( $start, 0, 10 ) > substr( $end, 0, 10 ) ) {
			$start = '';
		}

		return "$start/$end";
	}

	/**
	 * Keep a valid start or end of a reading, its day alone or with its time, as READING_TIME_FORMAT; anything else becomes
	 * empty, an unknown one.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function sanitize_reading_date( string $value ): string {
		$format = 10 === strlen( $value ) ? 'Y-m-d' : self::READING_TIME_FORMAT;

		return self::is_date( $value, $format ) ? $value : '';
	}

	/**
	 * Keep a valid year, year and month, or Y-m-d date, such as 1929, 1929-11 or 1929-11-08; anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_partial_date( $value ): string {
		$value   = (string) $value;
		$formats = array(
			4  => 'Y',
			7  => 'Y-m',
			10 => 'Y-m-d',
		);
		$format  = $formats[ strlen( $value ) ] ?? null;

		return $format && self::is_date( $value, $format ) ? $value : '';
	}

	/**
	 * Whether a value is a date of a format, written as the format writes it: 2026-02-30 is none, nor 2026-2-3.
	 *
	 * @param string $value  Value.
	 * @param string $format Format of DateTimeImmutable, such as Y-m-d.
	 * @return bool
	 */
	private static function is_date( string $value, string $format ): bool {
		$date = DateTimeImmutable::createFromFormat( "!$format", $value );

		return $date && $date->format( $format ) === $value;
	}

	/**
	 * Keep a number of pages from MIN_PAGES, 1, anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return int|string
	 */
	public static function sanitize_pages( $value ): int|string {
		if ( ! is_numeric( $value ) || $value < self::MIN_PAGES ) {
			return '';
		}

		return (int) $value;
	}

	/**
	 * Keep a rating within RATING_LIMITS, from 1 to 10, rounded to its step, half points; anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return float|string
	 */
	public static function sanitize_rating( $value ): float|string {
		$limits = self::RATING_LIMITS;

		if ( ! is_numeric( $value ) || $value < $limits['minimum'] || $value > $limits['maximum'] ) {
			return '';
		}

		return round( (float) $value / $limits['multipleOf'] ) * $limits['multipleOf'];
	}
}
