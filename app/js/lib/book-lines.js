/**
 * The lines of a book in the language of the page: its state, its readings, its translation, whether it is read in its
 * original language and whether it is kept.
 */

import { __, _n, _x, notKept, sprintf, toBuy } from 'library-app/app';
import { getPeople } from 'library-app/api';
import {
	formatAnd,
	formatDate,
	formatLanguage,
	formatNumber,
	formatRating,
	join,
	pageLanguage,
} from 'library-app/format';

/** @import { Book } from 'library-app/api' */

const DAY_MS = 24 * 60 * 60 * 1000;

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
		.map( formatLanguage );

	if ( ! languages.length ) {
		return sprintf(
			/* translators: %s: list of the translators of a book. */
			__( 'Translated by %s', 'library' ),
			formatAnd( translators )
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

	return sprintf( format, formatAnd( languages ), formatAnd( translators ) );
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
 * That a book is read, how many times from two readings: "Read", "Read 2 times".
 *
 * @param {Book} book The book, read.
 * @return {string} How many times it is read.
 */
function formatReadCount( book ) {
	const readings = book.readings.length;

	return readings > 1
		? sprintf(
				/* translators: %s: number of readings of a book, an unknown date included, 2 or more. */
				_n( 'Read %s time', 'Read %s times', readings, 'library' ),
				formatNumber( readings )
		  )
		: __( 'Read', 'library' );
}

/**
 * Whether a book is being read, read, how many times from two readings, or to read unless it is to buy, and its rating.
 *
 * @param {Book} book The book.
 * @return {string} Its state.
 */
export function formatState( book ) {
	const toRead = toBuy === book.status ? '' : __( 'To read', 'library' );
	const state = book.read ? formatReadCount( book ) : toRead;

	return join( [
		book.in_progress ? __( 'In progress', 'library' ) : state,
		formatRating( book.rating ),
	] );
}

/**
 * How long the latest reading of a book took, when its start and its end are known: "12 days", the day it started and
 * the day it ended counted; empty otherwise.
 *
 * @param {Book} book The book.
 * @return {string} How long it took, or empty.
 */
function formatReadingTime( book ) {
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
 * The readings of a book, in a line: how many times it is read, the day its latest reading ended and how long it took,
 * when they are known: "Read 2 times · April 4, 2025 · in 12 days"; empty unread.
 *
 * @param {Book} book The book.
 * @return {string} Its readings, or empty.
 */
export function formatReadings( book ) {
	const reading = book.readings.at( -1 );

	if ( ! reading ) {
		return '';
	}

	const time = formatReadingTime( book );

	return join( [
		formatReadCount( book ),
		reading.end && formatDate( reading.end ),
		time &&
			sprintf(
				/* translators: %s: how long a reading took, e.g. 12 days. */
				__( 'in %s', 'library' ),
				time
			),
	] );
}
