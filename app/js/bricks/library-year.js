import { __, _n, sprintf } from 'library-app/app';
import { store } from 'library-app/store';
import { Brick } from 'library-app/brick';
import { formatNumber } from 'library-app/format';
import { fill } from 'library-app/render';

/** The reading stats: all the books, this year against last year, and the books to read at this pace. */
class LibraryYearElement extends Brick {
	connect() {
		return [
			store.select(
				( state ) => state.stats,
				( stats ) => {
					const year = stats?.year_to_date;
					/* translators: 1: total books, 2: read books, 3: percent read. */
					const summary = __(
						'%1$s books, %2$s read (%3$s%%)',
						'library'
					);
					/* translators: 1: books read this year, 2: year, 3: books read by the same date last year. */
					const yearToDate = __(
						'%1$s read in %2$d, %3$s by this date last year',
						'library'
					);
					/* translators: %s: number of books expected by the end of the year. */
					const projection = __(
						'About %s by the end of the year at this pace',
						'library'
					);
					/* translators: 1: number of books to read, 2: year they would all be read in. */
					const toRead = _n(
						'%1$s book to read, read in %2$d at this pace',
						'%1$s books to read, all read in %2$d at this pace',
						year?.to_read_count ?? 0,
						'library'
					);

					fill( this, {
						summary: stats
							? sprintf(
									summary,
									formatNumber( stats.book_count ),
									formatNumber( stats.read_count ),
									formatNumber( stats.percent )
							  )
							: '',
						yearToDate: year
							? sprintf(
									yearToDate,
									formatNumber( year.read_count ),
									year.year,
									formatNumber( year.last_year_read_count )
							  )
							: '',
						projection:
							year && null !== year.projected_read_count
								? sprintf(
										projection,
										formatNumber(
											year.projected_read_count
										)
								  )
								: '',
						toRead:
							year && null !== year.all_read_in
								? sprintf(
										toRead,
										formatNumber( year.to_read_count ),
										year.all_read_in
								  )
								: '',
					} );
				}
			),
		];
	}
}
customElements.define( 'library-year', LibraryYearElement );
