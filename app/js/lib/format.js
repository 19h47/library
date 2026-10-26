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

const andFormat = new Intl.ListFormat( lang );
const languageNames = new Intl.DisplayNames( lang, { type: 'language' } );
const { language: pageLanguage } = new Intl.Locale( lang );

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

/**
 * Who translated a book, and from which of its original languages: "Translated from Italian by A and B". A book without
 * translator is read in its original language, and says nothing. The article before the language may depend on its
 * first letter, as the "de l'" and "du" of French: each string is translated for a vowel and for a consonant.
 */
export function formatTranslation( book ) {
	const translators = book.translators
		.split( ',' )
		.map( ( translator ) => translator.trim() )
		.filter( Boolean );

	if ( ! translators.length ) {
		return '';
	}

	const languages = book.languages.filter( ( code ) => code !== pageLanguage ).map( ( code ) => languageNames.of( code ) );

	if ( ! languages.length ) {
		/* translators: %s: list of the translators of a book. */
		return sprintf( __( 'Translated by %s', 'library' ), andFormat.format( translators ) );
	}

	const format = /^[aeiou]/i.test( languages[ 0 ].normalize( 'NFD' ) )
		? /* translators: 1: original languages of a book, the first starting with a vowel, e.g. Italian; 2: list of its translators. */
		  _x( 'Translated from %1$s by %2$s', 'language starting with a vowel', 'library' )
		: /* translators: 1: original languages of a book, the first starting with a consonant, e.g. Japanese; 2: list of its translators. */
		  _x( 'Translated from %1$s by %2$s', 'language starting with a consonant', 'library' );

	return sprintf( format, andFormat.format( languages ), andFormat.format( translators ) );
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
