<?php
/**
 * Reading idea dashboard widget.
 *
 * @link       https://github.com/19h47/library
 * @since      1.1.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Suggests a book to read each week, among all the books not read yet, kept or not.
 *
 * Ideas alternate between three kinds, each one with its reason: the next volume of a series
 * in progress, a book by an author rated well, and the rest of the reading pile.
 *
 * @since      1.1.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Reading_Idea {

	/**
	 * Query argument of the "Another idea" link: the position of the idea in the week's list.
	 *
	 * @var string
	 */
	private const QUERY_ARG = 'library_idea';

	/**
	 * Average rating, out of 10, from which an author counts as rated well.
	 *
	 * @var int
	 */
	private const WELL_RATED = 8;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget(): void {
		wp_add_dashboard_widget( 'library_dashboard_idea', __( 'Reading idea of the week', 'library' ), array( $this, 'render' ) );
	}

	/**
	 * Render the idea at the requested position of this week's list.
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only position in the list.
		$position = isset( $_GET[ self::QUERY_ARG ] ) ? absint( $_GET[ self::QUERY_ARG ] ) : 0;
		$idea     = $this->get_idea( $position );
		$book     = $idea ? get_post( $idea['id'] ) : null;
		$next_url = add_query_arg( self::QUERY_ARG, $position + 1, admin_url( 'index.php' ) ) . '#library_dashboard_idea';

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-idea.php';
	}

	/**
	 * The idea at a position of this week's list, which loops.
	 *
	 * @param int $position Position, 0 for the idea of the week.
	 * @return array{id:int,reason:string,position:int,total:int,week:string}|null Null when every book is read.
	 */
	public function get_idea( int $position = 0 ): ?array {
		$ideas = $this->get_ideas();

		if ( ! $ideas ) {
			return null;
		}

		$total    = count( $ideas );
		$position = $position % $total;

		return $ideas[ $position ] + array(
			'position' => $position,
			'total'    => $total,
			'week'     => wp_date( 'o-\WW' ),
		);
	}

	/**
	 * This week's ideas: the three kinds taken in turn, each one shuffled the same way all week.
	 *
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_ideas(): array {
		$week   = wp_date( 'o-W' );
		$kinds  = array( $this->get_series_ideas(), $this->get_author_ideas(), $this->get_pile_ideas() );
		$ideas  = array();
		$chosen = array();

		foreach ( $kinds as &$kind ) {
			usort( $kind, fn( array $a, array $b ) => strcmp( md5( $week . $a['id'] ), md5( $week . $b['id'] ) ) );
		}
		unset( $kind );

		while ( array_filter( $kinds ) ) {
			foreach ( $kinds as &$kind ) {
				$idea = array_shift( $kind );

				if ( $idea && ! isset( $chosen[ $idea['id'] ] ) ) {
					$chosen[ $idea['id'] ] = true;
					$ideas[]               = $idea;
				}
			}
			unset( $kind );
		}

		return $ideas;
	}

	/**
	 * Books not read yet with their series and volume number.
	 *
	 * A book without series but with a volume number is a volume of the series named by its title, e.g. "City Hunter".
	 *
	 * @param bool $read Whether to get the books read instead.
	 * @return array<int,array{id:int,series:string,volume:int|null,status:string}>
	 */
	private function get_books( bool $read ): array {
		global $wpdb;

		$condition = $read ? 'r.post_id IS NOT NULL' : 'r.post_id IS NULL';
		$rows      = Library_Dashboard::select(
			"SELECT p.ID, p.post_title, p.post_status, s.meta_value AS series, v.meta_value AS volume
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
			LEFT JOIN {$wpdb->postmeta} AS s ON s.post_id = p.ID AND s.meta_key = 'series'
			LEFT JOIN {$wpdb->postmeta} AS v ON v.post_id = p.ID AND v.meta_key = 'volume_number'
			WHERE $condition AND {books}"
		);

		$books = array();

		foreach ( $rows as $row ) {
			$volume = preg_match( '/\d+/', (string) $row->volume, $match ) ? (int) $match[0] : null;
			$series = (string) $row->series;

			if ( '' === $series && null !== $volume ) {
				$series = $row->post_title;
			}

			$books[ (int) $row->ID ] = array(
				'id'     => (int) $row->ID,
				'series' => $series,
				'volume' => $volume,
				'status' => $row->post_status,
			);
		}

		return $books;
	}

	/**
	 * The first volume not read yet of each series with at least one volume read.
	 *
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_series_ideas(): array {
		$read_by_series = array();

		foreach ( $this->get_books( true ) as $book ) {
			if ( '' !== $book['series'] && null !== $book['volume'] ) {
				$read_by_series[ $book['series'] ] = ( $read_by_series[ $book['series'] ] ?? 0 ) + 1;
			}
		}

		$next_by_series = array();

		foreach ( $this->get_books( false ) as $book ) {
			$series = $book['series'];

			if ( ! isset( $read_by_series[ $series ] ) || null === $book['volume'] ) {
				continue;
			}

			if ( ! isset( $next_by_series[ $series ] ) || $book['volume'] < $next_by_series[ $series ]['volume'] ) {
				$next_by_series[ $series ] = $book;
			}
		}

		$ideas = array();

		foreach ( $next_by_series as $series => $book ) {
			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => sprintf(
					/* translators: 1: series, 2: number of volumes read. */
					_n( 'Next in %1$s: you have read %2$s volume.', 'Next in %1$s: you have read %2$s volumes.', $read_by_series[ $series ], 'library' ),
					$series,
					number_format_i18n( $read_by_series[ $series ] )
				),
			);
		}

		return $ideas;
	}

	/**
	 * Books not read yet by the authors rated well, pseudonyms counted for their real name.
	 *
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_author_ideas(): array {
		global $wpdb;

		$author = 'COALESCE( NULLIF( tt.parent, 0 ), tt.term_id )';

		$authors = array_column(
			Library_Dashboard::select(
				"SELECT $author AS author_id, AVG( n.meta_value ) AS average, COUNT( DISTINCT p.ID ) AS rated
				FROM {$wpdb->term_relationships} AS tr
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'library-author'
				INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
				INNER JOIN {$wpdb->postmeta} AS n ON n.post_id = p.ID AND n.meta_key = 'rating'
				WHERE {books}
				GROUP BY author_id
				HAVING rated >= 2 AND average >= %d",
				self::WELL_RATED
			),
			null,
			'author_id'
		);

		$books = $authors ? Library_Dashboard::select(
			"SELECT DISTINCT p.ID, $author AS author_id
			FROM {$wpdb->term_relationships} AS tr
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'library-author'
			INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id
			LEFT JOIN {$wpdb->postmeta} AS r ON r.post_id = p.ID AND r.meta_key = 'read' AND r.meta_value = '1'
			WHERE r.post_id IS NULL AND {books}"
		) : array();

		$ideas = array();

		foreach ( $books as $book ) {
			$rating = $authors[ $book->author_id ] ?? null;
			$term   = $rating ? get_term( (int) $book->author_id, 'library-author' ) : null;

			if ( ! $term instanceof WP_Term ) {
				continue;
			}

			$ideas[] = array(
				'id'     => (int) $book->ID,
				'reason' => sprintf(
					/* translators: 1: author, 2: average rating, 3: number of books rated. */
					__( 'You gave %1$s %2$s/10 on average over %3$s books.', 'library' ),
					$term->name,
					number_format_i18n( (float) $rating->average, 1 ),
					number_format_i18n( (int) $rating->rated )
				),
			);
		}

		return $ideas;
	}

	/**
	 * All the other books not read yet.
	 *
	 * @return array<int,array{id:int,reason:string}>
	 */
	private function get_pile_ideas(): array {
		$ideas = array();

		foreach ( $this->get_books( false ) as $book ) {
			$ideas[] = array(
				'id'     => $book['id'],
				'reason' => Library_Posts::NOT_KEPT === $book['status']
					? __( 'Not kept, still to read: borrowed?', 'library' )
					: __( 'Waiting on the reading pile.', 'library' ),
			);
		}

		return $ideas;
	}
}
