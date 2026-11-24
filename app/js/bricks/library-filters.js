import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatOfList } from 'library-app/behaviors';
import { calendarFormCounts, calendarReadingCount } from 'library-app/parts';

/**
 * @import { FiltersSheetElement } from '../components/filters-sheet.js'
 * @import { State } from 'library-app/store'
 */

// The counts of the values of the forms, by list: the books of each form on the shelves, its readings, or its readings
// of the year, the month or the day the calendar shows.
const FORM_COUNTS = {
	search: ( /** @type {State} */ state ) => state.stats?.form_book_counts,
	diary: ( /** @type {State} */ state ) => state.stats?.form_reading_counts,
	calendar: ( /** @type {State} */ state ) =>
		state.calendar.loaded
			? calendarFormCounts( state.calendar )
			: undefined,
};
// How many the filters find, by list: the books or the readings of the list, or the readings of the year, the month or
// the day the calendar shows.
/** @type {Object<string, ( state: State ) => number>} */
const TOTALS = {
	calendar: ( state ) => calendarReadingCount( state.calendar ),
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
			// Its filters hide while they would find nothing whatever the forms, such as a month without readings.
			store.select( FORM_COUNTS[ list ], ( counts ) => {
				if ( counts ) {
					$sheet.counts = { forms: counts };
					this.hidden = ! Object.values( counts ).some( Boolean );
				}
			} ),
			store.select(
				( state ) =>
					state[ list ].loaded
						? formatOfList( list )(
								TOTALS[ list ]?.( state ) ?? state[ list ].total
						  )
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
