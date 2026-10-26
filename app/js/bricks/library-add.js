import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { fill, fillCover } from 'library-app/render';
import { inputList } from 'library-app/behaviors';

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

		const addAuthor = inputList( { $list: $authors, $add: $addAuthor, id: 'library-add-author', signal } );

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
		$publisher.addEventListener( 'input', suggestCollections, { signal } );
		$cover.addEventListener( 'change', () => showCover( $cover.files[ 0 ] ), { signal } );

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
