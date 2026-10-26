/**
 * What the page gives the bricks: the store, created in the head, wp.i18n, which WordPress gives the translations
 * of the modules, and the config of the server.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, store, libraryApp
 */

export const { store } = window;
export const { __, _n, _x, sprintf } = window.wp.i18n;
export const { editUrl, authorUrl, bookUrl, searchUrl, notKept } = window.libraryApp;
