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
	 * Its label with a count, as the views of the books list name it: "To buy (3)".
	 *
	 * @return array{0:string,1:string,singular:string,plural:string,context:null,domain:string|null} Noop of _n_noop(), as register_post_status() takes it.
	 */
	public function label_count(): array {
		return match ( $this ) {
			/* translators: %s: number of books. */
			self::In_Library => _n_noop( 'In the library <span class="count">(%s)</span>', 'In the library <span class="count">(%s)</span>', 'library' ),
			/* translators: %s: number of books. */
			self::Not_Kept   => _n_noop( 'Not kept <span class="count">(%s)</span>', 'Not kept <span class="count">(%s)</span>', 'library' ),
			/* translators: %s: number of books. */
			self::To_Buy     => _n_noop( 'To buy <span class="count">(%s)</span>', 'To buy <span class="count">(%s)</span>', 'library' ),
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

	/**
	 * The statuses out of the library, not kept and to buy, which core does not know: the plugin registers them.
	 *
	 * @return self[]
	 */
	public static function get_others(): array {
		return array_values( array_filter( self::cases(), fn( self $status ) => self::In_Library !== $status ) );
	}

	/**
	 * The values of the statuses out of the library, as the forms of a book and the lists of terms tell them apart.
	 *
	 * @return string[]
	 */
	public static function get_other_values(): array {
		return array_column( self::get_others(), 'value' );
	}
}
