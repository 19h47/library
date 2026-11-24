import { Component } from 'library-app/component';
import { fillCover } from 'library-app/dom';

/**
 * @import { Cover } from 'library-app/api'
 */

/**
 * A book of a day: its title, where it leads, and its cover, if any.
 *
 * @typedef {Object} CalendarBook
 * @property {string}     title Its title.
 * @property {string}     url   Its page.
 * @property {Cover|null} cover Its cover, none without one.
 */

/**
 * A day gone by: its level, from 0, none read, to 4, and the books whose reading ended that day.
 *
 * @typedef {Object} CalendarDay
 * @property {number}         level Its level.
 * @property {CalendarBook[]} books The books whose reading ended that day.
 */

/**
 * The days of a month: its year, the month, from 0 for January, and its days gone by, from the 1st; the others are to
 * come.
 *
 * @typedef {Object} MonthCalendarValue
 * @property {number}        year  The year.
 * @property {number}        month The month, from 0.
 * @property {CalendarDay[]} days  Its days gone by.
 */

/**
 * The days of a month, the root of the component month-calendar: in weeks from Monday, the first one in the column of its
 * weekday, each as dark as its level, a day to come its outline; covers, each day its number and the cover of the book
 * whose reading ended that day, a link to it, the others counted.
 *
 * Its API: `value`, the year, the month and its days gone by.
 */
export class MonthCalendarElement extends Component {
	static properties = [ 'value' ];

	/** @type {MonthCalendarValue} */
	#value = { year: 0, month: 0, days: [] };

	/** @return {MonthCalendarValue} Its days. */
	get value() {
		return this.#value;
	}

	/** @param {MonthCalendarValue} value Its days. */
	set value( value ) {
		const { year, month, days } = value;
		const length = new Date( year, month + 1, 0 ).getDate();
		// Monday first, as the weeks of ISO 8601.
		const weekday = ( new Date( year, month, 1 ).getDay() + 6 ) % 7;
		const $template = /** @type {HTMLTemplateElement} */ (
			this.querySelector( 'template' )
		).content.firstElementChild;
		const $days = [ ...Array( year ? length : 0 ).keys() ].map( ( day ) => {
			const $day = /** @type {HTMLElement} */ (
				$template.cloneNode( true )
			);

			if ( day < days.length ) {
				$day.dataset.level = days[ day ].level.toString();
				this.#fillBooks( $day, days[ day ].books );
			} else {
				$day.dataset.day = 'later';
			}

			const $number = $day.querySelector( '[data-part="number"]' );

			if ( $number ) {
				$number.textContent = ( day + 1 ).toString();
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

	/**
	 * Shows on the day `$day` the first of `books`, a link to it, and how many others, in the variant that shows them.
	 *
	 * @param {HTMLElement}    $day  The day.
	 * @param {CalendarBook[]} books Its books.
	 */
	#fillBooks( $day, books ) {
		/** @type {HTMLAnchorElement|null} */
		const $book = $day.querySelector( '[data-part="book"]' );
		const [ book ] = books;

		if ( ! $book || ! book ) {
			return;
		}

		const $more = /** @type {HTMLElement} */ (
			$day.querySelector( '[data-part="more"]' )
		);

		$book.hidden = false;
		$book.href = book.url;
		$book.querySelector( '[data-part="title"]' ).textContent = book.title;
		fillCover( $book.querySelector( 'img' ), book.cover );
		$more.hidden = books.length < 2;
		$more.textContent = `+${ books.length - 1 }`;
	}
}
customElements.define( 'month-calendar', MonthCalendarElement );
