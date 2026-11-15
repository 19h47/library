<?php
/**
 * The shelves of a reader: where each book is for them, since when, and their copy, its printed date, its readings, their
 * notes and its photo.
 *
 * @link       https://github.com/19h47/library
 * @since      10.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Keeps on a book, in meta of their own, where it is for each reader, since when, when their copy was printed, their
 * readings, their notes and the photo of their copy: its key followed by their user ID, `library_status_12`. The catalogue is shared, its books published; a book
 * on none of the shelves of a reader has none of their rows. A copy is on a shelf: a reading, a printed date, notes or a
 * photo put a book on none of them among the books not kept, and a book with readings stays on one.
 *
 * @since      10.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Shelf {

	/**
	 * Key of the status of a book for a reader: in the library, not kept or to buy.
	 *
	 * @var string
	 */
	public const string STATUS = 'library_status';

	/**
	 * Key of the date a reader added a book, Y-m-d H:i:s, in the time zone of the site, as a post date.
	 *
	 * @var string
	 */
	public const string ADDED = 'library_added';

	/**
	 * Keys of a shelf, each reader having a row of their own, or one per reading.
	 *
	 * @var list<string>
	 */
	private const array KEYS = array( self::STATUS, self::ADDED, 'library_printed_date', 'library_reading', 'library_notes', 'library_cover' );

	/**
	 * Meta keys of the copy of a reader by their names in the REST API: see Meta::SHELF_KEYS.
	 *
	 * @var array<string,string>
	 */
	public const array NAMES = array(
		'printed_date' => 'library_printed_date',
		'readings'     => 'library_reading',
		'read'         => 'library_read',
		'in_progress'  => 'library_in_progress',
		'rating'       => 'library_rating',
		'notes'        => 'library_notes',
		'cover'        => 'library_cover',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'is_protected_meta', array( $this, 'protect' ), 10, 2 );
		add_action( 'deleted_user', array( $this, 'forget_reader' ) );
	}

	/**
	 * Key of a shelf for a reader: `library_status_12`.
	 *
	 * @param string $key    Key of a shelf, such as Shelf::STATUS.
	 * @param int    $reader User ID of the reader.
	 * @return string
	 */
	public static function key( string $key, int $reader ): string {
		return "{$key}_{$reader}";
	}

	/**
	 * Where a book is for a reader, null on none of their shelves.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return Status|null
	 */
	public static function get_status( int $book_id, int $reader ): ?Status {
		return Status::tryFrom( (string) get_post_meta( $book_id, self::key( self::STATUS, $reader ), true ) );
	}

	/**
	 * When a reader added a book, Y-m-d H:i:s, or empty on none of their shelves.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return string
	 */
	public static function get_added( int $book_id, int $reader ): string {
		return (string) get_post_meta( $book_id, self::key( self::ADDED, $reader ), true );
	}

	/**
	 * When the copy of a reader was printed, Y, Y-m or Y-m-d, or empty.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return string
	 */
	public static function get_printed_date( int $book_id, int $reader ): string {
		return (string) get_post_meta( $book_id, self::key( 'library_printed_date', $reader ), true );
	}

	/**
	 * The copy of a reader as its forms edit it, by meta key: when it was printed, its readings, whether it is read and
	 * being read, its rating, their notes and the image of its photo, 0 for none.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return array{library_printed_date:string,library_reading:Reading[],library_read:bool,library_in_progress:bool,library_rating:float|null,library_notes:string,library_cover:int}
	 */
	public static function get_values( int $book_id, int $reader ): array {
		$readings = Meta::get_readings( $book_id, $reader );

		return array(
			'library_printed_date' => self::get_printed_date( $book_id, $reader ),
			'library_reading'      => $readings,
			'library_read'         => (bool) Meta::get_ended( $readings ),
			'library_in_progress'  => count( Meta::get_ended( $readings ) ) < count( $readings ),
			'library_rating'       => Meta::get_rating( $readings ),
			'library_notes'        => (string) get_post_meta( $book_id, self::key( 'library_notes', $reader ), true ),
			'library_cover'        => (int) get_post_meta( $book_id, self::key( 'library_cover', $reader ), true ),
		);
	}

	/**
	 * Put a book on a shelf of a reader, or take it off their shelves with null, unless they read it: a book read stays
	 * on a shelf, not kept once passed on. A book added, or leaving the books to buy, is dated from now: it came to the
	 * library then.
	 *
	 * @param int         $book_id Book ID.
	 * @param int         $reader  User ID of the reader.
	 * @param Status|null $status  Status, null to take it off.
	 * @return bool Whether it is where it was asked: false for a book read taken off.
	 */
	public static function set_status( int $book_id, int $reader, ?Status $status ): bool {
		$before = self::get_status( $book_id, $reader );

		// Taken off, even a book on none of their shelves loses the rows a reader may have kept, such as a copy of before.
		if ( null === $status ) {
			if ( Meta::get_readings( $book_id, $reader ) ) {
				return false;
			}

			foreach ( self::KEYS as $key ) {
				delete_post_meta( $book_id, self::key( $key, $reader ) );
			}

			return true;
		}

		if ( $status === $before ) {
			return true;
		}

		update_post_meta( $book_id, self::key( self::STATUS, $reader ), $status->value );

		if ( null === $before || Status::To_Buy === $before ) {
			update_post_meta( $book_id, self::key( self::ADDED, $reader ), current_time( 'mysql' ) );
		}

		return true;
	}

	/**
	 * Save the copy of a reader, as given by meta key, see Meta::SHELF_KEYS: when it was printed, their notes and the photo
	 * of their copy, an empty value deleting it, a photo they may not give left out, and its readings, see
	 * Meta::save_readings(). A copy on a book on none of their shelves puts it among the books not kept: see has_copy().
	 *
	 * @param int                 $book_id Book ID.
	 * @param int                 $reader  User ID of the reader.
	 * @param array<string,mixed> $values  Unslashed values keyed by meta key.
	 * @return void
	 */
	public static function save( int $book_id, int $reader, array $values ): void {
		if ( isset( $values['library_cover'] ) && ! self::can_cover( (int) $values['library_cover'], $reader ) ) {
			unset( $values['library_cover'] );
		}

		foreach ( array( 'library_printed_date', 'library_notes', 'library_cover' ) as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}

			$value = sanitize_meta( $key, $values[ $key ], 'post', 'library_book' );

			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $book_id, self::key( $key, $reader ) );
			} else {
				update_post_meta( $book_id, self::key( $key, $reader ), wp_slash( $value ) );
			}
		}

		Meta::save_readings( $book_id, $reader, $values );

		if ( null === self::get_status( $book_id, $reader ) && self::has_copy( $book_id, $reader ) ) {
			self::set_status( $book_id, $reader, Status::Not_Kept );
		}
	}

	/**
	 * Whether a reader has a copy of a book: a reading, the date it was printed, their notes or its photo, which only a
	 * book on their shelves has.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return bool
	 */
	private static function has_copy( int $book_id, int $reader ): bool {
		$copy = self::get_values( $book_id, $reader );

		return $copy['library_reading'] || '' !== $copy['library_printed_date'] || '' !== $copy['library_notes'] || 0 !== $copy['library_cover'];
	}

	/**
	 * Whether a reader may give the photo of their copy an image: one they uploaded, or none, 0.
	 *
	 * @param int $image_id Attachment ID, 0 for none.
	 * @param int $reader   User ID of the reader.
	 * @return bool
	 */
	public static function can_cover( int $image_id, int $reader ): bool {
		$author = (int) get_post_field( 'post_author', $image_id );

		return 0 === $image_id || ( wp_attachment_is_image( $image_id ) && $author === $reader );
	}

	/**
	 * The copy of a reader by meta key, from their names, as the REST API takes them: see NAMES. Another name is left out.
	 *
	 * @param array<string,mixed> $values Values, by name.
	 * @return array<string,mixed>
	 */
	public static function from_names( array $values ): array {
		$given = array_intersect_key( $values, self::NAMES );

		return array_combine( array_map( fn( string $name ) => self::NAMES[ $name ], array_keys( $given ) ), $given );
	}

	/**
	 * The shelf of a book for a reader, as the REST API gives it: its status and the date added, null on none of their
	 * shelves, and their copy by its names, see NAMES: its readings in order, each with its start, its end and its rating,
	 * their notes as typed and as HTML, and the image of its photo with its ID, none without.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return array<string,mixed>
	 */
	public static function to_array( int $book_id, int $reader ): array {
		$added = self::get_added( $book_id, $reader );
		$copy  = self::get_values( $book_id, $reader );

		$copy['library_reading'] = array_map( fn( Reading $reading ) => $reading->to_array(), $copy['library_reading'] );
		$copy['library_notes']   = array(
			'raw'      => $copy['library_notes'],
			'rendered' => wpautop( $copy['library_notes'] ),
		);
		$image                   = Books::get_image( $copy['library_cover'] );
		$copy['library_cover']   = $image ? array( 'id' => $copy['library_cover'] ) + $image : null;

		return array(
			'status' => self::get_status( $book_id, $reader )?->value,
			'added'  => '' !== $added ? $added : null,
		) + array_combine( array_keys( self::NAMES ), array_map( fn( string $key ) => $copy[ $key ], self::NAMES ) );
	}

	/**
	 * Keep the rows of the shelves out of the custom fields, as WordPress does its own meta: they are the shelves.
	 *
	 * @param bool   $is_protected Whether the key is protected.
	 * @param string $meta_key     Meta key.
	 * @return bool
	 */
	public function protect( bool $is_protected, string $meta_key ): bool {
		return $is_protected || (bool) preg_match( '/^(' . implode( '|', self::KEYS ) . ')_\d+$/', $meta_key );
	}

	/**
	 * Take every book off the shelves of a reader deleted.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function forget_reader( int $user_id ): void {
		foreach ( self::KEYS as $key ) {
			delete_metadata( 'post', 0, self::key( $key, $user_id ), '', true );
		}
	}
}
