/**
 * The covers of the reading app: sharp on a dense screen, in their shape, a photo picked for a cover too, held by their
 * card from the list to the grid and back.
 */

import { fileURLToPath } from 'node:url';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	addBook,
	deleteAllBooks,
	getBook,
	openApp,
	waitForIdle,
} from './library.js';

// A photo of a cover, 1200 by 1800, from which WordPress makes the sizes of a srcset.
const PHOTO = fileURLToPath( new URL( './assets/cover.jpg', import.meta.url ) );

/**
 * The shape of an image as it shows and as it is, width over height, and the source the browser took.
 *
 * @param {Object} image Locator of the image.
 * @return {Promise<{shown:number,natural:number,source:string,src:string}>} Its shapes and sources.
 */
function shapeOf( image ) {
	return image.evaluate( ( img ) => {
		const box = img.getBoundingClientRect();

		return {
			shown: box.width / box.height,
			natural: img.naturalWidth / img.naturalHeight,
			source: img.currentSrc,
			src: img.src,
		};
	} );
}

test.describe( 'Covers', () => {
	let book;

	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await requestUtils.deleteAllMedia();

		const media = await requestUtils.uploadMedia( PHOTO );

		book = await addBook( requestUtils, {
			title: 'The Cover Test',
			featured_media: media.id,
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await requestUtils.deleteAllMedia();
	} );

	test( 'the cover of a book takes a sharper size on a dense screen, in its shape', async ( {
		page,
	} ) => {
		await openApp( page, `&view=book&book=${ book.id }` );
		await waitForIdle( page );

		const cover = page.locator(
			'library-book > header [data-field="cover"]'
		);

		await expect( cover ).toHaveAttribute( 'sizes', 'auto' );
		// Once the source it takes is loaded, which gives its shape.
		await expect
			.poll( async () => ( await shapeOf( cover ) ).natural )
			.toBeGreaterThan( 0 );

		const shape = await shapeOf( cover );

		// The medium size, its source, is 200 wide: three times the width it shows takes a larger one.
		expect( shape.source ).not.toBe( shape.src );
		expect( shape.shown ).toBeCloseTo( shape.natural, 1 );
	} );

	test( 'a card holds its cover, from the list to the grid and back', async ( {
		page,
	} ) => {
		await openApp( page );
		await waitForIdle( page );

		const books = page.locator( 'library-view[name="books"]' );
		const toggle = books.getByRole( 'checkbox', { name: 'Covers' } );
		const card = books
			.locator( 'library-list .library-book-card' )
			.filter( { hasText: 'The Cover Test' } );

		// The height of the card and of its cover: a card Safari leaves undrawn is as high as its padding, or of none.
		const heightsOf = () =>
			card.evaluate( ( item ) => ( {
				card: item.getBoundingClientRect().height,
				cover: item
					.querySelector( '[data-part="cover"] > img' )
					.getBoundingClientRect().height,
			} ) );

		for ( const covers of [ false, true, false ] ) {
			await toggle.setChecked( covers );
			await expect
				.poll( async () => ( await heightsOf() ).cover )
				.toBeGreaterThan( 0 );

			const heights = await heightsOf();

			expect( heights.card ).toBeGreaterThanOrEqual( heights.cover );
		}
	} );

	test( 'a photo picked for a cover shows in its shape', async ( {
		page,
	} ) => {
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );

		await form.getByLabel( 'Choose a cover' ).setInputFiles( PHOTO );

		const preview = form.locator( '[data-part="preview"] > img' );

		await expect( preview ).toBeVisible();
		await expect
			.poll( async () => ( await shapeOf( preview ) ).natural )
			.toBeGreaterThan( 0 );

		const shape = await shapeOf( preview );

		expect( await preview.getAttribute( 'sizes' ) ).toBeNull();
		expect( shape.shown ).toBeCloseTo( shape.natural, 1 );
	} );

	test( 'a photo picked shows Remove, which empties the field, the focus going to Choose a cover', async ( {
		page,
	} ) => {
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );
		const remove = form.getByRole( 'button', { name: 'Remove the cover' } );

		await expect( remove ).toBeHidden();
		await form.getByLabel( 'Choose a cover' ).setInputFiles( PHOTO );
		await expect( remove ).toBeVisible();

		await remove.click();
		await expect( remove ).toBeHidden();
		await expect(
			form.locator( '[data-part="preview"] > img' )
		).toBeHidden();
		await expect( form.getByLabel( 'Choose a cover' ) ).toBeFocused();
		await expect( form.getByLabel( 'Choose a cover' ) ).toHaveValue( '' );
	} );

	test( 'the cover of a book removed is saved without it, the form unchecked once open again', async ( {
		page,
		requestUtils,
	} ) => {
		const media = await requestUtils.uploadMedia( PHOTO );
		const covered = await addBook( requestUtils, {
			title: 'The Removed Cover Test',
			featured_media: media.id,
		} );

		await openApp( page, `&view=book&book=${ covered.id }` );
		await page.getByRole( 'button', { name: 'Edit Book' } ).click();

		const form = page.locator( 'library-edit' );
		const remove = form.getByRole( 'button', { name: 'Remove the cover' } );
		const removed = form.locator( '[name="cover_removed"]' );

		await expect(
			form.locator( '[data-part="preview"] > img' )
		).toBeVisible();
		await remove.click();
		await expect( removed ).toBeChecked();
		await form.getByRole( 'button', { name: 'Save' } ).click();
		await expect
			.poll(
				async () =>
					( await getBook( requestUtils, covered.id ) ).featured_media
			)
			.toBe( 0 );

		await waitForIdle( page );
		await page.getByRole( 'button', { name: 'Edit Book' } ).click();
		await expect( removed ).not.toBeChecked();
		await expect( remove ).toBeHidden();
	} );
} );
