/**
 * Store of the reading app: the state every brick reads, and the actions that change it.
 *
 * Created in the head, before the first paint, so the document takes its theme from it.
 * The data come from the REST API through wp.apiFetch, which handles the root URL and the nonce;
 * the store turns the books into plain text once, so no brick parses what the API returns.
 * Each view loads its data the first time it shows.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, libraryApp
 */
( () => {
	'use strict';

	const STORAGE_KEY = 'library-app';
	// Kept between visits. The query, the view and the author open are in the URL only, as ?q=, ?view= and ?author=.
	const SAVED = [ 'theme', 'themeChosen', 'read' ];
	// The views of the navigation; the page of an author is another one, opened from a book or an achievement.
	const VIEWS = [ 'books', 'diary', 'idea', 'achievements' ];
	const AUTHOR_VIEW = 'author';
	const PER_PAGE = 24;
	// A search waits for a pause in the typing.
	const SEARCH_DELAY = 250;

	const { apiFetch } = window.wp;
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

	/** A book of the API as the app shows it: plain text, its cover, its authors and its original languages. */
	function toBook( item ) {
		const media = item._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
		const sizes = media?.media_details?.sizes ?? {};
		const terms = ( item._embedded?.[ 'wp:term' ] ?? [] ).flat();

		return {
			...item.meta,
			id: item.id,
			title: text( item.title.rendered ),
			credits: text( item.credits ),
			status: item.status,
			cover: ( sizes.medium ?? sizes.full )?.source_url ?? '',
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
		return error?.message ?? config.strings.error;
	}

	/** The view of a URL, the books by default; the page of an author needs an author. */
	function viewOf( url ) {
		const view = new URL( url ).searchParams.get( 'view' );

		if ( AUTHOR_VIEW === view ) {
			return authorOf( url ) ? view : 'books';
		}

		return VIEWS.includes( view ) ? view : 'books';
	}

	/** The author of a URL, as a term ID, or 0. */
	function authorOf( url ) {
		const id = Number( new URL( url ).searchParams.get( 'author' ) );

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
	 * `search` the books found, `diary` the readings, `idea` the reading idea, `author` the books of the author open, `book` the book open.
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
				idea: { id: 0, reason: '', position: 0, total: 0, loading: false, loaded: false, error: '' },
				achievements: { earned: [], close: [], loading: false, loaded: false, error: '' },
				stats: null,
				author: author(),
				book: 0,
			} );

			darkQuery.addEventListener( 'change', ( { matches } ) => {
				this.set( ( state ) =>
					state.themeChosen ? state : { ...state, theme: matches ? 'dark' : 'light' }
				);
			} );

			// Back and forward between the views.
			addEventListener( 'popstate', () => this.#show( viewOf( location.href ), authorOf( location.href ) ) );

			this.subscribe( ( state, previous ) => {
				if ( [ ...SAVED, 'query' ].some( ( key ) => state[ key ] !== previous[ key ] ) ) {
					save( state );
				}
			} );

			this.#show( this.get().view, authorOf( location.href ) );
			this.#fetchStats();
		}

		/** Shows a view, with its URL in the history. */
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
			history.pushState( null, '', url );
			this.#show( view );
		}

		/** Opens the page of an author, at its top, with its URL in the history: the book open closes. */
		openAuthor( id ) {
			const { view, author: current } = this.get();

			this.closeBook();

			if ( AUTHOR_VIEW === view && id === current.id ) {
				return;
			}

			const url = new URL( location.href );

			url.searchParams.set( 'view', AUTHOR_VIEW );
			url.searchParams.set( 'author', id );
			history.pushState( null, '', url );
			this.#show( AUTHOR_VIEW, id );
			scrollTo( 0, 0 );
		}

		setQuery( query ) {
			this.set( ( state ) => ( { ...state, query } ) );

			clearTimeout( this.#timeout );
			this.#timeout = setTimeout( () => this.#fetchSearch( 1 ), SEARCH_DELAY );
		}

		setRead( read ) {
			this.set( ( state ) => ( { ...state, read } ) );
			this.#fetchSearch( 1 );
		}

		/** The next page of the search, unless one is loading or none is left. */
		loadMoreBooks() {
			const { loading, page, pages: last } = this.get().search;

			if ( ! loading && page < last ) {
				this.#fetchSearch( page + 1 );
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

		openBook( id ) {
			this.set( ( state ) => ( id in state.books ? { ...state, book: id } : state ) );
		}

		closeBook() {
			this.set( ( state ) => ( state.book ? { ...state, book: 0 } : state ) );
		}

		toggleTheme() {
			this.set( ( state ) => ( {
				...state,
				theme: 'dark' === state.theme ? 'light' : 'dark',
				themeChosen: true,
			} ) );
		}

		/** Shows a view, and loads its data the first time; the page of an author starts again for another author `id`. */
		#show( view, id = 0 ) {
			this.set( ( state ) => {
				const next = state.view === view ? state : { ...state, view };

				return AUTHOR_VIEW !== view || id === state.author.id ? next : { ...next, author: author( id ) };
			} );

			const state = this.get();
			const loaders = {
				books: [ state.search, () => this.#fetchSearch( 1 ) ],
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

		/** A page of the search; the first one replaces the books found, the next ones add to them. A new search cancels the one running. */
		async #fetchSearch( page ) {
			clearTimeout( this.#timeout );
			this.#controller?.abort();
			this.#controller = new AbortController();

			const { signal } = this.#controller;
			const { query, read } = this.get();
			const search = [ query.trim(), read && `read:${ read }` ].filter( Boolean ).join( ' ' );
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				status: config.statuses,
				per_page: PER_PAGE,
				page,
			} );

			if ( search ) {
				params.set( 'search', search );
			}

			this.#update( 'search', () => ( { loading: true, error: '' } ) );

			try {
				const response = await apiFetch( { path: `/wp/v2/books?${ params }`, parse: false, signal } );
				const ids = this.#addBooks( await response.json() );

				this.#update( 'search', ( current ) => ( {
					items: 1 === page ? ids : [ ...current.items, ...ids ],
					total: Number( response.headers.get( 'X-WP-Total' ) ),
					pages: Number( response.headers.get( 'X-WP-TotalPages' ) ),
					page,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'search', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}
			}
		}

		/** A page of the readings, latest first, with their books. */
		async #fetchDiary( page ) {
			this.#update( 'diary', () => ( { loading: true, error: '' } ) );

			try {
				const response = await apiFetch( { path: `/library/v1/books/readings?page=${ page }`, parse: false } );
				const readings = ( await response.json() ).map( ( { date, book } ) => ( { date, id: book } ) );

				await this.#fetchBooks( readings.map( ( reading ) => reading.id ) );

				this.#update( 'diary', ( current ) => ( {
					items: [ ...current.items, ...readings ],
					total: Number( response.headers.get( 'X-WP-Total' ) ),
					pages: Number( response.headers.get( 'X-WP-TotalPages' ) ),
					page,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				this.#update( 'diary', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
			}
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

		/** The author open, then the first page of their books. Another author cancels the one loading. */
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
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'author', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}

				return;
			}

			await this.#fetchAuthorBooks( 1 );
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
