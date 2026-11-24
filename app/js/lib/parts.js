/**
 * The parts of the state of the store that the API fills, or that a form fills, empty: how each starts, and how it
 * stands as it loads.
 */

/**
 * @import { Achievements, BookForm, Calendar, CatalogBook, Idea, PersonData, PublisherData, Recap, SeriesData, Suggestions } from 'library-app/api'
 * @import { ApiRoute, ApiTry } from 'library-app/reference'
 */

/**
 * A list loaded by pages: its items so far, and where it stands.
 *
 * @template [T=number]
 * @typedef {Object} Pages
 * @property {T[]}     items    Its items so far, such as the IDs of its books.
 * @property {number}  total    How many it has in all.
 * @property {number}  page     The last page loaded, 0 for none.
 * @property {number}  pages    How many pages it has.
 * @property {boolean} loading  Whether it is loading.
 * @property {boolean} renewing Whether it loads again from its start, its items shown until then.
 * @property {boolean} loaded   Whether it was loaded, or could not be.
 * @property {string}  error    Why it could not be loaded, or empty.
 */

/**
 * How a resource stands: whether it is loading, was loaded, or why it could not be.
 *
 * @typedef {Object} Loading
 * @property {boolean} loading Whether it is loading.
 * @property {boolean} loaded  Whether it was loaded, or could not be.
 * @property {string}  error   Why it could not be loaded, or empty.
 */

/**
 * The volumes of the series of the book open.
 *
 * @typedef {Pages & {name: string}} Volumes
 */

/**
 * The saving of the reading of the book open.
 *
 * @typedef {Object} Saving
 * @property {boolean} loading Whether it is saving.
 * @property {string}  error   Why it could not be saved, or empty.
 */

/**
 * The form of a book, to add one or to edit one.
 *
 * @typedef {Object} Editing
 * @property {number}        id        The book edited, 0 for a book to add.
 * @property {BookForm|null} book      The values of the book edited, none until loaded.
 * @property {boolean}       open      Whether it is open.
 * @property {boolean}       loading   Whether it is saving.
 * @property {string}        error     Why it could not be loaded or saved, or empty.
 * @property {number}        duplicate The book that has the ISBN typed for a book to add, 0 for none.
 * @property {Catalog}       catalog   The book of that ISBN in the catalogue of the BnF, for a book to add.
 */

/**
 * The search of the ISBN typed for a book to add in the catalogue of the BnF.
 *
 * @typedef {Object} Catalog
 * @property {string}                                         isbn   The ISBN searched, its digits only, empty for none.
 * @property {''|'searching'|'found'|'unknown'|'unreachable'} status Where the search is: the catalogue has the book or not, or did not answer.
 * @property {CatalogBook|null}                               book   The book found, else none.
 */

/** @typedef {Pages & PersonData & {id: number}} PersonPage The page of the person open. */
/** @typedef {Pages & SeriesData & {id: number}} SeriesPage The page of the series open. */
/** @typedef {Pages & PublisherData & {id: number}} PublisherPage The page of the collection open. */

/**
 * The reference of the API: its routes, and the requests tried from it, by endpoint.
 *
 * @typedef {Object} Reference
 * @property {ApiRoute[]}                                  routes Its routes.
 * @property {Object<string, ApiTry & {loading: boolean}>} tries  The requests tried, by the key of their endpoint.
 */

/**
 * The names the form of a book suggests, and whether they are loading or loaded.
 *
 * @typedef {Suggestions & Omit<Loading, 'error'>} Terms
 */

/**
 * A list loaded by pages: its items so far, and where it stands; `renewing` when it loads again from its start, its items shown until then.
 *
 * @template T
 * @param {T[]} [items] Its items so far.
 * @return {Pages<T>} The list.
 */
export function pages( items = [] ) {
	return {
		items,
		total: 0,
		page: 0,
		pages: 0,
		loading: false,
		renewing: false,
		loaded: false,
		error: '',
	};
}

/**
 * The reading idea, none loaded.
 *
 * @return {Idea & Loading} The idea.
 */
export function idea() {
	return {
		book_id: 0,
		reason: '',
		position: 0,
		idea_count: 0,
		loading: false,
		loaded: false,
		error: '',
	};
}

/**
 * The achievements, none loaded.
 *
 * @return {Achievements & Loading} The achievements.
 */
export function achievements() {
	return { earned: [], close: [], loading: false, loaded: false, error: '' };
}

/**
 * The reference of the API, none loaded.
 *
 * @return {Reference & Loading} The reference.
 */
export function reference() {
	return { routes: [], tries: {}, loading: false, loaded: false, error: '' };
}

/**
 * The recap of `year`, none loaded.
 *
 * @param {number} [year] The year.
 * @return {Recap & Loading} The recap.
 */
export function recap( year = 0 ) {
	return {
		year,
		years: [],
		day_count: 0,
		reading_count: 0,
		previous_reading_count: 0,
		reread_count: 0,
		books: [],
		pages: { total: 0, counted: 0 },
		months: [],
		forms: [],
		people: [],
		discoveries: [],
		languages: [],
		series: [],
		rating: null,
		best: [],
		records: { longest: 0, shortest: 0, oldest: 0 },
		achievements: [],
		added_count: 0,
		calendar: {
			levels: [],
			memorable: [],
			read_day_count: 0,
			streak: 0,
			best: null,
			dated_reading_count: 0,
		},
		loading: false,
		loaded: false,
		error: '',
	};
}

/**
 * What the calendar shows: a year, one of its months, or one of its days.
 *
 * @typedef {'year'|'month'|'day'} CalendarScale
 */

/**
 * What the calendar shows: its scale, the year, a month or a day, its year, its month, from 0, and its day of the month,
 * from 1.
 *
 * @typedef {Object} CalendarPeriod
 * @property {CalendarScale} scale Its scale.
 * @property {number}        year  Its year.
 * @property {number}        month Its month, from 0.
 * @property {number}        day   Its day of the month, from 1.
 */

/**
 * The calendar: the days of a year, and what it shows, the year, its month `month`, from 0, or its day `day` of that
 * month, from 1.
 *
 * @typedef {Calendar & Loading & CalendarPeriod} DiaryCalendar
 */

/**
 * What the calendar shows, from the ID of its URL, as calendarId() writes it: a year, 2026, a month, 202611, or a day,
 * 20261124; the current year for none, or for an ID of no day.
 *
 * @param {number} id The ID.
 * @return {CalendarPeriod} What it shows.
 */
export function calendarOf( id ) {
	const now = new Date();
	const digits = id.toString();
	const year = Number( digits.slice( 0, 4 ) );
	const month = Number( digits.slice( 4, 6 ) || '1' ) - 1;
	const day = Number( digits.slice( 6, 8 ) || '1' );
	const date = new Date( year, month, day );
	const scale = /** @type {CalendarScale|undefined} */ (
		{ 4: 'year', 6: 'month', 8: 'day' }[ digits.length ]
	);

	if (
		! scale ||
		date.getFullYear() !== year ||
		date.getMonth() !== month ||
		date.getDate() !== day
	) {
		return {
			scale: 'year',
			year: now.getFullYear(),
			month: now.getMonth(),
			day: now.getDate(),
		};
	}

	return {
		scale,
		year,
		month: 'year' === scale ? now.getMonth() : month,
		day: 'day' === scale ? day : now.getDate(),
	};
}

/**
 * The days of the year the calendar shows, from 0 for January 1st: from the first one, then up to the next one, out of
 * them, of the year, the month or the day shown.
 *
 * @param {DiaryCalendar} calendar The calendar.
 * @return {[number, number]} Its first day, and the one after its last.
 */
export function calendarDays( { scale, year, month, day, levels } ) {
	const dayOf = ( /** @type {number} */ m, /** @type {number} */ d ) =>
		( Date.UTC( year, m, d ) - Date.UTC( year, 0, 1 ) ) / 86400000;
	/** @type {Object<CalendarScale, [number, number]>} */
	const days = {
		year: [ 0, levels.length ],
		month: [ dayOf( month, 1 ), dayOf( month + 1, 1 ) ],
		day: [ dayOf( month, day ), dayOf( month, day + 1 ) ],
	};

	return days[ scale ];
}

/**
 * How many readings of the forms chosen ended in what the calendar shows, the year, the month or the day.
 *
 * @param {DiaryCalendar} shown The calendar.
 * @return {number} The readings.
 */
export function calendarReadingCount( shown ) {
	const [ first, last ] = calendarDays( shown );

	return Object.entries( shown.ends )
		.filter( ( [ day ] ) => Number( day ) >= first && Number( day ) < last )
		.reduce( ( count, [ , ids ] ) => count + ids.length, 0 );
}

/**
 * The readings of each form ended in what the calendar shows, the year, the month or the day, of every form, by term ID,
 * as its filter counts them.
 *
 * @param {DiaryCalendar} shown The calendar.
 * @return {Object<string, number>} The readings of each form.
 */
export function calendarFormCounts( shown ) {
	const [ first, last ] = calendarDays( shown );
	/** @type {Object<string, number>} */
	const counts = {};

	Object.entries( shown.form_ends )
		.filter( ( [ day ] ) => Number( day ) >= first && Number( day ) < last )
		.forEach( ( [ , forms ] ) =>
			Object.entries( forms ).forEach( ( [ form, count ] ) => {
				counts[ form ] = ( counts[ form ] ?? 0 ) + count;
			} )
		);

	return counts;
}

/**
 * The ID of the URL of what the calendar shows: its year, 2026, then its month, 202611, then its day, 20261124, as its
 * scale goes.
 *
 * @param {CalendarPeriod} period What it shows.
 * @return {number} The ID.
 */
export function calendarId( { scale, year, month, day } ) {
	const pad = ( /** @type {number} */ number ) =>
		number.toString().padStart( 2, '0' );

	return Number(
		{
			year: `${ year }`,
			month: `${ year }${ pad( month + 1 ) }`,
			day: `${ year }${ pad( month + 1 ) }${ pad( day ) }`,
		}[ scale ]
	);
}

/**
 * The calendar for `year`, empty, showing `scale`, the year, its month `month` or the day `day` of it.
 *
 * @param {number}        [year]  The year, the current one by default.
 * @param {CalendarScale} [scale] What it shows: the year, one of its months, or one of its days.
 * @param {number}        [month] The month it shows, from 0, the current one by default.
 * @param {number}        [day]   The day of the month it shows, from 1, today by default.
 * @return {DiaryCalendar} The calendar.
 */
export function calendar(
	year = new Date().getFullYear(),
	scale = 'year',
	month = new Date().getMonth(),
	day = new Date().getDate()
) {
	return {
		year,
		years: [],
		levels: [],
		pages: [],
		ends: {},
		reads: {},
		form_ends: {},
		scale,
		month,
		day,
		loading: false,
		loaded: false,
		error: '',
	};
}

/**
 * The volumes of the series `name` of the book open, in reading order.
 *
 * @param {string} [name] The series, after its cycles.
 * @return {Volumes} Its volumes.
 */
export function volumes( name = '' ) {
	return { ...pages(), name };
}

/**
 * The saving of the reading of the book open.
 *
 * @return {Saving} The saving.
 */
export function saving() {
	return { loading: false, error: '' };
}

/**
 * The form of a book, to add one or to edit the book `id`, whose values it holds as `book`, and its saving; `duplicate`
 * the book that has the ISBN typed for a book to add.
 *
 * @param {number}        [id]   The book edited, 0 for a book to add.
 * @param {BookForm|null} [book] Its values.
 * @return {Editing} The form.
 */
export function editing( id = 0, book = null ) {
	return {
		id,
		book,
		open: false,
		loading: false,
		error: '',
		duplicate: 0,
		catalog: { isbn: '', status: '', book: null },
	};
}

/**
 * The names the form of a book suggests, as terms: people and their other names, publishers and collections, series.
 *
 * @return {Terms} The names, none loaded.
 */
export function terms() {
	return {
		people: [],
		publishers: [],
		series: [],
		loading: false,
		loaded: false,
	};
}

/**
 * The page of a term open, its books loaded as a list, a page at a time, from all their IDs, `books`, in order; its
 * `wishes`, books to buy without a reading, few, loaded at once with it; `completion` is an achievement, a
 * `description` is HTML. The person open, under the name they are known by: `series` theirs, those with the most of
 * their books first, `bookPages` the pages of their books, as `pages` counts the pages of the list. The
 * series open, or the cycle: its name after its cycles, its `cycle`, its `parts`, the series of a cycle, its `authors`,
 * who wrote the most first, its books in reading order, those of its series with them. The collection open, or the
 * publisher: its name, a sub-collection after the collections it is part of, its `publisher`, the `collection` it is part
 * of, its `collections`, its `authors`, its books by collection, in the order of their number.
 */
const PAGE_STATES = {
	person: () => ( {
		otherNames: [],
		series: [],
		rating: null,
		bookPages: 0,
	} ),
	series: () => ( { cycle: null, issn: '', parts: [], authors: [] } ),
	publisher: () => ( {
		publisher: null,
		collection: null,
		collections: [],
		authors: [],
	} ),
};

/**
 * The page `view` of the term `id`, empty.
 *
 * @param {string} view The view of the page: "person", "series" or "publisher".
 * @param {number} [id] Term ID.
 * @return {PersonPage|SeriesPage|PublisherPage} The page.
 */
export function page( view, id = 0 ) {
	return {
		...pages(),
		id,
		name: '',
		description: '',
		books: [],
		wishes: [],
		read: 0,
		completion: null,
		...PAGE_STATES[ view ](),
	};
}
