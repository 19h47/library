import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatState } from 'library-app/format';
import { renderBook } from 'library-app/render';
import {
	followBooks,
	observeEnd,
	renderKeyed,
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
		const $items = this.querySelector( '.app-list__items' );
		const observe = observeEnd(
			this.querySelector( '.library-list__more' ),
			signal,
			() => store.loadMoreBooks( list )
		);

		return [
			followBooks( $items, formatState ),
			store.select(
				( state ) => state[ list ].items,
				( ids ) => {
					const { books } = store.get();

					renderKeyed( $items, ids, ( id ) =>
						renderBook( books[ id ], formatState( books[ id ] ) )
					);
					observe();
				}
			),
			store.select(
				( state ) => state.covers,
				( covers ) => {
					this.toggleAttribute( 'covers', covers );
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
