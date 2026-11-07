/**
 * The router of the reading app: the URL says what shows, its view, the book, the person, the series or the collection
 * open, the year of a recap, and the search of the books. The links of the app are links: the Navigation API turns a navigation within the
 * app into a change of view, without reloading, puts a view gone back to where it was, and leaves a link opened in a
 * new tab or window to the browser. A view gives way to the next one in a view transition, in which the cover of a book
 * opened goes from its card to its page, and back.
 */

import { appUrl } from 'library-app/app';
import { PAGE_VIEWS } from 'library-app/api';
import { store } from 'library-app/store';

/**
 * What a URL of the app shows.
 *
 * @typedef {Object} Route
 * @property {string} view  Its view, such as "books" or "person".
 * @property {number} id    The ID of the book or the term of its page, or the year of a recap, 0 for none.
 * @property {string} query The search of the books, empty for another view.
 */

// The views of the navigation, and the styleguide, opened by searching its name: their name is enough.
const NAMED_VIEWS = [
	'books',
	'diary',
	'idea',
	'achievements',
	'to-buy',
	'styleguide',
];
// The pages of a book, a person, a series or a collection, with its ID in the argument of the same name: ?view=book&book=564;
// the recap of a year, with its year: ?view=recap&recap=2025.
const ID_VIEWS = [ 'book', ...PAGE_VIEWS, 'recap' ];

const app = new URL( appUrl );
/**
 * Where each entry of the history was left, by its key, to put a view gone back to there.
 *
 * @type {Map<string, number>}
 */
const places = new Map();
/**
 * The link each entry of the history was left by, by its key, for the cover of its card to take a book gone back from.
 *
 * @type {Map<string, Element>}
 */
const links = new Map();
// Whether the router writes the search in the URL itself: the navigation it makes shows nothing new.
let isWritingSearch = false;
// A cover shown, of a card, a row or a page.
const COVER = 'img[data-field="cover"]:not([hidden])';
// The name the cover of a book takes in a view transition, on its card or row, then on its page, or the other way.
const COVER_TRANSITION = 'library-cover';
// How long a view transition waits for the cover of a page to load before it shows the page, when it cannot take the one
// of the card: a moment, then the page all the same.
const COVER_WAIT = 300;

/**
 * Shows in `$cover` the image `$shown` shows already, loaded, until `until` settles, then its own sources again, which
 * load: the cover of a page takes the one of its card for a view transition, rather than holding it while its own,
 * larger, loads, and shows its own, sharper, once it has.
 *
 * @param {HTMLImageElement} $cover The cover, not loaded yet.
 * @param {HTMLImageElement} $shown A cover of the same book, loaded.
 * @param {Promise<unknown>} until  Settles when the cover may take its own sources again.
 * @return {Promise<void>} Once the cover shows the image lent.
 */
function lendCover( $cover, $shown, until ) {
	// Its sizes before its sources, as fillCover() sets them.
	const names = [ 'sizes', 'srcset', 'src' ];
	const own = names.map( ( attribute ) => $cover.getAttribute( attribute ) );

	$cover.removeAttribute( 'srcset' );
	$cover.removeAttribute( 'sizes' );
	$cover.src = $shown.currentSrc;
	until.finally( () =>
		names.forEach( ( attribute, index ) =>
			null === own[ index ]
				? $cover.removeAttribute( attribute )
				: $cover.setAttribute( attribute, own[ index ] )
		)
	);

	return $cover.decode().catch( () => {} );
}

/**
 * Whether `url` is in the app: its page, whatever it shows.
 *
 * @param {string} url A URL.
 * @return {boolean} Whether it is in the app.
 */
function isApp( url ) {
	const { origin, pathname, searchParams } = new URL( url );

	return (
		origin === app.origin &&
		pathname === app.pathname &&
		[ ...app.searchParams ].every(
			( [ key, value ] ) => value === searchParams.get( key )
		)
	);
}

/**
 * What `url` shows: its view, the books unless it names another one, the ID of its page, and the search of the books,
 * which only the books have: the page of a person opened from a search leaves it to the books, to go back to.
 *
 * @param {string} url A URL of the app.
 * @return {Route} What it shows.
 */
export function routeOf( url ) {
	const { searchParams } = new URL( url );
	const name = searchParams.get( 'view' );
	const id = Number( searchParams.get( name ) );
	const isPage =
		ID_VIEWS.includes( name ) && Number.isInteger( id ) && id > 0;
	const view = isPage || NAMED_VIEWS.includes( name ) ? name : 'books';

	return {
		view,
		id: isPage ? id : 0,
		query: 'books' === view ? searchParams.get( 'q' ) ?? '' : '',
	};
}

/**
 * The URL of what shows, as routeOf() reads it: a page with its ID, the books with their search.
 *
 * @param {Partial<Route>} route What shows: the books unless another view.
 * @return {string} The URL.
 */
export function urlOf( { view = 'books', id = 0, query = '' } ) {
	const url = new URL( appUrl );

	if ( 'books' !== view ) {
		url.searchParams.set( 'view', view );
	}

	if ( id ) {
		url.searchParams.set( view, id.toString() );
	}

	if ( query ) {
		url.searchParams.set( 'q', query );
	}

	return url.href;
}

/**
 * Goes back to where the view shown was opened from, in the app as it is open; a view opened first, as from a link or
 * once reloaded, goes to the home.
 */
export function back() {
	const previous = navigation.entries()[ navigation.currentEntry.index - 1 ];

	if ( previous?.sameDocument ) {
		navigation.back();
	} else {
		navigation.navigate( appUrl );
	}
}

/**
 * The cover of the book `id` on the view shown: the one of its page, else the one of its card or row `$from`, a link
 * followed, else the first of its cards or rows; none when it has no cover.
 *
 * @param {Route}        route   The view shown.
 * @param {number}       id      The book.
 * @param {Element|null} [$from] An element of a card or a row of the book, such as the link followed.
 * @return {HTMLImageElement|null} The cover.
 */
function coverOf( route, id, $from = null ) {
	if ( 'book' === route.view && id === route.id ) {
		return document.querySelector(
			`library-view[name="book"]:not([hidden]) .library-book__cover:not([hidden])`
		);
	}

	const card = `li[data-id="${ id }"]`;
	const $item =
		$from?.closest( card ) ??
		document.querySelector(
			`library-view:not([hidden]) ${ card }:has(${ COVER })`
		);

	return $item?.querySelector( COVER ) ?? null;
}

/**
 * Names the cover `$cover` for a view transition, or no longer. A card skips its content until the browser sees it on
 * screen, which it does not while a view transition holds the frames, and the transition would not take a cover it
 * skips: the card of a cover named shows its content.
 *
 * @param {HTMLImageElement} $cover  A cover.
 * @param {boolean}          isNamed Whether it is named.
 */
function nameCover( $cover, isNamed ) {
	/** @type {HTMLElement|null} */
	const $item = $cover.closest( '.app-item' );

	$cover.style.viewTransitionName = isNamed ? COVER_TRANSITION : '';

	if ( $item ) {
		$item.style.contentVisibility = isNamed ? 'visible' : '';
	}
}

/**
 * Shows the destination of `event`, in a view transition when the browser does not show one of its own, as when a page is swiped back on
 * a phone, and no modal dialog is open over it, such as the form of a book added: the cover of the book opened or left
 * goes from its card to its page, or back, under the name it takes for the transition only.
 *
 * @param {NavigateEvent}       event The navigation.
 * @param {Route}               from  The view left, as the URL said before the navigation changed it.
 * @param {Element|null}        link  The link followed, or the one the view was left by before, going forward again.
 * @param {() => Promise<void>} show  Shows the destination, once it is shown.
 * @return {Promise<void>} Once the destination is shown, before the transition ends.
 */
async function transition( event, from, link, show ) {
	if (
		event.hasUAVisualTransition ||
		document.querySelector( 'dialog:modal' )
	) {
		return show();
	}

	const to = routeOf( event.destination.url );
	// The book a page shows, opened or left.
	const id = [ to, from ].find( ( route ) => 'book' === route.view )?.id;
	/** @type {HTMLImageElement[]} */
	const covers = [];
	/** @param {HTMLImageElement|null} $cover A cover, or none. */
	const name = ( $cover ) => {
		if ( $cover ) {
			nameCover( $cover, true );
			covers.push( $cover );
		}
	};

	const $left = id ? coverOf( from, id, link ) : null;
	// Settles once the transition is over, for a cover lent to show its own sources again.
	const { promise: finished, resolve: finish } = Promise.withResolvers();

	name( $left );

	const viewTransition = document.startViewTransition( async () => {
		covers.forEach( ( $cover ) => nameCover( $cover, false ) );

		await show();

		// Gone back to, the card of the link the view was left by.
		const $cover = id
			? coverOf( to, id, links.get( event.destination.key ) )
			: null;

		name( $cover );

		if ( ! $cover || $cover.complete ) {
			return;
		}

		// The cover of a page, which loads as large as it shows: the one of the card at once, its own after the transition,
		// else its own for a moment at most.
		if ( $left?.complete && $left.currentSrc ) {
			await lendCover( $cover, $left, finished );
		} else {
			await Promise.race( [
				$cover.decode().catch( () => {} ),
				new Promise( ( resolve ) => setTimeout( resolve, COVER_WAIT ) ),
			] );
		}
	} );

	viewTransition.finished.finally( () => {
		covers.forEach( ( $cover ) => nameCover( $cover, false ) );
		finish();
	} );

	return viewTransition.updateCallbackDone;
}

navigation.addEventListener( 'navigate', ( event ) => {
	// A reload loads the page again; a download, a form sent or a page out of the app are the browser's.
	if (
		isWritingSearch ||
		! event.canIntercept ||
		event.hashChange ||
		event.downloadRequest ||
		event.formData ||
		'reload' === event.navigationType ||
		! isApp( event.destination.url )
	) {
		return;
	}

	places.set( navigation.currentEntry.key, scrollY );

	// The view left: the URL is the one of the destination once the navigation is intercepted.
	const from = routeOf( location.href );
	const link =
		event.sourceElement ?? links.get( navigation.currentEntry.key ) ?? null;

	if ( event.sourceElement ) {
		links.set( navigation.currentEntry.key, event.sourceElement );
	}

	event.intercept( {
		// The view gives the focus itself: to its title opened, to what had it gone back to.
		focusReset: 'manual',
		// The browser would scroll as the links of the page do, smoothly, such as "Top".
		scroll: 'manual',
		handler: () =>
			transition( event, from, link, async () => {
				store.show(
					routeOf( event.destination.url ),
					event.navigationType
				);
				// Once the bricks have drawn the view, as the store tells them after the task: in the next one, as a view
				// transition holds the frames until the view shows.
				await new Promise( ( resolve ) => setTimeout( resolve ) );

				const top =
					'traverse' === event.navigationType
						? places.get( navigation.currentEntry.key ) ?? 0
						: 0;

				// At the top of a view opened, where a view gone back to was, at once.
				scrollTo( { top, behavior: 'instant' } );
			} ),
	} );
} );

store.show( routeOf( location.href ) );

// The search typed goes to the URL of the books, in place, as a link to the books it finds has it.
store.select(
	( state ) => state.query.trim(),
	( query ) => {
		const route = routeOf( location.href );
		const url = urlOf( { ...route, query } );

		if ( 'books' === route.view && url !== location.href ) {
			isWritingSearch = true;
			history.replaceState( history.state, '', url );
			isWritingSearch = false;
		}
	}
);
