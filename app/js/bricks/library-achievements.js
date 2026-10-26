import { store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatNumber } from 'library-app/format';
import { fill, renderAchievement, renderCloseAchievement } from 'library-app/render';
import { openLinks } from 'library-app/behaviors';

/** The achievements close to be earned, with their progress, then the ones earned, latest first. */
class LibraryAchievementsElement extends Brick {
	connect( signal ) {
		const $close = this.querySelector( '[data-list="close"]' );
		const $earned = this.querySelector( '[data-list="earned"]' );

		openLinks( this, signal );

		return [
			store.select(
				( state ) => state.achievements,
				( { earned, close, error } ) => {
					fill( this, {
						status: error,
						closeCount: close.length ? formatNumber( close.length ) : '',
						earnedCount: earned.length ? formatNumber( earned.length ) : '',
					} );
					$close.replaceChildren(
						...close.map( ( achievement, index ) => renderCloseAchievement( achievement, `library-achievement-close-${ index }` ) )
					);
					$earned.replaceChildren( ...earned.map( renderAchievement ) );
				}
			),
		];
	}
}
customElements.define( 'library-achievements', LibraryAchievementsElement );
