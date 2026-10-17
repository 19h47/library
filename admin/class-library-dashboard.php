<?php
/**
 * Dashboard widget for Library.
 *
 * @link       https://github.com/19h47/library
 * @since      1.1.0
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Adds a small dashboard widget summarizing books read.
 *
 * @since      1.1.0
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Dashboard {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue dashboard assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'index.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'library-dashboard',
			plugin_dir_url( __FILE__ ) . 'css/library-dashboard.css',
			array(),
			LIBRARY_VERSION
		);
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget(): void {
		wp_add_dashboard_widget(
			'library_dashboard_overview',
			__( 'Books reading overview', 'library' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Render the dashboard widget content.
	 */
	public function render_widget(): void {
		$data = $this->get_reading_stats();

		$widget_aria_label = sprintf(
			/* translators: 1: total books, 2: read books. */
			__( 'Library reading overview: %1$d books, %2$d read', 'library' ),
			(int) $data['total'],
			(int) $data['read']
		);

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-widget.php';
	}

	/**
	 * Get basic stats: total books, read books, and yearly breakdown.
	 *
	 * @return array{
	 *     total:int,
	 *     read:int,
	 *     percent:int,
	 *     years:array<int,array{year:int,read:int}>
	 * }
	 */
	private function get_reading_stats(): array {
		$reading_stats = Library_Posts::get_reading_stats();

		global $wpdb;

		// Yearly breakdown based on read_date meta (Y-m-d).
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT YEAR( m_read_date.meta_value ) AS y, COUNT( DISTINCT p.ID ) AS c
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS m_read_date
					ON ( p.ID = m_read_date.post_id AND m_read_date.meta_key = %s AND m_read_date.meta_value != '' )
				WHERE p.post_type = %s
					AND p.post_status != 'trash'
				GROUP BY y
				ORDER BY y DESC",
				'read_date',
				'book'
			),
			ARRAY_A
		);

		$years = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$year  = isset( $row['y'] ) ? (int) $row['y'] : 0;
				$count = isset( $row['c'] ) ? (int) $row['c'] : 0;

				if ( $year > 0 ) {
					$years[] = array(
						'year' => $year,
						'read' => $count,
					);
				}
			}
		}

		// Rating: count and average (books with rating meta).
		$rated_count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT( DISTINCT p.ID )
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS m_rating
					ON ( p.ID = m_rating.post_id AND m_rating.meta_key = %s AND m_rating.meta_value != '' )
				WHERE p.post_type = %s
					AND p.post_status != 'trash'",
				'rating',
				'book'
			)
		);
		$rating_avg  = null;
		if ( $rated_count > 0 ) {
			$rating_avg = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT AVG( CAST( m_rating.meta_value AS DECIMAL(4,2) ) )
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->postmeta} AS m_rating
						ON ( p.ID = m_rating.post_id AND m_rating.meta_key = %s AND m_rating.meta_value != '' )
					WHERE p.post_type = %s
						AND p.post_status != 'trash'",
					'rating',
					'book'
				)
			);
			$rating_avg = is_numeric( $rating_avg ) ? round( (float) $rating_avg, 1 ) : null;
		}

		// Chart: all years with readings (oldest first for left-to-right bars).
		$current_year = (int) gmdate( 'Y' );
		$year_keys    = array_column( $years, 'year' );
		if ( ! in_array( $current_year, $year_keys, true ) ) {
			array_unshift(
				$years,
				array(
					'year' => $current_year,
					'read' => 0,
				)
			);
		}
		$chart_years = array_values( array_reverse( $years ) );

		// Chart: current year by month (1–12).
		$months_rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT MONTH( m_read_date.meta_value ) AS m, COUNT( DISTINCT p.ID ) AS c
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS m_read_date
					ON ( p.ID = m_read_date.post_id AND m_read_date.meta_key = %s AND m_read_date.meta_value != '' )
				WHERE p.post_type = %s
					AND p.post_status != 'trash'
					AND YEAR( m_read_date.meta_value ) = %d
				GROUP BY m
				ORDER BY m ASC",
				'read_date',
				'book',
				$current_year
			),
			ARRAY_A
		);
		$chart_months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$chart_months[] = array(
				'month' => $m,
				'read'  => 0,
			);
		}
		if ( is_array( $months_rows ) ) {
			foreach ( $months_rows as $row ) {
				$m = isset( $row['m'] ) ? (int) $row['m'] : 0;
				$c = isset( $row['c'] ) ? (int) $row['c'] : 0;
				if ( $m >= 1 && $m <= 12 ) {
					$chart_months[ $m - 1 ]['read'] = $c;
				}
			}
		}

		// Chart: all years combined by month (1–12).
		$months_all_rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT MONTH( m_read_date.meta_value ) AS m, COUNT( DISTINCT p.ID ) AS c
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS m_read_date
					ON ( p.ID = m_read_date.post_id AND m_read_date.meta_key = %s AND m_read_date.meta_value != '' )
				WHERE p.post_type = %s
					AND p.post_status != 'trash'
				GROUP BY m
				ORDER BY m ASC",
				'read_date',
				'book'
			),
			ARRAY_A
		);
		$chart_months_all = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$chart_months_all[] = array(
				'month' => $m,
				'read'  => 0,
			);
		}
		if ( is_array( $months_all_rows ) ) {
			foreach ( $months_all_rows as $row ) {
				$m = isset( $row['m'] ) ? (int) $row['m'] : 0;
				$c = isset( $row['c'] ) ? (int) $row['c'] : 0;
				if ( $m >= 1 && $m <= 12 ) {
					$chart_months_all[ $m - 1 ]['read'] = $c;
				}
			}
		}

		// Chart: rating distribution (1–10). Round stored value to nearest integer.
		$rating_rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT ROUND( CAST( m_rating.meta_value AS DECIMAL(4,2) ) ) AS r, COUNT( DISTINCT p.ID ) AS c
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS m_rating
					ON ( p.ID = m_rating.post_id AND m_rating.meta_key = %s AND m_rating.meta_value != '' )
				WHERE p.post_type = %s
					AND p.post_status != 'trash'
					AND CAST( m_rating.meta_value AS DECIMAL(4,2) ) >= 1
					AND CAST( m_rating.meta_value AS DECIMAL(4,2) ) <= 10
				GROUP BY r
				ORDER BY r ASC",
				'rating',
				'book'
			),
			ARRAY_A
		);
		$chart_ratings = array();
		for ( $r = 1; $r <= 10; $r++ ) {
			$chart_ratings[] = array(
				'rating' => $r,
				'count'  => 0,
			);
		}
		if ( is_array( $rating_rows ) ) {
			foreach ( $rating_rows as $row ) {
				$r = isset( $row['r'] ) ? (int) $row['r'] : 0;
				$c = isset( $row['c'] ) ? (int) $row['c'] : 0;
				if ( $r >= 1 && $r <= 10 ) {
					$chart_ratings[ $r - 1 ]['count'] = $c;
				}
			}
		}

		// Chart: publication year distribution (date_published = edition year).
		$pub_year_rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT SUBSTRING( m_pub.meta_value, 1, 4 ) AS y, COUNT( DISTINCT p.ID ) AS c
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->postmeta} AS m_pub
					ON ( p.ID = m_pub.post_id AND m_pub.meta_key = %s AND m_pub.meta_value != '' AND m_pub.meta_value REGEXP %s )
				WHERE p.post_type = %s
					AND p.post_status != 'trash'
				GROUP BY y
				HAVING y REGEXP %s AND CAST( y AS UNSIGNED ) BETWEEN 1000 AND 2100
				ORDER BY y ASC",
				'date_published',
				'^[0-9]{4}',
				'book',
				'^[0-9]{4}$'
			),
			ARRAY_A
		);
		$chart_pub_years = array();
		if ( is_array( $pub_year_rows ) ) {
			foreach ( $pub_year_rows as $row ) {
				$y = isset( $row['y'] ) ? (int) $row['y'] : 0;
				$c = isset( $row['c'] ) ? (int) $row['c'] : 0;
				if ( $y >= 1000 && $y <= 2100 ) {
					$chart_pub_years[] = array(
						'year'  => $y,
						'count' => $c,
					);
				}
			}
		}

		// If there are too many publication years to display nicely, bucket by decade.
		$chart_pub_years_display = $chart_pub_years;
		$pub_year_bucket_size    = 1;
		if ( count( $chart_pub_years_display ) > 40 ) {
			$pub_year_bucket_size = 10;
			$buckets              = array();
			foreach ( $chart_pub_years_display as $item ) {
				$year = isset( $item['year'] ) ? (int) $item['year'] : 0;
				$c    = isset( $item['count'] ) ? (int) $item['count'] : 0;
				if ( $year < 1000 || $year > 2100 ) {
					continue;
				}
				$start = (int) ( floor( $year / $pub_year_bucket_size ) * $pub_year_bucket_size );
				$end   = $start + ( $pub_year_bucket_size - 1 );
				$key   = (string) $start;
				if ( ! isset( $buckets[ $key ] ) ) {
					$buckets[ $key ] = array(
						'start' => $start,
						'end'   => $end,
						'label' => sprintf( '%ds', $start ),
						'count' => 0,
					);
				}
				$buckets[ $key ]['count'] += $c;
			}
			ksort( $buckets, SORT_NUMERIC );
			$chart_pub_years_display = array_values( $buckets );
		}

		return array(
			'total'                => $reading_stats['total'],
			'read'                 => $reading_stats['read'],
			'percent'              => $reading_stats['percent'],
			'rated_count'          => $rated_count,
			'rating_avg'           => $rating_avg,
			'chart_ratings'        => $chart_ratings,
			'years'                => $years,
			'chart_years'          => $chart_years,
			'chart_months'         => $chart_months,
			'chart_months_all'     => $chart_months_all,
			'chart_pub_years'      => $chart_pub_years_display,
			'pub_year_bucket_size' => $pub_year_bucket_size,
			'current_year'         => $current_year,
		);
	}
}
