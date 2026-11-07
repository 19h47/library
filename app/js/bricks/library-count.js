import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatTotal } from 'library-app/format';

/** The number of books of a list, those found or the one its `list` attribute names. */
class LibraryCountElement extends Brick {
	connect() {
		const list = this.getAttribute( 'list' ) ?? 'search';

		return [
			store.select(
				( state ) => formatTotal( state[ list ] ),
				( total ) => {
					this.textContent = total;
				}
			),
		];
	}
}
customElements.define( 'library-count', LibraryCountElement );
