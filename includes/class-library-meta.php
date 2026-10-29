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
	public const TEXT_KEYS = array( 'subtitle', 'series', 'isbn', 'issn', 'volume_number', 'translators' );

	/**
	 * Date meta keys, stored as Y-m-d; a book has one read date per reading.
	 *
	 * @var string[]
	 */
	private const DATE_KEYS = array( 'edition_date', 'read_date' );

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
	 * Book meta edited in quick edit.
	 *
	 * @var string[]
	 */
	public const QUICK_EDIT_KEYS = array( 'isbn', 'pages', 'read_date', 'rating' );

	/**
	 * All book meta keys.
	 *
	 * The read state comes after the read dates, so unchecking "Read" in the form
	 * clears the dates instead of the dates checking "Read" again.
	 *
	 * @var string[]
	 */
	public const KEYS = array( ...self::TEXT_KEYS, ...self::DATE_KEYS, 'published_date', 'pages', 'rating', 'read' );

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
			'subtitle'       => __( 'Subtitle', 'library' ),
			'series'         => __( 'Series', 'library' ),
			'isbn'           => __( 'ISBN', 'library' ),
			'issn'           => __( 'ISSN', 'library' ),
			'volume_number'  => __( 'Volume Number', 'library' ),
			'translators'    => __( 'Translators', 'library' ),
			'edition_date'   => __( 'Edition date', 'library' ),
			'read_date'      => __( 'Read dates', 'library' ),
			'published_date' => __( 'First published', 'library' ),
			'pages'          => __( 'Pages', 'library' ),
			'rating'         => __( 'Rating', 'library' ),
			'read'           => __( 'Read', 'library' ),
		);
		$descriptions = array(
			'subtitle'       => __( 'Shown after the title, such as “The Knife: Meditations After an Attempted Murder”.', 'library' ),
			'isbn'           => __( '10 or 13 digits, with or without hyphens, such as 9782070360246, which becomes 978-2-07-036024-6; for a book without ISBN, the 13 digits of its barcode.', 'library' ),
			'issn'           => __( 'Two groups of four characters, such as 0395-2649.', 'library' ),
			'series'         => __( 'Separate series with commas', 'library' ),
			'translators'    => __( 'Separate translators with commas', 'library' ),
			'published_date' => __( 'When the work first came out, whatever the edition: a year, a year and month, or a date, such as 1929, 1929-11 or 1929-11-08.', 'library' ),
			'rating'         => __( 'Rating from 1 to 10.', 'library' ),
		);
		$patterns     = array(
			'isbn' => self::ISBN_PATTERN,
			'issn' => self::ISSN_PATTERN,
		);
		$sanitizers   = array( 'isbn' => array( self::class, 'sanitize_isbn' ) );

		foreach ( self::TEXT_KEYS as $key ) {
			register_post_meta(
				'book',
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
				'book',
				$key,
				array(
					'type'              => 'string',
					'label'             => $labels[ $key ],
					'single'            => 'read_date' !== $key,
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
			'book',
			'published_date',
			array(
				'type'              => 'string',
				'label'             => $labels['published_date'],
				'description'       => $descriptions['published_date'],
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array( 'pattern' => '^\d{4}(-\d{2}(-\d{2})?)?$' ),
				),
				'sanitize_callback' => array( self::class, 'sanitize_partial_date' ),
			)
		);

		register_post_meta(
			'book',
			'pages',
			array(
				'type'              => 'integer',
				'label'             => $labels['pages'],
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => array( 'minimum' => 1 ),
				),
				'sanitize_callback' => array( self::class, 'sanitize_pages' ),
			)
		);

		register_post_meta(
			'book',
			'rating',
			array(
				'type'              => 'number',
				'label'             => $labels['rating'],
				'description'       => $descriptions['rating'],
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

		register_post_meta(
			'book',
			'read',
			array(
				'type'              => 'boolean',
				'label'             => $labels['read'],
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
	}

	/**
	 * Label of a book meta, as registered.
	 *
	 * @param string $key Meta key.
	 * @return string
	 */
	public static function label( string $key ): string {
		return get_registered_meta_keys( 'post', 'book' )[ $key ]['label'];
	}

	/**
	 * Description of a book meta, as registered, or empty.
	 *
	 * @param string $key Meta key.
	 * @return string
	 */
	public static function description( string $key ): string {
		return get_registered_meta_keys( 'post', 'book' )[ $key ]['description'];
	}

	/**
	 * Ratings a form offers, by value, with their label: none, then the ones the meta is registered with, from 1 to 10 by
	 * half points.
	 *
	 * @return array<string,string>
	 */
	public static function get_rating_choices(): array {
		$schema  = get_registered_meta_keys( 'post', 'book' )['rating']['show_in_rest']['schema'];
		$ratings = array( '' => __( 'No rating', 'library' ) );

		for ( $rating = $schema['minimum']; $rating <= $schema['maximum']; $rating += $schema['multipleOf'] ) {
			/* translators: %s: rating, e.g. 7.5. */
			$ratings[ (string) $rating ] = sprintf( __( '%s/10', 'library' ), Library_Books::rating_text( $rating ) );
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
	public static function control( string $key, array $attributes = array() ): string {
		if ( 'rating' === $key ) {
			$select = Library::select( self::get_rating_choices(), $attributes['value'] ?? null );

			unset( $attributes['value'] );

			return Library::control( $select, $attributes + array( 'name' => $key ) );
		}

		$meta   = get_registered_meta_keys( 'post', 'book' )[ $key ];
		$schema = $meta['show_in_rest']['schema'] ?? array();
		$type   = match ( true ) {
			'boolean' === $meta['type'] => 'checkbox',
			in_array( $meta['type'], array( 'integer', 'number' ), true ) => 'number',
			'date' === ( $schema['format'] ?? '' ) => 'date',
			default => 'text',
		};

		return Library::control(
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
		if ( ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) || 'book' !== get_post_type( $post_id ) ) {
			return;
		}

		$read       = (bool) get_post_meta( $post_id, 'read', true );
		$read_dates = self::get_read_dates( $post_id );

		if ( 'read_date' === $meta_key && $read_dates && ! $read ) {
			update_post_meta( $post_id, 'read', true );
		}

		if ( 'read' === $meta_key && ! $read && $read_dates ) {
			delete_post_meta( $post_id, 'read_date' );
		}
	}

	/**
	 * Read dates of a book, one per reading, from the first to the latest.
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	public static function get_read_dates( int $post_id ): array {
		$read_dates = array_unique( array_filter( get_post_meta( $post_id, 'read_date', false ) ) );

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
		$was_read = (bool) get_post_meta( $post_id, 'read', true );

		foreach ( self::KEYS as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}

			if ( 'read_date' === $key ) {
				self::save_read_dates( $post_id, (array) $values[ $key ] );
				continue;
			}

			$value = sanitize_meta( $key, $values[ $key ], 'post', 'book' );

			if ( '' === $value || false === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}

			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}

		$marked_read = ! $was_read && (bool) get_post_meta( $post_id, 'read', true );

		if ( $marked_read && ! self::get_read_dates( $post_id ) ) {
			add_post_meta( $post_id, 'read_date', current_time( 'Y-m-d' ) );
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
		$read_dates = array_map( fn( $read_date ) => sanitize_meta( 'read_date', $read_date, 'post', 'book' ), $read_dates );
		$read_dates = array_unique( array_filter( $read_dates ) );

		sort( $read_dates );

		if ( self::get_read_dates( $post_id ) === $read_dates ) {
			return;
		}

		delete_post_meta( $post_id, 'read_date' );

		foreach ( $read_dates as $read_date ) {
			add_post_meta( $post_id, 'read_date', $read_date );
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
