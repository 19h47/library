/**
 * The reading of a book in the reading app: each change saved at once, with Undo, a change made while the one before
 * saves, the focus kept; a reading in progress, from the day it started, which Read ends.
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
		const today = await page.evaluate( () =>
			new Date().toLocaleDateString( 'sv' )
		);

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
			{
				meta: {
					library_in_progress: true,
					library_reading: [ `${ today }/..` ],
				},
			},
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
				).meta.library_reading.map( ( reading ) =>
					reading.split( '/' )[ 1 ].slice( 0, 10 )
				)
			)
			.toEqual( [ today ] );
		await expect( read ).toBeFocused();
	} );

	test( 'a reading in progress, from the day it started, ends with Read', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const started = page.locator(
			'library-reading [name="library_started"]'
		);
		const today = await page.evaluate( () =>
			new Date().toLocaleDateString( 'sv' )
		);

		await page
			.locator( 'library-reading [name="library_in_progress"]' )
			.check();
		await waitForIdle( page );
		await expect( started ).toHaveValue( today );

		await started.fill( '2020-01-01' );
		await waitForIdle( page );
		await expect
			.poll(
				async () =>
					( await getBook( requestUtils, book.id ) ).meta
						.library_reading
			)
			.toEqual( [ '2020-01-01/..' ] );

		await page.locator( 'library-reading [name="library_read"]' ).check();
		await waitForIdle( page );
		await expect
			.poll( async () =>
				(
					await getBook( requestUtils, book.id )
				).meta.library_reading.map( ( reading ) =>
					reading.slice( 0, 21 )
				)
			)
			.toEqual( [ `2020-01-01/${ today }` ] );
		await expect(
			page.locator( 'library-reading [name="library_reading_start"]' )
		).toHaveValue( '2020-01-01' );
		await expect( started ).toBeHidden();
	} );
} );
