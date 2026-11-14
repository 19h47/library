/**
 * The reading of a book in the reading app: each change saved at once, with Undo, a change made while the one before
 * saves, the focus kept; a reading in progress, from the day it started, which Read ends; the readings summed up in a
 * line; a date typed, at the keyboard too, saved once left, its error shown then, without taking the focus, and gone as
 * soon as it is valid.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	addBook,
	deleteAllBooks,
	getBook,
	openApp,
	waitForIdle,
} from './library.js';

/**
 * Types `dates` in the date field `$field` as Chrome and Firefox give them, focused: a date at each key, through the
 * dates on the way, each with an input and a change event.
 *
 * @param {import('@playwright/test').Locator} $field The date field.
 * @param {string[]}                           dates  The dates, one per key.
 * @return {Promise<void>}
 */
async function typeDates( $field, dates ) {
	await $field.focus();

	for ( const date of dates ) {
		await $field.evaluate( ( $input, value ) => {
			$input.value = value;
			$input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			$input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}, date );
	}
}

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
			.locator( 'library-reading [name="rating"]' )
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
			page.locator( 'library-reading [name="rating"]' )
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
			.locator( 'library-reading [name="rating"]' )
			.selectOption( '8' );
		await page.locator( 'library-reading [name="in_progress"]' ).check();
		await page.waitForFunction( () => window.store.get().saving.loading );
		await waitForIdle( page );
		await waitForIdle( page );

		expect( posts ).toEqual( [
			{ meta: { rating: 8 } },
			{
				meta: {
					in_progress: true,
					reading: [ `${ today }/..` ],
				},
			},
		] );

		await expect
			.poll( async () => ( await getBook( requestUtils, book.id ) ).meta )
			.toMatchObject( { library_rating: 8, library_in_progress: true } );
		await expect(
			page.locator( 'library-reading [name="rating"]' )
		).toHaveValue( '8' );
		await expect(
			page.locator( 'library-reading [name="in_progress"]' )
		).toBeChecked();
	} );

	test( 'Read adds a reading of today, the focus staying on it', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const read = page.locator( 'library-reading [name="read"]' );

		await read.focus();
		await page.keyboard.press( 'Space' );
		await waitForIdle( page );

		const today = await page.evaluate( () =>
			new Date().toLocaleDateString( 'sv' )
		);

		await expect
			.poll( async () =>
				( await getBook( requestUtils, book.id ) ).readings.map(
					( reading ) => reading.split( '/' )[ 1 ].slice( 0, 10 )
				)
			)
			.toEqual( [ today ] );
		await expect( read ).toBeFocused();
	} );

	test( 'the readings are summed up in a line, which opens them', async ( {
		page,
		requestUtils,
	} ) => {
		await requestUtils.rest( {
			method: 'POST',
			path: `/library/v1/books/${ book.id }`,
			data: {
				meta: {
					reading: [ '/', '2025-03-10/2025-03-21' ],
				},
			},
		} );
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const summary = page.locator( 'library-reading summary' );

		await expect( summary ).toHaveText(
			'Read 2 times · March 21, 2025 · in 12 days'
		);
		await expect(
			page.locator( 'library-reading [name="reading_end"]' ).last()
		).toBeHidden();

		await summary.click();
		await expect(
			page.locator( 'library-reading [name="reading_end"]' ).last()
		).toHaveValue( '2025-03-21' );
	} );

	test( 'a reading removed is saved at once, the focus going to Add a reading', async ( {
		page,
		requestUtils,
	} ) => {
		const posts = [];

		await requestUtils.rest( {
			method: 'POST',
			path: `/library/v1/books/${ book.id }`,
			data: {
				meta: {
					reading: [ '/', '2025-03-10/2025-03-21' ],
				},
			},
		} );
		await page.route( /\/library\/v1\/books\/\d+/, async ( route ) => {
			if ( 'POST' === route.request().method() ) {
				posts.push( route.request().postDataJSON() );
			}

			await route.continue();
		} );
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );
		await page.locator( 'library-reading summary' ).click();

		// The reading whose date is unknown, the first one.
		await page
			.locator( 'library-reading' )
			.getByRole( 'button', { name: 'Remove this reading' } )
			.first()
			.click();
		await waitForIdle( page );

		expect( posts ).toEqual( [
			{ meta: { reading: [ '2025-03-10/2025-03-21' ] } },
		] );
		await expect(
			page.getByRole( 'button', { name: 'Add a reading', exact: true } )
		).toBeFocused();
		await expect
			.poll(
				async () => ( await getBook( requestUtils, book.id ) ).readings
			)
			.toEqual( [ '2025-03-10/2025-03-21' ] );
	} );

	test( 'a reading in progress, from the day it started, ends with Read', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const started = page.locator( 'library-reading [name="started"]' );
		const today = await page.evaluate( () =>
			new Date().toLocaleDateString( 'sv' )
		);

		await page.locator( 'library-reading [name="in_progress"]' ).check();
		await waitForIdle( page );
		await expect( started ).toHaveValue( today );

		await started.fill( '2020-01-01' );
		await started.blur();
		await waitForIdle( page );
		await expect
			.poll(
				async () => ( await getBook( requestUtils, book.id ) ).readings
			)
			.toEqual( [ '2020-01-01/..' ] );

		await page.locator( 'library-reading [name="read"]' ).check();
		await waitForIdle( page );
		await expect
			.poll( async () =>
				( await getBook( requestUtils, book.id ) ).readings.map(
					( reading ) => reading.slice( 0, 21 )
				)
			)
			.toEqual( [ `2020-01-01/${ today }` ] );
		await expect(
			page.locator( 'library-reading [name="reading_start"]' )
		).toHaveValue( '2020-01-01' );
		await expect( started ).toBeHidden();
	} );

	test( 'a reading added ends the one in progress, from its start, and one of an unknown date is saved too', async ( {
		page,
		requestUtils,
	} ) => {
		await requestUtils.rest( {
			method: 'POST',
			path: `/library/v1/books/${ book.id }`,
			data: {
				meta: { reading: [ '2019-05-01/2019-05-10', '2020-01-01/..' ] },
			},
		} );
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const reading = page.locator( 'library-reading' );
		const today = await page.evaluate( () =>
			new Date().toLocaleDateString( 'sv' )
		);
		const readings = async () =>
			( await getBook( requestUtils, book.id ) ).readings.map(
				( interval ) => interval.slice( 0, 21 )
			);

		await reading.locator( 'summary' ).click();
		await reading
			.getByRole( 'button', { name: 'Add a reading', exact: true } )
			.click();
		await waitForIdle( page );

		// Its start, the one of the reading in progress, which it ends; the focus on it.
		await expect(
			reading.locator( '[name="reading_start"]' ).last()
		).toBeFocused();
		await expect(
			reading.locator( '[name="reading_start"]' ).last()
		).toHaveValue( '2020-01-01' );
		await expect(
			reading.locator( '[name="in_progress"]' )
		).not.toBeChecked();
		await expect
			.poll( readings )
			.toEqual( [ '2019-05-01/2019-05-10', `2020-01-01/${ today }` ] );

		// No date to type: the focus on the button that removes it.
		await reading
			.getByRole( 'button', {
				name: 'Add a reading with an unknown date',
			} )
			.click();
		await waitForIdle( page );
		await expect(
			reading
				.getByRole( 'button', { name: 'Remove this reading' } )
				.last()
		).toBeFocused();
		await expect.poll( readings ).toContain( '/' );
	} );

	test.describe( 'a date typed', () => {
		const posts = [];

		test.beforeEach( async ( { page, requestUtils } ) => {
			posts.length = 0;
			await requestUtils.rest( {
				method: 'POST',
				path: `/library/v1/books/${ book.id }`,
				data: {
					meta: { reading: [ '2025-03-10/2025-03-20' ] },
				},
			} );
			await page.route( /\/library\/v1\/books\/\d+/, async ( route ) => {
				if ( 'POST' === route.request().method() ) {
					posts.push( route.request().postDataJSON() );
				}

				await route.continue();
			} );
			await openApp( page, `&view=book&book=${ book.id }` );
			await waitForIdle( page );
			// The readings, folded under the line that sums them up.
			await page.locator( 'library-reading summary' ).click();
		} );

		test( 'is saved once left, without an error on the way', async ( {
			page,
			requestUtils,
		} ) => {
			const end = page.locator( 'library-reading [name="reading_end"]' );

			// The year 0002, then 0020 and 0202, on the way to 2025, before its start.
			await typeDates( end, [
				'2025-03-01',
				'2025-03-15',
				'0002-03-15',
				'0020-03-15',
				'0202-03-15',
				'2025-03-15',
			] );
			await expect(
				page.locator( 'library-reading [aria-invalid="true"]' )
			).toHaveCount( 0 );
			expect( posts ).toEqual( [] );

			await end.blur();
			await waitForIdle( page );

			expect( posts ).toEqual( [
				{ meta: { reading: [ '2025-03-10/2025-03-15' ] } },
			] );
			await expect
				.poll(
					async () =>
						( await getBook( requestUtils, book.id ) ).readings
				)
				.toEqual( [ '2025-03-10/2025-03-15' ] );
		} );

		test( 'typed at the keyboard keeps each key, and is saved once left', async ( {
			page,
			requestUtils,
		} ) => {
			const end = page.locator( 'library-reading [name="reading_end"]' );
			const values = [];

			// Its month, its day, then its year, part after part as Chrome takes them: the same date in either order of
			// the month and the day. Chrome moves the focus into the next part at the end of one, and starts typing a
			// field again as its bounds are set.
			await end.focus();

			for ( const key of '04042025' ) {
				await page.keyboard.type( key );
				values.push( await end.inputValue() );
			}

			// A month or a day begun with 0 has no date yet; its year goes through the years on the way.
			expect( values.slice( 4 ) ).toEqual( [
				'0002-04-04',
				'0020-04-04',
				'0202-04-04',
				'2025-04-04',
			] );
			expect( posts ).toEqual( [] );

			await end.blur();
			await waitForIdle( page );

			expect( posts ).toEqual( [
				{ meta: { reading: [ '2025-03-10/2025-04-04' ] } },
			] );
			await expect
				.poll(
					async () =>
						( await getBook( requestUtils, book.id ) ).readings
				)
				.toEqual( [ '2025-03-10/2025-04-04' ] );
		} );

		test( 'before its start shows its error once left, the focus going on, until the start goes before it', async ( {
			page,
			requestUtils,
		} ) => {
			const start = page.locator(
				'library-reading [name="reading_start"]'
			);
			const end = page.locator( 'library-reading [name="reading_end"]' );

			await typeDates( end, [ '2025-03-05' ] );
			await start.focus();

			await expect( end ).toHaveAttribute( 'aria-invalid', 'true' );
			await expect( start ).toBeFocused();
			expect( posts ).toEqual( [] );

			await typeDates( start, [ '2025-03-01' ] );
			await expect( end ).not.toHaveAttribute( 'aria-invalid' );

			await start.blur();
			await waitForIdle( page );
			await expect
				.poll(
					async () =>
						( await getBook( requestUtils, book.id ) ).readings
				)
				.toEqual( [ '2025-03-01/2025-03-05' ] );
		} );
	} );
} );
