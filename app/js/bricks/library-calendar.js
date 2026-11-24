import { __ } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fill } from 'library-app/dom';
import {
	focusTitle,
	followCards,
	renderBooks,
	showBusy,
} from 'library-app/behaviors';
import { urlOf } from 'library-app/router';
import { calendarId, streakOf } from 'library-app/parts';
import { formatDate, formatMonthName, formatNumber } from 'library-app/format';

/**
 * @import { Book } from 'library-app/api'
 * @import { CalendarPeriod, CalendarScale, DiaryCalendar } from 'library-app/parts'
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
 * The URL of the calendar showing `period`.
 *
 * @param {CalendarPeriod} period What it shows.
 * @return {string} Its URL.
 */
function calendarUrl( period ) {
	return urlOf( { view: 'calendar', id: calendarId( period ) } );
}

/**
 * Leads `$link` to `url`, or nowhere, out of use, without one.
 *
 * @param {HTMLAnchorElement} $link The link.
 * @param {string}            url   Where it leads, empty for nowhere.
 */
function lead( $link, url ) {
	if ( url ) {
		$link.href = url;
		$link.removeAttribute( 'role' );
		$link.removeAttribute( 'aria-disabled' );
	} else {
		// A link without a URL, out of use, as a link still.
		$link.removeAttribute( 'href' );
		$link.setAttribute( 'role', 'link' );
		$link.setAttribute( 'aria-disabled', 'true' );
	}
}

/**
 * The calendar, a view of its own opened from the diary: the days of reading of a year, its months side by side, of one
 * of its months, each day the cover of the book whose reading ended then, or of one of its days, the books read then, as
 * cards; above them, what they count, the readings ended and their pages, and for a year or a month, the days of reading
 * and the most in a row. Its URL says what it shows, which Before and After, a month of the year and a day of the month
 * lead to, from the first year with a reading to today; its scale goes to the same period, the year, its month or its day.
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
		/** @type {HTMLAnchorElement} */
		const $previous = this.querySelector( '[data-part="previous"]' );
		/** @type {HTMLAnchorElement} */
		const $next = this.querySelector( '[data-part="next"]' );
		/** @type {HTMLElement} */
		const $year = this.querySelector( '[data-part="year"]' );
		/** @type {HTMLElement} */
		const $month = this.querySelector( '[data-part="month"]' );
		/** @type {HTMLElement} */
		const $day = this.querySelector( '[data-part="day"]' );
		/** @type {HTMLElement} */
		const $period = this.querySelector( '[data-field="period"]' );
		const $cards = $day.querySelector( '[data-list="day"]' );
		const line = ( /** @type {Book} */ book ) =>
			this.#ended.has( book.id )
				? __( 'Finished that day', 'library' )
				: __( 'In progress', 'library' );

		$scale.addEventListener(
			'change',
			() =>
				navigation.navigate(
					calendarUrl( {
						...store.get().calendar,
						scale: /** @type {CalendarScale} */ ( $scale.value ),
					} )
				),
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
			// Opened, what it shows takes the focus, as the title of a page.
			store.select(
				( state ) =>
					'calendar' === state.view &&
					calendarId( state.calendar ).toString(),
				( shown ) => {
					if ( shown && 'forward' === store.get().moved ) {
						focusTitle( $period );
					}
				}
			),
		];
	}

	/**
	 * Fills the months of the year, each a link to it, and the month shown, its days with their books, each a link to it.
	 *
	 * @param {DiaryCalendar} calendar The calendar.
	 * @param {HTMLElement}   $year    The months of the year.
	 * @param {HTMLElement}   $month   The month shown.
	 */
	#fillMonths( calendar, $year, $month ) {
		const { year } = calendar;
		const $links = /** @type {HTMLAnchorElement[]} */ ( [
			...$year.querySelectorAll( '[data-part="month-link"]' ),
		] );

		$year
			.querySelectorAll( 'month-calendar' )
			.forEach( ( $days, month ) => {
				$links[ month ].textContent = formatMonthName(
					new Date( year, month )
				);
				$links[ month ].href = calendarUrl( {
					scale: 'month',
					year,
					month,
					day: 1,
				} );
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
	 * The days gone by of the month `month`: the level of each, the books whose reading ended then, and its link.
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
						cover: books[ id ].cover,
					} ) ),
				url: calendarUrl( {
					scale: 'day',
					year,
					month,
					day: index + 1,
				} ),
			} ) );
	}

	/**
	 * Fills what the days shown count: the year, the month or the day shown, its readings ended and their pages, and for a
	 * year or a month, its days of reading and the most in a row; none of them, a figure hides.
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

		// A figure of none hides, its name with it; a day counts no days of reading nor any in a row.
		const figure = ( /** @type {number} */ count ) =>
			count ? formatNumber( count ) : '';

		fill( this, {
			period: {
				year: year.toString(),
				month: formatDate( isoDate( year, month, 1 ).slice( 0, 7 ) ),
				day: formatDate( isoDate( year, month, day ), 'full' ),
			}[ scale ],
			readings: figure( ended.length ),
			// The pages read that day, of the books read from a known start, or of the books ended in the year or the month.
			pages: figure(
				isDay
					? pages[ first ] ?? 0
					: ended.reduce(
							( sum, book ) => sum + ( book.pages || 0 ),
							0
					  )
			),
			days: isDay ? '' : figure( shown.filter( Boolean ).length ),
			streak: isDay ? '' : figure( streakOf( shown ) ),
			status: error,
		} );
	}

	/**
	 * Before, as long as a year with a reading comes before, and After, up to the current year, month or day.
	 *
	 * @param {DiaryCalendar}     calendar  The calendar.
	 * @param {HTMLAnchorElement} $previous Before.
	 * @param {HTMLAnchorElement} $next     After.
	 */
	#fillSteps( { scale, year, month, day, years }, $previous, $next ) {
		const today = new Date();
		const firstYear = Math.min( years[ 0 ] ?? year, year );
		const by = ( /** @type {number} */ step ) =>
			new Date(
				year + ( 'year' === scale ? step : 0 ),
				month + ( 'month' === scale ? step : 0 ),
				'day' === scale ? day + step : 1
			);
		const periodOf = ( /** @type {Date} */ date ) => ( {
			scale,
			year: date.getFullYear(),
			month: 'year' === scale ? month : date.getMonth(),
			day: 'day' === scale ? date.getDate() : day,
		} );
		const before = by( -1 );
		const after = by( 1 );
		// The first day of the period after the one shown, the year after starting on January 1st.
		const afterStart =
			'year' === scale ? new Date( year + 1, 0, 1 ) : after;

		lead(
			$previous,
			before.getFullYear() >= firstYear
				? calendarUrl( periodOf( before ) )
				: ''
		);
		lead(
			$next,
			afterStart <= today ? calendarUrl( periodOf( after ) ) : ''
		);
	}
}
customElements.define( 'library-calendar', LibraryCalendarElement );
