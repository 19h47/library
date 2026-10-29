<?php
/**
 * ISBN: its hyphens and its check digit, and the EAN-13 of a book without ISBN.
 *
 * @link       https://github.com/19h47/library
 * @since      6.14.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Places the hyphens of an ISBN as the International ISBN Agency gives its parts, from the ranges of
 * includes/isbn-ranges.json, and checks its check digit.
 *
 * An ISBN-13 is its EAN prefix, its group, such as a country or a language, its registrant, the publisher, its
 * publication and its check digit: 978-2-07-036024-6. An ISBN-10 is the same without its prefix, read as 978.
 * An ISBN-13 is an EAN-13 of the prefixes 978 and 979: another EAN-13, the barcode of a book without ISBN, such as
 * an army book of Warhammer, has the same check digit, and keeps its 13 digits.
 *
 * @since      6.14.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_ISBN {

	/**
	 * Ranges of the ISBN by prefix, read once.
	 *
	 * @var array<string, int[][]>|null
	 */
	private static ?array $ranges = null;

	/**
	 * The code of a book as it is saved, typed with or without hyphens or spaces: an ISBN with its hyphens, such as
	 * 978-2-07-036024-6 for 9782070360246, or another EAN-13 as its 13 digits, such as 5011921988754 for
	 * 501-1-921-98875-4. Empty for a code whose check digit is wrong, or an ISBN whose group or registrant no range of
	 * the agency gives.
	 *
	 * @param string $code ISBN or EAN-13 as typed.
	 * @return string
	 */
	public static function format( string $code ): string {
		$digits = self::get_digits( $code );

		if ( ! self::has_valid_check_digit( $digits ) ) {
			return '';
		}

		if ( 13 === strlen( $digits ) && ! preg_match( '/^97[89]/', $digits ) ) {
			return $digits;
		}

		$is_isbn10 = 10 === strlen( $digits );
		$parts     = array( $is_isbn10 ? '978' : substr( $digits, 0, 3 ) );
		$rest      = substr( $digits, $is_isbn10 ? 0 : 3, 9 );

		// The group after the prefix, then the registrant after the group: the range of the digits that follow gives its length.
		for ( $part = 0; $part < 2; $part++ ) {
			$length = self::get_length( implode( '-', $parts ), $rest );

			if ( ! $length || $length >= strlen( $rest ) ) {
				return '';
			}

			$parts[] = substr( $rest, 0, $length );
			$rest    = substr( $rest, $length );
		}

		$parts = array( ...$parts, $rest, substr( $digits, -1 ) );

		return implode( '-', $is_isbn10 ? array_slice( $parts, 1 ) : $parts );
	}

	/**
	 * Whether an ISBN of 13 or 10 digits, or another EAN-13, typed with or without hyphens or spaces, has a wrong check
	 * digit: a digit mistyped or two swapped, such as 978-2-07-036042-6 for 978-2-07-036024-6.
	 *
	 * @param string $code ISBN or EAN-13 as typed.
	 * @return bool
	 */
	public static function has_wrong_check_digit( string $code ): bool {
		$digits = self::get_digits( $code );

		return in_array( strlen( $digits ), array( 10, 13 ), true ) && preg_match( '/^\d+X?$/', $digits ) && ! self::has_valid_check_digit( $digits );
	}

	/**
	 * Characters of a code without its hyphens and spaces, a check digit of 10 as X.
	 *
	 * @param string $code ISBN or EAN-13 as typed.
	 * @return string
	 */
	private static function get_digits( string $code ): string {
		return strtoupper( (string) preg_replace( '/[\s-]+/', '', $code ) );
	}

	/**
	 * Whether digits are an ISBN-13 or EAN-13 whose weighted sum by 1 and 3 is a multiple of 10, or an ISBN-10 whose
	 * weighted sum from 10 down to 1 is a multiple of 11, its check digit X for 10.
	 *
	 * @param string $digits Digits of a code, from get_digits().
	 * @return bool
	 */
	private static function has_valid_check_digit( string $digits ): bool {
		if ( preg_match( '/^\d{13}$/', $digits ) ) {
			$sum = 0;

			foreach ( str_split( $digits ) as $index => $digit ) {
				$sum += (int) $digit * ( $index % 2 ? 3 : 1 );
			}

			return 0 === $sum % 10;
		}

		if ( preg_match( '/^\d{9}[\dX]$/', $digits ) ) {
			$sum = 0;

			foreach ( str_split( $digits ) as $index => $digit ) {
				$sum += ( 'X' === $digit ? 10 : (int) $digit ) * ( 10 - $index );
			}

			return 0 === $sum % 11;
		}

		return false;
	}

	/**
	 * Length of the part that follows a prefix, as the range of the seven digits after the prefix gives it; 0 when none does.
	 *
	 * @param string $prefix Prefix, such as 978 or 978-2.
	 * @param string $digits Digits after the prefix.
	 * @return int
	 */
	private static function get_length( string $prefix, string $digits ): int {
		self::$ranges ??= json_decode( (string) file_get_contents( __DIR__ . '/isbn-ranges.json' ), true )['ranges']; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A file of the plugin.

		$value = (int) substr( str_pad( $digits, 7, '0' ), 0, 7 );

		foreach ( self::$ranges[ $prefix ] ?? array() as list( $first, $last, $length ) ) {
			if ( $value >= $first && $value <= $last ) {
				return $length;
			}
		}

		return 0;
	}
}
