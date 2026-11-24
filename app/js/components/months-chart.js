import { __, sprintf } from 'library-app/app';
import { Component } from 'library-app/component';
import {
	formatBooks,
	formatMonthInitial,
	formatMonthName,
	formatNumber,
} from 'library-app/format';

/**
 * The readings of each month of a year, the root of the component months-chart: a bar each, as high as its share of the
 * month read the most, its number above it and the initial of its month under it; assistive technologies read the name
 * of the month and its books.
 *
 * Its API: `year`, the year, then `months`, the readings of each of its months, from January.
 */
export class MonthsChartElement extends Component {
	static properties = [ 'year', 'months' ];

	#year = new Date().getFullYear();

	/** @type {number[]} */
	#months = [];

	/** @return {number} The year. */
	get year() {
		return this.#year;
	}

	/** @param {number} year The year, whose months it names. */
	set year( year ) {
		this.#year = year;
		this.#show();
	}

	/** @return {number[]} The readings of each month. */
	get months() {
		return this.#months;
	}

	/** @param {number[]} months The readings of each month, from January. */
	set months( months ) {
		this.#months = months;
		this.#show();
	}

	/** Draws a bar per month. */
	#show() {
		const most = Math.max( 1, ...this.#months );
		const $month = /** @type {HTMLTemplateElement} */ (
			this.querySelector( 'template' )
		).content.firstElementChild;

		this.querySelector( '[data-part="months"]' ).replaceChildren(
			...this.#months.map( ( readings, month ) => {
				const $item = /** @type {HTMLElement} */ (
					$month.cloneNode( true )
				);
				const date = new Date( this.#year, month );
				const share = readings / most;

				$item.querySelector( '[data-part="count"]' ).textContent =
					formatNumber( readings );
				$item.querySelector( '[data-part="initial"]' ).textContent =
					formatMonthInitial( date );
				$item.querySelector( '[data-part="said"]' ).textContent =
					sprintf(
						/* translators: 1: name of a month, 2: number of books read in it. */
						__( '%1$s: %2$s', 'library' ),
						formatMonthName( date ),
						formatBooks( readings )
					);
				$item.style.setProperty( '--library-share', share.toString() );

				return $item;
			} )
		);
	}
}
customElements.define( 'months-chart', MonthsChartElement );
