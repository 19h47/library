import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { urlOf } from 'library-app/router';

// The views for development, out of the navigation, opened by searching their name then Enter or the button: the
// styleguide and the reference of the API. Typed, a name is a search, as any other, until then: the view does not change
// during the typing.
const HIDDEN_VIEWS = [ 'styleguide', 'api' ];

/**
 * The search of the books: what is typed is the query, searched after a pause in the typing, or at once with Enter or its
 * button; the name of a view for development, then Enter or the button, opens it, the search left empty. Its keywords show under it while it is used, as a search field shows its suggestions; one taken adds
 * to the query, the cursor in its quotes, if any, for the name to type.
 */
class LibrarySearchElement extends Brick {
	connect( signal ) {
		const $input = this.querySelector( 'input' );
		/** @type {HTMLElement} */
		const $keywords = this.querySelector( '[data-list="keywords"]' );
		const hide = () => {
			$keywords.hidden = true;
		};
		// A finger or a button down, until its click: what it is on takes the focus, but the tap has not reached it yet. A
		// finger lifts before the focus moves, so the tap lasts until its click, or until it turns into a scroll.
		let isPointing = false;

		$input.addEventListener(
			'input',
			() => store.setQuery( $input.value ),
			{ signal }
		);
		// Enter or the button: the search at once, without waiting for a pause, or the view for development typed.
		this.querySelector( 'form' ).addEventListener(
			'submit',
			( event ) => {
				const view = $input.value.trim().toLowerCase();

				event.preventDefault();

				if ( ! HIDDEN_VIEWS.includes( view ) ) {
					store.setQuery( $input.value, { now: true } );
					return;
				}

				// The query may be empty already, as when the word comes at once: the field does not follow it then.
				$input.value = '';
				store.setQuery( '' );
				navigation.navigate( urlOf( { view } ) );
			},
			{ signal }
		);
		this.addEventListener(
			'focusin',
			() => {
				$keywords.hidden = false;
			},
			{ signal }
		);
		// Left with the keyboard, or with a tap elsewhere once it has reached what it was on: hidden as the finger comes
		// down, the keywords would move it away first. A tap on a keyword may take the focus from the field first.
		this.addEventListener(
			'focusout',
			( event ) => {
				if (
					! isPointing &&
					event.relatedTarget &&
					! this.contains(
						/** @type {Node} */ ( event.relatedTarget )
					)
				) {
					hide();
				}
			},
			{ signal }
		);
		document.addEventListener(
			'pointerdown',
			() => {
				isPointing = true;
			},
			{ signal }
		);
		// Nor does a press that ends elsewhere, whose click never comes: the keyboard ends it.
		[ 'pointercancel', 'keydown' ].forEach( ( type ) =>
			document.addEventListener(
				type,
				() => {
					isPointing = false;
				},
				{ signal }
			)
		);
		document.addEventListener(
			'click',
			( event ) => {
				isPointing = false;

				if ( ! this.contains( /** @type {Node} */ ( event.target ) ) ) {
					hide();
				}
			},
			{ signal }
		);
		// A keyword keeps the focus in the field, and the keyboard of a phone open.
		$keywords.addEventListener(
			'mousedown',
			( event ) => event.preventDefault(),
			{ signal }
		);
		$keywords.addEventListener(
			'click',
			( event ) => {
				/** @type {HTMLElement} */
				const $keyword = /** @type {Element} */ (
					event.target
				).closest( '[data-keyword]' );
				const keyword = $keyword?.dataset.keyword;

				if ( ! keyword ) {
					return;
				}

				const query = [ $input.value.trim(), keyword ]
					.filter( Boolean )
					.join( ' ' );
				const caret = keyword.endsWith( '""' )
					? query.length - 1
					: query.length;

				$input.value = query;
				$input.focus();
				$input.setSelectionRange( caret, caret );
				store.setQuery( query );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.query,
				( query ) => {
					if ( $input.value !== query ) {
						$input.value = query;
					}
				}
			),
		];
	}
}
customElements.define( 'library-search', LibrarySearchElement );
