import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	dayFormat,
	formatBooks,
	formatLanguage,
	formatNumber,
	formatRating,
	join,
	monthNameFormat,
} from 'library-app/format';
import {
	fill,
	fillCover,
	fillPills,
	pageLink,
	renderAchievementPill,
	renderCover,
	renderMonths,
	renderShares,
	renderTile,
} from 'library-app/render';
import { urlOf } from 'library-app/router';

/**
 * @import { Book, Recap } from 'library-app/api'
 * @import { Share } from 'library-app/render'
 * @import { Loading } from 'library-app/store'
 */

// The columns of covers going by behind the last screen, more than the widest frame holds, one in the middle, and the
// covers a column has at least, to go past the height of a screen.
const MARQUEE_COLUMNS = 15;
const MARQUEE_COVERS = 16;
// How long a figure counts up to its value, slowing down to it.
const COUNT_MS = 1000;
const reducedMotion = matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * The recap of a year, a screen per subject: the books read, against the year before, the pages read, the readings of
 * each month, the forms, the people read the most and those read for the first time, the original languages, the series,
 * the days of reading, over the calendar of the year, the average rating with the books rated the highest, the records,
 * the achievements earned and the books added; a screen without anything to say hides. The last one leads to the recaps of the years before and after, those gone by.
 * Its figures count up to their value as their screen comes, once per year shown.
 */
class LibraryRecapElement extends Brick {
	/**
	 * The figures to count up, as they come on the screen, with their value.
	 *
	 * @type {Map<Element, number>}
	 */
	#counts = new Map();
	/**
	 * The figures counted up, of the year shown.
	 *
	 * @type {WeakSet<Element>}
	 */
	#counted = new WeakSet();
	// The year shown.
	#year = 0;
	/** @type {IntersectionObserver} */
	#observer;

	connect( signal ) {
		this.#observer = new IntersectionObserver(
			( entries ) =>
				entries
					.filter( ( entry ) => entry.isIntersecting )
					.forEach( ( { target } ) => this.#countUp( target ) ),
			{ threshold: 1 }
		);
		signal.addEventListener( 'abort', () => this.#observer.disconnect() );

		return [
			store.select(
				( state ) => state.recap,
				( recap ) => this.#render( recap )
			),
		];
	}

	/**
	 * Draws the recap.
	 *
	 * @param {Recap & Loading} recap The recap.
	 */
	#render( recap ) {
		const { books } = store.get();
		const read = recap.books.map( ( id ) => books[ id ] ).filter( Boolean );
		const isShown =
			recap.loaded && ! recap.error && recap.reading_count > 0;
		const started = recap.series.filter( ( series ) => series.started );
		const finished = recap.series.filter( ( series ) => series.finished );

		fill( this, {
			year: recap.year ? recap.year.toString() : '',
			status: this.#status( recap ),
			readings: formatNumber( recap.reading_count ),
			previous: this.#previous( recap ),
			rereads: recap.reread_count
				? sprintf(
						/* translators: %s: number of readings of a book read again in the year. */
						_n(
							'Including %s read again',
							'Including %s read again',
							recap.reread_count,
							'library'
						),
						formatNumber( recap.reread_count )
				  )
				: '',
			...this.#pages( recap ),
			bestMonth: this.#bestMonth( recap ),
			...this.#days( recap ),
			discoveries: formatNumber( recap.discoveries.length ),
			discovered: _n(
				'person read for the first time',
				'people read for the first time',
				recap.discoveries.length,
				'library'
			),
			languages: formatNumber( recap.languages.length ),
			series: formatNumber( recap.series.length ),
			seriesState: join( [
				started.length &&
					sprintf(
						/* translators: %s: number of series first read in the year. */
						_n(
							'%s started',
							'%s started',
							started.length,
							'library'
						),
						formatNumber( started.length )
					),
				finished.length &&
					sprintf(
						/* translators: %s: number of series read in full, the last book in the year. */
						_n(
							'%s finished',
							'%s finished',
							finished.length,
							'library'
						),
						formatNumber( finished.length )
					),
			] ),
			rating: formatRating( recap.rating ),
			achievements: formatNumber( recap.achievements.length ),
			added: formatNumber( recap.added_count ),
			addedBooks: _n(
				'book added',
				'books added',
				recap.added_count,
				'library'
			),
			pile: this.#pile( recap.added_count, read.length ),
			closing: recap.year
				? sprintf(
						/* translators: %s: year of the recap. */
						__( 'That was %s', 'library' ),
						recap.year.toString()
				  )
				: '',
			...this.#years( recap ),
		} );

		const covered = read.filter( ( book ) => book.cover );

		this.querySelector( '[data-list="covers"]' ).replaceChildren(
			...covered.map( renderTile )
		);
		this.querySelector( '[data-list="marquee"]' ).replaceChildren(
			...this.#renderMarquee( covered )
		);
		this.querySelector( '[data-list="months"]' ).replaceChildren(
			...renderMonths( recap.months, recap.year )
		);
		this.#fillCalendar( recap );
		this.#fillShares(
			'forms',
			recap.forms.map( ( { name, reading_count: readings } ) => ( {
				name,
				readings,
			} ) )
		);
		this.#fillShares(
			'people',
			recap.people.map( ( { id, name, reading_count: readings } ) => ( {
				name: pageLink( 'person', id, name ),
				readings,
			} ) )
		);
		// Every one, small, in a cloud the screen cuts under a fade: the recap shows them, a search lists them.
		fillPills(
			this.querySelector( '[data-list="discoveries"]' ),
			recap.discoveries.map( ( { id, name } ) =>
				pageLink( 'person', id, name )
			),
			'app-pill app-pill--small'
		);
		this.#fillShares(
			'languages',
			recap.languages.map( ( { code, reading_count: readings } ) => ( {
				name: formatLanguage( code ),
				readings,
			} ) )
		);
		this.#fillShares(
			'series',
			recap.series
				.slice( 0, 5 )
				.map( ( { id, name, reading_count: readings } ) => ( {
					name: pageLink( 'series', id, name ),
					readings,
				} ) )
		);
		this.querySelector( '[data-list="best"]' ).replaceChildren(
			...recap.best
				.filter( ( id ) => books[ id ] )
				.map( ( id ) => renderCover( books[ id ] ) )
		);
		this.#fillRecords( recap, books );
		this.querySelector( '[data-list="achievements"]' ).replaceChildren(
			...recap.achievements.map( renderAchievementPill )
		);

		this.#prepareCounts( recap.year, {
			readings: recap.reading_count,
			pages: recap.pages.total,
			daysRead: recap.calendar.read_day_count,
			discoveries: recap.discoveries.length,
			languages: recap.languages.length,
			series: recap.series.length,
			achievements: recap.achievements.length,
			added: recap.added_count,
			recordLongest: books[ recap.records.longest ]?.pages ?? 0,
			recordShortest: books[ recap.records.shortest ]?.pages ?? 0,
		} );
		this.#showScreens( {
			readings: isShown,
			pages: isShown && recap.pages.counted > 0,
			months: isShown,
			days: isShown,
			forms: isShown && recap.forms.length > 0,
			people: isShown && recap.people.length > 0,
			discoveries: isShown && recap.discoveries.length > 0,
			languages: isShown && recap.languages.length > 0,
			series: isShown && recap.series.length > 0,
			rating: isShown && null !== recap.rating,
			records: isShown && Object.values( recap.records ).some( Boolean ),
			achievements: isShown && recap.achievements.length > 0,
			pile: isShown,
			closing: isShown,
		} );
	}

	/**
	 * Gets the figures `counts` of `year` ready to count up as they come on the screen: each shows 0, while assistive
	 * technologies read its value, unless it was counted up already, or without motion.
	 *
	 * @param {number}                 year   The year.
	 * @param {Object<string, number>} counts The value of each figure, by its field.
	 */
	#prepareCounts( year, counts ) {
		if ( year !== this.#year ) {
			this.#year = year;
			this.#counted = new WeakSet();
		}

		Object.entries( counts ).forEach( ( [ name, value ] ) => {
			const $figure = this.querySelector( `[data-field="${ name }"]` );

			if (
				reducedMotion.matches ||
				! value ||
				this.#counted.has( $figure )
			) {
				return;
			}

			const $said = document.createElement( 'span' );
			const $shown = document.createElement( 'span' );

			$said.className = 'sr-only';
			$said.textContent = $figure.textContent;
			$shown.setAttribute( 'aria-hidden', 'true' );
			$shown.textContent = formatNumber( 0 );
			$figure.replaceChildren( $said, $shown );
			this.#counts.set( $figure, value );
			this.#observer.observe( $figure );
		} );
	}

	/**
	 * Counts the figure `$figure` up to its value, slowing down to it: its digits as wide as one another, it does not jump.
	 *
	 * @param {Element} $figure The figure.
	 */
	#countUp( $figure ) {
		const value = this.#counts.get( $figure );
		const $shown = $figure.lastElementChild;
		const start = performance.now();
		/** @param {number} now The time of the frame. */
		const step = ( now ) => {
			const progress = Math.min( 1, ( now - start ) / COUNT_MS );
			const eased = 1 - ( 1 - progress ) ** 3;

			$shown.textContent = formatNumber( Math.round( value * eased ) );

			if ( progress < 1 ) {
				requestAnimationFrame( step );
			}
		};

		this.#observer.unobserve( $figure );
		this.#counts.delete( $figure );
		this.#counted.add( $figure );
		requestAnimationFrame( step );
	}

	/**
	 * What the recap says while it loads, could not, or finds no reading in its year.
	 *
	 * @param {Recap & Loading} recap The recap.
	 * @return {string} What it says, or empty.
	 */
	#status( recap ) {
		if ( recap.error ) {
			return recap.error;
		}

		if ( ! recap.loaded ) {
			return recap.loading ? __( 'Loading…', 'library' ) : '';
		}

		return recap.reading_count
			? ''
			: __( 'No readings that year.', 'library' );
	}

	/**
	 * The books read against the year before: how many more or fewer.
	 *
	 * @param {Recap} recap The recap.
	 * @return {string} The comparison, or empty without a reading the year before.
	 */
	#previous( {
		year,
		reading_count: readings,
		previous_reading_count: previous,
	} ) {
		if ( ! previous ) {
			return '';
		}

		const difference = Math.abs( readings - previous );

		if ( ! difference ) {
			/* translators: %d: the year before the one of the recap. */
			return sprintf( __( 'As many as in %d', 'library' ), year - 1 );
		}

		if ( readings > previous ) {
			return sprintf(
				/* translators: 1: number of books, 2: the year before the one of the recap. */
				_n(
					'%1$s more than in %2$d',
					'%1$s more than in %2$d',
					difference,
					'library'
				),
				formatNumber( difference ),
				year - 1
			);
		}

		return sprintf(
			/* translators: 1: number of books, 2: the year before the one of the recap. */
			_n(
				'%1$s fewer than in %2$d',
				'%1$s fewer than in %2$d',
				difference,
				'library'
			),
			formatNumber( difference ),
			year - 1
		);
	}

	/**
	 * The pages read: in all, a day of the year gone by, a book on average, and the books they count, while some have
	 * no number of pages.
	 *
	 * @param {Recap} recap The recap.
	 * @return {{pages: string, perDay: string, perBook: string, counted: string}} The fields.
	 */
	#pages( { pages, day_count: days, reading_count: readings } ) {
		const perDay = days ? Math.round( pages.total / days ) : 0;
		const perBook = pages.counted
			? Math.round( pages.total / pages.counted )
			: 0;

		return {
			pages: formatNumber( pages.total ),
			perDay: perDay
				? sprintf(
						/* translators: %s: number of pages read a day, on average. */
						__( 'About %s a day', 'library' ),
						formatNumber( perDay )
				  )
				: '',
			perBook: perBook
				? sprintf(
						/* translators: %s: number of pages of a book read, on average. */
						__( '%s a book on average', 'library' ),
						formatNumber( perBook )
				  )
				: '',
			counted:
				pages.counted < readings
					? sprintf(
							/* translators: 1: number of books read with their number of pages, 2: number of books read. */
							_n(
								'Pages filled in for %1$s book out of %2$s.',
								'Pages filled in for %1$s books out of %2$s.',
								pages.counted,
								'library'
							),
							formatNumber( pages.counted ),
							formatNumber( readings )
					  )
					: '',
		};
	}

	/**
	 * The columns of covers going by behind the last screen: the books in turn, a column each, as many times as a column
	 * needs to be higher than the screen, then twice over, for a column to go round without a gap. They load at once, as
	 * they move into sight, and the first screen has loaded them already.
	 *
	 * @param {Book[]} books The books read, with a cover.
	 * @return {HTMLUListElement[]} The columns, none without a book.
	 */
	#renderMarquee( books ) {
		if ( ! books.length ) {
			return [];
		}

		return [ ...Array( MARQUEE_COLUMNS ).keys() ].map( ( column ) => {
			const $column = document.createElement( 'ul' );

			// Its place, for its round to start at a point of its own.
			$column.style.setProperty(
				'--library-recap-column',
				column.toString()
			);
			const own = books.filter(
				( _book, index ) => column === index % MARQUEE_COLUMNS
			);
			const shown = own.length ? own : books;
			const once = [
				...Array( Math.max( shown.length, MARQUEE_COVERS ) ).keys(),
			].map( ( index ) => shown[ index % shown.length ] );

			$column.append(
				...[ ...once, ...once ].map( ( book ) => {
					const $tile = renderTile( book );

					/** @type {HTMLImageElement} */ (
						$tile.firstElementChild
					).loading = 'eager';

					return $tile;
				} )
			);

			return $column;
		} );
	}

	/**
	 * The month read the most, the first one of them.
	 *
	 * @param {Recap} recap The recap.
	 * @return {string} The month and its readings, or empty.
	 */
	#bestMonth( { year, months } ) {
		const most = Math.max( 0, ...months );

		if ( ! most ) {
			return '';
		}

		return sprintf(
			/* translators: 1: name of a month, 2: number of books read in it. */
			__( 'The month read the most: %1$s, %2$s', 'library' ),
			monthNameFormat.format( new Date( year, months.indexOf( most ) ) ),
			formatBooks( most )
		);
	}

	/**
	 * The days of reading: how many, the most in a row, the day the most pages were read, and the readings they go by,
	 * while some have no start.
	 *
	 * @param {Recap} recap The recap.
	 * @return {{daysRead: string, daysReadLabel: string, streak: string, bestDay: string, dated: string}} The fields.
	 */
	#days( { year, reading_count: readings, calendar } ) {
		const {
			read_day_count: read,
			streak,
			best,
			dated_reading_count: dated,
		} = calendar;

		return {
			daysRead: formatNumber( read ),
			daysReadLabel: _n(
				'day of reading',
				'days of reading',
				read,
				'library'
			),
			streak:
				streak > 1
					? sprintf(
							/* translators: %s: number of days read in a row, the most of the year. */
							_n(
								'Up to %s day in a row',
								'Up to %s days in a row',
								streak,
								'library'
							),
							formatNumber( streak )
					  )
					: '',
			bestDay: best
				? sprintf(
						/* translators: 1: number of pages read in a day, the most of the year, 2: the day, such as 12 March. */
						_n(
							'The biggest day: %1$s page, on %2$s',
							'The biggest day: %1$s pages, on %2$s',
							best.pages,
							'library'
						),
						formatNumber( best.pages ),
						dayFormat.format( new Date( year, 0, 1 + best.day ) )
				  )
				: '',
			dated:
				dated < readings
					? sprintf(
							/* translators: 1: number of readings of the year with their start date, 2: number of readings of the year. */
							_n(
								'Start date known for %1$s reading out of %2$s.',
								'Start date known for %1$s readings out of %2$s.',
								dated,
								'library'
							),
							formatNumber( dated ),
							formatNumber( readings )
					  )
					: '',
		};
	}

	/**
	 * The calendar behind the screen of the days: every day of the year, from January 1st, in weeks from Monday, the
	 * first one in the column of its weekday; each as dark as its level, a day to come blank, a memorable one pulsing.
	 *
	 * @param {Recap} recap The recap.
	 */
	#fillCalendar( { year, calendar } ) {
		/** @type {HTMLOListElement} */
		const $list = this.querySelector( '[data-list="calendar"]' );
		const length = 1 === new Date( year, 1, 29 ).getMonth() ? 366 : 365;
		// Monday first, as the weeks of ISO 8601.
		const weekday = ( new Date( year, 0, 1 ).getDay() + 6 ) % 7;
		const memorable = new Set( calendar.memorable );

		const $days = [ ...Array( year ? length : 0 ).keys() ].map( ( day ) => {
			const $day = document.createElement( 'li' );

			if ( day >= calendar.levels.length ) {
				$day.className = 'library-recap__later';
			} else {
				$day.dataset.level = calendar.levels[ day ].toString();
			}

			if ( memorable.has( day ) ) {
				$day.className = 'library-recap__memorable';
				// Its own point of its pulse, so that no two beat together.
				$day.style.setProperty( '--library-recap-day', day.toString() );
			}

			return $day;
		} );

		$days[ 0 ]?.style.setProperty(
			'grid-column-start',
			( weekday + 1 ).toString()
		);
		$list.replaceChildren( ...$days );
	}

	/**
	 * The books added against the books read: whether the pile grew, went down or held steady.
	 *
	 * @param {number} added The books added.
	 * @param {number} read  The books read.
	 * @return {string} What the pile did.
	 */
	#pile( added, read ) {
		if ( added === read ) {
			return __( 'The pile held steady.', 'library' );
		}

		const difference = formatBooks( Math.abs( added - read ) );

		if ( added > read ) {
			return sprintf(
				/* translators: %s: number of books, those added beyond those read. */
				__( 'The pile grew by %s.', 'library' ),
				difference
			);
		}

		return sprintf(
			/* translators: %s: number of books, those read beyond those added. */
			__( 'The pile went down by %s.', 'library' ),
			difference
		);
	}

	/**
	 * The links to the recaps of the year with a reading before, and after, as long as it is gone by.
	 *
	 * @param {Recap} recap The recap.
	 * @return {{previousYear: string, nextYear: string}} Their text, empty for none.
	 */
	#years( { year, years } ) {
		const thisYear = new Date().getFullYear();
		const before = years.filter( ( item ) => item < year ).at( -1 );
		const after = years.find( ( item ) => item > year && item < thisYear );
		/** @type {HTMLAnchorElement} */
		const $before = this.querySelector( '[data-field="previousYear"]' );
		/** @type {HTMLAnchorElement} */
		const $after = this.querySelector( '[data-field="nextYear"]' );

		$before.href = before ? urlOf( { view: 'recap', id: before } ) : '';
		$after.href = after ? urlOf( { view: 'recap', id: after } ) : '';

		return {
			previousYear: before ? `← ${ before }` : '',
			nextYear: after ? `${ after } →` : '',
		};
	}

	/**
	 * Fills the list `name` with `shares`.
	 *
	 * @param {string}  name   The list.
	 * @param {Share[]} shares The lines.
	 */
	#fillShares( name, shares ) {
		this.querySelector( `[data-list="${ name }"]` ).replaceChildren(
			...renderShares( shares )
		);
	}

	/**
	 * The records of the year, a line each: its cover, its figure, as large as the figures of the recap, and what it
	 * counts, then which record it is and the book, which it opens; the longest and the shortest by their pages, which
	 * count up as the others, the oldest by the year it first came out.
	 *
	 * @param {Recap}                recap The recap.
	 * @param {Object<number, Book>} books The books, by their IDs.
	 */
	#fillRecords( { records }, books ) {
		const year = ( book ) =>
			( book.published_date || book.edition_date ).slice( 0, 4 );
		/** @type {{id: number, field: string, label: string, figure: ( book: Book ) => string, unit: ( book: Book ) => string}[]} */
		const lines = [
			{
				id: records.longest,
				field: 'recordLongest',
				label: __( 'The longest', 'library' ),
				figure: ( book ) => formatNumber( book.pages ),
				unit: ( book ) =>
					/* translators: what the figure of a record counts, after it: 1 947 pages. */
					_n( 'page', 'pages', book.pages, 'library' ),
			},
			{
				id: records.shortest,
				field: 'recordShortest',
				label: __( 'The shortest', 'library' ),
				figure: ( book ) => formatNumber( book.pages ),
				unit: ( book ) =>
					/* translators: what the figure of a record counts, after it: 1 947 pages. */
					_n( 'page', 'pages', book.pages, 'library' ),
			},
			{
				id: records.oldest,
				field: 'recordOldest',
				label: __( 'The oldest', 'library' ),
				figure: year,
				unit: () => '',
			},
		];

		this.querySelector( '[data-list="records"]' ).replaceChildren(
			...lines
				.filter( ( { id } ) => books[ id ] )
				.map( ( { id, field, label, figure, unit } ) => {
					const book = books[ id ];
					const $item = document.createElement( 'li' );
					const $cover = document.createElement( 'img' );
					const $text = document.createElement( 'div' );
					const $figure = document.createElement( 'p' );
					const $label = document.createElement( 'p' );
					const $value = document.createElement( 'span' );
					const $unit = document.createElement( 'span' );

					// The title names the book: its cover is an image of it only.
					$cover.alt = '';
					$cover.loading = 'lazy';
					fillCover( $cover, book.cover );
					$value.dataset.field = field;
					$value.textContent = figure( book );
					$unit.className = 'app-meta';
					$unit.textContent = unit( book );
					$figure.className = 'library-recap__record-figure';
					$figure.append( $value, ' ', $unit );
					$label.className = 'app-meta';
					$label.textContent = label;
					$text.append(
						$figure,
						$label,
						pageLink( 'book', id, book.title )
					);
					$item.className = 'library-recap__record';
					$item.append( $cover, $text );

					return $item;
				} )
		);
	}

	/**
	 * Shows the screens with something to say, hides the others.
	 *
	 * @param {Object<string, boolean>} screens Whether each screen shows, by its name.
	 */
	#showScreens( screens ) {
		Object.entries( screens ).forEach( ( [ name, isShown ] ) => {
			/** @type {HTMLElement} */ (
				this.querySelector( `[data-screen="${ name }"]` )
			).hidden = ! isShown;
		} );
	}
}
customElements.define( 'library-recap', LibraryRecapElement );
