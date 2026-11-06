/**
 * The covers of the reading app: sharp on a dense screen, in their shape, a photo picked for a cover too.
 */

import { fileURLToPath } from 'node:url';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

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

		const cover = page.locator( 'library-book .library-book__cover' );

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

	test( 'a photo picked for a cover shows in its shape', async ( {
		page,
	} ) => {
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );

		await form.locator( 'input[type="file"]' ).setInputFiles( PHOTO );

		const preview = form.locator( '.library-image-field__preview' );

		await expect( preview ).toBeVisible();
		await expect
			.poll( async () => ( await shapeOf( preview ) ).natural )
			.toBeGreaterThan( 0 );

		const shape = await shapeOf( preview );

		expect( await preview.getAttribute( 'sizes' ) ).toBeNull();
		expect( shape.shown ).toBeCloseTo( shape.natural, 1 );
	} );
} );
