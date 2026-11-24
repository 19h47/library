import { __, sprintf } from 'library-app/app';
import { Component } from 'library-app/component';
import { formatNumber } from 'library-app/format';

/**
 * A progress bar, the root of the component progress-bar: its count shows how far it is, "9 / 14", and its bar says it
 * in words, "9 of 14", rather than as a percentage.
 *
 * Its API: `value` and `max`, as the ones of a progress; `labelledBy`, the ID of the element that names it.
 */
export class ProgressBarElement extends Component {
	static properties = [ 'value', 'max', 'labelledBy' ];

	/** @return {number} How far it is. */
	get value() {
		return this.#progress.value;
	}

	/** @param {number} value How far it is. */
	set value( value ) {
		this.#progress.value = value;
		this.#show();
	}

	/** @return {number} Where it ends. */
	get max() {
		return this.#progress.max;
	}

	/** @param {number} max Where it ends. */
	set max( max ) {
		this.#progress.max = max;
		this.#show();
	}

	/** @return {string} The ID of the element that names it. */
	get labelledBy() {
		return this.#progress.getAttribute( 'aria-labelledby' ) ?? '';
	}

	/** @param {string} id The ID of the element that names it. */
	set labelledBy( id ) {
		this.#progress.setAttribute( 'aria-labelledby', id );
	}

	/** @return {HTMLProgressElement} Its bar. */
	get #progress() {
		return this.querySelector( 'progress' );
	}

	/** Shows how far it is, in its count and in the words of its bar. */
	#show() {
		const value = formatNumber( this.value );
		const max = formatNumber( this.max );

		this.querySelector(
			'[data-part="count"]'
		).textContent = `${ value } / ${ max }`;
		this.#progress.ariaValueText = sprintf(
			/* translators: 1: number done, 2: total, e.g. "13 of 14". */
			__( '%1$s of %2$s', 'library' ),
			value,
			max
		);
	}
}
customElements.define( 'progress-bar', ProgressBarElement );
