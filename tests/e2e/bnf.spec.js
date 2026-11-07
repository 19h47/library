/**
 * The form of a book to add takes the values of the catalogue of the BnF for the ISBN typed, in the fields left empty,
 * and says what the catalogue answered. The route of the catalogue answers as the plugin would: the catalogue itself is
 * not asked. The route is intercepted for the whole context, as the service worker sends the requests of the app.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { deleteAllBooks, openApp } from './library.js';

// The route of the catalogue, in a pretty or a plain permalink.
const ROUTE = /books(\/|%2F)bnf/;

// A book of the catalogue, as the route gives it.
const BOOK = {
	title: 'Sentinelles',
	people: [
		{ name: 'Margaret Weis', role: 'author' },
		{ name: 'Simone Hilling', role: 'translator' },
	],
	publisher: 'Pocket',
	collection: '',
	series: 'Le bouclier des étoiles',
	languages: [],
	form: 0,
	meta: {
		library_subtitle: '',
		library_volume_number: 'Tome 01',
		library_collection_number: '5702',
		library_pages: '',
		library_edition_languages: [],
	},
};

test.describe( 'BnF', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the catalogue fills in the fields left empty', async ( {
		page,
	} ) => {
		await page
			.context()
			.route( ROUTE, ( route ) => route.fulfill( { json: BOOK } ) );
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );
		const title = form.locator( '[name="title"]' );

		await title.fill( 'Typed before' );
		await form
			.locator( '[name="library_isbn"]' )
			.fill( '978-2-266-08975-3' );
		await form.locator( '[name="library_isbn"]' ).press( 'Tab' );

		await expect(
			form.getByText( 'Filled in from the catalogue of the BnF' )
		).toBeVisible();
		// What was typed stays.
		await expect( title ).toHaveValue( 'Typed before' );
		await expect( form.locator( '[name="person"]' ).nth( 0 ) ).toHaveValue(
			'Margaret Weis'
		);
		await expect( form.locator( '[name="person"]' ).nth( 1 ) ).toHaveValue(
			'Simone Hilling'
		);
		await expect( form.locator( '[name="role"]' ).nth( 1 ) ).toHaveValue(
			'translator'
		);
		await expect( form.locator( '[name="publisher"]' ) ).toHaveValue(
			'Pocket'
		);
		await expect( form.locator( '[name="series"]' ) ).toHaveValue(
			'Le bouclier des étoiles'
		);
		await expect(
			form.locator( '[name="library_volume_number"]' )
		).toHaveValue( 'Tome 01' );
		await expect(
			form.locator( '[name="library_collection_number"]' )
		).toHaveValue( '5702' );
	} );

	test( 'an ISBN the catalogue does not know leaves the form as it is', async ( {
		page,
	} ) => {
		await page.context().route( ROUTE, ( route ) =>
			route.fulfill( {
				status: 404,
				json: {
					code: 'library_not_in_bnf',
					message:
						'The catalogue of the BnF has no book of this ISBN.',
					data: { status: 404 },
				},
			} )
		);
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );

		await form
			.locator( '[name="library_isbn"]' )
			.fill( '978-2-266-08975-3' );
		await form.locator( '[name="library_isbn"]' ).press( 'Tab' );

		await expect(
			form.getByText(
				'The catalogue of the BnF has no book of this ISBN.'
			)
		).toBeVisible();
		await expect( form.locator( '[name="title"]' ) ).toHaveValue( '' );
	} );
} );
