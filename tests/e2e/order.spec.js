/**
 * The order of the books, beside their filters: the books found in the order chosen, and its first choice named the
 * reading order for a search for a series, which the server lists so.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

test.describe( 'Order', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );

		for ( const title of [ 'Order Beta', 'Order Gamma', 'Order Alpha' ] ) {
			await addBook( requestUtils, { title, shelf: { readings: [] } } );
		}
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the books in the order chosen, the reading order for a series', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const view = page.locator( 'library-view[name="books"]' );
		const order = view.getByLabel( 'Order' );
		const titles = view.locator( 'library-list .library-book-card__title' );

		await expect( titles ).toHaveCount( 3 );
		await order.selectOption( 'title' );
		await expect( titles ).toHaveText( [
			'Order Alpha',
			'Order Beta',
			'Order Gamma',
		] );

		await openApp( page, '&q=series:Dune' );
		await expect( order.locator( 'option' ).first() ).toHaveText(
			'Reading order'
		);
		await expect( order ).toHaveValue( 'title' );
	} );
} );
