/**
 * Store of the reading app: the state every brick reads, and the actions that change it.
 *
 * Created in the head, before the first paint, so the document takes its theme from it.
 * The data come from the REST API through wp.apiFetch, which handles the root URL and the nonce;
 * the store turns the books into plain text once, so no brick parses what the API returns.
 * Each view loads its data the first time it shows. A book saved, its reading or its form, loads every view again,
 * but the one shown; a book added loads them all again.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, libraryApp
 */
( () => {
	'use strict';

	const STORAGE_KEY = 'library-app';
	// Kept between visits. The query, the view, the person and the book open are in the URL only, as ?q=, ?view=, ?person= and ?book=.
	const SAVED = [ 'theme', 'themeChosen', 'read', 'status' ];
	// The views of the navigation; the page of a person is another one, opened from a book or an achievement.
	const VIEWS = [ 'books', 'diary', 'idea', 'achievements', 'to-buy' ];
	const PERSON_VIEW = 'person';
	// The components of the app, for development: a view out of the navigation, opened by searching its name.
	const STYLEGUIDE_VIEW = 'styleguide';
	// The home, the books view without a query, shows the latest readings, the reading idea and the achievements close to be earned.
	const HOME = [ 'diary', 'idea', 'achievements' ];
	const PER_PAGE = 24;
	// A search waits for a pause in the typing.
	const SEARCH_DELAY = 250;

	const { apiFetch } = window.wp;
	const { __ } = window.wp.i18n;
	const { addQueryArgs } = window.wp.url;
	const config = window.libraryApp;
	const $root = document.documentElement;
	const $themeColor = document.querySelector( 'meta[name="theme-color"]' );
	const $favicon = document.querySelector( 'link[rel="icon"]' );
	const darkQuery = matchMedia( '(prefers-color-scheme: dark)' );
	const parser = new DOMParser();

	// The fields of a book the app reads, and its cover and terms.
	const BOOK_QUERY = {
		_embed: 'wp:featuredmedia,wp:term',
		_fields: 'id,title,excerpt,content,credits,status,meta,_links,_embedded',
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

	/** A book of the API as the app shows it: plain text but its notes, its meta by their names, its read dates in order, its cover with its size, its authors and the names with another role, and its original languages. */
	function toBook( item ) {
		const media = item._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ];
		const details = media?.media_details;
		// The image itself when the server made no size of it, as for an AVIF it cannot resize: the API then gives no full size either.
		const original = media?.source_url && { source_url: media.source_url, width: details.width, height: details.height };
		const size = details?.sizes?.medium ?? original;
		const terms = ( item._embedded?.[ 'wp:term' ] ?? [] ).flat();
		// Its meta without the prefix of their keys: library_rating is its rating.
		const meta = Object.fromEntries( Object.entries( item.meta ).map( ( [ key, value ] ) => [ key.replace( /^library_/, '' ), value ] ) );
		// As named on the book: a pseudonym opens the page of the person too.
		const names = terms
			.filter( ( term ) => 'library_person' === term.taxonomy )
			.map( ( term ) => ( { id: term.id, name: text( term.name ) } ) );
		const roleIds = config.roles.flatMap( ( role ) => meta[ role ] );

		return {
			...meta,
			// A date stored empty comes back as null.
			read_date: [ ...new Set( meta.read_date.filter( Boolean ) ) ].sort(),
			id: item.id,
			title: text( item.title.rendered ),
			excerpt: text( item.excerpt.rendered ),
			// HTML as the API renders it: its paragraphs and links.
			content: item.content.rendered,
			credits: text( item.credits ),
			status: item.status,
			// Its size gives the image its place before it loads.
			cover: size ? { url: size.source_url, width: size.width, height: size.height } : null,
			// Its names without another role, then the others by role, such as its colorists, in place of their IDs.
			authors: names.filter( ( name ) => ! roleIds.includes( name.id ) ),
			...Object.fromEntries( config.roles.map( ( role ) => [ role, names.filter( ( name ) => meta[ role ].includes( name.id ) ) ] ) ),
			// As their ISO 639 codes, the slugs of their terms: the app names them in a sentence.
			languages: terms.filter( ( term ) => 'library_language' === term.taxonomy ).map( ( term ) => term.slug ),
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

	/** The view of a URL, the books by default; the page of a person needs a person. */
	function viewOf( url ) {
		const view = new URL( url ).searchParams.get( 'view' );

		if ( PERSON_VIEW === view ) {
			return idOf( url, 'person' ) ? view : 'books';
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
			// "", "yes" or "no": the search keyword read:.
			read: [ 'yes', 'no' ].includes( saved?.read ) ? saved.read : '',
			// "" for any, or the status of the books shown, such as not kept.
			status: config.statuses.split( ',' ).includes( saved?.status ) ? saved.status : '',
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

	/**
	 * What a list asks the API for: `search` the books the search finds, of the status chosen, else of any status but
	 * to buy for the books to read, which are not at hand; `toBuy` the books to buy.
	 */
	function queryOf( list, state ) {
		if ( 'toBuy' === list ) {
			return { status: config.toBuy };
		}

		const search = searchOf( state );
		const atHand = config.statuses.split( ',' ).filter( ( item ) => config.toBuy !== item ).join();
		const status = state.status || ( 'no' === state.read ? atHand : config.statuses );

		return search ? { search, status } : { status };
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

	/** The saving of the reading of the book open. */
	function saving() {
		return { loading: false, error: '' };
	}

	/** The form of a book, to add one or to edit the book `id`, whose values it holds as `book`, and its saving. */
	function editing( id = 0, book = null ) {
		return { id, book, open: false, loading: false, error: '' };
	}

	/** The names the form of a book suggests, as terms: people and pseudonyms, publishers and collections, original languages. */
	function terms() {
		return { people: [], publishers: [], languages: [], loading: false, loaded: false };
	}

	/**
	 * The person open, under their real name: `books` all their book IDs, in order,
	 * `items` the ones loaded so far, a page at a time; `bookPages` the pages of their books,
	 * as `pages` counts the pages of the list; `completion` is an achievement; `description` is HTML.
	 */
	function person( id = 0 ) {
		return { ...pages(), id, name: '', pseudonyms: [], description: '', books: [], read: 0, rating: null, bookPages: 0, completion: null };
	}

	/**
	 * The app state. Every brick reads and writes it, none knows the others.
	 * `books` holds every book loaded, by ID; the views refer to them by ID:
	 * `search` the books found, `diary` the readings by month, `idea` the reading idea, `toBuy` the books to buy, latest added first,
	 * `person` the books of the person open, `book` the book open,
	 * `saving` the saving of its reading, `editing` the form of a book, to add or edit, `terms` the names it suggests.
	 */
	class LibraryStore extends Store {
		#timeout = 0;
		// The request of each list running, `search` or `toBuy`: another one cancels it.
		#controllers = {};
		#personController = null;

		constructor() {
			super( {
				...load(),
				books: {},
				search: pages(),
				diary: pages(),
				idea: idea(),
				achievements: achievements(),
				toBuy: pages(),
				stats: null,
				person: person(),
				book: 0,
				saving: saving(),
				editing: editing(),
				terms: terms(),
			} );

			darkQuery.addEventListener( 'change', ( { matches } ) => {
				this.set( ( state ) =>
					state.themeChosen ? state : { ...state, theme: matches ? 'dark' : 'light' }
				);
			} );

			// Back and forward between the views; they open and close the book too.
			addEventListener( 'popstate', () => {
				this.#show( viewOf( location.href ), idOf( location.href, 'person' ) );
				this.#showBook( idOf( location.href, 'book' ) );
			} );

			this.subscribe( ( state, previous ) => {
				if ( [ ...SAVED, 'query' ].some( ( key ) => state[ key ] !== previous[ key ] ) ) {
					save( state );
				}
			} );

			this.#show( this.get().view, idOf( location.href, 'person' ) );
			this.#showBook( idOf( location.href, 'book' ) );
			this.#fetchStats();
		}

		/** Shows a view, at its top, with its URL in the history. */
		setView( view ) {
			if ( ! isNamedView( view ) || view === this.get().view ) {
				return;
			}

			const url = new URL( location.href );

			if ( 'books' === view ) {
				url.searchParams.delete( 'view' );
			} else {
				url.searchParams.set( 'view', view );
			}

			url.searchParams.delete( 'person' );
			url.searchParams.delete( 'book' );
			history.pushState( null, '', url );
			this.#show( view );
			scrollToTop();
		}

		/**
		 * Opens the page of a person, at its top, with its URL in the history. The book open closes, its URL
		 * staying in the history: going back opens it again. On the page of the person already, it just closes.
		 */
		openPerson( id ) {
			const { view, person: current } = this.get();

			if ( PERSON_VIEW === view && id === current.id ) {
				this.closeBook();
				return;
			}

			const url = new URL( location.href );

			this.#hideBook();
			url.searchParams.set( 'view', PERSON_VIEW );
			url.searchParams.set( 'person', id );
			url.searchParams.delete( 'book' );
			history.pushState( null, '', url );
			this.#show( PERSON_VIEW, id );
			scrollToTop();
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

		/**
		 * Shows the books a search finds, read or not, of any status, at the top of the books view, such as the books
		 * of a series from an achievement. The view comes first, so that the query goes to its URL.
		 */
		searchBooks( query ) {
			this.setView( 'books' );
			this.set( ( state ) => ( { ...state, query, read: '', status: '' } ) );
			this.#fetchList( 'search' );
			scrollToTop();
		}

		/** The next books of a list, `search` or `toBuy`, unless some are loading or none is left. */
		loadMoreBooks( list ) {
			const { loading, items, total } = this.get()[ list ];

			if ( ! loading && items.length < total ) {
				this.#fetchList( list, items.length );
			}
		}

		/** The next page of the books of the person, unless one is loading or none is left. */
		loadMorePersonBooks() {
			const { loading, page, pages: last } = this.get().person;

			if ( ! loading && page < last ) {
				this.#fetchPersonBooks( page + 1 );
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
		 * Saves the reading of the book open, then closes it. Only what changed is sent, so a value the form
		 * cannot show stays as it is; marked as unread, the book loses its read dates on the server.
		 */
		async saveReading( { read, readDates, rating, status: chosen } ) {
			const { book: id, books } = this.get();
			const book = books[ id ];
			const dates = [ ...new Set( readDates ) ].sort();
			// As in the "Library" menu of the book form, a book in another status than not kept or to buy, such as a draft,
			// shows as in the library, and stays in it unless another one is chosen.
			const shown = [ config.notKept, config.toBuy ].includes( book.status ) ? book.status : 'publish';
			const status = chosen === shown ? book.status : chosen;
			const meta = {};

			if ( read !== book.read ) {
				meta.library_read = read;
			}

			if ( read && dates.join() !== book.read_date.join() ) {
				meta.library_read_date = dates;
			}

			if ( rating !== ( book.rating || null ) ) {
				meta.library_rating = rating;
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

		/** Uploads the photo of a cover, named and titled after its book as its form sends it, and returns the ID of its image. */
		async #uploadCover( file, { title, people, meta } ) {
			const query = { title, people, meta, photo: file.name };
			const { name } = await apiFetch( { path: addQueryArgs( '/library/v1/books/cover-name', query ) } );
			const body = new FormData();

			body.append( 'file', file, name );
			body.append( 'title', title );

			return ( await apiFetch( { path: '/wp/v2/media', method: 'POST', body } ) ).id;
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

		/** Shows a view, and loads its data the first time; the page of a person starts again for another person `id`. */
		#show( view, id = 0 ) {
			this.set( ( state ) => {
				const next = state.view === view ? state : { ...state, view };

				return PERSON_VIEW !== view || id === state.person.id ? next : { ...next, person: person( id ) };
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

		/** Loads the data of a view, unless they are loaded or loading; the styleguide has none, its samples are written in it. */
		#load( view ) {
			if ( STYLEGUIDE_VIEW === view ) {
				return;
			}

			const state = this.get();
			const loaders = {
				books: [ state.search, () => this.#fetchList( 'search' ) ],
				diary: [ state.diary, () => this.#fetchDiary( 1 ) ],
				idea: [ state.idea, () => this.#fetchIdea( 0 ) ],
				achievements: [ state.achievements, () => this.#fetchAchievements() ],
				'to-buy': [ state.toBuy, () => this.#fetchList( 'toBuy' ) ],
				[ PERSON_VIEW ]: [ state.person, () => this.#fetchPerson() ],
			};
			const [ data, fetchData ] = loaders[ view ];

			if ( ! data.loaded && ! data.loading ) {
				fetchData();
			}
		}

		/**
		 * After the book `id` was saved: the stats, and the views but the one shown, load again. The list shown follows
		 * the book where it is: the books found lose it when the search no longer finds it, the books to buy when it is
		 * bought, the diary takes its readings added or removed, the page of a person updates their books and their reading,
		 * the achievements load again, and the home its readings and achievements; elsewhere its card follows it.
		 */
		#reload( id ) {
			const { view } = this.get();
			const home = isHome( this.get() );

			this.set( ( state ) => ( {
				...state,
				search: 'books' === view ? state.search : pages(),
				toBuy: 'to-buy' === view ? state.toBuy : pages(),
				diary: 'diary' === view || home ? state.diary : pages(),
				idea: 'idea' === view || home ? state.idea : idea(),
				achievements: 'achievements' === view || home ? state.achievements : achievements(),
				person: PERSON_VIEW === view ? state.person : person(),
			} ) );
			this.#fetchStats();

			if ( 'books' === view ) {
				this.#refreshList( 'search', id );
			}

			if ( 'to-buy' === view ) {
				this.#refreshList( 'toBuy', id );
			}

			if ( 'diary' === view || home ) {
				this.#refreshDiary();
			}

			if ( 'achievements' === view || home ) {
				this.#fetchAchievements();
			}

			if ( PERSON_VIEW === view ) {
				this.#fetchPerson();
			}
		}

		/** After a book was added: the stats, every view and the names to suggest load again; the view shown at once. */
		#reloadAll() {
			const { view, person: current } = this.get();

			this.set( ( state ) => ( {
				...state,
				search: pages(),
				diary: pages(),
				idea: idea(),
				achievements: achievements(),
				toBuy: pages(),
				person: person( current.id ),
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
		 * The books of a list from `offset`, `search` or `toBuy`: from 0 they replace the books of the list, from further they
		 * add to them. An offset rather than a page, as a book may have left the list. A new request of the list cancels the
		 * one running; for the search, it cancels the one waiting for a pause in the typing.
		 */
		async #fetchList( list, offset = 0 ) {
			if ( 'search' === list ) {
				clearTimeout( this.#timeout );
			}

			this.#controllers[ list ]?.abort();
			this.#controllers[ list ] = new AbortController();

			const { signal } = this.#controllers[ list ];
			const params = new URLSearchParams( {
				...BOOK_QUERY,
				...queryOf( list, this.get() ),
				per_page: PER_PAGE,
				offset,
			} );

			this.#update( list, () => ( { loading: true, error: '' } ) );

			try {
				const response = await apiFetch( { path: `/wp/v2/books?${ params }`, parse: false, signal } );
				const ids = this.#addBooks( await response.json() );

				this.#update( list, ( current ) => ( {
					items: offset ? [ ...current.items, ...ids ] : ids,
					total: Number( response.headers.get( 'X-WP-Total' ) ),
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( list, () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}
			}
		}

		/**
		 * Takes the book `id` out of a list when it no longer finds it, such as a book read with the filter "To read",
		 * a book bought with the filter "To buy", or a book to buy bought.
		 */
		async #refreshList( list, id ) {
			const query = queryOf( list, this.get() );

			// Every book is found without a search or a status chosen.
			if ( 'search' === list && ! query.search && ! this.get().status ) {
				return;
			}

			const params = new URLSearchParams( { ...query, include: id, _fields: 'id' } );

			try {
				const found = await apiFetch( { path: `/wp/v2/books?${ params }` } );
				// Another search or another status may have replaced the books found meanwhile.
				const { search, status } = queryOf( list, this.get() );
				const isListed = search === query.search && status === query.status && this.get()[ list ].items.includes( id );

				if ( ! found.length && isListed ) {
					this.#update( list, ( current ) => ( {
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

		/** The person open, then the first page of their books, unless it is loaded already. Another person cancels the one loading. */
		async #fetchPerson() {
			this.#personController?.abort();
			this.#personController = new AbortController();

			const { signal } = this.#personController;

			this.#update( 'person', () => ( { loading: true, error: '' } ) );

			try {
				const data = await apiFetch( { path: `/library/v1/people/${ this.get().person.id }`, signal } );

				this.#update( 'person', ( current ) => ( {
					// A book saved without the person leaves their books.
					items: current.items.filter( ( book ) => data.books.includes( book ) ),
					name: text( data.name ),
					pseudonyms: data.pseudonyms.map( text ),
					description: data.description,
					books: data.books,
					read: data.read,
					rating: data.rating,
					bookPages: data.pages,
					completion: data.completion,
					total: data.books.length,
					pages: Math.ceil( data.books.length / PER_PAGE ),
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'person', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
				}

				return;
			}

			if ( ! this.get().person.page ) {
				await this.#fetchPersonBooks( 1 );
			}
		}

		/** A page of the books of the person open, from their IDs. */
		async #fetchPersonBooks( page ) {
			const { signal } = this.#personController;
			const ids = this.get().person.books.slice( ( page - 1 ) * PER_PAGE, page * PER_PAGE );

			this.#update( 'person', () => ( { loading: true, error: '' } ) );

			try {
				await this.#fetchBooks( ids, signal );

				this.#update( 'person', ( current ) => ( {
					items: [ ...current.items, ...ids ],
					page,
					loading: false,
					loaded: true,
				} ) );
			} catch ( error ) {
				if ( 'AbortError' !== error?.name ) {
					this.#update( 'person', () => ( { loading: false, loaded: true, error: errorMessage( error ) } ) );
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

		/** Every person and pseudonym, publisher and collection, and original language: apiFetch loads all their pages. */
		async #fetchTerms() {
			this.#update( 'terms', () => ( { loading: true } ) );

			try {
				const bases = [ 'people', 'publishers', 'languages' ];
				const [ people, publishers, languages ] = await Promise.all(
					bases.map( ( base ) => apiFetch( { path: `/wp/v2/${ base }?per_page=-1&_fields=id,name,parent` } ) )
				);
				const toTerm = ( { id, name, parent } ) => ( { id, name: text( name ), parent } );

				this.#update( 'terms', () => ( {
					people: people.map( toTerm ),
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
