/**
 * The reading app without network: its service worker keeps the app of the reader apart from what it loads, opens it
 * offline, and leaves nothing on the device once the reader logs out.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	APP_PATH,
	addBook,
	deleteAllBooks,
	openApp,
	waitForIdle,
} from './library.js';

/**
 * Waits until the service worker of the app controls it: it does once the app opens again.
 *
 * @param {Object} page The page of the app.
 * @return {Promise<void>}
 */
async function waitForWorker( page ) {
	await page.waitForFunction( async () =>
		Boolean( await navigator.serviceWorker.ready )
	);
	await page.reload();
	await page.waitForFunction( () =>
		Boolean( navigator.serviceWorker.controller )
	);
}

/**
 * What the caches of the origin keep: the paths of their requests, by cache.
 *
 * @param {Object} page The page.
 * @return {Promise<Object<string, string[]>>} The paths, by cache.
 */
function getCaches( page ) {
	return page.evaluate( async () => {
		const names = await window.caches.keys();
		const kept = {};

		for ( const name of names ) {
			kept[ name ] = (
				await ( await window.caches.open( name ) ).keys()
			).map( ( request ) => new URL( request.url ).pathname );
		}

		return kept;
	} );
}

test.describe( 'Offline', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
		await addBook( requestUtils, { title: 'The Offline Test' } );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	test( 'the app is kept apart from what it loads, and opens without network', async ( {
		page,
		context,
	} ) => {
		await openApp( page );
		await waitForWorker( page );
		await page.evaluate( async () => {
			window.store = ( await import( 'library-app/store' ) ).store;
		} );
		await waitForIdle( page );

		const reader = await page.evaluate( () =>
			new URL( window.libraryApp.serviceWorkerUrl ).searchParams.get(
				'reader'
			)
		);
		const caches = await getCaches( page );

		expect( Object.keys( caches ).sort() ).toEqual( [
			`library-app-data-${ reader }`,
			`library-app-shell-${ reader }`,
		] );
		expect( caches[ `library-app-shell-${ reader }` ] ).toContain(
			'/wp-admin/edit.php'
		);
		expect( caches[ `library-app-data-${ reader }` ] ).not.toContain(
			'/wp-admin/edit.php'
		);

		await context.setOffline( true );
		await page.reload();
		await expect( page.getByRole( 'heading', { level: 1 } ) ).toBeVisible();
		// The status of the app, before the views, the styleguide among them.
		await expect( page.locator( 'offline-status' ).first() ).toBeVisible();
		await context.setOffline( false );
	} );
} );

test.describe( 'Logging out', () => {
	// A reader of their own, logged in through the login of the app: logging out ends their session, not the one of the
	// other tests.
	test.use( { storageState: { cookies: [], origins: [] } } );

	test.beforeAll( async ( { requestUtils } ) => {
		await requestUtils.createUser( {
			username: 'reader',
			email: 'reader@example.com',
			password: 'reader',
			roles: [ 'library_reader' ],
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.deleteAllUsers();
	} );

	test( 'leaves nothing of the reader on the device', async ( { page } ) => {
		await page.goto( APP_PATH );
		await page.locator( '#user_login' ).fill( 'reader' );
		await page.locator( '#user_pass' ).fill( 'reader' );
		await page.locator( '#wp-submit' ).click();
		await page.waitForURL( `**${ APP_PATH }` );
		await waitForWorker( page );

		expect( Object.keys( await getCaches( page ) ) ).toHaveLength( 2 );

		// The logout of the admin bar, outside the app.
		await page.goto( '/wp-admin/profile.php' );
		await page.goto(
			await page
				.locator( '#wp-admin-bar-logout a' )
				.getAttribute( 'href' )
		);
		await page.waitForURL( '**/wp-login.php?loggedout=true**' );

		expect( await getCaches( page ) ).toEqual( {} );
		expect(
			await page.evaluate(
				async () =>
					( await navigator.serviceWorker.getRegistrations() ).length
			)
		).toBe( 0 );
	} );
} );
