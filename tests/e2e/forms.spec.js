/**
 * The forms filter the books and the readings of the diary: several at once, checked in the sheet of the filters of
 * either view, the same ones in both, what each finds counted.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

const FORMS = [ 'Forms Novel', 'Forms Comic', 'Forms Essay' ];

test.describe( 'Forms', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );

		const [ novel, comic, essay ] = await Promise.all(
			FORMS.map( ( name ) => getFormId( requestUtils, name ) )
		);

		await addBook( requestUtils, {
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

	/**
	 * The term ID of a form, added if the site has none of that name.
	 *
	 * @param {Object} requestUtils The request utils of WordPress.
	 * @param {string} name         Its name.
	 * @return {Promise<number>} Its term ID.
	 */
	async function getFormId( requestUtils, name ) {
		const [ found ] = await requestUtils.rest( {
			path: '/wp/v2/forms',
			params: { search: name, _fields: 'id,name' },
		} );

		if ( found?.name === name ) {
			return found.id;
		}

		const { id } = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/forms',
			data: { name },
		} );

		return id;
	}

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

	test( 'every form but one: its pill says all but it', async ( {
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
		const forms = sheet.getByRole( 'checkbox' );

		for ( const form of await forms.all() ) {
			await form.check();
		}
		await sheet.getByRole( 'checkbox', { name: 'Forms Novel' } ).uncheck();

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

		await filters.getByRole( 'button', { name: 'Filters' } ).click();
		const sheet = filters.getByRole( 'dialog' );
		await sheet.getByRole( 'checkbox', { name: 'Forms Novel' } ).check();
		await sheet.getByRole( 'checkbox', { name: 'Forms Essay' } ).check();

		await expect(
			sheet.getByRole( 'button', { name: 'Show 3 readings' } )
		).toBeVisible();
		await sheet.getByRole( 'button', { name: 'Show 3 readings' } ).click();

		await expect( months ).toHaveCount( 3 );
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
} );
