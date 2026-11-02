/**
 * The router of the reading app: the URL says what shows, its view, the book, the person, the series or the collection
 * open, and the search of the books. The links of the app are links: the Navigation API turns a navigation within the
 * app into a change of view, without reloading, puts a view gone back to where it was, and leaves a link opened in a
 * new tab or window to the browser.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { appUrl } from 'library-app/app';
import { store } from 'library-app/store';

// The views of the navigation, and the styleguide, opened by searching its name: their name is enough.
const NAMED_VIEWS = [ 'books', 'diary', 'idea', 'achievements', 'to-buy', 'styleguide' ];
// The pages of a book, a person, a series or a collection, with its ID in the argument of the same name: ?view=book&book=564.
const PAGE_VIEWS = [ 'book', 'person', 'series', 'publisher' ];

const app = new URL( appUrl );
// Where each entry of the history was left, by its key, to put a view gone back to there.
const places = new Map();
// Whether the router writes the search in the URL itself: the navigation it makes shows nothing new.
let isWritingSearch = false;

/** Whether `url` is in the app: its page, whatever it shows. */
function isApp( url ) {
	const { origin, pathname, searchParams } = new URL( url );

	return origin === app.origin && pathname === app.pathname && [ ...app.searchParams ].every( ( [ key, value ] ) => value === searchParams.get( key ) );
}

/**
 * What `url` shows: its view, the books unless it names another one, the ID of its page, and the search of the books,
 * which only the books have: the page of a person opened from a search leaves it to the books, to go back to.
 */
export function routeOf( url ) {
	const { searchParams } = new URL( url );
	const name = searchParams.get( 'view' );
	const id = Number( searchParams.get( name ) );
	const isPage = PAGE_VIEWS.includes( name ) && Number.isInteger( id ) && id > 0;
	const view = isPage || NAMED_VIEWS.includes( name ) ? name : 'books';

	return {
		view,
		id: isPage ? id : 0,
		query: 'books' === view ? searchParams.get( 'q' ) ?? '' : '',
	};
}

/** The URL of what shows, as routeOf() reads it: a page with its ID, the books with their search. */
export function urlOf( { view = 'books', id = 0, query = '' } ) {
	const url = new URL( appUrl );

	if ( 'books' !== view ) {
		url.searchParams.set( 'view', view );
	}

	if ( id ) {
		url.searchParams.set( view, id );
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

navigation.addEventListener( 'navigate', ( event ) => {
	// A reload loads the page again; a download, a form sent or a page out of the app are the browser's.
	if ( isWritingSearch || ! event.canIntercept || event.hashChange || event.downloadRequest || event.formData || 'reload' === event.navigationType || ! isApp( event.destination.url ) ) {
		return;
	}

	places.set( navigation.currentEntry.key, scrollY );

	event.intercept( {
		// The view gives the focus itself: to its title opened, to what had it gone back to.
		focusReset: 'manual',
		// The browser would scroll as the links of the page do, smoothly, such as "Top".
		scroll: 'manual',
		async handler() {
			store.show( routeOf( event.destination.url ), event.navigationType );
			// Once the view shows: the bricks draw it after the task, the view gives the focus in the next frame.
			await new Promise( ( resolve ) => requestAnimationFrame( resolve ) );

			const top = 'traverse' === event.navigationType ? places.get( navigation.currentEntry.key ) ?? 0 : 0;

			// At the top of a view opened, where a view gone back to was, at once.
			scrollTo( { top, behavior: 'instant' } );
		},
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
