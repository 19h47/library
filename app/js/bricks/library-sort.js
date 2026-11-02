import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';

/**
 * The order of the books: latest added, by title, by rating or by latest read date. A search for a series lists it in
 * reading order unless another order is chosen: its first choice says so.
 */
class LibrarySortElement extends Brick {
	connect( signal ) {
		const $select = this.querySelector( 'select' );
		const $default = $select.options[ 0 ];
		const latest = $default.textContent;

		$select.addEventListener( 'change', () => store.setSort( $select.value ), { signal } );

		return [
			store.select(
				( state ) => state.sort,
				( sort ) => {
					$select.value = sort;
				}
			),
			store.select(
				( state ) => /(^|\s)series:/.test( state.query ),
				( isSeries ) => {
					$default.textContent = isSeries ? $select.dataset.series : latest;
				}
			),
		];
	}
}
customElements.define( 'library-sort', LibrarySortElement );
