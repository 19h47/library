import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The filter of the books by status: in the library, not kept or to buy. */
class LibraryStatusFilterElement extends Brick {
	connect( signal ) {
		const $select = this.querySelector( 'select' );

		$select.addEventListener( 'change', () => store.setStatus( $select.value ), { signal } );

		return [
			store.select(
				( state ) => state.status,
				( status ) => {
					$select.value = status;
				}
			),
		];
	}
}
customElements.define( 'library-status-filter', LibraryStatusFilterElement );
