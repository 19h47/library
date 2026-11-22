/**
 * The page of a person lists their wishes, books to buy without a reading, apart from their books, which its counts and
 * its completion keep to; a page without wish hides the section.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { addBook, deleteAllBooks, openApp } from './library.js';

const AUTHOR = 'Ada Wishes';
const ALONE = 'Ada Alone';

test.describe( 'Wishes', () => {
	let wish;

	test.beforeAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );

		for ( let index = 1; index <= 3; index++ ) {
			await addBook( requestUtils, {
				title: `Wishes Book ${ index }`,
				people: [ { name: AUTHOR, role: 'author' } ],
				shelf: { readings: [ `/2024-0${ index }-10` ] },
			} );
		}

		wish = await addBook( requestUtils, {
			title: 'Wishes Wish',
			people: [ { name: AUTHOR, role: 'author' } ],
			shelf: { status: 'to-buy' },
		} );

		await addBook( requestUtils, {
			title: 'Alone Book',
			people: [ { name: ALONE, role: 'author' } ],
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await deleteAllBooks( requestUtils );
	} );

	/**
	 * The term ID of a person.
	 *
	 * @param {Object} requestUtils The request utils of WordPress.
	 * @param {string} name         Their name.
	 * @return {Promise<number>} Their term ID.
	 */
	async function getPersonId( requestUtils, name ) {
		const [ { id } ] = await requestUtils.rest( {
			path: '/wp/v2/people',
			params: { search: name, _fields: 'id' },
		} );

		return id;
	}

	test( 'the wishes of a person are apart from their books, out of their completion', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp(
			page,
			`&view=person&person=${ await getPersonId( requestUtils, AUTHOR ) }`
		);

		const person = page.locator( 'library-person' );
		const wishes = person.locator( '[data-row="wishes"]' );

		await expect( wishes ).toBeVisible();
		await expect(
			wishes.getByRole( 'heading', { name: 'To buy 1 book' } )
		).toBeVisible();
		await expect( wishes.locator( '[data-id]' ) ).toHaveCount( 1 );
		await expect(
			wishes.locator( `[data-id="${ wish.id }"]` )
		).toBeVisible();

		const books = person.locator( '[data-list="books"]' );

		await expect( books.locator( '[data-id]' ) ).toHaveCount( 3 );
		await expect( books.locator( `[data-id="${ wish.id }"]` ) ).toHaveCount(
			0
		);
		await expect(
			person.locator( '[data-field="summary"]' )
		).toContainText( '3 books' );
		// Their three books read, the wish left out: the completion is earned.
		await expect(
			person.locator( '[data-list="completion"]' )
		).toContainText( '3 books' );
		await expect(
			person.locator( '[data-list="completion"] progress' )
		).toHaveCount( 0 );
	} );

	test( 'a page without wish hides the section', async ( {
		page,
		requestUtils,
	} ) => {
		await openApp(
			page,
			`&view=person&person=${ await getPersonId( requestUtils, ALONE ) }`
		);

		const person = page.locator( 'library-person' );

		await expect(
			person.locator( '[data-list="books"] [data-id]' )
		).toHaveCount( 1 );
		await expect( person.locator( '[data-row="wishes"]' ) ).toBeHidden();
	} );
} );
