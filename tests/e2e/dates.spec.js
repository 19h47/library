/**
 * A date often known by its year only, such as the edition date of a book, by its day, its month in words and its
 * year: the day waits for a month and lists the days of that month, the year is required once a month is chosen, and
 * the form sends the date as the API takes it, 1972, 1972-11 or 1972-11-08.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, getBook, openApp } from './library.js';

test.describe( 'Dates', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'a date by its parts shows the one of the book, lists the days of its month and saves as the API takes it', async ( {
		page,
		requestUtils,
	} ) => {
		const book = await addBook( requestUtils, {
			title: 'The Dates Test',
			meta: { edition_date: '1972-11' },
		} );

		await openApp( page, `&view=book&book=${ book.id }` );
		await page.getByRole( 'button', { name: 'Edit Book' } ).click();
		await page
			.locator( 'library-edit [data-part="details"] > summary' )
			.click();

		const form = page.locator( 'library-edit' );
		const date = form.getByRole( 'group', { name: 'Edition date' } );
		const day = date.getByLabel( 'Day' );
		const month = date.getByLabel( 'Month' );
		const year = date.getByLabel( 'Year' );

		// The date of the book, by its month: November has no 31st.
		await expect( month ).toHaveValue( '11' );
		await expect( year ).toHaveValue( '1972' );
		await expect( day ).toBeEnabled();
		await expect( day.locator( 'option[value="30"]' ) ).not.toHaveAttribute(
			'hidden'
		);
		await expect( day.locator( 'option[value="31"]' ) ).toHaveAttribute(
			'hidden'
		);

		// The 31st, then a month of 30 days: the day is emptied rather than moved.
		await month.selectOption( '12' );
		await day.selectOption( '31' );
		await month.selectOption( '04' );
		await expect( day ).toHaveValue( '' );

		// Without a month, the day waits for one; a month without a year asks for one.
		await month.selectOption( '' );
		await expect( day ).toBeDisabled();
		await year.fill( '' );
		await month.selectOption( '02' );
		await expect( year ).toHaveJSProperty( 'required', true );
		await year.fill( '2000' );
		await year.blur();
		await day.selectOption( '29' );

		const saved = page.waitForResponse(
			( response ) =>
				'POST' === response.request().method() &&
				/books(\/|%2F)\d+/.test( response.url() )
		);

		await form.getByRole( 'button', { name: 'Save' } ).click();
		await saved;

		const { meta } = await getBook( requestUtils, book.id );

		expect( meta.library_edition_date ).toBe( '2000-02-29' );
	} );
} );
