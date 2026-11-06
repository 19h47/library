/**
 * Before the tests: logs in, as the fixtures of WordPress then do from its state, and empties the site of its books, so
 * that each test finds only the books it adds.
 */

import { request } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { deleteAllBooks } from './library.js';

export default async function globalSetup( config ) {
	const { baseURL, storageState } = config.projects[ 0 ].use;
	const context = await request.newContext( { baseURL } );
	const requestUtils = new RequestUtils( context, {
		baseURL,
		storageStatePath: storageState,
	} );

	await requestUtils.setupRest();
	await deleteAllBooks( requestUtils );
	await requestUtils.deleteAllMedia();
	await context.dispose();
}
