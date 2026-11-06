/**
 * The views of the reading app: the tabs on a phone, the name of the page, and the links of the books.
 *
 * @package Library
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

const AUTHOR = 'Ada Navigation';
const SERIES = 'Navigation Series';

test.describe( 'Navigation', () => {
	let books = [];

	test.beforeAll( async ( { requestUtils } ) => {
		books = [];

		for ( const volume of [ 1, 2, 3 ] ) {
			books.push(
				await addBook( requestUtils, {
					title: `Volume ${ volume }`,
					series: SERIES,
					people: [ { name: AUTHOR, role: 'author' } ],
					meta: { library_volume_number: String( volume ) },
				} )
			);
		}
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	for ( const width of [ 320, 390 ] ) {
		test( `the tabs of the views all show at ${ width } px`, async ( { page } ) => {
			await page.setViewportSize( { width, height: 700 } );
			await openApp( page );

			const tabs = page.getByRole( 'navigation' ).getByRole( 'link' );

			await expect( tabs ).toHaveCount( 5 );

			for ( const tab of await tabs.all() ) {
				const box = await tab.boundingBox();
				const overflows = await tab.evaluate( ( link ) => link.scrollWidth > link.clientWidth );

				expect( box.x ).toBeGreaterThanOrEqual( 0 );
				expect( box.x + box.width ).toBeLessThanOrEqual( width );
				expect( overflows ).toBe( false );
			}
		} );
	}

	test( 'the page is named after what it shows', async ( { page } ) => {
		await openApp( page );

		const appName = await page.title();

		expect( appName ).not.toContain( '–' );

		await page.getByRole( 'navigation' ).getByRole( 'link', { name: 'Diary' } ).click();
		await expect( page ).toHaveTitle( `Diary – ${ appName }` );

		await page.goto( page.url().replace( /&view=diary.*/, `&view=book&book=${ books[ 0 ].id }` ) );
		await expect( page ).toHaveTitle( new RegExp( `^${ SERIES }, .*Volume 1.* – ${ appName }$` ) );
	} );

	test( 'a book opens from its card, in the app', async ( { page } ) => {
		await openApp( page );
		await waitForIdle( page );
		await page.evaluate( () => {
			window.isSameDocument = true;
		} );

		const card = page.locator( 'library-list .library-item__link' ).first();

		await expect( card ).toHaveAttribute( 'href', /view=book&book=\d+/ );
		await card.click();
		await page.waitForFunction( () => 'book' === window.store.get().view );

		expect( await page.evaluate( () => window.isSameDocument ) ).toBe( true );
	} );

	test( 'the volume open is the current page among its series', async ( { page } ) => {
		await openApp( page, `&view=book&book=${ books[ 1 ].id }` );
		await page.waitForFunction( () => window.store.get().volumes.loaded );

		const current = page.locator( 'library-volumes [aria-current="page"]' );

		await expect( current ).toHaveCount( 1 );
		await expect( current ).toHaveAttribute( 'href', new RegExp( `book=${ books[ 1 ].id }$` ) );
	} );
} );
