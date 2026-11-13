import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatState } from 'library-app/format';
import {
	followCards,
	observeEnd,
	renderBooks,
	showBusy,
} from 'library-app/behaviors';

/** @import { List } from 'library-app/store' */

/**
 * The books of a list, those found or the one its `list` attribute names, such as `toBuy`, loading the next ones as the
 * end of the list comes: a card per book, kept while the book stays in the list, or a grid of their covers, as chosen.
 * Loading again from its start, as for another search, the list shows its books faded and busy until the new ones come.
 */
class LibraryListElement extends Brick {
	connect( signal ) {
		const list = /** @type {List} */ (
			this.getAttribute( 'list' ) ?? 'search'
		);
		const $items = this.querySelector( 'ul' );
		const observe = observeEnd(
			this.querySelector( '[data-part="more"]' ),
			signal,
			() => store.loadMoreBooks( list )
		);

		return [
			followCards( $items, formatState ),
			store.select(
				( state ) => state[ list ].items,
				( ids ) => {
					renderBooks( $items, ids, formatState );
					observe();
				}
			),
			store.select(
				( state ) => state.covers,
				( covers ) => {
					$items.toggleAttribute( 'data-covers', covers );
				}
			),
			store.select(
				( state ) => state[ list ].renewing,
				( renewing ) => {
					showBusy( $items, renewing );
				}
			),
		];
	}
}
customElements.define( 'library-list', LibraryListElement );
