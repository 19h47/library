import { fillPills, pageLink } from 'library-app/render';
import { TermPage } from 'library-app/term-page';

/**
 * @import { PageView } from 'library-app/api'
 * @import { PublisherPage } from 'library-app/parts'
 */

/**
 * The page of a collection, or of a publisher: its name, a sub-collection after the collections it is part of, its
 * publisher and the collection it is part of, its description, how many of its books are read and the completion they
 * count for, its collections, its authors, then its books by collection, in the order of their number.
 */
class LibraryPublisherElement extends TermPage {
	/** @type {PageView} */
	static page = 'publisher';

	/**
	 * Fills its publisher, the collection it is part of, and its collections.
	 *
	 * @param {PublisherPage} publisher The collection.
	 */
	fillPage( publisher ) {
		// The publisher above, and the collection above a sub-collection.
		this.fillLink( 'publisher', 'publisher', publisher.publisher );
		this.fillLink( 'collection', 'publisher', publisher.collection );

		fillPills(
			this.querySelector( '[data-list="collections"]' ),
			publisher.collections.map( ( collection ) =>
				pageLink( 'publisher', collection.id, collection.name )
			)
		);
	}
}
customElements.define( 'library-publisher', LibraryPublisherElement );
