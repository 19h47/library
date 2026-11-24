/**
 * Rendering of the bricks: the cards, rows, covers, books being read and achievements, and the tiles, the months and
 * the shares of a recap, in clones of the templates of the page; library-app/dom fills their fields.
 */

import { __, _n, roles, sprintf } from 'library-app/app';
import { urlOf } from 'library-app/router';
import { clone, fill, fillCover, fillHtml } from 'library-app/dom';
import { getPeople } from 'library-app/api';
import {
	formatBooks,
	formatDate,
	formatLanguage,
	formatList,
	formatListParts,
	formatNumber,
	formatRecap,
	join,
} from 'library-app/format';
import {
	formatOriginal,
	formatStatus,
	formatTranslation,
} from 'library-app/book-lines';

/**
 * @import { Achievement, Author, Book, CloseAchievement, Cover, PersonSeries, Target, Term } from 'library-app/api'
 * @import { Route } from 'library-app/router'
 */

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
		$item.querySelector( '[data-part="link"]' )
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
 * @param {Element}             $list             The list.
 * @param {HTMLAnchorElement[]} $links            The links.
 * @param {string}              [pill='app-pill'] The class of a pill, such as a small one.
 */
export function fillPills( $list, $links, pill = 'app-pill' ) {
	$list.replaceChildren(
		...$links.map( ( $link ) => {
			const $item = document.createElement( 'li' );

			$link.className = pill;
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
		...formatListParts( people.map( ( person ) => person.name ) ).map(
			( part ) =>
				'element' === part.type ? $links.shift() : part.value
		)
	);
}

/**
 * A link to what an achievement leads to in the app: its book, the page of its author, of its series or of its
 * collection, or the books its search finds; none without.
 *
 * @param {string}      text                 The text of the link.
 * @param {Object}      achievement          The achievement.
 * @param {Target|null} [achievement.target] What it leads to, if anything.
 * @return {HTMLAnchorElement|null} The link, or none.
 */
function achievementLink( text, { target } ) {
	const links = {
		book: () => pageLink( 'book', target.id, text ),
		author: () => pageLink( 'person', target.id, text ),
		series: () => pageLink( 'series', target.id, text ),
		collection: () => pageLink( 'publisher', target.id, text ),
		search: () => link( { query: target.search }, text ),
	};

	return text && target?.type in links ? links[ target.type ]() : null;
}

/**
 * Links the subject of an achievement to what it leads to in the app: see achievementLink().
 *
 * @param {Element}     $field               The field of its subject.
 * @param {Object}      achievement          The achievement.
 * @param {Target|null} [achievement.target] What its subject leads to, if anything.
 */
function linkAchievement( $field, achievement ) {
	const $link = achievementLink( $field.textContent, achievement );

	if ( $link ) {
		$field.replaceChildren( $link );
	}
}

/**
 * Fills the cover of a book, the component book-cover: its image, or without one its title in its frame, which a place
 * that has the title already hides.
 *
 * @param {HTMLElement}                 $cover The cover.
 * @param {Pick<Book, 'cover'|'title'>} book   The book.
 */
export function fillBookCover( $cover, book ) {
	fillCover( $cover.querySelector( '[data-field="cover"]' ), book.cover );
	fill( $cover, { cover_title: book.cover ? '' : book.title } );
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
	fillBookCover( $item.querySelector( '[data-part="cover"]' ), book );
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
 * Fills the page of a book: its title page, its cover, its title and subtitle, its credits, its translation and its
 * excerpt, its notes, and its fields, those of its work, of its edition and of its copy, its people and its publisher
 * linked to their pages. The brick of the page and the styleguide fill it alike.
 *
 * @param {HTMLElement} $root The page, or the part of it the styleguide shows.
 * @param {Book}        book  The book.
 */
export function fillBookPage( $root, book ) {
	const authors = getPeople( book, 'author' );
	const $publishers = $root.querySelector( '[data-field="publishers"]' );
	const date = ( /** @type {string} */ value ) =>
		value ? formatDate( value ) : '';
	const languages = ( /** @type {string[]} */ codes ) =>
		formatList( codes.map( formatLanguage ) );

	fillBookCover( $root.querySelector( '[data-part="cover"]' ), book );
	fill( $root, {
		credits: book.credits,
		main_title: book.main_title,
		subtitle: book.subtitle,
		translation: formatTranslation( book ),
		excerpt: book.excerpt,
		authors: formatList( authors.map( ( author ) => author.name ) ),
		published_date: date( book.published_date ),
		languages: languages( book.languages ),
		form: book.form,
		publishers: book.publishers
			.map( ( publisher ) => publisher.name )
			.join( ', ' ),
		edition_date: date( book.edition_date ),
		isbn: book.isbn,
		pages: book.pages ? formatNumber( book.pages ) : '',
		edition_languages: languages( book.edition_languages ),
		printed_date: date( book.printed_date ),
		added_date: date( book.added_date ),
	} );
	linkPeople( $root.querySelector( '[data-field="authors"]' ), authors );
	// Its publisher, then its collections, each to its page, as in its credits, its number after its collection.
	$publishers.replaceChildren(
		...book.publishers.flatMap( ( publisher, index ) => [
			...( index ? [ ', ' ] : [] ),
			pageLink( 'publisher', publisher.id, publisher.name ),
		] )
	);

	if ( book.collection_number ) {
		$publishers.append(
			' ',
			sprintf(
				/* translators: %s: number of the book in its collection. */
				__( 'no. %s', 'library' ),
				book.collection_number
			)
		);
	}
	// The other names of the book, by role, such as its colorists; the translators, in the translation line, have no field.
	fill(
		$root,
		Object.fromEntries(
			roles.map( ( role ) => [
				role,
				formatList(
					getPeople( book, role ).map( ( person ) => person.name )
				),
			] )
		)
	);
	roles.forEach( ( role ) => {
		const $field = $root.querySelector( `[data-field="${ role }"]` );

		if ( $field ) {
			linkPeople( $field, getPeople( book, role ) );
		}
	} );
	fillHtml( $root.querySelector( '[data-html="notes"]' ), book.notes_html );
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
	fillBookCover( $item.querySelector( '[data-part="cover"]' ), book );
	fill( $item, { state } );

	return $item;
}

/**
 * A book being read, as its cover, larger than the latest readings show it, which opens it: its title and its status
 * under it, or its title in its frame without a cover.
 *
 * @param {Book} book The book.
 * @return {HTMLElement} The cover.
 */
export function renderCurrent( book ) {
	const $item = clone( 'library-current' );

	linkBook( $item, book );
	fillBookCover( $item.querySelector( '[data-part="cover"]' ), book );
	fill( $item, {
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
	const $item = clone( 'library-book-row' );

	linkBook( $item, book );
	fillBookCover( $item.querySelector( '[data-part="cover"]' ), book );
	fill( $item, {
		title: book.title,
		authors: formatList(
			getPeople( book, 'author' ).map( ( author ) => author.name )
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
	const $item = clone( 'library-book-card' );

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
	const $item = clone( 'library-term-card' );

	fill( $item, { line } );
	$item.querySelector( '[data-part="name"]' ).append( $link );
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

// The cards of a cards-group shown before the others fold, two rows of two: five show all, six fold two.
const SHOWN_CARDS = 4;

/**
 * Fills the cards-group of `$section` with `$cards`: the first ones in its [data-list="shown"], the others folded in its
 * [data-list="more"], closed, under a pill that `more( count )` names; the section, which holds it, hides without cards.
 *
 * @param {HTMLElement}                 $section The section that holds the cards-group.
 * @param {HTMLElement[]}               $cards   The cards.
 * @param {( count: number ) => string} more     The name of the pill, from the number of cards folded.
 */
export function fillCardsGroup( $section, $cards, more ) {
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
	fillCardsGroup(
		$section,
		authors.map( ( author ) =>
			renderAuthor(
				author,
				total > 1 ? formatBooks( author.book_count ) : ''
			)
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
	const $item = clone( 'library-achievement-card' );

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
 * An achievement earned as a small pill, as the cloud of a recap shows it: its title and its subject, leading where its
 * subject does; its detail and its date said to assistive technologies only.
 *
 * @param {Achievement} achievement The achievement.
 * @return {HTMLLIElement} Its item.
 */
export function renderAchievementPill( achievement ) {
	const $item = document.createElement( 'li' );
	const text = join( [ achievement.title, achievement.subject ] );
	const $pill = achievementLink( text, achievement ) ?? span( text, '' );
	const said = join( [
		achievement.detail,
		achievement.date && formatDate( achievement.date ),
	] );

	$pill.className = 'app-pill app-pill--small';

	if ( said ) {
		$pill.append( span( ` · ${ said }`, 'sr-only' ) );
	}

	$item.append( $pill );

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
	const $item = clone( 'library-achievement-card-close' );
	const $name = $item.querySelector( '[data-field="name"]' );
	const $progress = $item.querySelector( 'progress-bar' );
	const hasSubject = '' !== achievement.subject;

	fill( $item, {
		name: hasSubject ? achievement.subject : achievement.title,
		meta: join( [ hasSubject && achievement.title, achievement.detail ] ),
	} );
	$name.id = id;
	linkAchievement( $name, achievement );
	$progress.max = achievement.total;
	$progress.value = achievement.done;
	$progress.labelledBy = $name.id;

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
 * A cover of a recap, which opens nothing, an image of its book only: on the wall of the books read, their cards a tap
 * away in the diary, in the columns going by behind its last screen, and beside a record, which names its book.
 *
 * @param {Book} book The book.
 * @return {HTMLElement} The tile.
 */
export function renderTile( book ) {
	const $item = clone( 'library-tile' );

	fillBookCover( $item.querySelector( '[data-part="cover"]' ), book );

	return $item;
}

/**
 * A month of the diary: its name, how many readings it has, and their cards.
 *
 * @param {string}        month     The month, Y-m.
 * @param {number}        count     How many readings it has, those whose book is not loaded too.
 * @param {HTMLElement[]} $readings The cards of its readings.
 * @return {HTMLElement} Its section.
 */
export function renderMonth( month, count, $readings ) {
	const $month = clone( 'library-month' );

	fill( $month, { month: formatDate( month ), count: formatBooks( count ) } );
	$month.querySelector( 'ul' ).append( ...$readings );

	return $month;
}

/**
 * The link to the recap of `year`, before its latest month in the diary.
 *
 * @param {number} year The year.
 * @return {HTMLElement} Its paragraph.
 */
export function renderDiaryRecap( year ) {
	const $recap = clone( 'library-diary-recap' );

	/** @type {HTMLAnchorElement} */ ( $recap.querySelector( 'a' ) ).href =
		urlOf( { view: 'recap', id: year } );
	fill( $recap, { recap: formatRecap( year ) } );

	return $recap;
}
