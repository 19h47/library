import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';

/** The button that switches between the light and dark themes. */
class LibraryThemeToggleElement extends Brick {
	connect( signal ) {
		const $button = this.querySelector( 'button' );

		$button.addEventListener( 'click', () => store.toggleTheme(), { signal } );

		return [
			store.select(
				( state ) => state.theme,
				( theme ) => {
					// U+FE0E forces text presentation (no emoji rendering).
					$button.textContent = 'dark' === theme ? '☀︎' : '☽︎';
					$button.setAttribute( 'aria-pressed', ( 'dark' === theme ).toString() );
				}
			),
		];
	}
}
customElements.define( 'library-theme-toggle', LibraryThemeToggleElement );
