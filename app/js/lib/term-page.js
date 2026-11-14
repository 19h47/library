/**
 * The base of the page of a term, a person, a series or a collection.
 */

import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fillCompletion } from 'library-app/render';
import { fill, fillHtml } from 'library-app/dom';
import {
	focusTitle,
	followCards,
	formatListStatus,
	observeEnd,
	renderBooks,
} from 'library-app/behaviors';
import { formatBooks, formatRead, join } from 'library-app/format';
import { formatState } from 'library-app/book-lines';

/**
 * @import { Book, PageView } from 'library-app/api'
 * @import { PersonPage, PublisherPage, SeriesPage } from 'library-app/store'
 */

/**
 * The page of a term: its name, its description, how many of its books are read and the completion they count for, then
 * its books, loading as the end of the list comes, as cards or a grid of their covers, as chosen. Opened, its name takes the focus once it is loaded. A page adds what
 * is its own: `summary()` what its summary says after its books and those read, `fillPage()` the rest of its state,
 * `subscriptions()` what else it follows, and `line()` the line of a book.
 */
export class TermPage extends Brick {
	/**
	 * Its page in the store: "person", "series" or "publisher".
	 *
	 * @type {PageView}
	 */
	static page;

	/**
	 * @param {AbortSignal} signal Aborted as it leaves the page.
	 * @return {Array<() => void>} The unsubscribe functions.
	 */
	connect( signal ) {
		const { page: key } = /** @type {typeof TermPage} */ (
			this.constructor
		);
		/** @type {HTMLElement} */
		const $name = this.querySelector( '[data-field="name"]' );
		/** @type {HTMLElement} */
		const $description = this.querySelector( '[data-html="description"]' );
		/** @type {HTMLElement} */
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd(
			this.querySelector( '[data-part="more"]' ),
			signal,
			() => store.loadMorePageBooks( key )
		);

		return [
			followCards( $items, ( book ) => this.line( book ) ),
			store.select(
				( state ) => state[ key ],
				( page ) => {
					fill( this, {
						status: formatListStatus( page ),
						name: page.name,
						summary: page.name
							? join( [
									formatBooks( page.total ),
									formatRead( page.read ),
									...this.summary( page ),
							  ] )
							: '',
					} );
					fillHtml( $description, page.description );
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
						focusTitle( $name );
					}
				}
			),
			store.select(
				( state ) => state.covers,
				( covers ) => {
					$items.toggleAttribute( 'data-covers', covers );
				}
			),
			store.select(
				( state ) => state[ key ].items,
				( ids ) => {
					// A card per book, kept from a page to the next one, as from a person to another one who shares it.
					renderBooks( $items, ids, ( book ) => this.line( book ) );
					observe();
				}
			),
			...this.subscriptions(),
		];
	}

	/**
	 * What its summary says after its books and those read, from its state.
	 *
	 * @type {( page: PersonPage|SeriesPage|PublisherPage ) => Array<string|0|null>}
	 */
	summary() {
		return [];
	}

	/**
	 * Fills what is its own from its state.
	 *
	 * @type {( page: PersonPage|SeriesPage|PublisherPage ) => void}
	 */
	fillPage() {}

	/**
	 * What else it follows of the store; each returns its unsubscribe function.
	 *
	 * @return {Array<() => void>} The unsubscribe functions.
	 */
	subscriptions() {
		return [];
	}

	/**
	 * The line of a book, under its title: its reading state.
	 *
	 * @param {Book} book The book.
	 * @return {string} Its line.
	 */
	line( book ) {
		return formatState( book );
	}
}
