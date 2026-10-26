/**
 * What the page gives the bricks: the store, created in the head, wp.i18n, and the config of the server.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, store, libraryApp
 */

export const { store } = window;
export const { sprintf } = window.wp.i18n;
export const { strings, editUrl, authorUrl, bookUrl, searchUrl, notKept } = window.libraryApp;
