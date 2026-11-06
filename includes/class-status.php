<?php
/**
 * The statuses of a book.
 *
 * @link       https://github.com/19h47/library
 * @since      9.25.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

/**
 * Where a book is, as its post status: in the library, published; not kept, read but borrowed or passing through; or to
 * buy, not in the library yet. A book of another status, such as a draft, is left out of the books until it is published.
 *
 * @since      9.25.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
enum Status: string {

	case In_Library = 'publish';
	case Not_Kept   = 'library-not-kept';
	case To_Buy     = 'library-to-buy';

	/**
	 * Its label, as the forms of a book and the filters of the books offer it.
	 *
	 * @return string
	 */
	public function label(): string {
		return match ( $this ) {
			self::In_Library => _x( 'In the library', 'book status', 'library' ),
			self::Not_Kept   => _x( 'Not kept', 'book status', 'library' ),
			self::To_Buy     => _x( 'To buy', 'book status', 'library' ),
		};
	}

	/**
	 * Every status, as a form offers it: its label by its value.
	 *
	 * @return array<string,string>
	 */
	public static function get_choices(): array {
		return array_combine( array_column( self::cases(), 'value' ), array_map( fn( self $status ) => $status->label(), self::cases() ) );
	}

	/**
	 * The values of every status, as the queries of the books take them.
	 *
	 * @return string[]
	 */
	public static function get_values(): array {
		return array_column( self::cases(), 'value' );
	}
}
