/**
 * What the reading app loads: a resource that could not load loads again, a book loaded again as it was leaves the form
 * as it is, a page of a person loaded again shows its books as they are.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	addBook,
	comeBackLater,
	deleteAllBooks,
	openApp,
	waitForIdle,
} from './library.js';

const AUTHOR = 'Ada Store';

test.describe( 'Store', () => {
	let books = [];

	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		books = [];

		for ( let index = 1; index <= 3; index++ ) {
			books.push(
				await addBook( requestUtils, {
					title: `Store Book ${ index }`,
					people: [ { name: AUTHOR, role: 'author' } ],
				} )
			);
		}
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'a resource that could not load loads again once the network is back', async ( {
		page,
	} ) => {
		let isOffline = true;

		await page.route( /library\/v1\/idea/, ( route ) =>
			isOffline ? route.abort( 'internetdisconnected' ) : route.continue()
		);
		await openApp( page );
		await page.waitForFunction( () => window.store.get().idea.error );

		isOffline = false;
		await page.evaluate( () =>
			window.dispatchEvent( new Event( 'online' ) )
		);
		await expect
			.poll( () =>
				page.evaluate(
					() =>
						window.store.get().idea.book_id > 0 &&
						! window.store.get().idea.error
				)
			)
			.toBe( true );
	} );

	test( 'the reading form keeps what was typed when the app comes back after a while', async ( {
		page,
	} ) => {
		await openApp( page, `&view=book&book=${ books[ 0 ].id }` );
		await waitForIdle( page );

		const status = page.locator( 'library-reading [name="status"]' );

		// Typed, not chosen yet: no change, nothing saved.
		const typed = await status.evaluate( ( select ) => {
			select.value = [ ...select.options ].find(
				( option ) => ! option.selected
			).value;

			return select.value;
		} );
		await comeBackLater( page );
		await waitForIdle( page );

		await expect( status ).toHaveValue( typed );
	} );

	test( 'a page of a person loaded again shows a book added to it meanwhile', async ( {
		page,
		requestUtils,
	} ) => {
		const person = await page.request.get(
			`/wp-json/wp/v2/people?search=${ encodeURIComponent(
				AUTHOR
			) }&_fields=id`
		);
		const [ { id } ] = await person.json();

		await openApp( page, `&view=person&person=${ id }` );
		await page.waitForFunction(
			() => 3 === window.store.get().person.items.length
		);

		const added = await addBook( requestUtils, {
			title: 'Store Book Added',
			people: [ { name: AUTHOR, role: 'author' } ],
		} );

		await comeBackLater( page );
		await page.waitForFunction(
			( bookId ) => window.store.get().person.items.includes( bookId ),
			added.id
		);

		await expect(
			page.locator( `library-person [data-id="${ added.id }"]` )
		).toBeVisible();
	} );
} );
