import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The filter of the books by reading state: the search keyword read:. */
class LibraryReadFilterElement extends Brick {
	connect( signal ) {
		const $select = this.querySelector( 'select' );

		$select.addEventListener( 'change', () => store.setRead( $select.value ), { signal } );

		return [
			store.select(
				( state ) => state.read,
				( read ) => {
					$select.value = read;
				}
			),
		];
	}
}
customElements.define( 'library-read-filter', LibraryReadFilterElement );
