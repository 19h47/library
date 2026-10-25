/**
 * Rendering of the bricks: the fields of the templates of the page, filled as text, and the cards, covers and achievements.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { authorUrl, sprintf, strings } from 'library-app/app';
import { formatDate, formatNumber, join, listFormat } from 'library-app/format';

/**
 * Sets the text of the [data-field] elements of `$root`; an empty one is hidden, with its [data-row].
 * A field the markup leaves out is skipped: a brick may show part of its fields, as on the home.
 */
export function fill( $root, fields ) {
	Object.entries( fields ).forEach( ( [ name, value ] ) => {
		const $field = $root.querySelector( `[data-field="${ name }"]` );

		if ( ! $field ) {
			return;
		}

		const $row = $field.closest( '[data-row]' ) ?? $field;

		$field.textContent = value;
		$row.hidden = ! value;
	} );
}

/** Turns the text of `$field` into a link, when there is a URL. */
function link( $field, url ) {
	if ( ! url || ! $field.textContent ) {
		return;
	}

	const $link = document.createElement( 'a' );

	$link.href = url;
	$link.textContent = $field.textContent;
	$field.replaceChildren( $link );
}

/** A link to the page of an author, which the app opens without reloading. */
function authorLink( id, name ) {
	const $link = document.createElement( 'a' );

	$link.href = authorUrl + id;
	$link.dataset.author = id;
	$link.textContent = name;

	return $link;
}

/** Turns the names of authors in `$field` into links to their pages, listed as the language lists them. */
export function linkAuthors( $field, authors ) {
	const $links = authors.map( ( author ) => authorLink( author.id, author.name ) );

	$field.replaceChildren(
		...listFormat
			.formatToParts( authors.map( ( author ) => author.name ) )
			.map( ( part ) => ( 'element' === part.type ? $links.shift() : part.value ) )
	);
}

/** Links the subject of an achievement: to the page of its author, else to the books list. */
function linkAchievement( $field, achievement ) {
	if ( achievement.author && $field.textContent ) {
		$field.replaceChildren( authorLink( achievement.author, $field.textContent ) );
	} else {
		link( $field, achievement.url );
	}
}

/**
 * Shows a cover, or hides the image when the book has none. The size the API gives makes the place of
 * the image known before it loads; a photo picked for a book to add has none.
 */
export function fillCover( $image, cover ) {
	$image.hidden = ! cover;

	if ( ! cover ) {
		$image.removeAttribute( 'src' );
		return;
	}

	$image.src = cover.url;

	if ( cover.width ) {
		$image.width = cover.width;
		$image.height = cover.height;
	}
}

export function clone( id ) {
	return document.getElementById( id ).content.firstElementChild.cloneNode( true );
}

/** Fills the card of a book; `state` is the line under its title. */
export function fillBook( $item, book, state ) {
	fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
	fill( $item, { credits: book.credits, title: book.title, state } );
}

/** The cover of a book, which opens it; a book without cover shows its title instead. */
export function renderCover( book ) {
	const $item = clone( 'library-cover' );
	const $image = $item.querySelector( '[data-field="cover"]' );

	$item.dataset.id = book.id;
	$image.alt = book.title;
	fillCover( $image, book.cover );
	fill( $item, { title: book.cover ? '' : book.title } );

	return $item;
}

/** The card of a book, which opens it. */
export function renderBook( book, state ) {
	const $item = clone( 'library-item' );

	$item.dataset.id = book.id;
	fillBook( $item, book, state );

	return $item;
}

/** An achievement earned: its title, its subject, its detail and date, as on the Achievements page. */
export function renderAchievement( achievement ) {
	const $item = clone( 'library-achievement' );

	fill( $item, {
		title: achievement.title,
		subject: achievement.subject,
		meta: join( [ achievement.detail, achievement.date && formatDate( achievement.date ) ] ),
	} );
	linkAchievement( $item.querySelector( '[data-field="subject"]' ), achievement );

	return $item;
}

/** An achievement close to be earned: its subject, or else its title, and its progress, which the element `id` labels. */
export function renderCloseAchievement( achievement, id ) {
	const $item = clone( 'library-achievement-close' );
	const $name = $item.querySelector( '[data-field="name"]' );
	const $progress = $item.querySelector( 'progress' );
	const hasSubject = '' !== achievement.subject;
	const done = formatNumber( achievement.done );
	const total = formatNumber( achievement.total );

	fill( $item, {
		name: hasSubject ? achievement.subject : achievement.title,
		meta: join( [ hasSubject && achievement.title, achievement.detail ] ),
		count: `${ done } / ${ total }`,
	} );
	$name.id = id;
	linkAchievement( $name, achievement );
	$progress.max = achievement.total;
	$progress.value = achievement.done;
	$progress.setAttribute( 'aria-labelledby', $name.id );
	$progress.setAttribute( 'aria-valuetext', sprintf( strings.progress, done, total ) );

	return $item;
}
