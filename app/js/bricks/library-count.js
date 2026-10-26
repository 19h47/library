import { __, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks } from 'library-app/format';

/** The number of books found. */
class LibraryCountElement extends Brick {
	connect() {
		return [
			store.select(
				( { search } ) => ( search.loaded ? search.total : null ),
				( total ) => {
					this.textContent = null === total ? __( 'Loading…', 'library' ) : formatBooks( total );
				}
			),
		];
	}
}
customElements.define( 'library-count', LibraryCountElement );
