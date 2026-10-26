/**
 * Formats of the bricks: numbers, dates, books and lists in the language of the page, and the line of a book.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { __, _n, _x, notKept, sprintf } from 'library-app/app';

const { lang } = document.documentElement;
const numberFormat = new Intl.NumberFormat( lang );
const dateFormat = new Intl.DateTimeFormat( lang, { dateStyle: 'long' } );
const monthFormat = new Intl.DateTimeFormat( lang, { year: 'numeric', month: 'long' } );

export const dayFormat = new Intl.DateTimeFormat( lang, { day: 'numeric', month: 'long' } );
export const listFormat = new Intl.ListFormat( lang, { type: 'unit' } );

export function formatNumber( number ) {
	return numberFormat.format( number );
}

/** A number of books: "12 books". */
export function formatBooks( books ) {
	/* translators: %s: number of books. */
	return sprintf( _n( '%s book', '%s books', books, 'library' ), formatNumber( books ) );
}

/** A date of the API: "2024-05-12", or "1929-11" and "1929" for a date first published. */
export function formatDate( date, format = dateFormat ) {
	const [ year, month, day ] = date.split( '-' );

	if ( day ) {
		return format.format( new Date( `${ date }T00:00` ) );
	}

	return month ? monthFormat.format( new Date( Number( year ), Number( month ) - 1 ) ) : year;
}

export function formatRating( rating ) {
	/* translators: %s: rating, e.g. 7.5. */
	return rating ? sprintf( __( '%s/10', 'library' ), formatNumber( rating ) ) : '';
}

/** Whether a book is kept: shown only when it is not. */
export function formatStatus( book ) {
	return notKept === book.status ? _x( 'Not kept', 'book status', 'library' ) : '';
}

/** Whether a book is read, and its rating. */
export function formatState( book ) {
	return join( [ book.read ? __( 'Read', 'library' ) : __( 'To read', 'library' ), formatRating( book.rating ) ] );
}

export function join( parts ) {
	return parts.filter( Boolean ).join( ' · ' );
}

/** Today on the device, as a read date: Y-m-d. */
export function today() {
	const now = new Date();

	return [ now.getFullYear(), now.getMonth() + 1, now.getDate() ].map( ( part ) => part.toString().padStart( 2, '0' ) ).join( '-' );
}
