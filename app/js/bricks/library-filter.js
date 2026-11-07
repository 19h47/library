import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';

// A search for a series, which the server lists in reading order, as it reads the keyword (Query::parse_keywords()):
// whatever its case, not excluded, with a name, quoted or not.
const SERIES_SEARCH = /(?:^|\s)series:(?:"[^"]*\S[^"]*"|[^\s",])/i;

/**
 * A filter of the books, or their order, its select named by `data-filter`: read, status, form or sort. A select whose
 * first choice has another name for a search for a series, `data-series`, takes it: such a search lists the series in
 * reading order unless another order is chosen.
 */
class LibraryFilterElement extends Brick {
	connect( signal ) {
		const { filter } = this.dataset;
		const $select = this.querySelector( 'select' );
		const $first = $select.options[ 0 ];
		const label = $first.textContent;

		$select.addEventListener(
			'change',
			() => store.setFilter( filter, $select.value ),
			{ signal }
		);

		return [
			store.select(
				( state ) => state[ filter ],
				( value ) => {
					$select.value = value;
				}
			),
			store.select(
				( state ) => SERIES_SEARCH.test( state.query ),
				( isSeries ) => {
					$first.textContent = isSeries
						? $select.dataset.series ?? label
						: label;
				}
			),
		];
	}
}
customElements.define( 'library-filter', LibraryFilterElement );
