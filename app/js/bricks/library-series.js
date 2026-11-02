import { __, _n, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join } from 'library-app/format';
import { fill, fillAuthors, fillCompletion, fillPills, pageLink, renderBook } from 'library-app/render';
import { followBooks, observeEnd, openBooks, renderKeyed } from 'library-app/behaviors';
import { urlOf } from 'library-app/router';

/**
 * The page of a series, or of a cycle: its name after its cycles, its cycle, its description, how many of its books are
 * read and the completion they count for, the series of a cycle, its authors, then its books in reading order, those of
 * its series with them, loading as the end of the list comes. Opened, its name takes the focus once it is loaded.
 */
class LibrarySeriesElement extends Brick {
	connect( signal ) {
		const $name = this.querySelector( '[data-field="name"]' );
		const $cycle = this.querySelector( '[data-field="cycle"]' );
		const $description = this.querySelector( '[data-html="description"]' );
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $parts = this.querySelector( '[data-list="parts"]' );
		const $authors = this.querySelector( '[data-row="authors"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMorePageBooks( 'series' ) );

		openBooks( $items, signal );

		return [
			followBooks( $items, formatState ),
			store.select(
				( state ) => state.series,
				( series ) => {
					/* translators: %s: number of books read, after the number of books of a series. */
					const read = sprintf( _n( '%s read', '%s read', series.read, 'library' ), formatNumber( series.read ) );

					fill( this, {
						status: series.error || ( series.loaded && ! series.total ? __( 'No book found.', 'library' ) : '' ),
						name: series.name,
						cycle: series.cycle?.name ?? '',
						summary: series.name ? join( [ formatBooks( series.total ), read ] ) : '',
					} );

					if ( series.cycle ) {
						$cycle.href = urlOf( { view: 'series', id: series.cycle.id } );
					}

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = series.description;
					$description.hidden = ! series.description;

					fillPills(
						$parts,
						series.parts.map( ( part ) => pageLink( 'series', part.id, part.name ) )
					);
				}
			),
			store.select(
				( state ) => state.series.authors,
				// Loaded with its authors.
				( authors ) => fillAuthors( $authors, authors, store.get().series.total )
			),
			store.select(
				( state ) => state.series.completion,
				( completion ) => fillCompletion( $completion, completion, 'library-series-completion' )
			),
			store.select(
				( state ) => state.series.name,
				( name ) => {
					if ( name && 'forward' === store.get().moved ) {
						$name.focus( { preventScroll: true } );
					}
				}
			),
			store.select(
				( state ) => state.series.items,
				( ids ) => {
					const { books } = store.get();

					renderKeyed(
						$items,
						ids.filter( ( id ) => id in books ),
						( id ) => renderBook( books[ id ], formatState( books[ id ] ) )
					);
					observe();
				}
			),
		];
	}
}
customElements.define( 'library-series', LibrarySeriesElement );
