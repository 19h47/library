import { Component } from 'library-app/component';
import { today } from 'library-app/dates';

/**
 * The period of a reading, the root of the component reading-period, from its start to its end, two date fields: a
 * start never after its end, an end never before its start, and neither later than today. The field typed in keeps its
 * bounds: Chrome starts the typing of a date field again as its bounds are set, which empties it at the next key, such
 * as the year 0002 of 2025; a bound is set only when it changes.
 *
 * Its API: `value`, the period as an interval of days, 2025-03-10/2025-03-21, a side empty when unknown; its fields,
 * reading_start and reading_end, which its form sends.
 */
export class ReadingPeriodElement extends Component {
	static properties = [ 'value' ];

	/** @return {string} The period, as an interval of days. */
	get value() {
		const [ $start, $end ] = this.#fields;

		return `${ $start.value }/${ $end.value }`;
	}

	/** @param {string} interval The period, as an interval of days. */
	set value( interval ) {
		const [ $start, $end ] = this.#fields;
		const [ start, end ] = interval.split( '/' );

		$start.value = start;
		$end.value = end;
		this.#limit();
	}

	/** @return {HTMLInputElement[]} Its start and its end. */
	get #fields() {
		return /** @type {HTMLInputElement[]} */ ( [
			...this.querySelectorAll( 'input[type="date"]' ),
		] );
	}

	/**
	 * Sets the bounds of the start and of the end, but those of the field typed in.
	 *
	 * @param {EventTarget|null} [$typed] The field typed in, if any.
	 */
	#limit( $typed = null ) {
		const [ $start, $end ] = this.#fields;
		const bound = (
			/** @type {HTMLInputElement} */ $input,
			/** @type {'min'|'max'} */ name,
			/** @type {string} */ value
		) => {
			if ( $input !== $typed && $input[ name ] !== value ) {
				$input[ name ] = value;
			}
		};

		bound( $start, 'max', $end.value || today() );
		bound( $end, 'min', $start.value );
		bound( $end, 'max', today() );
	}

	connect( signal ) {
		this.#limit();
		this.addEventListener(
			'input',
			( event ) => this.#limit( event.target ),
			{
				signal,
			}
		);
	}
}
customElements.define( 'reading-period', ReadingPeriodElement );
