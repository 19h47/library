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
	// On the home: the covers of the latest books read, and the achievements close to be earned.
	const LATEST_BOOKS = 12;
	const CLOSE_ACHIEVEMENTS = 3;

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

	/**
	 * Sets the text of the [data-field] elements of `$root`; an empty one is hidden, with its [data-row].
	 * A field the markup leaves out is skipped: a brick may show part of its fields, as on the home.
	 */
	function fill( $root, fields ) {
		Object.entries( fields ).forEach( ( [ name, value ] ) => {
			const $field = $root.querySelector( `[data-field="${ name }"]` );

			if ( ! $field ) {
				return;
			}

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

	/**
	 * Shows a cover, or hides the image when the book has none. The size the API gives makes the place of
	 * the image known before it loads; a photo picked for a book to add has none.
	 */
	function fillCover( $image, cover ) {
		$image.hidden = ! cover;

		if ( ! cover ) {
			$image.removeAttribute( 'src' );
			return;
		}

		$image.src = cover.url;

		if ( cover.width ) {
			$image.width = cover.width;
			$image.height = cover.height;
		}
	}

	function clone( id ) {
		return document.getElementById( id ).content.firstElementChild.cloneNode( true );
	}

	/** Today on the device, as a read date: Y-m-d. */
	function today() {
		const now = new Date();

		return [ now.getFullYear(), now.getMonth() + 1, now.getDate() ].map( ( part ) => part.toString().padStart( 2, '0' ) ).join( '-' );
	}

	/** Fills the card of a book; `state` is the line under its title. */
	function fillBook( $item, book, state ) {
		fillCover( $item.querySelector( '[data-field="cover"]' ), book.cover );
		fill( $item, { credits: book.credits, title: book.title, state } );
	}

	/** The cover of a book, which opens it; a book without cover shows its title instead. */
	function renderCover( book ) {
		const $item = clone( 'library-cover' );
		const $image = $item.querySelector( '[data-field="cover"]' );

		$item.dataset.id = book.id;
		$image.alt = book.title;
		fillCover( $image, book.cover );
		fill( $item, { title: book.cover ? '' : book.title } );

		return $item;
	}

	/** The card of a book, which opens it. */
	function renderBook( book, state ) {
		const $item = clone( 'library-item' );

		$item.dataset.id = book.id;
		fillBook( $item, book, state );

		return $item;
	}

	/**
	 * Fills again the cards of `$root` whose book changed, such as a book saved; `stateOf` gives the line
	 * of a card. Returns the unsubscribe function.
	 */
	function followBooks( $root, stateOf ) {
		let previous = store.get().books;

		return store.select(
			( state ) => state.books,
			( books ) => {
				$root.querySelectorAll( '.library-item' ).forEach( ( $item ) => {
					const book = books[ $item.dataset.id ];

					if ( book !== previous[ $item.dataset.id ] ) {
						fillBook( $item, book, stateOf( book, $item ) );
					}
				} );
				previous = books;
			}
		);
	}

	/** Opens the book of a card or a cover clicked in `$root`: the button of an element with the ID of a book. */
	function openBooks( $root, signal ) {
		$root.addEventListener(
			'click',
			( event ) => {
				const $book = event.target.closest( 'button' )?.closest( '[data-id]' );

				if ( $book && $root.contains( $book ) ) {
					store.openBook( Number( $book.dataset.id ) );
				}
			},
			{ signal }
		);
	}

	/** Switches to the view of a link clicked in `$root`, without reloading; a new tab or window stays a link. */
	function openViews( $root, signal ) {
		$root.addEventListener(
			'click',
			( event ) => {
				const $link = event.target.closest( 'a[data-view]' );

				if ( ! $link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
					return;
				}

				event.preventDefault();
				store.setView( $link.dataset.view );
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

			openViews( this, signal );

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

	/** The home: the books view without a query, above the books. */
	class LibraryHomeElement extends Brick {
		connect( signal ) {
			openViews( this, signal );

			return [
				store.select(
					( state ) => ! state.query.trim(),
					( isHome ) => {
						this.hidden = ! isHome;
					}
				),
			];
		}
	}
	customElements.define( 'library-home', LibraryHomeElement );

	/** The covers of the latest books read, which open them; hidden until the diary has readings. */
	class LibraryLatestElement extends Brick {
		connect( signal ) {
			const $list = this.querySelector( 'ul' );

			openBooks( $list, signal );

			return [
				store.select(
					( state ) => state.diary.items,
					( readings ) => {
						const { books } = store.get();
						// A book read again shows once.
						const ids = [ ...new Set( readings.map( ( reading ) => reading.id ) ) ].filter( ( id ) => id in books ).slice( 0, LATEST_BOOKS );

						$list.replaceChildren( ...ids.map( ( id ) => renderCover( books[ id ] ) ) );
						this.hidden = ! ids.length;
					}
				),
			];
		}
	}
	customElements.define( 'library-latest', LibraryLatestElement );

	/** The achievements closest to be earned, with their progress; hidden when none is close. */
	class LibraryCloseElement extends Brick {
		connect( signal ) {
			const $list = this.querySelector( 'ul' );

			openAuthors( this, signal );

			return [
				store.select(
					( state ) => state.achievements.close,
					( close ) => {
						$list.replaceChildren(
							...close.slice( 0, CLOSE_ACHIEVEMENTS ).map( ( achievement, index ) => renderCloseAchievement( achievement, `library-close-${ index }` ) )
						);
						this.hidden = ! close.length;
					}
				),
			];
		}
	}
	customElements.define( 'library-close', LibraryCloseElement );

	/** The books found, loading the next ones as the end of the list comes. */
	class LibraryListElement extends Brick {
		#ids = [];

		connect( signal ) {
			const $items = this.querySelector( '.app-list__items' );
			const observe = observeEnd( this.querySelector( '.library-list__more' ), signal, () => store.loadMoreBooks() );

			openBooks( $items, signal );

			return [
				followBooks( $items, formatState ),
				store.select(
					( state ) => state.search.items,
					( ids ) => {
						const { books } = store.get();
						const found = new Set( ids );
						const kept = this.#ids.filter( ( id ) => found.has( id ) );
						// The list goes on from the books still found: a book no longer found leaves it, the next ones add to it.
						const goesOn = kept.every( ( id, index ) => id === ids[ index ] );

						if ( goesOn ) {
							$items.querySelectorAll( '.library-item' ).forEach( ( $item ) => {
								if ( ! found.has( Number( $item.dataset.id ) ) ) {
									this.#remove( $item );
								}
							} );
						} else {
							$items.replaceChildren();
						}

						$items.append( ...ids.slice( goesOn ? kept.length : 0 ).map( ( id ) => renderBook( books[ id ], formatState( books[ id ] ) ) ) );
						this.#ids = ids;
						observe();
					}
				),
			];
		}

		/** Removes a card; the focus it holds, back from the book closed, goes to the next card, or else the previous one. */
		#remove( $item ) {
			const $sibling = $item.nextElementSibling ?? $item.previousElementSibling;

			if ( $item.contains( document.activeElement ) ) {
				$sibling?.querySelector( '.library-item__button' ).focus();
			}

			$item.remove();
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
				followBooks( $months, ( book, $item ) => this.#state( book, $item.dataset.date ) ),
				store.select(
					( { diary } ) => diary.error || ( diary.loaded && ! diary.total ? strings.noBooks : '' ),
					( message ) => fill( this, { status: message } )
				),
				store.select(
					( state ) => state.diary.items,
					( readings ) => {
						const $cards = [ ...$months.querySelectorAll( '.library-item' ) ];
						const $focused = $cards.find( ( $item ) => $item.contains( document.activeElement ) );

						$months.replaceChildren( ...this.#renderMonths( readings ) );

						if ( $focused ) {
							this.#refocus( $focused, $cards.indexOf( $focused ) );
						}

						observe();
					}
				),
			];
		}

		/**
		 * Gives the focus back to the reading of the card `$focused` once the months are drawn again, as the next page comes
		 * or a book saved changes its readings; a reading removed leaves it to the one at its place.
		 */
		#refocus( $focused, index ) {
			const { id, date } = $focused.dataset;
			const $cards = [ ...this.querySelectorAll( '.library-item' ) ];
			const $card = $cards.find( ( $item ) => id === $item.dataset.id && date === $item.dataset.date ) ?? $cards[ Math.min( index, $cards.length - 1 ) ];

			$card?.querySelector( '.library-item__button' ).focus();
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
						const $item = renderBook( books[ id ], this.#state( books[ id ], date ) );

						$item.dataset.date = date;

						return $item;
					} )
				);

				return $month;
			} );
		}

		/** The line of a reading: its day, whether the book was read again then, its rating and whether it is kept. */
		#state( book, date ) {
			const isFirstReading = date === book.read_date[ 0 ];

			return join( [ formatDate( date, dayFormat ), ! isFirstReading && strings.readAgain, ...formatDetails( book ) ] );
		}
	}
	customElements.define( 'library-diary', LibraryDiaryElement );

	/** The reading idea of the week, and the next one on demand; hidden until it is loaded. */
	class LibraryIdeaElement extends Brick {
		connect( signal ) {
			const $book = this.querySelector( '[data-field="book"]' );
			const $next = this.querySelector( '.library-idea__actions button' );

			openBooks( $book, signal );
			$next.addEventListener( 'click', () => store.nextIdea(), { signal } );

			return [
				followBooks( $book, formatState ),
				store.select(
					( state ) => state.idea,
					( idea ) => {
						const book = store.get().books[ idea.id ];

						this.hidden = ! idea.loaded;
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
				followBooks( $items, ( book ) => this.#state( book ) ),
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
								.map( ( id ) => renderBook( books[ id ], this.#state( books[ id ] ) ) )
						);
						this.#id = author.id;
						this.#ids = ids;
						observe();
					}
				),
			];
		}

		/** The line of a book: the year it first came out, as the list follows it, then its state. */
		#state( book ) {
			const published = book.published_date && sprintf( strings.publishedIn, book.published_date.slice( 0, 4 ) );

			return join( [ published, formatState( book ) ] );
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
			fillCover( $dialog.querySelector( '[data-field="cover"]' ), book.cover );
			fill( $dialog, {
				credits: book.credits,
				title: book.title,
				authors: listFormat.format( book.authors.map( ( author ) => author.name ) ),
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

	/**
	 * The reading of the book open, as a form: whether it is read, its read dates, its rating and whether it is kept.
	 * Saving closes the book; closing it drops what was not saved.
	 */
	class LibraryReadingElement extends Brick {
		connect( signal ) {
			const $form = this.querySelector( 'form' );
			const { read: $read, rating: $rating, not_kept: $notKept } = $form.elements;
			const $dates = this.querySelector( '.library-reading__dates' );
			const $list = $dates.querySelector( 'ul' );
			const $add = $dates.querySelector( '[data-action="add"]' );
			const $submit = $form.querySelector( '[type="submit"]' );

			const addDate = ( date ) => {
				const $item = clone( 'library-read-date' );
				const $input = $item.querySelector( 'input' );

				$input.value = date;
				$input.max = today();
				$list.append( $item );

				return $input;
			};

			// Unread, the book has no read dates: they are left out of the form.
			const showDates = () => {
				$dates.disabled = ! $read.checked;
				$dates.hidden = ! $read.checked;
			};

			$read.addEventListener(
				'change',
				() => {
					// Read today, unless a date is there already.
					if ( $read.checked && ! $list.children.length ) {
						addDate( today() );
					}

					showDates();
				},
				{ signal }
			);

			$add.addEventListener( 'click', () => addDate( today() ).focus(), { signal } );

			$list.addEventListener(
				'click',
				( event ) => {
					const $remove = event.target.closest( '[data-action="remove"]' );

					if ( $remove ) {
						$remove.closest( 'li' ).remove();
						$add.focus();
					}
				},
				{ signal }
			);

			$form.addEventListener(
				'submit',
				( event ) => {
					const data = new FormData( $form );
					const rating = data.get( 'rating' );

					event.preventDefault();
					store.saveBook( {
						read: data.has( 'read' ),
						readDates: data.getAll( 'read_date' ),
						rating: rating ? Number( rating ) : null,
						kept: ! data.has( 'not_kept' ),
					} );
				},
				{ signal }
			);

			return [
				store.select(
					( state ) => state.books[ state.book ] ?? null,
					( book ) => {
						if ( ! book ) {
							return;
						}

						$read.checked = book.read;
						$list.replaceChildren();
						book.read_date.forEach( ( date ) => addDate( date ) );
						showDates();
						$rating.value = ( book.rating || '' ).toString();
						$notKept.checked = notKept === book.status;
					}
				),
				store.select(
					( state ) => state.saving,
					( saving ) => {
						$submit.disabled = saving.loading;
						fill( this, { error: saving.error } );
					}
				),
			];
		}
	}
	customElements.define( 'library-reading', LibraryReadingElement );

	/** The button that opens the form of a book to add. */
	class LibraryAddToggleElement extends Brick {
		connect( signal ) {
			this.querySelector( 'button' ).addEventListener( 'click', () => store.openAdd(), { signal } );

			return [];
		}
	}
	customElements.define( 'library-add-toggle', LibraryAddToggleElement );

	/** Options of a datalist or a select, such as the names to suggest. */
	function renderOptions( terms, value = ( term ) => term.name ) {
		return terms.map( ( term ) => new Option( term.name, value( term ) ) );
	}

	/**
	 * The form of a book to add, in a modal dialog: its cover, title, authors, publisher and collection, ISBN,
	 * and more details. The names typed are suggested among those of the library, the collections among those
	 * of the publisher; a name not found is added. Closing the form drops it.
	 */
	class LibraryAddElement extends Brick {
		#preview = '';

		connect( signal ) {
			const $dialog = this.querySelector( 'dialog' );
			const $form = this.querySelector( '.library-add__form' );
			const { publisher: $publisher, collection: $collection, language: $language, cover: $cover } = $form.elements;
			const $authors = this.querySelector( '[data-list="authors"]' );
			const $addAuthor = this.querySelector( '[data-action="add-author"]' );
			const $preview = this.querySelector( '.library-add__preview' );
			const $details = this.querySelector( '.library-add__details' );
			const $submit = $form.querySelector( '[type="submit"]' );
			const $suggestions = {
				authors: this.querySelector( '#library-add-authors' ),
				publishers: this.querySelector( '#library-add-publishers' ),
				collections: this.querySelector( '#library-add-collections' ),
			};

			const addAuthor = () => {
				const $item = clone( 'library-add-author' );

				$authors.append( $item );

				return $item.querySelector( 'input' );
			};

			// The collections of the publisher typed, when the library has it; a collection needs a publisher.
			const suggestCollections = () => {
				const name = $publisher.value.trim().toLowerCase();
				const { publishers } = store.get().terms;
				const publisher = publishers.find( ( term ) => ! term.parent && name === term.name.toLowerCase() );

				$collection.disabled = ! name;
				$suggestions.collections.replaceChildren( ...renderOptions( publisher ? publishers.filter( ( term ) => publisher.id === term.parent ) : [] ) );
			};

			const showCover = ( file ) => {
				URL.revokeObjectURL( this.#preview );
				this.#preview = file ? URL.createObjectURL( file ) : '';
				fillCover( $preview, file ? { url: this.#preview } : null );
			};

			$dialog.addEventListener( 'close', () => store.closeAdd(), { signal } );
			$addAuthor.addEventListener( 'click', () => addAuthor().focus(), { signal } );
			$publisher.addEventListener( 'input', suggestCollections, { signal } );
			$cover.addEventListener( 'change', () => showCover( $cover.files[ 0 ] ), { signal } );

			$authors.addEventListener(
				'click',
				( event ) => {
					const $remove = event.target.closest( '[data-action="remove"]' );

					if ( $remove ) {
						$remove.closest( 'li' ).remove();
						$addAuthor.focus();
					}
				},
				{ signal }
			);

			$form.addEventListener(
				'submit',
				( event ) => {
					const data = new FormData( $form );
					const cover = data.get( 'cover' );
					// The fields of the meta are named after them.
					const meta = Object.fromEntries( [ ...$form.querySelectorAll( '[data-meta]' ) ].map( ( $field ) => [ $field.name, $field.value ] ) );

					event.preventDefault();
					store.addBook( {
						title: data.get( 'title' ),
						authors: data.getAll( 'author' ).map( ( name ) => name.trim() ).filter( Boolean ),
						publisher: data.get( 'publisher' ),
						collection: data.get( 'collection' ) ?? '',
						language: Number( data.get( 'language' ) ),
						meta,
						cover: cover.size ? cover : null,
					} );
				},
				{ signal }
			);

			return [
				store.select(
					( state ) => state.adding.open,
					( isOpen ) => {
						if ( ! isOpen ) {
							$dialog.close();
							return;
						}

						// The form opens empty, its details folded.
						$form.reset();
						$details.open = false;
						$authors.replaceChildren();
						addAuthor();
						showCover( null );
						suggestCollections();
						$dialog.showModal();
						$dialog.scrollTop = 0;
					}
				),
				store.select(
					( state ) => state.adding,
					( adding ) => {
						$submit.disabled = adding.loading;
						fill( this, { error: adding.error } );
					}
				),
				store.select(
					( state ) => state.terms,
					( terms ) => {
						const $none = $language.options[ 0 ];

						$suggestions.authors.replaceChildren( ...renderOptions( terms.authors ) );
						$suggestions.publishers.replaceChildren( ...renderOptions( terms.publishers.filter( ( term ) => ! term.parent ) ) );
						$language.replaceChildren( $none, ...renderOptions( terms.languages, ( term ) => term.id ) );
						suggestCollections();
					}
				),
			];
		}
	}
	customElements.define( 'library-add', LibraryAddElement );

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
