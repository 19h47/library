import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatOfList, formatTotal } from 'library-app/behaviors';

/** The number of books of a list, those found or the one its `list` attribute names, or of the readings of the diary. */
class LibraryCountElement extends Brick {
	connect() {
		const list = this.getAttribute( 'list' ) ?? 'search';

		return [
			store.select(
				( state ) => formatTotal( state[ list ], formatOfList( list ) ),
				( total ) => {
					this.textContent = total;
				}
			),
		];
	}
}
customElements.define( 'library-count', LibraryCountElement );
