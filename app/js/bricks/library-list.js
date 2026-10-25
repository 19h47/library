import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatState } from 'library-app/format';
import { renderBook } from 'library-app/render';
import { followBooks, observeEnd, openBooks } from 'library-app/behaviors';

/** The books found, loading the next ones as the end of the list comes. */
class LibraryListElement extends Brick {
	#ids = [];

	connect( signal ) {
		const $items = this.querySelector( '.app-list__items' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreBooks() );

		openBooks( $items, signal );

		return [
			followBooks( $items, formatState ),
			store.select(
				( state ) => state.search.items,
				( ids ) => {
					const { books } = store.get();
					const found = new Set( ids );
					const kept = this.#ids.filter( ( id ) => found.has( id ) );
					// The list goes on from the books still found: a book no longer found leaves it, the next ones add to it.
					const goesOn = kept.every( ( id, index ) => id === ids[ index ] );

					if ( goesOn ) {
						$items.querySelectorAll( '.library-item' ).forEach( ( $item ) => {
							if ( ! found.has( Number( $item.dataset.id ) ) ) {
								this.#remove( $item );
							}
						} );
					} else {
						$items.replaceChildren();
					}

					$items.append( ...ids.slice( goesOn ? kept.length : 0 ).map( ( id ) => renderBook( books[ id ], formatState( books[ id ] ) ) ) );
					this.#ids = ids;
					observe();
				}
			),
		];
	}

	/** Removes a card; the focus it holds, back from the book closed, goes to the next card, or else the previous one. */
	#remove( $item ) {
		const $sibling = $item.nextElementSibling ?? $item.previousElementSibling;

		if ( $item.contains( document.activeElement ) ) {
			$sibling?.querySelector( '.library-item__button' ).focus();
		}

		$item.remove();
	}
}
customElements.define( 'library-list', LibraryListElement );
