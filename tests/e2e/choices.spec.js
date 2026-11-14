/**
 * A field of several choices in the form of a book, its original languages: a pill for each choice made, which
 * removes it, the focus going to the button that adds one; the form opened again on the book shows its choices again.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp } from './library.js';

/**
 * The language `name`, added unless the site has it already.
 *
 * @param {Object} requestUtils The request utils of WordPress.
 * @param {string} name         Its name.
 * @return {Promise<{id: number}>} The language.
 */
async function languageOf( requestUtils, name ) {
	const [ found ] = await requestUtils.rest( {
		path: '/wp/v2/languages',
		params: { search: name },
	} );

	return (
		found ??
		requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/languages',
			data: { name },
		} )
	);
}

test.describe( 'Choices', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'a choice made shows as a pill, which removes it, and shows again as the form opens again', async ( {
		page,
		requestUtils,
	} ) => {
		const esperanto = await languageOf( requestUtils, 'Esperanto' );

		await languageOf( requestUtils, 'Latin' );

		const book = await addBook( requestUtils, {
			title: 'The Choices Test',
			languages: [ esperanto.id ],
		} );

		await openApp( page, `&view=book&book=${ book.id }` );

		const edit = page.getByRole( 'button', { name: 'Edit Book' } );
		const languages = page.locator(
			'library-edit checkboxes-field[name="languages"]'
		);
		const pill = languages.getByRole( 'button', {
			name: 'Remove: Esperanto',
		} );

		// The languages, among the details of the form, folded.
		const details = page.locator(
			'library-edit [data-part="details"] > summary'
		);

		await edit.click();
		await details.click();
		await expect( pill ).toBeVisible();
		await expect(
			languages.getByRole( 'button', { name: 'Remove: Latin' } )
		).toBeHidden();

		await pill.click();
		await expect( pill ).toBeHidden();
		await expect(
			languages.getByRole( 'button', { name: 'Add a language' } )
		).toBeFocused();

		await page.keyboard.press( 'Escape' );
		await edit.click();
		await details.click();
		await expect( pill ).toBeVisible();
	} );
} );
