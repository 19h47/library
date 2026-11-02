/**
 * Rendering of the bricks: the fields of the templates of the page, filled as text, and the cards, covers and achievements.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { __, _n, personUrl, bookUrl, seriesUrl, publisherUrl, searchUrl, sprintf } from 'library-app/app';
import { formatBooks, formatDate, formatNumber, formatOriginal, formatStatus, join, listFormat } from 'library-app/format';

// The URL of the page of a person, of a series and of a collection, by the data of its links.
const PAGE_URLS = { person: personUrl, series: seriesUrl, publisher: publisherUrl };

/** The data of the links to the pages of a person, of a series and of a collection, whose value is the ID of their term. */
export const PAGE_LINKS = Object.keys( PAGE_URLS );

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

/** A link the app follows without reloading, as its data say: to the page of a book, or to the books a search finds. */
function link( href, data, text ) {
	const $link = document.createElement( 'a' );

	$link.href = href;
	Object.assign( $link.dataset, data );
	$link.textContent = text;

	return $link;
}

/** Points `$link` to the `page` of the term `id`, "person", "series" or "publisher", which the app opens without reloading. */
export function setPageLink( $link, page, id ) {
	$link.href = PAGE_URLS[ page ] + id;
	$link.dataset[ page ] = id;
}

/** A link to the `page` of the term `id`: of a person, of a series or a cycle, of a collection or a publisher. */
export function pageLink( page, id, name ) {
	const $link = document.createElement( 'a' );

	setPageLink( $link, page, id );
	$link.textContent = name;

	return $link;
}

/** Fills `$list` with `$links` as pills, one per item, such as the series of a cycle; its [data-row] hides without one. */
export function fillPills( $list, $links ) {
	$list.replaceChildren(
		...$links.map( ( $link ) => {
			const $item = document.createElement( 'li' );

			$link.className = 'app-pill';
			$item.append( $link );

			return $item;
		} )
	);
	$list.closest( '[data-row]' ).hidden = ! $links.length;
}

/** Turns the names of people in `$field` into links to their pages, listed as the language lists them. */
export function linkPeople( $field, people ) {
	const $links = people.map( ( person ) => pageLink( 'person', person.id, person.name ) );

	$field.replaceChildren(
		...listFormat
			.formatToParts( people.map( ( person ) => person.name ) )
			.map( ( part ) => ( 'element' === part.type ? $links.shift() : part.value ) )
	);
}

/**
 * Links the subject of an achievement to what it leads to in the app: its book, the page of its author, of its series or
 * of its collection, or the books its search finds.
 */
function linkAchievement( $field, { target } ) {
	const text = $field.textContent;
	const links = {
		book: () => link( bookUrl + target.id, { book: target.id }, text ),
		author: () => pageLink( 'person', target.id, text ),
		series: () => pageLink( 'series', target.id, text ),
		collection: () => pageLink( 'publisher', target.id, text ),
		search: () => link( searchUrl + encodeURIComponent( target.search ), { search: target.search }, text ),
	};

	if ( text && target?.type in links ) {
		$field.replaceChildren( links[ target.type ]() );
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

/**
 * Fills the card of a book; `state` is the line under its title, followed by the tags of its form, of a book in its
 * original language and of a book not kept or to buy.
 */
export function fillBook( $item, book, state ) {
	fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
	fill( $item, { credits: book.credits, title: book.title, excerpt: book.excerpt, state, form: book.form, original: formatOriginal( book ), status: formatStatus( book ) } );
}

/**
 * The cover of a book, which opens it, its `state`, such as read, said after its title to assistive technologies only, as
 * the covers count them above; a book without cover shows its title instead.
 */
export function renderCover( book, state = '' ) {
	const $item = clone( 'library-cover' );
	const $image = $item.querySelector( '[data-field="cover"]' );

	$item.dataset.id = book.id;
	$image.alt = book.title;
	fillCover( $image, book.cover );
	fill( $item, { title: book.cover ? '' : book.title, state } );

	return $item;
}

/** The card of a book, which opens it. */
export function renderBook( book, state ) {
	const $item = clone( 'library-item' );

	$item.dataset.id = book.id;
	fillBook( $item, book, state );

	return $item;
}

/** The card of a person or a series: its name, `$link` to its page, its description, then `line`, the line under it. */
function renderTerm( $link, description, line ) {
	const $item = clone( 'library-term' );
	const $description = $item.querySelector( '[data-html="description"]' );

	fill( $item, { line } );
	$item.querySelector( '.library-term__name' ).append( $link );
	// HTML as the API renders it, among the few tags core keeps when it saves a description.
	$description.innerHTML = description;
	$description.hidden = ! description;

	return $item;
}

/** The card of an author of a series: their name, which leads to their page, their description, then `line`. */
export function renderAuthor( author, line ) {
	return renderTerm( pageLink( 'person', author.id, author.name ), author.description, line );
}

/** The card of a series of a person: its name after its cycles, which leads to its page, its description, then `line`. */
export function renderSeries( series, line ) {
	return renderTerm( pageLink( 'series', series.id, series.name ), series.description, line );
}

// The cards of a section shown before the others fold: four show all, five fold two.
const SHOWN_CARDS = 3;

/**
 * Fills `$section` with `$cards`: the first ones in its [data-list="shown"], the others folded in its [data-list="more"],
 * closed, under a pill that `more( count )` names; the section hides without cards.
 */
export function fillFolded( $section, $cards, more ) {
	const $more = $section.querySelector( '[data-list="more"]' );
	const shown = $cards.length > SHOWN_CARDS + 1 ? SHOWN_CARDS : $cards.length;
	const folded = $cards.length - shown;

	$section.querySelector( '[data-list="shown"]' ).replaceChildren( ...$cards.slice( 0, shown ) );
	$more.replaceChildren( ...$cards.slice( shown ) );
	$more.closest( 'details' ).open = false;
	fill( $section, { more: folded ? more( folded ) : '' } );
	$section.hidden = ! $cards.length;
}

/**
 * Fills `$section` with the cards of the authors of the `total` books of a series or a collection, who wrote the most
 * first: how many of its books each one wrote, unless it has only one.
 */
export function fillAuthors( $section, authors, total ) {
	fillFolded(
		$section,
		authors.map( ( author ) => renderAuthor( author, total > 1 ? formatBooks( author.books ) : '' ) ),
		/* translators: %s: number of authors of a series or a collection, after the first ones. */
		( folded ) => sprintf( _n( '%s other author', '%s other authors', folded, 'library' ), formatNumber( folded ) )
	);
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
	/* translators: 1: number done, 2: total, e.g. "13 of 14". */
	$progress.setAttribute( 'aria-valuetext', sprintf( __( '%1$s of %2$s', 'library' ), done, total ) );

	return $item;
}

/**
 * Fills `$completion` with the completion the books of a page count for, earned or close, whose progress the element
 * `id` labels; it links nowhere, the page being its subject. Hidden without one.
 */
export function fillCompletion( $completion, completion, id ) {
	$completion.hidden = ! completion;

	if ( ! completion ) {
		$completion.replaceChildren();
		return;
	}

	const achievement = { ...completion, target: null };

	$completion.replaceChildren( 'total' in achievement ? renderCloseAchievement( achievement, id ) : renderAchievement( achievement ) );
}
