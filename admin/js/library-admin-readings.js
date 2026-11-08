/**
 * Readings of the book information metabox: "Add a reading" adds one, ended today unless changed, and each reading has
 * a button that removes it, as in the reading app.
 */
( () => {
	'use strict';

	const fieldset = document.querySelector( '.library-readings' );

	if ( ! fieldset ) {
		return;
	}

	const list = fieldset.querySelector( 'ul' );
	/** @type {HTMLButtonElement} */
	const add = fieldset.querySelector( '[data-action="add"]' );
	const template = fieldset.querySelector( 'template' );

	add.addEventListener( 'click', () => {
		const item = /** @type {HTMLElement} */ (
			template.content.firstElementChild.cloneNode( true )
		);

		list.append( item );
		item.querySelector( 'input' ).focus();
	} );

	list.addEventListener( 'click', ( event ) => {
		const remove = /** @type {Element} */ ( event.target ).closest(
			'[data-action="remove"]'
		);

		if ( remove ) {
			remove.closest( 'li' ).remove();
			add.focus();
		}
	} );
} )();
