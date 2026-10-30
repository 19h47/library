/**
 * Quick edit: prefill the book fields from the row inline data, one element per field, its class the meta key.
 *
 * Extends inlineEditPost.edit (WordPress core), which fills its own fields
 * from the hidden #inline_{id} block the same way.
 *
 * Also adds the "Not kept" and "To buy" statuses to the quick and bulk edit status menus, which only list
 * core statuses: without them, quick edit would publish a book not kept or to buy.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     inlineEditPost, libraryInlineEdit
 */
( () => {
	'use strict';

	const { statusChoices } = window.libraryInlineEdit;
	const originalEdit = inlineEditPost.edit;

	document.querySelectorAll( '#inlineedit select[name="_status"]' ).forEach( ( select ) => {
		Object.entries( statusChoices ).forEach( ( [ status, label ] ) => select.add( new Option( label, status ) ) );
	} );

	inlineEditPost.edit = function ( id, ...args ) {
		originalEdit.call( this, id, ...args );

		const postId     = typeof id === 'object' ? this.getId( id ) : id;
		const inlineData = document.getElementById( `inline_${ postId }` );
		const editRow    = document.getElementById( `edit-${ postId }` );

		if ( ! inlineData || ! editRow ) {
			return;
		}

		inlineData.querySelectorAll( '[class^="library_"]' ).forEach( ( value ) => {
			const control = editRow.querySelector( `[name="${ value.className }"]` );

			if ( control ) {
				control.value = value.textContent;
			}
		} );
	};
} )();
