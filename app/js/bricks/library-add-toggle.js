import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The button that opens the form of a book to add. */
class LibraryAddToggleElement extends Brick {
	connect( signal ) {
		this.querySelector( 'button' ).addEventListener( 'click', () => store.openEdit(), { signal } );

		return [];
	}
}
customElements.define( 'library-add-toggle', LibraryAddToggleElement );
