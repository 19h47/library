/**
 * What bricks share: a book opened from its card or its cover, the elements of the books following them, its cards
 * among them, a list drawn by keys, a list of cards of books so, the next page as the end of a list comes, a list of
 * inputs to add and remove, the title of a page opened, the errors of a form at its fields and the button that sends it
 * while it saves.
 */

import { store } from 'library-app/store';
import { clone, fillBook, renderBook } from 'library-app/render';

/** @import { Book } from 'library-app/api' */

/** @typedef {HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement} Field A field of a form. */

// The next page loads before the end of the list shows.
const LOAD_MARGIN_PX = 800;

/**
 * The message of each field in error, numbered across the forms for their IDs.
 *
 * @type {WeakMap<Field, HTMLElement>}
 */
const errorMessages = new WeakMap();
let errorCount = 0;

/**
 * Draws again the elements of the books of `$root` whose book changed, such as a book saved, each by its data-id, such
 * as the item of a card: `redraw` draws one, from the element and its book, such as a card filled again in place.
 * Returns the unsubscribe function.
 *
 * @param {Element}                                    $root  The element of the books.
 * @param {( $item: HTMLElement, book: Book ) => void} redraw Draws an element again, from its book.
 * @return {() => void} The unsubscribe function.
 */
export function followBooks( $root, redraw ) {
	let previous = store.get().books;

	return store.select(
		( state ) => state.books,
		( books ) => {
			$root
				.querySelectorAll( '[data-id]' )
				.forEach( ( /** @type {HTMLElement} */ $item ) => {
					const book = books[ $item.dataset.id ];

					if ( book !== previous[ $item.dataset.id ] ) {
						redraw( $item, book );
					}
				} );
			previous = books;
		}
	);
}

/**
 * Draws in `$list` an element per key of `keys`, in their order, such as a card per book: an element drawn already for
 * a key, its data-id, stays as it is, its focus too, even moved; a new one comes from `render( key )`. An element whose
 * key left goes, its focus to the nearest one that stays, after it, or else before it.
 *
 * @param {Element}                        $list  The list.
 * @param {number[]}                       keys   The keys, such as book IDs.
 * @param {( key: number ) => HTMLElement} render The element of a new key.
 */
export function renderKeyed( $list, keys, render ) {
	const $children = /** @type {HTMLElement[]} */ ( [ ...$list.children ] );
	const $drawn = new Map(
		$children.map( ( $item ) => [ $item.dataset.id, $item ] )
	);
	const kept = new Set( keys.map( ( key ) => key.toString() ) );
	const $kept = $children.filter( ( $item ) => kept.has( $item.dataset.id ) );
	const $focused = $list.ownerDocument.activeElement;
	const $left = [ ...$drawn.values() ].find(
		( $item ) =>
			! kept.has( $item.dataset.id ) && $item.contains( $focused )
	);

	if ( $left ) {
		const $after = $kept.find(
			( $item ) =>
				// eslint-disable-next-line no-bitwise -- A flag of the position of the element.
				$left.compareDocumentPosition( $item ) &
				Node.DOCUMENT_POSITION_FOLLOWING
		);

		/** @type {HTMLElement} */ (
			( $after ?? $kept.at( -1 ) )?.querySelector( 'button, a' )
		)?.focus();
	}

	$drawn.forEach( ( $item, key ) => {
		if ( ! kept.has( key ) ) {
			$item.remove();
		}
	} );

	const $active = /** @type {HTMLElement} */ (
		$list.ownerDocument.activeElement
	);
	let $next = $list.firstElementChild;

	keys.forEach( ( key ) => {
		const $item = $drawn.get( key.toString() ) ?? render( key );

		if ( $item === $next ) {
			$next = $next.nextElementSibling;
		} else if ( $list.moveBefore && $item.isConnected ) {
			// Moved, an element keeps its focus where the browser can.
			$list.moveBefore( $item, $next );
		} else {
			$list.insertBefore( $item, $next );
		}
	} );

	// Moved elsewhere, an element loses its focus: it gets it back.
	if (
		$active !== $list.ownerDocument.activeElement &&
		$list.contains( $active )
	) {
		$active.focus( { preventScroll: true } );
	}
}

/**
 * Fills again the cards of `$root` whose book changed, in place, their focus too: see followBooks(); `line` gives the
 * line of a card. Returns the unsubscribe function.
 *
 * @param {Element}                                      $root The element of the cards.
 * @param {( book: Book, $item: HTMLElement ) => string} line  The line of a card, from its book and the card.
 * @return {() => void} The unsubscribe function.
 */
export function followCards( $root, line ) {
	return followBooks( $root, ( $item, book ) =>
		fillBook( $item, book, line( book, $item ) )
	);
}

/**
 * Draws in `$items` a card per book of `ids` loaded, in their order, as renderKeyed() draws them: a card stays while its
 * book does, a new one shows `line( book )` under its title.
 *
 * @param {Element}                  $items The list of the cards.
 * @param {number[]}                 ids    Book IDs.
 * @param {( book: Book ) => string} line   The line of a card, from its book.
 */
export function renderBooks( $items, ids, line ) {
	const { books } = store.get();

	renderKeyed(
		$items,
		ids.filter( ( id ) => id in books ),
		( id ) => renderBook( books[ id ], line( books[ id ] ) )
	);
}

/**
 * Calls `callback` as the end `$more` of a list comes near. Returns the function
 * to call once the list has grown: its end may still be in view.
 *
 * @param {Element}     $more    The end of the list.
 * @param {AbortSignal} signal   Stops observing it.
 * @param {() => void}  callback Called as it comes near.
 * @return {() => void} The function to call once the list has grown.
 */
export function observeEnd( $more, signal, callback ) {
	// The last entry: one from before observe() may come first.
	const observer = new IntersectionObserver(
		( entries ) => {
			if ( entries.at( -1 ).isIntersecting ) {
				callback();
			}
		},
		{ rootMargin: `0px 0px ${ LOAD_MARGIN_PX }px 0px` }
	);

	signal.addEventListener( 'abort', () => observer.disconnect() );

	return () => {
		observer.unobserve( $more );
		observer.observe( $more );
	};
}

/**
 * A list of inputs in `$list`, one per clone of the template `id`, each one with a button to remove it, and `$add` to add one,
 * such as the people of a book or its readings. Removing an input gives the focus to `$add`, adding one gives it the focus.
 * `id` may be a function of the value, for a row whose template depends on it, such as a reading whose date is unknown.
 * `value` gives the value of a new input, `setup` prepares each one from its value, such as a row of a reading, whose two
 * inputs take its start and its end. `into` gives the list a new input goes in when `$list` holds several, such as the
 * people of a book in the group of the role chosen. Returns the function that adds an input, with a value, in a list.
 *
 * @param {Object}                                              options         Options.
 * @param {Element}                                             options.$list   The list, or the box of its groups.
 * @param {HTMLElement}                                         options.$add    The button that adds an input.
 * @param {string|(( value: string ) => string)}                options.id      The ID of the template of a row, or the function that gives it from the value.
 * @param {AbortSignal}                                         options.signal  Removes the listeners.
 * @param {() => string}                                        [options.value] The value of a new input.
 * @param {( $input: HTMLInputElement, value: string ) => void} [options.setup] Prepares each input, from its value.
 * @param {() => Element}                                       [options.into]  The list a new input goes in, `$list` unless given.
 * @return {( text?: string, $into?: Element ) => HTMLInputElement} The function that adds an input, with a value, in a list.
 */
export function inputsList( {
	$list,
	$add,
	id,
	signal,
	value = () => '',
	setup = () => {},
	into = () => $list,
} ) {
	const templateOf = typeof id === 'function' ? id : () => id;
	const add = ( text = value(), $into = into() ) => {
		const $item = clone( templateOf( text ) );
		const $input = $item.querySelector( 'input' );

		$input.value = text;
		setup( $input, text );
		$into.append( $item );

		return $input;
	};

	$add.addEventListener( 'click', () => add().focus(), { signal } );

	$list.addEventListener(
		'click',
		( event ) => {
			const $remove = /** @type {Element} */ ( event.target ).closest(
				'[data-action="remove"]'
			);

			if ( $remove ) {
				$remove.closest( 'li' ).remove();
				$add.focus();
			}
		},
		{ signal }
	);

	return add;
}

/**
 * The IDs that describe `$field`, the one of its error message first when it shows.
 *
 * @param {Element}     $field   The field.
 * @param {HTMLElement} $message Its error message.
 * @param {boolean}     isShown  Whether its error message shows.
 */
function describe( $field, $message, isShown ) {
	const ids = ( $field.getAttribute( 'aria-describedby' ) ?? '' )
		.split( ' ' )
		.filter( ( id ) => id && id !== $message.id );
	const describedBy = ( isShown ? [ $message.id, ...ids ] : ids ).join( ' ' );

	if ( describedBy ) {
		$field.setAttribute( 'aria-describedby', describedBy );
	} else {
		$field.removeAttribute( 'aria-describedby' );
	}
}

/**
 * Shows that `$field` is in error: it says so (aria-invalid), and its `message`, the one of the browser unless given,
 * shows under it and describes it; in a row of a list, such as a read date, under the row.
 *
 * @param {Field}  $field    The field.
 * @param {string} [message] Its message.
 */
export function showError( $field, message = $field.validationMessage ) {
	let $message = errorMessages.get( $field );

	if ( ! $message ) {
		$message = document.createElement( 'span' );
		$message.className = 'library-error';
		$message.id = `library-error-${ ++errorCount }`;
		errorMessages.set( $field, $message );
	}

	$message.textContent = message;

	const $row = $field.closest( 'li' );

	if ( $row ) {
		$row.append( $message );
	} else {
		$field.after( $message );
	}

	$field.setAttribute( 'aria-invalid', 'true' );
	describe( $field, $message, true );
}

/**
 * Gives the focus to the title of a page opened, where assistive technologies read on, without its outline: the title
 * is no control, and Safari on iOS draws it after a tap, as a link tapped there takes no focus to tell.
 *
 * @param {HTMLElement} $title The title.
 */
export function focusTitle( $title ) {
	$title.focus( { preventScroll: true, focusVisible: false } );
}

/**
 * Marks `$element` as busy while it loads, as assistive technologies read it: aria-busy true, or no attribute.
 *
 * @param {Element} $element The element.
 * @param {boolean} isBusy   Whether it is busy.
 */
export function showBusy( $element, isBusy ) {
	if ( isBusy ) {
		$element.setAttribute( 'aria-busy', 'true' );
	} else {
		$element.removeAttribute( 'aria-busy' );
	}
}

/**
 * Says on the button that sends a form that it is saving, its `data-saving`, or its label once saved.
 *
 * @param {HTMLElement} $submit  The button.
 * @param {boolean}     isSaving Whether the form is saving.
 */
export function showSaving( $submit, isSaving ) {
	$submit.dataset.label ??= $submit.textContent;
	$submit.textContent = isSaving
		? $submit.dataset.saving
		: $submit.dataset.label;
}

/**
 * Clears the error of `$field`, its message gone.
 *
 * @param {Field} $field The field.
 */
function clearError( $field ) {
	const $message = errorMessages.get( $field );

	$field.removeAttribute( 'aria-invalid' );

	if ( $message ) {
		$message.remove();
		describe( $field, $message, false );
	}
}

/**
 * Shows at its fields what the browser finds wrong with `$form` when it is sent, rather than in a bubble on the first one:
 * the details that hold a field in error open, the first one takes the focus, unless `focus` is false, as for a form saved
 * as it changes, which leaves the focus where it went. A field in error loses it once valid, as it changes or as another
 * one bounds it, such as an end by its start; it shows it again only when the form is sent, not at each key on the way.
 * Returns the function that clears the errors, for a form filled again.
 *
 * @param {HTMLFormElement} $form           The form.
 * @param {AbortSignal}     signal          Removes the listeners.
 * @param {Object}          [options]       Options.
 * @param {boolean}         [options.focus] Whether the first field in error takes the focus, true by default.
 * @return {() => void} The function that clears the errors.
 */
export function showErrors( $form, signal, { focus = true } = {} ) {
	let focused = false;

	// The browser tells each field in error, one after the other, as the form is sent.
	$form.addEventListener(
		'invalid',
		( event ) => {
			const $field = /** @type {Field} */ ( event.target );

			event.preventDefault();
			showError( $field );
			$field.closest( 'details' )?.setAttribute( 'open', '' );

			if ( focus && ! focused ) {
				focused = true;
				$field.focus();
				setTimeout( () => {
					focused = false;
				} );
			}
		},
		{ capture: true, signal }
	);

	$form.addEventListener(
		'input',
		() =>
			$form
				.querySelectorAll( '[aria-invalid="true"]' )
				.forEach(
					( /** @type {Field} */ $field ) =>
						$field.validity.valid && clearError( $field )
				),
		{ signal }
	);

	return () =>
		$form.querySelectorAll( '[aria-invalid="true"]' ).forEach( clearError );
}
