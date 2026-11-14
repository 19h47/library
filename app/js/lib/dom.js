/**
 * Writing in the page, with nothing of the app: the fields of an element filled as text, or as HTML, the image of a
 * cover, and the clone of a template of the page. Bricks and components share it: it imports nothing.
 */

/**
 * @import { Cover } from 'library-app/api'
 */

/**
 * Sets the text of the [data-field] elements of `$root`; an empty one is hidden, with its [data-row].
 * A field the markup leaves out is skipped: a brick may show part of its fields, as on the home.
 *
 * @param {Element}                $root  The element of the fields, such as a brick or a card.
 * @param {Object<string, string>} fields The text of each field, by its name.
 */
export function fill( $root, fields ) {
	Object.entries( fields ).forEach( ( [ name, value ] ) => {
		/** @type {HTMLElement} */
		const $field = $root.querySelector( `[data-field="${ name }"]` );

		if ( ! $field ) {
			return;
		}

		/** @type {HTMLElement} */
		const $row = $field.closest( '[data-row]' ) ?? $field;

		$field.textContent = value;
		$row.hidden = ! value;
	} );
}

/**
 * Sets the HTML of `$element`, as the API renders it: a description, among the few tags core keeps when it saves one, or
 * the notes of a book. Hidden without.
 *
 * @param {HTMLElement} $element The element.
 * @param {string}      html     Its HTML.
 */
export function fillHtml( $element, html ) {
	$element.innerHTML = html;
	$element.hidden = ! html;
}

/**
 * Shows a cover, or hides the image when the book has none. The size the API gives makes the place of
 * the image known before it loads; a photo picked for a book to add has none.
 *
 * @param {HTMLImageElement} $image The image.
 * @param {Cover|null}       cover  The cover, none without one.
 */
export function fillCover( $image, cover ) {
	$image.hidden = ! cover;

	if ( ! cover ) {
		$image.removeAttribute( 'sizes' );
		$image.removeAttribute( 'srcset' );
		$image.removeAttribute( 'src' );
		return;
	}

	// Its sizes before its source, for the browser to take the one it shows at once, as wide as it shows: sizes="auto", which
	// a lazy image takes. Only with them: the browser sizes such an image as if it were 300 by 150 until its width and height
	// say otherwise, which a cover of the API has, and a photo picked has not.
	if ( cover.srcset ) {
		$image.sizes = 'auto';
		$image.srcset = cover.srcset;
	} else {
		$image.removeAttribute( 'sizes' );
		$image.removeAttribute( 'srcset' );
	}

	$image.src = cover.url;

	// Its shape as well, which the covers take from --library-cover-ratio: Chrome counts a lazy image of an automatic width
	// as unsized whatever its width and height say. A photo picked has none, nor the ones of the cover it replaces.
	if ( cover.width ) {
		const ratio = `${ cover.width } / ${ cover.height }`;

		$image.width = cover.width;
		$image.height = cover.height;
		$image.style.setProperty( '--library-cover-ratio', ratio );
	} else {
		$image.removeAttribute( 'width' );
		$image.removeAttribute( 'height' );
		$image.style.removeProperty( '--library-cover-ratio' );
	}
}

/**
 * A clone of the element of the template `id` of the page, which holds the translated labels.
 *
 * @param {string} id The ID of the template.
 * @return {HTMLElement} The clone.
 */
export function clone( id ) {
	return /** @type {HTMLElement} */ (
		/** @type {HTMLTemplateElement} */ (
			document.getElementById( id )
		).content.firstElementChild.cloneNode( true )
	);
}
