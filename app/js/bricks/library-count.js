import { __, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks } from 'library-app/format';

/** The number of books of a list, those found or the one its `list` attribute names. */
class LibraryCountElement extends Brick {
	connect() {
		const list = this.getAttribute( 'list' ) ?? 'search';

		return [
			store.select(
				( state ) => ( state[ list ].loaded ? state[ list ].total : null ),
				( total ) => {
					this.textContent = null === total ? __( 'Loading…', 'library' ) : formatBooks( total );
				}
			),
		];
	}
}
customElements.define( 'library-count', LibraryCountElement );
