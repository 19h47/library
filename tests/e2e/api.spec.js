/**
 * The views for development, opened by searching their name then Enter, and the reference of the API, which lists the
 * routes as the API describes them and tries a GET request.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp } from './library.js';

test.describe( 'API reference', () => {
	let book;

	test.beforeAll( async ( { requestUtils } ) => {
		book = await addBook( requestUtils, { title: 'Reference Book' } );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'a view for development opens with Enter only', async ( {
		page,
	} ) => {
		await openApp( page );

		const search = page.getByRole( 'search' ).getByRole( 'searchbox' );

		await search.fill( 'apiculture' );
		await search.press( 'Enter' );
		await expect( page ).not.toHaveURL( /view=api/ );
		await expect( search ).toHaveValue( 'apiculture' );

		await search.fill( 'styleguide' );
		await expect( page ).not.toHaveURL( /view=styleguide/ );
		await search.press( 'Enter' );
		await expect( page ).toHaveURL( /view=styleguide/ );
	} );

	test( '"api" then Enter opens the reference, which tries a request', async ( {
		page,
	} ) => {
		await openApp( page );

		const search = page.getByRole( 'searchbox' );

		await search.fill( 'api' );
		await search.press( 'Enter' );
		await expect( page ).toHaveURL( /view=api/ );
		await expect( search ).toHaveValue( '' );

		const route = page
			.locator( '.library-api__route' )
			.filter( { hasText: '/library/v1/books/{id}' } );

		await expect( page.locator( '.library-api__route' ) ).not.toHaveCount(
			0
		);
		await route.locator( 'summary' ).click();
		await route
			.getByRole( 'textbox', { name: 'id' } )
			.fill( String( book.id ) );
		await route.getByRole( 'button', { name: 'Try' } ).click();
		await expect( route.getByRole( 'status' ) ).toContainText( '200' );
		await expect( route.locator( 'pre' ) ).toContainText(
			'"title": "Reference Book"'
		);
	} );
} );
