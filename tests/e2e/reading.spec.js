/**
 * The reading of a book in the reading app: each change saved at once, with Undo, a change made while the one before
 * saves, the focus kept; Finished on the home.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	addBook,
	deleteAllBooks,
	getBook,
	openApp,
	waitForIdle,
} from './library.js';

test.describe( 'Reading', () => {
	let book;

	test.beforeEach( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		book = await addBook( requestUtils, {
			title: 'The Reading Test',
			people: [ { name: 'Ada Reading', role: 'author' } ],
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'a rating is saved at once, and Undo puts it back', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		await page
			.locator( 'library-reading [name="library_rating"]' )
			.selectOption( '7' );
		await expect(
			page.getByRole( 'button', { name: 'Undo' } )
		).toBeVisible();
		await expect
			.poll(
				async () =>
					( await getBook( requestUtils, book.id ) ).meta
						.library_rating
			)
			.toBe( 7 );

		await page.getByRole( 'button', { name: 'Undo' } ).click();
		await expect
			.poll(
				async () =>
					( await getBook( requestUtils, book.id ) ).meta
						.library_rating
			)
			.toBeNull();
		await expect(
			page.locator( 'library-reading [name="library_rating"]' )
		).toHaveValue( '' );
	} );

	test( 'a change made while the one before saves is saved after it', async ( {
		page,
		requestUtils,
	} ) => {
		const posts = [];

		// A save that takes its time, so that the second change comes while the first one saves.
		await page.route( /\/library\/v1\/books\/\d+/, async ( route ) => {
			if ( 'POST' === route.request().method() ) {
				posts.push( route.request().postDataJSON() );
				await new Promise( ( resolve ) => setTimeout( resolve, 500 ) );
			}

			await route.continue();
		} );
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		await page
			.locator( 'library-reading [name="library_rating"]' )
			.selectOption( '8' );
		await page
			.locator( 'library-reading [name="library_in_progress"]' )
			.check();
		await page.waitForFunction( () => window.store.get().saving.loading );
		await waitForIdle( page );
		await waitForIdle( page );

		expect( posts ).toEqual( [
			{ meta: { library_rating: 8 } },
			{ meta: { library_in_progress: true } },
		] );

		await expect
			.poll( async () => ( await getBook( requestUtils, book.id ) ).meta )
			.toMatchObject( { library_rating: 8, library_in_progress: true } );
		await expect(
			page.locator( 'library-reading [name="library_rating"]' )
		).toHaveValue( '8' );
		await expect(
			page.locator( 'library-reading [name="library_in_progress"]' )
		).toBeChecked();
	} );

	test( 'Read adds a reading of today, the focus staying on it', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const read = page.locator( 'library-reading [name="library_read"]' );

		await read.focus();
		await page.keyboard.press( 'Space' );
		await waitForIdle( page );

		const today = await page.evaluate( () =>
			new Date().toLocaleDateString( 'sv' )
		);

		await expect
			.poll( async () =>
				(
					await getBook( requestUtils, book.id )
				).meta.library_read_date.map( ( date ) => date.slice( 0, 10 ) )
			)
			.toEqual( [ today ] );
		await expect( read ).toBeFocused();
	} );

	test( 'Finished on the home leaves the focus to the next book being read', async ( {
		page,
		requestUtils,
	} ) => {
		const next = await addBook( requestUtils, {
			title: 'The Next One',
			meta: { library_in_progress: true },
		} );

		await requestUtils.rest( {
			method: 'POST',
			path: `/library/v1/books/${ book.id }`,
			data: { meta: { library_in_progress: true } },
		} );
		await openApp( page );
		await page.waitForFunction(
			() => 2 === window.store.get().inProgress.items.length
		);

		const first = page.locator(
			`library-in-progress [data-id="${ await page.evaluate(
				() => window.store.get().inProgress.items[ 0 ]
			) }"]`
		);
		const finished = first.getByRole( 'button', { name: 'Finished' } );
		const firstId = Number( await first.getAttribute( 'data-id' ) );
		const nextId = firstId === book.id ? next.id : book.id;

		await finished.focus();
		await page.keyboard.press( 'Enter' );
		await page.waitForFunction(
			( id ) => ! window.store.get().inProgress.items.includes( id ),
			firstId
		);
		await waitForIdle( page );

		await expect(
			page.locator(
				`library-in-progress [data-id="${ nextId }"] .library-cover__link`
			)
		).toBeFocused();
		await expect
			.poll(
				async () =>
					( await getBook( requestUtils, firstId ) ).meta
						.library_in_progress
			)
			.toBe( false );
	} );
} );
