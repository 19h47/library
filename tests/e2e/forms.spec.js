/**
 * The forms filter the books and the readings of the diary: several at once, checked in the sheet of the filters of
 * either view, the same ones in both, what each finds counted.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	addBook,
	deleteAllBooks,
	getFormId,
	openApp,
	waitForIdle,
} from './library.js';

const FORMS = [ 'Forms Novel', 'Forms Comic', 'Forms Essay' ];

test.describe( 'Forms', () => {
	let firstNovel;

	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );

		const [ novel, comic, essay ] = await Promise.all(
			FORMS.map( ( name ) => getFormId( requestUtils, name ) )
		);

		firstNovel = await addBook( requestUtils, {
			title: 'Forms Novel 1',
			form: novel,
			shelf: { readings: [ '/2024-01-10' ] },
		} );
		await addBook( requestUtils, {
			title: 'Forms Novel 2',
			form: novel,
			shelf: { readings: [ '/2024-02-10' ] },
		} );
		await addBook( requestUtils, {
			title: 'Forms Comic',
			form: comic,
			shelf: { readings: [ '/2024-03-10' ] },
		} );
		await addBook( requestUtils, {
			title: 'Forms Essay',
			form: essay,
			shelf: { readings: [ '/2024-04-10' ] },
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the books of several forms, counted, the forms chosen one pill, which removes them', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const view = page.locator( 'library-view[name="books"]' );
		const filters = view.locator( 'library-filters' );
		const count = view.locator( 'library-count' );

		await expect( count ).toHaveText( '4 books' );

		await filters.getByRole( 'button', { name: 'Filters' } ).click();
		const sheet = filters.getByRole( 'dialog' );

		// Each form with its books on the shelves.
		await expect(
			sheet.getByRole( 'checkbox', {
				name: 'Forms Novel 2',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			sheet.getByRole( 'checkbox', {
				name: 'Forms Comic 1',
				exact: true,
			} )
		).toBeVisible();

		await sheet.getByRole( 'checkbox', { name: 'Forms Novel' } ).check();
		await sheet.getByRole( 'checkbox', { name: 'Forms Comic' } ).check();

		await expect( count ).toHaveText( '3 books' );
		await expect(
			sheet.getByRole( 'button', { name: 'Show 3 books' } )
		).toBeVisible();
		await sheet.getByRole( 'button', { name: 'Show 3 books' } ).click();

		// One filter chosen, one pill.
		await expect(
			filters.getByRole( 'button', { name: 'Filters · 1' } )
		).toBeVisible();
		await expect(
			filters.getByRole( 'button', { name: /^Remove the filter:/ } )
		).toHaveCount( 1 );

		// One form left, its pill says its name.
		await filters.getByRole( 'button', { name: 'Filters · 1' } ).click();
		await sheet.getByRole( 'checkbox', { name: 'Forms Comic' } ).uncheck();
		await expect( count ).toHaveText( '2 books' );
		await expect(
			view.locator( 'library-list' ).getByText( 'Forms Comic' )
		).toHaveCount( 0 );
		await sheet.getByRole( 'button', { name: 'Show 2 books' } ).click();

		await filters
			.getByRole( 'button', { name: 'Remove the filter: Forms Novel' } )
			.click();
		await expect( count ).toHaveText( '4 books' );
		await expect(
			filters.getByRole( 'button', { name: 'Filters', exact: true } )
		).toBeFocused();
	} );

	test( 'every form checked at once, then all but one: its pill says all but it', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const view = page.locator( 'library-view[name="books"]' );
		const filters = view.locator( 'library-filters' );
		const pill = filters.getByRole( 'button', {
			name: /^Remove the filter:/,
		} );

		await filters.getByRole( 'button', { name: 'Filters' } ).click();
		const sheet = filters.getByRole( 'dialog' );
		const toggle = sheet.getByRole( 'button', { name: 'Check all' } );

		await toggle.click();
		await expect( sheet.getByRole( 'checkbox' ).first() ).toBeChecked();
		await expect(
			sheet.getByRole( 'button', { name: 'Uncheck all' } )
		).toBeVisible();
		await sheet.getByRole( 'checkbox', { name: 'Forms Novel' } ).uncheck();
		await expect( toggle ).toBeVisible();

		await expect( view.locator( 'library-count' ) ).toHaveText( '2 books' );
		await sheet.getByRole( 'button', { name: 'Show 2 books' } ).click();
		await expect( pill ).toHaveAccessibleName(
			'Remove the filter: All but Forms Novel'
		);
	} );

	test( 'the readings of the diary of the forms chosen, the same as the books', async ( {
		page,
	} ) => {
		await openApp( page, '&view=diary' );
		await waitForIdle( page );

		const view = page.locator( 'library-view[name="diary"]' );
		const filters = view.locator( 'library-filters' );
		const months = view.locator( '[data-list="months"] > section' );

		await expect( months ).toHaveCount( 4 );
		await expect( view.locator( 'library-count' ) ).toHaveText(
			'4 readings'
		);

		await filters.getByRole( 'button', { name: 'Filters' } ).click();
		const sheet = filters.getByRole( 'dialog' );
		await sheet.getByRole( 'checkbox', { name: 'Forms Novel' } ).check();
		await sheet.getByRole( 'checkbox', { name: 'Forms Essay' } ).check();

		await expect(
			sheet.getByRole( 'button', { name: 'Show 3 readings' } )
		).toBeVisible();
		await sheet.getByRole( 'button', { name: 'Show 3 readings' } ).click();

		await expect( months ).toHaveCount( 3 );
		await expect( view.locator( 'library-count' ) ).toHaveText(
			'3 readings'
		);
		await expect( months.getByText( 'Forms Comic' ) ).toHaveCount( 0 );

		// The books view keeps the same forms: the novels and the essay.
		await page
			.getByRole( 'navigation' )
			.getByRole( 'link', { name: 'Books' } )
			.click();
		await expect(
			page.locator( 'library-view[name="books"] library-count' )
		).toHaveText( '3 books' );
	} );

	test( 'a book saved with another form: the forms counted again, a form required', async ( {
		page,
	} ) => {
		await openApp( page, `&view=book&book=${ firstNovel.id }` );
		await page.getByRole( 'button', { name: 'Edit Book' } ).click();

		const form = page.locator( 'library-edit' );
		const select = form.getByLabel( 'Form (required)' );

		await expect( select ).toHaveJSProperty( 'required', true );
		await select.selectOption( { label: 'Forms Comic' } );

		const saved = page.waitForResponse(
			( response ) =>
				'POST' === response.request().method() &&
				/books(\/|%2F)\d+/.test( response.url() )
		);

		await form.getByRole( 'button', { name: 'Save' } ).click();
		await saved;

		await page
			.getByRole( 'navigation' )
			.getByRole( 'link', { name: 'Books' } )
			.click();

		const filters = page.locator(
			'library-view[name="books"] library-filters'
		);

		await filters.getByRole( 'button', { name: 'Filters' } ).click();
		const sheet = filters.getByRole( 'dialog' );

		await expect(
			sheet.getByRole( 'checkbox', {
				name: 'Forms Novel 1',
				exact: true,
			} )
		).toBeVisible();
		await expect(
			sheet.getByRole( 'checkbox', {
				name: 'Forms Comic 2',
				exact: true,
			} )
		).toBeVisible();
	} );
} );
