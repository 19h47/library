import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { renderCloseAchievement } from 'library-app/render';

// How many achievements show.
const CLOSE_ACHIEVEMENTS = 3;

/** The achievements closest to be earned, with their progress; hidden when none is close. */
class LibraryCloseElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );


		return [
			store.select(
				( state ) => state.achievements.close,
				( close ) => {
					$list.replaceChildren(
						...close.slice( 0, CLOSE_ACHIEVEMENTS ).map( ( achievement, index ) => renderCloseAchievement( achievement, `library-close-${ index }` ) )
					);
					this.hidden = ! close.length;
				}
			),
		];
	}
}
customElements.define( 'library-close', LibraryCloseElement );
