/**
 * The views of the reading app give way to one another in a view transition: the cover of a book opened goes from its
 * card to its page, and back, under a name it takes for the transition only.
 */

import { fileURLToPath } from 'node:url';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

// A photo of a cover.
const PHOTO = fileURLToPath( new URL( './assets/cover.jpg', import.meta.url ) );

test.describe( 'Transitions', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await requestUtils.deleteAllMedia();

		const media = await requestUtils.uploadMedia( PHOTO );

		await addBook( requestUtils, {
			title: 'The Transition Test',
			featured_media: media.id,
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await requestUtils.deleteAllMedia();
	} );

	test( 'the cover of a book goes from its card to its page, and back', async ( {
		page,
	} ) => {
		// Each view transition, with the covers named for it before and after the views change, and once it ended: the one of
		// a card, an article, or the one of the page of a book, in its header.
		await page.addInitScript( () => {
			const start = document.startViewTransition.bind( document );
			const named = () =>
				[ ...document.querySelectorAll( 'img' ) ]
					.filter( ( img ) => img.style.viewTransitionName )
					.map(
						( img ) => img.closest( 'article, header' )?.localName
					);

			window.transitions = [];
			document.startViewTransition = ( update ) => {
				const record = { before: named() };
				const transition = start( async () => {
					await update();
					record.after = named();
				} );

				window.transitions.push( record );
				transition.finished.then(
					() =>
						setTimeout( () => {
							record.ended = named();
						} ),
					( error ) => {
						record.error = error.message;
					}
				);

				return transition;
			};
		} );
		await openApp( page );
		await waitForIdle( page );

		await page
			.locator( 'library-view[name="books"] .library-book-card' )
			.filter( { hasText: 'The Transition Test' } )
			.getByRole( 'link' )
			.click();
		await expect( page.locator( 'library-book h2' ) ).toHaveText(
			'The Transition Test'
		);
		await expect
			.poll( () => page.evaluate( () => window.transitions[ 0 ]?.ended ) )
			.toEqual( [] );

		await page.getByRole( 'button', { name: 'Back' } ).click();
		await expect
			.poll( () => page.evaluate( () => window.transitions[ 1 ]?.ended ) )
			.toEqual( [] );

		expect(
			await page.evaluate( () =>
				window.transitions.map( ( { before, after } ) => ( {
					before,
					after,
				} ) )
			)
		).toEqual( [
			{
				before: [ 'article' ],
				after: [ 'header' ],
			},
			{
				before: [ 'header' ],
				after: [ 'article' ],
			},
		] );
	} );
} );
