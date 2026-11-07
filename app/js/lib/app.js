/**
 * What the page gives the modules, from WordPress: wp.apiFetch, which handles the root URL and the nonce and answers the
 * requests the page preloads, wp.i18n, which WordPress gives the translations of each module, wp.url, and the config of
 * the server. No other module reads the page for them.
 */

export const { apiFetch } = window.wp;
export const { __, _n, _x, sprintf } = window.wp.i18n;
export const { addQueryArgs } = window.wp.url;
export const {
	appUrl,
	bookFields,
	editUrl,
	forms,
	icons,
	inLibrary,
	maxPerPage,
	notKept,
	perPage,
	roles,
	serviceWorkerUrl,
	statuses,
	storageKey,
	toBuy,
} = window.libraryApp;
