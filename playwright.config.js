/**
 * The tests of the reading app in a browser, on a WordPress of their own: wp-env, or any site given by WP_BASE_URL with
 * its WP_USERNAME and WP_PASSWORD. Each test adds the books it needs, through the route of the book form; a site of the
 * tests is emptied of its books first, never a site with a library.
 *
 * @package Library
 */

import { fileURLToPath } from 'node:url';
import { defineConfig, devices } from '@playwright/test';

// The login of the tests, which the fixtures of @wordpress/e2e-test-utils-playwright read from the environment.
process.env.STORAGE_STATE_PATH ??= fileURLToPath( new URL( './tests/e2e/.auth/admin.json', import.meta.url ) );

export default defineConfig( {
	testDir: './tests/e2e',
	globalSetup: './tests/e2e/global-setup.js',
	// The tests share the books of the site: one at a time.
	workers: 1,
	fullyParallel: false,
	forbidOnly: Boolean( process.env.CI ),
	retries: process.env.CI ? 1 : 0,
	reporter: process.env.CI ? [ [ 'github' ], [ 'html', { open: 'never' } ] ] : 'list',
	use: {
		baseURL: process.env.WP_BASE_URL ?? 'http://localhost:8889',
		storageState: process.env.STORAGE_STATE_PATH,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			// A phone, as the app is used: its width, its density, the view of a phone of the app.
			name: 'phone',
			use: { ...devices[ 'Desktop Chrome' ], viewport: { width: 390, height: 844 }, deviceScaleFactor: 3 },
		},
	],
} );
