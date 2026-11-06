/**
 * The checks of the scripts of the plugin, with the rules of WordPress: the reading app, the scripts of the admin and
 * the tests of the app.
 */

import globals from 'globals';
import wordpress from '@wordpress/eslint-plugin';

export default [
	{
		ignores: [
			'vendor/**',
			'node_modules/**',
			'languages/**',
			'playwright-report/**',
			'test-results/**',
		],
	},
	...wordpress.configs.recommended,
	{
		// No React here: the rules of WordPress bring its plugin, which would look for it.
		settings: { react: { version: '19.0' } },
	},
	{
		files: [ 'app/js/**', 'admin/js/**' ],
		languageOptions: {
			globals: { ...globals.browser, wp: 'readonly' },
		},
	},
	{
		// The quick edit of the books list, which WordPress gives the page.
		files: [ 'admin/js/**' ],
		languageOptions: {
			globals: { inlineEditPost: 'readonly' },
		},
	},
	{
		// A worker, its own global scope.
		files: [ 'app/js/library-app-service-worker.js' ],
		languageOptions: {
			globals: globals.serviceworker,
		},
	},
	{
		// The modules of the app by the names of the import map of the page.
		files: [ 'app/js/**' ],
		settings: {
			'import/core-modules': [
				'library-app/app',
				'library-app/store',
				'library-app/state',
				'library-app/api',
				'library-app/brick',
				'library-app/behaviors',
				'library-app/format',
				'library-app/render',
				'library-app/router',
				'library-app/term-page',
			],
		},
	},
	...wordpress.configs[ 'test-playwright' ].map( ( config ) => ( {
		...config,
		files: [ 'tests/e2e/**' ],
	} ) ),
];
