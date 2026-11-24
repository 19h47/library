import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	fillBookCover,
	fillPills,
	pageLink,
	renderAchievementPill,
	renderCover,
	renderTile,
} from 'library-app/render';
import { clone, fill } from 'library-app/dom';
import { streakOf } from 'library-app/parts';
import { urlOf } from 'library-app/router';
import {
	formatBooks,
	formatDay,
	formatLanguage,
	formatMonthName,
	formatNumber,
	formatRating,
	join,
} from 'library-app/format';

/**
 * @import { Book } from 'library-app/api'
 * @import { CountUpElement } from '../components/count-up.js'
 * @import { Share, SharesChartElement } from '../components/shares-chart.js'
 * @import { RecapPage } from 'library-app/parts'
 */

/**
 * What the days of a year count, from its calendar: the days read, the most in a row, the day the most pages were read,
 * by its index, with its pages, the memorable days, and the readings ended in the year with their start known.
 *
 * @typedef {Object} RecapDays
 * @property {number}                            read      The days read.
 * @property {number}                            streak    The most days read in a row.
 * @property {{day: number, pages: number}|null} best      The day the most pages were read, none without pages.
 * @property {number[]}                          memorable The memorable days, by their index, in order.
 * @property {number}                            dated     The readings ended in the year with their start known.
 */

// The columns of covers going by behind the last screen, more than the widest frame holds, one in the middle, and the
// covers a column has at least, to go past the height of a screen.
const MARQUEE_COLUMNS = 15;
const MARQUEE_COVERS = 16;

/**
 * The recap of a year, a screen per subject: the books read, against the year before, the pages read, the readings of
 * each month, the forms, the people read the most and those read for the first time, the original languages, the series,
 * the days of reading, over the calendar of the year, the average rating with the books rated the highest, the records,
 * the achievements earned and the books added; a screen without anything to say hides. The last one leads to the recaps of the years before and after, those gone by.
 * Its figures, count-up, count up to their value as their screen comes.
 */
class LibraryRecapElement extends Brick {
	connect() {
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
	 * @param {RecapPage} recap The recap.
	 */
	#render( recap ) {
		const { books } = store.get();
		const read = recap.books.map( ( id ) => books[ id ] ).filter( Boolean );
		const days = this.#daysOf( recap, read, books );
		const isShown =
			recap.loaded && ! recap.error && recap.reading_count > 0;
		const started = recap.series.filter( ( series ) => series.started );
		const finished = recap.series.filter( ( series ) => series.finished );

		fill( this, {
			year: recap.year ? recap.year.toString() : '',
			status: this.#status( recap ),
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
			...this.#days( recap, days ),
			discovered: _n(
				'person read for the first time',
				'people read for the first time',
				recap.discoveries.length,
				'library'
			),
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
		const $months = this.querySelector( 'months-chart' );

		$months.year = recap.year;
		$months.months = recap.months;
		this.querySelector( 'days-calendar' ).value = {
			year: recap.year,
			levels: recap.calendar.levels,
			memorable: days.memorable,
		};
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

		this.#count( {
			readings: recap.reading_count,
			pages: recap.pages.total,
			daysRead: days.read,
			discoveries: recap.discoveries.length,
			languages: recap.languages.length,
			series: recap.series.length,
			achievements: recap.achievements.length,
			added: recap.added_count,
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
	 * Gives each figure its value, which it counts up to.
	 *
	 * @param {Object<string, number>} counts The value of each figure, by its field.
	 */
	#count( counts ) {
		Object.entries( counts ).forEach( ( [ name, value ] ) => {
			/** @type {CountUpElement} */ (
				this.querySelector( `count-up[data-field="${ name }"]` )
			).value = value;
		} );
	}

	/**
	 * What the recap says while it loads, could not, or finds no reading in its year.
	 *
	 * @param {RecapPage} recap The recap.
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
	 * @param {RecapPage} recap The recap.
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
	 * @param {RecapPage} recap The recap.
	 * @return {{perDay: string, perBook: string, counted: string}} The fields.
	 */
	#pages( { pages, day_count: days, reading_count: readings } ) {
		const perDay = days ? Math.round( pages.total / days ) : 0;
		const perBook = pages.counted
			? Math.round( pages.total / pages.counted )
			: 0;

		return {
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
						$tile.querySelector( '[data-field="cover"]' )
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
	 * @param {RecapPage} recap The recap.
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
			formatMonthName( new Date( year, months.indexOf( most ) ) ),
			formatBooks( most )
		);
	}

	/**
	 * What the days of the year count, from its calendar: the days read, the most in a row, the day the most pages were
	 * read, the memorable days, that one and the days a favourite of the year, of its best rating, was finished, and the
	 * readings ended in the year with their start known.
	 *
	 * @param {RecapPage}            recap The recap.
	 * @param {Book[]}               read  The books read in the year.
	 * @param {Object<number, Book>} books The books, by their IDs.
	 * @return {RecapDays} What they count.
	 */
	#daysOf( { year, calendar }, read, books ) {
		const { levels, pages, ends } = calendar;
		const most = Math.max( 0, ...pages );
		const rating = Math.max( 0, ...read.map( ( book ) => book.rating ) );
		const favourites = Object.entries( ends )
			.filter(
				( [ , ids ] ) =>
					rating &&
					ids.some( ( id ) => books[ id ]?.rating === rating )
			)
			.map( ( [ day ] ) => Number( day ) );
		const best = most ? pages.indexOf( most ) : -1;

		return {
			read: levels.filter( Boolean ).length,
			streak: streakOf( levels ),
			best: most ? { day: best, pages: most } : null,
			memorable: [
				...new Set( [ ...( most ? [ best ] : [] ), ...favourites ] ),
			].sort( ( a, b ) => a - b ),
			dated: read
				.flatMap( ( book ) => book.readings )
				.filter(
					( { start, end } ) =>
						start && end.startsWith( `${ year }-` )
				).length,
		};
	}

	/**
	 * The days of reading: how many, the most in a row, the day the most pages were read, and the readings they go by,
	 * while some have no start.
	 *
	 * @param {RecapPage} recap The recap.
	 * @param {RecapDays} days  What its days count.
	 * @return {{daysReadLabel: string, streak: string, bestDay: string, dated: string}} The fields.
	 */
	#days( { year, reading_count: readings }, { read, streak, best, dated } ) {
		return {
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
						formatDay( new Date( year, 0, 1 + best.day ) )
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
	 * @param {RecapPage} recap The recap.
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
	 * Gives the chart of the screen `name` its `shares`.
	 *
	 * @param {string}  name   The screen.
	 * @param {Share[]} shares The lines.
	 */
	#fillShares( name, shares ) {
		/** @type {SharesChartElement} */ (
			this.querySelector( `[data-screen="${ name }"] shares-chart` )
		).shares = shares;
	}

	/**
	 * The records of the year, a line each: its cover, its figure, as large as the figures of the recap, and what it
	 * counts, then which record it is and the book, which it opens; the longest and the shortest by their pages, which
	 * count up as the others, the oldest by the year it first came out.
	 *
	 * @param {RecapPage}            recap The recap.
	 * @param {Object<number, Book>} books The books, by their IDs.
	 */
	#fillRecords( { records }, books ) {
		// As Books::get_date(): the work, else its edition, else its copy.
		const year = ( book ) =>
			(
				book.published_date ||
				book.edition_date ||
				book.printed_date
			).slice( 0, 4 );
		const pages = ( /** @type {Book} */ book ) => book.pages;
		const unit = ( /** @type {Book} */ book ) =>
			/* translators: what the figure of a record counts, after it: 1 947 pages. */
			_n( 'page', 'pages', book.pages, 'library' );
		/** @type {{id: number, label: string, figure: ( book: Book ) => number, year: ( book: Book ) => string, unit: ( book: Book ) => string}[]} */
		const lines = [
			{
				id: records.longest,
				label: __( 'The longest', 'library' ),
				figure: pages,
				year: () => '',
				unit,
			},
			{
				id: records.shortest,
				label: __( 'The shortest', 'library' ),
				figure: pages,
				year: () => '',
				unit,
			},
			{
				id: records.oldest,
				label: __( 'The oldest', 'library' ),
				figure: () => 0,
				year,
				unit: () => '',
			},
		];

		this.querySelector( '[data-list="records"]' ).replaceChildren(
			...lines
				.filter( ( { id } ) => books[ id ] )
				.map( ( { id, label, figure, unit: unitOf, year: yearOf } ) => {
					const book = books[ id ];
					const $item = clone( 'library-recap-record' );
					const $figure = $item.querySelector( 'count-up' );
					/** @type {HTMLAnchorElement} */
					const $title = $item.querySelector(
						'[data-field="title"]'
					);

					// The title names the book: its cover is an image of it only.
					fillBookCover(
						$item.querySelector( '[data-part="cover"]' ),
						book
					);
					fill( $item, {
						year: yearOf( book ),
						unit: unitOf( book ),
						label,
						title: book.title,
					} );
					$title.href = urlOf( { view: 'book', id } );
					$figure.hidden = ! figure( book );
					$figure.value = figure( book );

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
