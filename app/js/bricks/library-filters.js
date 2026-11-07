import { __, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	formatBooks,
	formatNumber,
	formatTotal,
	join,
} from 'library-app/format';
import { fill } from 'library-app/render';

/**
 * The filters of the books: a button that says how many are chosen opens their fields in a dialog, each choice applied
 * at once, the books found counted on the button that closes it; the choices made show as pills, which remove them.
 */
class LibraryFiltersElement extends Brick {
	connect( signal ) {
		/** @type {HTMLButtonElement} */
		const $open = this.querySelector( '[data-action="open-filters"]' );
		const $dialog = this.querySelector( 'dialog' );
		/** @type {HTMLElement} */
		const $chosen = this.querySelector( '.library-filters__chosen' );
		const $pills = /** @type {HTMLElement[]} */ ( [
			...$chosen.querySelectorAll( '[data-filter]' ),
		] );
		const filters = [
			...new Set( $pills.map( ( $pill ) => $pill.dataset.filter ) ),
		];
		const label = $open.textContent.trim();

		$open.addEventListener( 'click', () => $dialog.showModal(), {
			signal,
		} );
		$chosen.addEventListener(
			'click',
			( event ) => {
				/** @type {HTMLElement} */
				const $pill = /** @type {Element} */ ( event.target ).closest(
					'[data-filter]'
				);

				if ( ! $pill ) {
					return;
				}

				store.setFilter( $pill.dataset.filter, '' );
				// The pill leaves: the focus goes to the button of the filters.
				$open.focus();
			},
			{ signal }
		);

		return [
			store.select(
				( state ) =>
					filters.map( ( filter ) => state[ filter ] ).join(),
				() => {
					const state = store.get();
					const chosen = $pills.filter(
						( $pill ) =>
							state[ $pill.dataset.filter ] ===
							$pill.dataset.value
					);

					$pills.forEach( ( $pill ) => {
						$pill.hidden = ! chosen.includes( $pill );
					} );
					$chosen.hidden = ! chosen.length;
					fill( $open, {
						label: join( [
							label,
							chosen.length && formatNumber( chosen.length ),
						] ),
					} );
				}
			),
			store.select(
				( state ) =>
					formatTotal( state.search, ( total ) =>
						sprintf(
							/* translators: %s: number of books found, e.g. 19 books. */
							__( 'Show %s', 'library' ),
							formatBooks( total )
						)
					),
				( show ) => {
					fill( this, { show } );
				}
			),
		];
	}
}
customElements.define( 'library-filters', LibraryFiltersElement );
