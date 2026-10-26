/**
 * Store of the reading app: the state every brick reads, and the actions that change it.
 *
 * Created in the head, before the first paint, so the document takes its theme from it.
 * The data come from the REST API through wp.apiFetch, which handles the root URL and the nonce;
 * the store turns the books into plain text once, so no brick parses what the API returns.
 * Each view loads its data the first time it shows. A book saved loads every view again, but the one shown;
 * a book added loads them all again.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, libraryApp
 */
( () => {
	'use strict';

	const STORAGE_KEY = 'library-app';
	// Kept between visits. The query, the view, the author and the book open are in the URL only, as ?q=, ?view=, ?author= and ?book=.
	const SAVED = [ 'theme', 'themeChosen', 'read' ];
	// The views of the navigation; the page of an author is another one, opened from a book or an achievement.
	const VIEWS = [ 'books', 'diary', 'idea', 'achievements' ];
	const AUTHOR_VIEW = 'author';
	// The home, the books view without a query, shows the latest readings, the reading idea and the achievements close to be earned.
	const HOME = [ 'diary', 'idea', 'achievements' ];
	const PER_PAGE = 24;
	// A search waits for a pause in the typing.
	const SEARCH_DELAY = 250;

	const { apiFetch } = window.wp;
	const { __ } = window.wp.i18n;
	const config = window.libraryApp;
	const $root = document.documentElement;
	const $themeColor = document.querySelector( 'meta[name="theme-color"]' );
	const $favicon = document.querySelector( 'link[rel="icon"]' );
	const darkQuery = matchMedia( '(prefers-color-scheme: dark)' );
	const parser = new DOMParser();

	// The fields of a book the app reads, and its cover and terms.
	const BOOK_QUERY = {
		_embed: 'wp:featuredmedia,wp:term',
		_fields: 'id,title,credits,status,meta,_links,_embedded',
	};

	/** Observable state: the part of @agencecinq/store used here. */
	class Store {
		#state;
		#listeners = new Set();

		constructor( initial ) {
			this.#state = initial;
		}

		get() {
			return this.#state;
		}

		/** A function is called with the current state and returns the next one. */
		set( update ) {
			const previous = this.#state;
			const next = typeof update === 'function' ? update( previous ) : update;

			if ( Object.is( next, previous ) ) {
				return;
			}

			this.#state = next;
			this.#listeners.forEach( ( listener ) => {
				try {
					listener( next, previous );
				} catch ( error ) {
					reportError( error );
				}
			} );
		}

		/** Called on every change, not right away. Returns the unsubscribe function. */
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

	/** Plain text of an HTML string of the API, such as a title with &#8217;: never parsed as markup in the page. */
	function text( html ) {
		return parser.parseFromString( html, 'text/html' ).body.textContent;
	}

	/** A book of the API as the app shows it: plain text, its read dates in order, its cover with its size, its authors and its original languages. */
	function toBook( item ) {
		const media = item._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
		const sizes = media?.media_details?.sizes ?? {};
		const size = sizes.medium ?? sizes.full;
		const terms = ( item._embedded?.[ 'wp:term' ] ?? [] ).flat();

		return {
			...item.meta,
			// A date stored empty comes back as null.
			read_date: [ ...new Set( item.meta.read_date.filter( Boolean ) ) ].sort(),
			id: item.id,
			title: text( item.title.rendered ),
			credits: text( item.credits ),
			status: item.status,
			// Its size gives the image its place before it loads.
			cover: size ? { url: size.source_url, width: size.width, height: size.height } : null,
			// As named on the book: a pseudonym opens the page of the author too.
			authors: terms
				.filter( ( term ) => 'library-author' === term.taxonomy )
				.map( ( term ) => ( { id: term.id, name: text( term.name ) } ) ),
			languages: terms
				.filter( ( term ) => 'library-language' === term.taxonomy )
				.map( ( term ) => text( term.name ) ),
		};
	}

	/** The message of a failed request: the one of the API, or a generic one. */
	function errorMessage( error ) {
		return error?.message ?? __( 'The books could not be loaded.', 'library' );
	}

	/** The view of a URL, the books by default; the page of an author needs an author. */
	function viewOf( url ) {
		const view = new URL( url ).searchParams.get( 'view' );

		if ( AUTHOR_VIEW === view ) {
			return idOf( url, 'author' ) ? view : 'books';
		}

		return VIEWS.includes( view ) ? view : 'books';
	}

	/** The ID a URL gives as `name`, such as the author of ?author= or the book of ?book=, or 0. */
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
			// "", "yes" or "no": the search keyword read:.
			read: [ 'yes', 'no' ].includes( saved?.read ) ? saved.read : '',
		};
	}

	/** Goes to the top of a view opened, at once: the smooth scroll of the page is for its links, such as "Top". */
	function scrollToTop() {
		scrollTo( { top: 0, behavior: 'instant' } );
	}

	/** Whether the home shows: the books view, without a query. */
	function isHome( state ) {
		return 'books' === state.view && ! state.query.trim();
	}

	/** The search sent to the API: the query, and the read state as the keyword read:. */
	function searchOf( { query, read } ) {
		return [ query.trim(), read && `read:${ read }` ].filter( Boolean ).join( ' ' );
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

	/** A list loaded by pages: its items so far, and where it stands. */
	function pages( items = [] ) {
		return { items, total: 0, page: 0, pages: 0, loading: false, loaded: false, error: '' };
	}

	function idea() {
		return { id: 0, reason: '', position: 0, total: 0, loading: false, loaded: false, error: '' };
	}

	function achievements() {
		return { earned: [], close: [], loading: false, loaded: false, error: '' };
	}

	/** The saving of the book open. */
	function saving() {
		return { loading: false, error: '' };
	}

	/** The form of a book to add, and its saving. */
	function adding() {
		return { open: false, loading: false, error: '' };
	}

	/** The names the form of a book to add suggests, as terms: authors and pseudonyms, publishers and collections, original languages. */
	function terms() {
		return { authors: [], publishers: [], languages: [], loading: false, loaded: false };
	}

	/**
	 * The author open, under their real name: `books` all their book IDs, in order,
	 * `items` the ones loaded so far, a page at a time; `completion` is an achievement.
	 */
	function author( id = 0 ) {
		return { ...pages(), id, name: '', pseudonyms: [], books: [], read: 0, rating: null, completion: null };
	}

	/**
	 * The app state. Every brick reads and writes it, none knows the others.
	 * `books` holds every book loaded, by ID; the views refer to them by ID:
	 * `search` the books found, `diary` the readings by month, `idea` the reading idea, `author` the books of the author open, `book` the book open,
	 * `saving` the saving of its reading, `adding` the form of a book to add, `terms` the names it suggests.
	 */
	class LibraryStore extends Store {
		#timeout = 0;
		#controller = null;
		#authorController = null;

		constructor() {
			super( {
				...load(),
				books: {},
				search: pages(),
				diary: pages(),
				idea: idea(),
				achievements: achievements(),
				stats: null,
				author: author(),
				book: 0,
				saving: saving(),
				adding: adding(),
				terms: terms(),
			} );

			darkQuery.addEventListener( 'change', ( { matches } ) => {
				this.set( ( state ) =>
					state.themeChosen ? state : { ...state, theme: matches ? 'dark' : 'light' }
				);
			} );

			// Back and forward between the views; they open and close the book too.
			addEventListener( 'popstate', () => {
				this.#show( viewOf( location.href ), idOf( location.href, 'author' ) );
				this.#showBook( idOf( location.href, 'book' ) );
			} );

			this.subscribe( ( state, previous ) => {
				if ( [ ...SAVED, 'query' ].some( ( key ) => state[ key ] !== previous[ key ] ) ) {
					save( state );
				}
			} );

			this.#show( this.get().view, idOf( location.href, 'author' ) );
			this.#showBook( idOf( location.href, 'book' ) );
			this.#fetchStats();
		}

		/** Shows a view, at its top, with its URL in the history. */
		setView( view ) {
			if ( ! VIEWS.includes( view ) || view === this.get().view ) {
				return;
			}

			const url = new URL( location.href );

			if ( 'books' === view ) {
				url.searchParams.delete( 'view' );
			} else {
				url.searchParams.set( 'view', view );
			}

			url.searchParams.delete( 'author' );
			url.searchParams.delete( 'book' );
			history.pushState( null, '', url );
			this.#show( view );
			scrollToTop();
		}

		/**
		 * Opens the page of an author, at its top, with its URL in the history. The book open closes, its URL
		 * staying in the history: going back opens it again. On the page of the author already, it just closes.
		 */
		openAuthor( id ) {
			const { view, author: current } = this.get();

			if ( AUTHOR_VIEW === view && id === current.id ) {
				this.closeBook();
				return;
			}

			const url = new URL( location.href );

			this.#hideBook();
			url.searchParams.set( 'view', AUTHOR_VIEW );
			url.searchParams.set( 'author', id );
			url.searchParams.delete( 'book' );
			history.pushState( null, '', url );
			this.#show( AUTHOR_VIEW, id );
			scrollToTop();
		}

		setQuery( query ) {
			this.set( ( state ) => ( { ...state, query } ) );
			this.#loadHome();

			clearTimeout( this.#timeout );
			this.#timeout = setTimeout( () => this.#fetchSearch(), SEARCH_DELAY );
		}

		setRead( read ) {
			this.set( ( state ) => ( { ...state, read } ) );
			this.#fetchSearch();
		}

		/**
		 * Shows the books a search finds, read or not, at the top of the books view, such as the books of a series
		 * from an achievement. The view comes first, so that the query goes to its URL.
		 */
		searchBooks( query ) {
			this.setView( 'books' );
			this.set( ( state ) => ( { ...state, query, read: '' } ) );
			this.#fetchSearch();
			scrollToTop();
		}

		/** The next books of the search, unless some are loading or none is left. */
		loadMoreBooks() {
			const { loading, items, total } = this.get().search;

			if ( ! loading && items.length < total ) {
				this.#fetchSearch( items.length );
			}
		}

		/** The next page of the books of the author, unless one is loading or none is left. */
		loadMoreAuthorBooks() {
			const { loading, page, pages: last } = this.get().author;

			if ( ! loading && page < last ) {
				this.#fetchAuthorBooks( page + 1 );
			}
		}

		/** The next page of the diary, unless one is loading or none is left. */
		loadMoreReadings() {
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
		 * Opens a book, loaded first when it is not yet, such as the book of an achievement,
		 * with its URL in the history: going back closes it.
		 */
		async openBook( id ) {
			try {
				await this.#fetchBooks( [ id ] );
			} catch {
				return;
			}

			const { books, book } = this.get();

			if ( ! ( id in books ) || id === book ) {
				return;
			}

			const url = new URL( location.href );

			url.searchParams.set( 'book', id );
			history.pushState( { book: id }, '', url );
			this.set( ( state ) => ( { ...state, book: id, saving: saving() } ) );
		}

		/** Closes the book open: back in the history when it was opened there, else its URL loses it, so that the app stays. */
		closeBook() {
			const { book } = this.get();

			if ( ! book ) {
				return;
			}

			this.#hideBook();

			if ( book === history.state?.book ) {
				history.back();
				return;
			}

			const url = new URL( location.href );

			url.searchParams.delete( 'book' );
			history.replaceState( history.state, '', url );
		}

		/** Opens the form of a book to add; the names it suggests load the first time. */
		openAdd() {
			this.set( ( state ) => ( { ...state, adding: { ...adding(), open: true } } ) );

			const { loaded, loading } = this.get().terms;

			if ( ! loaded && ! loading ) {
				this.#fetchTerms();
			}
		}

		closeAdd() {
			this.set( ( state ) => ( state.adding.open ? { ...state, adding: adding() } : state ) );
		}

		/**
		 * Adds a book, the image of its cover uploaded first, then opens it, for its reading. Every view loads
		 * again, so the book comes first among the books, and so do the names to suggest, with the ones it added.
		 */
		async addBook( { cover, ...book } ) {
			this.#update( 'adding', () => ( { loading: true, error: '' } ) );

			try {
				let coverId = 0;

				if ( cover ) {
					const body = new FormData();

					body.append( 'file', cover );
					body.append( 'title', book.title );
					coverId = ( await apiFetch( { path: '/wp/v2/media', method: 'POST', body } ) ).id;
				}

				const { id } = await apiFetch( { path: '/library/v1/books', method: 'POST', data: { ...book, cover: coverId } } );

				await this.#fetchBooks( [ id ] );
				this.closeAdd();
				this.#reloadAll();
				this.openBook( id );
			} catch ( error ) {
				this.#update( 'adding', () => ( { loading: false, error: errorMessage( error ) } ) );
			}
		}

		/**
		 * Saves the reading of the book open, then closes it. Only what changed is sent, so a value the form
		 * cannot show stays as it is; marked as unread, the book loses its read dates on the server.
		 */
		async saveBook( { read, readDates, rating, kept } ) {
			const { book: id, books } = this.get();
			const book = books[ id ];
			const dates = [ ...new Set( readDates ) ].sort();
			const isKept = config.notKept !== book.status;
			const meta = {};
			let status = book.status;

			// Kept again, a book not kept is published, as with the "Not kept" box of the book form.
			if ( kept !== isKept ) {
				status = kept ? 'publish' : config.notKept;
			}

			if ( read !== book.read ) {
				meta.read = read;
			}

			if ( read && dates.join() !== book.read_date.join() ) {
				meta.read_date = dates;
			}

			if ( rating !== ( book.rating || null ) ) {
				meta.rating = rating;
			}

			if ( ! Object.keys( meta ).length && status === book.status ) {
				this.closeBook();
				return;
			}

			const params = new URLSearchParams( BOOK_QUERY );
			const data = status === book.status ? { meta } : { meta, status };

			this.#update( 'saving', () => ( { loading: true, error: '' } ) );

			try {
				const item = await apiFetch( { path: `/wp/v2/books/${ id }?${ params }`, method: 'POST', data } );

				this.#addBooks( [ item ] );
				this.closeBook();
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

		/** The book open closes, the history left as it is. */
		#hideBook() {
			this.set( ( state ) => ( state.book ? { ...state, book: 0, saving: saving() } : state ) );
		}

		/** Shows the book `id` of the URL, loaded first when it is not yet; 0 closes the book open. */
		async #showBook( id ) {
			if ( ! id ) {
				this.#hideBook();
				return;
			}

			try {
				await this.#fetchBooks( [ id ] );
			} catch {
				return;
			}

			// The URL may have changed meanwhile, and a book not found does not open.
			if ( id === idOf( location.href, 'book' ) && id in this.get().books ) {
				this.set( ( state ) => ( { ...state, book: id, saving: saving() } ) );
			}
		}

		/** Shows a view, and loads its data the first time; the page of an author starts again for another author `id`. */
		#show( view, id = 0 ) {
			this.set( ( state ) => {
				const next = state.view === view ? state : { ...state, view };

				return AUTHOR_VIEW !== view || id === state.author.id ? next : { ...next, author: author( id ) };
			} );
			this.#load( view );
			this.#loadHome();
		}

		/** Loads what the home shows, when it shows. */
		#loadHome() {
			if ( isHome( this.get() ) ) {
				HOME.forEach( ( view ) => this.#load( view ) );
			}
		}

		/** Loads the data of a view, unless they are loaded or loading. */
		#load( view ) {
			const state = this.get();
			const loaders = {
				books: [ state.search, () => this.#fetchSearch() ],
				diary: [ state.diary, () => this.#fetchDiary( 1 ) ],
				idea: [ state.idea, () => this.#fetchIdea( 0 ) ],
				achievements: [ state.achievements, () => this.#fetchAchievements() ],
				[ AUTHOR_VIEW ]: [ state.author, () => this.#fetchAuthor() ],
			};
			const [ data, fetchData ] = loaders[ view ];

			if ( ! data.loaded && ! data.loading ) {
				fetchData();
			}
		}

		/**
		 * After the book `id` was saved: the stats, and the views but the one shown, load again. The list shown follows
		 * the book where it is: the books found lose it when the search no longer finds it, the diary takes its readings
		 * added or removed, the page of an author updates their reading, the home its readings and achievements;
		 * elsewhere its card follows it.
		 */
		#reload( id ) {
			const { view } = this.get();
			const home = isHome( this.get() );

			this.set( ( state ) => ( {
				...state,
				search: 'books' === view ? state.search : pages(),
				diary: 'diary' === view || home ? state.diary : pages(),
				idea: 'idea' === view || home ? state.idea : idea(),
				achievements: home ? state.achievements : achievements(),
				author: AUTHOR_VIEW === view ? state.author : author(),
			} ) );
			this.#fetchStats();

			if ( 'books' === view ) {
				this.#refreshSearch( id );
			}

			if ( 'diary' === view || home ) {
				this.#refreshDiary();
			}

			if ( home ) {
				this.#fetchAchievements();
			}

			if ( AUTHOR_VIEW === view ) {
				this.#fetchAuthor();
			}
		}

		/** After a book was added: the stats, every view and the names to suggest load again; the view shown at once. */
		#reloadAll() {
			const { view, author: current } = this.get();

			this.set( ( state ) => ( {
				...state,
				search: pages(),
				diary: pages(),
				idea: idea(),
				achievements: achievements(),
				author: author( current.id ),
				terms: terms(),
			} ) );
			this.#fetchStats();
			this.#show( view, current.id );
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

		/** Loads the books not loaded yet among `ids`. */
		async #fetchBooks( ids, signal ) {
			const { books } = this.get();
			const missing = [ ...new Set( ids ) ].filter( ( id ) => ! ( id in books ) );

			if ( ! missing.length ) {
				return;
			}

			const params = new URLSearchParams( {
				...BOOK_QUERY,
				status: config.statuses,
				include: missing.join( ',' ),
				per_page: missing.length,
			} );

			this.#addBooks( await apiFetch( { path: `/wp/v2/books?${ params }`, signal } ) );
		}

		/**
		 * The books of the search from `offset`: from 0 they replace the books found, from further they add to them.
		 * An offset rather than a page, as a book may have left the list. A new search cancels the one running.
		 */
		async #fetchSearch( offset = 0 ) {
			clearTimeout( this.#timeout );
			this.#controller?.abort();
			this.#controller = new AbortController();

			const { signal } = this.#controller;
			const search = searchOf( this.get() );
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				status: config.statuses,
				per_page: PER_PAGE,
				offset,
			} );

			if ( search ) {
				params.set( 'search', search );
			}

			this.#update( 'search', () => ( { loading: true, error: '' } ) );

			try {
				const response = await apiFetch( { path: `/wp/v2/books?${ params }`, parse: false, signal } );
				const ids = this.#addBooks( await response.json() );

				this.#update( 'search', ( current ) => ( {
					items: offset ? [ ...current.items, ...ids ] : ids,
					total: Number( response.headers.get( 'X-WP-Total' ) ),
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'search', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}
			}
		}

		/** Takes the book `id` out of the books found when the search no longer finds it, such as a book read with the filter "To read". */
		async #refreshSearch( id ) {
			const search = searchOf( this.get() );

			// Every book is found without a search.
			if ( ! search ) {
				return;
			}

			const params = new URLSearchParams( { search, status: config.statuses, include: id, _fields: 'id' } );

			try {
				const found = await apiFetch( { path: `/wp/v2/books?${ params }` } );
				// Another search may have replaced the books found meanwhile.
				const isListed = search === searchOf( this.get() ) && this.get().search.items.includes( id );

				if ( ! found.length && isListed ) {
					this.#update( 'search', ( current ) => ( {
						items: current.items.filter( ( item ) => item !== id ),
						total: current.total - 1,
					} ) );
				}
			} catch {}
		}

		/** A page of the diary: whole months, latest first, each with its readings and their books; the totals count months. */
		async #fetchReadings( page ) {
			const response = await apiFetch( { path: `/library/v1/books/readings?page=${ page }`, parse: false } );
			const months = ( await response.json() ).map( ( { month, readings } ) => ( {
				month,
				readings: readings.map( ( { date, book } ) => ( { date, id: book } ) ),
			} ) );

			await this.#fetchBooks( months.flatMap( ( { readings } ) => readings.map( ( reading ) => reading.id ) ) );

			return {
				months,
				total: Number( response.headers.get( 'X-WP-Total' ) ),
				pages: Number( response.headers.get( 'X-WP-TotalPages' ) ),
			};
		}

		/** The next page of the diary. */
		async #fetchDiary( page ) {
			this.#update( 'diary', () => ( { loading: true, error: '' } ) );

			try {
				const { months, total, pages: last } = await this.#fetchReadings( page );

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

		/** The author open, then the first page of their books, unless it is loaded already. Another author cancels the one loading. */
		async #fetchAuthor() {
			this.#authorController?.abort();
			this.#authorController = new AbortController();

			const { signal } = this.#authorController;

			this.#update( 'author', () => ( { loading: true, error: '' } ) );

			try {
				const data = await apiFetch( { path: `/library/v1/authors/${ this.get().author.id }`, signal } );

				this.#update( 'author', () => ( {
					name: text( data.name ),
					pseudonyms: data.pseudonyms.map( text ),
					books: data.books,
					read: data.read,
					rating: data.rating,
					completion: data.completion,
					total: data.books.length,
					pages: Math.ceil( data.books.length / PER_PAGE ),
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'author', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}

				return;
			}

			if ( ! this.get().author.page ) {
				await this.#fetchAuthorBooks( 1 );
			}
		}

		/** A page of the books of the author open, from their IDs. */
		async #fetchAuthorBooks( page ) {
			const { signal } = this.#authorController;
			const ids = this.get().author.books.slice( ( page - 1 ) * PER_PAGE, page * PER_PAGE );

			this.#update( 'author', () => ( { loading: true, error: '' } ) );

			try {
				await this.#fetchBooks( ids, signal );

				this.#update( 'author', ( current ) => ( {
					items: [ ...current.items, ...ids ],
					page,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'author', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
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

		/** Every author and pseudonym, publisher and collection, and original language: apiFetch loads all their pages. */
		async #fetchTerms() {
			this.#update( 'terms', () => ( { loading: true } ) );

			try {
				const taxonomies = [ 'library-author', 'library-publisher', 'library-language' ];
				const [ authors, publishers, languages ] = await Promise.all(
					taxonomies.map( ( taxonomy ) => apiFetch( { path: `/wp/v2/${ taxonomy }?per_page=-1&_fields=id,name,parent` } ) )
				);
				const toTerm = ( { id, name, parent } ) => ( { id, name: text( name ), parent } );

				this.#update( 'terms', () => ( {
					authors: authors.map( toTerm ),
					publishers: publishers.map( toTerm ),
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
} )();
