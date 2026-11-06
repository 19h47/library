/**
 * Store of the reading app: the state every brick reads, and the actions that change it.
 *
 * What shows comes from the URL, as the router reads it, through show(). The parts of the state the API fills are
 * resources: each loads the first time it shows, or again once it failed, and loads again in place once a book changed,
 * if it was loaded: at once if it shows, else the next time it does. The books to buy load at once, whatever the view,
 * and again at once: kept by the service worker, they show without network, as in a bookshop. A book saved, its reading
 * or its form, loads the resources loaded again so; a book added loads them all again.
 *
 * @package    Library
 * @subpackage Library/app
 */

import { __, appUrl, forms, icons, maxPerPage, notKept, perPage, serviceWorkerUrl, statuses, toBuy } from 'library-app/app';
import { Store } from 'library-app/state';
import * as api from 'library-app/api';
import { today } from 'library-app/format';

const STORAGE_KEY = 'library-app';
// Kept between visits. The query, the view and the page open are in the URL only, which the router reads.
const SAVED = [ 'theme', 'themeChosen', 'read', 'status', 'form', 'sort' ];
// The orders of the books besides the latest added, as the API names them: by title from A, the others highest or latest first.
const SORTS = [ 'title', 'rating', 'read' ];
// The filters of the books, and their order: see load().
const FILTERS = [ 'read', 'status', 'form', 'sort' ];
// The page of a book, opened from its card, its cover or a link.
const BOOK_VIEW = 'book';
// The home, the books view without a query, shows the books being read, the latest readings, the reading idea and the achievements close to be earned.
const HOME = [ 'inProgress', 'diary', 'idea', 'achievements' ];
// The resource each view shows; the page of a book shows the book open.
const VIEW_RESOURCES = {
	books: 'search',
	diary: 'diary',
	idea: 'idea',
	achievements: 'achievements',
	'to-buy': 'toBuy',
	...Object.fromEntries( api.PAGE_VIEWS.map( ( view ) => [ view, view ] ) ),
};
// A search waits for a pause in the typing.
const SEARCH_DELAY = 250;
// Back to the app after this long, as from another app, its data load again: they may have changed in the admin or on another device.
const REFRESH_AFTER_MS = 5 * 60 * 1000;

const $root = document.documentElement;
const $themeColor = document.querySelector( 'meta[name="theme-color"]' );
const $favicon = document.querySelector( 'link[rel="icon"]' );
const darkQuery = matchMedia( '(prefers-color-scheme: dark)' );

/** What localStorage holds, checked: anything else falls back. */
function load() {
	let saved = null;

	try {
		saved = JSON.parse( localStorage.getItem( STORAGE_KEY ) );
	} catch {}

	const theme = true === saved?.themeChosen && [ 'light', 'dark' ].includes( saved.theme ) ? saved.theme : null;

	return {
		// The theme shown, and whether it was chosen over the system one.
		theme: theme ?? ( darkQuery.matches ? 'dark' : 'light' ),
		themeChosen: null !== theme,
		// "", "yes" or "no", the search keyword read:, or "progress", the keyword progress:.
		read: [ 'yes', 'no', 'progress' ].includes( saved?.read ) ? saved.read : '',
		// "" for any, or the status of the books shown, such as not kept.
		status: statuses.split( ',' ).includes( saved?.status ) ? saved.status : '',
		// "" for any, or the term ID of the form of the books shown, such as comics.
		form: forms.split( ',' ).includes( saved?.form ) ? saved.form : '',
		// "" for the latest added, or one of SORTS.
		sort: SORTS.includes( saved?.sort ) ? saved.sort : '',
	};
}

/** SAVED goes to localStorage. */
function save( state ) {
	try {
		localStorage.setItem( STORAGE_KEY, JSON.stringify( Object.fromEntries( SAVED.map( ( key ) => [ key, state[ key ] ] ) ) ) );
	} catch {}
}

/** Whether the home shows: the books view, without a query. */
export function isHome( state ) {
	return 'books' === state.view && ! state.query.trim();
}

/** The search sent to the API: the query, and the read state as the keyword read:, or the reading in progress as progress:. */
function searchOf( { query, read } ) {
	const keyword = 'progress' === read ? 'progress:yes' : read && `read:${ read }`;

	return [ query.trim(), keyword ].filter( Boolean ).join( ' ' );
}

/**
 * The reading of `book`, as the route of its form takes it, to put it back as it was: whether it is read and being read,
 * its read dates by their days, as the form gives them, its rating, and its status unless another one, such as a draft.
 */
function readingOf( book ) {
	const meta = {
		library_read: book.read,
		library_in_progress: book.in_progress,
		library_read_date: api.toReadings( book.read_date.map( ( date ) => date.slice( 0, 10 ) ) ),
		library_rating: book.rating || null,
	};

	return statuses.split( ',' ).includes( book.status ) ? { meta, status: book.status } : { meta };
}

/**
 * What a list asks the API for: `search` the books the search finds, of the status chosen, else of any status but
 * to buy for the books to read, which are not at hand, and of the form chosen, in the order chosen; `toBuy` the books
 * to buy; `inProgress` the books being read.
 */
function queryOf( list, state ) {
	if ( 'toBuy' === list ) {
		return { status: toBuy };
	}

	if ( 'inProgress' === list ) {
		return { search: 'progress:yes', status: statuses };
	}

	const search = searchOf( state );
	const atHand = statuses.split( ',' ).filter( ( item ) => toBuy !== item ).join();
	const status = state.status || ( 'no' === state.read ? atHand : statuses );
	const order = state.sort && { orderby: state.sort, order: 'title' === state.sort ? 'asc' : 'desc' };

	return { ...( search && { search } ), status, ...( state.form && { forms: state.form } ), ...order };
}

/** A list loaded by pages: its items so far, and where it stands; `renewing` when it loads again from its start, its items shown until then. */
function pages( items = [] ) {
	return { items, total: 0, page: 0, pages: 0, loading: false, renewing: false, loaded: false, error: '' };
}

function idea() {
	return { id: 0, reason: '', position: 0, total: 0, loading: false, loaded: false, error: '' };
}

function achievements() {
	return { earned: [], close: [], loading: false, loaded: false, error: '' };
}

/** The volumes of the series `name` of the book open, in reading order. */
function volumes( name = '' ) {
	return { ...pages(), name };
}

/** The saving of the reading of the book open. */
function saving() {
	return { loading: false, error: '' };
}

/**
 * The form of a book, to add one or to edit the book `id`, whose values it holds as `book`, and its saving; `duplicate`
 * the book that has the ISBN typed for a book to add.
 */
function editing( id = 0, book = null ) {
	return { id, book, open: false, loading: false, error: '', duplicate: 0 };
}

/** The names the form of a book suggests, as terms: people and their other names, publishers and collections, series. */
function terms() {
	return { people: [], publishers: [], series: [], loading: false, loaded: false };
}

/**
 * The page of a term open, its books loaded as a list, a page at a time, from all their IDs, `books`, in order; `completion`
 * is an achievement, a `description` is HTML. The person open, under the name they are known by: `series` theirs, those
 * with the most of their books first, `bookPages` the pages of their books, as `pages` counts the pages of the list. The
 * series open, or the cycle: its name after its cycles, its `cycle`, its `parts`, the series of a cycle, its `authors`,
 * who wrote the most first, its books in reading order, those of its series with them. The collection open, or the
 * publisher: its name, a sub-collection after the collections it is part of, its `publisher`, the `collection` it is part
 * of, its `collections`, its `authors`, its books by collection, in the order of their number.
 */
const PAGE_STATES = {
	person: () => ( { otherNames: [], series: [], rating: null, bookPages: 0 } ),
	series: () => ( { cycle: null, parts: [], authors: [] } ),
	publisher: () => ( { publisher: null, collection: null, collections: [], authors: [] } ),
};

function page( view, id = 0 ) {
	return { ...pages(), id, name: '', description: '', books: [], read: 0, completion: null, ...PAGE_STATES[ view ]() };
}

/**
 * The app state. Every brick reads and writes it, none knows the others.
 * `books` holds every book loaded, by ID; the views refer to them by ID:
 * `search` the books found, `inProgress` the books being read, `diary` the readings by month, `idea` the reading idea, `toBuy` the books to buy, latest added first,
 * `person` the person open, `series` the series open, `publisher` the collection open, `book` the book open, `volumes` the books of its series,
 * `saving` the saving of its reading, `editing` the form of a book, to add or edit, `terms` the names it suggests,
 * `notice` what an action done says, such as a reading saved, with the `action` that undoes it if any, once: a new object each time,
 * `view` the view shown, none until the router shows the one of the URL, `query` the search of the books,
 * `moved` how the view shown came, "forward" opened or "back" through the history, for the bricks to place the focus.
 */
class LibraryStore extends Store {
	#timeout = 0;
	// The request running of each resource: another one cancels it.
	#controllers = {};
	// The resources loaded, hidden as a book changed: they load again in place the next time they show.
	#stale = new Set();

	/**
	 * The resources: the parts of the state the API fills, each with its state `empty`, how it loads the first time, and
	 * how it loads again in place, `reload( id )`, after the book `id` was saved, or after a while away, without one.
	 */
	#resources = {
		search: {
			empty: pages,
			load: () => this.#fetchList( 'search' ),
			// A book saved leaves the books found if the search no longer finds it; back after a while, as many load again.
			reload: ( id ) => ( id ? this.#refreshList( 'search', id ) : this.#fetchList( 'search', { count: this.get().search.items.length, inPlace: true } ) ),
		},
		inProgress: {
			empty: pages,
			load: () => this.#fetchList( 'inProgress' ),
			reload: () => this.#fetchList( 'inProgress', { inPlace: true } ),
		},
		toBuy: {
			empty: pages,
			load: () => this.#fetchList( 'toBuy' ),
			reload: () => this.#fetchList( 'toBuy', { count: this.get().toBuy.items.length, inPlace: true } ),
		},
		diary: {
			empty: pages,
			load: () => this.#fetchDiary( 1 ),
			reload: () => this.#refreshDiary(),
		},
		idea: {
			empty: idea,
			load: () => this.#fetchIdea( 0 ),
			reload: () => this.#fetchIdea( this.get().idea.position ),
		},
		achievements: {
			empty: achievements,
			load: () => this.#fetchAchievements(),
			reload: () => this.#fetchAchievements(),
		},
		...Object.fromEntries(
			api.PAGE_VIEWS.map( ( view ) => [
				view,
				{
					empty: () => page( view ),
					load: () => this.#fetchPage( view ),
					reload: () => this.#fetchPage( view ),
				},
			] )
		),
	};

	constructor() {
		super( {
			...load(),
			view: '',
			query: '',
			books: {},
			stats: null,
			book: 0,
			volumes: volumes(),
			saving: saving(),
			editing: editing(),
			terms: terms(),
			notice: null,
			moved: '',
		} );

		this.set( ( state ) => ( { ...state, ...this.#emptyResources() } ) );

		darkQuery.addEventListener( 'change', ( { matches } ) => {
			this.set( ( state ) => ( state.themeChosen ? state : { ...state, theme: matches ? 'dark' : 'light' } ) );
		} );

		this.subscribe( ( state, previous ) => {
			if ( SAVED.some( ( key ) => state[ key ] !== previous[ key ] ) ) {
				save( state );
			}
		} );

		let hiddenAt = 0;

		document.addEventListener( 'visibilitychange', () => {
			if ( document.hidden ) {
				hiddenAt = Date.now();
			} else if ( hiddenAt && Date.now() - hiddenAt > REFRESH_AFTER_MS ) {
				this.#refresh();
			}
		} );

		// Back online, what could not load loads again, if it shows.
		addEventListener( 'online', () => this.#loadShown() );

		this.#fetchStats();
		this.#load( 'toBuy' );
	}

	/**
	 * Shows what the URL says, as the router reads it: its view, the book or the page open, and the search of the books;
	 * the data of a view load the first time it shows. `navigationType` is how it came, as the Navigation API names it:
	 * a link to a search, pushed, shows all the books it finds, whatever the filters chosen; gone back to, through the
	 * history, a view gives back the focus it had.
	 */
	show( { view, id, query }, navigationType = '' ) {
		const isSearchLink = 'books' === view && 'push' === navigationType && query && query !== this.get().query;

		this.set( ( state ) => ( {
			...state,
			moved: { push: 'forward', replace: 'forward', traverse: 'back' }[ navigationType ] ?? '',
			// The books found load again once the books view shows.
			...( 'books' === view && query !== state.query && { query, search: pages() } ),
			...( isSearchLink && Object.fromEntries( FILTERS.map( ( filter ) => [ filter, '' ] ) ) ),
		} ) );
		this.#show( view, id );
	}

	/** The search of the books, as typed. */
	setQuery( query ) {
		this.set( ( state ) => ( { ...state, query } ) );
		this.#loadHome();

		clearTimeout( this.#timeout );
		this.#timeout = setTimeout( () => this.#fetchList( 'search' ), SEARCH_DELAY );
	}

	/** A filter of the books, or their order, one of FILTERS, "" for none: the books found load again. */
	setFilter( filter, value ) {
		if ( FILTERS.includes( filter ) ) {
			this.set( ( state ) => ( { ...state, [ filter ]: value } ) );
			this.#fetchList( 'search' );
		}
	}

	/** The next books of a list, `search` or `toBuy`, unless some are loading or none is left. */
	loadMoreBooks( list ) {
		const { loading, items, total } = this.get()[ list ];

		if ( ! loading && items.length < total ) {
			this.#fetchList( list, { offset: items.length } );
		}
	}

	/** The next page of the books of the page `key`, `person`, `series` or `publisher`, unless one is loading or none is left. */
	loadMorePageBooks( key ) {
		const { loading, page: current, pages: last } = this.get()[ key ];

		if ( ! loading && current < last ) {
			this.#fetchPageBooks( key, current + 1 );
		}
	}

	/** The next page of the diary, unless one is loading or none is left. */
	loadMoreDiary() {
		const { loading, page: current, pages: last } = this.get().diary;

		if ( ! loading && current < last ) {
			this.#fetchDiary( current + 1 );
		}
	}

	/** The next idea of the week, back to the first one after the last. */
	nextIdea() {
		const { position, total, loading } = this.get().idea;

		if ( ! loading && total ) {
			this.#fetchIdea( ( position + 1 ) % total );
		}
	}

	/**
	 * Opens the form of a book: of the book `id`, its values loaded first, or of a book to add, empty.
	 * The names it suggests load the first time.
	 */
	async openEdit( id = 0 ) {
		let book = null;
		let error = '';

		if ( id ) {
			try {
				book = await api.getForm( id );
			} catch ( fetchError ) {
				error = api.errorMessage( fetchError );
			}
		}

		this.set( ( state ) => ( { ...state, editing: { ...editing( id, book ), open: true, error } } ) );

		const { loaded, loading } = this.get().terms;

		if ( ! loaded && ! loading ) {
			this.#fetchTerms();
		}
	}

	closeEdit() {
		this.set( ( state ) => ( state.editing.open ? { ...state, editing: editing() } : state ) );
	}

	/**
	 * Looks for the book that has the ISBN typed in the form of a book to add, such as a book to buy found in a bookshop.
	 * An ISBN has 10 or 13 digits, hyphens aside.
	 */
	async checkIsbn( isbn ) {
		const digits = isbn.replace( /[\s-]/g, '' );
		let duplicate = 0;

		if ( [ 10, 13 ].includes( digits.length ) ) {
			try {
				const found = await api.findIsbn( digits );

				await this.#fetchBooks( found ? [ found ] : [] );
				duplicate = found;
			} catch {}
		}

		this.#update( 'editing', ( current ) => ( current.open && ! current.id ? { duplicate } : {} ) );
	}

	/**
	 * The book to buy `id`, bought: it joins the library, the form of a book to add closed. Every view loads again.
	 * Returns its ID once bought, for its page to open, else 0.
	 */
	async buyBook( id ) {
		this.#update( 'editing', () => ( { loading: true, error: '' } ) );

		try {
			this.#addBooks( [ await api.saveBook( id, { status: 'publish' } ) ] );
			this.closeEdit();
			this.#reloadAll();

			return id;
		} catch ( error ) {
			this.#update( 'editing', () => ( { loading: false, error: api.errorMessage( error ) } ) );

			return 0;
		}
	}

	/**
	 * Adds a book, the image of its cover uploaded first. Every view loads again, so the book comes first among the
	 * books, and so do the names to suggest, with the ones it added. Returns its ID, for its page to open, for its
	 * reading, else 0.
	 */
	async addBook( { cover, ...book } ) {
		this.#update( 'editing', () => ( { loading: true, error: '' } ) );

		try {
			const coverId = cover ? await api.uploadCover( cover, book ) : 0;
			const [ id ] = this.#addBooks( [ await api.addBook( { ...book, featured_media: coverId } ) ] );

			this.closeEdit();
			this.#reloadAll();

			return id;
		} catch ( error ) {
			this.#update( 'editing', () => ( { loading: false, error: api.errorMessage( error ) } ) );

			return 0;
		}
	}

	/**
	 * Saves the form of the book open, which then shows its new values. Only what changed is sent: the publisher
	 * with its collection, the meta one by one; a photo picked is uploaded first, a cover removed is sent as 0.
	 * The views load again as after a reading, and so do the names to suggest, with the ones it added.
	 */
	async editBook( { cover, coverRemoved, meta, ...values } ) {
		const { books, editing: form } = this.get();
		const { id, book } = form;
		// Compared as JSON: the people are objects, each name with its role.
		const changes = Object.fromEntries( Object.entries( values ).filter( ( [ key, value ] ) => JSON.stringify( value ) !== JSON.stringify( book[ key ] ) ) );
		const changedMeta = Object.fromEntries( Object.entries( meta ).filter( ( [ key, value ] ) => value !== book.meta[ key ] ) );

		// A collection is under its publisher: they go together.
		if ( 'publisher' in changes || 'collection' in changes ) {
			changes.publisher = values.publisher;
			changes.collection = values.collection;
		}

		if ( Object.keys( changedMeta ).length ) {
			changes.meta = changedMeta;
		}

		if ( coverRemoved && ! cover && books[ id ].cover ) {
			changes.featured_media = 0;
		}

		if ( ! cover && ! Object.keys( changes ).length ) {
			this.closeEdit();
			return;
		}

		this.#update( 'editing', () => ( { loading: true, error: '' } ) );

		try {
			if ( cover ) {
				changes.featured_media = await api.uploadCover( cover, { ...values, meta } );
			}

			this.#addBooks( [ await api.saveBook( id, changes ) ] );
			// The form closes on the book; the names to suggest load again the next time it opens.
			this.set( ( state ) => ( { ...state, editing: editing(), terms: terms() } ) );
			this.#reloadLoaded( id );
		} catch ( error ) {
			this.#update( 'editing', () => ( { loading: false, error: api.errorMessage( error ) } ) );
		}
	}

	/**
	 * Reads the book `id` being read today, as its reading form would with the date of the day added: no longer in
	 * progress, read once more. Says so in a notice, whose action Undo puts its reading back as it was, or says what
	 * went wrong.
	 */
	async finishReading( id ) {
		const book = this.get().books[ id ];
		const { meta } = readingOf( book );
		const finished = { library_read: true, library_in_progress: false, library_read_date: api.toReadings( [ ...meta.library_read_date, today() ] ) };

		try {
			this.#addBooks( [ await api.saveBook( id, { meta: finished } ) ] );
			this.#reloadLoaded( id );
			this.#noticeSaved( id, readingOf( book ) );
		} catch ( error ) {
			this.set( ( state ) => ( { ...state, notice: { text: api.errorMessage( error ) } } ) );
		}
	}

	/** Says that the reading of the book `id` was saved, in a notice whose action Undo puts it back as it was, `before`. */
	#noticeSaved( id, before ) {
		const undo = async () => {
			try {
				this.#addBooks( [ await api.saveBook( id, before ) ] );
				this.#reloadLoaded( id );
				this.set( ( state ) => ( { ...state, notice: { text: __( 'Reading undone.', 'library' ) } } ) );
			} catch ( error ) {
				this.set( ( state ) => ( { ...state, notice: { text: api.errorMessage( error ) } } ) );
			}
		};

		this.set( ( state ) => ( { ...state, notice: { text: __( 'Reading saved.', 'library' ), action: { label: __( 'Undo', 'library' ), run: undo } } } ) );
	}

	/**
	 * Saves the reading of the book open, which says so, with Undo. Only what changed is sent, so a value the form
	 * cannot show stays as it is; marked as unread, the book loses its read dates on the server.
	 * It goes through the route of the book form, which writes the read dates at once: the core API, which
	 * removes a meta value by value, would remove them all with an unknown date, empty.
	 */
	async saveReading( { read, inProgress, readDates, rating, status: chosen } ) {
		const { book: id, books } = this.get();
		const book = books[ id ];
		const dates = api.toReadings( readDates );
		// As in the "Library" menu of the book form, a book in another status than not kept or to buy, such as a draft,
		// shows as in the library, and stays in it unless another one is chosen.
		const shown = [ notKept, toBuy ].includes( book.status ) ? book.status : 'publish';
		const status = chosen === shown ? book.status : chosen;
		const meta = {};

		if ( read !== book.read ) {
			meta.library_read = read;
		}

		if ( inProgress !== book.in_progress ) {
			meta.library_in_progress = inProgress;
		}

		// By their days, as the form gives them: the server keeps the time of a reading of the day.
		const saved = api.toReadings( book.read_date.map( ( date ) => date.slice( 0, 10 ) ) );
		// One by one: an unknown date, empty, is a reading, which a book without reading has not.
		const datesChanged = dates.length !== saved.length || dates.some( ( date, index ) => date !== saved[ index ] );

		if ( read && datesChanged ) {
			meta.library_read_date = dates;
		}

		if ( rating !== ( book.rating || null ) ) {
			meta.library_rating = rating;
		}

		if ( ! Object.keys( meta ).length && status === book.status ) {
			return;
		}

		this.#update( 'saving', () => ( { loading: true, error: '' } ) );

		try {
			this.#addBooks( [ await api.saveBook( id, status === book.status ? { meta } : { meta, status } ) ] );
			this.set( ( state ) => ( { ...state, saving: saving() } ) );
			this.#reloadLoaded( id );
			this.#noticeSaved( id, readingOf( book ) );
		} catch ( error ) {
			this.#update( 'saving', () => ( { loading: false, error: api.errorMessage( error ) } ) );
		}
	}

	toggleTheme() {
		this.set( ( state ) => ( {
			...state,
			theme: 'dark' === state.theme ? 'light' : 'dark',
			themeChosen: true,
		} ) );
	}

	/** Every resource, empty. */
	#emptyResources() {
		return Object.fromEntries( Object.entries( this.#resources ).map( ( [ key, resource ] ) => [ key, resource.empty() ] ) );
	}

	/**
	 * Loads the resource `key` as it shows: the first time, or again once it failed; again in place if a book changed
	 * while it was hidden. Not while it loads.
	 */
	#load( key ) {
		const { loaded, loading, error } = this.get()[ key ];
		const isStale = this.#stale.delete( key );

		if ( loading ) {
			return;
		}

		if ( ! loaded || error ) {
			this.#resources[ key ].load();
		} else if ( isStale ) {
			this.#resources[ key ].reload();
		}
	}

	/** The resources the view shown shows, the books to buy with them: see VIEW_RESOURCES and HOME. */
	#shownResources() {
		const state = this.get();
		const shown = [ 'toBuy', VIEW_RESOURCES[ state.view ], ...( isHome( state ) ? HOME : [] ) ];

		return shown.filter( Boolean );
	}

	/** Loads what the view shown shows, as it shows. */
	#loadShown() {
		this.#shownResources().forEach( ( key ) => this.#load( key ) );
	}

	/** Loads what the home shows, when it shows. */
	#loadHome() {
		if ( isHome( this.get() ) ) {
			HOME.forEach( ( key ) => this.#load( key ) );
		}
	}

	/**
	 * Shows a view, and loads its data the first time: the page of a person, a series or a collection starts again for another `id`,
	 * the page of a book shows the book `id`, its reading not saved left behind.
	 */
	#show( view, id = 0 ) {
		const book = BOOK_VIEW === view ? id : 0;
		const fresh = api.PAGE_VIEWS.includes( view ) && { [ view ]: page( view, id ) };

		this.set( ( state ) => {
			const next = state.view === view && state.book === book ? state : { ...state, view, book, saving: saving() };

			return ! fresh || id === state[ view ].id ? next : { ...next, ...fresh };
		} );

		if ( BOOK_VIEW === view ) {
			this.#loadBook( id );
		}

		this.#loadShown();
	}

	/**
	 * Loads the book `id` of the page of a book, unless it is loaded, then its series. A book not found, as from a link
	 * to a book deleted, or that could not load, leaves its page for the home, which says why.
	 */
	async #loadBook( id ) {
		let failure = null;

		try {
			await this.#fetchBooks( [ id ] );
		} catch ( error ) {
			failure = error;
		}

		// The URL may have changed meanwhile.
		if ( BOOK_VIEW !== this.get().view || id !== this.get().book ) {
			return;
		}

		if ( id in this.get().books ) {
			this.#loadVolumes();
			return;
		}

		const text = failure ? api.errorMessage( failure ) : __( 'No book found.', 'library' );

		navigation.navigate( appUrl, { history: 'replace' } );
		this.set( ( state ) => ( { ...state, notice: { text } } ) );
	}

	/** Loads the books of the series of the book open, unless they are loaded or loading. */
	#loadVolumes() {
		const { books, book, volumes: current } = this.get();
		const name = books[ book ]?.series ?? '';

		if ( name && name !== current.name ) {
			this.#fetchVolumes( name );
		}
	}

	/**
	 * What was loaded, again in place, after the book `id` was saved, or after a while away, without one: the stats, the
	 * series of the book open, and every resource loaded, at once if it shows, else the next time it does, so that a
	 * view gone back to shows the books as they are, where it was.
	 */
	#reloadLoaded( id = 0 ) {
		const state = this.get();
		const shown = this.#shownResources();
		// Its reading states may have changed, or its series.
		const series = state.books[ state.book ]?.series ?? '';

		this.#fetchStats();

		if ( series ) {
			this.#fetchVolumes( series );
		} else {
			this.set( ( current ) => ( { ...current, volumes: volumes() } ) );
		}

		Object.entries( this.#resources )
			.filter( ( [ key ] ) => state[ key ].loaded )
			.forEach( ( [ key, resource ] ) => ( shown.includes( key ) ? resource.reload( id ) : this.#stale.add( key ) ) );
	}

	/**
	 * Back to the app after a while: what was loaded loads again in place, as after a book saved, the books found as
	 * many as they were, and so do the book open, the books of the page open and the names to suggest.
	 */
	#refresh() {
		const { book } = this.get();
		const pageBooks = api.PAGE_VIEWS.flatMap( ( view ) => this.get()[ view ].items );

		this.set( ( state ) => ( { ...state, terms: terms() } ) );
		this.#reloadLoaded();

		if ( book ) {
			api.getBook( book )
				.then( ( loaded ) => this.#addBooks( [ loaded ] ) )
				.catch( () => {} );
		}

		this.#fetchBooks( pageBooks, { fresh: true } ).catch( () => {} );
	}

	/** After a book was added: the stats, every view and the names to suggest load again; the view shown at once. */
	#reloadAll() {
		const { view, book } = this.get();
		const id = BOOK_VIEW === view ? book : this.get()[ view ]?.id ?? 0;

		this.#stale.clear();
		this.set( ( state ) => ( { ...state, ...this.#emptyResources(), volumes: volumes(), terms: terms() } ) );
		this.#fetchStats();
		this.#load( 'toBuy' );
		this.#show( view, id );
	}

	#update( key, update ) {
		this.set( ( state ) => ( { ...state, [ key ]: { ...state[ key ], ...update( state[ key ] ) } } ) );
	}

	/** Cancels the request running of the resource `key`, if any, and returns the signal of the next one. */
	#signal( key ) {
		this.#controllers[ key ]?.abort();
		this.#controllers[ key ] = new AbortController();

		return this.#controllers[ key ].signal;
	}

	/**
	 * Loads the resource `key` through `request`, which returns what its state takes, from the state as it is then: it is
	 * loading meanwhile, then loaded, or says why it could not; a request cancelled leaves it to the one that cancelled
	 * it. `start` is what its state takes as it starts, `end` as it ends, either way. Returns whether it loaded.
	 */
	async #request( key, request, { start = {}, end = {} } = {} ) {
		this.#update( key, () => ( { loading: true, error: '', ...start } ) );

		try {
			const update = await request();

			this.#update( key, ( current ) => ( { ...update( current ), loading: false, loaded: true, ...end } ) );

			return true;
		} catch ( error ) {
			if ( 'AbortError' !== error?.name ) {
				this.#update( key, () => ( { loading: false, loaded: true, error: api.errorMessage( error ), ...end } ) );
			}

			return false;
		}
	}

	/**
	 * Adds books to the ones loaded, and returns their IDs. A book loaded again as it was stays the one loaded, so what
	 * shows it, such as its reading form being filled, is not drawn again.
	 */
	#addBooks( books ) {
		this.set( ( state ) => {
			const changed = books.filter( ( book ) => JSON.stringify( book ) !== JSON.stringify( state.books[ book.id ] ) );

			return changed.length ? { ...state, books: { ...state.books, ...Object.fromEntries( changed.map( ( book ) => [ book.id, book ] ) ) } } : state;
		} );

		return books.map( ( book ) => book.id );
	}

	/** Loads the books not loaded yet among `ids`, or all of them when `fresh`. */
	async #fetchBooks( ids, { signal, fresh = false } = {} ) {
		const { books } = this.get();
		const missing = ids.filter( ( id ) => fresh || ! ( id in books ) );

		if ( missing.length ) {
			this.#addBooks( await api.getBooks( missing, { signal } ) );
		}
	}

	/**
	 * The books of a list from `offset`, `search`, `inProgress` or `toBuy`: from 0 they replace the books of the list, from further they
	 * add to them. An offset rather than a page, as a book may have left the list. A new request of the list cancels the
	 * one running; for the search, it cancels the one waiting for a pause in the typing. `count` books at least load, up to
	 * a page of the API, so that a list loaded again keeps its length; `inPlace`, the list loads again as it is, such as
	 * after a book saved, rather than for another search, which it shows faded until then.
	 */
	#fetchList( list, { offset = 0, count = perPage, inPlace = false } = {} ) {
		if ( 'search' === list ) {
			clearTimeout( this.#timeout );
		}

		const signal = this.#signal( list );
		const query = queryOf( list, this.get() );
		const size = Math.min( Math.max( count, perPage ), maxPerPage );

		return this.#request(
			list,
			async () => {
				const { books, total } = await api.findBooks( query, { offset, count: size, signal } );
				const ids = this.#addBooks( books );

				return ( current ) => ( { items: offset ? [ ...current.items, ...ids ] : ids, total } );
			},
			{ start: { renewing: ! offset && ! inPlace }, end: { renewing: false } }
		);
	}

	/**
	 * Takes the book `id` out of a list when it no longer finds it, such as a book read with the filter "To read",
	 * a book bought with the filter "To buy", or a book to buy bought.
	 */
	async #refreshList( list, id ) {
		const query = queryOf( list, this.get() );

		// Every book is found without a search, a status or a form chosen.
		if ( 'search' === list && ! query.search && ! this.get().status && ! query.forms ) {
			return;
		}

		try {
			const isFound = await api.finds( query, id );
			// Another search, status or form may have replaced the books found meanwhile.
			const isListed = JSON.stringify( queryOf( list, this.get() ) ) === JSON.stringify( query ) && this.get()[ list ].items.includes( id );

			if ( ! isFound && isListed ) {
				this.#update( list, ( current ) => ( {
					items: current.items.filter( ( item ) => item !== id ),
					total: current.total - 1,
				} ) );
			}
		} catch {}
	}

	/**
	 * A page of the diary, with the books of its readings. `fresh`, they load all, as the page preloads them, rather than
	 * the ones not loaded yet.
	 */
	async #fetchReadings( number, fresh = false ) {
		const readings = await api.getReadings( number );

		await this.#fetchBooks(
			readings.months.flatMap( ( { readings: items } ) => items.map( ( reading ) => reading.id ) ),
			{ fresh }
		);

		return readings;
	}

	/** The next page of the diary; the first one, as the page preloads it. */
	#fetchDiary( number ) {
		return this.#request( 'diary', async () => {
			const { months, total, pages: last } = await this.#fetchReadings( number, 1 === number );

			return ( current ) => ( { items: [ ...current.items, ...months ], total, pages: last, page: number } );
		} );
	}

	/**
	 * The pages of the diary loaded so far, again, as a book was saved: its readings added or removed take their
	 * place, and the next page follows on.
	 */
	async #refreshDiary() {
		const { page: loaded } = this.get().diary;

		if ( ! loaded ) {
			return;
		}

		try {
			const all = await Promise.all( Array.from( { length: loaded }, ( value, index ) => this.#fetchReadings( index + 1 ) ) );
			const { total, pages: last } = all.at( -1 );

			this.#update( 'diary', () => ( { items: all.flatMap( ( { months } ) => months ), total, pages: last, page: loaded } ) );
		} catch {}
	}

	/** The idea at `position` in the week's list, with its book; none, as it could not load. */
	async #fetchIdea( position ) {
		const isLoaded = await this.#request( 'idea', async () => {
			const found = await api.getIdea( position );

			await this.#fetchBooks( [ found.id ] );

			return () => ( { id: found.id, reason: found.reason, position: found.position, total: found.total } );
		} );

		if ( ! isLoaded ) {
			this.#update( 'idea', () => ( { id: 0 } ) );
		}
	}

	/**
	 * The page `key` open, `person`, `series` or `publisher`, then the first page of its books, unless it is loaded
	 * already: loaded again, its books show as many pages as they did, those of now, such as a book saved into it or out
	 * of it. Another person, series or collection cancels the one loading.
	 */
	async #fetchPage( key ) {
		const signal = this.#signal( key );
		const isLoaded = await this.#request( key, async () => {
			const data = await api.getPage( key, this.get()[ key ].id, { signal } );
			const items = data.books.slice( 0, this.get()[ key ].page * perPage );

			await this.#fetchBooks( items, { signal } );

			return () => ( {
				...data,
				items,
				total: data.books.length,
				pages: Math.ceil( data.books.length / perPage ),
			} );
		} );

		if ( isLoaded && ! this.get()[ key ].page ) {
			await this.#fetchPageBooks( key, 1 );
		}
	}

	/** A page of the books of the page `key` open, from their IDs. */
	#fetchPageBooks( key, number ) {
		const { signal } = this.#controllers[ key ];
		const ids = this.get()[ key ].books.slice( ( number - 1 ) * perPage, number * perPage );

		return this.#request( key, async () => {
			await this.#fetchBooks( ids, { signal } );

			return ( current ) => ( { items: [ ...current.items, ...ids ], page: number } );
		} );
	}

	/**
	 * The books of the series `name`, in reading order. Another series cancels the one loading, and starts empty; the
	 * same one, loaded again, shows its books until the new ones come.
	 */
	#fetchVolumes( name ) {
		const signal = this.#signal( 'volumes' );

		if ( name !== this.get().volumes.name ) {
			this.set( ( state ) => ( { ...state, volumes: volumes( name ) } ) );
		}

		return this.#request( 'volumes', async () => {
			const ids = this.#addBooks( await api.getVolumes( name, { signal } ) );

			return () => ( { items: ids, total: ids.length } );
		} );
	}

	#fetchAchievements() {
		return this.#request( 'achievements', async () => {
			const { earned, close } = await api.getAchievements();

			return () => ( { earned, close } );
		} );
	}

	async #fetchTerms() {
		this.#update( 'terms', () => ( { loading: true } ) );

		try {
			const found = await api.getTerms();

			this.#update( 'terms', () => ( { ...found, loading: false, loaded: true } ) );
		} catch {
			this.#update( 'terms', () => ( { loading: false } ) );
		}
	}

	async #fetchStats() {
		try {
			const stats = await api.getStats();

			this.set( ( state ) => ( { ...state, stats } ) );
		} catch {}
	}
}

export const store = new LibraryStore();

// The document follows the store: CSS reads this attribute.
store.select(
	( state ) => state.theme,
	( theme ) => {
		$root.dataset.theme = theme;
		$themeColor.content = getComputedStyle( $root ).backgroundColor;
		$favicon.href = icons[ theme ];
	}
);

// The app and what it loaded, kept for a visit without network: the URL of the app is the scope of its service worker.
navigator.serviceWorker?.register( serviceWorkerUrl, { scope: appUrl } ).catch( reportError );
