/**
 * The form of a book shows its people by role, a group each, the authors first: a person added goes in the group of the
 * role chosen, a group shows once it holds a name and hides with its last one, and each name is saved with the role of
 * its group.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp } from './library.js';

test.describe( 'People', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the people of a book are grouped by role, and saved with the role of their group', async ( {
		page,
		requestUtils,
	} ) => {
		const book = await addBook( requestUtils, {
			title: 'Codex',
			people: [
				{ name: 'An Author', role: 'author' },
				{ name: 'A Translator', role: 'translator' },
				{ name: 'Another Author', role: 'author' },
			],
		} );

		await openApp( page, `&view=book&book=${ book.id }` );
		await page.getByRole( 'button', { name: 'Edit Book' } ).click();

		const form = page.locator( 'library-edit' );
		const people = form.getByRole( 'group', { name: 'People' } );
		const authors = people.getByRole( 'group', { name: 'Authors' } );
		const translators = people.getByRole( 'group', {
			name: 'Translators',
		} );
		const colorists = people.getByRole( 'group', { name: 'Colorists' } );

		await expect( authors.locator( '[name="person"]' ) ).toHaveCount( 2 );
		await expect(
			authors.locator( '[name="person"]' ).nth( 1 )
		).toHaveValue( 'Another Author' );
		await expect( translators.locator( '[name="person"]' ) ).toHaveValue(
			'A Translator'
		);
		// A role without name hides.
		await expect( colorists ).toBeHidden();

		// A person added goes in the group of the role chosen, which shows.
		await people.getByLabel( 'Role' ).selectOption( 'colorist' );
		await people.getByRole( 'button', { name: 'Add a person' } ).click();
		await expect( colorists ).toBeVisible();
		await expect( colorists.locator( '[name="person"]' ) ).toBeFocused();
		await page.keyboard.type( 'A Colorist' );

		// The last name of a group removed, the group hides.
		await translators
			.getByRole( 'button', { name: 'Remove this person' } )
			.click();
		await expect( translators ).toBeHidden();

		const saved = page.waitForResponse(
			( response ) =>
				'POST' === response.request().method() &&
				/books(\/|%2F)\d+/.test( response.url() )
		);

		await form.getByRole( 'button', { name: 'Save' } ).click();
		await saved;

		const { people: credits } = await requestUtils.rest( {
			path: `/library/v1/books/${ book.id }`,
		} );

		// Each one with its term ID, which the comparison leaves out.
		expect(
			credits.map( ( { name, role } ) => ( { name, role } ) )
		).toEqual(
			expect.arrayContaining( [
				{ name: 'An Author', role: 'author' },
				{ name: 'Another Author', role: 'author' },
				{ name: 'A Colorist', role: 'colorist' },
			] )
		);
		expect( credits ).toHaveLength( 3 );
	} );
} );
