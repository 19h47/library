/**
 * What the page gives the bricks: the store, wp.i18n, which WordPress gives the translations of the modules, and the
 * config of the server.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, libraryApp
 */

export { store } from 'library-app/store';
export const { __, _n, _x, sprintf } = window.wp.i18n;
export const { appUrl, editUrl, notKept, toBuy, roles } = window.libraryApp;
