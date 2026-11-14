/**
 * Formats in the language of the page: numbers, quantities named, dates, languages and lists, each by a function. It
 * imports only the strings of the app: bricks and components share it. The lines of a book are library-app/book-lines.
 */

import { __, _n, sprintf } from 'library-app/app';

const { lang } = document.documentElement;
const numberFormat = new Intl.NumberFormat( lang );
const dateFormats = {
	long: new Intl.DateTimeFormat( lang, { dateStyle: 'long' } ),
	day: new Intl.DateTimeFormat( lang, { day: 'numeric', month: 'long' } ),
};
const monthFormat = new Intl.DateTimeFormat( lang, {
	year: 'numeric',
	month: 'long',
} );
const monthNameFormat = new Intl.DateTimeFormat( lang, { month: 'long' } );
const monthInitialFormat = new Intl.DateTimeFormat( lang, { month: 'narrow' } );
const listFormat = new Intl.ListFormat( lang, { type: 'unit' } );
const andFormat = new Intl.ListFormat( lang );
const languageNames = new Intl.DisplayNames( lang, { type: 'language' } );

/** The language of the page, its ISO 639 code: "fr". */
export const { language: pageLanguage } = new Intl.Locale( lang );

/**
 * A number in the language of the page: "1,234".
 *
 * @param {number} number The number.
 * @return {string} The number formatted.
 */
export function formatNumber( number ) {
	return numberFormat.format( number );
}

/**
 * A number of books: "12 books".
 *
 * @param {number} books The number of books.
 * @return {string} The number of books formatted.
 */
export function formatBooks( books ) {
	return sprintf(
		/* translators: %s: number of books. */
		_n( '%s book', '%s books', books, 'library' ),
		formatNumber( books )
	);
}

/**
 * A number of books read, after a number of books: "8 read".
 *
 * @param {number} read The number of books read.
 * @return {string} The number of books read formatted.
 */
export function formatRead( read ) {
	return sprintf(
		/* translators: %s: number of books read, after the number of books of a person, a series or a collection. */
		_n( '%s read', '%s read', read, 'library' ),
		formatNumber( read )
	);
}

/**
 * A number of pages: "320 pages".
 *
 * @param {number} pages The number of pages.
 * @return {string} The number of pages formatted.
 */
export function formatPages( pages ) {
	return sprintf(
		/* translators: %s: number of pages. */
		_n( '%s page', '%s pages', pages, 'library' ),
		formatNumber( pages )
	);
}

/**
 * The name of the recap of a year: "Recap of 2025".
 *
 * @param {number} year The year.
 * @return {string} Its name.
 */
export function formatRecap( year ) {
	return sprintf(
		/* translators: %s: year. */
		__( 'Recap of %s', 'library' ),
		year.toString()
	);
}

/**
 * A date of the API: "2024-05-12", a read date of the day with its time, "2026-11-01T21:40", or "1929-11" and "1929" for a
 * date first published; its day long, "May 12, 2024", or as a day of its year, "May 12".
 *
 * @param {string}       date    The date.
 * @param {'long'|'day'} [style] How its day shows, long unless a day of its year.
 * @return {string} The date formatted.
 */
export function formatDate( date, style = 'long' ) {
	const day = date.slice( 0, 10 );
	const [ year, month, dayOfMonth ] = day.split( '-' );

	if ( ! dayOfMonth ) {
		return month
			? monthFormat.format(
					new Date( Number( year ), Number( month ) - 1 )
			  )
			: year;
	}

	return dateFormats[ style ].format( new Date( `${ day }T00:00` ) );
}

/**
 * A rating out of 10: "7.5/10"; none for none.
 *
 * @param {number} rating The rating, 0 for none.
 * @return {string} The rating formatted, or empty.
 */
export function formatRating( rating ) {
	return rating
		? sprintf(
				/* translators: %s: rating, e.g. 7.5. */
				__( '%s/10', 'library' ),
				formatNumber( rating )
		  )
		: '';
}

/**
 * The name of a language, from its ISO 639 code: "Italian".
 *
 * @param {string} code The code.
 * @return {string} Its name.
 */
export function formatLanguage( code ) {
	return languageNames.of( code );
}

/**
 * A day of the device, as a day of its year: "May 12".
 *
 * @param {Date} date The day.
 * @return {string} The day formatted.
 */
export function formatDay( date ) {
	return dateFormats.day.format( date );
}

/**
 * The name of a month, as the recap of a year shows it: "May".
 *
 * @param {Date} date A day of the month.
 * @return {string} Its name.
 */
export function formatMonthName( date ) {
	return monthNameFormat.format( date );
}

/**
 * The initial of a month, as the recap of a year shows it under its bars: "M".
 *
 * @param {Date} date A day of the month.
 * @return {string} Its initial.
 */
export function formatMonthInitial( date ) {
	return monthInitialFormat.format( date );
}

/**
 * Names in a list, as units: "A, B, C".
 *
 * @param {string[]} names The names.
 * @return {string} The list.
 */
export function formatList( names ) {
	return listFormat.format( names );
}

/**
 * The parts of a list of names, as units, its names and what goes between them, for each name to be a link.
 *
 * @param {string[]} names The names.
 * @return {ReturnType<Intl.ListFormat['formatToParts']>} Its parts.
 */
export function formatListParts( names ) {
	return listFormat.formatToParts( names );
}

/**
 * Names joined by "and": "A, B and C".
 *
 * @param {string[]} names The names.
 * @return {string} The list.
 */
export function formatAnd( names ) {
	return andFormat.format( names );
}

/**
 * Parts of a line, the empty ones left out: "Read · 8/10".
 *
 * @param {Array<string|false|0|null>} parts The parts, false, 0, null or empty for none.
 * @return {string} The line.
 */
export function join( parts ) {
	return parts.filter( Boolean ).join( ' · ' );
}

/**
 * Today on the device, as a read date: Y-m-d.
 *
 * @return {string} Today.
 */
export function today() {
	const now = new Date();

	return [ now.getFullYear(), now.getMonth() + 1, now.getDate() ]
		.map( ( part ) => part.toString().padStart( 2, '0' ) )
		.join( '-' );
}
