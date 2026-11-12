/**
 * Formats of the bricks: numbers, dates, books and lists in the language of the page, and the line of a book.
 */

import { __, _n, _x, notKept, sprintf, toBuy } from 'library-app/app';
import { getPeople } from 'library-app/api';

/** @import { Book } from 'library-app/api' */

const { lang } = document.documentElement;
const numberFormat = new Intl.NumberFormat( lang );
const dateFormat = new Intl.DateTimeFormat( lang, { dateStyle: 'long' } );
const monthFormat = new Intl.DateTimeFormat( lang, {
	year: 'numeric',
	month: 'long',
} );

const andFormat = new Intl.ListFormat( lang );
const languageNames = new Intl.DisplayNames( lang, { type: 'language' } );
const { language: pageLanguage } = new Intl.Locale( lang );
const DAY_MS = 24 * 60 * 60 * 1000;

export const dayFormat = new Intl.DateTimeFormat( lang, {
	day: 'numeric',
	month: 'long',
} );
export const listFormat = new Intl.ListFormat( lang, { type: 'unit' } );
// The name of a month, and its initial, such as the recap of a year shows them under its bars.
export const monthNameFormat = new Intl.DateTimeFormat( lang, {
	month: 'long',
} );
export const monthInitialFormat = new Intl.DateTimeFormat( lang, {
	month: 'narrow',
} );

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
 * How many books a list has, as `format` says it, once it is loaded; that it is loading until then.
 *
 * @param {{loaded: boolean, total: number}} list     The list.
 * @param {( books: number ) => string}      [format] What it says of its books, their number unless another one.
 * @return {string} What it says.
 */
export function formatTotal( list, format = formatBooks ) {
	return list.loaded ? format( list.total ) : __( 'Loading…', 'library' );
}

/**
 * Why a list shows nothing: why it could not load, or `empty` once loaded without an item; nothing otherwise.
 *
 * @param {{error: string, loaded: boolean, total: number}} list    The list.
 * @param {string}                                          [empty] What it says without an item, that no book is found unless another one.
 * @return {string} Why it shows nothing, or empty.
 */
export function formatListStatus(
	list,
	empty = __( 'No book found.', 'library' )
) {
	return list.error || ( list.loaded && ! list.total ? empty : '' );
}

/**
 * A date of the API: "2024-05-12", a read date of the day with its time, "2026-11-01T21:40", or "1929-11" and "1929" for a date first published.
 *
 * @param {string}              date     The date.
 * @param {Intl.DateTimeFormat} [format] The format of a day, the long date unless another one.
 * @return {string} The date formatted.
 */
export function formatDate( date, format = dateFormat ) {
	const day = date.slice( 0, 10 );
	const [ year, month, dayOfMonth ] = day.split( '-' );

	if ( ! dayOfMonth ) {
		return month
			? monthFormat.format(
					new Date( Number( year ), Number( month ) - 1 )
			  )
			: year;
	}

	return format.format( new Date( `${ day }T00:00` ) );
}

/**
 * How long the latest reading of a book took, when its start and its end are known: "12 days", the day it started and
 * the day it ended counted; empty otherwise.
 *
 * @param {Book} book The book.
 * @return {string} How long it took, or empty.
 */
export function formatReadingTime( book ) {
	const reading = book.readings.at( -1 );

	if ( ! reading?.start || ! reading.end ) {
		return '';
	}

	const [ start, end ] = [ reading.start, reading.end ].map( ( date ) =>
		new Date( `${ date.slice( 0, 10 ) }T00:00` ).getTime()
	);
	// A day of a change of time is an hour shorter or longer.
	const days = Math.round( ( end - start ) / DAY_MS ) + 1;

	return sprintf(
		/* translators: %s: number of days a reading took, the first and the last counted, e.g. 12. */
		_n( '%s day', '%s days', days, 'library' ),
		formatNumber( days )
	);
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
 * Who translated a book, and from which of its original languages: "Translated from Italian by A and B". A book without
 * translator is read in its original language, and says nothing. The article before the language may depend on its
 * first letter, as the "de l'" and "du" of French: each string is translated for a vowel and for a consonant.
 *
 * @param {Book} book The book.
 * @return {string} Who translated it, or empty.
 */
export function formatTranslation( book ) {
	const translators = getPeople( book, 'translator' ).map(
		( translator ) => translator.name
	);

	if ( ! translators.length ) {
		return '';
	}

	const languages = book.languages
		.filter( ( code ) => code !== pageLanguage )
		.map( ( code ) => languageNames.of( code ) );

	if ( ! languages.length ) {
		return sprintf(
			/* translators: %s: list of the translators of a book. */
			__( 'Translated by %s', 'library' ),
			andFormat.format( translators )
		);
	}

	const format = /^[aeiou]/i.test( languages[ 0 ].normalize( 'NFD' ) )
		? /* translators: 1: original languages of a book, the first starting with a vowel, e.g. Italian; 2: list of its translators. */
		  _x(
				'Translated from %1$s by %2$s',
				'language starting with a vowel',
				'library'
		  )
		: /* translators: 1: original languages of a book, the first starting with a consonant, e.g. Japanese; 2: list of its translators. */
		  _x(
				'Translated from %1$s by %2$s',
				'language starting with a consonant',
				'library'
		  );

	return sprintf(
		format,
		andFormat.format( languages ),
		andFormat.format( translators )
	);
}

/**
 * Whether a book is read in its original language, as the achievement "In the original" counts it: its text is in its
 * original languages only, none of them the one of the page. A book without original language, or whose edition
 * languages are unknown, says nothing; nor does a multilingual edition, whose text is also in a translation.
 *
 * @param {Book} book The book.
 * @return {string} Its tag, or empty.
 */
export function formatOriginal( book ) {
	const isOriginal =
		book.languages.length &&
		book.edition_languages.length &&
		book.edition_languages.every( ( code ) =>
			book.languages.includes( code )
		) &&
		! book.edition_languages.includes( pageLanguage );

	return isOriginal
		? _x(
				'Original',
				'book in its original language, short tag',
				'library'
		  )
		: '';
}

/**
 * Whether a book is kept: shown only when it is not, not kept or to buy.
 *
 * @param {Book} book The book.
 * @return {string} Its status, or empty.
 */
export function formatStatus( book ) {
	const labels = {
		[ notKept ]: _x( 'Not kept', 'book status', 'library' ),
		[ toBuy ]: _x( 'To buy', 'book status', 'library' ),
	};

	return labels[ book.status ] ?? '';
}

/**
 * Whether a book is being read, read, how many times from two readings, or to read unless it is to buy, and its rating.
 *
 * @param {Book} book The book.
 * @return {string} Its state.
 */
export function formatState( book ) {
	const readings = book.readings.length;
	const toRead = toBuy === book.status ? '' : __( 'To read', 'library' );
	const read =
		readings > 1
			? sprintf(
					/* translators: %s: number of readings of a book, an unknown date included, 2 or more. */
					_n( 'Read %s time', 'Read %s times', readings, 'library' ),
					formatNumber( readings )
			  )
			: __( 'Read', 'library' );
	const state = book.read ? read : toRead;

	return join( [
		book.in_progress ? __( 'In progress', 'library' ) : state,
		formatRating( book.rating ),
	] );
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
