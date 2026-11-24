import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fill } from 'library-app/dom';
import { showBusy } from 'library-app/behaviors';
import { urlOf } from 'library-app/router';
import { formatDate, formatMonthName, formatNumber } from 'library-app/format';

/**
 * @import { Book } from 'library-app/api'
 * @import { DiaryCalendar } from 'library-app/parts'
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
 * The calendar of the diary: the days of reading of a year, its months side by side, or of one of its months, each day
 * the cover of the book whose reading ended then; above them, what they count, the readings ended, their pages, the
 * days of reading and the most in a row. Before and After go from a year, or a month, to the next one, from the first
 * year with a reading to the current month; a month of the year opens it.
 */
class LibraryCalendarElement extends Brick {
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
		// Before, or after: the year, or the month, before or after the one shown.
		const step = ( /** @type {number} */ by ) => {
			const { scale, year, month } = store.get().calendar;
			const date = new Date( year, month + by );

			store.showCalendar(
				'year' === scale
					? { year: year + by }
					: { year: date.getFullYear(), month: date.getMonth() }
			);
		};

		$scale.addEventListener(
			'change',
			() =>
				store.showCalendar( {
					scale: /** @type {'year'|'month'} */ ( $scale.value ),
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

		return [
			store.select(
				( state ) => state.calendar,
				( calendar ) => {
					const isYear = 'year' === calendar.scale;

					$scale.value = calendar.scale;
					$year.hidden = ! isYear;
					$month.hidden = isYear;
					showBusy( this, calendar.loading );
					this.#fillMonths( calendar, $year, $month );
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
	 * Fills what the days shown count: the year or the month shown, its readings ended, their pages, its days of reading
	 * and the most in a row.
	 *
	 * @param {DiaryCalendar} calendar The calendar.
	 */
	#fillStats( { year, month, scale, levels, ends, error } ) {
		const { books } = store.get();
		const isYear = 'year' === scale;
		const first = isYear ? 0 : dayOfYear( year, month, 1 );
		const last = isYear
			? levels.length
			: first + new Date( year, month + 1, 0 ).getDate();
		const shown = levels.slice( first, last );
		/** @type {Book[]} */
		const ended = Object.entries( ends )
			.filter(
				( [ day ] ) => Number( day ) >= first && Number( day ) < last
			)
			.flatMap( ( [ , ids ] ) => ids.map( ( id ) => books[ id ] ) )
			.filter( Boolean );

		fill( this, {
			period: isYear
				? year.toString()
				: formatDate(
						`${ year }-${ ( month + 1 )
							.toString()
							.padStart( 2, '0' ) }`
				  ),
			readings: formatNumber( ended.length ),
			pages: formatNumber(
				ended.reduce(
					( pages, book ) => pages + ( book.pages || 0 ),
					0
				)
			),
			days: formatNumber( shown.filter( Boolean ).length ),
			streak: formatNumber( streakOf( shown ) ),
			status: error,
		} );
	}

	/**
	 * Before, as long as a year with a reading comes before, and After, up to the current year or month.
	 *
	 * @param {DiaryCalendar}     calendar  The calendar.
	 * @param {HTMLButtonElement} $previous Before.
	 * @param {HTMLButtonElement} $next     After.
	 */
	#fillSteps( { scale, year, month, years }, $previous, $next ) {
		const now = new Date();
		const isYear = 'year' === scale;
		const firstYear = Math.min( years[ 0 ] ?? year, year );

		$previous.disabled = isYear
			? year <= firstYear
			: year <= firstYear && 0 === month;
		$next.disabled = isYear
			? year >= now.getFullYear()
			: year * 12 + month >= now.getFullYear() * 12 + now.getMonth();
	}
}
customElements.define( 'library-calendar', LibraryCalendarElement );
