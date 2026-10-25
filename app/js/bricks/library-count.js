import { store, strings } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { plural } from 'library-app/format';

/** The number of books found. */
class LibraryCountElement extends Brick {
	connect() {
		return [
			store.select(
				( { search } ) => ( search.loaded ? search.total : null ),
				( total ) => {
					this.textContent = null === total ? strings.loading : plural( total, strings.books );
				}
			),
		];
	}
}
customElements.define( 'library-count', LibraryCountElement );
