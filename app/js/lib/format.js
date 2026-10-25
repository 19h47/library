/**
 * Formats of the bricks: numbers, dates, plurals and lists in the language of the page, and the line of a book.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { notKept, sprintf, strings } from 'library-app/app';

const { lang } = document.documentElement;
const numberFormat = new Intl.NumberFormat( lang );
const dateFormat = new Intl.DateTimeFormat( lang, { dateStyle: 'long' } );
const monthFormat = new Intl.DateTimeFormat( lang, { year: 'numeric', month: 'long' } );

export const pluralRules = new Intl.PluralRules( lang );
export const dayFormat = new Intl.DateTimeFormat( lang, { day: 'numeric', month: 'long' } );
export const listFormat = new Intl.ListFormat( lang, { type: 'unit' } );

export function formatNumber( number ) {
	return numberFormat.format( number );
}

/** The form of `forms` for `number`, with its number: in French, 0 takes the singular too. */
export function plural( number, forms ) {
	return sprintf( forms[ pluralRules.select( number ) ] ?? forms.other, formatNumber( number ) );
}

/** A date of the API: "2024-05-12", or "1929-11" and "1929" for a date first published. */
export function formatDate( date, format = dateFormat ) {
	const [ year, month, day ] = date.split( '-' );

	if ( day ) {
		return format.format( new Date( `${ date }T00:00` ) );
	}

	return month ? monthFormat.format( new Date( Number( year ), Number( month ) - 1 ) ) : year;
}

function formatRating( rating ) {
	return rating ? sprintf( strings.rating, formatNumber( rating ) ) : '';
}

/** Its rating, and whether it is kept: the end of the state of a book. */
export function formatDetails( book ) {
	return [ formatRating( book.rating ), notKept === book.status && strings.notKept ];
}

/** Whether a book is read, its rating, and whether it is kept. */
export function formatState( book ) {
	return join( [ book.read ? strings.read : strings.toRead, ...formatDetails( book ) ] );
}

export function join( parts ) {
	return parts.filter( Boolean ).join( ' · ' );
}

/** Today on the device, as a read date: Y-m-d. */
export function today() {
	const now = new Date();

	return [ now.getFullYear(), now.getMonth() + 1, now.getDate() ].map( ( part ) => part.toString().padStart( 2, '0' ) ).join( '-' );
}
