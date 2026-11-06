import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import {
	formatBooks,
	formatNumber,
	formatState,
	join,
	listFormat,
} from 'library-app/format';
import { fill, fillFolded, renderSeries } from 'library-app/render';
import { TermPage } from 'library-app/term-page';

/**
 * @import { Book, PageView } from 'library-app/api'
 * @import { PersonPage } from 'library-app/store'
 */

/**
 * The page of a person: their names, their description, their reading, the pages of their books and the completion
 * their books count for, their series, then their books, in the order they first came out.
 */
class LibraryPersonElement extends TermPage {
	/** @type {PageView} */
	static page = 'person';

	/**
	 * What its summary says after its books and those read: the average rating of their books and their pages.
	 *
	 * @param {PersonPage} person The person.
	 * @return {Array<string|0|null>} The parts of its summary, none for none.
	 */
	summary( person ) {
		const rating =
			person.rating &&
			sprintf(
				/* translators: %s: average rating of the books of a person, e.g. 7.5. */
				__( '%s/10 on average', 'library' ),
				formatNumber( person.rating )
			);
		const bookPages =
			person.bookPages &&
			sprintf(
				/* translators: %s: number of pages. */
				_n( '%s page', '%s pages', person.bookPages, 'library' ),
				formatNumber( person.bookPages )
			);

		return [ rating, bookPages ];
	}

	/**
	 * Fills their other names.
	 *
	 * @param {PersonPage} person The person.
	 */
	fillPage( person ) {
		const otherNames = sprintf(
			/* translators: %s: list of the other names of a person, such as a pseudonym or their birth name. */
			_n(
				'Other name: %s',
				'Other names: %s',
				person.otherNames.length,
				'library'
			),
			listFormat.format( person.otherNames )
		);

		fill( this, {
			otherNames: person.otherNames.length ? otherNames : '',
		} );
	}

	/**
	 * Follows their series, as cards, the ones past the first folded.
	 *
	 * @return {Array<() => void>} The unsubscribe functions.
	 */
	subscriptions() {
		/** @type {HTMLElement} */
		const $series = this.querySelector( '[data-row="series"]' );

		return [
			store.select(
				( state ) => state.person.series,
				( series ) =>
					fillFolded(
						$series,
						series.map( ( item ) => {
							const read = sprintf(
								/* translators: %s: number of books read, after the number of books of a person in a series. */
								_n(
									'%s read',
									'%s read',
									item.read,
									'library'
								),
								formatNumber( item.read )
							);

							return renderSeries(
								item,
								join( [ formatBooks( item.books ), read ] )
							);
						} ),
						( folded ) =>
							sprintf(
								/* translators: %s: number of series of a person, after the first ones. */
								_n(
									'%s other series',
									'%s other series',
									folded,
									'library'
								),
								formatNumber( folded )
							)
					)
			),
		];
	}

	/**
	 * The line of a book: the year it first came out, as the list follows it, then its state.
	 *
	 * @param {Book} book The book.
	 * @return {string} Its line.
	 */
	line( book ) {
		const published =
			book.published_date &&
			sprintf(
				/* translators: %s: year a book first came out. */
				__( 'First published in %s', 'library' ),
				book.published_date.slice( 0, 4 )
			);

		return join( [ published, formatState( book ) ] );
	}
}
customElements.define( 'library-person', LibraryPersonElement );
