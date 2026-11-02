import { Brick } from 'library-app/brick';
import { back } from 'library-app/router';

/** Goes back to where the view shown was opened from, as the history does: the page of a book or of a person has one. */
class LibraryBackElement extends Brick {
	connect( signal ) {
		this.querySelector( 'button' ).addEventListener( 'click', back, { signal } );

		return [];
	}
}
customElements.define( 'library-back', LibraryBackElement );
