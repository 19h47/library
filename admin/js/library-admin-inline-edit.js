/**
 * Quick edit: prefill the book fields from the row inline data, one element per field, its class the meta key.
 *
 * Extends inlineEditPost.edit (WordPress core), which fills its own fields
 * from the hidden #inline_{id} block the same way.
 */
( () => {
	'use strict';

	const originalEdit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id, ...args ) {
		originalEdit.call( this, id, ...args );

		const postId = typeof id === 'object' ? this.getId( id ) : id;
		const inlineData = document.getElementById( `inline_${ postId }` );
		const editRow = document.getElementById( `edit-${ postId }` );

		if ( ! inlineData || ! editRow ) {
			return;
		}

		inlineData
			.querySelectorAll( '[class^="library_"]' )
			.forEach( ( value ) => {
				/** @type {HTMLInputElement|HTMLSelectElement} */
				const control = editRow.querySelector(
					`[name="${ value.className }"]`
				);

				if ( control ) {
					control.value = value.textContent;
				}
			} );
	};
} )();
