import { notKept, toBuy } from 'library-app/app';
import { Brick } from 'library-app/brick';
import {
	dayFormat,
	formatBooks,
	formatDate,
	formatRating,
	formatState,
	join,
} from 'library-app/format';
import {
	clone,
	fill,
	fillCover,
	fillFolded,
	fillHtml,
	renderAchievement,
	renderAuthor,
	renderBook,
	renderCloseAchievement,
	renderCover,
	renderCurrent,
	renderMonths,
	renderRow,
	renderSeries,
	renderShares,
	renderTile,
} from 'library-app/render';
import { showError } from 'library-app/behaviors';

/** @import { Book } from 'library-app/api' */

// A cover drawn rather than loaded, two thirds as wide as high, as most covers.
const COVER = {
	url: `data:image/svg+xml,${ encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 300"><rect width="200" height="300" fill="#8a7f6a"/><rect y="200" width="200" height="40" fill="#c9c4b8"/></svg>'
	) }`,
	width: 200,
	height: 300,
};

// The cases of a card: the samples are written in English, as code is. They have the fields a card reads only.
const BOOKS = [
	{
		title: 'A book with a cover, read again',
		credits: 'An Author · A Publisher, A Collection · 2024',
		cover: COVER,
		read: true,
		readings: [
			{ start: '', end: '' },
			{ start: '2026-10-20', end: '2026-10-28' },
		],
		rating: 8,
	},
	{
		title: 'A book without a cover',
		credits: 'An Author · A Publisher · 2024',
	},
	{
		title: 'A book with an excerpt',
		credits: 'An Author · A Publisher · 2024',
		excerpt:
			'Its excerpt stops at its third line on a card, and shows in full when the book opens: the rest of it is here to go past three lines, at any width the card takes.',
		cover: COVER,
		read: true,
		rating: 7.5,
		form: 'A form',
	},
	{
		title: 'A book not kept',
		credits: 'An Author · A Publisher · 2024',
		status: notKept,
		read: true,
		form: 'A form',
		// In its original language: its tag VO.
		languages: [ 'ja' ],
		edition_languages: [ 'ja' ],
	},
	{
		title: 'A book to buy',
		credits: 'An Author · A Publisher · 2024',
		cover: COVER,
		status: toBuy,
	},
	{
		title: 'A title long enough to go over several lines, as the title of a volume of a series with its subtitle does',
		credits:
			'A First Author, A Second Author and A Third Author · A Publisher With A Long Name, A Collection With A Long Name · 2024',
		cover: COVER,
	},
	{ title: 'A title alone' },
].map(
	( book, index ) =>
		/** @type {Book} */ ( {
			id: index + 1,
			credits: '',
			excerpt: '',
			cover: null,
			status: '',
			read: false,
			readings: [],
			started: '',
			rating: 0,
			form: '',
			languages: [],
			edition_languages: [],
			authors: [ { id: 1, name: 'An Author' } ],
			translators: [],
			...book,
		} )
);

const AUTHORS = [
	{
		id: 1,
		name: 'An Author',
		description:
			'<p>A description long enough to be clamped at three lines, as on the page of a series, where the page of the author shows it in full, with its paragraphs.</p>\n<p>Its second paragraph.</p>',
		book_count: 12,
	},
	{
		id: 2,
		name: 'An Author Without Description',
		description: '',
		book_count: 1,
	},
];

const SERIES = {
	id: 1,
	name: 'A Cycle, A Series',
	description: '<p>The description of a series, on the page of a person.</p>',
	book_count: 3,
	read_count: 2,
};

const ACHIEVEMENTS = [
	{
		title: 'An achievement',
		subject: 'Its subject',
		detail: 'Its detail',
		date: '2026-10-28',
	},
	{
		title: 'An achievement whose subject is a link',
		subject: 'Tintin',
		detail: '24 books',
		date: '2026-10-28',
		target: { type: 'search', search: 'Tintin' },
	},
];

const CLOSE_ACHIEVEMENTS = [
	{
		title: 'An achievement close to it',
		subject: 'Its subject',
		detail: '1 book left: A Title',
		done: 22,
		total: 23,
	},
	{
		title: 'An achievement without subject',
		subject: '',
		detail: '',
		done: 3,
		total: 10,
	},
];

// The headers of the page of a person and of a series: their fields, and their description, HTML.
const HEADERS = {
	person: {
		name: 'A Person',
		otherNames: 'Other names: A Pseudonym, A Birth Name',
		description:
			'<p>Their description, as written in the admin.</p>\n<p>A second paragraph, with <em>a word in italics</em>.</p>',
		summary: '12 books, 8 read, rated 7.5/10 on average',
	},
	series: {
		name: 'A Series',
		cycle: 'A Cycle',
		description: '',
		summary: '6 books · 4 read',
	},
};

/** The styleguide: the samples of the components the app renders, filled with the functions it renders them with. */
class LibraryStyleguideElement extends Brick {
	connect() {
		const samples = {
			books: () =>
				BOOKS.map( ( book ) =>
					renderBook( book, formatState( book ) )
				),
			grid: () =>
				BOOKS.map( ( book ) =>
					renderBook( book, formatState( book ) )
				),
			current: () =>
				[ BOOKS[ 0 ], BOOKS[ 3 ], BOOKS[ 5 ] ].map( renderCurrent ),
			rows: () => [ BOOKS[ 0 ], BOOKS[ 3 ], BOOKS[ 5 ] ].map( renderRow ),
			month: () => [ this.#renderDiaryRecap(), this.#renderMonth() ],
			volumes: () => this.#renderVolumes(),
			terms: () => [
				...AUTHORS.map( ( author ) =>
					renderAuthor( author, formatBooks( author.book_count ) )
				),
				renderSeries(
					SERIES,
					join( [
						formatBooks( SERIES.book_count ),
						`${ SERIES.read_count } read`,
					] )
				),
			],
			mosaic: () =>
				Array.from( { length: 14 }, () => renderTile( BOOKS[ 0 ] ) ),
			// A month without a reading keeps a line.
			months: () =>
				renderMonths( [ 2, 7, 5, 8, 10, 11, 0, 8, 12, 3, 2, 5 ], 2025 ),
			shares: () =>
				renderShares( [
					{ name: 'A form', readings: 45 },
					{ name: 'Another form', readings: 30 },
					{
						name: 'A form of a name long enough to go on two lines',
						readings: 3,
					},
				] ),
			achievements: () => ACHIEVEMENTS.map( renderAchievement ),
			close: () =>
				CLOSE_ACHIEVEMENTS.map( ( achievement, index ) =>
					renderCloseAchievement(
						achievement,
						`library-styleguide-close-${ index }`
					)
				),
			people: () =>
				[ 'A Person', '' ].map( ( name ) =>
					this.#renderInput( 'library-edit-person', name )
				),
			readings: () =>
				[ '/', '2026-10-20/2026-10-28', '/2126-10-28' ].map(
					( interval ) => this.#renderReading( interval )
				),
		};

		Object.entries( samples ).forEach( ( [ name, render ] ) =>
			this.querySelector( `[data-sample="${ name }"]` ).replaceChildren(
				...render()
			)
		);

		// Five authors, the ones past the first folded, as the page of a series folds them.
		fillFolded(
			this.querySelector( '[data-folded="authors"]' ),
			Array.from( { length: 5 }, ( _, index ) =>
				renderAuthor(
					AUTHORS[ index % AUTHORS.length ],
					formatBooks( AUTHORS[ index % AUTHORS.length ].book_count )
				)
			),
			( folded ) => `${ folded } other authors`
		);

		// The covers of the atoms and of the book open, and the one of the form, drawn as the others; the form can remove it.
		this.querySelectorAll( '[data-sample-cover]' ).forEach( ( $cover ) =>
			fillCover( /** @type {HTMLImageElement} */ ( $cover ), COVER )
		);
		fillCover(
			this.querySelector( '.library-image-field__preview' ),
			COVER
		);

		// The headers of the page of a person and of a series, filled as their pages fill them.
		Object.entries( HEADERS ).forEach(
			( [ name, { description, ...fields } ] ) => {
				const $header = this.querySelector(
					`[data-sample-header="${ name }"]`
				);

				fill( $header, fields );
				fillHtml(
					$header.querySelector( '[data-html="description"]' ),
					description
				);
			}
		);
		/** @type {HTMLButtonElement} */ (
			this.querySelector( '.library-image-field [data-action]' )
		).hidden = false;

		// The fields in error show as the forms show theirs.
		/** @type {HTMLInputElement} */
		const $invalid = this.querySelector( '[data-error]' );

		showError( $invalid, $invalid.dataset.error );
		showError(
			this.querySelector(
				'[data-sample="readings"] li:last-child [name="reading_end"]'
			),
			'A date later than today, as the browser says it.'
		);

		// The size each sample of text takes, at this width.
		this.querySelectorAll( '.library-styleguide__sizes li' ).forEach(
			( $item ) => {
				const size = parseFloat(
					getComputedStyle(
						$item.querySelector( '.library-styleguide__size' )
					).fontSize
				);

				$item.querySelector( '[data-size]' ).textContent = `${
					Math.round( size * 10 ) / 10
				} px`;
			}
		);

		return [];
	}

	/**
	 * The volumes of a series, as the page of the second one shows them.
	 *
	 * @return {HTMLElement[]} Their covers.
	 */
	#renderVolumes() {
		const $covers = [
			[ BOOKS[ 0 ], 'Read' ],
			[ BOOKS[ 4 ], 'In progress' ],
			[ BOOKS[ 1 ], '' ],
		].map( ( /** @type {[Book, string]} */ [ book, state ] ) =>
			renderCover( book, state )
		);

		$covers[ 1 ]
			.querySelector( 'a' )
			.setAttribute( 'aria-current', 'page' );

		return $covers;
	}

	/**
	 * The link of the diary to the recap of a year gone by, from its template.
	 *
	 * @return {HTMLElement} The link, in its paragraph.
	 */
	#renderDiaryRecap() {
		const $recap = clone( 'library-diary-recap' );

		fill( $recap, { recap: 'Recap of 2025' } );

		return $recap;
	}

	/**
	 * A month of the diary, with two readings.
	 *
	 * @return {HTMLElement} The month.
	 */
	#renderMonth() {
		const $month = clone( 'library-month' );

		fill( $month, {
			month: formatDate( '2026-10' ),
			count: formatBooks( 2 ),
		} );
		$month
			.querySelector( 'ul' )
			.append(
				...[ BOOKS[ 0 ], BOOKS[ 1 ] ].map( ( book ) =>
					renderBook(
						book,
						join( [
							formatDate( '2026-10-28', dayFormat ),
							formatRating( book.rating ),
						] )
					)
				)
			);

		return $month;
	}

	/**
	 * A row of a list of inputs, from its template, with its value.
	 *
	 * @param {string} template The ID of its template.
	 * @param {string} value    The value of its input.
	 * @return {HTMLElement} The row.
	 */
	#renderInput( template, value ) {
		const $item = clone( template );

		$item.querySelector( 'input' ).value = value;

		return $item;
	}

	/**
	 * A row of a reading, of its unknown dates, or its start and end in their inputs.
	 *
	 * @param {string} interval The reading, as an interval: 2026-10-20/2026-10-28.
	 * @return {HTMLElement} The row.
	 */
	#renderReading( interval ) {
		const [ start, end ] = interval.split( '/' );
		const $item = clone(
			'/' === interval
				? 'library-reading-unknown'
				: 'library-reading-ended'
		);

		/** @type {HTMLInputElement} */
		const $start = $item.querySelector( '[name="reading_start"]' );
		/** @type {HTMLInputElement} */
		const $end = $item.querySelector( '[name="reading_end"]' );

		$start.value = start;
		$end.value = end;

		return $item;
	}
}
customElements.define( 'library-styleguide', LibraryStyleguideElement );
