import { __, editUrl, inLibrary, toBuy } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { fill } from 'library-app/render';
import { join } from 'library-app/format';
import { showErrors, showSaving } from 'library-app/behaviors';
import { urlOf } from 'library-app/router';

/**
 * @import { Field } from 'library-app/behaviors'
 * @import { CatalogBook, Credit } from 'library-app/api'
 * @import { Catalog } from 'library-app/store'
 * @import { CheckboxesFieldElement } from '../components/checkboxes-field.js'
 */

/**
 * The fields of the form of a book, by their names.
 *
 * @typedef {Object} EditFields
 * @property {HTMLInputElement}    title      Its title.
 * @property {HTMLTextAreaElement} excerpt    Its summary in a few words.
 * @property {HTMLTextAreaElement} content    Its notes.
 * @property {HTMLInputElement}    publisher  Its publisher.
 * @property {HTMLInputElement}    collection Its collection.
 * @property {HTMLInputElement}    series     Its series.
 * @property {HTMLSelectElement}   form       Its form.
 * @property {HTMLInputElement}    cover      The photo of its cover.
 * @property {HTMLSelectElement}   status     The status of a book to add.
 * @property {HTMLInputElement}    isbn       Its ISBN.
 */

/**
 * Opens the page of the book `id`, added or bought, for its reading, then closes the form over it, rather than over
 * the view it was opened from; none, the form stays open and says why.
 *
 * @param {number} id The book, 0 for none.
 */
function openBook( id ) {
	if ( id ) {
		navigation
			.navigate( urlOf( { view: 'book', id } ) )
			.finished.finally( () => store.closeEdit() );
	}
}

// Compares names whatever their case and accents.
const nameCollator = new Intl.Collator( document.documentElement.lang, {
	sensitivity: 'base',
} );

/**
 * Options of a datalist, the names to suggest.
 *
 * @param {Array<{name: string}>} terms The names.
 * @return {HTMLOptionElement[]} The options.
 */
function renderOptions( terms ) {
	return terms.map( ( term ) => new Option( term.name ) );
}

/**
 * The form of a book, in a modal dialog: empty for a book to add, else with the values of the book open, over it.
 * The fields most books have, in the order a book in hand gives them: its cover, ISBN, title, people, series and volume,
 * publisher, collection and its number, form and printed date, its status when it is added, the one of the list shown
 * (the reading of a book changes it after); then more details, which few books have, folded, their summary counting
 * the ones filled in: subtitle, first published, edition date, pages, original languages, edition languages, collective work,
 * excerpt and notes. A book added is in the language of the reader, unless the catalogue says otherwise.
 * The names typed are suggested among those of the library, the collections among those of the publisher, after the
 * ones they are part of, the series after their cycles; a name not found is added. For a book to add, an ISBN typed that
 * a book has already shows it, which opens, and a book to buy is bought from there; else the catalogue of the BnF fills
 * in the fields left empty, the names as the library gives them. Where the browser reads EAN-13 barcodes, a photo of the
 * barcode of a book types its ISBN, as if typed: its component, barcode-scan, does it.
 * Closing the form drops it.
 */
class LibraryEditElement extends Brick {
	connect( signal ) {
		const $dialog = this.querySelector( 'dialog' );
		/** @type {HTMLElement} */
		const $heading = this.querySelector( '#library-edit-heading' );
		/** @type {HTMLFormElement} */
		const $form = this.querySelector( '[data-part="form"]' );
		const {
			title: $title,
			excerpt: $excerpt,
			content: $content,
			publisher: $publisher,
			collection: $collection,
			series: $series,
			form: $bookForm,
			cover: $cover,
			status: $status,
			isbn: $isbn,
		} = /** @type {HTMLFormControlsCollection & EditFields} */ (
			$form.elements
		);
		// The fields of the meta are named after them.
		const $meta = /** @type {HTMLInputElement[]} */ ( [
			...$form.querySelectorAll( '[data-meta]' ),
		] );
		// The languages, a checkbox each, its value a term ID.
		/** @type {CheckboxesFieldElement} */
		const $languages = $form.querySelector(
			'checkboxes-field[name="languages"]'
		);
		/** @type {CheckboxesFieldElement} */
		const $editionLanguages = $form.querySelector(
			'checkboxes-field[name="edition_languages"]'
		);
		// A checkbox stands for a boolean, its value being the one it sends once checked.
		/** @type {( $field: Field ) => string|boolean} */
		const getValue = ( $field ) =>
			'checkbox' === $field.type
				? /** @type {HTMLInputElement} */ ( $field ).checked
				: $field.value;
		// The names, each in the group of its role.
		const $people = this.querySelector( 'input-groups' );
		const $coverField = this.querySelector( 'image-field' );
		/** @type {HTMLDetailsElement} */
		const $details = this.querySelector( '[data-part="details"]' );
		const $summary = $details.querySelector( 'summary' );
		/** @type {HTMLAnchorElement} */
		const $admin = this.querySelector( '[data-link="admin"]' );
		/** @type {HTMLButtonElement} */
		const $submit = $form.querySelector( '[type="submit"]' );
		/** @type {HTMLAnchorElement} */
		const $duplicate = this.querySelector( '[data-field="duplicate"]' );
		/** @type {HTMLButtonElement} */
		const $buy = this.querySelector( '[data-action="buy"]' );
		const $suggestions = {
			people: this.querySelector( '#library-edit-people' ),
			publishers: this.querySelector( '#library-edit-publishers' ),
			collections: this.querySelector( '#library-edit-collections' ),
			series: this.querySelector( '#library-edit-series-paths' ),
		};

		const clearErrors = showErrors( $form, signal );

		// The summary of the details counts the fields filled in, as the button of the filters does: the checkboxes of a field once.
		const countDetails = () => {
			const filled = new Set(
				/** @type {Field[]} */ ( [
					...$details.querySelectorAll( 'input, select, textarea' ),
				] )
					.filter( getValue )
					.map( ( $field ) => $field.name )
			).size;

			$summary.textContent = join( [
				$summary.dataset.label,
				filled && filled.toString(),
			] );
		};

		$details.addEventListener( 'input', countDetails, { signal } );

		// The collections of the publisher typed, at any depth, when the library has it; a collection needs a publisher.
		// Its name is compared case and accents aside, as the server finds a term by its name.
		const suggestCollections = () => {
			const name = $publisher.value.trim();
			const { publishers } = store.get().terms;
			const publisher = publishers.find(
				( term ) =>
					! term.parent &&
					0 === nameCollator.compare( name, term.name )
			);
			const collections = publisher
				? publishers.filter(
						( term ) =>
							term.parent && publisher.id === term.publisher
				  )
				: [];

			$collection.disabled = ! name;
			$suggestions.collections.replaceChildren(
				...renderOptions(
					collections.map( ( term ) => ( {
						...term,
						name: term.collection,
					} ) )
				)
			);
		};

		/**
		 * The rows of the names, each in the group of its role, one empty row of an author for none.
		 *
		 * @param {Credit[]} people The names.
		 */
		const showPeople = ( people ) => {
			$people.value = people.length
				? people.map( ( { name, role } ) => ( {
						value: name,
						group: role,
				  } ) )
				: [ { value: '', group: 'author' } ];
		};

		/**
		 * The fields left empty take the values of the book of the catalogue: what was typed stays, and the names of the
		 * people only when none was.
		 *
		 * @param {CatalogBook} book The book of the catalogue.
		 */
		const fillEmpty = ( book ) => {
			/** @type {Array<[Field, string]>} */
			const values = [
				[ $title, book.title ],
				[ $publisher, book.publisher ],
				[ $series, book.series ],
				[ $bookForm, ( book.form || '' ).toString() ],
				...$meta.map( ( $field ) => {
					const value = ( book.meta[ $field.name ] ?? '' ).toString();

					return /** @type {[Field, string]} */ ( [ $field, value ] );
				} ),
			];
			values
				.filter(
					( [ $field, value ] ) => value && ! getValue( $field )
				)
				.forEach( ( [ $field, value ] ) => {
					$field.value = value;
				} );

			if (
				book.people.length &&
				$people.value.every( ( { value } ) => ! value )
			) {
				showPeople( book.people );
			}

			if ( ! $languages.value.length ) {
				$languages.value = book.languages;
			}

			// The edition languages left as the form gave them, the language of the reader, take the ones of the catalogue.
			if (
				book.meta.edition_languages.length &&
				$editionLanguages.value.join() ===
					$editionLanguages.defaultValue.join()
			) {
				$editionLanguages.value = book.meta.edition_languages;
			}

			// A collection needs its publisher, which enables it.
			suggestCollections();

			if (
				book.collection &&
				! $collection.value &&
				! $collection.disabled
			) {
				$collection.value = book.collection;
			}

			countDetails();
		};

		$dialog.addEventListener( 'close', () => store.closeEdit(), {
			signal,
		} );

		$isbn.addEventListener(
			'change',
			() => {
				if ( ! store.get().editing.id ) {
					store.checkIsbn( $isbn.value );
				}
			},
			{ signal }
		);

		// The link to the book opens it under the form, then closes the form over it, as openBook() does: closed at the
		// click, the form would forget the book, and the link with it, before the browser follows it.
		$duplicate.addEventListener(
			'click',
			() =>
				navigation.addEventListener(
					'navigatesuccess',
					() => store.closeEdit(),
					{ once: true, signal }
				),
			{ signal }
		);
		$buy.addEventListener(
			'click',
			async () =>
				openBook(
					await store.buyBook( store.get().editing.duplicate )
				),
			{ signal }
		);
		$publisher.addEventListener( 'input', suggestCollections, { signal } );

		$form.addEventListener(
			'submit',
			( event ) => {
				const data = new FormData( $form );
				const cover = $cover.files[ 0 ] ?? null;
				const book = {
					title: /** @type {string} */ ( data.get( 'title' ) ),
					excerpt: $excerpt.value,
					content: $content.value,
					// Each name with the role of its group.
					people: $people.value
						.map( ( { value, group } ) => ( {
							name: value.trim(),
							role: group,
						} ) )
						.filter( ( person ) => person.name ),
					publisher: /** @type {string} */ (
						data.get( 'publisher' )
					),
					collection:
						/** @type {string|null} */ (
							data.get( 'collection' )
						) ?? '',
					// After its cycles, as typed: a name not found adds it.
					series: /** @type {string} */ (
						data.get( 'series' )
					).trim(),
					languages: data.getAll( 'languages' ).map( Number ),
					form: Number( data.get( 'form' ) ),
					meta: {
						...Object.fromEntries(
							$meta.map( ( $field ) => [
								$field.name,
								getValue( $field ),
							] )
						),
						edition_languages: data
							.getAll( 'edition_languages' )
							.map( Number ),
					},
				};

				event.preventDefault();

				if ( store.get().editing.id ) {
					store.editBook( {
						...book,
						cover,
						coverRemoved: data.has( 'cover_removed' ),
					} );
				} else {
					store
						.addBook( { ...book, cover, status: $status.value } )
						.then( openBook );
				}
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.editing.open,
				( isOpen ) => {
					if ( ! isOpen ) {
						$dialog.close();
						return;
					}

					const { editing, view, status, form } = store.get();
					const { id, book } = editing;
					// A book added joins the list shown: to buy from the books to buy, of the status and form chosen from the books.
					const listedStatus = { books: status, 'to-buy': toBuy }[
						view
					];

					// The form opens on the values of the book, or empty, its details folded.
					$form.reset();
					clearErrors();
					$heading.textContent = id
						? $heading.dataset.edit
						: $heading.dataset.add;
					$admin.href = editUrl + id;
					$admin.parentElement.hidden = ! id;
					$status.disabled = Boolean( id );
					$status.parentElement.hidden = Boolean( id );
					$status.value = listedStatus || inLibrary;
					$bookForm.value = 'books' === view ? form : '';
					showPeople( book?.people ?? [] );

					if ( book ) {
						$title.value = book.title;
						$excerpt.value = book.excerpt;
						$content.value = book.content;
						$publisher.value = book.publisher;
						$collection.value = book.collection;
						$series.value = book.series;
						$languages.value = book.languages;
						$editionLanguages.value = book.meta.edition_languages;
						$bookForm.value = ( book.form || '' ).toString();
						$meta.forEach( ( $field ) => {
							const value = book.meta[ $field.name ] ?? '';

							if ( 'checkbox' === $field.type ) {
								$field.checked = Boolean( value );
							} else {
								$field.value = value.toString();
							}
						} );
					}

					$details.open = false;
					countDetails();
					// The cover the book has; the field shows a photo picked itself.
					$coverField.cover = store.get().books[ id ]?.cover ?? null;
					suggestCollections();
					$dialog.showModal();
					$dialog.scrollTop = 0;
				}
			),
			store.select(
				( state ) => state.editing,
				( editing ) => {
					// A book whose values could not be loaded is not saved.
					$submit.disabled =
						editing.loading ||
						Boolean( editing.id && ! editing.book );
					showSaving( $submit, editing.loading );
					fill( this, { error: editing.error } );
				}
			),
			store.select(
				( state ) => state.editing.catalog,
				/** @param {Catalog} catalog The search of the ISBN typed in the catalogue. */
				( catalog ) => {
					const messages = {
						'': '',
						searching: __(
							'Searching the catalogue of the BnF…',
							'library'
						),
						found: __(
							'Filled in from the catalogue of the BnF: check what it gives.',
							'library'
						),
						unknown: __(
							'The catalogue of the BnF has no book of this ISBN.',
							'library'
						),
						unreachable: __(
							'The catalogue of the BnF does not answer: try again later.',
							'library'
						),
					};

					fill( this, { catalog: messages[ catalog.status ] } );

					if ( catalog.book ) {
						fillEmpty( catalog.book );
					}
				}
			),
			store.select(
				( state ) => state.editing.duplicate,
				( id ) => {
					const book = store.get().books[ id ];

					fill( this, { duplicate: book?.title ?? '' } );
					$duplicate.href = urlOf( { view: 'book', id } );
					$buy.hidden = toBuy !== book?.status;
				}
			),
			store.select(
				( state ) => state.terms,
				( terms ) => {
					$suggestions.people.replaceChildren(
						...renderOptions( terms.people )
					);
					$suggestions.publishers.replaceChildren(
						...renderOptions(
							terms.publishers.filter( ( term ) => ! term.parent )
						)
					);
					$suggestions.series.replaceChildren(
						...renderOptions( terms.series )
					);
					suggestCollections();
				}
			),
		];
	}
}
customElements.define( 'library-edit', LibraryEditElement );
