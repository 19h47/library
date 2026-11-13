/**
 * The lists of books show as a grid of their covers when chosen, the choice kept: each card its cover alone, its title
 * still the name of its link, which opens the book; a book without a cover shows its title.
 */

import { fileURLToPath } from 'node:url';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

// A photo of a cover.
const PHOTO = fileURLToPath( new URL( './assets/cover.jpg', import.meta.url ) );

test.describe( 'Grid', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await requestUtils.deleteAllMedia();

		const media = await requestUtils.uploadMedia( PHOTO );

		await addBook( requestUtils, {
			title: 'The Grid With Cover',
			featured_media: media.id,
		} );
		await addBook( requestUtils, { title: 'The Grid Without Cover' } );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await requestUtils.deleteAllMedia();
	} );

	test( 'the books show as a grid of their covers, the choice kept', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const books = page.locator( 'library-view[name="books"]' );
		const toggle = books.getByRole( 'checkbox', { name: 'Covers' } );
		const list = books.locator( 'library-list .app-list' );

		await expect( toggle ).not.toBeChecked();
		await toggle.check();
		await expect( toggle ).toBeChecked();
		await expect( list ).toHaveAttribute( 'data-covers' );

		// A book without a cover shows its title in the frame of its cover; the title of a card is the name of its link only.
		await expect(
			list
				.locator( '[data-field="cover_title"]' )
				.filter( { hasText: 'The Grid Without Cover' } )
		).toBeVisible();
		await expect(
			list.getByRole( 'link', { name: 'The Grid With Cover' } )
		).toHaveCSS( 'opacity', '1' );
		await expect(
			list
				.locator( '.library-book-card__title' )
				.filter( { hasText: 'The Grid With Cover' } )
		).toHaveCSS( 'opacity', '0' );

		// Opened again, as the next time.
		await openApp( page );
		await waitForIdle( page );
		await expect( list ).toHaveAttribute( 'data-covers' );
		await expect( toggle ).toBeChecked();

		// A tap on the cover opens the book: the link of the card covers it.
		await list
			.locator( '.library-book-card' )
			.filter( { hasText: 'The Grid With Cover' } )
			.click();
		await expect( page.locator( 'library-book h2' ) ).toHaveText(
			'The Grid With Cover'
		);
	} );
} );
