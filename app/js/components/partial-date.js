import { Component } from 'library-app/component';

/**
 * A date often known by its year only, the root of the component partial-date: its day, its month and its year. The
 * day waits for a month, and lists the days of that month, of a leap year while the year is unknown: a day the month
 * has not, as 31 then April, is emptied rather than moved. The year is required once a month is chosen. Its parts send
 * nothing: its form sends its value under its name, as it reads its data, and its reset empties them as any field.
 *
 * Its API: `name`, the one of its value in its form; `value`, the date as a year, a month or a day, 1929, 1929-11 or
 * 1929-11-08, empty without a year, set without an event, as a field set by a script.
 */
export class PartialDateElement extends Component {
	static properties = [ 'value' ];

	/** @return {string} The name of its value in its form. */
	get name() {
		return this.getAttribute( 'name' ) ?? '';
	}

	/** @return {string} The date, as a year, a month or a day, empty without a year. */
	get value() {
		const { day, month, year } = this.#parts;

		if ( ! year.value ) {
			return '';
		}

		return [ year.value, month.value, month.value && day.value ]
			.filter( Boolean )
			.join( '-' );
	}

	/** @param {string} date The date, as a year, a month or a day, or empty. */
	set value( date ) {
		const { day, month, year } = this.#parts;
		const [ yearValue = '', monthValue = '', dayValue = '' ] =
			date.split( '-' );

		year.value = yearValue;
		month.value = monthValue;
		day.value = dayValue;
		this.#limit();
	}

	/** @return {{day: HTMLSelectElement, month: HTMLSelectElement, year: HTMLInputElement}} Its parts. */
	get #parts() {
		return {
			day: this.querySelector( '[data-part="day"]' ),
			month: this.querySelector( '[data-part="month"]' ),
			year: this.querySelector( '[data-part="year"]' ),
		};
	}

	// The days of the month chosen, the day once there is a month, the year once there is one.
	#limit() {
		const { day, month, year } = this.#parts;
		// The day 0 of the next month, the last of this one; a leap year, 2000, while the year is unknown.
		const last = new Date(
			Number( year.value ) || 2000,
			Number( month.value ),
			0
		).getDate();

		[ ...day.options ].forEach( ( $option ) => {
			$option.hidden = Number( $option.value ) > last;
		} );

		if ( ! month.value || Number( day.value ) > last ) {
			day.value = '';
		}

		day.disabled = ! month.value;
		year.required = Boolean( month.value );
	}

	connect( signal ) {
		const $form = this.closest( 'form' );

		this.addEventListener( 'change', () => this.#limit(), { signal } );
		$form?.addEventListener(
			'formdata',
			( event ) => event.formData.set( this.name, this.value ),
			{ signal }
		);
		// The reset of the form empties the parts after its event: the days and the day follow then.
		$form?.addEventListener(
			'reset',
			() => queueMicrotask( () => this.#limit() ),
			{ signal }
		);
		this.#limit();
	}
}
customElements.define( 'partial-date', PartialDateElement );
