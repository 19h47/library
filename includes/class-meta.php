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
	 * The prefix of the keys of the book meta, which the database needs among the meta of every plugin.
	 *
	 * @var string
	 */
	public const string PREFIX = 'library_';

	/**
	 * Text meta keys.
	 *
	 * @var string[]
	 */
	public const array TEXT_KEYS = array( 'library_subtitle', 'library_isbn', 'library_volume_number', 'library_collection_number' );

	/**
	 * Date meta keys, often known by their year only: Y, Y-m or Y-m-d. The work, then the edition of its ISBN, as the
	 * catalogue of the BnF records them; the copy in hand, a printing of that edition, is the one of each reader: see
	 * Shelf.
	 *
	 * @var string[]
	 */
	private const array DATE_KEYS = array( 'library_published_date', 'library_edition_date' );

	/**
	 * Pattern of a date often known by its year only: Y, Y-m or Y-m-d.
	 *
	 * @var string
	 */
	public const string DATE_PATTERN = '^\d{4}(-\d{2}(-\d{2})?)?$';

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
	 * Book meta edited in quick edit: those of the record, which a reader of the book may not edit, are edited elsewhere.
	 *
	 * @var string[]
	 */
	public const array QUICK_EDIT_KEYS = array( 'library_isbn', 'library_pages' );

	/**
	 * All book meta keys, those of the record the readers share.
	 *
	 * @var string[]
	 */
	public const array KEYS = array( ...self::TEXT_KEYS, ...self::DATE_KEYS, 'library_pages', 'library_collective' );

	/**
	 * Book meta keys its forms edit: KEYS, then its edition languages, a list.
	 *
	 * @var string[]
	 */
	public const array FORM_KEYS = array( ...self::KEYS, 'library_edition_languages' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
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
			'library_volume_number'     => __( 'Volume Number', 'library' ),
			'library_collection_number' => __( 'Collection Number', 'library' ),
			'library_translators'       => __( 'Translators', 'library' ),
			'library_edition_date'      => __( 'Edition date', 'library' ),
			'library_published_date'    => __( 'First published', 'library' ),
			'library_pages'             => __( 'Pages', 'library' ),
			'library_collective'        => __( 'Collective work', 'library' ),
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
			'library_collection_number' => __( 'Shown after the collection, such as “Pocket Science-fiction no. 5667”.', 'library' ),
			'library_published_date'    => __( 'When the work first came out, whatever the edition.', 'library' ),
			'library_edition_date'      => __( 'When this edition, the one of its ISBN, first came out, whatever the printing of the copy.', 'library' ),
			'library_collective'        => __( 'A magazine or an anthology, whose authors each give a part: it does not count among the books of any of them.', 'library' ),
			'library_edition_languages' => __( 'The languages of its text, several for a multilingual edition: it is read in its original language when they all are original languages of the book.', 'library' ),
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
					'revisions_enabled' => true,
					'show_in_rest'      => null !== self::get_pattern( $key ) ? array( 'schema' => array( 'pattern' => self::get_pattern( $key ) ) ) : true,
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
					'description'       => $descriptions[ $key ],
					'single'            => true,
					'revisions_enabled' => true,
					'show_in_rest'      => array(
						'schema' => array( 'pattern' => self::DATE_PATTERN ),
					),
					'sanitize_callback' => array( self::class, 'sanitize_partial_date' ),
				)
			);
		}

		register_post_meta(
			'library_book',
			'library_pages',
			array(
				'type'              => 'integer',
				'label'             => $labels['library_pages'],
				'single'            => true,
				'revisions_enabled' => true,
				'show_in_rest'      => array(
					'schema' => array( 'minimum' => self::MIN_PAGES ),
				),
				'sanitize_callback' => array( self::class, 'sanitize_pages' ),
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
				'revisions_enabled' => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
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
				'revisions_enabled' => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
			)
		);

		// The REST API gives each name of a book with its role, in its names, rather than these term IDs.
		foreach ( self::ROLE_KEYS as $key ) {
			register_post_meta(
				'library_book',
				$key,
				array(
					'type'              => 'integer',
					'label'             => $labels[ $key ],
					'single'            => false,
					'revisions_enabled' => true,
					'show_in_rest'      => false,
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
	 * The name of a book meta in the REST API of the plugin and in the forms of the app: its key without the prefix the
	 * database needs among the meta of every plugin, isbn for library_isbn.
	 *
	 * @param string $key Meta key.
	 * @return string
	 */
	public static function get_name( string $key ): string {
		return substr( $key, strlen( self::PREFIX ) );
	}

	/**
	 * The meta of a book by their names, as the REST API of the plugin gives them: isbn for library_isbn.
	 *
	 * @param array<string,mixed> $values Values, by meta key.
	 * @return array<string,mixed>
	 */
	public static function to_names( array $values ): array {
		return array_combine( array_map( array( self::class, 'get_name' ), array_keys( $values ) ), $values );
	}

	/**
	 * The meta of a book by their keys, from their names as the REST API of the plugin takes them: those of a book only,
	 * its readings and its edition languages among them; another name is left out.
	 *
	 * @param array<string,mixed> $values Values, by name.
	 * @return array<string,mixed>
	 */
	public static function from_names( array $values ): array {
		$keys = self::FORM_KEYS;
		$keys = array_combine( array_map( array( self::class, 'get_name' ), $keys ), $keys );

		return array_combine(
			array_map( fn( string $name ) => $keys[ $name ], array_keys( array_intersect_key( $values, $keys ) ) ),
			array_intersect_key( $values, $keys )
		);
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

		for ( $rating = Readings::RATING_LIMITS['minimum']; $rating <= Readings::RATING_LIMITS['maximum']; $rating += Readings::RATING_LIMITS['multipleOf'] ) {
			$ratings[ (string) $rating ] = Books::format_rating_out_of_ten( $rating );
		}

		return $ratings;
	}

	/**
	 * The choice of how a reading ended, in a row of the readings of a form: its rating, none, or abandoned, rather than a
	 * rating, as a reading abandoned has none.
	 *
	 * @param Reading|null        $reading    The reading, none for one to add.
	 * @param array<string,mixed> $attributes Attributes of the select, such as its name.
	 * @return string HTML.
	 */
	public static function get_ending_control( ?Reading $reading, array $attributes ): string {
		$choices = self::get_rating_choices() + array( Readings::ABANDONED => __( 'Abandoned', 'library' ) );
		$value   = match ( true ) {
			null === $reading => null,
			$reading->abandoned => Readings::ABANDONED,
			null !== $reading->rating => (string) $reading->rating,
			default => '',
		};

		return Markup::set_attributes( Markup::get_select( $choices, $value ), $attributes );
	}

	/**
	 * The control of a book meta, as its registration says: a select of its choices for the rating, a checkbox for a
	 * boolean, a number with its minimum, maximum and step, or a text with its pattern, anchored in the schema
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
			default => 'text',
		};

		return Markup::set_attributes(
			'<input>',
			$attributes + array(
				'type'    => $type,
				'name'    => $key,
				'pattern' => 'text' === $type && null !== self::get_pattern( $key ) ? trim( self::get_pattern( $key ), '^$' ) : null,
				'min'     => $schema['minimum'] ?? null,
				'max'     => $schema['maximum'] ?? null,
				'step'    => $schema['multipleOf'] ?? null,
			)
		);
	}

	/**
	 * Pattern of the text of a book meta, as its schema and its control state it, none without: an ISBN, or a date
	 * often known by its year only.
	 *
	 * @param string $key Meta key.
	 * @return string|null
	 */
	public static function get_pattern( string $key ): ?string {
		return match ( true ) {
			'library_isbn' === $key => self::ISBN_PATTERN,
			in_array( $key, array( ...self::DATE_KEYS, 'library_printed_date' ), true ) => self::DATE_PATTERN,
			default => null,
		};
	}

	/**
	 * Values of a book as its forms edit them, by meta key, in the order of KEYS, then its edition languages: one value
	 * each, a boolean as a boolean, as a checkbox gives it back, the edition languages as a list.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public static function get_values( int $post_id ): array {
		$types  = wp_list_pluck( get_registered_meta_keys( 'post', 'library_book' ), 'type' );
		$values = array();

		foreach ( self::KEYS as $key ) {
			$value          = get_post_meta( $post_id, $key, true );
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
	 * Save the given meta values of the record; an empty value deletes the meta.
	 *
	 * Keys that are not book meta are ignored, so a whole form submission can be passed.
	 * Values are saved in the order of KEYS, whatever the order of the form fields, then the edition languages, a list.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $values  Unslashed values keyed by meta key.
	 * @return void
	 */
	public static function save( int $post_id, array $values ): void {
		foreach ( self::KEYS as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
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
	 * Refuse an ISBN typed with a mistake, which its check digit tells, such as two digits swapped, or one another book of
	 * the catalogue has, which the readers share: a reader puts that book on their shelves rather than adding it twice.
	 * A new ISBN, or one that changes, so that a book saved with a wrong ISBN, or twice, before can still be edited. The
	 * forms of a book, in the reading app and the admin, check it before anything is written.
	 *
	 * @param int    $post_id Book ID, 0 for a book to add.
	 * @param string $isbn    ISBN sent.
	 * @return WP_Error|null The error, with the book that has the ISBN, `book`, for one taken.
	 */
	public static function check_isbn( int $post_id, string $isbn ): ?WP_Error {
		if ( '' === $isbn || get_post_meta( $post_id, 'library_isbn', true ) === $isbn ) {
			return null;
		}

		if ( ISBN::has_wrong_check_digit( $isbn ) ) {
			/* translators: %s: ISBN typed, e.g. 978-2-07-036042-6. */
			$message = sprintf( __( 'The last digit of the ISBN %s, its check digit, does not match the others: check them.', 'library' ), $isbn );

			return new WP_Error( 'library_isbn_check_digit', $message, array( 'status' => 400 ) );
		}

		$book_id = self::find_isbn( $isbn, $post_id );

		if ( ! $book_id ) {
			return null;
		}

		/* translators: 1: ISBN typed, e.g. 978-2-07-036042-6, 2: title of the book that has it. */
		$message = sprintf( __( 'The ISBN %1$s already belongs to “%2$s” in the catalogue. Add that book to your shelves instead.', 'library' ), $isbn, get_the_title( $book_id ) );

		return new WP_Error(
			'library_isbn_taken',
			$message,
			array(
				'status' => 409,
				'book'   => $book_id,
			)
		);
	}

	/**
	 * The book of the catalogue, published, that has an ISBN, by its digits, whatever its hyphens, but the one given; 0
	 * for none. A draft is no book of the catalogue: the readers do not find it, nor shelve it.
	 *
	 * @param string $isbn    ISBN.
	 * @param int    $except  Book ID left out, 0 for none.
	 * @return int Book ID.
	 */
	private static function find_isbn( string $isbn, int $except ): int {
		global $wpdb;

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- A book saved, once.
			$wpdb->prepare(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} AS pm
				INNER JOIN {$wpdb->posts} AS p ON p.ID = pm.post_id AND p.post_type = 'library_book' AND p.post_status = 'publish'
				WHERE pm.meta_key = 'library_isbn' AND REPLACE( REPLACE( pm.meta_value, '-', '' ), ' ', '' ) = %s AND pm.post_id <> %d
				LIMIT 1",
				ISBN::get_digits( $isbn ),
				$except
			)
		);
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
	public static function is_date( string $value, string $format ): bool {
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
}
