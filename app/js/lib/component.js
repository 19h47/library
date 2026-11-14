/**
 * The base of the components: custom elements that are the root of a component of app/components, its behavior and its
 * API, its attributes, its properties, its events and the fields it gives its form.
 *
 * A component reads no store and knows no element but its own: a place plugs it in, or out, through its API alone, as
 * a package. A brick, of library-app/brick, is the other kind, an element of the app that reads the store.
 */

/** A component: `connect()` adds its listeners with `signal`, which aborts as it leaves the page. */
export class Component extends HTMLElement {
	/**
	 * The properties of its API, which a place may set before the component is defined, such as on a row cloned from a
	 * template: an own property then, which would hide its accessor.
	 *
	 * @type {string[]}
	 */
	static properties = [];

	/** @type {AbortController|null} */
	#controller = null;

	connectedCallback() {
		const { properties } = /** @type {typeof Component} */ (
			this.constructor
		);

		// A value set before the component was defined goes through its accessor.
		properties
			.filter( ( name ) => Object.hasOwn( this, name ) )
			.forEach( ( name ) => {
				const value = Reflect.get( this, name );

				Reflect.deleteProperty( this, name );
				Reflect.set( this, name, value );
			} );

		this.#controller = new AbortController();
		this.connect( this.#controller.signal );
	}

	disconnectedCallback() {
		this.#controller?.abort();
	}

	/**
	 * What the component does as it joins the page, its listeners added with `signal`, which aborts as it leaves.
	 *
	 * @type {( signal: AbortSignal ) => void}
	 */
	connect() {}
}
