import { __ } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fill } from 'library-app/dom';
import { followCards, renderBooks, showBusy } from 'library-app/behaviors';
import { urlOf } from 'library-app/router';
import { formatDate, formatMonthName, formatNumber } from 'library-app/format';

/**
 * @import { Book } from 'library-app/api'
 * @import { CalendarScale, DiaryCalendar } from 'library-app/parts'
 * @import { CalendarDay } from '../components/month-calendar.js'
 */

// A day, in milliseconds, to count the days of a year from its first one.
const DAY_MS = 24 * 60 * 60 * 1000;

/**
 * The day of its year of a day of `month`, from 0 for January 1st.
 *
 * @param {number} year  The year.
 * @param {number} month The month, from 0.
 * @param {number} day   The day of the month, from 1.
 * @return {number} The day of the year.
 */
function dayOfYear( year, month, day ) {
	return ( Date.UTC( year, month, day ) - Date.UTC( year, 0, 1 ) ) / DAY_MS;
}

/**
 * A day as an ISO 8601 date: 2026-11-24.
 *
 * @param {number} year  The year.
 * @param {number} month The month, from 0.
 * @param {number} day   The day of the month, from 1.
 * @return {string} The date.
 */
function isoDate( year, month, day ) {
	const pad = ( /** @type {number} */ number ) =>
		number.toString().padStart( 2, '0' );

	return `${ year }-${ pad( month + 1 ) }-${ pad( day ) }`;
}

/**
 * The most days read in a row among `levels`.
 *
 * @param {number[]} levels The level of each day, in order.
 * @return {number} The most days read in a row.
 */
function streakOf( levels ) {
	let streak = 0;
	let current = 0;

	levels.forEach( ( level ) => {
		current = level ? current + 1 : 0;
		streak = Math.max( streak, current );
	} );

	return streak;
}

/**
 * The calendar of the diary: the days of reading of a year, its months side by side, of one of its months, each day the
 * cover of the book whose reading ended then, or of one of its days, the books read then, as cards; above them, what they
 * count, the readings ended and their pages, and for a year or a month, the days of reading and the most in a row. Before
 * and After go from a year, a month or a day to the next one, from the first year with a reading to today; a month of the
 * year opens it, a day of the month opens it.
 */
class LibraryCalendarElement extends Brick {
	/**
	 * The books whose reading ended on the day shown, whose cards say so.
	 *
	 * @type {Set<number>}
	 */
	#ended = new Set();

	// The day shown, by its date, whose cards a card of another day does not stand for: their lines differ.
	#date = '';

	connect( signal ) {
		/** @type {HTMLSelectElement} */
		const $scale = this.querySelector( 'select[name="scale"]' );
		/** @type {HTMLButtonElement} */
		const $previous = this.querySelector( '[data-action="previous"]' );
		/** @type {HTMLButtonElement} */
		const $next = this.querySelector( '[data-action="next"]' );
		/** @type {HTMLElement} */
		const $year = this.querySelector( '[data-part="year"]' );
		/** @type {HTMLElement} */
		const $month = this.querySelector( '[data-part="month"]' );
		/** @type {HTMLElement} */
		const $day = this.querySelector( '[data-part="day"]' );
		const $cards = $day.querySelector( '[data-list="day"]' );
		const line = ( /** @type {Book} */ book ) =>
			this.#ended.has( book.id )
				? __( 'Finished that day', 'library' )
				: __( 'In progress', 'library' );
		// Before, or after: the year, the month or the day before or after the one shown.
		const step = ( /** @type {number} */ by ) => {
			const { scale, year, month, day } = store.get().calendar;
			const date = new Date(
				year + ( 'year' === scale ? by : 0 ),
				month + ( 'month' === scale ? by : 0 ),
				'day' === scale ? day + by : 1
			);

			store.showCalendar( {
				year: date.getFullYear(),
				month: date.getMonth(),
				...( 'day' === scale && { day: date.getDate() } ),
			} );
		};

		$scale.addEventListener(
			'change',
			() =>
				store.showCalendar( {
					scale: /** @type {CalendarScale} */ ( $scale.value ),
				} ),
			{ signal }
		);
		$previous.addEventListener( 'click', () => step( -1 ), { signal } );
		$next.addEventListener( 'click', () => step( 1 ), { signal } );
		$year.addEventListener(
			'click',
			( event ) => {
				/** @type {HTMLButtonElement|null} */
				const $open = /** @type {Element} */ ( event.target ).closest(
					'[data-action="month"]'
				);

				if ( $open ) {
					store.showCalendar( {
						scale: 'month',
						month: Number( $open.value ),
					} );
				}
			},
			{ signal }
		);
		$month.addEventListener(
			'dayselect',
			( event ) =>
				store.showCalendar( {
					scale: 'day',
					day: /** @type {CustomEvent<{day: number}>} */ ( event )
						.detail.day,
				} ),
			{ signal }
		);

		return [
			followCards( $cards, line ),
			store.select(
				( state ) => state.calendar,
				( calendar ) => {
					$scale.value = calendar.scale;
					$year.hidden = 'year' !== calendar.scale;
					$month.hidden = 'month' !== calendar.scale;
					$day.hidden = 'day' !== calendar.scale;
					showBusy( this, calendar.loading );
					this.#fillMonths( calendar, $year, $month );
					this.#fillDay( calendar, $day, line );
					this.#fillStats( calendar );
					this.#fillSteps( calendar, $previous, $next );
				}
			),
		];
	}

	/**
	 * Fills the months of the year, and the month shown, its days with their books.
	 *
	 * @param {DiaryCalendar} calendar The calendar.
	 * @param {HTMLElement}   $year    The months of the year.
	 * @param {HTMLElement}   $month   The month shown.
	 */
	#fillMonths( calendar, $year, $month ) {
		const { year } = calendar;
		const $names = /** @type {HTMLButtonElement[]} */ ( [
			...$year.querySelectorAll( '[data-action="month"]' ),
		] );

		$year
			.querySelectorAll( 'month-calendar' )
			.forEach( ( $days, month ) => {
				$names[ month ].textContent = formatMonthName(
					new Date( year, month )
				);
				$days.value = {
					year,
					month,
					days: this.#days( calendar, month ),
				};
			} );
		$month.querySelector( 'month-calendar' ).value = {
			year,
			month: calendar.month,
			days: this.#days( calendar, calendar.month ),
		};
	}

	/**
	 * Fills the day shown: the cards of the books read then, or why there is none.
	 *
	 * @param {DiaryCalendar}            calendar The calendar.
	 * @param {HTMLElement}              $day     The day.
	 * @param {( book: Book ) => string} line     The line of a card.
	 */
	#fillDay( { year, month, day, reads, ends, loaded }, $day, line ) {
		const index = dayOfYear( year, month, day );
		const ids = [ ...new Set( reads[ index ] ?? [] ) ];
		const $cards = $day.querySelector( '[data-list="day"]' );
		const date = isoDate( year, month, day );

		if ( date !== this.#date ) {
			this.#date = date;
			$cards.replaceChildren();
		}

		this.#ended = new Set( ends[ index ] ?? [] );
		renderBooks( $cards, ids, line );
		/** @type {HTMLElement} */ (
			$day.querySelector( '[data-part="none"]' )
		).hidden = ! loaded || ids.length > 0;
	}

	/**
	 * The days gone by of the month `month`: the level of each, and the books whose reading ended then.
	 *
	 * @param {DiaryCalendar} calendar The calendar.
	 * @param {number}        month    The month, from 0.
	 * @return {CalendarDay[]} Its days gone by.
	 */
	#days( { year, levels, ends }, month ) {
		const { books } = store.get();
		const first = dayOfYear( year, month, 1 );
		const length = new Date( year, month + 1, 0 ).getDate();

		return levels
			.slice( first, first + length )
			.map( ( level, index ) => ( {
				level,
				books: ( ends[ first + index ] ?? [] )
					.filter( ( id ) => id in books )
					.map( ( id ) => ( {
						title: books[ id ].title,
						url: urlOf( { view: 'book', id } ),
						cover: books[ id ].cover,
					} ) ),
			} ) );
	}

	/**
	 * Fills what the days shown count: the year, the month or the day shown, its readings ended and their pages, and for a
	 * year or a month, its days of reading and the most in a row.
	 *
	 * @param {DiaryCalendar} calendar The calendar.
	 */
	#fillStats( { year, month, day, scale, levels, pages, ends, error } ) {
		const { books } = store.get();
		const isDay = 'day' === scale;
		const [ first, last ] = {
			year: [ 0, levels.length ],
			month: [
				dayOfYear( year, month, 1 ),
				dayOfYear( year, month + 1, 1 ),
			],
			day: [
				dayOfYear( year, month, day ),
				dayOfYear( year, month, day + 1 ),
			],
		}[ scale ];
		const shown = levels.slice( first, last );
		/** @type {Book[]} */
		const ended = Object.entries( ends )
			.filter(
				( [ index ] ) =>
					Number( index ) >= first && Number( index ) < last
			)
			.flatMap( ( [ , ids ] ) => ids.map( ( id ) => books[ id ] ) )
			.filter( Boolean );

		fill( this, {
			period: {
				year: year.toString(),
				month: formatDate( isoDate( year, month, 1 ).slice( 0, 7 ) ),
				day: formatDate( isoDate( year, month, day ), 'full' ),
			}[ scale ],
			readings: formatNumber( ended.length ),
			// The pages read that day, of the books read from a known start, or of the books ended in the year or the month.
			pages: formatNumber(
				isDay
					? pages[ first ] ?? 0
					: ended.reduce(
							( sum, book ) => sum + ( book.pages || 0 ),
							0
					  )
			),
			days: isDay ? '' : formatNumber( shown.filter( Boolean ).length ),
			streak: isDay ? '' : formatNumber( streakOf( shown ) ),
			status: error,
		} );
	}

	/**
	 * Before, as long as a year with a reading comes before, and After, up to the current year, month or day.
	 *
	 * @param {DiaryCalendar}     calendar  The calendar.
	 * @param {HTMLButtonElement} $previous Before.
	 * @param {HTMLButtonElement} $next     After.
	 */
	#fillSteps( { scale, year, month, day, years }, $previous, $next ) {
		const today = new Date();
		const firstYear = Math.min( years[ 0 ] ?? year, year );
		// The first day of the year, the month or the day shown, and of the one that follows it.
		const start = {
			year: new Date( year, 0, 1 ),
			month: new Date( year, month, 1 ),
			day: new Date( year, month, day ),
		}[ scale ];
		const end = {
			year: new Date( year + 1, 0, 1 ),
			month: new Date( year, month + 1, 1 ),
			day: new Date( year, month, day + 1 ),
		}[ scale ];

		$previous.disabled = start <= new Date( firstYear, 0, 1 );
		$next.disabled = end > today;
	}
}
customElements.define( 'library-calendar', LibraryCalendarElement );
