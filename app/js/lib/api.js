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
 * @property {string}   volume_number     Its number in its series.
 * @property {string}   collection_number Its number in its collection.
 * @property {string}   published_date    The date it first came out: a day, a month or a year.
 * @property {string}   edition_date      The date its edition, the one of its ISBN, first came out: a day, a month or a year.
 * @property {string}   printed_date      The date its copy was printed: a day, a month or a year.
 * @property {number}   pages             Its number of pages, 0 for unknown.
 * @property {number}   rating            Its rating out of 10, the one of its latest reading rated, 0 for none.
 * @property {boolean}  collective        Whether it is a collective work.
 * @property {boolean}  read              Whether it is read.
 * @property {boolean}  in_progress       Whether it is being read.
 * @property {number[]} edition_languages The term IDs of the languages of its text.
 */

/**
 * A name of a book, as named on it, with its role and its term ID, as the API gives it: library-book-person.
 *
 * @typedef {Credit & {id: number}} Person
 */

/**
 * A book as the app shows it, besides its meta.
 *
 * @typedef {Object} BookFields
 * @property {number}     id                Its ID.
 * @property {string}     title             Its title, with its series, its volume and its subtitle.
 * @property {string}     main_title        Its title but its subtitle, which its page shows above it.
 * @property {string}     excerpt           Its summary in a few words.
 * @property {string}     content           Its notes, HTML as the API renders them.
 * @property {string}     credits           Its authors, publisher and year, under its title.
 * @property {string}     status            Its status: in the library, not kept, to buy, or another one, such as a draft.
 * @property {Cover|null} cover             Its cover, none without one.
 * @property {Person[]}   people            Its names, each with its role.
 * @property {string}     series            Its series after its cycles, "Star Wars, Young Jedi Knights", or empty.
 * @property {number}     series_id         The term ID of its series, 0 without one.
 * @property {string[]}   languages         Its original languages, as their ISO 639 codes.
 * @property {string[]}   edition_languages The languages of its text, as their ISO 639 codes.
 * @property {string}     form              The name of its form, such as a novel, or empty.
 * @property {Term[]}     publishers        Its publishers, each followed by the collections it is in under it, from the widest.
 * @property {string}     added_date        The date it was added to the library.
 */

/**
 * A reading as the API gives it and takes it: its start and its end, null when unknown, its end “..” while it is read,
 * and the rating it ended with, null without.
 *
 * @typedef {Object} ApiReading
 * @property {string|null} start  Its start, or null.
 * @property {string|null} end    Its end, “..” while it is read, or null.
 * @property {number|null} rating The rating it ended with, or null.
 */

/**
 * A reading ended, from its start to its end, either empty when it is unknown: their days, or their times when the reading
 * started or ended the day it was saved; and the rating it ended with.
 *
 * @typedef {Object} EndedReading
 * @property {string}      start  Its start, or empty.
 * @property {string}      end    Its end, or empty.
 * @property {number|null} rating The rating it ended with, none without.
 */

/**
 * The readings of a book: the ones ended, from the first to the latest, and the start of the one in progress.
 *
 * @typedef {Object} BookReadings
 * @property {EndedReading[]} readings Its readings ended, from the first to the latest, the ones of an unknown end first.
 * @property {string}         started  The start of its reading in progress, empty without one or when it is unknown.
 */

/**
 * A book as the app shows it: plain text but its notes, its meta by their names, its readings in order, its cover with
 * its size, its names with their roles, its original languages, those of its text and its form.
 *
 * @typedef {BookFields & Omit<BookMeta, 'edition_languages'> & BookReadings} Book
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
 * @property {Object<string, unknown>} meta             Its meta, by their names, isbn… and its edition languages.
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
 * The meta of a book as its form edits them, by their names, as the REST API of the plugin names them: isbn… and the
 * term IDs of its edition languages.
 *
 * @typedef {Object<string, string|boolean> & {edition_languages: number[]}} FormMeta
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
 * @property {number}    book_count   The books.
 * @property {number}    read_count   The books read.
 * @property {number}    percent      The percentage of books read.
 * @property {YearStats} year_to_date This year against the last one.
 */

/**
 * The readings of the year to date, against the same date last year, and the pace.
 *
 * @typedef {Object} YearStats
 * @property {number}      year                 This year.
 * @property {number}      read_count           The books read this year.
 * @property {number}      last_year_read_count The books read by this date last year.
 * @property {number|null} projected_read_count The books read by the end of the year at this pace, none without a pace.
 * @property {number}      to_read_count        The books to read.
 * @property {number|null} all_read_in          The year they would all be read in at this pace, none without a pace.
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
 * @property {number} book_id    The ID of its book.
 * @property {string} reason     Why it is an idea.
 * @property {number} position   Its position in the week's list.
 * @property {number} idea_count The ideas of the week.
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
 * @property {number} book_count  How many of its books they wrote.
 */

/**
 * A series of a person, with how many of their books it holds and are read.
 *
 * @typedef {Object} PersonSeries
 * @property {number} id          Its term ID.
 * @property {string} name        Its name, after its cycles.
 * @property {string} description Its description, HTML.
 * @property {number} book_count  How many of their books it holds.
 * @property {number} read_count  How many of these are read.
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
 * @property {string}    issn    Its ISSN, for a magazine, or empty.
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
 * @property {number} id            Its term ID.
 * @property {string} name          Its name.
 * @property {number} reading_count Its readings in the year.
 */

/**
 * A series read in a year, with its readings: started when first read in the year, finished when every book of it is read, the last one in the year.
 *
 * @typedef {RecapTerm & {started: boolean, finished: boolean}} RecapSeries
 */

/**
 * The days of a year gone by, from January 1st: a day is read while a reading ended was open, or on its end alone when
 * its start is unknown, the pages of a book read from a known start spread over its days.
 *
 * @typedef {Object} RecapCalendar
 * @property {number[]}                          levels              The level of each day, 0 for none read, then 1 to 4 by its pages.
 * @property {number[]}                          memorable           The memorable days, by their index: the biggest one, and those a favourite was finished.
 * @property {number}                            read_day_count      The days read.
 * @property {number}                            streak              The most days read in a row.
 * @property {{day: number, pages: number}|null} best                The day the most pages were read, by its index, none without pages.
 * @property {number}                            dated_reading_count The readings ended in the year with their start known.
 */

/**
 * The recap of a year: a reading is a read date of the year, a book read again counting once per reading; the books
 * read, the ratings and the records take each book once.
 *
 * @typedef {Object} Recap
 * @property {number}                                              year                   The year.
 * @property {number[]}                                            years                  The years with a reading, the earliest first.
 * @property {number}                                              day_count              Its days gone by: all of them for a year gone.
 * @property {number}                                              reading_count          Its readings.
 * @property {number}                                              previous_reading_count The readings of the year before.
 * @property {number}                                              reread_count           Its readings of a book read again in the year.
 * @property {number[]}                                            books                  The IDs of the books read, in the order they were read.
 * @property {{total: number, counted: number}}                    pages                  The pages read, and the readings of a book with its number of pages.
 * @property {number[]}                                            months                 The readings of each month, from January.
 * @property {RecapTerm[]}                                         forms                  The forms read, the most read first.
 * @property {RecapTerm[]}                                         people                 The people read the most, by the name they are known by.
 * @property {Term[]}                                              discoveries            The people read for the first time, in the order they were read.
 * @property {{code: string, reading_count: number}[]}             languages              The original languages, as ISO 639 codes, the most read first.
 * @property {RecapSeries[]}                                       series                 The series read, the most read first.
 * @property {number|null}                                         rating                 The average rating of the books read, none without one.
 * @property {number[]}                                            best                   The IDs of the books read with a rating, the highest first.
 * @property {{longest: number, shortest: number, oldest: number}} records                The IDs of the longest, the shortest and the oldest books read, 0 for none.
 * @property {Achievement[]}                                       achievements           The achievements earned in the year.
 * @property {number}                                              added_count            The books added in the year, but those to buy.
 * @property {RecapCalendar}                                       calendar               Its days of reading.
 */

/**
 * A book as the REST API of core gives it, with the fields of the plugin, those the app asks for.
 *
 * @typedef {Object} ApiBook
 * @property {number}                  id                     Its ID.
 * @property {{rendered: string}}      title                  Its title, HTML.
 * @property {string}                  main_title             Its title but its subtitle, HTML.
 * @property {{rendered: string}}      excerpt                Its excerpt, HTML.
 * @property {{rendered: string}}      content                Its notes, HTML.
 * @property {string}                  credits                Its credits, HTML.
 * @property {string}                  status                 Its status.
 * @property {Object<string, unknown>} meta                   Its meta, by their keys.
 * @property {ApiReading[]}            readings               Its readings, in order, the one in progress last.
 * @property {Cover|null}              cover                  Its cover, none without one.
 * @property {Person[]}                names                  Its names, HTML, each with its term ID and its role.
 * @property {number[]}                series                 The term IDs of its series.
 * @property {string}                  series_name            Its series after its cycles, HTML.
 * @property {string[]}                language_codes         Its original languages, as their ISO 639 codes.
 * @property {string[]}                edition_language_codes The languages of its text, as their ISO 639 codes.
 * @property {string}                  form_name              The name of its form, HTML.
 * @property {Term[]}                  publisher_names        Its publishers, each followed by its collections, HTML, each with its term ID: library-book-publisher.
 * @property {string}                  date                   The date it was added, its post date.
 */

/**
 * A term of the core REST API, with the fields of the plugin the form of a book reads.
 *
 * @typedef {Object} ApiTerm
 * @property {number} id             Its term ID.
 * @property {string} name           Its name, HTML.
 * @property {number} parent         The term ID of its parent, 0 at the top.
 * @property {number} [publisher_id] The term ID of the publisher of a collection, its own for a publisher.
 * @property {string} [path]         A series after its cycles, or a collection after the collections it is part of,
 *                                   without its publisher, in plain text.
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

// The end of a reading in progress, which has none yet, as an interval of ISO 8601-2 left open: 2026-11-08/..
export const IN_PROGRESS = '..';

/**
 * Readings as the API gives them, in order, as the app reads them: the ones ended, an unknown start or end empty, and the
 * start of the one in progress.
 *
 * @param {ApiReading[]} items Readings, from the first to the latest, the one in progress last.
 * @return {BookReadings} The readings ended and the start of the one in progress.
 */
export function toReadings( items ) {
	const all = items.map( ( { start, end, rating } ) => ( {
		start: start ?? '',
		end: end ?? '',
		rating,
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
export function toApiReading( { start, end, rating } ) {
	return { start: start || null, end: end || null, rating };
}

/**
 * The reading in progress as the API takes it, since its start, empty when unknown.
 *
 * @param {string} started Its start.
 * @return {ApiReading} The reading of the API.
 */
export function toApiReadingInProgress( started ) {
	return { start: started || null, end: IN_PROGRESS, rating: null };
}

/**
 * A reading by its days, as the forms give it and send it: the server keeps the time of a start or an end of today, and
 * of one saved with its time given by its day.
 *
 * @param {EndedReading} reading A reading, its start and end with their times or not.
 * @return {EndedReading} Its days.
 */
export function toDays( { start, end, rating } ) {
	return { start: start.slice( 0, 10 ), end: end.slice( 0, 10 ), rating };
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
 * The names of a book with a role, such as its authors or its translators, in their order.
 *
 * @param {Book}   book The book.
 * @param {string} role The role: author, translator…
 * @return {Person[]} Its names with that role.
 */
export function getPeople( book, role ) {
	return book.people.filter( ( person ) => role === person.role );
}

/**
 * A book of the API as the app shows it: plain text but its notes, its meta by their names, its read dates in order, its cover with its size, its names with their roles, its original languages, its form and its publishers.
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

	return {
		...meta,
		...toReadings( item.readings ),
		id: item.id,
		title: text( item.title.rendered ),
		main_title: text( item.main_title ),
		excerpt: text( item.excerpt.rendered ),
		// HTML as the API renders it: its paragraphs and links.
		content: item.content.rendered,
		credits: text( item.credits ),
		status: item.status,
		// Its size gives the image its place before it loads.
		cover: item.cover,
		// As named on the book: another name opens the page of the person too.
		people: item.names.map( toPlainName ),
		// After its cycles, as typed, "Star Wars, Young Jedi Knights", and its ID, for its page.
		series: item.series_name,
		series_id: item.series[ 0 ] ?? 0,
		// As their ISO 639 codes: the app names them in a sentence.
		languages: item.language_codes,
		edition_languages: item.edition_language_codes,
		// Its name, for the tag of its card.
		form: text( item.form_name ),
		// From the widest, each to its page.
		publishers: item.publisher_names.map( toPlainName ),
		added_date: item.date,
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
 * A page of a list of the API, its items, and how many items and pages the list has in all, from its headers.
 *
 * @template T
 * @typedef {Object} Paged
 * @property {T[]}    items Its items.
 * @property {number} total How many items the list has in all.
 * @property {number} pages How many pages it has.
 */

/**
 * A page of a list of the API, as `options` ask for it, with its totals: see Paged.
 *
 * @template T
 * @param {Object}      options          The request.
 * @param {string}      options.path     Its path.
 * @param {AbortSignal} [options.signal] Cancels it.
 * @return {Promise<Paged<T>>} The page.
 */
async function fetchPaged( options ) {
	const response = await apiFetch( { ...options, parse: false } );

	return {
		items: await response.json(),
		total: Number( response.headers.get( 'X-WP-Total' ) ),
		pages: Number( response.headers.get( 'X-WP-TotalPages' ) ),
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
			read: data.read_count,
			rating: data.rating,
			bookPages: data.page_count,
			completion: data.completion,
		} ),
	},
	series: {
		route: 'series',
		from: ( data ) => ( {
			name: text( data.name ),
			cycle: toPlainName( data.cycle ),
			issn: data.issn,
			parts: data.parts.map( toPlainName ),
			authors: data.authors.map( toPlainName ),
			description: data.description,
			read: data.read_count,
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
			read: data.read_count,
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
			const path = addQueryArgs( '/wp/v2/books', {
				...BOOK_QUERY,
				status: statuses,
				include: chunk.join( ',' ),
				per_page: chunk.length,
			} );

			return apiFetch( { path, signal } );
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
			path: addQueryArgs( `/wp/v2/books/${ id }`, BOOK_QUERY ),
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
	const path = addQueryArgs( '/wp/v2/books', {
		...BOOK_QUERY,
		...query,
		per_page: count,
		offset,
	} );
	/** @type {Paged<ApiBook>} */
	const { items, total } = await fetchPaged( { path, signal } );

	return { books: items.map( toBook ), total };
}

/**
 * Whether `query` finds the book `id`.
 *
 * @param {Object<string, string>} query The query of the books.
 * @param {number}                 id    Book ID.
 * @return {Promise<boolean>} Whether it finds it.
 */
export async function finds( query, id ) {
	const path = addQueryArgs( '/wp/v2/books', {
		...query,
		include: id,
		_fields: 'id',
	} );
	/** @type {Array<{id: number}>} */
	const found = await apiFetch( { path } );

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
	const path = addQueryArgs( '/wp/v2/books', {
		...BOOK_QUERY,
		search: `series:"${ name }"`,
		status: statuses,
		// Every page, however many volumes: apiFetch loads the first one, as the page preloads it, then the others.
		per_page: -1,
	} );
	/** @type {ApiBook[]} */
	const books = await apiFetch( { path, signal } );

	return books.map( toBook );
}

/**
 * The book that has the ISBN `digits`, or none.
 *
 * @param {string} digits An ISBN, its digits only.
 * @return {Promise<number>} The ID of the book, 0 for none.
 */
export async function findIsbn( digits ) {
	const path = addQueryArgs( '/wp/v2/books', {
		search: `isbn:${ digits }`,
		status: statuses,
		per_page: 1,
		_fields: 'id',
	} );
	/** @type {Array<{id: number}>} */
	const [ found ] = await apiFetch( { path } );

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
	try {
		/** @type {CatalogBook} */
		const book = await apiFetch( {
			path: addQueryArgs( '/library/v1/books/bnf', { isbn } ),
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
	/** @type {Paged<{month: string, readings: Array<{date: string, book_id: number}>}>} */
	const { items, total, pages } = await fetchPaged( {
		path: addQueryArgs( '/library/v1/readings', { page } ),
	} );
	const months = items.map( ( { month, readings } ) => ( {
		month,
		readings: readings.map( ( { date, book_id: id } ) => ( { date, id } ) ),
	} ) );

	return { months, total, pages };
}

/**
 * The idea at `position` in the week's list, with the ID of its book.
 *
 * @param {number} position Its position, from 0.
 * @return {Promise<Idea>} The idea.
 */
export function getIdea( position ) {
	return apiFetch( {
		path: addQueryArgs( '/library/v1/idea', { position } ),
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
	return apiFetch( { path: '/library/v1/achievements' } );
}

/**
 * The reading stats.
 *
 * @return {Promise<Stats>} The stats.
 */
export function getStats() {
	return apiFetch( { path: '/library/v1/stats' } );
}

/**
 * Every person and their other names, publisher and collection, with its publisher and after the collections it is part of,
 * and series after its cycles, as the server names them, the publishers and the series in the order of their IDs: apiFetch
 * loads all their pages. The original languages are in the page.
 *
 * @return {Promise<Suggestions>} The names to suggest.
 */
export async function getTerms() {
	/** @type {( base: string, args: Object<string, string> ) => Promise<ApiTerm[]>} */
	const fetchAll = ( base, args ) =>
		apiFetch( {
			path: addQueryArgs( `/wp/v2/${ base }`, { ...args, per_page: -1 } ),
		} );
	const [ people, publishers, series ] = await Promise.all( [
		fetchAll( 'people', { _fields: 'id,name,parent' } ),
		fetchAll( 'publishers', {
			_fields: 'id,name,parent,publisher_id,path',
			orderby: 'id',
		} ),
		fetchAll( 'series', { _fields: 'id,parent,path', orderby: 'id' } ),
	] );
	/** @type {( term: ApiTerm ) => Suggestion} */
	const toTerm = ( { id, name, parent } ) => ( {
		id,
		name: text( name ),
		parent,
	} );

	return {
		people: people.map( toTerm ),
		publishers: publishers.map( ( term ) => ( {
			...toTerm( term ),
			publisher: term.publisher_id,
			collection: term.path,
		} ) ),
		series: series.map( ( { id, path, parent } ) => ( {
			id,
			name: path,
			parent,
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
			path: addQueryArgs( `/library/v1/books/${ id }`, BOOK_QUERY ),
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
			path: addQueryArgs( '/library/v1/books', BOOK_QUERY ),
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
	const body = new FormData();

	body.append( 'file', file );
	appendFields( body, { title, people, series, meta } );

	/** @type {{id: number}} */
	const image = await apiFetch( {
		path: '/library/v1/covers',
		method: 'POST',
		body,
	} );

	return image.id;
}

/**
 * Appends values to a form data, a list or an object by the names of its items in brackets, as PHP reads a form:
 * people[0][name]; true is 1, false is empty, and null is left out.
 *
 * @param {FormData} body   The form data.
 * @param {Object}   values The values, by name.
 * @param {string}   [base] The name they are under, if any.
 */
function appendFields( body, values, base = '' ) {
	Object.entries( values ).forEach( ( [ key, value ] ) => {
		const name = base ? `${ base }[${ key }]` : key;

		if ( null === value || undefined === value ) {
			return;
		}

		if ( 'object' === typeof value ) {
			appendFields( body, value, name );
			return;
		}

		if ( 'boolean' === typeof value ) {
			body.append( name, value ? '1' : '' );
			return;
		}

		body.append( name, String( value ) );
	} );
}

/**
 * An argument of an endpoint, as the API describes it: its type, whether it is required, and its rules, such as its
 * default or its values, in plain text.
 *
 * @typedef {Object} ApiArg
 * @property {string}        name        Its name.
 * @property {string}        type        Its type, such as "integer" or "string[]".
 * @property {boolean}       required    Whether it is required.
 * @property {string}        description Its description, or empty.
 * @property {string[]}      rules       Its rules, such as "default: 1".
 * @property {ApiProperty[]} properties  The properties it holds, such as those of a person of a book.
 */

/**
 * A property of what a route gives, as its schema describes it, with the ones it holds, such as the meta of a book.
 *
 * @typedef {Object} ApiProperty
 * @property {string}        name        Its name.
 * @property {string}        type        Its type.
 * @property {string}        description Its description, or empty.
 * @property {boolean}       readonly    Whether it is only read.
 * @property {string[]}      rules       Its rules, such as its values.
 * @property {ApiProperty[]} properties  The properties it holds.
 */

/**
 * An endpoint of a route: its methods and its arguments, those of the path apart.
 *
 * @typedef {Object} ApiEndpoint
 * @property {string[]} methods Its methods, such as GET.
 * @property {ApiArg[]} args    Its arguments.
 */

/**
 * A route of the API, as its reference shows it.
 *
 * @typedef {Object} ApiRoute
 * @property {string}        pattern    Its pattern, as the API registers it: /library/v1/series/(?P<id>\d+).
 * @property {string}        path       Its path, its arguments in braces: /library/v1/series/{id}.
 * @property {string[]}      params     The arguments of its path, such as id.
 * @property {ApiEndpoint[]} endpoints  Its endpoints.
 * @property {ApiProperty[]} properties What it gives, when its schema says it.
 */

/**
 * A request tried from the reference of the API, and its answer.
 *
 * @typedef {Object} ApiTry
 * @property {string} path   The path asked for.
 * @property {number} status The status of the answer, 0 without one.
 * @property {string} total  The number of items of a collection, from X-WP-Total, or empty.
 * @property {string} body   The answer, as indented JSON, or why it failed.
 */

// The namespace of the plugin, whose routes the reference gives all.
const NAMESPACE = '/library/v1';
// The routes of core the app reads, by their base: the reference gives what they take to read and what they give.
const CORE_ROUTES = [
	'books',
	'people',
	'publishers',
	'series',
	'languages',
	'forms',
];
// The arguments of a path: (?P<id>\d+).
const PATH_PARAM = /\(\?P<(\w+)>[^)]*\)/g;
// What a request is asked with besides its methods, and what the API answers itself.
const HIDDEN_METHODS = [ 'OPTIONS', 'HEAD' ];

/**
 * The type of a value of a schema: its types, a list as the type of its items followed by []; a schema named, such as
 * the one of a person of a book, by its title: library-book-person[].
 *
 * @param {Object} schema A schema.
 * @return {string} The type, or empty.
 */
function typeOf( schema ) {
	if ( schema.title ) {
		return schema.title;
	}

	const types = [ schema.type ?? [] ].flat();

	return types
		.map( ( type ) =>
			'array' === type && schema.items
				? `${ typeOf( schema.items ) || 'mixed' }[]`
				: type
		)
		.join( ' | ' );
}

/**
 * The rules of an argument, in plain text: its default, its values, its limits, its pattern.
 *
 * @param {Object} arg An argument, as the API describes it.
 * @return {string[]} The rules.
 */
function rulesOf( arg ) {
	const value = ( item ) => JSON.stringify( item );
	const isEmpty =
		undefined === arg.default ||
		'' === arg.default ||
		( Array.isArray( arg.default ) && ! arg.default.length ) ||
		( arg.default &&
			'object' === typeof arg.default &&
			! Object.keys( arg.default ).length );
	const values = arg.enum ?? arg.items?.enum;

	return [
		! isEmpty && `default: ${ value( arg.default ) }`,
		values && `values: ${ values.join( ', ' ) }`,
		undefined !== arg.minimum && `minimum: ${ arg.minimum }`,
		undefined !== arg.maximum && `maximum: ${ arg.maximum }`,
		arg.format && `format: ${ arg.format }`,
		( arg.pattern ?? arg.items?.pattern ) &&
			`pattern: ${ arg.pattern ?? arg.items.pattern }`,
	].filter( Boolean );
}

/**
 * The properties of a schema, in its order, each with the ones it holds.
 *
 * @param {Object} schema A schema.
 * @return {ApiProperty[]} The properties.
 */
function propertiesOf( schema ) {
	return Object.entries( schema?.properties ?? {} ).map(
		( [ name, property ] ) => ( {
			name,
			type: typeOf( property ),
			description: property.description ?? '',
			readonly: Boolean( property.readonly ),
			rules: rulesOf( property ),
			properties: propertiesOf( property.items ?? property ),
		} )
	);
}

/**
 * A route of the API as its reference shows it: its endpoints with `methods`, the arguments of its path apart.
 *
 * @param {string}   pattern The pattern of the route.
 * @param {Object}   route   The route, as the API describes it.
 * @param {string[]} methods The methods to show, all if empty.
 * @return {ApiRoute} The route.
 */
function toRoute( pattern, route, methods = [] ) {
	const params = [ ...pattern.matchAll( PATH_PARAM ) ].map(
		( [ , name ] ) => name
	);

	return {
		pattern,
		path: pattern.replace( PATH_PARAM, '{$1}' ),
		params,
		endpoints: route.endpoints
			.map( ( endpoint ) => ( {
				methods: endpoint.methods.filter(
					( method ) =>
						! HIDDEN_METHODS.includes( method ) &&
						( ! methods.length || methods.includes( method ) )
				),
				args: Object.entries( endpoint.args )
					.filter( ( [ name ] ) => ! params.includes( name ) )
					.map( ( [ name, arg ] ) => ( {
						name,
						type: typeOf( arg ),
						required: Boolean( arg.required ),
						description: arg.description ?? '',
						rules: rulesOf( arg ),
						properties: propertiesOf( arg.items ?? arg ),
					} ) ),
			} ) )
			.filter( ( endpoint ) => endpoint.methods.length ),
		properties: propertiesOf( route.schema ),
	};
}

/**
 * The reference of the API, as the API describes itself: every route of the plugin, then the routes of core the app reads,
 * with what they take to read and what they give. The index of the namespace asked for help gives the schemas of the
 * routes that have one; a route of core asked with OPTIONS gives its own.
 *
 * @param {Object}      [options]        Options.
 * @param {AbortSignal} [options.signal] Cancels it.
 * @return {Promise<ApiRoute[]>} The routes.
 */
export async function getReference( { signal } = {} ) {
	const [ index, ...core ] = await Promise.all( [
		apiFetch( {
			path: addQueryArgs( NAMESPACE, { context: 'help' } ),
			signal,
		} ),
		...CORE_ROUTES.map( ( base ) =>
			apiFetch( { path: `/wp/v2/${ base }`, method: 'OPTIONS', signal } )
		),
	] );

	return [
		...Object.entries( index.routes )
			.filter( ( [ pattern ] ) => NAMESPACE !== pattern )
			.map( ( [ pattern, route ] ) => toRoute( pattern, route ) ),
		...core.map( ( route, position ) =>
			toRoute( `/wp/v2/${ CORE_ROUTES[ position ] }`, route, [ 'GET' ] )
		),
	];
}

/**
 * The path of a request to a route: the arguments of its path in their place, then those of its query given.
 *
 * @param {ApiRoute}               route  The route.
 * @param {Object<string, string>} values The values of its arguments, by name, empty for one not given.
 * @return {string} The path.
 */
export function pathOf( route, values ) {
	const path = route.pattern.replace( PATH_PARAM, ( match, name ) =>
		encodeURIComponent( values[ name ] ?? '' )
	);
	const query = Object.fromEntries(
		Object.entries( values ).filter(
			( [ name, value ] ) => value && ! route.params.includes( name )
		)
	);

	return addQueryArgs( path, query );
}

/**
 * A GET request tried from the reference of the API: its status, the number of items of a collection, and its answer as
 * indented JSON, an error of the API among them.
 *
 * @param {string} path The path, with its arguments.
 * @return {Promise<ApiTry>} The answer.
 */
export async function tryRoute( path ) {
	/** @type {Response} */
	let response;

	try {
		response = await apiFetch( { path, parse: false } );
	} catch ( error ) {
		if ( ! ( error instanceof Response ) ) {
			return { path, status: 0, total: '', body: errorMessage( error ) };
		}

		response = error;
	}

	const body = await response.text();
	let indented = body;

	try {
		indented = JSON.stringify( JSON.parse( body ), null, 2 );
	} catch {
		// Not JSON: the answer as it came.
	}

	return {
		path,
		status: response.status,
		total: response.headers.get( 'X-WP-Total' ) ?? '',
		body: indented,
	};
}
