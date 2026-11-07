/**
 * The recap of a year gone by: a screen per subject, those without anything to say hidden, which the diary leads to,
 * as the home does in January only.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

// A year gone by, whatever the year the tests run.
const YEAR = 2025;

test.describe( 'Recap', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );

		await addBook( requestUtils, {
			title: 'The Recap Long Book',
			people: [ { name: 'Ann Recap', role: 'author' } ],
			meta: {
				library_read: true,
				library_read_date: [ `${ YEAR }-03-02` ],
				library_pages: 600,
				library_rating: 9,
			},
		} );
		await addBook( requestUtils, {
			title: 'The Recap Short Book',
			people: [ { name: 'Ann Recap', role: 'author' } ],
			meta: {
				library_read: true,
				library_read_date: [ `${ YEAR }-03-20`, `${ YEAR }-09-01` ],
				library_pages: 40,
			},
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the recap of a year shows a screen per subject', async ( {
		page,
	} ) => {
		await openApp( page, `&view=recap&recap=${ YEAR }` );
		await waitForIdle( page );
		await page.waitForFunction( () => window.store.get().recap.loaded );

		const recap = page.locator( 'library-recap' );

		await expect(
			recap.getByRole( 'heading', { level: 2, name: `${ YEAR }` } )
		).toBeVisible();
		await expect( page ).toHaveTitle( new RegExp( `Recap of ${ YEAR }` ) );

		// Three readings of two books, one read again: the figure counts up to them as its screen comes, assistive
		// technologies reading their number at once.
		const readings = recap.locator( '[data-screen="readings"]' );
		const figure = readings.locator( '[data-field="readings"]' );

		await figure.scrollIntoViewIfNeeded();
		await expect( figure.locator( '.sr-only' ) ).toHaveText( '3' );
		await expect( figure.locator( '[aria-hidden="true"]' ) ).toHaveText(
			'3'
		);
		await expect( readings ).toContainText( 'Including 1 read again' );

		// The pages of each reading, two in March; assistive technologies read the name of a month and its books.
		await expect(
			recap.locator( '[data-field="pages"] .sr-only' )
		).toHaveText( '680' );
		await expect( recap.locator( '[data-list="months"] li' ) ).toHaveCount(
			12
		);
		await expect( recap.locator( '[data-list="months"]' ) ).toContainText(
			'March: 2 books'
		);

		// The person read the most leads to their page; read for the first time this year.
		await expect(
			recap
				.locator( '[data-list="people"]' )
				.getByRole( 'link', { name: 'Ann Recap' } )
		).toBeVisible();
		await expect(
			recap.locator( '[data-field="discoveries"] .sr-only' )
		).toHaveText( '1' );

		// The records, and the screens without anything to say hidden: no form, no series, no achievement.
		await expect( recap.locator( '[data-list="records"]' ) ).toContainText(
			'The Recap Long Book'
		);
		await expect( recap.locator( '[data-screen="forms"]' ) ).toBeHidden();
		await expect( recap.locator( '[data-screen="series"]' ) ).toBeHidden();
		await expect(
			recap.locator( '[data-screen="closing"]' )
		).toContainText( `That was ${ YEAR }` );
	} );

	test( 'the diary leads to the recap of a year gone by, its year focused', async ( {
		page,
	} ) => {
		await openApp( page, '&view=diary' );
		await waitForIdle( page );
		await page
			.locator( 'library-diary' )
			.getByRole( 'link', { name: `Recap of ${ YEAR }` } )
			.click();

		await expect(
			page
				.locator( 'library-recap' )
				.getByRole( 'heading', { level: 2, name: `${ YEAR }` } )
		).toBeFocused();
	} );

	test( 'the home leads to the recap of the year gone by in January only', async ( {
		page,
	} ) => {
		const link = page.locator( 'library-recap-link a' );

		await page.clock.setFixedTime( new Date( YEAR + 1, 5, 15 ) );
		await openApp( page );
		await expect( link ).toBeHidden();

		await page.clock.setFixedTime( new Date( YEAR + 1, 0, 15 ) );
		await openApp( page );
		await expect( link ).toBeVisible();
		await link.click();

		await expect(
			page
				.locator( 'library-recap' )
				.getByRole( 'heading', { level: 2, name: `${ YEAR }` } )
		).toBeVisible();
	} );
} );
