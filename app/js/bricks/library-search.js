import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The search of the books: what is typed is the query. */
class LibrarySearchElement extends Brick {
	connect( signal ) {
		const $input = this.querySelector( 'input' );

		$input.addEventListener( 'input', () => store.setQuery( $input.value ), { signal } );

		return [
			store.select(
				( state ) => state.query,
				( query ) => {
					if ( $input.value !== query ) {
						$input.value = query;
					}
				}
			),
		];
	}
}
customElements.define( 'library-search', LibrarySearchElement );
