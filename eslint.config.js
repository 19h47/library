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
				'library-app/component',
				'library-app/behaviors',
				'library-app/format',
				'library-app/render',
				'library-app/router',
				'library-app/rows',
				'library-app/term-page',
			],
		},
	},
	{
		// A component is plugged in and out as a package, through its API alone: it reads no state of the app.
		files: [ 'app/js/components/**' ],
		rules: {
			'no-restricted-imports': [
				'error',
				{
					paths: [
						'library-app/store',
						'library-app/state',
						'library-app/router',
						'library-app/brick',
						'library-app/term-page',
					].map( ( name ) => ( {
						name,
						message:
							'A component reads no state of the app: its place sets its API, its attributes and its properties.',
					} ) ),
				},
			],
		},
	},
	{
		// A script reaches an element by a data attribute or by what it is, never by a class of its styles.
		files: [ 'app/js/**', 'admin/js/**' ],
		rules: {
			'no-restricted-syntax': [
				'error',
				...[
					'Literal[value',
					'TemplateLiteral > TemplateElement[value.raw',
				].map( ( node ) => ( {
					selector: `CallExpression[callee.property.name=/^(querySelector|querySelectorAll|closest|matches)$/] > ${ node }=/(^|[\\s>+~,(])\\.[A-Za-z_-]/]`,
					message:
						'Reach an element by a data attribute, such as data-part, or by what it is, never by a class of its styles.',
				} ) ),
			],
		},
	},
	...wordpress.configs[ 'test-playwright' ].map( ( config ) => ( {
		...config,
		files: [ 'tests/e2e/**' ],
	} ) ),
];
