import { Brick } from 'library-app/brick';
import { fillRecapLink } from 'library-app/render';

/**
 * The link of the home to the recap of the year just gone by, in January only: the year is over, and its readings all
 * in, rather than in December, while it still runs.
 */
class LibraryRecapLinkElement extends Brick {
	connect() {
		const now = new Date();
		const year = now.getFullYear() - 1;

		this.hidden = 0 !== now.getMonth();
		fillRecapLink( this.querySelector( 'a' ), year );

		return [];
	}
}
customElements.define( 'library-recap-link', LibraryRecapLinkElement );
