import { __, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatBooks, formatNumber, join } from 'library-app/format';
import { fill } from 'library-app/render';

// The action of the store that sets each filter.
const SETTERS = { read: 'setRead', status: 'setStatus', form: 'setForm' };

/**
 * The filters of the books: a button that says how many are chosen opens their fields in a dialog, each choice applied
 * at once, the books found counted on the button that closes it; the choices made show as pills, which remove them.
 */
class LibraryFiltersElement extends Brick {
	connect( signal ) {
		const $open = this.querySelector( '[data-action="open-filters"]' );
		const $dialog = this.querySelector( 'dialog' );
		const $chosen = this.querySelector( '.library-filters__chosen' );
		const $pills = [ ...$chosen.querySelectorAll( '[data-filter]' ) ];
		const label = $open.textContent.trim();

		$open.addEventListener( 'click', () => $dialog.showModal(), { signal } );
		$chosen.addEventListener(
			'click',
			( event ) => {
				const $pill = event.target.closest( '[data-filter]' );

				if ( ! $pill ) {
					return;
				}

				store[ SETTERS[ $pill.dataset.filter ] ]( '' );
				// The pill leaves: the focus goes to the button of the filters.
				$open.focus();
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => Object.keys( SETTERS ).map( ( filter ) => state[ filter ] ).join(),
				() => {
					const state = store.get();
					const chosen = $pills.filter( ( $pill ) => state[ $pill.dataset.filter ] === $pill.dataset.value );

					$pills.forEach( ( $pill ) => {
						$pill.hidden = ! chosen.includes( $pill );
					} );
					$chosen.hidden = ! chosen.length;
					fill( $open, { label: join( [ label, chosen.length && formatNumber( chosen.length ) ] ) } );
				}
			),
			store.select(
				( state ) => ( state.search.loaded ? state.search.total : null ),
				( total ) => {
					/* translators: %s: number of books found, e.g. 19 books. */
					fill( this, { show: null === total ? __( 'Loading…', 'library' ) : sprintf( __( 'Show %s', 'library' ), formatBooks( total ) ) } );
				}
			),
		];
	}
}
customElements.define( 'library-filters', LibraryFiltersElement );
