/**
 * Bricks of the reading app: custom elements that read the store with select() and change it through its actions.
 *
 * No brick knows another, so removing one leaves the others working. Text from the API is set with
 * textContent, in clones of the templates of the page, which hold the translated labels.
 *
 * @package    Library
 * @subpackage Library/app
 * @global     wp, store, libraryApp
 */
( () => {
	'use strict';

	const { store } = window;
	const { sprintf } = window.wp.i18n;
	const { strings, editUrl, authorUrl, notKept } = window.libraryApp;

	// The next page loads before the end of the list shows.
	const LOAD_MARGIN_PX = 800;

	const { lang } = document.documentElement;
	const numberFormat = new Intl.NumberFormat( lang );
	const pluralRules = new Intl.PluralRules( lang );
	const dateFormat = new Intl.DateTimeFormat( lang, { dateStyle: 'long' } );
	const dayFormat = new Intl.DateTimeFormat( lang, { day: 'numeric', month: 'long' } );
	const monthFormat = new Intl.DateTimeFormat( lang, { year: 'numeric', month: 'long' } );
	const listFormat = new Intl.ListFormat( lang, { type: 'unit' } );

	function formatNumber( number ) {
		return numberFormat.format( number );
	}

	/** The form of `forms` for `number`, with its number: in French, 0 takes the singular too. */
	function plural( number, forms ) {
		return sprintf( forms[ pluralRules.select( number ) ] ?? forms.other, formatNumber( number ) );
	}

	/** A date of the API: "2024-05-12", or "1929-11" and "1929" for a date first published. */
	function formatDate( date, format = dateFormat ) {
		const [ year, month, day ] = date.split( '-' );

		if ( day ) {
			return format.format( new Date( `${ date }T00:00` ) );
		}

		return month ? monthFormat.format( new Date( Number( year ), Number( month ) - 1 ) ) : year;
	}

	function formatRating( rating ) {
		return rating ? sprintf( strings.rating, formatNumber( rating ) ) : '';
	}

	/** Its rating, and whether it is kept: the end of the state of a book. */
	function formatDetails( book ) {
		return [ formatRating( book.rating ), notKept === book.status && strings.notKept ];
	}

	/** Whether a book is read, its rating, and whether it is kept. */
	function formatState( book ) {
		return join( [ book.read ? strings.read : strings.toRead, ...formatDetails( book ) ] );
	}

	function join( parts ) {
		return parts.filter( Boolean ).join( ' · ' );
	}

	/** Sets the text of the [data-field] elements of `$root`; an empty one is hidden, with its [data-row]. */
	function fill( $root, fields ) {
		Object.entries( fields ).forEach( ( [ name, value ] ) => {
			const $field = $root.querySelector( `[data-field="${ name }"]` );
			const $row = $field.closest( '[data-row]' ) ?? $field;

			$field.textContent = value;
			$row.hidden = ! value;
		} );
	}

	/** Turns the text of `$field` into a link, when there is a URL. */
	function link( $field, url ) {
		if ( ! url || ! $field.textContent ) {
			return;
		}

		const $link = document.createElement( 'a' );

		$link.href = url;
		$link.textContent = $field.textContent;
		$field.replaceChildren( $link );
	}

	/** A link to the page of an author, which the app opens without reloading. */
	function authorLink( id, name ) {
		const $link = document.createElement( 'a' );

		$link.href = authorUrl + id;
		$link.dataset.author = id;
		$link.textContent = name;

		return $link;
	}

	/** Turns the names of authors in `$field` into links to their pages, listed as the language lists them. */
	function linkAuthors( $field, authors ) {
		const $links = authors.map( ( author ) => authorLink( author.id, author.name ) );

		$field.replaceChildren(
			...listFormat
				.formatToParts( authors.map( ( author ) => author.name ) )
				.map( ( part ) => ( 'element' === part.type ? $links.shift() : part.value ) )
		);
	}

	/** Links the subject of an achievement: to the page of its author, else to the books list. */
	function linkAchievement( $field, achievement ) {
		if ( achievement.author && $field.textContent ) {
			$field.replaceChildren( authorLink( achievement.author, $field.textContent ) );
		} else {
			link( $field, achievement.url );
		}
	}

	/** Shows a cover, or hides the image when the book has none. */
	function fillCover( $image, url ) {
		$image.hidden = ! url;

		if ( url ) {
			$image.src = url;
		} else {
			$image.removeAttribute( 'src' );
		}
	}

	function clone( id ) {
		return document.getElementById( id ).content.firstElementChild.cloneNode( true );
	}

	/** The card of a book, which opens it; `state` is the line under its title. */
	function renderBook( book, state ) {
		const $item = clone( 'library-item' );

		$item.dataset.id = book.id;
		fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
		fill( $item, { credits: book.credits, title: book.title, state } );

		return $item;
	}

	/** Opens the book of a card clicked in `$root`. */
	function openBooks( $root, signal ) {
		$root.addEventListener(
			'click',
			( event ) => {
				const $button = event.target.closest( '.library-item__button' );

				if ( $button ) {
					store.openBook( Number( $button.closest( '.library-item' ).dataset.id ) );
				}
			},
			{ signal }
		);
	}

	/** Opens the page of an author whose link is clicked in `$root`; a new tab or window stays a link. */
	function openAuthors( $root, signal ) {
		$root.addEventListener(
			'click',
			( event ) => {
				const $link = event.target.closest( 'a[data-author]' );

				if ( ! $link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
					return;
				}

				event.preventDefault();
				store.openAuthor( Number( $link.dataset.author ) );
			},
			{ signal }
		);
	}

	/** An achievement earned: its title, its subject, its detail and date, as on the Achievements page. */
	function renderAchievement( achievement ) {
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
	function renderCloseAchievement( achievement, id ) {
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
		$progress.setAttribute( 'aria-valuetext', sprintf( strings.progress, done, total ) );

		return $item;
	}

	/**
	 * Calls `callback` as the end `$more` of a list comes near. Returns the function
	 * to call once the list has grown: its end may still be in view.
	 */
	function observeEnd( $more, signal, callback ) {
		// The last entry: one from before observe() may come first.
		const observer = new IntersectionObserver(
			( entries ) => {
				if ( entries.at( -1 ).isIntersecting ) {
					callback();
				}
			},
			{ rootMargin: `0px 0px ${ LOAD_MARGIN_PX }px 0px` }
		);

		signal.addEventListener( 'abort', () => observer.disconnect() );

		return () => {
			observer.unobserve( $more );
			observer.observe( $more );
		};
	}

	/** A brick: `connect()` returns what to subscribe, all unsubscribed when the brick leaves the page. */
	class Brick extends HTMLElement {
		#controller = null;

		connectedCallback() {
			this.#controller = new AbortController();

			const { signal } = this.#controller;

			// select() returns the unsubscribe function: abort() calls it.
			this.connect( signal ).forEach( ( unsubscribe ) => signal.addEventListener( 'abort', unsubscribe ) );
		}

		disconnectedCallback() {
			this.#controller?.abort();
		}
	}

	/** The links to the views: they switch views without reloading, and mark the current one. */
	class LibraryNavElement extends Brick {
		connect( signal ) {
			const $links = [ ...this.querySelectorAll( 'a[data-view]' ) ];

			$links.forEach( ( $link ) =>
				$link.addEventListener(
					'click',
					( event ) => {
						// A new tab or window stays a link.
						if ( event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
							return;
						}

						event.preventDefault();
						store.setView( $link.dataset.view );
					},
					{ signal }
				)
			);

			return [
				store.select(
					( state ) => state.view,
					( view ) =>
						$links.forEach( ( $link ) => {
							if ( view === $link.dataset.view ) {
								$link.setAttribute( 'aria-current', 'page' );
							} else {
								$link.removeAttribute( 'aria-current' );
							}
						} )
				),
			];
		}
	}
	customElements.define( 'library-nav', LibraryNavElement );

	/** A view, shown when it is the current one. */
	class LibraryViewElement extends Brick {
		connect() {
			return [
				store.select(
					( state ) => state.view,
					( view ) => {
						this.hidden = view !== this.getAttribute( 'name' );
					}
				),
			];
		}
	}
	customElements.define( 'library-view', LibraryViewElement );

	class LibrarySearchElement extends Brick {
		connect( signal ) {
			const $input = this.querySelector( 'input' );

			$input.addEventListener( 'input', () => store.setQuery( $input.value ), { signal } );

			return [
				store.select(
					( state ) => state.query,
					( query ) => {
						if ( $input.value !== query ) {
							$input.value = query;
						}
					}
				),
			];
		}
	}
	customElements.define( 'library-search', LibrarySearchElement );

	class LibraryReadFilterElement extends Brick {
		connect( signal ) {
			const $select = this.querySelector( 'select' );

			$select.addEventListener( 'change', () => store.setRead( $select.value ), { signal } );

			return [
				store.select(
					( state ) => state.read,
					( read ) => {
						$select.value = read;
					}
				),
			];
		}
	}
	customElements.define( 'library-read-filter', LibraryReadFilterElement );

	class LibraryCountElement extends Brick {
		connect() {
			return [
				store.select(
					( { search } ) => ( search.loaded ? search.total : null ),
					( total ) => {
						this.textContent = null === total ? strings.loading : plural( total, strings.books );
					}
				),
			];
		}
	}
	customElements.define( 'library-count', LibraryCountElement );

	class LibraryStatusElement extends Brick {
		connect() {
			return [
				store.select(
					( { search } ) => search.error || ( search.loaded && ! search.total ? strings.noBooks : '' ),
					( message ) => {
						this.hidden = ! message;
						this.textContent = message;
					}
				),
			];
		}
	}
	customElements.define( 'library-status', LibraryStatusElement );

	/** The books found, loading the next page as the end of the list comes. */
	class LibraryListElement extends Brick {
		#ids = [];

		connect( signal ) {
			const $items = this.querySelector( '.app-list__items' );
			const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreBooks() );

			openBooks( $items, signal );

			return [
				store.select(
					( state ) => state.search.items,
					( ids ) => {
						const { books } = store.get();
						// A next page adds to the list, a new search starts it again.
						const start = this.#ids.length && ids[ 0 ] === this.#ids[ 0 ] ? this.#ids.length : 0;

						if ( ! start ) {
							$items.replaceChildren();
						}

						$items.append(
							...ids.slice( start ).map( ( id ) => {
								const book = books[ id ];

								return renderBook( book, formatState( book ) );
							} )
						);
						this.#ids = ids;
						observe();
					}
				),
			];
		}
	}
	customElements.define( 'library-list', LibraryListElement );

	/** The readings by month, latest first: a book read again comes once per reading. */
	class LibraryDiaryElement extends Brick {
		connect( signal ) {
			const $months = this.querySelector( '.library-diary__months' );
			const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreReadings() );

			openBooks( $months, signal );

			return [
				store.select(
					( { diary } ) => diary.error || ( diary.loaded && ! diary.total ? strings.noBooks : '' ),
					( message ) => fill( this, { status: message } )
				),
				store.select(
					( state ) => state.diary.items,
					( readings ) => {
						$months.replaceChildren( ...this.#renderMonths( readings ) );
						observe();
					}
				),
			];
		}

		/** A section per month; the count of the last one shows once its readings are all loaded. */
		#renderMonths( readings ) {
			const { books, diary } = store.get();
			// A reading shows with its book only.
			const months = Map.groupBy(
				readings.filter( ( reading ) => reading.id in books ),
				( reading ) => reading.date.slice( 0, 7 )
			);
			const complete = diary.page >= diary.pages;

			return [ ...months ].map( ( [ month, entries ], index ) => {
				const $month = clone( 'library-month' );
				const isLast = index === months.size - 1;

				fill( $month, {
					month: formatDate( month ),
					count: ! isLast || complete ? plural( entries.length, strings.books ) : '',
				} );
				$month.querySelector( 'ul' ).append(
					...entries.map( ( { date, id } ) => {
						const book = books[ id ];
						const isFirstReading = date === [ ...book.read_date ].sort()[ 0 ];

						return renderBook(
							book,
							join( [ formatDate( date, dayFormat ), ! isFirstReading && strings.readAgain, ...formatDetails( book ) ] )
						);
					} )
				);

				return $month;
			} );
		}
	}
	customElements.define( 'library-diary', LibraryDiaryElement );

	/** The reading idea of the week, and the next one on demand. */
	class LibraryIdeaElement extends Brick {
		connect( signal ) {
			const $book = this.querySelector( '[data-field="book"]' );
			const $next = this.querySelector( '.library-idea__actions button' );

			openBooks( $book, signal );
			$next.addEventListener( 'click', () => store.nextIdea(), { signal } );

			return [
				store.select(
					( state ) => state.idea,
					( idea ) => {
						const book = store.get().books[ idea.id ];

						$next.disabled = idea.loading;
						$next.hidden = idea.total < 2;
						fill( this, { status: idea.error, reason: book ? idea.reason : '' } );
						$book.replaceChildren(
							...( book ? [ renderBook( book, formatState( book ) ) ] : [] )
						);
					}
				),
			];
		}
	}
	customElements.define( 'library-idea', LibraryIdeaElement );

	/** The reading stats: all the books, and this year against last year. */
	class LibraryYearElement extends Brick {
		connect() {
			return [
				store.select(
					( state ) => state.stats,
					( stats ) => {
						const year = stats?.year_to_date;

						fill( this, {
							summary: stats ? sprintf( strings.summary, formatNumber( stats.total ), formatNumber( stats.read ), formatNumber( stats.percent ) ) : '',
							yearToDate: year ? sprintf( strings.yearToDate, formatNumber( year.this_year ), year.year, formatNumber( year.last_year ) ) : '',
							projection: null != year?.projection ? sprintf( strings.projection, formatNumber( year.projection ) ) : '',
						} );
					}
				),
			];
		}
	}
	customElements.define( 'library-year', LibraryYearElement );

	/** The achievements close to be earned, with their progress, then the ones earned, latest first. */
	class LibraryAchievementsElement extends Brick {
		connect( signal ) {
			const $close = this.querySelector( '[data-list="close"]' );
			const $earned = this.querySelector( '[data-list="earned"]' );

			openAuthors( this, signal );

			return [
				store.select(
					( state ) => state.achievements,
					( { earned, close, error } ) => {
						fill( this, {
							status: error,
							closeCount: close.length ? formatNumber( close.length ) : '',
							earnedCount: earned.length ? formatNumber( earned.length ) : '',
						} );
						$close.replaceChildren(
							...close.map( ( achievement, index ) => renderCloseAchievement( achievement, `library-achievement-close-${ index }` ) )
						);
						$earned.replaceChildren( ...earned.map( renderAchievement ) );
					}
				),
			];
		}
	}
	customElements.define( 'library-achievements', LibraryAchievementsElement );

	/**
	 * The page of an author: their names, their reading and the completion their books count for,
	 * then their books, in the order they first came out, loading as the end of the list comes.
	 */
	class LibraryAuthorElement extends Brick {
		#id = 0;
		#ids = [];

		connect( signal ) {
			const $completion = this.querySelector( '[data-list="completion"]' );
			const $items = this.querySelector( '[data-list="books"]' );
			const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreAuthorBooks() );

			openBooks( $items, signal );

			return [
				store.select(
					( state ) => state.author,
					( author ) => {
						const pseudonyms = strings.pseudonyms[ pluralRules.select( author.pseudonyms.length ) ] ?? strings.pseudonyms.other;
						const rating = author.rating && sprintf( strings.averageRating, formatNumber( author.rating ) );

						fill( this, {
							status: author.error || ( author.loaded && ! author.total ? strings.noBooks : '' ),
							name: author.name,
							pseudonyms: author.pseudonyms.length ? sprintf( pseudonyms, listFormat.format( author.pseudonyms ) ) : '',
							summary: author.name ? join( [ plural( author.total, strings.books ), plural( author.read, strings.booksRead ), rating ] ) : '',
						} );
					}
				),
				store.select(
					( state ) => state.author.completion,
					( completion ) => {
						$completion.hidden = ! completion;

						if ( ! completion ) {
							$completion.replaceChildren();
							return;
						}

						// The page is the author's: their completion links nowhere.
						const achievement = { ...completion, url: '', author: 0 };

						$completion.replaceChildren(
							'total' in achievement ? renderCloseAchievement( achievement, 'library-author-completion' ) : renderAchievement( achievement )
						);
					}
				),
				store.select(
					( state ) => state.author.items,
					( ids ) => {
						const { books, author } = store.get();
						// A next page adds to the list, another author starts it again.
						const start = author.id === this.#id ? this.#ids.length : 0;

						if ( ! start ) {
							$items.replaceChildren();
						}

						$items.append(
							...ids
								.slice( start )
								.filter( ( id ) => id in books )
								.map( ( id ) => {
									const book = books[ id ];
									const published = book.published_date && sprintf( strings.publishedIn, book.published_date.slice( 0, 4 ) );

									return renderBook( book, join( [ published, formatState( book ) ] ) );
								} )
						);
						this.#id = author.id;
						this.#ids = ids;
						observe();
					}
				),
			];
		}
	}
	customElements.define( 'library-author', LibraryAuthorElement );

	/** The book open, in a modal dialog; closing it, with its button or Escape, closes the book. */
	class LibraryBookElement extends Brick {
		connect( signal ) {
			const $dialog = this.querySelector( 'dialog' );

			$dialog.addEventListener( 'close', () => store.closeBook(), { signal } );
			openAuthors( $dialog, signal );

			return [
				store.select(
					( state ) => state.books[ state.book ] ?? null,
					( book ) => {
						if ( ! book ) {
							$dialog.close();
							return;
						}

						this.#render( $dialog, book );
						$dialog.showModal();
						$dialog.scrollTop = 0;
					}
				),
			];
		}

		#render( $dialog, book ) {
			const readDates = book.read_date ?? [];
			let read = book.read ? strings.yes : strings.no;

			if ( book.read && readDates.length ) {
				read = listFormat.format( readDates.map( ( date ) => formatDate( date ) ) );
			}

			fillCover( $dialog.querySelector( '[data-field="cover"]' ), book.cover );
			fill( $dialog, {
				credits: book.credits,
				title: book.title,
				status: notKept === book.status ? strings.notKept : '',
				authors: listFormat.format( book.authors.map( ( author ) => author.name ) ),
				read,
				rating: formatRating( book.rating ),
				isbn: book.isbn,
				pages: book.pages ? formatNumber( book.pages ) : '',
				edition_date: book.edition_date ? formatDate( book.edition_date ) : '',
				published_date: book.published_date ? formatDate( book.published_date ) : '',
				translators: book.translators,
				languages: listFormat.format( book.languages ),
			} );
			linkAuthors( $dialog.querySelector( '[data-field="authors"]' ), book.authors );
			$dialog.querySelector( '[data-field="edit"]' ).href = editUrl + book.id;
		}
	}
	customElements.define( 'library-book', LibraryBookElement );

	class LibraryProgressElement extends Brick {
		connect() {
			return [
				store.select(
					( state ) => state.stats?.percent,
					( percent ) => {
						this.textContent = undefined === percent ? '' : sprintf( strings.readPercent, formatNumber( percent ) );
					}
				),
			];
		}
	}
	customElements.define( 'library-progress', LibraryProgressElement );

	class LibraryThemeToggleElement extends Brick {
		connect( signal ) {
			const $button = this.querySelector( 'button' );

			$button.addEventListener( 'click', () => store.toggleTheme(), { signal } );

			return [
				store.select(
					( state ) => state.theme,
					( theme ) => {
						// U+FE0E forces text presentation (no emoji rendering).
						$button.textContent = 'dark' === theme ? '☀︎' : '☽︎';
						$button.setAttribute( 'aria-pressed', ( 'dark' === theme ).toString() );
					}
				),
			];
		}
	}
	customElements.define( 'library-theme-toggle', LibraryThemeToggleElement );
} )();
