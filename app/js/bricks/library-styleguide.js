import { inLibrary, notKept, toBuy } from 'library-app/app';
import { Brick } from 'library-app/brick';
import {
	fillBookPage,
	fillBookCover,
	fillCardsGroup,
	renderAchievement,
	renderAuthor,
	renderBook,
	renderDiaryRecap,
	renderMonth,
	renderCloseAchievement,
	renderCover,
	renderCurrent,
	renderRow,
	pageLink,
	renderSeries,
	renderTile,
} from 'library-app/render';
import { fill, fillHtml } from 'library-app/dom';
import { showError } from 'library-app/behaviors';
import {
	formatBooks,
	formatDate,
	formatRating,
	join,
} from 'library-app/format';
import { formatState } from 'library-app/book-lines';

/**
 * @import { Book } from 'library-app/api'
 * @import { CountUpElement } from '../components/count-up.js'
 */

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
			{ start: '', end: '', rating: null },
			{ start: '2026-10-20', end: '2026-10-28', rating: 8 },
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
			status: inLibrary,
			read: false,
			readings: [],
			started: '',
			rating: 0,
			form: '',
			languages: [],
			edition_languages: [],
			people: [ { id: 1, name: 'An Author', role: 'author' } ],
			...book,
		} )
);

// The book open, as its page shows it: its title page, its notes and the fields of its work, of its edition and of its copy.
const BOOK_PAGE = /** @type {Book} */ ( {
	...BOOKS[ 0 ],
	main_title: 'The title of a book',
	subtitle: 'Its subtitle',
	excerpt: 'Its excerpt, as written on its back cover.',
	notes_html:
		'<p>Its notes, written in its form.</p>\n<p>A second paragraph.</p>',
	people: [
		{ id: 1, name: 'An Author', role: 'author' },
		{ id: 2, name: 'A Translator', role: 'translator' },
	],
	languages: [ 'it' ],
	edition_languages: [ 'fr' ],
	form: 'A form',
	publishers: [
		{ id: 1, name: 'A Publisher' },
		{ id: 2, name: 'A Collection' },
	],
	collection_number: '42',
	published_date: '1949',
	edition_date: '1972-11',
	isbn: '978-2-07-036024-6',
	pages: 320,
	printed_date: '2014-04-23',
	added_date: '2024-05-12 10:00:00',
} );

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
			month: () => [
				renderDiaryRecap( 2025 ),
				renderMonth(
					'2026-10',
					2,
					[ BOOKS[ 0 ], BOOKS[ 1 ] ].map( ( book ) =>
						renderBook(
							book,
							join( [
								formatDate( '2026-10-28', 'day' ),
								formatRating( book.rating ),
							] )
						)
					)
				),
			],
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
			achievements: () => ACHIEVEMENTS.map( renderAchievement ),
			close: () =>
				CLOSE_ACHIEVEMENTS.map( ( achievement, index ) =>
					renderCloseAchievement(
						achievement,
						`library-styleguide-close-${ index }`
					)
				),
		};

		Object.entries( samples ).forEach( ( [ name, render ] ) =>
			this.querySelector( `[data-sample="${ name }"]` ).replaceChildren(
				...render()
			)
		);
		// The reading idea, its book as a card, then compact, as a row.
		this.querySelectorAll( '[data-sample="reading-idea"] > *' ).forEach(
			( /** @type {HTMLElement} */ $idea ) => {
				const $row = $idea.querySelector( '[data-list="row"]' );

				fill( $idea, {
					reason: 'A book on the pile for a long time, by an author read often.',
				} );
				(
					$row ?? $idea.querySelector( '[data-list="card"]' )
				).replaceChildren(
					$row
						? renderRow( BOOKS[ 0 ] )
						: renderBook( BOOKS[ 0 ], formatState( BOOKS[ 0 ] ) )
				);
			}
		);
		// The charts of a recap: the months, a month without a reading keeping a line; the shares, then ranked, a link each;
		// the days of a year, a level each, some memorable, the days past the 300th to come; a figure.
		const $months = this.querySelector( 'months-chart' );

		$months.year = 2025;
		$months.months = [ 2, 7, 5, 8, 10, 11, 0, 8, 12, 3, 2, 5 ];
		const [ $shares, $ranked ] = this.querySelectorAll( 'shares-chart' );

		$shares.shares = [
			{ name: 'A form', readings: 45 },
			{ name: 'Another form', readings: 30 },
			{
				name: 'A form of a name long enough to go on two lines',
				readings: 3,
			},
		];
		$ranked.shares = [
			{ name: pageLink( 'person', 1, 'A person' ), readings: 12 },
			{
				name: pageLink(
					'person',
					2,
					'A person of a name long enough to be cut short'
				),
				readings: 7,
			},
		];
		this.querySelector( 'days-calendar' ).value = {
			year: 2025,
			levels: Array.from(
				{ length: 300 },
				( _day, day ) => ( day * 7 ) % 5
			),
			memorable: [ 60, 200 ],
		};
		/** @type {CountUpElement} */ (
			this.querySelector( '[data-sample="count-up"] count-up' )
		).value = 82;

		// A progress, named by the line above it.
		const $progressBar = this.querySelector( 'progress-bar' );

		$progressBar.max = 14;
		$progressBar.value = 9;
		$progressBar.labelledBy = 'library-styleguide-progress-bar';
		// The period of a reading, its start and its end.
		this.querySelector( '[data-sample="reading-period"]' ).querySelector(
			'reading-period'
		).value = '2026-10-20/2026-10-28';
		// A date known by its month, the second one.
		this.querySelectorAll( 'partial-date' )[ 1 ].value = '1972-11';
		// The status of the app offline, shown whatever the network.
		this.querySelector( 'offline-status' ).hidden = false;
		// The scan of a barcode, shown whatever the browser reads, a photo without one.
		const $scan = this.querySelector( 'barcode-scan' );

		$scan.hidden = false;
		fill( $scan, {
			message: 'No barcode found in the photo: take it again, closer.',
		} );
		// The readings of a book, in the list of their field: of unknown dates, rated, then in error.
		this.querySelector( 'inputs-list' ).value = [
			'/',
			'2026-10-20/2026-10-28#8',
			'/2126-10-28',
		];

		// The book open, the link to the recap, what the catalogue tells of a book to add and the keywords of a search, filled
		// as their bricks fill them: a book of the catalogue on none of the shelves of the reader.
		fillBookPage( this.querySelector( '[data-sample="book"]' ), BOOK_PAGE );
		fill( this.querySelector( '[data-sample="recap-link"]' ), {
			year: '2025',
		} );
		this.querySelector(
			'[data-sample="catalogue-status-duplicate"]'
		).querySelector( 'catalogue-status' ).duplicate = {
			title: 'A title',
			url: '#top',
			kind: 'catalogue',
		};
		this.querySelector(
			'[data-sample="catalogue-status-catalog"]'
		).querySelector( 'catalogue-status' ).catalog = 'found';
		this.querySelector( '[data-sample="keywords"]' ).querySelector(
			'search-form'
		).open = true;
		// What the list of the filters finds, as the brick around them in the app says it.
		this.querySelector( 'filters-sheet' ).found = '15 books';

		// Six authors, the ones past the first folded, as the page of a series folds them.
		fillCardsGroup(
			this.querySelector( '[data-sample="cards-group"]' ),
			Array.from( { length: 6 }, ( _, index ) =>
				renderAuthor(
					AUTHORS[ index % AUTHORS.length ],
					formatBooks( AUTHORS[ index % AUTHORS.length ].book_count )
				)
			),
			( folded ) => `${ folded } other authors`
		);

		// The people of a book, each in the group of its role.
		this.querySelector( 'input-groups' ).value = [
			{ value: 'A Person', group: 'author' },
			{ value: 'A Translator', group: 'translator' },
			{ value: '', group: 'translator' },
		];

		// The covers of the atoms and of the book open, and the one of the form, drawn as the others; the form can remove it.
		this.querySelectorAll( '[data-sample-cover]' ).forEach( ( $cover ) =>
			fillBookCover( /** @type {HTMLElement} */ ( $cover ), {
				cover: COVER,
				title: '',
			} )
		);
		fillBookCover( this.querySelector( '[data-sample-frame]' ), {
			cover: null,
			title: 'A book without its cover, and a title long enough to be cut',
		} );
		this.querySelector( 'image-field' ).cover = COVER;

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
		this.querySelectorAll( '[data-list="sizes"] li' ).forEach(
			( $item ) => {
				const size = parseFloat(
					getComputedStyle(
						$item.querySelector( '[data-part="size"]' )
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
}
customElements.define( 'library-styleguide', LibraryStyleguideElement );
