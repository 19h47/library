import { store, strings } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** Why no book shows: an error, or none found. */
class LibraryStatusElement extends Brick {
	connect() {
		return [
			store.select(
				( { search } ) => search.error || ( search.loaded && ! search.total ? strings.noBooks : '' ),
				( message ) => {
					this.hidden = ! message;
					this.textContent = message;
				}
			),
		];
	}
}
customElements.define( 'library-status', LibraryStatusElement );
