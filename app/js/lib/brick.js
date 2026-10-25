/**
 * The base of the bricks: custom elements that read the store with select() and change it through its actions.
 *
 * No brick knows another, so removing one leaves the others working. Text from the API is set with
 * textContent, in clones of the templates of the page, which hold the translated labels.
 *
 * @package    Library
 * @subpackage Library/app
 */

/** A brick: `connect()` returns what to subscribe, all unsubscribed when the brick leaves the page. */
export class Brick extends HTMLElement {
	#controller = null;

	connectedCallback() {
		this.#controller = new AbortController();

		const { signal } = this.#controller;

		// select() returns the unsubscribe function: abort() calls it.
		this.connect( signal ).forEach( ( unsubscribe ) => signal.addEventListener( 'abort', unsubscribe ) );
	}

	disconnectedCallback() {
		this.#controller?.abort();
	}
}
