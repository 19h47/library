/**
 * The reading app without network: its service worker keeps the app apart from what it loads, and opens it offline.
 *
 * @package Library
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp, waitForIdle } from './library.js';

test.describe( 'Offline', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await addBook( requestUtils, { title: 'The Offline Test' } );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the app is kept apart from what it loads, and opens without network', async ( { page, context } ) => {
		await openApp( page );
		await page.waitForFunction( async () => Boolean( await navigator.serviceWorker.ready ) );
		// The worker controls the app once it opens again.
		await page.reload();
		await page.waitForFunction( () => Boolean( navigator.serviceWorker.controller ) );
		await page.evaluate( async () => {
			window.store = ( await import( 'library-app/store' ) ).store;
		} );
		await waitForIdle( page );

		const caches = await page.evaluate( async () => {
			const names = await window.caches.keys();
			const kept = {};

			for ( const name of names ) {
				kept[ name ] = ( await ( await window.caches.open( name ) ).keys() ).map( ( request ) => new URL( request.url ).pathname );
			}

			return kept;
		} );

		expect( Object.keys( caches ).sort() ).toEqual( [ 'library-app-data', 'library-app-shell' ] );
		expect( caches[ 'library-app-shell' ] ).toContain( '/wp-admin/edit.php' );
		expect( caches[ 'library-app-data' ] ).not.toContain( '/wp-admin/edit.php' );

		await context.setOffline( true );
		await page.reload();
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toBeVisible();
		await context.setOffline( false );
	} );
} );
