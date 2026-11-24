import { Component } from 'library-app/component';
import { formatNumber } from 'library-app/format';

// How long it counts up to its value, slowing down to it.
const COUNT_MS = 1000;
const reducedMotion = matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * A figure that counts up to its value, the root of the component count-up: it shows 0, then counts up once it is whole
 * on the screen, slowing down to its value, while assistive technologies read its value at once; without motion, or for
 * 0, its value at once. A value set again, the same, stays as it is.
 *
 * Its API: `value`, its number.
 */
export class CountUpElement extends Component {
	static properties = [ 'value' ];

	/** @type {number|null} */
	#value = null;

	// Whether it waits to count up, until it is whole on the screen.
	#isWaiting = false;

	#frame = 0;

	/** @type {IntersectionObserver|null} */
	#observer = null;

	/** @return {number|null} Its number, none until set. */
	get value() {
		return this.#value;
	}

	/** @param {number} value Its number. */
	set value( value ) {
		if ( value === this.#value ) {
			return;
		}

		const isAtOnce = reducedMotion.matches || ! value;

		this.#value = value;
		cancelAnimationFrame( this.#frame );
		this.querySelector( '[data-part="said"]' ).textContent =
			formatNumber( value );
		this.#shown.textContent = formatNumber( isAtOnce ? value : 0 );
		this.#isWaiting = ! isAtOnce;
		this.#observe();
	}

	/** @return {HTMLElement} What it shows, out of the reach of assistive technologies. */
	get #shown() {
		return this.querySelector( '[data-part="shown"]' );
	}

	connect( signal ) {
		this.#observer = new IntersectionObserver(
			( entries ) => {
				if ( entries.at( -1 ).isIntersecting ) {
					this.#countUp();
				}
			},
			{ threshold: 1 }
		);
		signal.addEventListener( 'abort', () => {
			this.#observer.disconnect();
			this.#observer = null;
			cancelAnimationFrame( this.#frame );
		} );
		this.#observe();
	}

	/** Watches for it to be whole on the screen, while it waits to count up. */
	#observe() {
		if ( this.#isWaiting ) {
			this.#observer?.observe( this );
		}
	}

	/** Counts up to its value, slowing down to it. */
	#countUp() {
		const value = this.#value;
		const start = performance.now();
		/** @param {number} now The time of the frame. */
		const step = ( now ) => {
			const progress = Math.min( 1, ( now - start ) / COUNT_MS );
			const eased = 1 - ( 1 - progress ) ** 3;

			this.#shown.textContent = formatNumber(
				Math.round( value * eased )
			);

			if ( progress < 1 ) {
				this.#frame = requestAnimationFrame( step );
			}
		};

		this.#isWaiting = false;
		this.#observer.unobserve( this );
		this.#frame = requestAnimationFrame( step );
	}
}
customElements.define( 'count-up', CountUpElement );
