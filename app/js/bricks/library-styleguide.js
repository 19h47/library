import { notKept, toBuy } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { dayFormat, formatBooks, formatDate, formatRating, formatState, join } from 'library-app/format';
import { clone, fill, renderAchievement, renderBook, renderCloseAchievement, renderCover } from 'library-app/render';
import { showError } from 'library-app/behaviors';

// A cover drawn rather than loaded, two thirds as wide as high, as most covers.
const COVER = {
	url: `data:image/svg+xml,${ encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 300"><rect width="200" height="300" fill="#8a7f6a"/><rect y="200" width="200" height="40" fill="#c9c4b8"/></svg>'
	) }`,
	width: 200,
	height: 300,
};

// The cases of a card: the samples are written in English, as code is.
const BOOKS = [
	{ title: 'A book with a cover', credits: 'An Author · A Publisher, A Collection · 2024', cover: COVER, read: true, rating: 8 },
	{ title: 'A book without a cover', credits: 'An Author · A Publisher · 2024' },
	{
		title: 'A book with an excerpt',
		credits: 'An Author · A Publisher · 2024',
		excerpt: 'Its excerpt stops at its third line on a card, and shows in full when the book opens: the rest of it is here to go past three lines, at any width the card takes.',
		cover: COVER,
		read: true,
		rating: 7.5,
	},
	{ title: 'A book not kept', credits: 'An Author · A Publisher · 2024', status: notKept, read: true },
	{ title: 'A book to buy', credits: 'An Author · A Publisher · 2024', cover: COVER, status: toBuy },
	{
		title: 'A title long enough to go over several lines, as the title of a volume of a series with its subtitle does',
		credits: 'A First Author, A Second Author and A Third Author · A Publisher With A Long Name, A Collection With A Long Name · 2024',
		cover: COVER,
	},
	{ title: 'A title alone' },
].map( ( book, index ) => ( { id: index + 1, credits: '', excerpt: '', cover: null, status: '', read: false, rating: 0, ...book } ) );

const ACHIEVEMENTS = [
	{ title: 'An achievement', subject: 'Its subject', detail: 'Its detail', date: '2026-10-28' },
	{ title: 'An achievement whose subject is a link', subject: 'Tintin', detail: '24 books', date: '2026-10-28', target: { type: 'search', search: 'Tintin' } },
];

const CLOSE_ACHIEVEMENTS = [
	{ title: 'An achievement close to it', subject: 'Its subject', detail: '1 book left: A Title', done: 22, total: 23 },
	{ title: 'An achievement without subject', subject: '', detail: '', done: 3, total: 10 },
];

/** The styleguide: the samples of the components the app renders, filled with the functions it renders them with. */
class LibraryStyleguideElement extends Brick {
	connect() {
		const samples = {
			books: () => BOOKS.map( ( book ) => renderBook( book, formatState( book ) ) ),
			month: () => [ this.#renderMonth() ],
			covers: () => [ BOOKS[ 0 ], BOOKS[ 1 ], { ...BOOKS[ 5 ], cover: null } ].map( ( book ) => renderCover( book ) ),
			achievements: () => ACHIEVEMENTS.map( renderAchievement ),
			close: () => CLOSE_ACHIEVEMENTS.map( ( achievement, index ) => renderCloseAchievement( achievement, `library-styleguide-close-${ index }` ) ),
			authors: () => [ 'An Author', '' ].map( ( name ) => this.#renderInput( 'library-edit-author', name ) ),
			dates: () => [ '2026-10-28', '2126-10-28' ].map( ( date ) => this.#renderInput( 'library-read-date', date ) ),
		};

		Object.entries( samples ).forEach( ( [ name, render ] ) => this.querySelector( `[data-sample="${ name }"]` ).replaceChildren( ...render() ) );

		// The fields in error show as the forms show theirs.
		const $invalid = this.querySelector( '[data-error]' );

		showError( $invalid, $invalid.dataset.error );
		showError( this.querySelector( '[data-sample="dates"] li:last-child input' ), 'A date later than today, as the browser says it.' );

		// The size each sample of text takes, at this width.
		this.querySelectorAll( '.library-styleguide__sizes li' ).forEach( ( $item ) => {
			const size = parseFloat( getComputedStyle( $item.querySelector( '.library-styleguide__size' ) ).fontSize );

			$item.querySelector( '[data-size]' ).textContent = `${ Math.round( size * 10 ) / 10 } px`;
		} );

		return [];
	}

	/** A month of the diary, with two readings. */
	#renderMonth() {
		const $month = clone( 'library-month' );

		fill( $month, { month: formatDate( '2026-10' ), count: formatBooks( 2 ) } );
		$month
			.querySelector( 'ul' )
			.append( ...[ BOOKS[ 0 ], BOOKS[ 1 ] ].map( ( book ) => renderBook( book, join( [ formatDate( '2026-10-28', dayFormat ), formatRating( book.rating ) ] ) ) ) );

		return $month;
	}

	/** A row of a list of inputs, from its template, with its value. */
	#renderInput( template, value ) {
		const $item = clone( template );

		$item.querySelector( 'input' ).value = value;

		return $item;
	}
}
customElements.define( 'library-styleguide', LibraryStyleguideElement );
