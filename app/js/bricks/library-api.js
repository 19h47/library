import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { __, _x, sprintf } from 'library-app/app';
import { pathOf } from 'library-app/api';
import { clone, fill } from 'library-app/render';

/**
 * @import { ApiArg, ApiEndpoint, ApiProperty, ApiRoute, ApiTry } from 'library-app/api'
 */

/**
 * An argument or a property: its name, its type and whether it is required or only read, its description, its rules,
 * then the properties it holds.
 *
 * @param {Object}        item              The item.
 * @param {string}        item.name         Its name.
 * @param {string}        item.type         Its type.
 * @param {string}        item.flag         Whether it is required or only read, or empty.
 * @param {string}        item.description  Its description.
 * @param {string[]}      [item.rules]      Its rules.
 * @param {ApiProperty[]} [item.properties] The properties it holds.
 * @return {HTMLElement} The item.
 */
function renderItem( {
	name,
	type,
	flag,
	description,
	rules = [],
	properties = [],
} ) {
	const $item = clone( 'library-api-item' );
	/** @type {HTMLElement} */
	const $items = $item.querySelector( '[data-list="items"]' );

	fill( $item, {
		name,
		type,
		flag,
		description,
		rules: rules.join( ' · ' ),
	} );
	$items.hidden = ! properties.length;
	$items.replaceChildren( ...properties.map( renderProperty ) );

	return $item;
}

/**
 * An argument of an endpoint.
 *
 * @param {ApiArg} arg The argument.
 * @return {HTMLElement} The item.
 */
function renderArg( arg ) {
	return renderItem( {
		...arg,
		flag: arg.required ? __( 'required', 'library' ) : '',
	} );
}

/**
 * A property of what a route gives, with the ones it holds.
 *
 * @param {ApiProperty} property The property.
 * @return {HTMLElement} The item.
 */
function renderProperty( property ) {
	return renderItem( {
		...property,
		flag: property.readonly ? __( 'read only', 'library' ) : '',
	} );
}

/**
 * A field of the request to try: an argument of the path, required, or of the query.
 *
 * @param {string}  id       Its ID.
 * @param {string}  name     The name of the argument.
 * @param {boolean} required Whether it is required.
 * @return {HTMLElement} The field.
 */
function renderField( id, name, required ) {
	const $field = clone( 'library-api-field' );
	const $label = $field.querySelector( 'label' );
	const $input = $field.querySelector( 'input' );

	$label.htmlFor = id;
	$label.textContent = required
		? /* translators: %s: label of a field that must be filled in, e.g. Title. */
		  sprintf( _x( '%s (required)', 'one field', 'library' ), name )
		: name;
	$input.id = id;
	$input.name = name;
	$input.required = required;

	return $field;
}

/**
 * The reference of the API: its routes, folded, each with its endpoints, their arguments, what it gives when its schema
 * says it, and a GET request to try, with the arguments of its path and of its query, whose answer shows under it.
 */
class LibraryApiElement extends Brick {
	/**
	 * The route of each request to try, by the key of its endpoint.
	 *
	 * @type {Map<string, ApiRoute>}
	 */
	#routes = new Map();

	connect( signal ) {
		/** @type {HTMLElement} */
		const $routes = this.querySelector( '[data-list="routes"]' );

		this.addEventListener(
			'submit',
			( event ) => {
				const $form = /** @type {HTMLFormElement} */ ( event.target );
				const { key } = /** @type {HTMLElement} */ (
					$form.closest( '[data-key]' )
				).dataset;
				const route = this.#routes.get( key );
				const values = Object.fromEntries(
					[ ...new FormData( $form ) ].map( ( [ name, value ] ) => [
						name,
						String( value ).trim(),
					] )
				);

				event.preventDefault();
				store.tryRoute( key, pathOf( route, values ) );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.reference,
				( { loading, error } ) => {
					fill( this, { status: error } );
					$routes.setAttribute( 'aria-busy', String( loading ) );
				}
			),
			store.select(
				( state ) => state.reference.routes,
				( routes ) => {
					this.#routes.clear();
					$routes.replaceChildren(
						...routes.map( ( route, index ) =>
							this.#renderRoute( route, String( index ) )
						)
					);
				}
			),
			store.select(
				( state ) => state.reference.tries,
				( tries ) =>
					Object.entries( tries ).forEach( ( [ key, tried ] ) =>
						this.#renderTry( key, tried )
					)
			),
		];
	}

	/**
	 * A route, folded: its methods and its path, then its endpoints and what it gives.
	 *
	 * @param {ApiRoute} route The route.
	 * @param {string}   key   Its key.
	 * @return {HTMLElement} The route.
	 */
	#renderRoute( route, key ) {
		const $route = clone( 'library-api-route' );
		/** @type {HTMLElement} */
		const $properties = $route.querySelector( '[data-row="properties"]' );
		const methods = [
			...new Set(
				route.endpoints.flatMap( ( { methods: own } ) => own )
			),
		];

		fill( $route, { methods: methods.join( ' ' ), path: route.path } );
		$route
			.querySelector( '[data-list="endpoints"]' )
			.replaceChildren(
				...route.endpoints.map( ( endpoint, index ) =>
					this.#renderEndpoint(
						route,
						endpoint,
						`${ key }-${ index }`
					)
				)
			);
		$properties.hidden = ! route.properties.length;
		$properties
			.querySelector( '[data-list="properties"]' )
			.replaceChildren( ...route.properties.map( renderProperty ) );

		return $route;
	}

	/**
	 * An endpoint: its methods, its arguments, and the request to try for a GET.
	 *
	 * @param {ApiRoute}    route    Its route.
	 * @param {ApiEndpoint} endpoint The endpoint.
	 * @param {string}      key      Its key.
	 * @return {HTMLElement} The endpoint.
	 */
	#renderEndpoint( route, endpoint, key ) {
		const $endpoint = clone( 'library-api-endpoint' );
		/** @type {HTMLElement} */
		const $none = $endpoint.querySelector( '[data-row="none"]' );
		/** @type {HTMLElement} */
		const $try = $endpoint.querySelector( '[data-row="try"]' );
		const id = ( name ) => `library-api-${ key }-${ name }`;

		$endpoint.dataset.key = key;
		// The summary of the route says the methods of its only endpoint.
		fill( $endpoint, {
			methods:
				route.endpoints.length > 1 ? endpoint.methods.join( ' ' ) : '',
		} );
		$endpoint
			.querySelector( '[data-list="args"]' )
			.replaceChildren( ...endpoint.args.map( renderArg ) );
		$none.hidden = Boolean( endpoint.args.length );

		if ( endpoint.methods.includes( 'GET' ) ) {
			this.#routes.set( key, route );
			$try.hidden = false;
			$try.querySelector( '[data-list="fields"]' ).replaceChildren(
				...route.params.map( ( name ) =>
					renderField( id( name ), name, true )
				),
				...endpoint.args.map( ( { name, required } ) =>
					renderField( id( name ), name, required )
				)
			);
		}

		return $endpoint;
	}

	/**
	 * The answer of a request tried: its status, the number of items of a collection, its path, then its body.
	 *
	 * @param {string}                      key   The key of its endpoint.
	 * @param {ApiTry & {loading: boolean}} tried The request tried.
	 */
	#renderTry( key, tried ) {
		const $endpoint = this.querySelector( `[data-key="${ key }"]` );

		if ( ! $endpoint ) {
			return;
		}

		/** @type {HTMLElement} */
		const $answer = $endpoint.querySelector( '[data-row="answer"]' );
		const status = tried.status
			? String( tried.status )
			: __( 'No answer', 'library' );
		const total = tried.total
			? /* translators: %s: number of items of a collection the API gives. */
			  sprintf( __( '%s items', 'library' ), tried.total )
			: '';

		$answer.hidden = false;
		$answer.setAttribute( 'aria-busy', String( tried.loading ) );
		$answer.querySelector( '[data-field="summary"]' ).textContent =
			tried.loading
				? __( 'Loading…', 'library' )
				: [ status, total, tried.path ].filter( Boolean ).join( ' · ' );
		$answer.querySelector( '[data-field="body"]' ).textContent =
			tried.body ?? '';
	}
}
customElements.define( 'library-api', LibraryApiElement );
