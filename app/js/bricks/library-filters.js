import { __, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fill } from 'library-app/dom';
import {
	formatAnd,
	formatBooks,
	formatNumber,
	formatReadings,
	join,
} from 'library-app/format';
import { formatTotal } from 'library-app/behaviors';

// What the button that closes the filters counts, by the list its `list` attribute names.
const COUNTS = { search: formatBooks, diary: formatReadings };
// The most values left unchecked a pill names, as all but them, such as All but Novel.
const MOST_LEFT = 2;

/**
 * The filters of a list, the books found or, its `list` attribute, the readings of the diary: a button that says how
 * many are chosen opens their fields in a dialog, a command of the dialog, each named after its filter, a select or the
 * checkboxes of its values, each choice applied at once, what the list finds counted on the button that closes it; each
 * filter chosen shows as a pill, which says its choice, or its label and how many values are checked, and removes it.
 */
class LibraryFiltersElement extends Brick {
	connect( signal ) {
		const list = this.getAttribute( 'list' ) ?? 'search';
		/** @type {HTMLButtonElement} */
		const $open = this.querySelector( '[data-part="open"]' );
		/** @type {HTMLElement} */
		const $chosen = this.querySelector( '[data-list="chosen"]' );
		const $pills = /** @type {HTMLElement[]} */ ( [
			...$chosen.querySelectorAll( '[data-filter]' ),
		] );
		const $fields =
			/** @type {Array<HTMLSelectElement|HTMLInputElement>} */ ( [
				...this.querySelectorAll( 'dialog :is(select, input)' ),
			] );
		const filters = [
			...new Set( $fields.map( ( $field ) => $field.name ) ),
		];
		const label = $open.textContent.trim();

		this.addEventListener(
			'change',
			( event ) => {
				const $field =
					/** @type {HTMLSelectElement|HTMLInputElement} */ (
						event.target
					);

				if ( ! filters.includes( $field.name ) ) {
					return;
				}

				const value =
					'checkbox' === $field.type
						? $fields
								.filter(
									( $item ) =>
										$item.name === $field.name &&
										/** @type {HTMLInputElement} */ (
											$item
										).checked
								)
								.map( ( $item ) => $item.value )
						: $field.value;

				store.setFilter( $field.name, value );
			},
			{ signal }
		);

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

				store.clearFilter( $pill.dataset.filter );
				// The pill leaves: the focus goes to the button of the filters.
				$open.focus();
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

					$fields.forEach( ( $field ) => {
						const value = state[ $field.name ];

						if ( 'checkbox' === $field.type ) {
							/** @type {HTMLInputElement} */ ( $field ).checked =
								value.includes( $field.value );
						} else {
							$field.value = value;
						}
					} );

					const chosen = $pills.filter( ( $pill ) => {
						const text = this.#chosen( $pill.dataset.filter );

						fill( $pill, { chosen: text } );
						$pill.hidden = ! text;

						return text;
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
					formatTotal( state[ list ], ( total ) =>
						sprintf(
							/* translators: %s: number of books or of readings found, e.g. 19 books. */
							__( 'Show %s', 'library' ),
							COUNTS[ list ]( total )
						)
					),
				( show ) => {
					fill( this, { show } );
				}
			),
		];
	}

	/**
	 * What the filter `filter` chose, as its pill says it: the label of the option chosen, or of the one value checked;
	 * all but the values left unchecked, a few and fewer than those checked, such as All but Novel; else its label and
	 * how many values are checked, such as Forms · 3; empty while none is.
	 *
	 * @param {string} filter The filter.
	 * @return {string} What it chose.
	 */
	#chosen( filter ) {
		const $field = this.querySelector( `dialog [name="${ filter }"]` );

		if ( $field instanceof HTMLSelectElement ) {
			return $field.value ? $field.selectedOptions[ 0 ].textContent : '';
		}

		const $values = /** @type {HTMLInputElement[]} */ ( [
			...this.querySelectorAll( `dialog [name="${ filter }"]` ),
		] );
		// The name of a value, its label without how many it has.
		const labelOf = ( $value ) => {
			const $label = /** @type {HTMLLabelElement} */ (
				$value.labels[ 0 ].cloneNode( true )
			);

			$label.querySelector( '[data-part="count"]' )?.remove();

			return $label.textContent.trim();
		};
		const checked = $values.filter( ( $value ) => $value.checked );

		if ( checked.length < 2 ) {
			return checked.map( labelOf ).join();
		}

		const left = $values.filter( ( $value ) => ! $value.checked );

		if (
			left.length &&
			left.length <= MOST_LEFT &&
			left.length < checked.length
		) {
			return sprintf(
				/* translators: %s: values left out of a filter, e.g. Novel and Essay. */
				__( 'All but %s', 'library' ),
				formatAnd( left.map( labelOf ) )
			);
		}

		return join( [
			$field.closest( 'fieldset' ).querySelector( 'legend' ).textContent,
			formatNumber( checked.length ),
		] );
	}
}
customElements.define( 'library-filters', LibraryFiltersElement );
