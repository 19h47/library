/**
 * Observable state: the base of the store of the reading app.
 *
 * @package    Library
 * @subpackage Library/app
 */

/**
 * Observable state, as @agencecinq/store, but for its listeners: they are called once the task that changed the
 * state is done, with the last state, rather than at each change. An action that changes several parts of the state,
 * such as the view and the book shown, reaches the bricks as one change, and a brick reads a state the others have drawn.
 */
export class Store {
	#state;
	// The state the listeners were last called with.
	#notified;
	#pending = false;
	#listeners = new Set();

	constructor( initial ) {
		this.#state = initial;
		this.#notified = initial;
	}

	get() {
		return this.#state;
	}

	/** A function is called with the current state and returns the next one. */
	set( update ) {
		const next = typeof update === 'function' ? update( this.#state ) : update;

		if ( Object.is( next, this.#state ) ) {
			return;
		}

		this.#state = next;

		if ( ! this.#pending ) {
			this.#pending = true;
			queueMicrotask( () => this.#notify() );
		}
	}

	/** Calls the listeners with the state, and the one they were last called with; a listener that changes it calls them again after. */
	#notify() {
		const previous = this.#notified;
		const next = this.#state;

		this.#pending = false;
		this.#notified = next;

		if ( Object.is( next, previous ) ) {
			return;
		}

		this.#listeners.forEach( ( listener ) => {
			try {
				listener( next, previous );
			} catch ( error ) {
				reportError( error );
			}
		} );
	}

	/** Called once per change of a task, not right away. Returns the unsubscribe function. */
	subscribe( listener ) {
		this.#listeners.add( listener );

		return () => {
			this.#listeners.delete( listener );
		};
	}

	/**
	 * Called right away with the selected slice, then only when it
	 * changes. Returns the unsubscribe function.
	 */
	select( selector, listener ) {
		let value = selector( this.#state );

		listener( value );

		return this.subscribe( ( state ) => {
			const next = selector( state );

			if ( Object.is( next, value ) ) {
				return;
			}

			value = next;
			listener( next );
		} );
	}
}
