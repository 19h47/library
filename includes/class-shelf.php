<?php
/**
 * The shelves of a reader: where each book is for them, and since when.
 *
 * @link       https://github.com/19h47/library
 * @since      10.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Keeps on a book, in meta of their own, where it is for each reader and since when: its key followed by their user ID,
 * `library_status_12`. The catalogue is shared, its books published; a book on none of the shelves of a reader has none
 * of their rows.
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
	 * Keys of a shelf, each reader having a row of their own.
	 *
	 * @var list<string>
	 */
	private const array KEYS = array( self::STATUS, self::ADDED );

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
	 * Put a book on a shelf of a reader, or take it off their shelves with null. A book added, or leaving the books to
	 * buy, is dated from now: it came to the library then.
	 *
	 * @param int         $book_id Book ID.
	 * @param int         $reader  User ID of the reader.
	 * @param Status|null $status  Status, null to take it off.
	 * @return void
	 */
	public static function set_status( int $book_id, int $reader, ?Status $status ): void {
		$before = self::get_status( $book_id, $reader );

		if ( $status === $before ) {
			return;
		}

		if ( null === $status ) {
			foreach ( self::KEYS as $key ) {
				delete_post_meta( $book_id, self::key( $key, $reader ) );
			}

			return;
		}

		update_post_meta( $book_id, self::key( self::STATUS, $reader ), $status->value );

		if ( null === $before || Status::To_Buy === $before ) {
			update_post_meta( $book_id, self::key( self::ADDED, $reader ), current_time( 'mysql' ) );
		}
	}

	/**
	 * The shelf of a book for a reader, as the REST API gives it: its status and the date added, null on none of their
	 * shelves.
	 *
	 * @param int $book_id Book ID.
	 * @param int $reader  User ID of the reader.
	 * @return array{status:string|null,added:string|null}
	 */
	public static function to_array( int $book_id, int $reader ): array {
		$added = self::get_added( $book_id, $reader );

		return array(
			'status' => self::get_status( $book_id, $reader )?->value,
			'added'  => '' !== $added ? $added : null,
		);
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
