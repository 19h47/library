/**
 * Observable state: the base of the store of the reading app.
 */

/**
 * Observable state, as `@agencecinq/store`, but for its listeners: they are called once the task that changed the
 * state is done, with the last state, rather than at each change. An action that changes several parts of the state,
 * such as the view and the book shown, reaches the bricks as one change, and a brick reads a state the others have drawn.
 *
 * @template S
 */
export class Store {
	/** @type {S} */
	#state;
	/**
	 * The state the listeners were last called with.
	 *
	 * @type {S}
	 */
	#notified;
	#pending = false;
	/** @type {Set<( state: S, previous: S ) => void>} */
	#listeners = new Set();

	/**
	 * @param {S} initial The state to start with.
	 */
	constructor( initial ) {
		this.#state = initial;
		this.#notified = initial;
	}

	/**
	 * The current state.
	 *
	 * @return {S} The state.
	 */
	get() {
		return this.#state;
	}

	/**
	 * A function is called with the current state and returns the next one.
	 *
	 * @param {S | (( state: S ) => S)} update The next state, or the function that returns it.
	 */
	set( update ) {
		const next =
			typeof update === 'function'
				? /** @type {( state: S ) => S} */ ( update )( this.#state )
				: update;

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

	/**
	 * Called once per change of a task, not right away. Returns the unsubscribe function.
	 *
	 * @param {( state: S, previous: S ) => void} listener Called with the state, and the one it was last called with.
	 * @return {() => void} The unsubscribe function.
	 */
	subscribe( listener ) {
		this.#listeners.add( listener );

		return () => {
			this.#listeners.delete( listener );
		};
	}

	/**
	 * Called right away with the selected slice, then only when it
	 * changes. Returns the unsubscribe function.
	 *
	 * @template T
	 * @param {( state: S ) => T}    selector Selects a slice of the state.
	 * @param {( value: T ) => void} listener Called with the slice.
	 * @return {() => void} The unsubscribe function.
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
