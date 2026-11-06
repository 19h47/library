/**
 * The base of the page of a term, a person, a series or a collection.
 */

import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	formatBooks,
	formatNumber,
	formatState,
	join,
} from 'library-app/format';
import { fill, fillCompletion, renderBook } from 'library-app/render';
import { followBooks, observeEnd, renderKeyed } from 'library-app/behaviors';

/**
 * The page of a term: its name, its description, how many of its books are read and the completion they count for, then
 * its books, loading as the end of the list comes. Opened, its name takes the focus once it is loaded. A page adds what
 * is its own: `summary()` what its summary says after its books and those read, `fillPage()` the rest of its state,
 * `subscriptions()` what else it follows, and `line()` the line of a book.
 */
export class TermPage extends Brick {
	/** Its page in the store: "person", "series" or "publisher". */
	static page = '';

	connect( signal ) {
		const { page: key } = this.constructor;
		const $name = this.querySelector( '[data-field="name"]' );
		const $description = this.querySelector( '[data-html="description"]' );
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd(
			this.querySelector( '.library-list__more' ),
			signal,
			() => store.loadMorePageBooks( key )
		);

		return [
			followBooks( $items, ( book ) => this.line( book ) ),
			store.select(
				( state ) => state[ key ],
				( page ) => {
					/* translators: %s: number of books read, after the number of books of a person, a series or a collection. */
					const read = sprintf(
						_n( '%s read', '%s read', page.read, 'library' ),
						formatNumber( page.read )
					);

					fill( this, {
						status:
							page.error ||
							( page.loaded && ! page.total
								? __( 'No book found.', 'library' )
								: '' ),
						name: page.name,
						summary: page.name
							? join( [
									formatBooks( page.total ),
									read,
									...this.summary( page ),
							  ] )
							: '',
					} );

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = page.description;
					$description.hidden = ! page.description;
					this.fillPage( page );
				}
			),
			store.select(
				( state ) => state[ key ].completion,
				( completion ) =>
					fillCompletion(
						$completion,
						completion,
						`library-${ key }-completion`
					)
			),
			store.select(
				( state ) => state[ key ].name,
				( name ) => {
					if ( name && 'forward' === store.get().moved ) {
						$name.focus( { preventScroll: true } );
					}
				}
			),
			store.select(
				( state ) => state[ key ].items,
				( ids ) => {
					const { books } = store.get();

					// A card per book, kept from a page to the next one, as from a person to another one who shares it.
					renderKeyed(
						$items,
						ids.filter( ( id ) => id in books ),
						( id ) =>
							renderBook( books[ id ], this.line( books[ id ] ) )
					);
					observe();
				}
			),
			...this.subscriptions(),
		];
	}

	/** What its summary says after its books and those read, from its state. */
	summary() {
		return [];
	}

	/** Fills what is its own from its state. */
	fillPage() {}

	/** What else it follows of the store; each returns its unsubscribe function. */
	subscriptions() {
		return [];
	}

	/**
	 * The line of a book, under its title: its reading state.
	 * @param book
	 */
	line( book ) {
		return formatState( book );
	}
}
