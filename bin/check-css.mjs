/**
 * Checks the stylesheets of the reading app against the rules of its design system (see DESIGN-SYSTEM.md):
 *
 * - a size is a token or a step of the scale, never a length written as it is; a length is allowed where it is none of
 *   the sizes of the scale: a line of 1 to 3 pixels, a length in em, which follows its text, a percentage or a length of
 *   a container, the whole or the half of the screen, and a length in rem in a media or container query, which takes no
 *   custom property, or in the definition of a token;
 * - a color is a token, written as it is in the definition of a token only;
 * - a component carries neither a margin nor a width of its own: in the sheets of the components, a rule that reaches
 *   the component itself, a selector without a combinator, sets no margin and no width, but 0, auto, a width of its
 *   content, the whole of its place, or one a custom property gives; a rule that reaches a part of a place, such as
 *   `library-view > .app-controls`, is the place spacing what it holds.
 *
 * A declaration that is an exception says why on its line, in a comment that starts with "Exception": an atom of a size
 * of its own, a layer fixed to the screen, a strip going out to the edges of the screen. The shared base, app-style.css,
 * is checked where it is written, the gist.
 *
 * Run from anywhere: node bin/check-css.mjs. It prints each declaration off the rules, with its file and its line, and
 * fails if it finds one.
 */

import { readFileSync, readdirSync } from 'node:fs';
import { join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = fileURLToPath( new URL( '..', import.meta.url ) );
const folder = join( root, 'app/css' );
// The file of the tokens of the app: its custom properties are the scale and the colors.
const tokens = join( folder, 'library-app.css' );
const components = join( folder, 'components' ) + sep;
const LENGTH =
	/(?<![\w.#-])(\d*\.?\d+)(px|rem|ch|vw|vh|dvh|svh|lvh|vmin|vmax)\b/g;
const COLOR =
	/#[0-9a-f]{3,8}\b|\b(?:rgba?|hsla?|hwb|oklch|oklab|lab|lch|color)\(/i;
const MARGIN = /^margin(-[a-z-]+)?$/;
const WIDTH = /^(min-|max-)?(width|inline-size)$/;
const WIDTHS = [
	'0',
	'100%',
	'auto',
	'none',
	'min-content',
	'max-content',
	'fit-content',
];

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
function isAllowedLength( value, unit, isQuery, isToken ) {
	if ( 'px' === unit ) {
		return value <= 3;
	}

	if ( [ 'rem', 'ch' ].includes( unit ) ) {
		return isQuery || isToken;
	}

	return 100 === value || 50 === value;
}

/**
 * Whether a selector reaches an element by itself, without a combinator, in one of the selectors of its list at least:
 * the component itself, rather than a part of a place. What is between parentheses or brackets is not a combinator.
 *
 * @param {string} selector The selector, its nesting resolved.
 * @return {boolean} Whether it reaches an element by itself.
 */
function isOwn( selector ) {
	let flat = selector;

	// The innermost groups first, until none is left.
	while ( /\([^()]*\)|\[[^[\]]*\]/.test( flat ) ) {
		flat = flat.replace( /\([^()]*\)|\[[^[\]]*\]/g, '' );
	}

	return flat
		.split( ',' )
		.some( ( part ) => ! /[\s>+~]/.test( part.trim() ) );
}

/**
 * The selector of the rule a declaration is in: the innermost one, a nested one, with `&`, resolved against its parent;
 * at-rules, such as a layer or a media query, are no selectors.
 *
 * @param {string[]} stack The preludes of the blocks the declaration is in, from the outermost.
 * @return {string} The selector, or empty out of a rule.
 */
function selectorOf( stack ) {
	return stack
		.filter( ( prelude ) => ! prelude.startsWith( '@' ) )
		.reduce(
			( parent, prelude ) =>
				prelude.includes( '&' )
					? prelude.replaceAll( '&', parent )
					: prelude,
			''
		);
}

const errors = sheetsOf( folder ).flatMap( ( file ) => {
	const raw = readFileSync( file, 'utf8' ).split( '\n' );
	// Without its comments, their lines kept, so that a line says where a declaration is.
	const lines = raw
		.join( '\n' )
		.replace( /\/\*[\s\S]*?\*\//g, ( comment ) =>
			comment.replace( /[^\n]/g, '' )
		)
		.split( '\n' );
	const isComponent = file.startsWith( components );
	const stack = [];
	let prelude = '';
	// The property of the declaration a line is in, which may go on over several lines.
	let property = '';

	return lines.flatMap( ( line, index ) => {
		const found = [];
		const where = `${ relative( root, file ) }:${ index + 1 }`;
		const isException = /\/\*\s*Exception\b/.test( raw[ index ] );
		const isQuery = /^\s*@(media|container)\b/.test( line );
		const declaration = line.match(
			/^\s*(--[\w-]+|[a-z-]+)\s*:(?!:)(.*)$/
		);

		if ( declaration && ! line.includes( '{' ) ) {
			property = declaration[ 1 ];
		}

		const isToken = file === tokens && property.startsWith( '--' );

		[ ...line.matchAll( LENGTH ) ]
			.filter(
				( [ , value, unit ] ) =>
					! isAllowedLength( Number( value ), unit, isQuery, isToken )
			)
			.forEach( ( [ length ] ) =>
				found.push(
					`${ where }: ${ length }, off the scale: a token, a step of the scale, or a length in em`
				)
			);

		if ( property && ! isToken && COLOR.test( line ) ) {
			found.push(
				`${ where }: a color written as it is: a token, defined in library-app.css`
			);
		}

		if ( isComponent && declaration && ! isException ) {
			const value = declaration[ 2 ].replace( /;\s*$/, '' ).trim();
			const isOwnRule = isOwn( selectorOf( stack ) );
			const isMargin =
				MARGIN.test( declaration[ 1 ] ) &&
				! value
					.split( /\s+/ )
					.every( ( part ) => /^(0|auto)$/.test( part ) );
			const isWidth =
				WIDTH.test( declaration[ 1 ] ) &&
				! WIDTHS.includes( value ) &&
				! value.startsWith( 'var(--' );

			if ( isOwnRule && ( isMargin || isWidth ) ) {
				found.push(
					`${ where }: ${ declaration[ 1 ] } of the component itself: its place spaces it and sizes it, or say why on the line, /* Exception, … */`
				);
			}
		}

		// The blocks the next lines are in: a prelude opens one, a closing brace ends the innermost.
		for ( const character of line ) {
			if ( '{' === character ) {
				stack.push( prelude.trim() );
				prelude = '';
			} else if ( '}' === character ) {
				stack.pop();
				prelude = '';
			} else if ( ';' !== character ) {
				prelude += character;
			} else {
				prelude = '';
			}
		}

		// A selector may go on over several lines; a declaration ends at its semicolon.
		prelude += ' ';

		if ( /[;{}]/.test( line ) ) {
			property = '';
			prelude = '';
		}

		return found;
	} );
} );

if ( errors.length ) {
	process.stderr.write( `${ errors.join( '\n' ) }\n` );
	process.exitCode = 1;
}
