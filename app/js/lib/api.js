/**
 * The REST API as the reading app asks it, through wp.apiFetch, which handles the root URL and the nonce and answers the
 * first requests from the page, which preloads them: each request with the paths the page preloads, and what comes back
 * in plain text once, so that no brick parses what the API returns. It keeps no state: the store does.
 */

import {
	__,
	addQueryArgs,
	apiFetch,
	bookFields,
	maxPerPage,
	perPage,
	roles,
	statuses,
} from 'library-app/app';

/**
 * A term as a book or a page names it: a person, a series, a collection…
 *
 * @typedef {Object} Term
 * @property {number} id   Its term ID.
 * @property {string} name Its name, in plain text.
 */

/**
 * The image of a cover, as the API gives it: with its width and height, which make its place known before it loads,
 * and its sizes; a photo picked for a book to add has its URL only.
 *
 * @typedef {Object} Cover
 * @property {string} url      URL of the image.
 * @property {number} [width]  Its width, in pixels.
 * @property {number} [height] Its height, in pixels.
 * @property {string} [srcset] Its sizes, as a srcset.
 */

/**
 * The meta of a book by their names, without the prefix of their keys: library_rating is its rating.
 *
 * @typedef {Object} BookMeta
 * @property {string}   subtitle          Its subtitle.
 * @property {string}   isbn              Its ISBN, as typed.
 * @property {string}   issn              Its ISSN, for a magazine.
 * @property {string}   volume_number     Its number in its series.
 * @property {string}   collection_number Its number in its collection.
 * @property {string}   edition_date      The date of its edition: a day, a month or a year.
 * @property {string}   published_date    The date it first came out: a day, a month or a year.
 * @property {string[]} read_date         Its readings, from the first to the latest, an unknown date empty.
 * @property {number}   pages             Its number of pages, 0 for unknown.
 * @property {number}   rating            Its rating out of 10, 0 for none.
 * @property {boolean}  collective        Whether it is a collective work.
 * @property {boolean}  read              Whether it is read.
 * @property {boolean}  in_progress       Whether it is being read.
 * @property {number[]} edition_languages The term IDs of the languages of its text.
 */

/**
 * The names of a book with another role than author, by role, as the app names them.
 *
 * @typedef {Object} BookRoles
 * @property {Term[]} translators     Its translators.
 * @property {Term[]} colorists       Its colorists.
 * @property {Term[]} inkers          Its inkers.
 * @property {Term[]} letterers       Its letterers.
 * @property {Term[]} directors       The directors of what it adapts, such as a film.
 * @property {Term[]} adapted_authors The authors of what it adapts.
 */

/**
 * A book as the app shows it, besides its meta and the names with another role.
 *
 * @typedef {Object} BookFields
 * @property {number}     id                Its ID.
 * @property {string}     title             Its title.
 * @property {string}     excerpt           Its summary in a few words.
 * @property {string}     content           Its notes, HTML as the API renders them.
 * @property {string}     credits           Its authors, publisher and year, under its title.
 * @property {string}     status            Its status: in the library, not kept, to buy, or another one, such as a draft.
 * @property {Cover|null} cover             Its cover, none without one.
 * @property {Term[]}     authors           Its names without another role.
 * @property {string}     series            Its series after its cycles, "Star Wars, Young Jedi Knights", or empty.
 * @property {number}     series_id         The term ID of its series, 0 without one.
 * @property {string[]}   languages         Its original languages, as their ISO 639 codes.
 * @property {string[]}   edition_languages The languages of its text, as their ISO 639 codes.
 * @property {string}     form              The name of its form, such as a novel, or empty.
 */

/**
 * A book as the app shows it: plain text but its notes, its meta by their names, its read dates in order, its cover
 * with its size, its authors and the names with another role, its original languages, those of its text and its form.
 *
 * @typedef {BookFields & Omit<BookMeta, 'edition_languages'> & BookRoles} Book
 */

/**
 * A name of a book as its form edits it, with its role, such as colorist.
 *
 * @typedef {Object} Credit
 * @property {string} name Its name, as typed.
 * @property {string} role Its role, author or another one.
 */

/**
 * What the form of a book sends, the values typed: its terms by their names, a name not found added.
 *
 * @typedef {Object} BookValues
 * @property {string}                  title            Its title.
 * @property {string}                  excerpt          Its summary in a few words.
 * @property {string}                  content          Its notes.
 * @property {Credit[]}                people           Its names, each with its role.
 * @property {string}                  publisher        The name of its publisher.
 * @property {string}                  collection       Its collection, after the collections it is part of.
 * @property {string}                  series           Its series, after its cycles.
 * @property {number[]}                languages        The term IDs of its original languages.
 * @property {number}                  form             The term ID of its form, 0 for none.
 * @property {Object<string, unknown>} meta             Its meta, by their keys, library_isbn… and its edition languages.
 * @property {string}                  [status]         The status of a book to add.
 * @property {number}                  [featured_media] The ID of the image of its cover, 0 for none.
 */

/**
 * The values of a book as its form edits them, from the route of the book form.
 *
 * @typedef {Object} BookForm
 * @property {number}   id         Its ID.
 * @property {string}   title      Its title.
 * @property {string}   excerpt    Its summary in a few words.
 * @property {string}   content    Its notes.
 * @property {Credit[]} people     Its names, each with its role.
 * @property {string}   publisher  The name of its publisher.
 * @property {string}   collection Its collection, after the collections it is part of.
 * @property {string}   series     Its series, after its cycles.
 * @property {number[]} languages  The term IDs of its original languages.
 * @property {number}   form       The term ID of its form, 0 for none.
 * @property {FormMeta} meta       Its meta, by their keys, its read dates aside.
 */

/**
 * The meta of a book as its form edits them, by their keys: library_isbn… and the term IDs of its edition languages.
 *
 * @typedef {Object<string, string|boolean> & {library_edition_languages: number[]}} FormMeta
 */

/**
 * A book to add as the catalogue of the BnF describes it, as its form takes it, with the names of the library.
 *
 * @typedef {Omit<BookForm, 'id'|'excerpt'|'content'>} CatalogBook
 */

/**
 * What leads from an achievement to the app: a book, a person, a series, a collection, or a search.
 *
 * @typedef {Object} Target
 * @property {string} type     "book", "author", "series", "collection" or "search".
 * @property {number} [id]     The ID of its book or its term.
 * @property {string} [search] The search of the books, for a search.
 */

/**
 * An achievement earned, as the app shows it.
 *
 * @typedef {Object} Achievement
 * @property {string}      title    Its title.
 * @property {string}      subject  What it is about, such as a series, or empty.
 * @property {string}      detail   Its detail, such as a number of books.
 * @property {string}      [date]   When it was earned.
 * @property {Target|null} [target] What its subject leads to, if anything.
 */

/**
 * An achievement close to be earned, as the app shows it, with its progress.
 *
 * @typedef {Object} CloseAchievement
 * @property {string}      title    Its title.
 * @property {string}      subject  What it is about, such as a series, or empty.
 * @property {string}      detail   Its detail, such as the books left.
 * @property {number}      done     How much of it is done.
 * @property {number}      total    How much it takes.
 * @property {Target|null} [target] What its subject leads to, if anything.
 */

/**
 * The achievements: those earned, latest first, and those close to be earned.
 *
 * @typedef {Object} Achievements
 * @property {Achievement[]}      earned Those earned.
 * @property {CloseAchievement[]} close  Those close to be earned.
 */

/**
 * The reading stats: all the books, those read, and this year against the last one.
 *
 * @typedef {Object} Stats
 * @property {number}    total        The books.
 * @property {number}    read         The books read.
 * @property {number}    percent      The percentage of books read.
 * @property {YearStats} year_to_date This year against the last one.
 */

/**
 * The readings of the year to date, against the same date last year, and the pace.
 *
 * @typedef {Object} YearStats
 * @property {number}      year        This year.
 * @property {number}      this_year   The books read this year.
 * @property {number}      last_year   The books read by this date last year.
 * @property {number|null} projection  The books read by the end of the year at this pace, none without a pace.
 * @property {number}      to_read     The books to read.
 * @property {number|null} all_read_in The year they would all be read in at this pace, none without a pace.
 */

/**
 * A reading of the diary: its date, and the ID of its book.
 *
 * @typedef {Object} Reading
 * @property {string} date Its date, empty when unknown.
 * @property {number} id   The ID of its book.
 */

/**
 * A month of the diary, with its readings.
 *
 * @typedef {Object} Month
 * @property {string}    month    The month, "2026-10".
 * @property {Reading[]} readings Its readings, latest first.
 */

/**
 * A reading idea of the week's list, with the ID of its book.
 *
 * @typedef {Object} Idea
 * @property {number} id       The ID of its book.
 * @property {string} reason   Why it is an idea.
 * @property {number} position Its position in the week's list.
 * @property {number} total    The ideas of the week.
 */

/**
 * A name the form of a book suggests: a person or one of their other names, or a series, under its parent.
 *
 * @typedef {Object} Suggestion
 * @property {number} id     Its term ID.
 * @property {string} name   Its name; a series after its cycles.
 * @property {number} parent The term ID of its parent, 0 at the top.
 */

/**
 * A publisher, or a collection, as the form of a book suggests it.
 *
 * @typedef {Object} PublisherSuggestion
 * @property {number} id         Its term ID.
 * @property {string} name       Its name.
 * @property {number} parent     The term ID of its parent, 0 for a publisher.
 * @property {number} publisher  The term ID of its publisher, its own for a publisher.
 * @property {string} collection Its name after the collections it is part of, without its publisher.
 */

/**
 * The names the form of a book suggests.
 *
 * @typedef {Object} Suggestions
 * @property {Suggestion[]}          people     People and their other names.
 * @property {PublisherSuggestion[]} publishers Publishers and collections.
 * @property {Suggestion[]}          series     Series, after their cycles.
 */

/**
 * An author of a series or a collection, with how many of its books they wrote.
 *
 * @typedef {Object} Author
 * @property {number} id          Their term ID.
 * @property {string} name        Their name.
 * @property {string} description Their description, HTML.
 * @property {number} books       How many of its books they wrote.
 */

/**
 * A series of a person, with how many of their books it holds and are read.
 *
 * @typedef {Object} PersonSeries
 * @property {number} id          Its term ID.
 * @property {string} name        Its name, after its cycles.
 * @property {string} description Its description, HTML.
 * @property {number} books       How many of their books it holds.
 * @property {number} read        How many of these are read.
 */

/**
 * What the pages of a person, a series and a collection share: the name, its description, how many of its books are
 * read, the completion they count for, and the IDs of its books, in order.
 *
 * @typedef {Object} TermData
 * @property {string}                            name        Its name.
 * @property {string}                            description Its description, HTML.
 * @property {number}                            read        How many of its books are read.
 * @property {Achievement|CloseAchievement|null} completion  The completion its books count for, earned or close, if any.
 * @property {number[]}                          books       The IDs of its books, in order.
 */

/**
 * The page of a person, under the name they are known by.
 *
 * @typedef {Object} PersonFields
 * @property {string[]}       otherNames Their other names, such as a pseudonym.
 * @property {PersonSeries[]} series     Their series, those with the most of their books first.
 * @property {number|null}    rating     The average rating of their books, none without one.
 * @property {number}         bookPages  The pages of their books.
 */

/**
 * The page of a series, or of a cycle.
 *
 * @typedef {Object} SeriesFields
 * @property {Term|null} cycle   Its cycle, if any.
 * @property {Term[]}    parts   The series of a cycle.
 * @property {Author[]}  authors Its authors, who wrote the most first.
 */

/**
 * The page of a collection, or of a publisher.
 *
 * @typedef {Object} PublisherFields
 * @property {Term|null} publisher   Its publisher, for a collection.
 * @property {Term|null} collection  The collection it is part of, for a sub-collection.
 * @property {Term[]}    collections Its collections.
 * @property {Author[]}  authors     Its authors, who wrote the most first.
 */

/** @typedef {TermData & PersonFields} PersonData The page of a person, as the API gives it. */
/** @typedef {TermData & SeriesFields} SeriesData The page of a series, as the API gives it. */
/** @typedef {TermData & PublisherFields} PublisherData The page of a collection, as the API gives it. */

/** @typedef {'person'|'series'|'publisher'} PageView The views of the pages of a term. */

/**
 * A term of the books read in a year, such as a form or a person, with its readings.
 *
 * @typedef {Object} RecapTerm
 * @property {number} id       Its term ID.
 * @property {string} name     Its name.
 * @property {number} readings Its readings in the year.
 */

/**
 * A series read in a year, with its readings: started when first read in the year, finished when every book of it is read, the last one in the year.
 *
 * @typedef {RecapTerm & {started: boolean, finished: boolean}} RecapSeries
 */

/**
 * The recap of a year: a reading is a read date of the year, a book read again counting once per reading; the books
 * read, the ratings and the records take each book once.
 *
 * @typedef {Object} Recap
 * @property {number}                                              year         The year.
 * @property {number[]}                                            years        The years with a reading, the earliest first.
 * @property {number}                                              days         Its days gone by: all of them for a year gone.
 * @property {number}                                              readings     Its readings.
 * @property {number}                                              previous     The readings of the year before.
 * @property {number}                                              rereads      Its readings of a book read again in the year.
 * @property {number[]}                                            books        The IDs of the books read, in the order they were read.
 * @property {{total: number, counted: number}}                    pages        The pages read, and the readings of a book with its number of pages.
 * @property {number[]}                                            months       The readings of each month, from January.
 * @property {RecapTerm[]}                                         forms        The forms read, the most read first.
 * @property {RecapTerm[]}                                         people       The people read the most, by the name they are known by.
 * @property {Term[]}                                              discoveries  The people read for the first time, in the order they were read.
 * @property {{code: string, readings: number}[]}                  languages    The original languages, as ISO 639 codes, the most read first.
 * @property {RecapSeries[]}                                       series       The series read, the most read first.
 * @property {number|null}                                         rating       The average rating of the books read, none without one.
 * @property {number[]}                                            best         The IDs of the books read rated the highest.
 * @property {{longest: number, shortest: number, oldest: number}} records      The IDs of the longest, the shortest and the oldest books read, 0 for none.
 * @property {Achievement[]}                                       achievements The achievements earned in the year.
 * @property {number}                                              added        The books added in the year, but those to buy.
 */

/**
 * A book as the REST API of core gives it, with the fields of the plugin, those the app asks for.
 *
 * @typedef {Object} ApiBook
 * @property {number}                  id                     Its ID.
 * @property {{rendered: string}}      title                  Its title, HTML.
 * @property {{rendered: string}}      excerpt                Its excerpt, HTML.
 * @property {{rendered: string}}      content                Its notes, HTML.
 * @property {string}                  credits                Its credits, HTML.
 * @property {string}                  status                 Its status.
 * @property {Object<string, unknown>} meta                   Its meta, by their keys.
 * @property {Cover|null}              cover                  Its cover, none without one.
 * @property {Term[]}                  names                  Its names, HTML, each with its term ID.
 * @property {number[]}                series                 The term IDs of its series.
 * @property {string}                  series_name            Its series after its cycles, HTML.
 * @property {string[]}                language_codes         Its original languages, as their ISO 639 codes.
 * @property {string[]}                edition_language_codes The languages of its text, as their ISO 639 codes.
 * @property {string}                  form_name              The name of its form, HTML.
 */

const parser = new DOMParser();

// The fields of a book the app reads, its cover and terms as the plugin gives them: nothing embedded, a list stays light.
const BOOK_QUERY = { _fields: bookFields };

/**
 * Plain text of an HTML string of the API, such as a title with &#8217;: never parsed as markup in the page.
 *
 * @param {string} html HTML of the API.
 * @return {string} Its text.
 */
export function text( html ) {
	return parser.parseFromString( html, 'text/html' ).body.textContent;
}

/**
 * Read dates as readings, from the first to the latest: each unknown date, empty, is a reading of its own, first; a
 * known date is one reading, as a date saved twice by an old import. As the server counts them.
 *
 * @param {string[]} dates Read dates, in any order.
 * @return {string[]} The readings.
 */
export function toReadings( dates ) {
	return [
		...dates.filter( ( date ) => '' === date ),
		...new Set( dates.filter( Boolean ) ),
	].sort();
}

/**
 * Read dates by their days, as the forms give them and send them, in order, the unknown ones first. Two readings of a
 * day stay two: the server keeps the time of each reading of today, and merges a day given twice otherwise.
 *
 * @param {string[]} dates Read dates, in any order, with their time or not.
 * @return {string[]} Their days.
 */
export function toDays( dates ) {
	return dates.map( ( date ) => date.slice( 0, 10 ) ).sort();
}

/**
 * The message of a failed request: the one of the API, or a generic one.
 *
 * @param {{message?: string}} error What the request threw: an error, or the one of the API.
 * @return {string} Its message.
 */
export function errorMessage( error ) {
	return error?.message ?? __( 'The books could not be loaded.', 'library' );
}

/**
 * A term the API gives, such as an author or a cycle, with its name in plain text; none stays none.
 *
 * @template {{name: string}} T
 * @param {T} term A term, or none.
 * @return {T} The term.
 */
function toPlainName( term ) {
	return term && { ...term, name: text( term.name ) };
}

/**
 * A book of the API as the app shows it: plain text but its notes, its meta by their names, its read dates in order, its cover with its size, its authors and the names with another role, its original languages and its form.
 *
 * @param {ApiBook} item A book of the API.
 * @return {Book} The book.
 */
function toBook( item ) {
	// Its meta without the prefix of their keys: library_rating is its rating.
	const meta = /** @type {BookMeta} */ (
		Object.fromEntries(
			Object.entries( item.meta ).map( ( [ key, value ] ) => [
				key.replace( /^library_/, '' ),
				value,
			] )
		)
	);
	// As named on the book: another name opens the page of the person too.
	const names = item.names.map( ( { id, name } ) => ( {
		id,
		name: text( name ),
	} ) );
	const roleIds = roles.flatMap( ( role ) => meta[ role ] );

	return {
		...meta,
		read_date: toReadings( meta.read_date ),
		id: item.id,
		title: text( item.title.rendered ),
		excerpt: text( item.excerpt.rendered ),
		// HTML as the API renders it: its paragraphs and links.
		content: item.content.rendered,
		credits: text( item.credits ),
		status: item.status,
		// Its size gives the image its place before it loads.
		cover: item.cover,
		// Its names without another role, then the others by role, such as its colorists, in place of their IDs.
		authors: names.filter( ( name ) => ! roleIds.includes( name.id ) ),
		.../** @type {BookRoles} */ (
			Object.fromEntries(
				roles.map( ( role ) => [
					role,
					names.filter( ( name ) =>
						meta[ role ].includes( name.id )
					),
				] )
			)
		),
		// After its cycles, as typed, "Star Wars, Young Jedi Knights", and its ID, for its page.
		series: item.series_name,
		series_id: item.series[ 0 ] ?? 0,
		// As their ISO 639 codes: the app names them in a sentence.
		languages: item.language_codes,
		edition_languages: item.edition_language_codes,
		// Its name, for the tag of its card.
		form: text( item.form_name ),
	};
}

/**
 * A book of the API as its form edits it: the names of its terms in plain text, the rest as typed.
 *
 * @template {CatalogBook} T
 * @param {T} item A book of the route of the book form, or of the catalogue of the BnF.
 * @return {T} Its values.
 */
function toForm( item ) {
	return {
		...item,
		people: item.people.map( toPlainName ),
		publisher: text( item.publisher ),
		collection: text( item.collection ),
	};
}

/**
 * The pages of a person, of a series and of a collection, by their view: their route, and what their state takes from
 * it, `books` and their count aside.
 */
const PAGES = {
	person: {
		route: 'people',
		from: ( data ) => ( {
			name: text( data.name ),
			otherNames: data.other_names.map( text ),
			description: data.description,
			series: data.series.map( toPlainName ),
			read: data.read,
			rating: data.rating,
			bookPages: data.pages,
			completion: data.completion,
		} ),
	},
	series: {
		route: 'series',
		from: ( data ) => ( {
			name: text( data.name ),
			cycle: toPlainName( data.cycle ),
			parts: data.series.map( toPlainName ),
			authors: data.authors.map( toPlainName ),
			description: data.description,
			read: data.read,
			completion: data.completion,
		} ),
	},
	publisher: {
		route: 'publishers',
		from: ( data ) => ( {
			name: text( data.name ),
			publisher: toPlainName( data.publisher ),
			collection: toPlainName( data.collection ),
			collections: data.collections.map( toPlainName ),
			authors: data.authors.map( toPlainName ),
			description: data.description,
			read: data.read,
			completion: data.completion,
		} ),
	},
};

/** The views of the pages of a person, of a series and of a collection. */
export const PAGE_VIEWS = /** @type {PageView[]} */ ( Object.keys( PAGES ) );

/**
 * The books `ids`, in the order of their IDs, as the page preloads the books of the diary: whatever is loaded already, the
 * request is the same. A page of the API at a time.
 *
 * @param {number[]}    ids              Book IDs.
 * @param {Object}      [options]        Options.
 * @param {AbortSignal} [options.signal] Cancels the requests.
 * @return {Promise<Book[]>} The books.
 */
export async function getBooks( ids, { signal } = {} ) {
	const sorted = [ ...new Set( ids ) ].sort( ( a, b ) => a - b );
	const chunks = Array.from(
		{ length: Math.ceil( sorted.length / maxPerPage ) },
		( value, index ) =>
			sorted.slice( index * maxPerPage, ( index + 1 ) * maxPerPage )
	);
	/** @type {ApiBook[][]} */
	const loaded = await Promise.all(
		chunks.map( ( chunk ) => {
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				status: statuses,
				include: chunk.join( ',' ),
				per_page: chunk.length.toString(),
			} );

			return apiFetch( { path: `/wp/v2/books?${ params }`, signal } );
		} )
	);

	return loaded.flat().map( toBook );
}

/**
 * The book `id` from the API, though it is loaded, as it may have changed elsewhere.
 *
 * @param {number} id Book ID.
 * @return {Promise<Book>} The book.
 */
export async function getBook( id ) {
	return toBook(
		await apiFetch( {
			path: `/wp/v2/books/${ id }?${ new URLSearchParams( BOOK_QUERY ) }`,
		} )
	);
}

/**
 * The books `query` finds from `offset`, `count` of them, and how many it finds in all: `query` as the API reads it, its
 * search, statuses, forms and order.
 *
 * @param {Object<string, string>} query            The query of the books.
 * @param {Object}                 [options]        Options.
 * @param {number}                 [options.offset] The first book, from 0.
 * @param {number}                 [options.count]  How many books.
 * @param {AbortSignal}            [options.signal] Cancels the request.
 * @return {Promise<{books: Book[], total: number}>} The books, and how many it finds in all.
 */
export async function findBooks(
	query,
	{ offset = 0, count = perPage, signal } = {}
) {
	const params = new URLSearchParams( {
		...BOOK_QUERY,
		...query,
		per_page: count.toString(),
		offset: offset.toString(),
	} );
	const response = await apiFetch( {
		path: `/wp/v2/books?${ params }`,
		parse: false,
		signal,
	} );

	return {
		books: ( await response.json() ).map( toBook ),
		total: Number( response.headers.get( 'X-WP-Total' ) ),
	};
}

/**
 * Whether `query` finds the book `id`.
 *
 * @param {Object<string, string>} query The query of the books.
 * @param {number}                 id    Book ID.
 * @return {Promise<boolean>} Whether it finds it.
 */
export async function finds( query, id ) {
	const params = new URLSearchParams( {
		...query,
		include: id.toString(),
		_fields: 'id',
	} );
	/** @type {Array<{id: number}>} */
	const found = await apiFetch( { path: `/wp/v2/books?${ params }` } );

	return found.length > 0;
}

/**
 * The books of the series `name`, all of them, in reading order, as a search for it lists them.
 *
 * @param {string}      name             The series, after its cycles.
 * @param {Object}      [options]        Options.
 * @param {AbortSignal} [options.signal] Cancels the request.
 * @return {Promise<Book[]>} Its books.
 */
export async function getVolumes( name, { signal } = {} ) {
	const params = new URLSearchParams( {
		...BOOK_QUERY,
		search: `series:"${ name }"`,
		status: statuses,
		// Every page, however many volumes: apiFetch loads the first one, as the page preloads it, then the others.
		per_page: '-1',
	} );
	/** @type {ApiBook[]} */
	const books = await apiFetch( {
		path: `/wp/v2/books?${ params }`,
		signal,
	} );

	return books.map( toBook );
}

/**
 * The book that has the ISBN `digits`, or none.
 *
 * @param {string} digits An ISBN, its digits only.
 * @return {Promise<number>} The ID of the book, 0 for none.
 */
export async function findIsbn( digits ) {
	const params = new URLSearchParams( {
		search: `isbn:${ digits }`,
		status: statuses,
		per_page: '1',
		_fields: 'id',
	} );
	/** @type {Array<{id: number}>} */
	const [ found ] = await apiFetch( { path: `/wp/v2/books?${ params }` } );

	return found?.id ?? 0;
}

/**
 * The book of the ISBN `isbn` in the catalogue of the BnF, as its form takes it, or none when the catalogue has no book
 * of that ISBN.
 *
 * @param {string} isbn An ISBN.
 * @return {Promise<CatalogBook|null>} The book, or none.
 */
export async function findInBnf( isbn ) {
	const params = new URLSearchParams( { isbn } );

	try {
		/** @type {CatalogBook} */
		const book = await apiFetch( {
			path: `/library/v1/books/bnf?${ params }`,
		} );

		return toForm( book );
	} catch ( error ) {
		if ( 'library_not_in_bnf' === error?.code ) {
			return null;
		}

		throw error;
	}
}

/**
 * A page of the diary: whole months, latest first, each with its readings and the IDs of their books; the totals count
 * months.
 *
 * @param {number} page The page, from 1.
 * @return {Promise<{months: Month[], total: number, pages: number}>} Its months, and how many months and pages in all.
 */
export async function getReadings( page ) {
	const response = await apiFetch( {
		path: `/library/v1/books/readings?page=${ page }`,
		parse: false,
	} );
	const months = ( await response.json() ).map( ( { month, readings } ) => ( {
		month,
		readings: readings.map( ( { date, book } ) => ( { date, id: book } ) ),
	} ) );

	return {
		months,
		total: Number( response.headers.get( 'X-WP-Total' ) ),
		pages: Number( response.headers.get( 'X-WP-TotalPages' ) ),
	};
}

/**
 * The idea at `position` in the week's list, with the ID of its book.
 *
 * @param {number} position Its position, from 0.
 * @return {Promise<Idea>} The idea.
 */
export function getIdea( position ) {
	return apiFetch( {
		path: `/library/v1/books/reading-idea?position=${ position }`,
	} );
}

/**
 * The page `view` of the term `id`, "person", "series" or "publisher": what its state takes, and the IDs of its books, in order.
 *
 * @param {PageView}    view             The view of the page.
 * @param {number}      id               Term ID.
 * @param {Object}      [options]        Options.
 * @param {AbortSignal} [options.signal] Cancels the request.
 * @return {Promise<PersonData|SeriesData|PublisherData>} The page.
 */
export async function getPage( view, id, { signal } = {} ) {
	const { route, from } = PAGES[ view ];
	/** @type {{books: number[]}} */
	const data = await apiFetch( {
		path: `/library/v1/${ route }/${ id }`,
		signal,
	} );

	return { ...from( data ), books: data.books };
}

/**
 * The recap of `year`, its names in plain text.
 *
 * @param {number}      year             The year.
 * @param {Object}      [options]        Options.
 * @param {AbortSignal} [options.signal] Cancels the request.
 * @return {Promise<Recap>} The recap.
 */
export async function getRecap( year, { signal } = {} ) {
	/** @type {Recap} */
	const recap = await apiFetch( {
		path: `/library/v1/recaps/${ year }`,
		signal,
	} );

	return {
		...recap,
		forms: recap.forms.map( toPlainName ),
		people: recap.people.map( toPlainName ),
		discoveries: recap.discoveries.map( toPlainName ),
		series: recap.series.map( toPlainName ),
	};
}

/**
 * The achievements earned and close to be earned.
 *
 * @return {Promise<Achievements>} The achievements.
 */
export function getAchievements() {
	return apiFetch( { path: '/library/v1/books/achievements' } );
}

/**
 * The reading stats.
 *
 * @return {Promise<Stats>} The stats.
 */
export function getStats() {
	return apiFetch( { path: '/library/v1/books/reading-stats' } );
}

/**
 * Every person and their other names, publisher and collection, with its publisher and after the collections it is part of,
 * and series after its cycles: apiFetch loads all their pages. The original languages are in the page.
 *
 * @return {Promise<Suggestions>} The names to suggest.
 */
export async function getTerms() {
	const bases = [ 'people', 'publishers', 'series' ];
	/** @type {Suggestion[][]} */
	const [ people, publishers, series ] = await Promise.all(
		bases.map( ( base ) =>
			apiFetch( {
				path: `/wp/v2/${ base }?per_page=-1&_fields=id,name,parent`,
			} )
		)
	);
	/** @type {( term: Suggestion ) => Suggestion} */
	const toTerm = ( { id, name, parent } ) => ( {
		id,
		name: text( name ),
		parent,
	} );
	const byId = Object.fromEntries(
		series.map( ( term ) => [ term.id, toTerm( term ) ] )
	);
	// A series after its cycles, as a book types it.
	/** @type {( term: Suggestion ) => string} */
	const pathOf = ( term ) =>
		term.parent in byId
			? `${ pathOf( byId[ term.parent ] ) }, ${ term.name }`
			: term.name;
	const publishersById = Object.fromEntries(
		publishers.map( ( term ) => [ term.id, toTerm( term ) ] )
	);
	/** @type {( term: Suggestion ) => Suggestion} */
	const publisherOf = ( term ) =>
		term.parent in publishersById
			? publisherOf( publishersById[ term.parent ] )
			: term;
	// A collection after the collections it is part of, without its publisher, as a book types it.
	/** @type {( term: Suggestion ) => string} */
	const collectionOf = ( term ) =>
		publishersById[ term.parent ]?.parent
			? `${ collectionOf( publishersById[ term.parent ] ) }, ${
					term.name
			  }`
			: term.name;

	return {
		people: people.map( toTerm ),
		publishers: Object.values( publishersById ).map( ( term ) => ( {
			...term,
			publisher: publisherOf( term ).id,
			collection: collectionOf( term ),
		} ) ),
		series: Object.values( byId ).map( ( term ) => ( {
			...term,
			name: pathOf( term ),
		} ) ),
	};
}

/**
 * The values of the book `id` as its form edits them.
 *
 * @param {number} id Book ID.
 * @return {Promise<BookForm>} Its values.
 */
export async function getForm( id ) {
	/** @type {BookForm} */
	const book = await apiFetch( { path: `/library/v1/books/${ id }` } );

	return toForm( book );
}

/**
 * Saves `data` to the book `id`, through the route of the book form, and returns the book as it is now, as the route gives it back.
 *
 * @param {number}              id   Book ID.
 * @param {Partial<BookValues>} data What changes, as the route takes it.
 * @return {Promise<Book>} The book.
 */
export async function saveBook( id, data ) {
	return toBook(
		await apiFetch( {
			path: `/library/v1/books/${ id }?${ new URLSearchParams(
				BOOK_QUERY
			) }`,
			method: 'POST',
			data,
		} )
	);
}

/**
 * Adds a book, and returns it, as the route gives it back.
 *
 * @param {BookValues} data The book, as the route takes it.
 * @return {Promise<Book>} The book.
 */
export async function addBook( data ) {
	return toBook(
		await apiFetch( {
			path: `/library/v1/books?${ new URLSearchParams( BOOK_QUERY ) }`,
			method: 'POST',
			data,
		} )
	);
}

/**
 * Uploads the photo of a cover, named and titled after its book as its form sends it, and returns the ID of its image.
 *
 * @param {File}                    file          The photo picked.
 * @param {Object}                  values        The values of its book, as its form sends them.
 * @param {string}                  values.title  Its title.
 * @param {Credit[]}                values.people Its names, each with its role.
 * @param {string}                  values.series Its series, after its cycles.
 * @param {Object<string, unknown>} values.meta   Its meta, by their keys.
 * @return {Promise<number>} The ID of the image.
 */
export async function uploadCover( file, { title, people, series, meta } ) {
	const query = { title, people, series, meta, photo: file.name };
	/** @type {{name: string}} */
	const { name } = await apiFetch( {
		path: addQueryArgs( '/library/v1/books/cover-name', query ),
	} );
	const body = new FormData();

	body.append( 'file', file, name );
	body.append( 'title', title );

	/** @type {{id: number}} */
	const image = await apiFetch( {
		path: '/wp/v2/media',
		method: 'POST',
		body,
	} );

	return image.id;
}
