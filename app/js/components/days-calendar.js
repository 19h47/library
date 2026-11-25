import { Component } from 'library-app/component';

/**
 * The days of a year as a calendar, the root of the component days-calendar: the year, the level of each day gone by,
 * from 0, none finished, to 4, and the memorable ones, by their days from January 1st.
 *
 * @typedef {Object} DaysCalendarValue
 * @property {number}   year      The year.
 * @property {number[]} levels    The level of each day gone by, from January 1st.
 * @property {number[]} memorable The memorable days, from 0 for January 1st.
 */

/**
 * The days of a year, the root of the component days-calendar: every day from January 1st, in weeks from Monday, the
 * first one in the column of its weekday; each as dark as its level, a day to come blank, a memorable one pulsing.
 *
 * Its API: `value`, the year, the levels of its days and the memorable ones.
 */
export class DaysCalendarElement extends Component {
	static properties = [ 'value' ];

	/** @type {DaysCalendarValue} */
	#value = { year: 0, levels: [], memorable: [] };

	/** @return {DaysCalendarValue} Its days. */
	get value() {
		return this.#value;
	}

	/** @param {DaysCalendarValue} value Its days. */
	set value( value ) {
		const { year, levels } = value;
		const length = 1 === new Date( year, 1, 29 ).getMonth() ? 366 : 365;
		// Monday first, as the weeks of ISO 8601.
		const weekday = ( new Date( year, 0, 1 ).getDay() + 6 ) % 7;
		const memorable = new Set( value.memorable );
		const $template = /** @type {HTMLTemplateElement} */ (
			this.querySelector( 'template' )
		).content.firstElementChild;
		const $days = [ ...Array( year ? length : 0 ).keys() ].map( ( day ) => {
			const $day = /** @type {HTMLElement} */ ( $template.cloneNode() );

			if ( day < levels.length ) {
				$day.dataset.level = levels[ day ].toString();
			} else {
				$day.dataset.day = 'later';
			}

			if ( memorable.has( day ) ) {
				$day.dataset.day = 'memorable';
				// Its own point of its pulse, so that no two beat together.
				$day.style.setProperty(
					'--library-days-calendar-day',
					day.toString()
				);
			}

			return $day;
		} );

		this.#value = value;
		$days[ 0 ]?.style.setProperty(
			'grid-column-start',
			( weekday + 1 ).toString()
		);
		this.querySelector( '[data-part="days"]' ).replaceChildren( ...$days );
	}
}
customElements.define( 'days-calendar', DaysCalendarElement );
