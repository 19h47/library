import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatListStatus } from 'library-app/behaviors';

/** Why no book of a list shows, those found or the one its `list` attribute names: an error, or none found. */
class LibraryStatusElement extends Brick {
	connect() {
		const list = this.getAttribute( 'list' ) ?? 'search';

		return [
			store.select(
				( state ) => formatListStatus( state[ list ] ),
				( message ) => {
					this.hidden = ! message;
					this.textContent = message;
				}
			),
		];
	}
}
customElements.define( 'library-status', LibraryStatusElement );
