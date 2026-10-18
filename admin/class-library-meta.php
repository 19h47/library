<?php
/**
 * Book meta: registration, sanitization and saving.
 *
 * @link       https://github.com/19h47/library
 * @since      1.0.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Registers book meta so every write (form, quick edit, REST) goes through
 * the same sanitize callbacks.
 *
 * @since      1.0.0
 * @package    Library
 * @subpackage Library/admin
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
	 * Date meta keys, stored as Y-m-d.
	 *
	 * @var string[]
	 */
	private const DATE_KEYS = array( 'edition_date', 'read_date' );

	/**
	 * HTML pattern of an ISBN-10 or ISBN-13, with or without hyphens or spaces.
	 *
	 * @var string
	 */
	public const ISBN_PATTERN = '(?:(?=.{17}$)(97[89]|501)[ -](?:[0-9]+[ -]){2}[0-9]+[ -][0-9]|97[89][0-9]{10}|(?=.{13}$)(?:[0-9]+[ -]){2}[0-9]+[ -][0-9Xx]|[0-9]{9}[0-9Xx])';

	/**
	 * Book meta edited in quick edit.
	 *
	 * @var string[]
	 */
	public const QUICK_EDIT_KEYS = array( 'isbn', 'pages', 'read_date', 'rating' );

	/**
	 * All book meta keys.
	 *
	 * The read state comes after the read date, so unchecking "Read" in the form
	 * clears the date instead of the date checking "Read" again.
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
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'added_post_meta', array( $this, 'sync_reading_state' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'sync_reading_state' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'sync_reading_state' ), 10, 3 );
	}

	/**
	 * Register book meta.
	 *
	 * @return void
	 */
	public function register(): void {
		foreach ( self::TEXT_KEYS as $key ) {
			register_post_meta(
				'book',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
				)
			);
		}

		foreach ( self::DATE_KEYS as $key ) {
			register_post_meta(
				'book',
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => array(
						'schema' => array( 'pattern' => '^\d{4}-\d{2}-\d{2}$' ),
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
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
	}

	/**
	 * Keep the read state and the read date consistent, whatever wrote them:
	 * a read date marks the book as read, marking it as unread clears the date.
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

		$read      = (bool) get_post_meta( $post_id, 'read', true );
		$read_date = get_post_meta( $post_id, 'read_date', true );

		if ( 'read_date' === $meta_key && '' !== $read_date && ! $read ) {
			update_post_meta( $post_id, 'read', true );
		}

		if ( 'read' === $meta_key && ! $read && '' !== $read_date ) {
			delete_post_meta( $post_id, 'read_date' );
		}
	}

	/**
	 * Save the given meta values; an empty value deletes the meta.
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

			$value = sanitize_meta( $key, $values[ $key ], 'post', 'book' );

			if ( '' === $value || false === $value ) {
				delete_post_meta( $post_id, $key );
				continue;
			}

			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}

		$marked_read = ! $was_read && (bool) get_post_meta( $post_id, 'read', true );

		if ( $marked_read && '' === get_post_meta( $post_id, 'read_date', true ) ) {
			update_post_meta( $post_id, 'read_date', current_time( 'Y-m-d' ) );
		}
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
