import { sprintf, store, strings } from 'library-app/app';
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

					fill( this, {
						summary: stats ? sprintf( strings.summary, formatNumber( stats.total ), formatNumber( stats.read ), formatNumber( stats.percent ) ) : '',
						yearToDate: year ? sprintf( strings.yearToDate, formatNumber( year.this_year ), year.year, formatNumber( year.last_year ) ) : '',
						projection: null != year?.projection ? sprintf( strings.projection, formatNumber( year.projection ) ) : '',
					} );
				}
			),
		];
	}
}
customElements.define( 'library-year', LibraryYearElement );
