import { fillPills, pageLink } from 'library-app/render';
import { fill } from 'library-app/dom';
import { TermPage } from 'library-app/term-page';

/**
 * @import { PageView } from 'library-app/api'
 * @import { SeriesPage } from 'library-app/parts'
 */

/**
 * The page of a series, or of a cycle: its name after its cycles, its cycle, its ISSN for a magazine, its description,
 * how many of its books are read and the completion they count for, the series of a cycle, its authors, then its books
 * in reading order, those of its series with them.
 */
class LibrarySeriesElement extends TermPage {
	/** @type {PageView} */
	static page = 'series';

	/**
	 * Fills its cycle, its ISSN and the series of a cycle.
	 *
	 * @param {SeriesPage} series The series.
	 */
	fillPage( series ) {
		fill( this, { issn: series.issn } );
		this.fillLink( 'cycle', 'series', series.cycle );

		fillPills(
			this.querySelector( '[data-list="parts"]' ),
			series.parts.map( ( part ) =>
				pageLink( 'series', part.id, part.name )
			)
		);
	}
}
customElements.define( 'library-series', LibrarySeriesElement );
