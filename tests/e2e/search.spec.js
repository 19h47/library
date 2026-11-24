/**
 * The search of the books: its keywords show while it is used, one taken adds to the query, the cursor in its quotes for
 * the name to type, and the query searches; the name of a view for development, then Enter, opens it.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { openApp, waitForIdle } from './library.js';

test.describe( 'Search', () => {
	test( 'a keyword taken adds to the query, the cursor in its quotes', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const search = page.locator( 'library-search' );
		const field = search.getByRole( 'searchbox' );
		const series = search.getByRole( 'button', { name: 'Series' } );

		await expect( series ).toBeHidden();
		await field.focus();
		await series.click();

		await expect( field ).toHaveValue( 'series:""' );
		await expect( field ).toBeFocused();
		await expect( field ).toHaveJSProperty( 'selectionStart', 8 );
		await page.keyboard.type( 'Dune' );
		await expect( field ).toHaveValue( 'series:"Dune"' );
		await expect
			.poll( () => page.evaluate( () => window.store.get().query ) )
			.toBe( 'series:"Dune"' );
	} );

	test( 'the name of a view for development, then Enter, opens it, the search left empty', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const field = page.locator( 'library-search' ).getByRole( 'searchbox' );

		await field.fill( 'styleguide' );
		await field.press( 'Enter' );

		await expect( page ).toHaveURL( /view=styleguide/ );
		// The search, hidden with the books, left empty.
		await expect( page.locator( 'library-search input' ) ).toHaveValue(
			''
		);
	} );
} );
