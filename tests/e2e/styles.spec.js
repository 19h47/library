/**
 * The styles of the components, as the styleguide shows them, filled with its own samples: the computed styles of each of
 * its elements, on a phone and on a wide screen, in both themes, against the ones written in tests/e2e/styles/styleguide.txt.
 * A change of the styles that changes no component changes none of them; one that does says which, and how.
 *
 * The file holds each set of styles once, under a hash of it, then each element with the hash of its styles: a token
 * changed changes a line of the styles, rather than every element that takes it.
 *
 * What a style changes on purpose is written again, and the diff of the file shows it in the pull request:
 * UPDATE_STYLES=1 pnpm test:e2e styles.
 *
 * Only what the styles set is compared, not what the layout makes of it, such as a width in pixels, which depends on the
 * fonts of the system; a number to two decimals.
 */

import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { openApp, waitForIdle } from './library.js';

const FILE = new URL( './styles/styleguide.txt', import.meta.url );
const PROPERTIES = [
	'display',
	'font-family',
	'font-size',
	'font-weight',
	'line-height',
	'letter-spacing',
	'text-transform',
	'text-decoration-line',
	'color',
	'background-color',
	'border-top-width',
	'border-top-color',
	'border-top-left-radius',
	'margin-top',
	'margin-right',
	'margin-bottom',
	'margin-left',
	'padding-top',
	'padding-right',
	'padding-bottom',
	'padding-left',
	'row-gap',
	'column-gap',
	'box-shadow',
	'opacity',
];
// The screens and the themes the styles are read in.
const PASSES = [
	{ name: 'phone light', width: 390, colorScheme: 'light' },
	{ name: 'phone dark', width: 390, colorScheme: 'dark' },
	{ name: 'wide light', width: 1280, colorScheme: 'light' },
];

/**
 * The computed styles of each element of the styleguide, by its sample and its place in it: the name of its sample, then
 * the path from it, so that a sample added or moved changes the keys of none of the others.
 *
 * @param {import('@playwright/test').Page} page       The page of the styleguide.
 * @param {string[]}                        properties The properties.
 * @return {Promise<Array<[string, string]>>} The key and the styles of each element.
 */
function readStyles( page, properties ) {
	return page.evaluate( ( names ) => {
		const $styleguide = document.querySelector( 'library-styleguide' );
		const pathOf = ( $element, $from ) => {
			const parts = [];

			for (
				let $node = $element;
				$node && $node !== $from;
				$node = $node.parentElement
			) {
				parts.unshift(
					`${ $node.localName }:${ [
						...$node.parentElement.children,
					].indexOf( $node ) }`
				);
			}

			return parts.join( '>' );
		};

		return [ ...$styleguide.querySelectorAll( '*' ) ]
			.filter(
				( $element ) =>
					! [ 'script', 'style', 'template' ].includes(
						$element.localName
					) && ! $element.closest( 'svg' )
			)
			.map( ( $element ) => {
				const $figure = $element.closest( 'figure' );
				const name = $figure
					?.querySelector( 'figcaption h4' )
					?.textContent.trim();
				const key = name
					? `${ name } > ${ pathOf( $element, $figure ) }`
					: pathOf( $element, $styleguide );
				const styles = window.getComputedStyle( $element );

				// Its numbers to two decimals, as a browser may compute them otherwise past them, such as a color mixed.
				return [
					key,
					names
						.map( ( property ) =>
							styles.getPropertyValue( property )
						)
						.join( '; ' )
						.replace( /-?\d*\.\d+/g, ( number ) =>
							(
								Math.round( Number( number ) * 100 ) / 100
							).toString()
						),
				];
			} );
	}, properties );
}

const MESSAGE =
	'The elements of the styleguide above changed their styles; on purpose, write them again: UPDATE_STYLES=1 pnpm test:e2e styles';

/**
 * A short hash of a set of styles, the same for the same styles.
 *
 * @param {string} styles The styles.
 * @return {string} Its hash.
 */
const hashOf = ( styles ) =>
	createHash( 'sha1' ).update( styles ).digest( 'hex' ).slice( 0, 10 );

/**
 * The elements whose styles changed, as the file has them then as the styleguide shows them now; none when the file is
 * written again, UPDATE_STYLES set.
 *
 * @param {string[]} lines Each element read, its pass, its key and its styles: "phone light | key | styles".
 * @return {string[]} What changed.
 */
function changesOf( lines ) {
	const read = new Map(
		lines.map( ( line ) => {
			const at = line.lastIndexOf( ' | ' );

			return [ line.slice( 0, at ), line.slice( at + 3 ) ];
		} )
	);

	if ( process.env.UPDATE_STYLES ) {
		const styles = new Map(
			[ ...read.values() ].map( ( value ) => [ hashOf( value ), value ] )
		);

		writeFileSync(
			FILE,
			[
				...[ ...styles ]
					.sort( ( [ a ], [ b ] ) => a.localeCompare( b ) )
					.map( ( [ hash, value ] ) => `#${ hash } | ${ value }` ),
				'',
				...[ ...read ].map(
					( [ key, value ] ) => `${ key } | #${ hashOf( value ) }`
				),
			].join( '\n' ) + '\n'
		);

		return [];
	}

	const [ table, elements ] = readFileSync( FILE, 'utf8' )
		.trimEnd()
		.split( '\n\n' );
	const styles = new Map(
		table.split( '\n' ).map( ( line ) => line.slice( 1 ).split( ' | ' ) )
	);
	const written = new Map(
		elements.split( '\n' ).map( ( line ) => {
			const at = line.lastIndexOf( ' | #' );

			return [ line.slice( 0, at ), styles.get( line.slice( at + 4 ) ) ];
		} )
	);

	return [ ...new Set( [ ...written.keys(), ...read.keys() ] ) ]
		.filter( ( key ) => written.get( key ) !== read.get( key ) )
		.map(
			( key ) =>
				`${ key }\n  written: ${
					written.get( key ) ?? 'none'
				}\n  now:     ${ read.get( key ) ?? 'none' }`
		);
}

test.describe( 'Styles', () => {
	test( 'the components keep their styles', async ( { page } ) => {
		const lines = [];

		for ( const { name, width, colorScheme } of PASSES ) {
			await page.setViewportSize( { width, height: 844 } );
			await page.emulateMedia( { colorScheme, reducedMotion: 'reduce' } );
			await openApp( page, '&view=styleguide' );
			await waitForIdle( page );

			for ( const [ key, styles ] of await readStyles(
				page,
				PROPERTIES
			) ) {
				lines.push( `${ name } | ${ key } | ${ styles }` );
			}
		}

		expect( changesOf( lines ), MESSAGE ).toEqual( [] );
	} );
} );
