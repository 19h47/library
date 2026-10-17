/**
 * Quick edit: prefill the book fields in the inline edit row.
 *
 * Extends inlineEditPost.edit (WordPress core) then fills custom fields.
 *
 * @package    Library
 * @subpackage Library/admin
 * @global     inlineEditPost
 */
( () => {
	'use strict';

	const fields = [ 'isbn', 'book_editions', 'read_date', 'rating' ];

	// Wrap WordPress quick edit like core does for custom columns.
	const originalEdit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id, ...args ) {
		originalEdit.call( this, id, ...args );

		const postId  = typeof id === 'object' ? parseInt( this.getId( id ), 10 ) : 0;
		const editRow = document.getElementById( `edit-${ postId }` );

		if ( ! editRow ) {
			return;
		}

		fields.forEach( ( field ) => {
			const source = document.getElementById( `library-${ field }-${ postId }` );
			const input  = editRow.querySelector( `input[name="${ field }"]` );

			if ( source && input ) {
				input.value = ( source.value ?? source.textContent ).trim();
			}
		} );
	};
} )();
