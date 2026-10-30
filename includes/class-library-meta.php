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

/**
 * Registers book meta so every write (form, quick edit, REST) goes through
 * the same sanitize callbacks.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Meta {

	/**
	 * Text meta keys.
	 *
	 * @var string[]
	 */
	public const TEXT_KEYS = array( 'library_subtitle', 'library_series', 'library_isbn', 'library_issn', 'library_volume_number', 'library_collection_number' );

	/**
	 * Date meta keys, stored as Y-m-d; a book has one read date per reading.
	 *
	 * @var string[]
	 */
	private const DATE_KEYS = array( 'library_edition_date', 'library_read_date' );

	/**
	 * Pattern of an ISBN-10 or ISBN-13, with or without hyphens or spaces, or of another EAN-13, the barcode of a book
	 * without ISBN, as its 13 digits.
	 *
	 * Browsers compile the pattern of an input with the v flag, where a hyphen in a class is escaped: an invalid pattern checks nothing.
	 *
	 * @var string
	 */
	private const ISBN_PATTERN = '^(?:(?=.{17}$)97[89][ \\-](?:[0-9]+[ \\-]){2}[0-9]+[ \\-][0-9]|[0-9]{13}|(?=.{13}$)(?:[0-9]+[ \\-]){2}[0-9]+[ \\-][0-9Xx]|[0-9]{9}[0-9Xx])$';

	/**
	 * Pattern of an ISSN, such as 1234-5678.
	 *
	 * @var string
	 */
	private const ISSN_PATTERN = '^\\S{4}-\\S{4}$';

	/**
	 * Meta of the names of a book with another role than author, by role, such as its colorists: the term IDs of these
	 * names in the people taxonomy, as catalogues give each name its function. A name of a book without role is one of
	 * its authors.
	 *
	 * @var array<string,string>
	 */
	public const ROLE_KEYS = array(
		'translator' => 'library_translators',
		'colorist'   => 'library_colorists',
		'inker'      => 'library_inkers',
		'letterer'   => 'library_letterers',
		'director'   => 'library_directors',
	);

	/**
	 * Book meta edited in quick edit.
	 *
	 * @var string[]
	 */
	public const QUICK_EDIT_KEYS = array( 'library_isbn', 'library_pages', 'library_read_date', 'library_rating' );

	/**
	 * All book meta keys.
	 *
	 * The read state comes after the read dates, so unchecking "Read" in the form
	 * clears the dates instead of the dates checking "Read" again.
	 *
	 * @var string[]
	 */
	public const KEYS = array( ...self::TEXT_KEYS, ...self::DATE_KEYS, 'library_published_date', 'library_pages', 'library_rating', 'library_collective', 'library_read' );

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'added_post_meta', array( $this, 'sync_reading_state' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'sync_reading_state' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'sync_reading_state' ), 10, 3 );
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
			'library_series'            => __( 'Series', 'library' ),
			'library_isbn'              => __( 'ISBN', 'library' ),
			'library_issn'              => __( 'ISSN', 'library' ),
			'library_volume_number'     => __( 'Volume Number', 'library' ),
			'library_collection_number' => __( 'Collection Number', 'library' ),
			'library_translators'       => __( 'Translators', 'library' ),
			'library_edition_date'      => __( 'Edition date', 'library' ),
			'library_read_date'         => __( 'Read dates', 'library' ),
			'library_published_date'    => __( 'First published', 'library' ),
			'library_pages'             => __( 'Pages', 'library' ),
			'library_rating'            => __( 'Rating', 'library' ),
			'library_collective'        => __( 'Collective work', 'library' ),
			'library_read'              => __( 'Read', 'library' ),
			'library_colorists'         => __( 'Colorists', 'library' ),
			'library_inkers'            => __( 'Inkers', 'library' ),
			'library_letterers'         => __( 'Letterers', 'library' ),
			'library_directors'         => __( 'Publishing directors', 'library' ),
		);
		$descriptions = array(
			'library_subtitle'          => __( 'Shown after the title, such as “The Knife: Meditations After an Attempted Murder”.', 'library' ),
			'library_isbn'              => __( '10 or 13 digits, with or without hyphens, such as 9782070360246, which becomes 978-2-07-036024-6; for a book without ISBN, the 13 digits of its barcode.', 'library' ),
			'library_issn'              => __( 'Two groups of four characters, such as 0395-2649.', 'library' ),
			'library_series'            => __( 'From the cycle, if any, to the series, separated with commas, such as “Star Wars, Young Jedi Knights”; the volume number goes with the series.', 'library' ),
			'library_collection_number' => __( 'Shown after the collection, such as “Pocket Science-fiction no. 5667”.', 'library' ),
			'library_published_date'    => __( 'When the work first came out, whatever the edition: a year, a year and month, or a date, such as 1929, 1929-11 or 1929-11-08.', 'library' ),
			'library_rating'            => __( 'Rating from 1 to 10.', 'library' ),
			'library_collective'        => __( 'A magazine or an anthology, whose authors each give a part: it does not count among the books of any of them.', 'library' ),
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
					'single'            => 'library_read_date' !== $key,
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
					'schema' => array( 'minimum' => 1 ),
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
					'schema' => array(
						'minimum'    => 1,
						'maximum'    => 10,
						'multipleOf' => 0.5,
					),
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

		register_post_meta(
			'library_book',
			'library_read',
			array(
				'type'              => 'boolean',
				'label'             => $labels['library_read'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
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
	 * Roles a form offers for a name of a book, by value, with their label: author, then the other roles.
	 *
	 * @return array<string,string>
	 */
	public static function get_role_choices(): array {
		return array(
			'author'     => _x( 'Author', 'role', 'library' ),
			'translator' => _x( 'Translator', 'role', 'library' ),
			'colorist'   => _x( 'Colorist', 'role', 'library' ),
			'inker'      => _x( 'Inker', 'role', 'library' ),
			'letterer'   => _x( 'Letterer', 'role', 'library' ),
			'director'   => _x( 'Publishing director', 'role', 'library' ),
		);
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

		foreach ( self::ROLE_KEYS as $role => $key ) {
			$role_ids = array_intersect( array_keys( $roles, $role, true ), $term_ids );

			delete_post_meta( $post_id, $key );

			foreach ( $role_ids as $term_id ) {
				add_post_meta( $post_id, $key, $term_id );
			}
		}
	}

	/**
	 * SQL condition: whether a name of a book has another role than author, such as colorist, from the columns of their IDs.
	 *
	 * @param string $book_id_column Column of the book ID, such as p.ID.
	 * @param string $term_id_column Column of the term ID of the name, such as tt.term_id.
	 * @return string
	 */
	public static function build_with_role_condition( string $book_id_column, string $term_id_column ): string {
		global $wpdb;

		$keys = "'" . implode( "', '", self::ROLE_KEYS ) . "'";

		return "EXISTS ( SELECT 1 FROM {$wpdb->postmeta} AS role WHERE role.post_id = {$book_id_column} AND role.meta_key IN ( {$keys} ) AND role.meta_value = {$term_id_column} )";
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
	 * Ratings a form offers, by value, with their label: none, then the ones the meta is registered with, from 1 to 10 by
	 * half points.
	 *
	 * @return array<string,string>
	 */
	public static function get_rating_choices(): array {
		$schema  = get_registered_meta_keys( 'post', 'library_book' )['library_rating']['show_in_rest']['schema'];
		$ratings = array( '' => __( 'No rating', 'library' ) );

		for ( $rating = $schema['minimum']; $rating <= $schema['maximum']; $rating += $schema['multipleOf'] ) {
			/* translators: %s: rating, e.g. 7.5. */
			$ratings[ (string) $rating ] = sprintf( __( '%s/10', 'library' ), Library_Books::format_rating( $rating ) );
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
	 * @param string $key        Meta key.
	 * @param array  $attributes More attributes, such as its ID or value, or ones that replace those of the registration;
	 *                           true gives an attribute without value, null leaves it out.
	 * @return string HTML.
	 */
	public static function get_control( string $key, array $attributes = array() ): string {
		if ( 'library_rating' === $key ) {
			$select = Library::get_select( self::get_rating_choices(), $attributes['value'] ?? null );

			unset( $attributes['value'] );

			return Library::set_attributes( $select, $attributes + array( 'name' => $key ) );
		}

		$meta   = get_registered_meta_keys( 'post', 'library_book' )[ $key ];
		$schema = $meta['show_in_rest']['schema'] ?? array();
		$type   = match ( true ) {
			'boolean' === $meta['type'] => 'checkbox',
			in_array( $meta['type'], array( 'integer', 'number' ), true ) => 'number',
			'date' === ( $schema['format'] ?? '' ) => 'date',
			default => 'text',
		};

		return Library::set_attributes(
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
	 * Keep the read state and the read dates consistent, whatever wrote them:
	 * a read date marks the book as read, marking it as unread clears the dates.
	 *
	 * Imports are left as they are: their data is already consistent, and the importer
	 * adds each meta after the ones this rule would write, which would duplicate them.
	 *
	 * @param int|int[] $meta_ids Meta ID(s).
	 * @param int       $post_id  Post ID.
	 * @param string    $meta_key Meta key.
	 * @return void
	 */
	public function sync_reading_state( $meta_ids, int $post_id, string $meta_key ): void {
		if ( ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) || 'library_book' !== get_post_type( $post_id ) ) {
			return;
		}

		$read       = (bool) get_post_meta( $post_id, 'library_read', true );
		$read_dates = self::get_read_dates( $post_id );

		if ( 'library_read_date' === $meta_key && $read_dates && ! $read ) {
			update_post_meta( $post_id, 'library_read', true );
		}

		if ( 'library_read' === $meta_key && ! $read && $read_dates ) {
			delete_post_meta( $post_id, 'library_read_date' );
		}
	}

	/**
	 * Read dates of a book, one per reading, from the first to the latest.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function get_read_dates( int $post_id ): array {
		$read_dates = array_unique( array_filter( get_post_meta( $post_id, 'library_read_date', false ) ) );

		sort( $read_dates );

		return $read_dates;
	}

	/**
	 * Read dates of a book with the latest one replaced by the given date, or removed when it is empty:
	 * quick edit edits the latest reading.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $read_date Date of the latest reading, Y-m-d, or empty.
	 * @return string[]
	 */
	public static function replace_latest_read_date( int $post_id, string $read_date ): array {
		$read_dates = self::get_read_dates( $post_id );

		array_pop( $read_dates );

		return array( ...$read_dates, $read_date );
	}

	/**
	 * Save the given meta values; an empty value deletes the meta, the read dates replace the former ones.
	 *
	 * Keys that are not book meta are ignored, so a whole form submission can be passed.
	 * Values are saved in the order of KEYS, whatever the order of the form fields.
	 * A book marked as read without a read date gets today.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $values  Unslashed values keyed by meta key.
	 * @return void
	 */
	public static function save( int $post_id, array $values ): void {
		$was_read = (bool) get_post_meta( $post_id, 'library_read', true );

		foreach ( self::KEYS as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}

			if ( 'library_read_date' === $key ) {
				self::save_read_dates( $post_id, (array) $values[ $key ] );
				continue;
			}

			$value = sanitize_meta( $key, $values[ $key ], 'post', 'library_book' );

			if ( '' === $value || false === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}

			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}

		$marked_read = ! $was_read && (bool) get_post_meta( $post_id, 'library_read', true );

		if ( $marked_read && ! self::get_read_dates( $post_id ) ) {
			add_post_meta( $post_id, 'library_read_date', current_time( 'Y-m-d' ) );
		}
	}

	/**
	 * Replace the read dates of a book, one row per reading; empty and invalid dates are dropped, a date is kept once.
	 *
	 * @param int   $post_id    Post ID.
	 * @param array $read_dates Read dates, Y-m-d.
	 * @return void
	 */
	private static function save_read_dates( int $post_id, array $read_dates ): void {
		$read_dates = array_map( fn( $read_date ) => sanitize_meta( 'library_read_date', $read_date, 'post', 'library_book' ), $read_dates );
		$read_dates = array_unique( array_filter( $read_dates ) );

		sort( $read_dates );

		if ( self::get_read_dates( $post_id ) === $read_dates ) {
			return;
		}

		delete_post_meta( $post_id, 'library_read_date' );

		foreach ( $read_dates as $read_date ) {
			add_post_meta( $post_id, 'library_read_date', $read_date );
		}
	}

	/**
	 * Keep an ISBN with its hyphens where its parts end, such as 978-2-07-036024-6 typed 9782070360246, and another
	 * EAN-13 as its 13 digits; a code Library_ISBN cannot format, such as one with a wrong check digit, as typed.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_isbn( $value ): string {
		$value = sanitize_text_field( (string) $value );
		$isbn  = Library_ISBN::format( $value );

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
		if ( ! Library_ISBN::has_wrong_check_digit( $isbn ) || get_post_meta( $post_id, 'library_isbn', true ) === $isbn ) {
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
		$date  = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );

		return $date && $date->format( 'Y-m-d' ) === $value ? $value : '';
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
		$date    = $format ? DateTimeImmutable::createFromFormat( "!$format", $value ) : false;

		return $date && $date->format( $format ) === $value ? $value : '';
	}

	/**
	 * Keep a number of pages from 1, anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return int|string
	 */
	public static function sanitize_pages( $value ): int|string {
		if ( ! is_numeric( $value ) || $value < 1 ) {
			return '';
		}

		return (int) $value;
	}

	/**
	 * Keep a rating from 1 to 10 by half points, anything else becomes empty.
	 *
	 * @param mixed $value Raw value.
	 * @return float|string
	 */
	public static function sanitize_rating( $value ): float|string {
		if ( ! is_numeric( $value ) || $value < 1 || $value > 10 ) {
			return '';
		}

		return round( (float) $value * 2 ) / 2;
	}
}
