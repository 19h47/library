/**
 * The form of a book reads the ISBN from the photo of its barcode, where the browser reads EAN-13 barcodes; elsewhere,
 * the pill that scans it does not show. The Barcode Detection API is given by the test, which says what it reads, as
 * Chrome has it on Android only, and the catalogue of the BnF answers that it has no book of that ISBN.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { deleteAllBooks, openApp } from './library.js';

// The route of the catalogue, in a pretty or a plain permalink.
const ROUTE = /books(\/|%2F)bnf/;

// A photo, of a pixel: what the barcode detector of the test reads in it is given apart.
const PHOTO = {
	name: 'barcode.png',
	mimeType: 'image/png',
	buffer: Buffer.from(
		'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==',
		'base64'
	),
};

/**
 * Gives the page a Barcode Detection API that reads `rawValue` in any image, or no barcode without one.
 *
 * @param {Object}      page     The page.
 * @param {string|null} rawValue The barcode read.
 * @return {Promise<void>}
 */
function detectBarcode( page, rawValue ) {
	return page.addInitScript( ( value ) => {
		window.BarcodeDetector = class {
			static async getSupportedFormats() {
				return [ 'ean_13' ];
			}

			async detect() {
				return value ? [ { rawValue: value, format: 'ean_13' } ] : [];
			}
		};
	}, rawValue );
}

test.describe( 'Scan', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test.beforeEach( async ( { page } ) => {
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
	} );

	test( 'the barcode of a book types its ISBN', async ( { page } ) => {
		await detectBarcode( page, '9782266089753' );
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );

		await form.getByLabel( 'Scan the barcode' ).setInputFiles( PHOTO );

		await expect( form.locator( '[name="isbn"]' ) ).toHaveValue(
			'9782266089753'
		);
		// The ISBN is searched as if typed.
		await expect(
			form.getByText(
				'The catalogue of the BnF has no book of this ISBN.'
			)
		).toBeVisible();
	} );

	test( 'a photo without a barcode says so', async ( { page } ) => {
		await detectBarcode( page, null );
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		const form = page.locator( 'library-edit' );

		await form.getByLabel( 'Scan the barcode' ).setInputFiles( PHOTO );

		await expect(
			form.getByText( 'No barcode found in the photo' )
		).toBeVisible();
		await expect( form.locator( '[name="isbn"]' ) ).toHaveValue( '' );

		// The ISBN typed instead, the message goes.
		await form.locator( '[name="isbn"]' ).fill( '978' );
		await expect(
			form.getByText( 'No barcode found in the photo' )
		).toBeHidden();
	} );

	test( 'the message of a photo without a barcode goes as the form opens again', async ( {
		page,
	} ) => {
		await detectBarcode( page, null );
		await openApp( page );

		const form = page.locator( 'library-edit' );
		const add = page.getByRole( 'button', { name: 'Add New Book' } );

		await add.click();
		await form.getByLabel( 'Scan the barcode' ).setInputFiles( PHOTO );
		await expect(
			form.getByText( 'No barcode found in the photo' )
		).toBeVisible();

		await page.keyboard.press( 'Escape' );
		await add.click();
		await expect(
			form.getByText( 'No barcode found in the photo' )
		).toBeHidden();
	} );

	test( 'a browser that reads no barcode shows no pill', async ( {
		page,
	} ) => {
		await page.addInitScript( () => delete window.BarcodeDetector );
		await openApp( page );
		await page.getByRole( 'button', { name: 'Add New Book' } ).click();

		await expect(
			page.locator( 'library-edit' ).getByLabel( 'Scan the barcode' )
		).toBeHidden();
	} );
} );
