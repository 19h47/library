/**
 * What bricks share: a book, a view, an author or a search opened on a click, the cards following the books,
 * the next page as the end of a list comes, a list of inputs to add and remove, the errors of a form at its fields.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { store } from 'library-app/app';
import { clone, fillBook } from 'library-app/render';

// The next page loads before the end of the list shows.
const LOAD_MARGIN_PX = 800;

// The message of each field in error, numbered across the forms for their IDs.
const errorMessages = new WeakMap();
let errorCount = 0;

/**
 * Fills again the cards of `$root` whose book changed, such as a book saved; `stateOf` gives the line
 * of a card. Returns the unsubscribe function.
 */
export function followBooks( $root, stateOf ) {
	let previous = store.get().books;

	return store.select(
		( state ) => state.books,
		( books ) => {
			$root.querySelectorAll( '.library-item' ).forEach( ( $item ) => {
				const book = books[ $item.dataset.id ];

				if ( book !== previous[ $item.dataset.id ] ) {
					fillBook( $item, book, stateOf( book, $item ) );
				}
			} );
			previous = books;
		}
	);
}

/** Opens the book of a card or a cover clicked in `$root`: the button of an element with the ID of a book. */
export function openBooks( $root, signal ) {
	$root.addEventListener(
		'click',
		( event ) => {
			const $book = event.target.closest( 'button' )?.closest( '[data-id]' );

			if ( $book && $root.contains( $book ) ) {
				store.openBook( Number( $book.dataset.id ) );
			}
		},
		{ signal }
	);
}

/** Switches to the view of a link clicked in `$root`, without reloading; a new tab or window stays a link. */
export function openViews( $root, signal ) {
	$root.addEventListener(
		'click',
		( event ) => {
			const $link = event.target.closest( 'a[data-view]' );

			if ( ! $link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}

			event.preventDefault();
			store.setView( $link.dataset.view );
		},
		{ signal }
	);
}

/**
 * Follows the links clicked in `$root` without reloading: to the page of an author, to a book, or to the books
 * a search finds. A new tab or window stays a link.
 */
export function openLinks( $root, signal ) {
	$root.addEventListener(
		'click',
		( event ) => {
			const $link = event.target.closest( 'a[data-author], a[data-book], a[data-search]' );

			if ( ! $link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
				return;
			}

			const { author, book, search } = $link.dataset;

			event.preventDefault();

			if ( author ) {
				store.openAuthor( Number( author ) );
			} else if ( book ) {
				store.openBook( Number( book ) );
			} else {
				store.searchBooks( search );
			}
		},
		{ signal }
	);
}

/**
 * Calls `callback` as the end `$more` of a list comes near. Returns the function
 * to call once the list has grown: its end may still be in view.
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
 * such as the authors of a book or its read dates. Removing an input gives the focus to `$add`, adding one gives it the focus.
 * `value` gives the value of a new input, `setup` prepares each one. Returns the function that adds an input, with a value.
 */
export function inputList( { $list, $add, id, signal, value = () => '', setup = () => {} } ) {
	const add = ( text = value() ) => {
		const $item = clone( id );
		const $input = $item.querySelector( 'input' );

		$input.value = text;
		setup( $input );
		$list.append( $item );

		return $input;
	};

	$add.addEventListener( 'click', () => add().focus(), { signal } );

	$list.addEventListener(
		'click',
		( event ) => {
			const $remove = event.target.closest( '[data-action="remove"]' );

			if ( $remove ) {
				$remove.closest( 'li' ).remove();
				$add.focus();
			}
		},
		{ signal }
	);

	return add;
}

/** The IDs that describe `$field`, the one of its error message first when it shows. */
function describe( $field, $message, isShown ) {
	const ids = ( $field.getAttribute( 'aria-describedby' ) ?? '' ).split( ' ' ).filter( ( id ) => id && id !== $message.id );
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

/** Clears the error of `$field`, its message gone. */
export function clearError( $field ) {
	const $message = errorMessages.get( $field );

	$field.removeAttribute( 'aria-invalid' );

	if ( $message ) {
		$message.remove();
		describe( $field, $message, false );
	}
}

/**
 * Shows at its fields what the browser finds wrong with `$form` when it is sent, rather than in a bubble on the first one:
 * the details that hold a field in error open, the first one takes the focus. A field changed loses its error once valid.
 * Returns the function that clears the errors, for a form filled again.
 */
export function showErrors( $form, signal ) {
	let focused = false;

	// The browser tells each field in error, one after the other, as the form is sent.
	$form.addEventListener(
		'invalid',
		( event ) => {
			event.preventDefault();
			showError( event.target );
			event.target.closest( 'details' )?.setAttribute( 'open', '' );

			if ( ! focused ) {
				focused = true;
				event.target.focus();
				setTimeout( () => {
					focused = false;
				} );
			}
		},
		{ capture: true, signal }
	);

	$form.addEventListener(
		'input',
		( event ) => {
			const $field = event.target;

			if ( 'true' !== $field.getAttribute( 'aria-invalid' ) ) {
				return;
			}

			if ( $field.validity.valid ) {
				clearError( $field );
			} else {
				showError( $field );
			}
		},
		{ signal }
	);

	return () => $form.querySelectorAll( '[aria-invalid="true"]' ).forEach( clearError );
}
