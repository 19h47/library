import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatOfList } from 'library-app/behaviors';

/** @import { FiltersSheetElement } from '../components/filters-sheet.js' */

// The counts of the stats of the values of the forms, by list: the books of each form on the shelves, or its readings.
const FORM_COUNTS = {
	search: 'form_book_counts',
	diary: 'form_reading_counts',
	calendar: 'form_reading_counts',
};
// A search for a series, which the server lists in reading order, as it reads the keyword (Query::parse_keywords()):
// whatever its case, not excluded, with a name, quoted or not.
const SERIES_SEARCH = /(?:^|\s)series:(?:"[^"]*\S[^"]*"|[^\s",])/i;

/**
 * The filters of a list, the books found or, its `list` attribute, the readings of the diary, and its order: the
 * component filters-sheet, and the selects beside it, such as the order of the books, each named after its filter. The
 * filters of the state are their values, each one changed set in the state; the forms are counted as the stats count
 * them, and what the list finds said. A select whose first choice has another name for a search for a series,
 * `data-series`, takes it: such a search lists the series in reading order unless another order is chosen.
 */
class LibraryFiltersElement extends Brick {
	connect( signal ) {
		const list = this.getAttribute( 'list' ) ?? 'search';
		/** @type {FiltersSheetElement} */
		const $sheet = this.querySelector( 'filters-sheet' );
		const $selects = [ ...this.querySelectorAll( 'select' ) ].filter(
			( $select ) => ! $sheet.contains( $select )
		);
		// The filters of its sheet, then those of the selects beside it.
		const sheetFilters = Object.keys( $sheet.value );
		const filters = [
			...sheetFilters,
			...$selects.map( ( $select ) => $select.name ),
		];
		/** @type {( name: string ) => string|string[]} */
		const valueOf = ( name ) =>
			$selects.find( ( $select ) => $select.name === name )?.value ??
			$sheet.value[ name ];

		this.addEventListener(
			'change',
			( event ) => {
				const { name } = /** @type {HTMLInputElement} */ (
					event.target
				);

				if ( filters.includes( name ) ) {
					store.setFilter( name, valueOf( name ) );
				}
			},
			{ signal }
		);

		return [
			store.select(
				( state ) =>
					JSON.stringify(
						filters.map( ( filter ) => state[ filter ] )
					),
				() => {
					const state = store.get();

					$sheet.value = Object.fromEntries(
						sheetFilters.map( ( filter ) => [
							filter,
							state[ filter ],
						] )
					);
					$selects.forEach( ( $select ) => {
						$select.value = state[ $select.name ];
					} );
				}
			),
			store.select(
				( state ) => state.stats?.[ FORM_COUNTS[ list ] ],
				( counts ) => {
					if ( counts ) {
						$sheet.counts = { forms: counts };
					}
				}
			),
			store.select(
				( state ) =>
					state[ list ].loaded
						? formatOfList( list )( state[ list ].total )
						: '',
				( found ) => {
					$sheet.found = found;
				}
			),
			...$selects
				.filter( ( $select ) => $select.dataset.series )
				.map( ( $select ) => {
					const $first = $select.options[ 0 ];
					const label = $first.textContent;

					return store.select(
						( state ) => SERIES_SEARCH.test( state.query ),
						( isSeries ) => {
							$first.textContent = isSeries
								? $select.dataset.series
								: label;
						}
					);
				} ),
		];
	}
}
customElements.define( 'library-filters', LibraryFiltersElement );
