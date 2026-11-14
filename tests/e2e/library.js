/**
 * What the tests of the reading app share: the URL of the app, books added and removed through the REST API, and the
 * store of the app, read from the page.
 */

// The page of the app in the admin, the books view.
export const APP_PATH =
	'/wp-admin/edit.php?post_type=library_book&page=library';

// The statuses a book of the tests may have, as the app lists them, and a draft.
const STATUSES = 'publish,library-not-kept,library-to-buy,draft';

// More books than a site of the tests ever has: a site with a library is never emptied.
const MOST_BOOKS = 100;

/**
 * A reading as the API takes it, from its interval, as the tests write it: its start and its end, either empty when
 * unknown, its end “..” while it is read, and its rating after a hash: '/2024-03-14', '2024-10-20/..', '/' for a reading
 * of unknown dates, '2024-03-02/2024-03-20#8'.
 *
 * @param {string} interval The interval.
 * @return {{start: string|null, end: string|null, rating: number|null}} The reading.
 */
export function reading( interval ) {
	const [ period, rating ] = interval.split( '#' );
	const [ start, end = '' ] = period.split( '/' );

	return {
		start: start || null,
		end: end || null,
		rating: rating ? Number( rating ) : null,
	};
}

/**
 * Readings as the API takes them, from their intervals: see reading().
 *
 * @param {string[]} intervals The intervals.
 * @return {Array<Object>} The readings.
 */
export function readings( intervals ) {
	return intervals.map( reading );
}

/**
 * Readings as the API gives them, as intervals, as the tests write them: see reading(). A start or an end of the day keeps
 * its time.
 *
 * @param {Array<{start: string|null, end: string|null, rating: number|null}>} items The readings.
 * @return {string[]} Their intervals.
 */
export function intervalsOf( items ) {
	return items.map(
		( { start, end, rating } ) =>
			`${ start ?? '' }/${ end ?? '' }${
				null !== rating ? `#${ rating }` : ''
			}`
	);
}

/**
 * Adds a book through the route of the book form, as the app adds one, and returns it as the route gives it back.
 *
 * @param {Object} requestUtils The request utils of WordPress.
 * @param {Object} book         Fields of the book, as the route takes them: title, people, series, meta, status…; its
 *                              readings as intervals, see reading().
 * @return {Promise<Object>} The book.
 */
export function addBook( requestUtils, book ) {
	const meta = book.meta?.reading
		? { ...book.meta, reading: readings( book.meta.reading ) }
		: book.meta;

	return requestUtils.rest( {
		method: 'POST',
		path: '/library/v1/books',
		data: meta ? { ...book, meta } : book,
	} );
}

/**
 * The book `id` as the REST API of core gives it, its meta included.
 *
 * @param {Object} requestUtils The request utils of WordPress.
 * @param {number} id           Book ID.
 * @return {Promise<Object>} The book.
 */
export function getBook( requestUtils, id ) {
	return requestUtils.rest( {
		path: `/wp/v2/books/${ id }`,
		params: { context: 'edit' },
	} );
}

/**
 * Removes every book of the site, whatever its status. Throws on a site with a library, which the tests must not touch.
 *
 * @param {Object} requestUtils The request utils of WordPress.
 * @return {Promise<void>}
 */
export async function deleteAllBooks( requestUtils ) {
	const books = await requestUtils.rest( {
		path: '/wp/v2/books',
		params: { status: STATUSES, per_page: MOST_BOOKS, _fields: 'id' },
	} );

	if ( books.length >= MOST_BOOKS ) {
		throw new Error(
			`The site has ${ MOST_BOOKS } books or more: the tests run on a site of their own, such as wp-env.`
		);
	}

	await Promise.all(
		books.map( ( { id } ) =>
			requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/books/${ id }`,
				params: { force: true },
			} )
		)
	);
}

/**
 * Opens the app at `query`, such as "&view=diary", and makes its store readable from the page as `window.store`, once
 * the app has shown what it opens with.
 *
 * @param {Object} page  The page.
 * @param {string} query Arguments of the URL after those of the app.
 * @return {Promise<void>}
 */
export async function openApp( page, query = '' ) {
	await page.goto( APP_PATH + query );
	await page.evaluate( async () => {
		window.store = ( await import( 'library-app/store' ) ).store;
	} );
	await page.waitForFunction( () => window.store.get().view );
}

/**
 * Back to the app after a while, as from another app: what it loaded loads again.
 *
 * @param {Object} page The page.
 * @return {Promise<void>}
 */
export function comeBackLater( page ) {
	return page.evaluate( () => {
		const now = Date.now;

		Object.defineProperty( document, 'hidden', {
			configurable: true,
			get: () => true,
		} );
		document.dispatchEvent( new Event( 'visibilitychange' ) );
		Date.now = () => now() + 10 * 60 * 1000;
		Object.defineProperty( document, 'hidden', {
			configurable: true,
			get: () => false,
		} );
		document.dispatchEvent( new Event( 'visibilitychange' ) );
		Date.now = now;
	} );
}

/**
 * Waits until no resource of the store is loading.
 *
 * @param {Object} page The page.
 * @return {Promise<void>}
 */
export function waitForIdle( page ) {
	return page.waitForFunction( () => {
		const state = window.store.get();

		return (
			! state.saving.loading &&
			[
				'search',
				'inProgress',
				'toBuy',
				'diary',
				'idea',
				'achievements',
				'person',
				'series',
				'publisher',
				'volumes',
			].every( ( key ) => ! state[ key ].loading )
		);
	} );
}
