/**
 * The REST API as the reading app asks it, through wp.apiFetch, which handles the root URL and the nonce and answers the
 * first requests from the page, which preloads them: each request with the paths the page preloads, and what comes back
 * in plain text once, so that no brick parses what the API returns. It keeps no state: the store does.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { __, addQueryArgs, apiFetch, bookFields, maxPerPage, perPage, roles, statuses } from 'library-app/app';

const parser = new DOMParser();

// The fields of a book the app reads, its cover and terms as the plugin gives them: nothing embedded, a list stays light.
const BOOK_QUERY = { _fields: bookFields };

/** Plain text of an HTML string of the API, such as a title with &#8217;: never parsed as markup in the page. */
export function text( html ) {
	return parser.parseFromString( html, 'text/html' ).body.textContent;
}

/**
 * Read dates as readings, from the first to the latest: each unknown date, empty, is a reading of its own, first; a
 * known date is one reading, as a date saved twice by an old import. As the server counts them.
 */
export function toReadings( dates ) {
	return [ ...dates.filter( ( date ) => '' === date ), ...new Set( dates.filter( Boolean ) ) ].sort();
}

/** The message of a failed request: the one of the API, or a generic one. */
export function errorMessage( error ) {
	return error?.message ?? __( 'The books could not be loaded.', 'library' );
}

/** A term the API gives, such as an author or a cycle, with its name in plain text; none stays none. */
function toPlainName( term ) {
	return term && { ...term, name: text( term.name ) };
}

/** A book of the API as the app shows it: plain text but its notes, its meta by their names, its read dates in order, its cover with its size, its authors and the names with another role, its original languages and its form. */
function toBook( item ) {
	// Its meta without the prefix of their keys: library_rating is its rating.
	const meta = Object.fromEntries( Object.entries( item.meta ).map( ( [ key, value ] ) => [ key.replace( /^library_/, '' ), value ] ) );
	// As named on the book: another name opens the page of the person too.
	const names = item.names.map( ( { id, name } ) => ( { id, name: text( name ) } ) );
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
		...Object.fromEntries( roles.map( ( role ) => [ role, names.filter( ( name ) => meta[ role ].includes( name.id ) ) ] ) ),
		// After its cycles, as typed, "Star Wars, Young Jedi Knights", and its ID, for its page.
		series: item.series_name,
		series_id: item.series[ 0 ] ?? 0,
		// As their ISO 639 codes: the app names them in a sentence.
		languages: item.language_codes,
		// Its name, for the tag of its card.
		form: text( item.form_name ),
	};
}

/** A book of the API as its form edits it: the names of its terms in plain text, the rest as typed. */
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
export const PAGE_VIEWS = Object.keys( PAGES );

/**
 * The books `ids`, in the order of their IDs, as the page preloads the books of the diary: whatever is loaded already, the
 * request is the same. A page of the API at a time.
 */
export async function getBooks( ids, { signal } = {} ) {
	const sorted = [ ...new Set( ids ) ].sort( ( a, b ) => a - b );
	const chunks = Array.from( { length: Math.ceil( sorted.length / maxPerPage ) }, ( value, index ) =>
		sorted.slice( index * maxPerPage, ( index + 1 ) * maxPerPage )
	);
	const loaded = await Promise.all(
		chunks.map( ( chunk ) => {
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				status: statuses,
				include: chunk.join( ',' ),
				per_page: chunk.length,
			} );

			return apiFetch( { path: `/wp/v2/books?${ params }`, signal } );
		} )
	);

	return loaded.flat().map( toBook );
}

/** The book `id` from the API, though it is loaded, as it may have changed elsewhere. */
export async function getBook( id ) {
	return toBook( await apiFetch( { path: `/wp/v2/books/${ id }?${ new URLSearchParams( BOOK_QUERY ) }` } ) );
}

/**
 * The books `query` finds from `offset`, `count` of them, and how many it finds in all: `query` as the API reads it, its
 * search, statuses, forms and order.
 */
export async function findBooks( query, { offset = 0, count = perPage, signal } = {} ) {
	const params = new URLSearchParams( { ...BOOK_QUERY, ...query, per_page: count, offset } );
	const response = await apiFetch( { path: `/wp/v2/books?${ params }`, parse: false, signal } );

	return { books: ( await response.json() ).map( toBook ), total: Number( response.headers.get( 'X-WP-Total' ) ) };
}

/** Whether `query` finds the book `id`. */
export async function finds( query, id ) {
	const params = new URLSearchParams( { ...query, include: id, _fields: 'id' } );

	return ( await apiFetch( { path: `/wp/v2/books?${ params }` } ) ).length > 0;
}

/** The books of the series `name`, in reading order, as a search for it lists them. */
export async function getVolumes( name, { signal } = {} ) {
	const params = new URLSearchParams( { ...BOOK_QUERY, search: `series:"${ name }"`, status: statuses, per_page: maxPerPage } );

	return ( await apiFetch( { path: `/wp/v2/books?${ params }`, signal } ) ).map( toBook );
}

/** The book that has the ISBN `digits`, or none. */
export async function findIsbn( digits ) {
	const params = new URLSearchParams( { search: `isbn:${ digits }`, status: statuses, per_page: 1, _fields: 'id' } );
	const [ found ] = await apiFetch( { path: `/wp/v2/books?${ params }` } );

	return found?.id ?? 0;
}

/**
 * A page of the diary: whole months, latest first, each with its readings and the IDs of their books; the totals count
 * months.
 */
export async function getReadings( page ) {
	const response = await apiFetch( { path: `/library/v1/books/readings?page=${ page }`, parse: false } );
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

/** The idea at `position` in the week's list, with the ID of its book. */
export function getIdea( position ) {
	return apiFetch( { path: `/library/v1/books/reading-idea?position=${ position }` } );
}

/** The page `view` of the term `id`, "person", "series" or "publisher": what its state takes, and the IDs of its books, in order. */
export async function getPage( view, id, { signal } = {} ) {
	const { route, from } = PAGES[ view ];
	const data = await apiFetch( { path: `/library/v1/${ route }/${ id }`, signal } );

	return { ...from( data ), books: data.books };
}

export function getAchievements() {
	return apiFetch( { path: '/library/v1/books/achievements' } );
}

export function getStats() {
	return apiFetch( { path: '/library/v1/books/reading-stats' } );
}

/**
 * Every person and their other names, publisher and collection, with its publisher and after the collections it is part of,
 * and series after its cycles: apiFetch loads all their pages. The original languages are in the page.
 */
export async function getTerms() {
	const bases = [ 'people', 'publishers', 'series' ];
	const [ people, publishers, series ] = await Promise.all(
		bases.map( ( base ) => apiFetch( { path: `/wp/v2/${ base }?per_page=-1&_fields=id,name,parent` } ) )
	);
	const toTerm = ( { id, name, parent } ) => ( { id, name: text( name ), parent } );
	const byId = Object.fromEntries( series.map( ( term ) => [ term.id, toTerm( term ) ] ) );
	// A series after its cycles, as a book types it.
	const pathOf = ( term ) => ( term.parent in byId ? `${ pathOf( byId[ term.parent ] ) }, ${ term.name }` : term.name );
	const publishersById = Object.fromEntries( publishers.map( ( term ) => [ term.id, toTerm( term ) ] ) );
	const publisherOf = ( term ) => ( term.parent in publishersById ? publisherOf( publishersById[ term.parent ] ) : term );
	// A collection after the collections it is part of, without its publisher, as a book types it.
	const collectionOf = ( term ) =>
		publishersById[ term.parent ]?.parent ? `${ collectionOf( publishersById[ term.parent ] ) }, ${ term.name }` : term.name;

	return {
		people: people.map( toTerm ),
		publishers: Object.values( publishersById ).map( ( term ) => ( { ...term, publisher: publisherOf( term ).id, collection: collectionOf( term ) } ) ),
		series: Object.values( byId ).map( ( term ) => ( { ...term, name: pathOf( term ) } ) ),
	};
}

/** The values of the book `id` as its form edits them. */
export async function getForm( id ) {
	return toForm( await apiFetch( { path: `/library/v1/books/${ id }` } ) );
}

/** Saves `data` to the book `id`, through the route of the book form, then returns the book as it is now. */
export async function saveBook( id, data ) {
	await apiFetch( { path: `/library/v1/books/${ id }`, method: 'POST', data } );

	return getBook( id );
}

/** Adds a book, and returns its ID. */
export async function addBook( data ) {
	return ( await apiFetch( { path: '/library/v1/books', method: 'POST', data } ) ).id;
}

/** Uploads the photo of a cover, named and titled after its book as its form sends it, and returns the ID of its image. */
export async function uploadCover( file, { title, people, series, meta } ) {
	const query = { title, people, series, meta, photo: file.name };
	const { name } = await apiFetch( { path: addQueryArgs( '/library/v1/books/cover-name', query ) } );
	const body = new FormData();

	body.append( 'file', file, name );
	body.append( 'title', title );

	return ( await apiFetch( { path: '/wp/v2/media', method: 'POST', body } ) ).id;
}
