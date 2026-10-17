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
	public const TEXT_KEYS = array( 'series', 'isbn', 'issn', 'volume_number', 'translators', 'book_editions' );

	/**
	 * Date meta keys, stored as Y-m-d.
	 *
	 * @var string[]
	 */
	private const DATE_KEYS = array( 'date_published', 'read_date' );

	/**
	 * All book meta keys.
	 *
	 * The read state comes after the read date, so unchecking "Read" in the form
	 * clears the date instead of the date checking "Read" again.
	 *
	 * @var string[]
	 */
	public const KEYS = array( ...self::TEXT_KEYS, ...self::DATE_KEYS, 'rating', 'read' );

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
	 * a read date marks the book as read, marking it as read without a date sets today,
	 * marking it as unread clears the date.
	 *
	 * Hooked to added_post_meta, updated_post_meta and deleted_post_meta.
	 *
	 * @param int|int[] $meta_ids Meta ID(s).
	 * @param int       $post_id  Post ID.
	 * @param string    $meta_key Meta key.
	 * @return void
	 */
	public function sync_reading_state( $meta_ids, int $post_id, string $meta_key ): void {
		if ( 'book' !== get_post_type( $post_id ) ) {
			return;
		}

		$read      = (bool) get_post_meta( $post_id, 'read', true );
		$read_date = get_post_meta( $post_id, 'read_date', true );

		if ( 'read_date' === $meta_key && '' !== $read_date && ! $read ) {
			update_post_meta( $post_id, 'read', true );
		}

		if ( 'read' === $meta_key && $read && '' === $read_date ) {
			update_post_meta( $post_id, 'read_date', current_time( 'Y-m-d' ) );
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
	 *
	 * @param int   $post_id Post ID.
	 * @param array $values  Unslashed values keyed by meta key.
	 * @return void
	 */
	public static function save( int $post_id, array $values ): void {
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
