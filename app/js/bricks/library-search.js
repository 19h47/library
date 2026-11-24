import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { urlOf } from 'library-app/router';

// The views for development, out of the navigation, opened by searching their name then Enter or the button: the
// styleguide and the reference of the API. Typed, a name is a search, as any other, until then: the view does not change
// during the typing.
const HIDDEN_VIEWS = [ 'styleguide', 'api' ];

/**
 * The search of the books, around the component search-form: what is typed is the query, searched after a pause in the
 * typing, or at once with Enter or its button; the name of a view for development, then Enter or the button, opens it,
 * the search left empty.
 */
class LibrarySearchElement extends Brick {
	connect( signal ) {
		const $search = this.querySelector( 'search-form' );

		$search.addEventListener(
			'input',
			() => store.setQuery( $search.value ),
			{ signal }
		);
		// Enter or the button: the search at once, without waiting for a pause, or the view for development typed.
		$search.addEventListener(
			'submit',
			() => {
				const view = $search.value.trim().toLowerCase();

				if ( ! HIDDEN_VIEWS.includes( view ) ) {
					store.setQuery( $search.value, { now: true } );
					return;
				}

				// The query may be empty already, as when the word comes at once: the field does not follow it then.
				$search.value = '';
				store.setQuery( '' );
				navigation.navigate( urlOf( { view } ) );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.query,
				( query ) => {
					$search.value = query;
				}
			),
		];
	}
}
customElements.define( 'library-search', LibrarySearchElement );
