/**
 * The calendar of the diary: the days of reading of the current year, its months side by side, with what they count;
 * Before goes to the year before, a month of it opens at the scale of a month, each day the book whose reading ended
 * then, a link to it; the scale chosen goes back to the year.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

// The year before the one the tests run, whose months are all gone by, and the current one.
const THIS_YEAR = new Date().getFullYear();
const YEAR = THIS_YEAR - 1;

test.describe( 'Calendar', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );

		await addBook( requestUtils, {
			title: 'The Calendar Long Book',
			meta: { pages: 600 },
			shelf: { readings: [ `${ YEAR }-02-20/${ YEAR }-03-02` ] },
		} );
		await addBook( requestUtils, {
			title: 'The Calendar Short Book',
			meta: { pages: 40 },
			shelf: { readings: [ `/${ YEAR }-03-20` ] },
		} );
		await addBook( requestUtils, {
			title: 'The Calendar New Book',
			meta: { pages: 100 },
			shelf: { readings: [ `/${ THIS_YEAR }-01-05` ] },
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the calendar of the diary counts the days of a year, then of a month, its books a link each', async ( {
		page,
	} ) => {
		await openApp( page, '&view=diary' );
		await waitForIdle( page );

		const calendar = page.locator( 'library-calendar' );
		const period = calendar.locator( '[data-field="period"]' );

		// The current year, its readings ended, After out of use.
		await expect( period ).toHaveText( `${ THIS_YEAR }` );
		await expect(
			calendar.locator( '[data-field="readings"]' )
		).toHaveText( '1' );
		await expect(
			calendar.getByRole( 'button', { name: 'After' } )
		).toBeDisabled();

		// The year before: two readings, their pages, the days from the start of the first one to its end and the end of
		// the second one.
		await calendar.getByRole( 'button', { name: 'Before' } ).click();
		await expect( period ).toHaveText( `${ YEAR }` );
		await expect(
			calendar.locator( '[data-field="readings"]' )
		).toHaveText( '2' );
		await expect( calendar.locator( '[data-field="pages"]' ) ).toHaveText(
			'640'
		);
		await expect( calendar.locator( '[data-field="days"]' ) ).toHaveText(
			'12'
		);
		await expect( calendar.locator( '[data-field="streak"]' ) ).toHaveText(
			'11'
		);
		await expect(
			calendar.getByRole( 'button', { name: 'Before' } )
		).toBeDisabled();

		// March, at the scale of a month: each book a link to it on the day its reading ended.
		await calendar.getByRole( 'button', { name: 'March' } ).click();
		await expect( period ).toHaveText( `March ${ YEAR }` );
		await expect( calendar.locator( 'select[name="scale"]' ) ).toHaveValue(
			'month'
		);

		const month = calendar.locator( '[data-part="month"] month-calendar' );

		await expect( month.locator( 'li' ) ).toHaveCount( 31 );
		await expect(
			month.getByRole( 'link', { name: 'The Calendar Long Book' } )
		).toHaveAttribute( 'href', /book=\d+/ );
		await expect(
			month.locator( 'li' ).nth( 1 ).getByRole( 'link' )
		).toHaveAccessibleName( 'The Calendar Long Book' );
		await expect(
			calendar.locator( '[data-field="readings"]' )
		).toHaveText( '2' );

		// Back to the year.
		await calendar.locator( 'select[name="scale"]' ).selectOption( 'year' );
		await expect( period ).toHaveText( `${ YEAR }` );
		await expect(
			calendar.locator( '[data-part="year"] month-calendar' )
		).toHaveCount( 12 );
	} );
} );
