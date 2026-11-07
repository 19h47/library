/**
 * Rendering of the bricks: the fields of the templates of the page, filled as text, and the cards, rows, covers, books being read and achievements,
 * and the tiles, the months and the shares of a recap.
 */

import { __, _n, sprintf } from 'library-app/app';
import {
	formatBooks,
	formatDate,
	formatNumber,
	formatOriginal,
	formatStatus,
	join,
	listFormat,
	monthInitialFormat,
	monthNameFormat,
} from 'library-app/format';
import { urlOf } from 'library-app/router';

/**
 * @import { Achievement, Author, Book, CloseAchievement, Cover, PersonSeries, Target, Term } from 'library-app/api'
 * @import { Route } from 'library-app/router'
 */

/**
 * A line of a list of a recap, such as a form and its readings, with a bar as long as its share of the most read one.
 *
 * @typedef {Object} Share
 * @property {string|HTMLElement} name     Its name, or a link to its page.
 * @property {number}             readings Its readings.
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
 * A link to what `route` shows, which the router opens without reloading, such as the books a search finds.
 *
 * @param {Partial<Route>} route What it shows.
 * @param {string}         text  Its text.
 * @return {HTMLAnchorElement} The link.
 */
function link( route, text ) {
	const $link = document.createElement( 'a' );

	$link.href = urlOf( route );
	$link.textContent = text;

	return $link;
}

/**
 * Links the element of a book, a card, a row or a cover, to its page, which its link opens.
 *
 * @param {HTMLElement} $item The element of the book.
 * @param {Book}        book  The book.
 */
function linkBook( $item, book ) {
	$item.dataset.id = book.id.toString();
	/** @type {HTMLAnchorElement} */ (
		$item.querySelector( '.library-item__link, .library-cover__link' )
	).href = urlOf( { view: 'book', id: book.id } );
}

/**
 * A link to the page `view` of `id`: of a book, of a person, of a series or a cycle, of a collection or a publisher.
 *
 * @param {string} view The view of the page: "book", "person", "series" or "publisher".
 * @param {number} id   The ID of its book or its term.
 * @param {string} name The text of the link, its name.
 * @return {HTMLAnchorElement} The link.
 */
export function pageLink( view, id, name ) {
	return link( { view, id }, name );
}

/**
 * Fills `$list` with `$links` as pills, one per item, such as the series of a cycle; its [data-row] hides without one.
 *
 * @param {Element}             $list  The list.
 * @param {HTMLAnchorElement[]} $links The links.
 */
export function fillPills( $list, $links ) {
	$list.replaceChildren(
		...$links.map( ( $link ) => {
			const $item = document.createElement( 'li' );

			$link.className = 'app-pill';
			$item.append( $link );

			return $item;
		} )
	);
	/** @type {HTMLElement} */ ( $list.closest( '[data-row]' ) ).hidden =
		! $links.length;
}

/**
 * Turns the names of people in `$field` into links to their pages, listed as the language lists them.
 *
 * @param {Element} $field The field.
 * @param {Term[]}  people The people.
 */
export function linkPeople( $field, people ) {
	const $links = people.map( ( person ) =>
		pageLink( 'person', person.id, person.name )
	);

	$field.replaceChildren(
		...listFormat
			.formatToParts( people.map( ( person ) => person.name ) )
			.map( ( part ) =>
				'element' === part.type ? $links.shift() : part.value
			)
	);
}

/**
 * Links the subject of an achievement to what it leads to in the app: its book, the page of its author, of its series or
 * of its collection, or the books its search finds.
 *
 * @param {Element}     $field               The field of its subject.
 * @param {Object}      achievement          The achievement.
 * @param {Target|null} [achievement.target] What its subject leads to, if anything.
 */
function linkAchievement( $field, { target } ) {
	const text = $field.textContent;
	const links = {
		book: () => pageLink( 'book', target.id, text ),
		author: () => pageLink( 'person', target.id, text ),
		series: () => pageLink( 'series', target.id, text ),
		collection: () => pageLink( 'publisher', target.id, text ),
		search: () => link( { query: target.search }, text ),
	};

	if ( text && target?.type in links ) {
		$field.replaceChildren( links[ target.type ]() );
	}
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

	if ( cover.width ) {
		$image.width = cover.width;
		$image.height = cover.height;
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

/**
 * Fills the card of a book; `state` is the line under its title, followed by the tags of its form, of a book in its
 * original language and of a book not kept or to buy.
 *
 * @param {HTMLElement} $item The card.
 * @param {Book}        book  The book.
 * @param {string}      state The line under its title.
 */
export function fillBook( $item, book, state ) {
	fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
	fill( $item, {
		credits: book.credits,
		title: book.title,
		excerpt: book.excerpt,
		state,
		form: book.form,
		original: formatOriginal( book ),
		status: formatStatus( book ),
	} );
}

/**
 * The cover of a book, which opens it, its `state`, such as read, said after its title to assistive technologies only, as
 * the covers count them above; a book without cover shows its title instead.
 *
 * @param {Book}   book    The book.
 * @param {string} [state] Its state, such as read.
 * @return {HTMLElement} The cover.
 */
export function renderCover( book, state = '' ) {
	const $item = clone( 'library-cover' );
	/** @type {HTMLImageElement} */
	const $image = $item.querySelector( '[data-field="cover"]' );

	linkBook( $item, book );
	$image.alt = book.title;
	fillCover( $image, book.cover );
	fill( $item, { title: book.cover ? '' : book.title, state } );

	return $item;
}

/**
 * A book being read, as its cover, larger than the latest readings show it, which opens it: its title and its status
 * under it, or its title in its frame without a cover, and its button Finished over its corner, described by them.
 *
 * @param {Book} book The book.
 * @return {HTMLElement} The cover.
 */
export function renderCurrent( book ) {
	const $item = clone( 'library-current' );
	const $open = $item.querySelector( '.library-cover__link' );

	linkBook( $item, book );
	$open.id = `library-current-${ book.id }`;
	$item
		.querySelector( '[data-action="finish"]' )
		.setAttribute( 'aria-describedby', $open.id );
	fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
	fill( $item, {
		frame: book.cover ? '' : book.title,
		title: book.cover ? book.title : '',
		status: formatStatus( book ),
	} );

	return $item;
}

/**
 * The row of a book, which opens it, as the reading idea on the home: its cover, its title, its authors and its status when it is not kept.
 *
 * @param {Book} book The book.
 * @return {HTMLElement} The row.
 */
export function renderRow( book ) {
	const $item = clone( 'library-row' );

	linkBook( $item, book );
	fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
	fill( $item, {
		title: book.title,
		authors: listFormat.format(
			book.authors.map( ( author ) => author.name )
		),
		status: formatStatus( book ),
	} );

	return $item;
}

/**
 * The card of a book, which opens it.
 *
 * @param {Book}   book  The book.
 * @param {string} state The line under its title.
 * @return {HTMLElement} The card.
 */
export function renderBook( book, state ) {
	const $item = clone( 'library-item' );

	linkBook( $item, book );
	fillBook( $item, book, state );

	return $item;
}

/**
 * The card of a person or a series: its name, `$link` to its page, its description, then `line`, the line under it.
 *
 * @param {HTMLAnchorElement} $link       The link to its page.
 * @param {string}            description Its description, HTML.
 * @param {string}            line        The line under it.
 * @return {HTMLElement} The card.
 */
function renderTerm( $link, description, line ) {
	const $item = clone( 'library-term' );

	fill( $item, { line } );
	$item.querySelector( '.library-term__name' ).append( $link );
	fillHtml( $item.querySelector( '[data-html="description"]' ), description );

	return $item;
}

/**
 * The card of an author of a series: their name, which leads to their page, their description, then `line`.
 *
 * @param {Author} author The author.
 * @param {string} line   The line under it.
 * @return {HTMLElement} The card.
 */
export function renderAuthor( author, line ) {
	return renderTerm(
		pageLink( 'person', author.id, author.name ),
		author.description,
		line
	);
}

/**
 * The card of a series of a person: its name after its cycles, which leads to its page, its description, then `line`.
 *
 * @param {PersonSeries} series The series.
 * @param {string}       line   The line under it.
 * @return {HTMLElement} The card.
 */
export function renderSeries( series, line ) {
	return renderTerm(
		pageLink( 'series', series.id, series.name ),
		series.description,
		line
	);
}

// The cards of a section shown before the others fold: four show all, five fold two.
const SHOWN_CARDS = 3;

/**
 * Fills `$section` with `$cards`: the first ones in its [data-list="shown"], the others folded in its [data-list="more"],
 * closed, under a pill that `more( count )` names; the section hides without cards.
 *
 * @param {HTMLElement}                 $section The section.
 * @param {HTMLElement[]}               $cards   The cards.
 * @param {( count: number ) => string} more     The name of the pill, from the number of cards folded.
 */
export function fillFolded( $section, $cards, more ) {
	const $more = $section.querySelector( '[data-list="more"]' );
	const shown = $cards.length > SHOWN_CARDS + 1 ? SHOWN_CARDS : $cards.length;
	const folded = $cards.length - shown;

	$section
		.querySelector( '[data-list="shown"]' )
		.replaceChildren( ...$cards.slice( 0, shown ) );
	$more.replaceChildren( ...$cards.slice( shown ) );
	$more.closest( 'details' ).open = false;
	fill( $section, { more: folded ? more( folded ) : '' } );
	$section.hidden = ! $cards.length;
}

/**
 * Fills `$section` with the cards of the authors of the `total` books of a series or a collection, who wrote the most
 * first: how many of its books each one wrote, unless it has only one.
 *
 * @param {HTMLElement} $section The section.
 * @param {Author[]}    authors  The authors.
 * @param {number}      total    The books of the series or the collection.
 */
export function fillAuthors( $section, authors, total ) {
	fillFolded(
		$section,
		authors.map( ( author ) =>
			renderAuthor( author, total > 1 ? formatBooks( author.books ) : '' )
		),
		( folded ) =>
			sprintf(
				/* translators: %s: number of authors of a series or a collection, after the first ones. */
				_n( '%s other author', '%s other authors', folded, 'library' ),
				formatNumber( folded )
			)
	);
}

/**
 * An achievement earned: its title, its subject, its detail and date, as on the Achievements page.
 *
 * @param {Achievement} achievement The achievement.
 * @return {HTMLElement} Its element.
 */
export function renderAchievement( achievement ) {
	const $item = clone( 'library-achievement' );

	fill( $item, {
		title: achievement.title,
		subject: achievement.subject,
		meta: join( [
			achievement.detail,
			achievement.date && formatDate( achievement.date ),
		] ),
	} );
	linkAchievement(
		$item.querySelector( '[data-field="subject"]' ),
		achievement
	);

	return $item;
}

/**
 * An achievement close to be earned: its subject, or else its title, and its progress, which the element `id` labels.
 *
 * @param {CloseAchievement} achievement The achievement.
 * @param {string}           id          The ID of the element that labels its progress.
 * @return {HTMLElement} Its element.
 */
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
	$progress.setAttribute(
		'aria-valuetext',
		sprintf(
			/* translators: 1: number done, 2: total, e.g. "13 of 14". */
			__( '%1$s of %2$s', 'library' ),
			done,
			total
		)
	);

	return $item;
}

/**
 * Fills `$completion` with the completion the books of a page count for, earned or close, whose progress the element
 * `id` labels; it links nowhere, the page being its subject. Hidden without one.
 *
 * @param {HTMLElement}                       $completion The element of the completion.
 * @param {Achievement|CloseAchievement|null} completion  The completion, earned or close, if any.
 * @param {string}                            id          The ID of the element that labels its progress.
 */
export function fillCompletion( $completion, completion, id ) {
	$completion.hidden = ! completion;

	if ( ! completion ) {
		$completion.replaceChildren();
		return;
	}

	const achievement = { ...completion, target: null };

	$completion.replaceChildren(
		'total' in achievement
			? renderCloseAchievement( achievement, id )
			: renderAchievement( achievement )
	);
}

/**
 * A span of `text`, of the class `className`.
 *
 * @param {string} text      Its text.
 * @param {string} className Its class.
 * @return {HTMLSpanElement} The span.
 */
function span( text, className ) {
	const $span = document.createElement( 'span' );

	$span.className = className;
	$span.textContent = text;

	return $span;
}

/**
 * Gives each of `$items` its share of the most of `values`, by their order, from 0 to 1, which sets the length of its
 * bar: --library-share.
 *
 * @param {HTMLElement[]} $items The elements, such as the lines of a list.
 * @param {number[]}      values Their values, such as their readings.
 */
function setShares( $items, values ) {
	const most = Math.max( 1, ...values );

	$items.forEach( ( $item, index ) =>
		$item.style.setProperty(
			'--library-share',
			( values[ index ] / most ).toString()
		)
	);
}

/**
 * A cover of the wall of the books read of a recap, which opens nothing: their cards are a tap away, in the diary.
 *
 * @param {Book} book The book.
 * @return {HTMLElement} The tile.
 */
export function renderTile( book ) {
	const $item = document.createElement( 'li' );
	const $image = document.createElement( 'img' );

	$image.alt = '';
	$image.loading = 'lazy';
	fillCover( $image, book.cover );
	$item.append( $image );

	return $item;
}

/**
 * The readings of each month of `year`, as bars as high as their share of the month read the most, under their number
 * and above the initial of their month; assistive technologies read the name of the month and its books.
 *
 * @param {number[]} months The readings of each month, from January.
 * @param {number}   year   The year.
 * @return {HTMLElement[]} The months.
 */
export function renderMonths( months, year ) {
	const $months = months.map( ( readings, month ) => {
		const $item = document.createElement( 'li' );
		const date = new Date( year, month );
		const $shown = [
			span( formatNumber( readings ), 'app-meta' ),
			span( '', 'library-recap__bar' ),
			span( monthInitialFormat.format( date ), 'app-meta' ),
		];

		$shown.forEach( ( $span ) =>
			$span.setAttribute( 'aria-hidden', 'true' )
		);
		$item.append(
			...$shown,
			span(
				sprintf(
					/* translators: 1: name of a month, 2: number of books read in it. */
					__( '%1$s: %2$s', 'library' ),
					monthNameFormat.format( date ),
					formatBooks( readings )
				),
				'sr-only'
			)
		);

		return $item;
	} );

	setShares( $months, months );

	return $months;
}

/**
 * The lines of a list of a recap, the most read first: each its name, its books and a bar as long as its share of the
 * first one.
 *
 * @param {Share[]} shares The lines.
 * @return {HTMLElement[]} The lines.
 */
export function renderShares( shares ) {
	const $lines = shares.map( ( share ) => {
		const $item = document.createElement( 'li' );
		const $name = span( '', 'library-recap__name' );
		const $bar = span( '', 'library-recap__bar' );

		$name.append( share.name );
		$bar.setAttribute( 'aria-hidden', 'true' );
		$item.append(
			$name,
			span( formatBooks( share.readings ), 'app-meta' ),
			$bar
		);

		return $item;
	} );

	setShares(
		$lines,
		shares.map( ( share ) => share.readings )
	);

	return $lines;
}
