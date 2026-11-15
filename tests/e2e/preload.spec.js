/**
 * The reading app opens on what its page preloads: the server writes the answers of the requests the store asks for
 * first (Page::preload()), which the store builds again (queryOf()). The two write the same requests; once they differ,
 * an answer preloaded is left unused and the request goes to the network, which this test tells.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { APP_PATH, addBook, deleteAllBooks, waitForIdle } from './library.js';

/**
 * A request of the REST API by its route and its arguments, sorted, as the preloading of wp.apiFetch matches them: the
 * language the app asks for, `_locale`, apart, which wp.apiFetch adds after the preloading.
 *
 * @param {string} route The route, such as /wp/v2/books.
 * @param {string} query Its arguments.
 * @return {string} The request.
 */
function requestOf( route, query ) {
	const args = [ ...new URLSearchParams( query ) ]
		.filter(
			( [ name ] ) => ! [ 'rest_route', '_locale' ].includes( name )
		)
		.map( ( [ name, value ] ) => `${ name }=${ value }` )
		.sort();

	return `${ route }?${ args.join( '&' ) }`;
}

/**
 * Whether a request is one the page preloads: the stats, the diary and the lists of books, which the store asks for as
 * the server wrote them. The reading idea, the achievements and their books, by their IDs, load after, from the network.
 *
 * @param {string} request The request, its route and its arguments.
 * @return {boolean} Whether the page preloads it.
 */
function isPreloaded( request ) {
	return (
		request.startsWith( '/library/v1/stats' ) ||
		request.startsWith( '/library/v1/readings' ) ||
		( request.startsWith( '/wp/v2/books?' ) &&
			! request.includes( 'include=' ) )
	);
}

test.describe( 'Preload', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		// A book of each list the home shows: read, being read, and to buy.
		await addBook( requestUtils, {
			title: 'The Preload Read',
			shelf: { readings: [ '/2025-03-10' ] },
		} );
		await addBook( requestUtils, {
			title: 'The Preload In Progress',
			shelf: { readings: [ '2025-04-01/..' ] },
		} );
		await addBook( requestUtils, {
			title: 'The Preload To Buy',
			shelf: { status: 'to-buy' },
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the app asks the network for none of the requests its page preloads', async ( {
		page,
	} ) => {
		/** @type {string[]} */
		const asked = [];

		page.on( 'request', ( request ) => {
			const url = new URL( request.url() );
			const route =
				url.searchParams.get( 'rest_route' ) ??
				url.pathname.split( '/wp-json' )[ 1 ];

			if ( route ) {
				asked.push( requestOf( route, url.search ) );
			}
		} );

		await page.goto( APP_PATH );

		// The answers the page holds, by their paths, which the inline script after wp.apiFetch gives it.
		const preloaded = await page.evaluate( () =>
			Object.keys(
				JSON.parse(
					document
						.getElementById( 'wp-api-fetch-js-after' )
						.textContent.match(
							/createPreloadingMiddleware\( (\{.*?\}) \);\s*let isLoading/s
						)[ 1 ]
				)
			)
		);

		await page.evaluate( async () => {
			window.store = ( await import( 'library-app/store' ) ).store;
		} );
		await waitForIdle( page );

		expect( preloaded.filter( isPreloaded ) ).toHaveLength( 5 );
		expect( asked.filter( isPreloaded ) ).toEqual( [] );
	} );
} );
