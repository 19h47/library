import { __ } from 'library-app/app';
import { Component } from 'library-app/component';
import { fill } from 'library-app/dom';

/**
 * A book of the catalogue that has the ISBN or the title typed: its title, its URL, and what it is for the reader,
 * `added` on their shelves, `to-buy` a book to buy, `catalogue` on none of them.
 *
 * @typedef {Object} Duplicate
 * @property {string}                       title Its title.
 * @property {string}                       url   Its URL.
 * @property {'added'|'to-buy'|'catalogue'} kind  What it is for the reader.
 */

/**
 * What the catalogue tells of a book to add, the root of the component catalogue-status: the book of the catalogue
 * that has its ISBN or its title, which opens, with the button that buys a book to buy or puts one of the catalogue in
 * the library; else where the search of its ISBN in the catalogue of the BnF stands.
 *
 * Its API: `duplicate`, that book, none for none; `catalog`, where the search stands: "" for none, "searching", "found",
 * "unknown" or "unreachable". Its events: `keep`, the button that buys the book or puts it in the library pressed;
 * `open`, the link to the book followed.
 */
export class CatalogueStatusElement extends Component {
	static properties = [ 'duplicate', 'catalog' ];

	/** @type {Duplicate|null} */
	#duplicate = null;
	#catalog = '';

	/** @return {Duplicate|null} The book of the catalogue that has the ISBN or the title typed. */
	get duplicate() {
		return this.#duplicate;
	}

	/** @param {Duplicate|null} duplicate The book of the catalogue that has the ISBN or the title typed, none for none. */
	set duplicate( duplicate ) {
		this.#duplicate = duplicate;

		const kind = duplicate?.kind;
		/** @type {HTMLAnchorElement} */
		const $link = this.querySelector( '[data-field="duplicate"]' );
		/** @type {HTMLButtonElement} */
		const $keep = this.querySelector( '[data-action="keep"]' );

		fill( this, {
			found:
				'catalogue' === kind
					? __( 'This book is in the catalogue:', 'library' )
					: __( 'Already added:', 'library' ),
			duplicate: duplicate?.title ?? '',
		} );
		$link.href = duplicate?.url ?? '';
		// A book to buy is bought, one of the catalogue put in the library; one added already is theirs.
		$keep.hidden = ! duplicate || 'added' === kind;
		$keep.textContent =
			'catalogue' === kind
				? __( 'Put it in my library', 'library' )
				: __( 'I bought it', 'library' );
	}

	/** @return {string} Where the search of the ISBN in the catalogue of the BnF stands. */
	get catalog() {
		return this.#catalog;
	}

	/** @param {string} catalog Where the search stands: "", "searching", "found", "unknown" or "unreachable". */
	set catalog( catalog ) {
		const messages = {
			'': '',
			searching: __( 'Searching the catalogue of the BnF…', 'library' ),
			found: __(
				'Filled in from the catalogue of the BnF: check what it gives.',
				'library'
			),
			unknown: __(
				'The catalogue of the BnF has no book of this ISBN.',
				'library'
			),
			unreachable: __(
				'The catalogue of the BnF does not answer: try again later.',
				'library'
			),
		};

		this.#catalog = catalog;
		fill( this, { catalog: messages[ catalog ] ?? '' } );
	}

	connect( signal ) {
		this.querySelector( '[data-action="keep"]' ).addEventListener(
			'click',
			() => this.dispatchEvent( new Event( 'keep' ) ),
			{ signal }
		);
		this.querySelector( '[data-field="duplicate"]' ).addEventListener(
			'click',
			() => this.dispatchEvent( new Event( 'open' ) ),
			{ signal }
		);
	}
}
customElements.define( 'catalogue-status', CatalogueStatusElement );
