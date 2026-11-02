import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { urlOf } from 'library-app/router';

// The components of the app, for development: a view out of the navigation, opened by searching its name.
const STYLEGUIDE = 'styleguide';

/**
 * The search of the books: what is typed is the query; the name of the styleguide opens it, the search left empty. Its keywords show under it while it is used, as a search
 * field shows its suggestions; one taken adds to the query, the cursor in its quotes, if any, for the name to type.
 */
class LibrarySearchElement extends Brick {
	connect( signal ) {
		const $input = this.querySelector( 'input' );
		const $keywords = this.querySelector( '.library-search__keywords' );
		const hide = () => {
			$keywords.hidden = true;
		};

		$input.addEventListener(
			'input',
			() => {
				if ( STYLEGUIDE !== $input.value.trim().toLowerCase() ) {
					store.setQuery( $input.value );
					return;
				}

				store.setQuery( '' );
				navigation.navigate( urlOf( { view: STYLEGUIDE } ) );
			},
			{ signal }
		);
		this.addEventListener(
			'focusin',
			() => {
				$keywords.hidden = false;
			},
			{ signal }
		);
		// Left with the keyboard, or with a tap elsewhere: a tap on a keyword may take the focus from the field first.
		this.addEventListener(
			'focusout',
			( event ) => {
				if ( event.relatedTarget && ! this.contains( event.relatedTarget ) ) {
					hide();
				}
			},
			{ signal }
		);
		document.addEventListener(
			'pointerdown',
			( event ) => {
				if ( ! this.contains( event.target ) ) {
					hide();
				}
			},
			{ signal }
		);
		// A keyword keeps the focus in the field, and the keyboard of a phone open.
		$keywords.addEventListener( 'mousedown', ( event ) => event.preventDefault(), { signal } );
		$keywords.addEventListener(
			'click',
			( event ) => {
				const keyword = event.target.closest( '[data-keyword]' )?.dataset.keyword;

				if ( ! keyword ) {
					return;
				}

				const query = [ $input.value.trim(), keyword ].filter( Boolean ).join( ' ' );
				const caret = keyword.endsWith( '""' ) ? query.length - 1 : query.length;

				$input.value = query;
				$input.focus();
				$input.setSelectionRange( caret, caret );
				store.setQuery( query );
			},
			{ signal }
		);

		return [
			store.select(
				( state ) => state.query,
				( query ) => {
					if ( $input.value !== query ) {
						$input.value = query;
					}
				}
			),
		];
	}
}
customElements.define( 'library-search', LibrarySearchElement );
