import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** Goes back to where the view shown was opened from, as the history does: the page of a book or of a person has one. */
class LibraryBackElement extends Brick {
	connect( signal ) {
		this.querySelector( 'button' ).addEventListener( 'click', () => store.back(), { signal } );

		return [];
	}
}
customElements.define( 'library-back', LibraryBackElement );
