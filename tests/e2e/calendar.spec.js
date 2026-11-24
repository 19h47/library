/**
 * The calendar, opened from the diary: the days of reading of the current year, its months side by side, with what they
 * count; Before goes to the year before, a month of it opens at the scale of a month, each day the book whose reading
 * ended then, a link to it, a day of it at the scale of a day, the books read then; the scale chosen goes back to the
 * year; its URL says what it shows, which the history goes back to.
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

	test( 'the calendar counts the days of a year, then of a month and of a day, its books a link each', async ( {
		page,
	} ) => {
		await openApp( page, '&view=diary' );
		await waitForIdle( page );
		await page
			.locator( 'library-view[name="diary"]' )
			.getByRole( 'link', { name: 'Calendar', exact: true } )
			.click();
		await expect( page ).toHaveURL( /view=calendar/ );
		// Part of the diary, which stays the view current.
		await expect(
			page.locator( 'library-nav' ).getByRole( 'link', { name: 'Diary' } )
		).toHaveAttribute( 'aria-current', 'page' );

		const calendar = page.locator( 'library-calendar' );
		const period = calendar.locator( '[data-field="period"]' );

		// The current year, its readings ended, After out of use.
		await expect( period ).toHaveText( `${ THIS_YEAR }` );
		await expect(
			calendar.locator( '[data-field="readings"]' )
		).toHaveText( '1' );
		await expect(
			calendar.getByRole( 'link', { name: 'After' } )
		).toHaveAttribute( 'aria-disabled', 'true' );

		// The year before: two readings, their pages, the days from the start of the first one to its end and the end of
		// the second one.
		await calendar.getByRole( 'link', { name: 'Before' } ).click();
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
			calendar.getByRole( 'link', { name: 'Before' } )
		).toHaveAttribute( 'aria-disabled', 'true' );

		// March, at the scale of a month: each day a link to it, the book whose reading ended then on it.
		await calendar.getByRole( 'link', { name: 'March' } ).click();
		await expect( period ).toHaveText( `March ${ YEAR }` );
		await expect( page ).toHaveURL( new RegExp( `calendar=${ YEAR }03` ) );

		// The filter of the forms counts the readings of the month shown, then of the day.
		const formCount = calendar
			.locator( 'checkboxes-field label', { hasText: 'Test Form' } )
			.locator( '[data-part="count"]' );

		await expect( formCount ).toHaveText( '2' );
		await expect( calendar.locator( '[data-field="show"]' ) ).toHaveText(
			'Show 2 readings'
		);
		await expect( calendar.locator( 'select[name="scale"]' ) ).toHaveValue(
			'month'
		);

		const month = calendar.locator( '[data-part="month"] month-calendar' );

		await expect( month.locator( 'li' ) ).toHaveCount( 31 );
		await expect(
			month.getByRole( 'link', { name: 'The Calendar Long Book' } )
		).toHaveAttribute( 'href', new RegExp( `calendar=${ YEAR }0302` ) );
		await expect(
			month
				.locator( 'li' )
				.nth( 1 )
				.getByRole( 'link', { name: 'The Calendar Long Book' } )
		).toBeVisible();
		await expect(
			calendar.locator( '[data-field="readings"]' )
		).toHaveText( '2' );

		// The 2nd, a day: the book whose reading ended then, its pages spread over its days; the day before, the same book,
		// being read.
		await month
			.getByRole( 'link', { name: `Sunday, March 2, ${ YEAR }` } )
			.click();
		await expect( period ).toHaveText( `Sunday, March 2, ${ YEAR }` );
		await expect( formCount ).toHaveText( '1' );
		await expect( calendar.locator( '[data-field="show"]' ) ).toHaveText(
			'Show 1 reading'
		);

		const cards = calendar.locator( '[data-list="day"]' );

		await expect( cards ).toContainText( 'The Calendar Long Book' );
		await expect( cards ).toContainText( 'Finished that day' );
		await expect( calendar.locator( '[data-field="pages"]' ) ).toHaveText(
			'55'
		);
		await expect( calendar.locator( '[data-row="streak"]' ) ).toBeHidden();
		await calendar.getByRole( 'link', { name: 'Before' } ).click();
		await expect( period ).toHaveText( `Saturday, March 1, ${ YEAR }` );
		await expect( cards ).toContainText( 'In progress' );
		await expect(
			calendar.locator( '[data-field="readings"]' )
		).toHaveText( '0' );

		// Back through the history, the day before.
		await page.goBack();
		await expect( period ).toHaveText( `Sunday, March 2, ${ YEAR }` );

		// April, without a reading: no filter to choose from.
		await calendar
			.locator( 'select[name="scale"]' )
			.selectOption( 'month' );
		await calendar.getByRole( 'link', { name: 'After' } ).click();
		await expect( period ).toHaveText( `April ${ YEAR }` );
		await expect( calendar.locator( 'library-filters' ) ).toBeHidden();
		await calendar.getByRole( 'link', { name: 'Before' } ).click();
		await expect( calendar.locator( 'library-filters' ) ).toBeVisible();

		// Back to the year.
		await calendar.locator( 'select[name="scale"]' ).selectOption( 'year' );
		await expect( period ).toHaveText( `${ YEAR }` );
		await expect(
			calendar.locator( '[data-part="year"] month-calendar' )
		).toHaveCount( 12 );
	} );
} );
