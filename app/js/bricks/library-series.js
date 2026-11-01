import { __, _n, seriesUrl, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, formatState, join } from 'library-app/format';
import { fill, renderAchievement, renderBook, renderCloseAchievement, seriesLink } from 'library-app/render';
import { followBooks, observeEnd, openBooks, openLinks, renderKeyed } from 'library-app/behaviors';

/**
 * The page of a series, or of a cycle: its name after its cycles, its cycle, its description, how many of its books are
 * read and the completion they count for, the series of a cycle, then its books in reading order, those of its series
 * with them, loading as the end of the list comes. Opened, its name takes the focus once it is loaded.
 */
class LibrarySeriesElement extends Brick {
	connect( signal ) {
		const $name = this.querySelector( '[data-field="name"]' );
		const $cycle = this.querySelector( '[data-field="cycle"]' );
		const $description = this.querySelector( '[data-html="description"]' );
		const $completion = this.querySelector( '[data-list="completion"]' );
		const $parts = this.querySelector( '[data-list="parts"]' );
		const $items = this.querySelector( '[data-list="books"]' );
		const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMorePageBooks( 'series' ) );

		openBooks( $items, signal );
		openLinks( this, signal );

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
						$cycle.href = seriesUrl + series.cycle.id;
						$cycle.dataset.series = series.cycle.id;
					}

					// HTML as the API renders it, among the few tags core keeps when it saves a description.
					$description.innerHTML = series.description;
					$description.hidden = ! series.description;

					$parts.replaceChildren(
						...series.parts.map( ( part ) => {
							const $item = document.createElement( 'li' );
							const $link = seriesLink( part.id, part.name );

							$link.className = 'app-pill';
							$item.append( $link );

							return $item;
						} )
					);
					$parts.closest( '[data-row]' ).hidden = ! series.parts.length;
				}
			),
			store.select(
				( state ) => state.series.completion,
				( completion ) => {
					$completion.hidden = ! completion;

					if ( ! completion ) {
						$completion.replaceChildren();
						return;
					}

					// The page is the series': its completion links nowhere.
					const achievement = { ...completion, target: null };

					$completion.replaceChildren(
						'total' in achievement ? renderCloseAchievement( achievement, 'library-series-completion' ) : renderAchievement( achievement )
					);
				}
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
