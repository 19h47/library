/**
 * The reference of the API, for development: the routes of the plugin and those of core the app reads, as the API
 * describes them, and a request tried from it, its answer as it comes.
 */

import { addQueryArgs, apiFetch } from 'library-app/app';
import { errorMessage } from 'library-app/api';

/**
 * An argument of an endpoint, as the API describes it: its type, whether it is required, and its rules, such as its
 * default or its values, in plain text.
 *
 * @typedef {Object} ApiArg
 * @property {string}        name        Its name.
 * @property {string}        type        Its type, such as "integer" or "string[]".
 * @property {boolean}       required    Whether it is required.
 * @property {string}        description Its description, or empty.
 * @property {string[]}      rules       Its rules, such as "default: 1".
 * @property {ApiProperty[]} properties  The properties it holds, such as those of a person of a book.
 */

/**
 * A property of what a route gives, as its schema describes it, with the ones it holds, such as the meta of a book.
 *
 * @typedef {Object} ApiProperty
 * @property {string}        name        Its name.
 * @property {string}        type        Its type.
 * @property {string}        description Its description, or empty.
 * @property {boolean}       readonly    Whether it is only read.
 * @property {string[]}      rules       Its rules, such as its values.
 * @property {ApiProperty[]} properties  The properties it holds.
 */

/**
 * An endpoint of a route: its methods and its arguments, those of the path apart.
 *
 * @typedef {Object} ApiEndpoint
 * @property {string[]} methods Its methods, such as GET.
 * @property {ApiArg[]} args    Its arguments.
 */

/**
 * A route of the API, as its reference shows it.
 *
 * @typedef {Object} ApiRoute
 * @property {string}        pattern    Its pattern, as the API registers it: /library/v1/series/(?P<id>\d+).
 * @property {string}        path       Its path, its arguments in braces: /library/v1/series/{id}.
 * @property {string[]}      params     The arguments of its path, such as id.
 * @property {ApiEndpoint[]} endpoints  Its endpoints.
 * @property {ApiProperty[]} properties What it gives, when its schema says it.
 */

/**
 * A request tried from the reference of the API, and its answer.
 *
 * @typedef {Object} ApiTry
 * @property {string} path   The path asked for.
 * @property {number} status The status of the answer, 0 without one.
 * @property {string} total  The number of items of a collection, from X-WP-Total, or empty.
 * @property {string} body   The answer, as indented JSON, or why it failed.
 */

// The namespace of the plugin, whose routes the reference gives all.
const NAMESPACE = '/library/v1';
// The routes of core the app reads, by their base: the reference gives what they take to read and what they give.
const CORE_ROUTES = [
	'books',
	'people',
	'publishers',
	'series',
	'languages',
	'forms',
];
// The arguments of a path: (?P<id>\d+).
const PATH_PARAM = /\(\?P<(\w+)>[^)]*\)/g;
// What a request is asked with besides its methods, and what the API answers itself.
const HIDDEN_METHODS = [ 'OPTIONS', 'HEAD' ];

/**
 * The type of a value of a schema: its types, a list as the type of its items followed by []; a schema named, such as
 * the one of a person of a book, by its title: library-book-person[].
 *
 * @param {Object} schema A schema.
 * @return {string} The type, or empty.
 */
function typeOf( schema ) {
	if ( schema.title ) {
		return schema.title;
	}

	const types = [ schema.type ?? [] ].flat();

	return types
		.map( ( type ) =>
			'array' === type && schema.items
				? `${ typeOf( schema.items ) || 'mixed' }[]`
				: type
		)
		.join( ' | ' );
}

/**
 * The rules of an argument, in plain text: its default, its values, its limits, its pattern.
 *
 * @param {Object} arg An argument, as the API describes it.
 * @return {string[]} The rules.
 */
function rulesOf( arg ) {
	const value = ( item ) => JSON.stringify( item );
	const isEmpty =
		undefined === arg.default ||
		'' === arg.default ||
		( Array.isArray( arg.default ) && ! arg.default.length ) ||
		( arg.default &&
			'object' === typeof arg.default &&
			! Object.keys( arg.default ).length );
	const values = arg.enum ?? arg.items?.enum;

	return [
		! isEmpty && `default: ${ value( arg.default ) }`,
		values && `values: ${ values.join( ', ' ) }`,
		undefined !== arg.minimum && `minimum: ${ arg.minimum }`,
		undefined !== arg.maximum && `maximum: ${ arg.maximum }`,
		arg.format && `format: ${ arg.format }`,
		( arg.pattern ?? arg.items?.pattern ) &&
			`pattern: ${ arg.pattern ?? arg.items.pattern }`,
	].filter( Boolean );
}

/**
 * The properties of a schema, in its order, each with the ones it holds.
 *
 * @param {Object} schema A schema.
 * @return {ApiProperty[]} The properties.
 */
function propertiesOf( schema ) {
	return Object.entries( schema?.properties ?? {} ).map(
		( [ name, property ] ) => ( {
			name,
			type: typeOf( property ),
			description: property.description ?? '',
			readonly: Boolean( property.readonly ),
			rules: rulesOf( property ),
			properties: propertiesOf( property.items ?? property ),
		} )
	);
}

/**
 * A route of the API as its reference shows it: its endpoints with `methods`, the arguments of its path apart.
 *
 * @param {string}   pattern The pattern of the route.
 * @param {Object}   route   The route, as the API describes it.
 * @param {string[]} methods The methods to show, all if empty.
 * @return {ApiRoute} The route.
 */
function toRoute( pattern, route, methods = [] ) {
	const params = [ ...pattern.matchAll( PATH_PARAM ) ].map(
		( [ , name ] ) => name
	);

	return {
		pattern,
		path: pattern.replace( PATH_PARAM, '{$1}' ),
		params,
		endpoints: route.endpoints
			.map( ( endpoint ) => ( {
				methods: endpoint.methods.filter(
					( method ) =>
						! HIDDEN_METHODS.includes( method ) &&
						( ! methods.length || methods.includes( method ) )
				),
				args: Object.entries( endpoint.args )
					.filter( ( [ name ] ) => ! params.includes( name ) )
					.map( ( [ name, arg ] ) => ( {
						name,
						type: typeOf( arg ),
						required: Boolean( arg.required ),
						description: arg.description ?? '',
						rules: rulesOf( arg ),
						properties: propertiesOf( arg.items ?? arg ),
					} ) ),
			} ) )
			.filter( ( endpoint ) => endpoint.methods.length ),
		properties: propertiesOf( route.schema ),
	};
}

/**
 * The reference of the API, as the API describes itself: every route of the plugin, then the routes of core the app reads,
 * with what they take to read and what they give. The index of the namespace asked for help gives the schemas of the
 * routes that have one; a route of core asked with OPTIONS gives its own.
 *
 * @param {Object}      [options]        Options.
 * @param {AbortSignal} [options.signal] Cancels it.
 * @return {Promise<ApiRoute[]>} The routes.
 */
export async function getReference( { signal } = {} ) {
	const [ index, ...core ] = await Promise.all( [
		apiFetch( {
			path: addQueryArgs( NAMESPACE, { context: 'help' } ),
			signal,
		} ),
		...CORE_ROUTES.map( ( base ) =>
			apiFetch( { path: `/wp/v2/${ base }`, method: 'OPTIONS', signal } )
		),
	] );

	return [
		...Object.entries( index.routes )
			.filter( ( [ pattern ] ) => NAMESPACE !== pattern )
			.map( ( [ pattern, route ] ) => toRoute( pattern, route ) ),
		...core.map( ( route, position ) =>
			toRoute( `/wp/v2/${ CORE_ROUTES[ position ] }`, route, [ 'GET' ] )
		),
	];
}

/**
 * The path of a request to a route: the arguments of its path in their place, then those of its query given.
 *
 * @param {ApiRoute}               route  The route.
 * @param {Object<string, string>} values The values of its arguments, by name, empty for one not given.
 * @return {string} The path.
 */
export function pathOf( route, values ) {
	const path = route.pattern.replace( PATH_PARAM, ( match, name ) =>
		encodeURIComponent( values[ name ] ?? '' )
	);
	const query = Object.fromEntries(
		Object.entries( values ).filter(
			( [ name, value ] ) => value && ! route.params.includes( name )
		)
	);

	return addQueryArgs( path, query );
}

/**
 * A GET request tried from the reference of the API: its status, the number of items of a collection, and its answer as
 * indented JSON, an error of the API among them.
 *
 * @param {string} path The path, with its arguments.
 * @return {Promise<ApiTry>} The answer.
 */
export async function tryRoute( path ) {
	/** @type {Response} */
	let response;

	try {
		response = await apiFetch( { path, parse: false } );
	} catch ( error ) {
		if ( ! ( error instanceof Response ) ) {
			return { path, status: 0, total: '', body: errorMessage( error ) };
		}

		response = error;
	}

	const body = await response.text();
	let indented = body;

	try {
		indented = JSON.stringify( JSON.parse( body ), null, 2 );
	} catch {
		// Not JSON: the answer as it came.
	}

	return {
		path,
		status: response.status,
		total: response.headers.get( 'X-WP-Total' ) ?? '',
		body: indented,
	};
}
