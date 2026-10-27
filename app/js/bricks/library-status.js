import { __, store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** Why no book of a list shows, those found or the one its `list` attribute names: an error, or none found. */
class LibraryStatusElement extends Brick {
	connect() {
		const list = this.getAttribute( 'list' ) ?? 'search';

		return [
			store.select(
				( state ) => state[ list ].error || ( state[ list ].loaded && ! state[ list ].total ? __( 'No book found.', 'library' ) : '' ),
				( message ) => {
					this.hidden = ! message;
					this.textContent = message;
				}
			),
		];
	}
}
customElements.define( 'library-status', LibraryStatusElement );
