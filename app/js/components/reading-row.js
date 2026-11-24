import { Component } from 'library-app/component';

/** @import { ReadingPeriodElement } from './reading-period.js' */

/**
 * A reading of a book, the root of the component reading-row: its period, a reading-period, or that its dates are
 * unknown, then the rating it ended with. A reading whose dates are unknown hides its period, empty, which it sends.
 *
 * Its API: `value`, the reading as an interval of days and its rating, 2025-03-10/2025-03-21#8, a side empty when
 * unknown, no rating without one, "/" for a reading whose dates are unknown, which says so rather than showing them;
 * `unknown`, whether its dates are unknown, as its value set it; its fields, reading_start, reading_end and
 * reading_rating, which its form sends.
 */
export class ReadingRowElement extends Component {
	static properties = [ 'value' ];

	/** @return {string} The reading, as an interval of days and its rating. */
	get value() {
		const period = this.unknown ? '/' : this.#period.value;
		const rating = this.#rating.value;

		return rating ? `${ period }#${ rating }` : period;
	}

	/** @param {string} value The reading, as an interval of days and its rating; "/" for one of unknown dates. */
	set value( value ) {
		const [ period, rating = '' ] = value.split( '#' );
		const unknown = '/' === period;

		this.#period.hidden = unknown;
		/** @type {HTMLElement} */ (
			this.querySelector( '[data-part="unknown"]' )
		).hidden = ! unknown;
		this.#period.value = period;
		this.#rating.value = rating;
	}

	/** @return {boolean} Whether its dates are unknown. */
	get unknown() {
		return this.#period.hidden;
	}

	/** @return {ReadingPeriodElement} Its period. */
	get #period() {
		return this.querySelector( 'reading-period' );
	}

	/** @return {HTMLSelectElement} Its rating. */
	get #rating() {
		return this.querySelector( 'select' );
	}
}
customElements.define( 'reading-row', ReadingRowElement );
