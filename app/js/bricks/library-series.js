import { store } from 'library-app/store';
import { fill, fillAuthors, fillPills, pageLink } from 'library-app/render';
import { urlOf } from 'library-app/router';
import { TermPage } from 'library-app/term-page';

/**
 * @import { PageView } from 'library-app/api'
 * @import { SeriesPage } from 'library-app/store'
 */

/**
 * The page of a series, or of a cycle: its name after its cycles, its cycle, its description, how many of its books are
 * read and the completion they count for, the series of a cycle, its authors, then its books in reading order, those of
 * its series with them.
 */
class LibrarySeriesElement extends TermPage {
	/** @type {PageView} */
	static page = 'series';

	/**
	 * Fills its cycle and the series of a cycle.
	 *
	 * @param {SeriesPage} series The series.
	 */
	fillPage( series ) {
		fill( this, { cycle: series.cycle?.name ?? '' } );

		if ( series.cycle ) {
			/** @type {HTMLAnchorElement} */ (
				this.querySelector( '[data-field="cycle"]' )
			).href = urlOf( {
				view: 'series',
				id: series.cycle.id,
			} );
		}

		fillPills(
			this.querySelector( '[data-list="parts"]' ),
			series.parts.map( ( part ) =>
				pageLink( 'series', part.id, part.name )
			)
		);
	}

	/**
	 * Follows its authors, as cards, the ones past the first folded.
	 *
	 * @return {Array<() => void>} The unsubscribe functions.
	 */
	subscriptions() {
		/** @type {HTMLElement} */
		const $authors = this.querySelector( '[data-row="authors"]' );

		// Loaded with its authors.
		return [
			store.select(
				( state ) => state.series.authors,
				( authors ) =>
					fillAuthors( $authors, authors, store.get().series.total )
			),
		];
	}
}
customElements.define( 'library-series', LibrarySeriesElement );
