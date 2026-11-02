/**
 * Store of the reading app: the state every brick reads, and the actions that change it.
 *
 * Created in the head, before the first paint, so the document takes its theme from it.
 * The data come from the REST API through wp.apiFetch, which handles the root URL and the nonce;
 * the store turns the books into plain text once, so no brick parses what the API returns.
 * Each view loads its data the first time it shows, but the books to buy, which load at once and again after a book
 * saved, whatever the view: kept by the service worker, they show without network, as in a bookshop. A book saved, its
 * reading or its form, loads every view again, but the one shown; a book added loads them all again.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, libraryApp
 */
( () => {
	'use strict';

	const STORAGE_KEY = 'library-app';
	// Kept between visits. The query, the view, the person and the book open are in the URL only, as ?q=, ?view=, ?person= and ?book=.
	const SAVED = [ 'theme', 'themeChosen', 'read', 'status', 'form', 'sort' ];
	// The orders of the books besides the latest added, as the API names them: by title from A, the others highest or latest first.
	const SORTS = [ 'title', 'rating', 'read' ];
	// The views of the navigation; the page of a person is another one, opened from a book or an achievement.
	const VIEWS = [ 'books', 'diary', 'idea', 'achievements', 'to-buy' ];
	const PERSON_VIEW = 'person';
	// The page of a book, opened from its card, its cover or a link.
	const BOOK_VIEW = 'book';
	// The page of a series or a cycle, opened from a book, an achievement or another series.
	const SERIES_VIEW = 'series';
	// The page of a collection or a publisher, opened from an achievement or another collection.
	const PUBLISHER_VIEW = 'publisher';
	// The views of a person, a book, a series or a collection, each with its ID in the argument of the same name: ?view=book&book=564.
	const PAGE_VIEWS = [ PERSON_VIEW, BOOK_VIEW, SERIES_VIEW, PUBLISHER_VIEW ];
	// The components of the app, for development: a view out of the navigation, opened by searching its name.
	const STYLEGUIDE_VIEW = 'styleguide';
	// The home, the books view without a query, shows the books being read, the latest readings, the reading idea and the achievements close to be earned.
	const HOME = [ 'inProgress', 'diary', 'idea', 'achievements' ];
	// A search waits for a pause in the typing.
	const SEARCH_DELAY = 250;
	// The place in a view is kept in its entry of the history once the scrolling pauses.
	const SCROLL_DELAY = 150;
	// Back to the app after this long, as from another app, its data load again: they may have changed in the admin or on another device.
	const REFRESH_AFTER_MS = 5 * 60 * 1000;

	const { apiFetch } = window.wp;
	const { __ } = window.wp.i18n;
	const { addQueryArgs } = window.wp.url;
	const config = window.libraryApp;
	const $root = document.documentElement;
	const $themeColor = document.querySelector( 'meta[name="theme-color"]' );
	const $favicon = document.querySelector( 'link[rel="icon"]' );
	const darkQuery = matchMedia( '(prefers-color-scheme: dark)' );
	const parser = new DOMParser();

	// The fields of a book the app reads, its cover and terms as the plugin gives them: nothing embedded, a list stays light.
	const BOOK_QUERY = { _fields: config.bookFields };
	// The books a list loads at once, as the page preloads them, and the most books a page of the API gives.
	const { perPage: PER_PAGE, maxPerPage: MAX_PER_PAGE } = config;

	/**
	 * Observable state, as @agencecinq/store, but for its listeners: they are called once the task that changed the
	 * state is done, with the last state, rather than at each change. An action that changes several parts of the state,
	 * such as the view and the book shown, reaches the bricks as one change, and a brick reads a state the others have drawn.
	 */
	class Store {
		#state;
		// The state the listeners were last called with.
		#notified;
		#pending = false;
		#listeners = new Set();

		constructor( initial ) {
			this.#state = initial;
			this.#notified = initial;
		}

		get() {
			return this.#state;
		}

		/** A function is called with the current state and returns the next one. */
		set( update ) {
			const next = typeof update === 'function' ? update( this.#state ) : update;

			if ( Object.is( next, this.#state ) ) {
				return;
			}

			this.#state = next;

			if ( ! this.#pending ) {
				this.#pending = true;
				queueMicrotask( () => this.#notify() );
			}
		}

		/** Calls the listeners with the state, and the one they were last called with; a listener that changes it calls them again after. */
		#notify() {
			const previous = this.#notified;
			const next = this.#state;

			this.#pending = false;
			this.#notified = next;

			if ( Object.is( next, previous ) ) {
				return;
			}

			this.#listeners.forEach( ( listener ) => {
				try {
					listener( next, previous );
				} catch ( error ) {
					reportError( error );
				}
			} );
		}

		/** Called once per change of a task, not right away. Returns the unsubscribe function. */
		subscribe( listener ) {
			this.#listeners.add( listener );

			return () => {
				this.#listeners.delete( listener );
			};
		}

		/**
		 * Called right away with the selected slice, then only when it
		 * changes. Returns the unsubscribe function.
		 */
		select( selector, listener ) {
			let value = selector( this.#state );

			listener( value );

			return this.subscribe( ( state ) => {
				const next = selector( state );

				if ( Object.is( next, value ) ) {
					return;
				}

				value = next;
				listener( next );
			} );
		}
	}

	/**
	 * Read dates as readings, from the first to the latest: each unknown date, empty, is a reading of its own, first; a
	 * known date is one reading, as a date saved twice by an old import. As the server counts them.
	 */
	function toReadings( dates ) {
		return [ ...dates.filter( ( date ) => '' === date ), ...new Set( dates.filter( Boolean ) ) ].sort();
	}

	/** Plain text of an HTML string of the API, such as a title with &#8217;: never parsed as markup in the page. */
	function text( html ) {
		return parser.parseFromString( html, 'text/html' ).body.textContent;
	}

	/** A book of the API as the app shows it: plain text but its notes, its meta by their names, its read dates in order, its cover with its size, its authors and the names with another role, its original languages and its form. */
	function toBook( item ) {
		// Its meta without the prefix of their keys: library_rating is its rating.
		const meta = Object.fromEntries( Object.entries( item.meta ).map( ( [ key, value ] ) => [ key.replace( /^library_/, '' ), value ] ) );
		// As named on the book: another name opens the page of the person too.
		const names = item.names.map( ( { id, name } ) => ( { id, name: text( name ) } ) );
		const roleIds = config.roles.flatMap( ( role ) => meta[ role ] );

		return {
			...meta,
			read_date: toReadings( meta.read_date ),
			id: item.id,
			title: text( item.title.rendered ),
			excerpt: text( item.excerpt.rendered ),
			// HTML as the API renders it: its paragraphs and links.
			content: item.content.rendered,
			credits: text( item.credits ),
			status: item.status,
			// Its size gives the image its place before it loads.
			cover: item.cover,
			// Its names without another role, then the others by role, such as its colorists, in place of their IDs.
			authors: names.filter( ( name ) => ! roleIds.includes( name.id ) ),
			...Object.fromEntries( config.roles.map( ( role ) => [ role, names.filter( ( name ) => meta[ role ].includes( name.id ) ) ] ) ),
			// After its cycles, as typed, "Star Wars, Young Jedi Knights", and its ID, for its page.
			series: item.series_name,
			series_id: item.series[ 0 ] ?? 0,
			// As their ISO 639 codes: the app names them in a sentence.
			languages: item.language_codes,
			// Its name, for the tag of its card.
			form: text( item.form_name ),
		};
	}

	/** A book of the API as its form edits it: the names of its terms in plain text, the rest as typed. */
	function toForm( item ) {
		return {
			...item,
			people: item.people.map( ( person ) => ( { ...person, name: text( person.name ) } ) ),
			publisher: text( item.publisher ),
			collection: text( item.collection ),
		};
	}

	/** The message of a failed request: the one of the API, or a generic one. */
	function errorMessage( error ) {
		return error?.message ?? __( 'The books could not be loaded.', 'library' );
	}

	/** The view of a URL, the books by default; the page of a person or a book needs its ID. */
	function viewOf( url ) {
		const view = new URL( url ).searchParams.get( 'view' );

		if ( PAGE_VIEWS.includes( view ) ) {
			return idOf( url, view ) ? view : 'books';
		}

		return isNamedView( view ) ? view : 'books';
	}

	/** Whether a view opens by its name alone: one of the navigation, or the styleguide. */
	function isNamedView( view ) {
		return VIEWS.includes( view ) || STYLEGUIDE_VIEW === view;
	}

	/** The ID a URL gives as `name`, such as the person of ?person= or the book of ?book=, or 0. */
	function idOf( url, name ) {
		const id = Number( new URL( url ).searchParams.get( name ) );

		return Number.isInteger( id ) && id > 0 ? id : 0;
	}

	/** What localStorage and the URL hold, checked: anything else falls back. */
	function load() {
		let saved = null;

		try {
			saved = JSON.parse( localStorage.getItem( STORAGE_KEY ) );
		} catch {}

		const theme =
			true === saved?.themeChosen && [ 'light', 'dark' ].includes( saved.theme )
				? saved.theme
				: null;

		return {
			// The theme shown, and whether it was chosen over the system one.
			theme: theme ?? ( darkQuery.matches ? 'dark' : 'light' ),
			themeChosen: null !== theme,
			view: viewOf( location.href ),
			query: new URLSearchParams( location.search ).get( 'q' ) ?? '',
			// "", "yes" or "no", the search keyword read:, or "progress", the keyword progress:.
			read: [ 'yes', 'no', 'progress' ].includes( saved?.read ) ? saved.read : '',
			// "" for any, or the status of the books shown, such as not kept.
			status: config.statuses.split( ',' ).includes( saved?.status ) ? saved.status : '',
			// "" for any, or the term ID of the form of the books shown, such as comics.
			form: config.forms.split( ',' ).includes( saved?.form ) ? saved.form : '',
			// "" for the latest added, or one of SORTS.
			sort: SORTS.includes( saved?.sort ) ? saved.sort : '',
		};
	}

	/** Keeps the place in the view shown in its entry of the history, to put it back there. */
	function keepScroll() {
		history.replaceState( { ...history.state, scroll: scrollY }, '' );
	}

	/** Goes to the top of a view opened, at once: the smooth scroll of the page is for its links, such as "Top". */
	function scrollToTop() {
		scrollTo( { top: 0, behavior: 'instant' } );
	}

	/** Whether the home shows: the books view, without a query. */
	function isHome( state ) {
		return 'books' === state.view && ! state.query.trim();
	}

	/** The search sent to the API: the query, and the read state as the keyword read:, or the reading in progress as progress:. */
	function searchOf( { query, read } ) {
		const keyword = 'progress' === read ? 'progress:yes' : read && `read:${ read }`;

		return [ query.trim(), keyword ].filter( Boolean ).join( ' ' );
	}

	/**
	 * What a list asks the API for: `search` the books the search finds, of the status chosen, else of any status but
	 * to buy for the books to read, which are not at hand, and of the form chosen, in the order chosen; `toBuy` the books
	 * to buy; `inProgress` the books being read.
	 */
	function queryOf( list, state ) {
		if ( 'toBuy' === list ) {
			return { status: config.toBuy };
		}

		if ( 'inProgress' === list ) {
			return { search: 'progress:yes', status: config.statuses };
		}

		const search = searchOf( state );
		const atHand = config.statuses.split( ',' ).filter( ( item ) => config.toBuy !== item ).join();
		const status = state.status || ( 'no' === state.read ? atHand : config.statuses );

		const order = state.sort && { orderby: state.sort, order: 'title' === state.sort ? 'asc' : 'desc' };

		return { ...( search && { search } ), status, ...( state.form && { forms: state.form } ), ...order };
	}

	/** SAVED goes to localStorage, the query to the URL. */
	function save( state ) {
		try {
			localStorage.setItem(
				STORAGE_KEY,
				JSON.stringify( Object.fromEntries( SAVED.map( ( key ) => [ key, state[ key ] ] ) ) )
			);
		} catch {}

		const query = state.query.trim();
		const url = new URL( location.href );

		if ( query ) {
			url.searchParams.set( 'q', query );
		} else {
			url.searchParams.delete( 'q' );
		}

		history.replaceState( history.state, '', url );
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

	/** The names the form of a book suggests, as terms: people and their other names, publishers and collections, original languages. */
	function terms() {
		return { people: [], publishers: [], series: [], languages: [], loading: false, loaded: false };
	}

	/**
	 * The person open, under the name they are known by: `series` theirs, those with the most of their books first,
	 * `books` all their book IDs, in order,
	 * `items` the ones loaded so far, a page at a time; `bookPages` the pages of their books,
	 * as `pages` counts the pages of the list; `completion` is an achievement; `description` is HTML.
	 */
	function person( id = 0 ) {
		return { ...pages(), id, name: '', otherNames: [], description: '', series: [], books: [], read: 0, rating: null, bookPages: 0, completion: null };
	}

	/**
	 * The series open, or the cycle: its name after its cycles, its `cycle`, its `parts`, the series of a cycle, its
	 * `authors`, who wrote the most first, its `books` in reading order, those of its series with them, loaded as those
	 * of a person; `completion` is an achievement; a `description` is HTML.
	 */
	function series( id = 0 ) {
		return { ...pages(), id, name: '', cycle: null, parts: [], authors: [], description: '', books: [], read: 0, completion: null };
	}

	/**
	 * The collection open, or the publisher: its name, a sub-collection after the collections it is part of, its
	 * `publisher`, the `collection` it is part of, its `collections`, its `authors`, who wrote the most first, its `books`
	 * by collection, in the order of their number, loaded as those of a person; `completion` is an achievement; a
	 * `description` is HTML.
	 */
	function publisher( id = 0 ) {
		return { ...pages(), id, name: '', publisher: null, collection: null, collections: [], authors: [], description: '', books: [], read: 0, completion: null };
	}

	/** A term the API gives, such as an author or a cycle, with its name in plain text; none stays none. */
	function toPlainName( term ) {
		return term && { ...term, name: text( term.name ) };
	}

	/**
	 * The pages of a person, of a series and of a collection, by their view: their route, their `state` for the term `id`,
	 * and what it takes from the API.
	 */
	const PAGES = {
		[ PERSON_VIEW ]: {
			route: 'people',
			state: person,
			from: ( data ) => ( {
				name: text( data.name ),
				otherNames: data.other_names.map( text ),
				description: data.description,
				series: data.series.map( toPlainName ),
				read: data.read,
				rating: data.rating,
				bookPages: data.pages,
				completion: data.completion,
			} ),
		},
		[ SERIES_VIEW ]: {
			route: 'series',
			state: series,
			from: ( data ) => ( {
				name: text( data.name ),
				cycle: toPlainName( data.cycle ),
				parts: data.series.map( toPlainName ),
				authors: data.authors.map( toPlainName ),
				description: data.description,
				read: data.read,
				completion: data.completion,
			} ),
		},
		[ PUBLISHER_VIEW ]: {
			route: 'publishers',
			state: publisher,
			from: ( data ) => ( {
				name: text( data.name ),
				publisher: toPlainName( data.publisher ),
				collection: toPlainName( data.collection ),
				collections: data.collections.map( toPlainName ),
				authors: data.authors.map( toPlainName ),
				description: data.description,
				read: data.read,
				completion: data.completion,
			} ),
		},
	};
	const PAGE_KEYS = Object.keys( PAGES );

	/** The pages of a person, of a series and of a collection, empty. */
	function emptyPages() {
		return Object.fromEntries( PAGE_KEYS.map( ( key ) => [ key, PAGES[ key ].state() ] ) );
	}

	/**
	 * The app state. Every brick reads and writes it, none knows the others.
	 * `books` holds every book loaded, by ID; the views refer to them by ID:
	 * `search` the books found, `inProgress` the books being read, `diary` the readings by month, `idea` the reading idea, `toBuy` the books to buy, latest added first,
	 * `person` the person open, `series` the series open, `publisher` the collection open, `book` the book open, `volumes` the books of its series,
	 * `saving` the saving of its reading, `editing` the form of a book, to add or edit, `terms` the names it suggests,
	 * `notice` what an action done says, such as a reading saved, once: a new object each time,
	 * `moved` how the view shown came, "forward" opened or "back" through the history, for the bricks to place the focus.
	 */
	class LibraryStore extends Store {
		#timeout = 0;
		// The request of each list running, `search` or `toBuy`: another one cancels it.
		#controllers = {};
		// The request of the page of a person, a series or a collection running: another one cancels it.
		#pageControllers = {};
		#volumesController = null;

		constructor() {
			super( {
				...load(),
				books: {},
				search: pages(),
				inProgress: pages(),
				diary: pages(),
				idea: idea(),
				achievements: achievements(),
				toBuy: pages(),
				stats: null,
				...emptyPages(),
				book: 0,
				volumes: volumes(),
				saving: saving(),
				editing: editing(),
				terms: terms(),
				notice: null,
				moved: '',
			} );

			darkQuery.addEventListener( 'change', ( { matches } ) => {
				this.set( ( state ) =>
					state.themeChosen ? state : { ...state, theme: matches ? 'dark' : 'light' }
				);
			} );

			// The app puts each view back where it was, from its entry of the history, rather than the browser, which would
			// before the view shows.
			history.scrollRestoration = 'manual';

			let scrolling = 0;

			addEventListener(
				'scroll',
				() => {
					clearTimeout( scrolling );
					scrolling = setTimeout( () => keepScroll(), SCROLL_DELAY );
				},
				{ passive: true }
			);

			// Back and forward between the views, a book or a person among them: each takes back the search of its URL and its place.
			addEventListener( 'popstate', () => {
				const view = viewOf( location.href );
				const query = new URLSearchParams( location.search ).get( 'q' ) ?? '';

				clearTimeout( scrolling );
				this.set( ( state ) => ( { ...state, moved: 'back' } ) );

				// The books found load again once the books view shows.
				if ( query !== this.get().query ) {
					this.set( ( state ) => ( { ...state, query, search: pages() } ) );
				}

				this.#show( view, idOf( location.href, view ) );
				// Once the view shows.
				requestAnimationFrame( () => scrollTo( { top: history.state?.scroll ?? 0, behavior: 'instant' } ) );
			} );

			this.subscribe( ( state, previous ) => {
				if ( [ ...SAVED, 'query' ].some( ( key ) => state[ key ] !== previous[ key ] ) ) {
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

			this.#show( this.get().view, idOf( location.href, this.get().view ) );
			this.#fetchStats();
			this.#load( 'to-buy' );
		}

		/**
		 * Shows a view, at its top, with its URL in the history. The view shown, its link touched again, goes back to its
		 * top; the books to their home, without their search.
		 */
		setView( view ) {
			if ( ! isNamedView( view ) ) {
				return;
			}

			if ( view === this.get().view ) {
				if ( 'books' === view ) {
					this.#clearQuery();
				}

				scrollToTop();
				return;
			}

			this.#go( view );
		}

		/**
		 * Opens the page `view` of the term `id`, of a person, of a series or a cycle, of a collection or a publisher, at its
		 * top, with its URL in the history, unless it shows already.
		 */
		openPage( view, id ) {
			const { view: shown, [ view ]: current } = this.get();

			if ( view !== shown || id !== current.id ) {
				this.#go( view, id );
			}
		}

		/** Goes back to where the view shown was opened from; a view opened first, as from a link, goes to the home. */
		back() {
			if ( history.state?.app ) {
				history.back();
			} else {
				this.goHome();
			}
		}

		/** The home, as the name of the app leads to it: the books view without its search, at its top. */
		goHome() {
			this.setView( 'books' );
			this.#clearQuery();
		}

		/** The search of the books; the name of the styleguide opens it, the search left empty behind it. */
		setQuery( query ) {
			if ( STYLEGUIDE_VIEW === query.trim().toLowerCase() ) {
				this.setQuery( '' );
				this.setView( STYLEGUIDE_VIEW );
				return;
			}

			this.set( ( state ) => ( { ...state, query } ) );
			this.#loadHome();

			clearTimeout( this.#timeout );
			this.#timeout = setTimeout( () => this.#fetchList( 'search' ), SEARCH_DELAY );
		}

		setRead( read ) {
			this.set( ( state ) => ( { ...state, read } ) );
			this.#fetchList( 'search' );
		}

		setStatus( status ) {
			this.set( ( state ) => ( { ...state, status } ) );
			this.#fetchList( 'search' );
		}

		setForm( form ) {
			this.set( ( state ) => ( { ...state, form } ) );
			this.#fetchList( 'search' );
		}

		setSort( sort ) {
			this.set( ( state ) => ( { ...state, sort } ) );
			this.#fetchList( 'search' );
		}

		/**
		 * Shows the books a search finds, read or not, of any status and form, in the default order, at the top of the books view, such as the books
		 * of a series from an achievement or from a book. The view comes first, so that the query goes to its URL.
		 */
		searchBooks( query ) {
			this.#go( 'books' );
			this.set( ( state ) => ( { ...state, query, read: '', status: '', form: '', sort: '' } ) );
			this.#fetchList( 'search' );
			scrollToTop();
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
			const { loading, page, pages: last } = this.get()[ key ];

			if ( ! loading && page < last ) {
				this.#fetchPageBooks( key, page + 1 );
			}
		}

		/** The next page of the diary, unless one is loading or none is left. */
		loadMoreDiary() {
			const { loading, page, pages: last } = this.get().diary;

			if ( ! loading && page < last ) {
				this.#fetchDiary( page + 1 );
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
		 * Opens the page of a book, at its top, with its URL in the history, unless it shows already: a book not loaded yet,
		 * such as the book of an achievement, loads on its page, which says so.
		 */
		openBook( id ) {
			const { book, view } = this.get();

			if ( BOOK_VIEW !== view || id !== book ) {
				this.#go( BOOK_VIEW, id );
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
					book = toForm( await apiFetch( { path: `/library/v1/books/${ id }` } ) );
				} catch ( fetchError ) {
					error = errorMessage( fetchError );
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
			const params = new URLSearchParams( { search: `isbn:${ digits }`, status: config.statuses, per_page: 1, _fields: 'id' } );
			let duplicate = 0;

			if ( [ 10, 13 ].includes( digits.length ) ) {
				try {
					const [ found ] = await apiFetch( { path: `/wp/v2/books?${ params }` } );

					await this.#fetchBooks( found ? [ found.id ] : [] );
					duplicate = found?.id ?? 0;
				} catch {}
			}

			this.#update( 'editing', ( current ) => ( current.open && ! current.id ? { duplicate } : {} ) );
		}

		/** The book to buy `id`, bought: it joins the library, then opens, the form of a book to add closed. Every view loads again. */
		async buyBook( id ) {
			this.#update( 'editing', () => ( { loading: true, error: '' } ) );

			try {
				await apiFetch( { path: `/library/v1/books/${ id }`, method: 'POST', data: { status: 'publish' } } );
				this.#addBooks( [ await apiFetch( { path: `/wp/v2/books/${ id }?${ new URLSearchParams( BOOK_QUERY ) }` } ) ] );
				this.closeEdit();
				this.#reloadAll();
				this.openBook( id );
			} catch ( error ) {
				this.#update( 'editing', () => ( { loading: false, error: errorMessage( error ) } ) );
			}
		}

		/**
		 * Adds a book, the image of its cover uploaded first, then opens it, for its reading. Every view loads
		 * again, so the book comes first among the books, and so do the names to suggest, with the ones it added.
		 */
		async addBook( { cover, ...book } ) {
			this.#update( 'editing', () => ( { loading: true, error: '' } ) );

			try {
				const coverId = cover ? await this.#uploadCover( cover, book ) : 0;
				const { id } = await apiFetch( { path: '/library/v1/books', method: 'POST', data: { ...book, featured_media: coverId } } );

				await this.#fetchBooks( [ id ] );
				this.closeEdit();
				this.#reloadAll();
				this.openBook( id );
			} catch ( error ) {
				this.#update( 'editing', () => ( { loading: false, error: errorMessage( error ) } ) );
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
			const changes = Object.fromEntries(
				Object.entries( values ).filter( ( [ key, value ] ) => JSON.stringify( value ) !== JSON.stringify( book[ key ] ) )
			);
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
					changes.featured_media = await this.#uploadCover( cover, { ...values, meta } );
				}

				await apiFetch( { path: `/library/v1/books/${ id }`, method: 'POST', data: changes } );
				this.#addBooks( [ await apiFetch( { path: `/wp/v2/books/${ id }?${ new URLSearchParams( BOOK_QUERY ) }` } ) ] );
				// The form closes on the book; the names to suggest load again the next time it opens.
				this.set( ( state ) => ( { ...state, editing: editing(), terms: terms() } ) );
				this.#reload( id );
			} catch ( error ) {
				this.#update( 'editing', () => ( { loading: false, error: errorMessage( error ) } ) );
			}
		}

		/**
		 * Saves the reading of the book open, which says so. Only what changed is sent, so a value the form
		 * cannot show stays as it is; marked as unread, the book loses its read dates on the server.
		 * It goes through the route of the book form, which writes the read dates at once: the core API, which
		 * removes a meta value by value, would remove them all with an unknown date, empty.
		 */
		async saveReading( { read, inProgress, readDates, rating, status: chosen } ) {
			const { book: id, books } = this.get();
			const book = books[ id ];
			const dates = toReadings( readDates );
			// As in the "Library" menu of the book form, a book in another status than not kept or to buy, such as a draft,
			// shows as in the library, and stays in it unless another one is chosen.
			const shown = [ config.notKept, config.toBuy ].includes( book.status ) ? book.status : 'publish';
			const status = chosen === shown ? book.status : chosen;
			const meta = {};

			if ( read !== book.read ) {
				meta.library_read = read;
			}

			if ( inProgress !== book.in_progress ) {
				meta.library_in_progress = inProgress;
			}

			// By their days, as the form gives them: the server keeps the time of a reading of the day.
			if ( read && dates.join() !== toReadings( book.read_date.map( ( date ) => date.slice( 0, 10 ) ) ).join() ) {
				meta.library_read_date = dates;
			}

			if ( rating !== ( book.rating || null ) ) {
				meta.library_rating = rating;
			}

			if ( ! Object.keys( meta ).length && status === book.status ) {
				return;
			}

			const params = new URLSearchParams( BOOK_QUERY );
			const data = status === book.status ? { meta } : { meta, status };

			this.#update( 'saving', () => ( { loading: true, error: '' } ) );

			try {
				await apiFetch( { path: `/library/v1/books/${ id }`, method: 'POST', data } );
				this.#addBooks( [ await apiFetch( { path: `/wp/v2/books/${ id }?${ params }` } ) ] );
				this.set( ( state ) => ( { ...state, saving: saving(), notice: { text: __( 'Reading saved.', 'library' ) } } ) );
				this.#reload( id );
			} catch ( error ) {
				this.#update( 'saving', () => ( { loading: false, error: errorMessage( error ) } ) );
			}
		}

		toggleTheme() {
			this.set( ( state ) => ( {
				...state,
				theme: 'dark' === state.theme ? 'light' : 'dark',
				themeChosen: true,
			} ) );
		}

		/** Uploads the photo of a cover, named and titled after its book as its form sends it, and returns the ID of its image. */
		async #uploadCover( file, { title, people, series, meta } ) {
			const query = { title, people, series, meta, photo: file.name };
			const { name } = await apiFetch( { path: addQueryArgs( '/library/v1/books/cover-name', query ) } );
			const body = new FormData();

			body.append( 'file', file, name );
			body.append( 'title', title );

			return ( await apiFetch( { path: '/wp/v2/media', method: 'POST', body } ) ).id;
		}

		/** Empties the search of the books, unless it is empty. */
		#clearQuery() {
			if ( this.get().query ) {
				this.setQuery( '' );
			}
		}

		/**
		 * Opens a view, at its top, with its URL in the history: a view of the navigation, or the page of the person or the
		 * book `id`. The search stays in the URL; the place in the view left is kept in its entry, to come back to it.
		 */
		#go( view, id = 0 ) {
			const url = new URL( location.href );

			PAGE_VIEWS.forEach( ( name ) => url.searchParams.delete( name ) );

			if ( 'books' === view ) {
				url.searchParams.delete( 'view' );
			} else {
				url.searchParams.set( 'view', view );
			}

			if ( id ) {
				url.searchParams.set( view, id );
			}

			keepScroll();
			// An entry of the app, which going back leads to.
			history.pushState( { app: true }, '', url );
			this.set( ( state ) => ( { ...state, moved: 'forward' } ) );
			this.#show( view, id );
			scrollToTop();
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

			const text = failure ? errorMessage( failure ) : __( 'No book found.', 'library' );

			history.replaceState( null, '', config.appUrl );
			this.#show( 'books' );
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
		 * Shows a view, and loads its data the first time: the page of a person, a series or a collection starts again for another `id`,
		 * the page of a book shows the book `id`, its reading not saved left behind.
		 */
		#show( view, id = 0 ) {
			const book = BOOK_VIEW === view ? id : 0;
			const fresh = view in PAGES && { [ view ]: PAGES[ view ].state( id ) };

			this.set( ( state ) => {
				const next = state.view === view && state.book === book ? state : { ...state, view, book, saving: saving() };

				return ! fresh || id === state[ view ].id ? next : { ...next, ...fresh };
			} );

			if ( BOOK_VIEW === view ) {
				this.#loadBook( id );
			} else {
				this.#load( view );
			}

			this.#loadHome();
		}

		/** Loads what the home shows, when it shows. */
		#loadHome() {
			if ( isHome( this.get() ) ) {
				HOME.forEach( ( view ) => this.#load( view ) );
			}
		}

		/**
		 * Loads the data of a view, or of a part of the home such as the books being read, unless they are loaded or loading;
		 * the styleguide has none, its samples are written in it.
		 */
		#load( view ) {
			if ( STYLEGUIDE_VIEW === view ) {
				return;
			}

			const state = this.get();
			const loaders = {
				books: [ state.search, () => this.#fetchList( 'search' ) ],
				inProgress: [ state.inProgress, () => this.#fetchList( 'inProgress' ) ],
				diary: [ state.diary, () => this.#fetchDiary( 1 ) ],
				idea: [ state.idea, () => this.#fetchIdea( 0 ) ],
				achievements: [ state.achievements, () => this.#fetchAchievements() ],
				'to-buy': [ state.toBuy, () => this.#fetchList( 'toBuy' ) ],
				...Object.fromEntries( PAGE_KEYS.map( ( key ) => [ key, [ state[ key ], () => this.#fetchPage( key ) ] ] ) ),
			};
			const [ data, fetchData ] = loaders[ view ];

			if ( ! data.loaded && ! data.loading ) {
				fetchData();
			}
		}

		/**
		 * After the book `id` was saved: the stats load again, and so does what was loaded, in place, so that a view gone
		 * back to shows the book as it is, where it was. The books found lose it when the search no longer finds it; the
		 * books to buy, the books being read, the diary, the reading idea, the achievements, the page of the person, the
		 * series or the collection open and the series of the book load again.
		 */
		#reload( id ) {
			this.#reloadLoaded( () => this.#refreshList( 'search', id ) );
		}

		/**
		 * Back to the app after a while: what was loaded loads again in place, as after a book saved, the books found as
		 * many as they were, and so do the book open, the books of the person, the series or the collection open and the names to suggest.
		 */
		#refresh() {
			const { book, search: found } = this.get();
			const pageBooks = PAGE_KEYS.flatMap( ( key ) => this.get()[ key ].items );

			this.set( ( state ) => ( { ...state, terms: terms() } ) );
			this.#reloadLoaded( () => this.#fetchList( 'search', { count: found.items.length, inPlace: true } ) );

			if ( book ) {
				this.#refetchBook( book );
			}

			this.#fetchBooks( pageBooks, { fresh: true } ).catch( () => {} );
		}

		/** The stats, and what was loaded, again in place; `refreshSearch` the books found. */
		#reloadLoaded( refreshSearch ) {
			const { toBuy: bought, inProgress, diary, idea: shown, achievements: earned } = this.get();
			const loadedPages = PAGE_KEYS.filter( ( key ) => this.get()[ key ].loaded );

			// Its reading states may have changed.
			this.set( ( state ) => ( { ...state, volumes: volumes() } ) );
			this.#fetchStats();
			this.#loadVolumes();

			if ( this.get().search.loaded ) {
				refreshSearch();
			}

			// The books to buy load at once, whatever the view: they show without network.
			if ( bought.loaded ) {
				this.#fetchList( 'toBuy', { count: bought.items.length, inPlace: true } );
			}

			if ( inProgress.loaded ) {
				this.#fetchList( 'inProgress', { inPlace: true } );
			}

			if ( diary.loaded ) {
				this.#refreshDiary();
			}

			if ( shown.loaded ) {
				this.#fetchIdea( shown.position );
			}

			if ( earned.loaded ) {
				this.#fetchAchievements();
			}

			loadedPages.forEach( ( key ) => this.#fetchPage( key ) );
		}

		/** The book `id` from the API, though it is loaded, as it may have changed elsewhere. */
		async #refetchBook( id ) {
			try {
				this.#addBooks( [ await apiFetch( { path: `/wp/v2/books/${ id }?${ new URLSearchParams( BOOK_QUERY ) }` } ) ] );
			} catch {}
		}

		/** After a book was added: the stats, every view and the names to suggest load again; the view shown at once. */
		#reloadAll() {
			const { view } = this.get();

			this.set( ( state ) => ( {
				...state,
				search: pages(),
				inProgress: pages(),
				diary: pages(),
				idea: idea(),
				achievements: achievements(),
				toBuy: pages(),
				...emptyPages(),
				volumes: volumes(),
				terms: terms(),
			} ) );
			this.#fetchStats();
			this.#load( 'to-buy' );
			this.#show( view, idOf( location.href, view ) );
		}

		/** Sets a part of the state, such as `search`, from its current value. */
		#update( key, update ) {
			this.set( ( state ) => ( { ...state, [ key ]: { ...state[ key ], ...update( state[ key ] ) } } ) );
		}

		/** Adds books of the API to the ones loaded, and returns their IDs. */
		#addBooks( items ) {
			const books = items.map( toBook );

			this.set( ( state ) => ( {
				...state,
				books: { ...state.books, ...Object.fromEntries( books.map( ( book ) => [ book.id, book ] ) ) },
			} ) );

			return books.map( ( book ) => book.id );
		}

		/**
		 * Loads the books not loaded yet among `ids`, or all of them when `fresh`, in the order of their IDs, as the page
		 * preloads the books of the diary: whatever is loaded already, the request is the same. A page of the API at a time.
		 */
		async #fetchBooks( ids, { signal, fresh = false } = {} ) {
			const { books } = this.get();
			const missing = [ ...new Set( ids ) ].filter( ( id ) => fresh || ! ( id in books ) ).sort( ( a, b ) => a - b );

			if ( ! missing.length ) {
				return;
			}

			const chunks = Array.from( { length: Math.ceil( missing.length / MAX_PER_PAGE ) }, ( value, index ) =>
				missing.slice( index * MAX_PER_PAGE, ( index + 1 ) * MAX_PER_PAGE )
			);
			const loaded = await Promise.all(
				chunks.map( ( chunk ) => {
					const params = new URLSearchParams( {
						...BOOK_QUERY,
						status: config.statuses,
						include: chunk.join( ',' ),
						per_page: chunk.length,
					} );

					return apiFetch( { path: `/wp/v2/books?${ params }`, signal } );
				} )
			);

			this.#addBooks( loaded.flat() );
		}

		/**
		 * The books of a list from `offset`, `search`, `inProgress` or `toBuy`: from 0 they replace the books of the list, from further they
		 * add to them. An offset rather than a page, as a book may have left the list. A new request of the list cancels the
		 * one running; for the search, it cancels the one waiting for a pause in the typing. `count` books at least load, up to
		 * a page of the API, so that a list loaded again keeps its length; `inPlace`, the list loads again as it is, such as
		 * after a book saved, rather than for another search, which it shows faded until then.
		 */
		async #fetchList( list, { offset = 0, count = PER_PAGE, inPlace = false } = {} ) {
			if ( 'search' === list ) {
				clearTimeout( this.#timeout );
			}

			this.#controllers[ list ]?.abort();
			this.#controllers[ list ] = new AbortController();

			const { signal } = this.#controllers[ list ];
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				...queryOf( list, this.get() ),
				per_page: Math.min( Math.max( count, PER_PAGE ), MAX_PER_PAGE ),
				offset,
			} );

			this.#update( list, () => ( { loading: true, renewing: ! offset && ! inPlace, error: '' } ) );

			try {
				const response = await apiFetch( { path: `/wp/v2/books?${ params }`, parse: false, signal } );
				const ids = this.#addBooks( await response.json() );

				this.#update( list, ( current ) => ( {
					items: offset ? [ ...current.items, ...ids ] : ids,
					total: Number( response.headers.get( 'X-WP-Total' ) ),
					loading: false,
					renewing: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( list, () => ( { loading: false, renewing: false, loaded: true, error: errorMessage( error ) } ) );
				}
			}
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

			const params = new URLSearchParams( { ...query, include: id, _fields: 'id' } );

			try {
				const found = await apiFetch( { path: `/wp/v2/books?${ params }` } );
				// Another search, status or form may have replaced the books found meanwhile.
				const isListed = JSON.stringify( queryOf( list, this.get() ) ) === JSON.stringify( query ) && this.get()[ list ].items.includes( id );

				if ( ! found.length && isListed ) {
					this.#update( list, ( current ) => ( {
						items: current.items.filter( ( item ) => item !== id ),
						total: current.total - 1,
					} ) );
				}
			} catch {}
		}

		/**
		 * A page of the diary: whole months, latest first, each with its readings and their books; the totals count months.
		 * `fresh`, its books load all, as the page preloads them, rather than the ones not loaded yet.
		 */
		async #fetchReadings( page, fresh = false ) {
			const response = await apiFetch( { path: `/library/v1/books/readings?page=${ page }`, parse: false } );
			const months = ( await response.json() ).map( ( { month, readings } ) => ( {
				month,
				readings: readings.map( ( { date, book } ) => ( { date, id: book } ) ),
			} ) );

			await this.#fetchBooks( months.flatMap( ( { readings } ) => readings.map( ( reading ) => reading.id ) ), { fresh } );

			return {
				months,
				total: Number( response.headers.get( 'X-WP-Total' ) ),
				pages: Number( response.headers.get( 'X-WP-TotalPages' ) ),
			};
		}

		/** The next page of the diary; the first one, as the page preloads it. */
		async #fetchDiary( page ) {
			const isFirst = 1 === page;

			this.#update( 'diary', () => ( { loading: true, error: '' } ) );

			try {
				const { months, total, pages: last } = await this.#fetchReadings( page, isFirst );

				this.#update( 'diary', ( current ) => ( {
					items: [ ...current.items, ...months ],
					total,
					pages: last,
					page,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				this.#update( 'diary', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
			}
		}

		/**
		 * The pages of the diary loaded so far, again, as a book was saved: its readings added or removed take their
		 * place, and the next page follows on.
		 */
		async #refreshDiary() {
			const { page } = this.get().diary;

			if ( ! page ) {
				return;
			}

			try {
				const loaded = await Promise.all( Array.from( { length: page }, ( value, index ) => this.#fetchReadings( index + 1 ) ) );
				const { total, pages: last } = loaded.at( -1 );

				this.#update( 'diary', () => ( {
					items: loaded.flatMap( ( { months } ) => months ),
					total,
					pages: last,
					page,
				} ) );
			} catch {}
		}

		/** The idea at `position` in the week's list, with its book. */
		async #fetchIdea( position ) {
			this.#update( 'idea', () => ( { loading: true, error: '' } ) );

			try {
				const idea = await apiFetch( { path: `/library/v1/books/reading-idea?position=${ position }` } );

				await this.#fetchBooks( [ idea.id ] );

				this.#update( 'idea', () => ( {
					id: idea.id,
					reason: idea.reason,
					position: idea.position,
					total: idea.total,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				this.#update( 'idea', () => ( { id: 0, loading: false, loaded: true, error: errorMessage( error ) } ) );
			}
		}

		/**
		 * The page `key` open, `person`, `series` or `publisher`, then the first page of its books, unless it is loaded
		 * already. Another person, series or collection cancels the one loading.
		 */
		async #fetchPage( key ) {
			this.#pageControllers[ key ]?.abort();
			this.#pageControllers[ key ] = new AbortController();

			const { signal } = this.#pageControllers[ key ];
			const { route, from } = PAGES[ key ];

			this.#update( key, () => ( { loading: true, error: '' } ) );

			try {
				const data = await apiFetch( { path: `/library/v1/${ route }/${ this.get()[ key ].id }`, signal } );

				this.#update( key, ( current ) => ( {
					// A book saved out of it leaves its books.
					items: current.items.filter( ( book ) => data.books.includes( book ) ),
					...from( data ),
					books: data.books,
					total: data.books.length,
					pages: Math.ceil( data.books.length / PER_PAGE ),
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( key, () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}

				return;
			}

			if ( ! this.get()[ key ].page ) {
				await this.#fetchPageBooks( key, 1 );
			}
		}

		/** A page of the books of the page `key` open, from their IDs. */
		async #fetchPageBooks( key, page ) {
			const { signal } = this.#pageControllers[ key ];
			const ids = this.get()[ key ].books.slice( ( page - 1 ) * PER_PAGE, page * PER_PAGE );

			this.#update( key, () => ( { loading: true, error: '' } ) );

			try {
				await this.#fetchBooks( ids, { signal } );

				this.#update( key, ( current ) => ( {
					items: [ ...current.items, ...ids ],
					page,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( key, () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}
			}
		}

		/** The books of the series `name`, in reading order, as a search for it lists them. Another series cancels the one loading. */
		async #fetchVolumes( name ) {
			this.#volumesController?.abort();
			this.#volumesController = new AbortController();

			const { signal } = this.#volumesController;
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				search: `series:"${ name }"`,
				status: config.statuses,
				per_page: MAX_PER_PAGE,
			} );

			this.set( ( state ) => ( { ...state, volumes: { ...volumes( name ), loading: true } } ) );

			try {
				const ids = this.#addBooks( await apiFetch( { path: `/wp/v2/books?${ params }`, signal } ) );

				this.#update( 'volumes', () => ( { items: ids, total: ids.length, loading: false, loaded: true } ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'volumes', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}
			}
		}

		async #fetchAchievements() {
			this.#update( 'achievements', () => ( { loading: true, error: '' } ) );

			try {
				const { earned, close } = await apiFetch( { path: '/library/v1/books/achievements' } );

				this.#update( 'achievements', () => ( { earned, close, loading: false, loaded: true } ) );
			} catch ( error ) {
				this.#update( 'achievements', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
			}
		}

		/**
		 * Every person and their other names, publisher and collection, with its publisher and after the collections it is part of,
		 * series after its cycles, and original language: apiFetch loads all their pages.
		 */
		async #fetchTerms() {
			this.#update( 'terms', () => ( { loading: true } ) );

			try {
				const bases = [ 'people', 'publishers', 'series', 'languages' ];
				const [ people, publishers, series, languages ] = await Promise.all(
					bases.map( ( base ) => apiFetch( { path: `/wp/v2/${ base }?per_page=-1&_fields=id,name,parent` } ) )
				);
				const toTerm = ( { id, name, parent } ) => ( { id, name: text( name ), parent } );
				const byId = Object.fromEntries( series.map( ( term ) => [ term.id, toTerm( term ) ] ) );
				// A series after its cycles, as a book types it.
				const pathOf = ( term ) => ( term.parent in byId ? `${ pathOf( byId[ term.parent ] ) }, ${ term.name }` : term.name );
				const publishersById = Object.fromEntries( publishers.map( ( term ) => [ term.id, toTerm( term ) ] ) );
				const publisherOf = ( term ) => ( term.parent in publishersById ? publisherOf( publishersById[ term.parent ] ) : term );
				// A collection after the collections it is part of, without its publisher, as a book types it.
				const collectionOf = ( term ) =>
					publishersById[ term.parent ]?.parent ? `${ collectionOf( publishersById[ term.parent ] ) }, ${ term.name }` : term.name;

				this.#update( 'terms', () => ( {
					people: people.map( toTerm ),
					publishers: Object.values( publishersById ).map( ( term ) => ( { ...term, publisher: publisherOf( term ).id, collection: collectionOf( term ) } ) ),
					series: Object.values( byId ).map( ( term ) => ( { ...term, name: pathOf( term ) } ) ),
					languages: languages.map( toTerm ),
					loading: false,
					loaded: true,
				} ) );
			} catch {
				this.#update( 'terms', () => ( { loading: false } ) );
			}
		}

		async #fetchStats() {
			try {
				const stats = await apiFetch( { path: '/library/v1/books/reading-stats' } );

				this.set( ( state ) => ( { ...state, stats } ) );
			} catch {}
		}
	}

	const store = new LibraryStore();

	// The document follows the store: CSS reads this attribute.
	store.select(
		( state ) => state.theme,
		( theme ) => {
			$root.dataset.theme = theme;
			$themeColor.content = getComputedStyle( $root ).backgroundColor;
			$favicon.href = config.icons[ theme ];
		}
	);

	window.store = store;

	// The app and what it loaded, kept for a visit without network: the URL of the app is the scope of its service worker.
	navigator.serviceWorker?.register( config.serviceWorkerUrl, { scope: config.appUrl } ).catch( reportError );
} )();
