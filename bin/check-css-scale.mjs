/**
 * Checks that the stylesheets of the reading app keep to the scale: a size is a token, --app-space-s or --app-text-l, or
 * a step of the scale, --app-u2, never a length written as it is. A length is allowed where it is none of the sizes of
 * the scale: a line of 1 to 3 pixels, a length in em, which follows its text, a percentage or a length of a container,
 * the whole or the half of the screen, and a length in rem in a media or container query, which takes no custom
 * property, or in the definition of a token. The shared base, app-style.css, is checked where it is written, the gist.
 *
 * Run from anywhere: node bin/check-css-scale.mjs. It prints each length off the scale, with its file and its line, and
 * fails if it finds one.
 */

import { readFileSync, readdirSync } from 'node:fs';
import { join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '..', import.meta.url ) );
const folder = join( root, 'app/css' );
// The file of the tokens of the app: its custom properties are the scale, in rem as its base.
const tokens = join( folder, 'library-app.css' );
const LENGTH = /(?<![\w.#-])(\d*\.?\d+)(px|rem|ch|vw|vh|dvh|svh|lvh|vmin|vmax)\b/g;
const QUERY = /^\s*@(media|container)\b/;
const TOKEN = /^\s*--[\w-]+\s*:/;

/**
 * The stylesheets of a folder and of the folders in it, but the shared base.
 *
 * @param {string} path The folder.
 * @return {string[]} Their paths.
 */
function sheetsOf( path ) {
	return readdirSync( path, { withFileTypes: true } ).flatMap( ( entry ) => {
		const file = join( path, entry.name );

		if ( entry.isDirectory() ) {
			return sheetsOf( file );
		}

		return entry.name.endsWith( '.css' ) && 'app-style.css' !== entry.name
			? [ file ]
			: [];
	} );
}

/**
 * Whether a length is allowed where it is written.
 *
 * @param {number}  value   Its number.
 * @param {string}  unit    Its unit.
 * @param {boolean} isQuery Whether it is in a media or container query.
 * @param {boolean} isToken Whether it defines a token.
 * @return {boolean} Whether it is allowed.
 */
function isAllowed( value, unit, isQuery, isToken ) {
	if ( 'px' === unit ) {
		return value <= 3;
	}

	if ( [ 'rem', 'ch' ].includes( unit ) ) {
		return isQuery || isToken;
	}

	return 100 === value || 50 === value;
}

const errors = sheetsOf( folder ).flatMap( ( file ) => {
	// Without its comments, their lines kept, so that a line says where a length is.
	const lines = readFileSync( file, 'utf8' )
		.replace( /\/\*[\s\S]*?\*\//g, ( comment ) =>
			comment.replace( /[^\n]/g, '' )
		)
		.split( '\n' );

	return lines.flatMap( ( line, index ) => {
		const isQuery = QUERY.test( line );
		const isToken = file === tokens && TOKEN.test( line );

		return [ ...line.matchAll( LENGTH ) ]
			.filter(
				( [ , value, unit ] ) =>
					! isAllowed( Number( value ), unit, isQuery, isToken )
			)
			.map(
				( [ length ] ) =>
					`${ relative( root, file ) }:${
						index + 1
					}: ${ length }, off the scale: a token, a step of the scale, or a length in em`
			);
	} );
} );

if ( errors.length ) {
	console.error( errors.join( '\n' ) );
	process.exitCode = 1;
}
