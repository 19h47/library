import { editUrl, store, toBuy } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { fill, fillCover } from 'library-app/render';
import { inputList } from 'library-app/behaviors';

/** Options of a datalist or a select, such as the names to suggest. */
function renderOptions( terms, value = ( term ) => term.name ) {
	return terms.map( ( term ) => new Option( term.name, value( term ) ) );
}

/**
 * The form of a book, in a modal dialog: empty for a book to add, else with the values of the book open, over it.
 * Its cover, title, excerpt, notes, authors, publisher and collection, ISBN, its status when it is added, to buy from the
 * books to buy (the reading of a book changes it after), and more details. The names typed are suggested
 * among those of the library, the collections among those of the publisher; a name not found is added.
 * Closing the form drops it.
 */
class LibraryEditElement extends Brick {
	#preview = '';
	// Whether the cover of the book was removed: a photo picked replaces it either way.
	#removed = false;

	connect( signal ) {
		const $dialog = this.querySelector( 'dialog' );
		const $heading = this.querySelector( '#library-edit-heading' );
		const $form = this.querySelector( '.library-edit__form' );
		const { title: $title, excerpt: $excerpt, content: $content, publisher: $publisher, collection: $collection, language: $language, cover: $cover, status: $status } = $form.elements;
		// The fields of the meta are named after them.
		const $meta = [ ...$form.querySelectorAll( '[data-meta]' ) ];
		const $authors = this.querySelector( '[data-list="authors"]' );
		const $addAuthor = this.querySelector( '[data-action="add-author"]' );
		const $preview = this.querySelector( '.library-edit__preview' );
		const $removeCover = this.querySelector( '[data-action="remove-cover"]' );
		const $details = this.querySelector( '.library-edit__details' );
		const $admin = this.querySelector( '[data-link="admin"]' );
		const $submit = $form.querySelector( '[type="submit"]' );
		const $suggestions = {
			authors: this.querySelector( '#library-edit-authors' ),
			publishers: this.querySelector( '#library-edit-publishers' ),
			collections: this.querySelector( '#library-edit-collections' ),
		};

		const addAuthor = inputList( { $list: $authors, $add: $addAuthor, id: 'library-edit-author', signal } );

		// The collections of the publisher typed, when the library has it; a collection needs a publisher.
		const suggestCollections = () => {
			const name = $publisher.value.trim().toLowerCase();
			const { publishers } = store.get().terms;
			const publisher = publishers.find( ( term ) => ! term.parent && name === term.name.toLowerCase() );

			$collection.disabled = ! name;
			$suggestions.collections.replaceChildren( ...renderOptions( publisher ? publishers.filter( ( term ) => publisher.id === term.parent ) : [] ) );
		};

		// The cover shown: the photo picked, else the one of the book, unless it was removed.
		const showCover = () => {
			const { books, editing } = store.get();
			const file = $cover.files[ 0 ];
			const bookCover = this.#removed ? null : ( books[ editing.id ]?.cover ?? null );

			URL.revokeObjectURL( this.#preview );
			this.#preview = file ? URL.createObjectURL( file ) : '';
			fillCover( $preview, file ? { url: this.#preview } : bookCover );
			$removeCover.hidden = ! file && ! bookCover;
		};

		$dialog.addEventListener( 'close', () => store.closeEdit(), { signal } );
		$publisher.addEventListener( 'input', suggestCollections, { signal } );
		$cover.addEventListener( 'change', showCover, { signal } );

		$removeCover.addEventListener(
			'click',
			() => {
				$cover.value = '';
				this.#removed = true;
				showCover();
				// The button hides: the focus goes to the one that picks a photo.
				$cover.focus();
			},
			{ signal }
		);

		$form.addEventListener(
			'submit',
			( event ) => {
				const data = new FormData( $form );
				const cover = $cover.files[ 0 ] ?? null;
				const book = {
					title: data.get( 'title' ),
					excerpt: $excerpt.value,
					content: $content.value,
					authors: data.getAll( 'author' ).map( ( name ) => name.trim() ).filter( Boolean ),
					publisher: data.get( 'publisher' ),
					collection: data.get( 'collection' ) ?? '',
					language: Number( data.get( 'language' ) ),
					meta: Object.fromEntries( $meta.map( ( $field ) => [ $field.name, $field.value ] ) ),
				};

				event.preventDefault();

				if ( store.get().editing.id ) {
					store.editBook( { ...book, cover, coverRemoved: this.#removed } );
				} else {
					store.addBook( { ...book, cover, status: $status.value } );
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

					const { id, book } = store.get().editing;

					// The form opens on the values of the book, or empty, its details folded unless they hold some.
					$form.reset();
					this.#removed = false;
					$heading.textContent = id ? $heading.dataset.edit : $heading.dataset.add;
					$admin.href = editUrl + id;
					$admin.parentElement.hidden = ! id;
					$status.disabled = Boolean( id );
					$status.parentElement.hidden = Boolean( id );
					$status.value = 'to-buy' === store.get().view ? toBuy : 'publish';
					$authors.replaceChildren();
					book?.authors.forEach( ( name ) => addAuthor( name ) );

					if ( ! $authors.children.length ) {
						addAuthor();
					}

					if ( book ) {
						$title.value = book.title;
						$excerpt.value = book.excerpt;
						$content.value = book.content;
						$publisher.value = book.publisher;
						$collection.value = book.collection;
						$language.value = ( book.language || '' ).toString();
						$meta.forEach( ( $field ) => {
							$field.value = book.meta[ $field.name ] ?? '';
						} );
					}

					$details.open = [ ...$details.querySelectorAll( 'input, select' ) ].some( ( $field ) => $field.value );
					showCover();
					suggestCollections();
					$dialog.showModal();
					$dialog.scrollTop = 0;
				}
			),
			store.select(
				( state ) => state.editing,
				( editing ) => {
					// A book whose values could not be loaded is not saved.
					$submit.disabled = editing.loading || Boolean( editing.id && ! editing.book );
					fill( this, { error: editing.error } );
				}
			),
			store.select(
				( state ) => state.terms,
				( terms ) => {
					const $none = $language.options[ 0 ];

					$suggestions.authors.replaceChildren( ...renderOptions( terms.authors ) );
					$suggestions.publishers.replaceChildren( ...renderOptions( terms.publishers.filter( ( term ) => ! term.parent ) ) );
					$language.replaceChildren( $none, ...renderOptions( terms.languages, ( term ) => term.id ) );
					// The languages may come once the form is open, the first time.
					$language.value = ( store.get().editing.book?.language || '' ).toString();
					suggestCollections();
				}
			),
		];
	}
}
customElements.define( 'library-edit', LibraryEditElement );
