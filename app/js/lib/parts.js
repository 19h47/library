/**
 * The parts of the state of the store that the API fills, or that a form fills, empty: how each starts, and how it
 * stands as it loads.
 */

/**
 * @import { Achievements, BookForm, CatalogBook, Idea, PersonData, PublisherData, Recap, SeriesData, Suggestions } from 'library-app/api'
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
