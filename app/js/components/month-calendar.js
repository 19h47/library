import { Component } from 'library-app/component';
import { fillCover } from 'library-app/dom';
import { formatDate } from 'library-app/format';

/**
 * @import { Cover } from 'library-app/api'
 */

/**
 * A book of a day: its title, and its cover, if any.
 *
 * @typedef {Object} CalendarBook
 * @property {string}     title Its title.
 * @property {Cover|null} cover Its cover, none without one.
 */

/**
 * A day gone by: its level, from 0, none read, to 4, the books whose reading ended that day, and where the day leads.
 *
 * @typedef {Object} CalendarDay
 * @property {number}         level Its level.
 * @property {CalendarBook[]} books The books whose reading ended that day.
 * @property {string}         [url] Where the day leads, the calendar of that day.
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
 * weekday, each as dark as its level, a day to come its outline, today marked; covers, each day a link to it, its number
 * and the cover of the book whose reading ended that day, or its title, the others counted.
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
		const now = new Date();
		const $days = [ ...Array( year ? length : 0 ).keys() ].map( ( day ) => {
			const $day = /** @type {HTMLElement} */ (
				$template.cloneNode( true )
			);
			const date = `${ year }-${ ( month + 1 )
				.toString()
				.padStart( 2, '0' ) }-${ ( day + 1 )
				.toString()
				.padStart( 2, '0' ) }`;

			if (
				year === now.getFullYear() &&
				month === now.getMonth() &&
				day + 1 === now.getDate()
			) {
				$day.setAttribute( 'aria-current', 'date' );
			}

			if ( day < days.length ) {
				$day.dataset.level = days[ day ].level.toString();
				this.#fillBooks( $day, days[ day ].books );
			} else {
				$day.dataset.day = 'later';
			}

			this.#fillLink( $day, day + 1, date, days[ day ]?.url ?? '' );

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
	 * Fills the link of the day `$day`, in the variant that has one: its number, its date, said in full, and where it
	 * leads; a day to come leads nowhere.
	 *
	 * @param {HTMLElement} $day   The day.
	 * @param {number}      number Its day of the month.
	 * @param {string}      date   Its date, ISO 8601.
	 * @param {string}      url    Where it leads, empty for nowhere.
	 */
	#fillLink( $day, number, date, url ) {
		/** @type {HTMLAnchorElement|null} */
		const $link = $day.querySelector( '[data-part="link"]' );

		if ( ! $link ) {
			return;
		}

		$link.querySelector( '[data-part="number"]' ).textContent =
			number.toString();
		$link.querySelector( '[data-part="date"]' ).textContent = formatDate(
			date,
			'full'
		);

		if ( url ) {
			$link.href = url;
		}
	}

	/**
	 * Shows on the day `$day` the first of `books`, its cover or its title, and how many others, in the variant that shows
	 * them.
	 *
	 * @param {HTMLElement}    $day  The day.
	 * @param {CalendarBook[]} books Its books.
	 */
	#fillBooks( $day, books ) {
		/** @type {HTMLElement|null} */
		const $book = $day.querySelector( '[data-part="book"]' );
		const [ book ] = books;

		if ( ! $book || ! book ) {
			return;
		}

		const $more = /** @type {HTMLElement} */ (
			$day.querySelector( '[data-part="more"]' )
		);

		$book.hidden = false;
		$book.querySelector( '[data-part="title"]' ).textContent = book.title;
		fillCover( $book.querySelector( 'img' ), book.cover );
		$more.hidden = books.length < 2;
		$more.textContent = `+${ books.length - 1 }`;
	}
}
customElements.define( 'month-calendar', MonthCalendarElement );
