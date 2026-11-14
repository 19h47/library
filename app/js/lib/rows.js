/**
 * The rows of a list of inputs, which the components inputs-list and input-groups share: a row is a clone of a template,
 * an item of a list, its control its first element, which takes its value, and a button that removes it.
 */

import { clone } from 'library-app/render';

/**
 * The control of a row, its first element: an input, or a component with a value, such as a reading-period.
 *
 * @param {Element} $row The row.
 * @return {HTMLInputElement} Its control.
 */
export const controlOf = ( $row ) =>
	/** @type {HTMLInputElement} */ ( $row.firstElementChild );

/**
 * Adds a row, a clone of the template `template`, at the end of `$list`; its control takes `value`, unless empty.
 *
 * @param {Element} $list    The list.
 * @param {string}  template The ID of the template of the row.
 * @param {string}  [value]  Its value.
 * @return {HTMLElement} The row.
 */
export function addRow( $list, template, value = '' ) {
	const $row = clone( template );

	// Once in the page, a component of the row is defined, and takes its value through its own accessor.
	$list.append( $row );

	if ( value ) {
		controlOf( $row ).value = value;
	}

	return $row;
}

/**
 * Lets the buttons of `$element` add and remove its rows. A button that adds one, `data-action="add"`, gives the row
 * `add()` makes the focus, its first field, or its button that removes it without one, and the row tells it with a
 * rowadd event, which bubbles. A button that removes one removes its row, gives the focus to the first button that adds
 * one, and `$element` tells it with a change, as a field changed does: the values of the form changed.
 *
 * @param {HTMLElement}                             $element The list of inputs.
 * @param {( $button: HTMLElement ) => HTMLElement} add      Adds the row of a button that adds one.
 * @param {AbortSignal}                             signal   Removes the listener.
 */
export function listenRows( $element, add, signal ) {
	$element.addEventListener(
		'click',
		( event ) => {
			const $target = /** @type {Element} */ ( event.target );
			/** @type {HTMLElement} */
			const $add = $target.closest( '[data-action="add"]' );
			const $remove = $target.closest( '[data-action="remove"]' );

			if ( $add ) {
				const $row = add( $add );

				/** @type {HTMLElement} */ (
					$row.querySelector(
						'input:not([type="hidden"]), select, textarea, [data-action="remove"]'
					)
				).focus();
				$row.dispatchEvent( new Event( 'rowadd', { bubbles: true } ) );
			}

			if ( $remove ) {
				$remove.closest( 'li' ).remove();
				/** @type {HTMLElement} */ (
					$element.querySelector( '[data-action="add"]' )
				).focus();
				$element.dispatchEvent(
					new Event( 'change', { bubbles: true } )
				);
			}
		},
		{ signal }
	);
}
