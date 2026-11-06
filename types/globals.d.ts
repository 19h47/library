/**
 * What the pages of the plugin give their scripts: the packages of WordPress as wp, and the data of the reading app,
 * which get_config() of the app prints.
 */

import type apiFetch from '@wordpress/api-fetch';
import type * as i18n from '@wordpress/i18n';
import type * as url from '@wordpress/url';
import type * as a11y from '@wordpress/a11y';

declare global {
	interface Window {
		wp: {
			apiFetch: typeof apiFetch;
			i18n: typeof i18n;
			url: typeof url;
			a11y: typeof a11y;
		};
		libraryApp: {
			statuses: string;
			bookFields: string;
			perPage: number;
			maxPerPage: number;
			forms: string;
			notKept: string;
			toBuy: string;
			roles: string[];
			editUrl: string;
			appUrl: string;
			serviceWorkerUrl: string;
			icons: { light: string; dark: string };
		};
		libraryInlineEdit: {
			statusChoices: Record< string, string >;
		};
	}
}

export {};
