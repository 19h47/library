import { Component } from 'library-app/component';

/**
 * The search of a list, the root of the component search-form: its field and its button, then its keywords, which show
 * while it is used, as a search field shows its suggestions; one taken adds to the query, the cursor in its quotes, if
 * any, for the name to type. A keyword keeps the focus in the field, and the keyboard of a phone open.
 *
 * Its API: `value`, the query in its field, set without an event; `open`, whether its keywords show. Its events: the
 * input of its field, as it is typed in or takes a keyword; the submit of its form, with Enter or its button, which
 * sends nothing anywhere.
 */
export class SearchFormElement extends Component {
	static properties = [ 'value', 'open' ];

	/** @return {string} The query in its field. */
	get value() {
		return this.#input.value;
	}

	/** @param {string} value The query, its field left as it is when it has it already, its cursor too. */
	set value( value ) {
		if ( this.#input.value !== value ) {
			this.#input.value = value;
		}
	}

	/** @return {boolean} Whether its keywords show. */
	get open() {
		return ! this.#keywords.hidden;
	}

	/** @param {boolean} open Whether its keywords show. */
	set open( open ) {
		this.#keywords.hidden = ! open;
	}

	/** @return {HTMLInputElement} Its field. */
	get #input() {
		return this.querySelector( 'input' );
	}

	/** @return {HTMLElement} Its keywords. */
	get #keywords() {
		return this.querySelector( '[data-list="keywords"]' );
	}

	/**
	 * Adds `keyword` to the query, the cursor in its quotes, if any, as a user would type it.
	 *
	 * @param {string} keyword The keyword, such as series:"".
	 */
	#take( keyword ) {
		const $input = this.#input;
		const query = [ $input.value.trim(), keyword ]
			.filter( Boolean )
			.join( ' ' );
		const caret = keyword.endsWith( '""' )
			? query.length - 1
			: query.length;

		$input.value = query;
		$input.focus();
		$input.setSelectionRange( caret, caret );
		$input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
	}

	connect( signal ) {
		// A finger or a button down, until its click: what it is on takes the focus, but the tap has not reached it yet. A
		// finger lifts before the focus moves, so the tap lasts until its click, or until it turns into a scroll.
		let isPointing = false;

		// It never goes anywhere: its place searches.
		this.querySelector( 'form' ).addEventListener(
			'submit',
			( event ) => event.preventDefault(),
			{ signal }
		);
		this.addEventListener(
			'focusin',
			() => {
				this.open = true;
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
					this.open = false;
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
					this.open = false;
				}
			},
			{ signal }
		);
		this.#keywords.addEventListener(
			'mousedown',
			( event ) => event.preventDefault(),
			{ signal }
		);
		this.#keywords.addEventListener(
			'click',
			( event ) => {
				/** @type {HTMLElement} */
				const $keyword = /** @type {Element} */ (
					event.target
				).closest( '[data-keyword]' );

				if ( $keyword?.dataset.keyword ) {
					this.#take( $keyword.dataset.keyword );
				}
			},
			{ signal }
		);
	}
}
customElements.define( 'search-form', SearchFormElement );
