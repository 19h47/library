import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { renderCloseAchievement } from 'library-app/render';

// How many achievements show: the others in the achievements, which the title leads to.
const CLOSE_ACHIEVEMENTS = 1;

/** The achievement closest to be earned, with its progress; hidden when none is close. */
class LibraryCloseElement extends Brick {
	connect( signal ) {
		const $list = this.querySelector( 'ul' );

		return [
			store.select(
				( state ) => state.achievements.close,
				( close ) => {
					$list.replaceChildren(
						...close
							.slice( 0, CLOSE_ACHIEVEMENTS )
							.map( ( achievement, index ) =>
								renderCloseAchievement(
									achievement,
									`library-close-${ index }`
								)
							)
					);
					this.hidden = ! close.length;
				}
			),
		];
	}
}
customElements.define( 'library-close', LibraryCloseElement );
