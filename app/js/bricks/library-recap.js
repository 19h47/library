import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import {
	formatBooks,
	formatDate,
	formatLanguage,
	formatNumber,
	formatPages,
	formatRating,
	join,
	monthNameFormat,
} from 'library-app/format';
import {
	fill,
	fillPills,
	pageLink,
	renderAchievement,
	renderCover,
	renderMonths,
	renderRow,
	renderShares,
	renderTile,
} from 'library-app/render';
import { urlOf } from 'library-app/router';

/**
 * @import { Book, Recap } from 'library-app/api'
 * @import { Share } from 'library-app/render'
 * @import { Loading } from 'library-app/store'
 */

// The people read for the first time a recap names, the others counted after them.
const SHOWN_DISCOVERIES = 12;
// How long a figure counts up to its value, slowing down to it.
const COUNT_MS = 1000;
const reducedMotion = matchMedia( '(prefers-reduced-motion: reduce)' );

/**
 * The recap of a year, a screen per subject: the books read, against the year before, the pages read, the readings of
 * each month, the forms, the people read the most and those read for the first time, the original languages, the series,
 * the average rating with the books rated the highest, the records, the achievements earned and the books added; a
 * screen without anything to say hides. The last one leads to the recaps of the years before and after, those gone by.
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
		const isShown = recap.loaded && ! recap.error && recap.readings > 0;
		const started = recap.series.filter( ( series ) => series.started );
		const finished = recap.series.filter( ( series ) => series.finished );

		fill( this, {
			year: recap.year ? recap.year.toString() : '',
			status: this.#status( recap ),
			readings: formatNumber( recap.readings ),
			previous: this.#previous( recap ),
			rereads: recap.rereads
				? sprintf(
						/* translators: %s: number of readings of a book read again in the year. */
						_n(
							'Including %s read again',
							'Including %s read again',
							recap.rereads,
							'library'
						),
						formatNumber( recap.rereads )
				  )
				: '',
			...this.#pages( recap ),
			bestMonth: this.#bestMonth( recap ),
			discoveries: formatNumber( recap.discoveries.length ),
			discovered: _n(
				'person read for the first time',
				'people read for the first time',
				recap.discoveries.length,
				'library'
			),
			moreDiscoveries:
				recap.discoveries.length > SHOWN_DISCOVERIES
					? sprintf(
							/* translators: %s: number of people read for the first time in the year, after the ones named. */
							_n(
								'And %s more',
								'And %s more',
								recap.discoveries.length - SHOWN_DISCOVERIES,
								'library'
							),
							formatNumber(
								recap.discoveries.length - SHOWN_DISCOVERIES
							)
					  )
					: '',
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
			added: formatNumber( recap.added ),
			addedBooks: _n(
				'book added',
				'books added',
				recap.added,
				'library'
			),
			pile: this.#pile( recap.added, read.length ),
			closing: recap.year
				? sprintf(
						/* translators: %s: year of the recap. */
						__( 'That was %s', 'library' ),
						recap.year.toString()
				  )
				: '',
			...this.#years( recap ),
		} );

		this.querySelector( '[data-list="covers"]' ).replaceChildren(
			...read.filter( ( book ) => book.cover ).map( renderTile )
		);
		this.querySelector( '[data-list="months"]' ).replaceChildren(
			...renderMonths( recap.months, recap.year )
		);
		this.#fillShares(
			'forms',
			recap.forms.map( ( { name, readings } ) => ( { name, readings } ) )
		);
		this.#fillShares(
			'people',
			recap.people.map( ( { id, name, readings } ) => ( {
				name: pageLink( 'person', id, name ),
				readings,
			} ) )
		);
		fillPills(
			this.querySelector( '[data-list="discoveries"]' ),
			recap.discoveries
				.slice( 0, SHOWN_DISCOVERIES )
				.map( ( { id, name } ) => pageLink( 'person', id, name ) )
		);
		this.#fillShares(
			'languages',
			recap.languages.map( ( { code, readings } ) => ( {
				name: formatLanguage( code ),
				readings,
			} ) )
		);
		this.#fillShares(
			'series',
			recap.series.slice( 0, 5 ).map( ( { id, name, readings } ) => ( {
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
			...recap.achievements.map( renderAchievement )
		);

		this.#prepareCounts( recap.year, {
			readings: recap.readings,
			pages: recap.pages.total,
			discoveries: recap.discoveries.length,
			languages: recap.languages.length,
			series: recap.series.length,
			achievements: recap.achievements.length,
			added: recap.added,
		} );
		this.#showScreens( {
			readings: isShown,
			pages: isShown && recap.pages.counted > 0,
			months: isShown,
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

		return recap.readings ? '' : __( 'No readings that year.', 'library' );
	}

	/**
	 * The books read against the year before: how many more or fewer.
	 *
	 * @param {Recap} recap The recap.
	 * @return {string} The comparison, or empty without a reading the year before.
	 */
	#previous( { year, readings, previous } ) {
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
	#pages( { pages, days, readings } ) {
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
	 * The records: the longest book read, the shortest, by their pages, and the oldest, by the date it first came out.
	 *
	 * @param {Recap}                recap The recap.
	 * @param {Object<number, Book>} books The books loaded.
	 */
	#fillRecords( { records }, books ) {
		/** @type {{id: number, label: string, detail: ( book: Book ) => string}[]} */
		const lines = [
			{
				id: records.longest,
				label: __( 'The longest', 'library' ),
				detail: ( book ) => formatPages( book.pages ),
			},
			{
				id: records.shortest,
				label: __( 'The shortest', 'library' ),
				detail: ( book ) => formatPages( book.pages ),
			},
			{
				id: records.oldest,
				label: __( 'The oldest', 'library' ),
				detail: ( book ) =>
					formatDate( book.published_date || book.edition_date ),
			},
		];

		this.querySelector( '[data-list="records"]' ).replaceChildren(
			...lines
				.filter( ( { id } ) => books[ id ] )
				.map( ( { id, label, detail } ) => {
					const $row = renderRow( books[ id ] );
					const $label = document.createElement( 'p' );

					$label.className = 'app-item__meta';
					$label.textContent = join( [
						label,
						detail( books[ id ] ),
					] );
					$row.querySelector( '.library-row__body' ).prepend(
						$label
					);

					return $row;
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
