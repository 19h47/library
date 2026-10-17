/**
 * Quick edit: prefill the book fields from the row inline data (library_{meta key}).
 *
 * Extends inlineEditPost.edit (WordPress core), which fills its own fields
 * from the hidden #inline_{id} block the same way.
 *
 * Also adds the "Not kept" status to the quick and bulk edit status menus, which only list
 * core statuses: without it, quick edit would publish a book not kept.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     inlineEditPost, libraryInlineEdit
 */
( () => {
	'use strict';

	const { notKeptStatus, notKeptLabel } = window.libraryInlineEdit;
	const originalEdit                    = inlineEditPost.edit;

	document.querySelectorAll( '#inlineedit select[name="_status"]' ).forEach( ( select ) => {
		select.add( new Option( notKeptLabel, notKeptStatus ) );
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
			const field = value.className.replace( 'library_', '' );
			const input = editRow.querySelector( `input[name="${ field }"]` );

			if ( input ) {
				input.value = value.textContent;
			}
		} );
	};
} )();
