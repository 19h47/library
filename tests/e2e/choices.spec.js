/**
 * A field of several choices in the form of a book, its original languages: a pill for each choice made, which
 * removes it, the focus going to the button that adds one; the form opened again on the book shows its choices again.
 * Inline, its choices all shown, a button checks them all, then unchecks them, on its own, as the styleguide shows it;
 * fields folded under a summary count the ones filled in, on their own too.
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
			'library-edit fields-disclosure summary'
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

	test( 'inline, a button checks every choice, then unchecks them, on its own', async ( {
		page,
	} ) => {
		await openApp( page, '&view=styleguide' );

		const field = page.locator(
			'checkboxes-field[name="forms"]:has(#library-styleguide-forms)'
		);
		const choices = field.getByRole( 'checkbox' );

		await field.getByRole( 'button', { name: 'Check all' } ).click();
		await expect( choices ).toHaveCount( 4 );
		for ( const choice of await choices.all() ) {
			await expect( choice ).toBeChecked();
		}

		await field.getByRole( 'button', { name: 'Uncheck all' } ).click();
		for ( const choice of await choices.all() ) {
			await expect( choice ).not.toBeChecked();
		}
		await expect(
			field.getByRole( 'button', { name: 'Check all' } )
		).toBeVisible();
	} );

	test( 'fields folded count the ones filled in, in their summary, on their own', async ( {
		page,
	} ) => {
		await openApp( page, '&view=styleguide' );

		const disclosure = page
			.locator( 'fields-disclosure' )
			.filter( { has: page.locator( '#library-styleguide-detail' ) } );
		const summary = disclosure.locator( 'summary' );

		await expect( summary ).toHaveText( 'More details · 1' );
		await summary.click();
		await disclosure.getByLabel( 'Another field' ).fill( 'Typed' );
		await expect( summary ).toHaveText( 'More details · 2' );
	} );
} );
