import { Component } from 'library-app/component';
import { formatBooks } from 'library-app/format';

/**
 * A share of a chart: its name, a text or a link to its page, and its books.
 *
 * @typedef {Object} Share
 * @property {string|Node} name     Its name.
 * @property {number}      readings Its books.
 */

/**
 * The shares of a chart, the root of the component shares-chart: a line each, its name, its books, then a bar as long as
 * its share of the first one, the most read first.
 *
 * Its API: `shares`, its lines, in their order.
 */
export class SharesChartElement extends Component {
	static properties = [ 'shares' ];

	/** @type {Share[]} */
	#shares = [];

	/** @return {Share[]} Its lines. */
	get shares() {
		return this.#shares;
	}

	/** @param {Share[]} shares Its lines, in their order. */
	set shares( shares ) {
		const most = Math.max(
			1,
			...shares.map( ( share ) => share.readings )
		);
		const $line = /** @type {HTMLTemplateElement} */ (
			this.querySelector( 'template' )
		).content.firstElementChild;

		this.#shares = shares;
		this.querySelector( '[data-part="lines"]' ).replaceChildren(
			...shares.map( ( { name, readings } ) => {
				const $item = /** @type {HTMLElement} */ (
					$line.cloneNode( true )
				);
				const share = readings / most;

				$item.querySelector( '[data-part="name"]' ).append( name );
				$item.querySelector( '[data-part="count"]' ).textContent =
					formatBooks( readings );
				$item.style.setProperty( '--library-share', share.toString() );

				return $item;
			} )
		);
	}
}
customElements.define( 'shares-chart', SharesChartElement );
