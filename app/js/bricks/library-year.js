import { __, sprintf, store } from 'library-app/app';
import { Brick } from 'library-app/brick';
import { formatNumber } from 'library-app/format';
import { fill } from 'library-app/render';

/** The reading stats: all the books, and this year against last year. */
class LibraryYearElement extends Brick {
	connect() {
		return [
			store.select(
				( state ) => state.stats,
				( stats ) => {
					const year = stats?.year_to_date;
					/* translators: 1: total books, 2: read books, 3: percent read. */
					const summary = __( '%1$s books, %2$s read (%3$s%%)', 'library' );
					/* translators: 1: books read this year, 2: year, 3: books read by the same date last year. */
					const yearToDate = __( '%1$s read in %2$d, %3$s by this date last year', 'library' );
					/* translators: %s: number of books expected by the end of the year. */
					const projection = __( 'About %s by the end of the year at this pace', 'library' );

					fill( this, {
						summary: stats ? sprintf( summary, formatNumber( stats.total ), formatNumber( stats.read ), formatNumber( stats.percent ) ) : '',
						yearToDate: year ? sprintf( yearToDate, formatNumber( year.this_year ), year.year, formatNumber( year.last_year ) ) : '',
						projection: null != year?.projection ? sprintf( projection, formatNumber( year.projection ) ) : '',
					} );
				}
			),
		];
	}
}
customElements.define( 'library-year', LibraryYearElement );
