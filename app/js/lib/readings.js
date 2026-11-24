/**
 * The readings of a book between the API and the app: as the API gives them and takes them, by their days as the forms
 * give them, and the reading of a book by the reader, to put it back as it was.
 */

/**
 * @import { ApiReading, Book, BookReadings, EndedReading, ShelfValues } from 'library-app/api'
 */

/**
 * The reading of a book by the reader: their copy, as the route of its shelf takes it, the values that change only, and
 * where it goes for them, empty to take it off their shelves.
 *
 * @typedef {Object} BookReading
 * @property {ShelfValues} copy   Their copy, its readings by their days, each with its rating, the one in progress last.
 * @property {string}      status Where it is for them, empty on none of their shelves.
 */

// The end of a reading in progress, which has none yet, as an interval of ISO 8601-2 left open: 2026-11-08/..
export const IN_PROGRESS = '..';
// How a reading ended, abandoned, in the choice of its rating on its row of a form, rather than a rating.
export const ABANDONED = 'abandoned';

/**
 * Readings as the API gives them, in order, as the app reads them: the ones ended, an unknown start or end empty, and the
 * start of the one in progress.
 *
 * @param {ApiReading[]} items Readings, from the first to the latest, the one in progress last.
 * @return {BookReadings} The readings ended and the start of the one in progress.
 */
export function toReadings( items ) {
	const all = items.map( ( { start, end, rating, abandoned } ) => ( {
		start: start ?? '',
		end: end ?? '',
		rating,
		abandoned: abandoned ?? false,
	} ) );

	return {
		readings: all.filter( ( { end } ) => IN_PROGRESS !== end ),
		started: all.find( ( { end } ) => IN_PROGRESS === end )?.start ?? '',
	};
}

/**
 * A reading as the API takes it: an unknown start or end null.
 *
 * @param {EndedReading} reading The reading.
 * @return {ApiReading} The reading of the API.
 */
export function toApiReading( { start, end, rating, abandoned } ) {
	return { start: start || null, end: end || null, rating, abandoned };
}

/**
 * The reading in progress as the API takes it, since its start, empty when unknown.
 *
 * @param {string} started Its start.
 * @return {ApiReading} The reading of the API.
 */
export function toApiReadingInProgress( started ) {
	return {
		start: started || null,
		end: IN_PROGRESS,
		rating: null,
		abandoned: false,
	};
}

/**
 * A reading by its days, as the forms give it and send it: the server keeps the time of a start or an end of today, and
 * of one saved with its time given by its day.
 *
 * @param {EndedReading} reading A reading, its start and end with their times or not.
 * @return {EndedReading} Its days.
 */
export function toDays( { start, end, rating, abandoned } ) {
	return {
		start: start.slice( 0, 10 ),
		end: end.slice( 0, 10 ),
		rating,
		abandoned,
	};
}

/**
 * A reading as a row of the reading form takes it: an interval of days and its rating after a hash, or that it was
 * abandoned, 2025-03-10/2025-03-21#8, 2025-03-10/2025-03-14#abandoned, a side empty when unknown, no rating without one.
 *
 * @param {EndedReading} reading The reading.
 * @return {string} Its interval and its rating.
 */
export function toInterval( reading ) {
	const { start, end, rating, abandoned } = toDays( reading );
	const ending = abandoned ? ABANDONED : rating ?? '';

	return `${ start }/${ end }${ '' !== ending ? `#${ ending }` : '' }`;
}

/**
 * The readings of `book` by their days, as the form gives them, as the API takes them: the ones ended, each with its
 * rating, then the one in progress.
 *
 * @param {Book} book The book.
 * @return {ApiReading[]} Its readings.
 */
export function readingsOf( book ) {
	const ended = book.readings.map( ( reading ) =>
		toApiReading( toDays( reading ) )
	);

	return book.in_progress
		? [ ...ended, toApiReadingInProgress( book.started.slice( 0, 10 ) ) ]
		: ended;
}

/**
 * Readings in any order, to compare: the server keeps the time of a reading of the day.
 *
 * @param {ApiReading[]} readings The readings.
 * @return {string} Them, sorted, as JSON.
 */
export const sortedOf = ( readings ) =>
	JSON.stringify(
		readings.map( ( reading ) => JSON.stringify( reading ) ).sort()
	);

/**
 * The reading of `book`, to put it back as it was: whether it is read and being read, its readings by their days, as the
 * form gives them, with their ratings, when its copy was printed, their notes, the photo of their copy, and where it is
 * for the reader.
 *
 * @param {Book} book The book.
 * @return {BookReading} Its reading.
 */
export function readingOf( book ) {
	const copy = {
		read: book.read,
		in_progress: book.in_progress,
		readings: readingsOf( book ),
		printed_date: book.printed_date,
		notes: book.notes,
		cover: book.copy_cover,
	};

	return { copy, status: book.status };
}

/**
 * The readings of `book` finished, which read it: the ones ended but those abandoned.
 *
 * @param {Book} book The book.
 * @return {EndedReading[]} Its readings finished.
 */
export function finishedOf( book ) {
	return book.readings.filter( ( reading ) => ! reading.abandoned );
}

/**
 * Whether `book` is abandoned: a reading of it abandoned, none finished, none in progress.
 *
 * @param {Book} book The book.
 * @return {boolean} Whether it is abandoned.
 */
export function isAbandoned( book ) {
	return (
		! book.read &&
		! book.in_progress &&
		book.readings.some( ( reading ) => reading.abandoned )
	);
}
