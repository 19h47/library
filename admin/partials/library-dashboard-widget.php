<?php
/**
 * Dashboard widget view for Library.
 *
 * @package Library
 * @subpackage Library/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget" aria-label="<?php echo isset( $widget_aria_label ) ? esc_attr( $widget_aria_label ) : esc_attr__( 'Books reading overview', 'library' ); ?>">
	<div class="library-dashboard-header">
		<div class="library-dashboard-header-main">
			<p class="library-dashboard-period">
				<?php esc_html_e( 'Library', 'library' ); ?>
			</p>
			<p class="library-dashboard-primary">
				<?php
				printf(
					/* translators: 1: total books, 2: read books, 3: percent read. */
					esc_html__( '%1$d books, %2$d read (%3$d%%)', 'library' ),
					(int) $data['total'],
					(int) $data['read'],
					(int) $data['percent']
				);
				?>
			</p>
			<?php
			$rated_count = isset( $data['rated_count'] ) ? (int) $data['rated_count'] : 0;
			$rating_avg  = isset( $data['rating_avg'] ) ? $data['rating_avg'] : null;
			if ( $rated_count > 0 && null !== $rating_avg ) :
				?>
				<p class="library-dashboard-secondary">
					<?php
					printf(
						/* translators: 1: number of books rated, 2: average rating (e.g. 7.5). */
						esc_html__( '%1$d rated, average %2$s/10', 'library' ),
						$rated_count,
						esc_html( number_format_i18n( (float) $rating_avg, 1 ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<div class="library-dashboard-stats-wrap">
		<a class="library-dashboard-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=book' ) ); ?>">
			<?php esc_html_e( 'View all books', 'library' ); ?>
		</a>
	</div>

	<?php
	$chart_height_px        = 100;
	$chart_label_reserve_px = 18;
	$chart_bar_max_px       = max( 20, $chart_height_px - $chart_label_reserve_px );
	$chart_years            = isset( $data['chart_years'] ) ? $data['chart_years'] : array();
	$chart_months           = isset( $data['chart_months'] ) ? $data['chart_months'] : array();
	$chart_months_all       = isset( $data['chart_months_all'] ) ? $data['chart_months_all'] : array();
	$chart_pub_years        = isset( $data['chart_pub_years'] ) ? $data['chart_pub_years'] : array();
	$pub_year_bucket_size   = isset( $data['pub_year_bucket_size'] ) ? (int) $data['pub_year_bucket_size'] : 1;
	$chart_ratings          = isset( $data['chart_ratings'] ) ? $data['chart_ratings'] : array();
	$current_year           = isset( $data['current_year'] ) ? (int) $data['current_year'] : (int) gmdate( 'Y' );
	?>

	<?php if ( ! empty( $chart_years ) ) : ?>
		<?php
		$max_year  = max( 1, max( array_column( $chart_years, 'read' ) ) );
		$peak_year = null;
		foreach ( $chart_years as $item ) {
			if ( (int) $item['read'] === $max_year ) {
				$peak_year = (int) $item['year'];
				break;
			}
		}
		$axis_min       = 0;
		$axis_max       = $max_year;
		$axis_mid       = $axis_max > 1 ? (int) round( ( $axis_min + $axis_max ) / 2 ) : null;
		$axis_mid_label = ( null !== $axis_mid && $axis_mid > 0 && $axis_mid < $axis_max ) ? number_format_i18n( $axis_mid ) : null;
		?>
		<div class="library-dashboard-graph-wrapper" role="img" aria-label="<?php esc_attr_e( 'Books read by year', 'library' ); ?>">
			<p class="library-dashboard-graph-title"><?php esc_html_e( 'Books read by year', 'library' ); ?></p>
			<div class="library-dashboard-graph-area" style="height: <?php echo (int) $chart_height_px; ?>px;">
				<div class="library-dashboard-graph-yaxis" aria-hidden="true">
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_max ) ); ?></span>
					<?php if ( null !== $axis_mid_label ) : ?>
						<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( $axis_mid_label ); ?></span>
					<?php endif; ?>
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_min ) ); ?></span>
				</div>
				<div class="library-dashboard-graph library-dashboard-graph--years" style="height: <?php echo (int) $chart_height_px; ?>px;">
					<?php foreach ( $chart_years as $item ) : ?>
						<?php
						$val        = (int) $item['read'];
						$bar_height = 0;
						if ( $val > 0 && $max_year > 0 ) {
							$bar_height = (int) round( ( $val / $max_year ) * $chart_bar_max_px );
							$bar_height = max( 3, min( $chart_bar_max_px, $bar_height ) );
						}
						$is_peak = ( $val === $max_year && $val > 0 );
						$title   = sprintf(
							/* translators: 1: year, 2: number of books. */
							__( '%1$d: %2$d book(s) read', 'library' ),
							(int) $item['year'],
							$val
						);
						?>
						<div class="library-graph-bar <?php echo $is_peak ? 'library-graph-bar--peak' : ''; ?>" aria-label="<?php echo esc_attr( $title ); ?>" title="<?php echo esc_attr( $title ); ?>">
							<div class="library-graph-bar-fill library-graph-bar-fill--sessions" style="height: <?php echo (int) $bar_height; ?>px;" aria-hidden="true"></div>
							<span class="library-graph-bar-label" aria-hidden="true"><?php echo esc_html( (string) $item['year'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( null !== $peak_year && $max_year > 0 ) : ?>
				<p class="library-dashboard-graph-peak">
					<?php
					printf(
						/* translators: 1: number of books, 2: year. */
						esc_html__( 'Most books: %1$s (%2$s)', 'library' ),
						esc_html( number_format_i18n( $max_year ) ),
						esc_html( (string) $peak_year )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $chart_months ) && max( array_column( $chart_months, 'read' ) ) > 0 ) : ?>
		<?php
		$max_month      = max( 1, max( array_column( $chart_months, 'read' ) ) );
		$peak_month_num = null;
		foreach ( $chart_months as $item ) {
			if ( (int) $item['read'] === $max_month ) {
				$peak_month_num = (int) $item['month'];
				break;
			}
		}
		$peak_month_label = ( null !== $peak_month_num ) ? date_i18n( 'F', mktime( 0, 0, 0, $peak_month_num, 1 ) ) : '';
		$axis_min_m       = 0;
		$axis_max_m       = $max_month;
		$axis_mid_m       = $axis_max_m > 1 ? (int) round( ( $axis_min_m + $axis_max_m ) / 2 ) : null;
		$axis_mid_label_m = ( null !== $axis_mid_m && $axis_mid_m > 0 && $axis_mid_m < $axis_max_m ) ? number_format_i18n( $axis_mid_m ) : null;
		$month_labels     = array(
			1  => _x( 'J', 'January initial', 'library' ),
			2  => _x( 'F', 'February initial', 'library' ),
			3  => _x( 'M', 'March initial', 'library' ),
			4  => _x( 'A', 'April initial', 'library' ),
			5  => _x( 'M', 'May initial', 'library' ),
			6  => _x( 'J', 'June initial', 'library' ),
			7  => _x( 'J', 'July initial', 'library' ),
			8  => _x( 'A', 'August initial', 'library' ),
			9  => _x( 'S', 'September initial', 'library' ),
			10 => _x( 'O', 'October initial', 'library' ),
			11 => _x( 'N', 'November initial', 'library' ),
			12 => _x( 'D', 'December initial', 'library' ),
		);
		?>
		<div class="library-dashboard-graph-wrapper" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Books read in %d by month', 'library' ), $current_year ) ); ?>">
			<p class="library-dashboard-graph-title">
				<?php
				/* translators: %d: year. */
				echo esc_html( sprintf( __( 'Books read in %d by month', 'library' ), $current_year ) );
				?>
			</p>
			<div class="library-dashboard-graph-area" style="height: <?php echo (int) $chart_height_px; ?>px;">
				<div class="library-dashboard-graph-yaxis" aria-hidden="true">
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_max_m ) ); ?></span>
					<?php if ( null !== $axis_mid_label_m ) : ?>
						<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( $axis_mid_label_m ); ?></span>
					<?php endif; ?>
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_min_m ) ); ?></span>
				</div>
				<div class="library-dashboard-graph library-dashboard-graph--months" style="height: <?php echo (int) $chart_height_px; ?>px;">
					<?php foreach ( $chart_months as $item ) : ?>
						<?php
						$val        = (int) $item['read'];
						$bar_height = 0;
						if ( $val > 0 && $max_month > 0 ) {
							$bar_height = (int) round( ( $val / $max_month ) * $chart_bar_max_px );
							$bar_height = max( 3, min( $chart_bar_max_px, $bar_height ) );
						}
						$m       = (int) $item['month'];
						$label   = isset( $month_labels[ $m ] ) ? $month_labels[ $m ] : (string) $m;
						$is_peak = ( $val === $max_month && $val > 0 );
						$title   = sprintf(
							/* translators: 1: month name or number, 2: number of books. */
							__( '%1$s: %2$d book(s) read', 'library' ),
							date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ),
							$val
						);
						?>
						<div class="library-graph-bar <?php echo $is_peak ? 'library-graph-bar--peak' : ''; ?>" aria-label="<?php echo esc_attr( $title ); ?>" title="<?php echo esc_attr( $title ); ?>">
							<div class="library-graph-bar-fill library-graph-bar-fill--sessions" style="height: <?php echo (int) $bar_height; ?>px;" aria-hidden="true"></div>
							<span class="library-graph-bar-label" aria-hidden="true"><?php echo esc_html( $label ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( '' !== $peak_month_label && $max_month > 0 ) : ?>
				<p class="library-dashboard-graph-peak">
					<?php
					printf(
						/* translators: 1: number of books, 2: month name. */
						esc_html__( 'Most books: %1$s (%2$s)', 'library' ),
						esc_html( number_format_i18n( $max_month ) ),
						esc_html( $peak_month_label )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $chart_months_all ) && max( array_column( $chart_months_all, 'read' ) ) > 0 ) : ?>
		<?php
		$max_month_all      = max( 1, max( array_column( $chart_months_all, 'read' ) ) );
		$peak_month_num_all = null;
		foreach ( $chart_months_all as $item ) {
			if ( (int) $item['read'] === $max_month_all ) {
				$peak_month_num_all = (int) $item['month'];
				break;
			}
		}
		$peak_month_label_all = ( null !== $peak_month_num_all ) ? date_i18n( 'F', mktime( 0, 0, 0, $peak_month_num_all, 1 ) ) : '';
		$axis_min_ma          = 0;
		$axis_max_ma          = $max_month_all;
		$axis_mid_ma          = $axis_max_ma > 1 ? (int) round( ( $axis_min_ma + $axis_max_ma ) / 2 ) : null;
		$axis_mid_label_ma    = ( null !== $axis_mid_ma && $axis_mid_ma > 0 && $axis_mid_ma < $axis_max_ma ) ? number_format_i18n( $axis_mid_ma ) : null;
		$month_labels_all     = array(
			1  => _x( 'J', 'January initial', 'library' ),
			2  => _x( 'F', 'February initial', 'library' ),
			3  => _x( 'M', 'March initial', 'library' ),
			4  => _x( 'A', 'April initial', 'library' ),
			5  => _x( 'M', 'May initial', 'library' ),
			6  => _x( 'J', 'June initial', 'library' ),
			7  => _x( 'J', 'July initial', 'library' ),
			8  => _x( 'A', 'August initial', 'library' ),
			9  => _x( 'S', 'September initial', 'library' ),
			10 => _x( 'O', 'October initial', 'library' ),
			11 => _x( 'N', 'November initial', 'library' ),
			12 => _x( 'D', 'December initial', 'library' ),
		);
		?>
		<div class="library-dashboard-graph-wrapper" role="img" aria-label="<?php esc_attr_e( 'Reading distribution by month', 'library' ); ?>">
			<p class="library-dashboard-graph-title"><?php esc_html_e( 'Reading distribution by month', 'library' ); ?></p>
			<div class="library-dashboard-graph-area" style="height: <?php echo (int) $chart_height_px; ?>px;">
				<div class="library-dashboard-graph-yaxis" aria-hidden="true">
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_max_ma ) ); ?></span>
					<?php if ( null !== $axis_mid_label_ma ) : ?>
						<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( $axis_mid_label_ma ); ?></span>
					<?php endif; ?>
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_min_ma ) ); ?></span>
				</div>
				<div class="library-dashboard-graph library-dashboard-graph--months-all" style="height: <?php echo (int) $chart_height_px; ?>px;">
					<?php foreach ( $chart_months_all as $item ) : ?>
						<?php
						$val_all   = (int) $item['read'];
						$bar_h_all = 0;
						if ( $val_all > 0 && $max_month_all > 0 ) {
							$bar_h_all = (int) round( ( $val_all / $max_month_all ) * $chart_bar_max_px );
							$bar_h_all = max( 3, min( $chart_bar_max_px, $bar_h_all ) );
						}
						$m_all     = (int) $item['month'];
						$label_all = isset( $month_labels_all[ $m_all ] ) ? $month_labels_all[ $m_all ] : (string) $m_all;
						$is_peak_a = ( $val_all === $max_month_all && $val_all > 0 );
						$title_all = sprintf(
							/* translators: 1: month name or number, 2: number of books. */
							__( '%1$s: %2$d book(s) read', 'library' ),
							date_i18n( 'F', mktime( 0, 0, 0, $m_all, 1 ) ),
							$val_all
						);
						?>
						<div class="library-graph-bar <?php echo $is_peak_a ? 'library-graph-bar--peak' : ''; ?>" aria-label="<?php echo esc_attr( $title_all ); ?>" title="<?php echo esc_attr( $title_all ); ?>">
							<div class="library-graph-bar-fill library-graph-bar-fill--sessions" style="height: <?php echo (int) $bar_h_all; ?>px;" aria-hidden="true"></div>
							<span class="library-graph-bar-label" aria-hidden="true"><?php echo esc_html( $label_all ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( '' !== $peak_month_label_all && $max_month_all > 0 ) : ?>
				<p class="library-dashboard-graph-peak">
					<?php
					printf(
						/* translators: 1: number of books, 2: month name. */
						esc_html__( 'Most books: %1$s (%2$s)', 'library' ),
						esc_html( number_format_i18n( $max_month_all ) ),
						esc_html( $peak_month_label_all )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $chart_pub_years ) ) : ?>
		<?php
		$max_pub_year       = max( 1, max( array_column( $chart_pub_years, 'count' ) ) );
		$peak_pub_year_label = null;
		foreach ( $chart_pub_years as $pub_item ) {
			if ( (int) $pub_item['count'] === $max_pub_year ) {
				if ( isset( $pub_item['label'] ) && is_string( $pub_item['label'] ) && '' !== $pub_item['label'] ) {
					$peak_pub_year_label = $pub_item['label'];
				} elseif ( isset( $pub_item['year'] ) ) {
					$peak_pub_year_label = (string) (int) $pub_item['year'];
				}
				break;
			}
		}
		$axis_min_pub   = 0;
		$axis_max_pub   = $max_pub_year;
		$axis_mid_pub   = $axis_max_pub > 1 ? (int) round( ( $axis_min_pub + $axis_max_pub ) / 2 ) : null;
		$axis_mid_lbl_pub = ( null !== $axis_mid_pub && $axis_mid_pub > 0 && $axis_mid_pub < $axis_max_pub ) ? number_format_i18n( $axis_mid_pub ) : null;
		?>
		<div class="library-dashboard-graph-wrapper" role="img" aria-label="<?php esc_attr_e( 'Publication year distribution', 'library' ); ?>">
			<p class="library-dashboard-graph-title">
				<?php esc_html_e( 'Publication year distribution', 'library' ); ?>
				<?php if ( $pub_year_bucket_size > 1 ) : ?>
					<?php echo esc_html( ' ' ); ?>
					<?php esc_html_e( '(by decade)', 'library' ); ?>
				<?php endif; ?>
			</p>
			<div class="library-dashboard-graph-area" style="height: <?php echo (int) $chart_height_px; ?>px;">
				<div class="library-dashboard-graph-yaxis" aria-hidden="true">
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_max_pub ) ); ?></span>
					<?php if ( null !== $axis_mid_lbl_pub ) : ?>
						<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( $axis_mid_lbl_pub ); ?></span>
					<?php endif; ?>
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_min_pub ) ); ?></span>
				</div>
				<div class="library-dashboard-graph library-dashboard-graph--pub-years" style="height: <?php echo (int) $chart_height_px; ?>px;">
					<?php foreach ( $chart_pub_years as $pub_item ) : ?>
						<?php
						$pub_val      = (int) $pub_item['count'];
						$pub_bar_h    = 0;
						if ( $pub_val > 0 && $max_pub_year > 0 ) {
							$pub_bar_h = (int) round( ( $pub_val / $max_pub_year ) * $chart_bar_max_px );
							$pub_bar_h = max( 3, min( $chart_bar_max_px, $pub_bar_h ) );
						}
						$pub_is_peak = ( $pub_val === $max_pub_year && $pub_val > 0 );
						$pub_label = isset( $pub_item['label'] ) && is_string( $pub_item['label'] ) ? $pub_item['label'] : (string) ( (int) ( $pub_item['year'] ?? 0 ) );
						$pub_range = null;
						if ( isset( $pub_item['start'], $pub_item['end'] ) ) {
							$pub_range = sprintf( '%d–%d', (int) $pub_item['start'], (int) $pub_item['end'] );
						}
						$pub_title = sprintf(
							/* translators: 1: year or year range, 2: number of books. */
							__( '%1$s: %2$d book(s)', 'library' ),
							$pub_range ? $pub_range : $pub_label,
							$pub_val
						);
						?>
						<div class="library-graph-bar <?php echo $pub_is_peak ? 'library-graph-bar--peak' : ''; ?>" aria-label="<?php echo esc_attr( $pub_title ); ?>" title="<?php echo esc_attr( $pub_title ); ?>">
							<div class="library-graph-bar-fill library-graph-bar-fill--sessions" style="height: <?php echo (int) $pub_bar_h; ?>px;" aria-hidden="true"></div>
							<span class="library-graph-bar-label" aria-hidden="true"><?php echo esc_html( $pub_label ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( null !== $peak_pub_year_label && $max_pub_year > 0 ) : ?>
				<p class="library-dashboard-graph-peak">
					<?php
					printf(
						/* translators: 1: number of books, 2: year. */
						esc_html__( 'Most books: %1$s (%2$s)', 'library' ),
						esc_html( number_format_i18n( $max_pub_year ) ),
						esc_html( (string) $peak_pub_year_label )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $chart_ratings ) && max( array_column( $chart_ratings, 'count' ) ) > 0 ) : ?>
		<?php
		$max_rating_count = max( 1, max( array_column( $chart_ratings, 'count' ) ) );
		$peak_rating      = null;
		foreach ( $chart_ratings as $item ) {
			if ( (int) $item['count'] === $max_rating_count ) {
				$peak_rating = (int) $item['rating'];
				break;
			}
		}
		$axis_min_r       = 0;
		$axis_max_r       = $max_rating_count;
		$axis_mid_r       = $axis_max_r > 1 ? (int) round( ( $axis_min_r + $axis_max_r ) / 2 ) : null;
		$axis_mid_label_r = ( null !== $axis_mid_r && $axis_mid_r > 0 && $axis_mid_r < $axis_max_r ) ? number_format_i18n( $axis_mid_r ) : null;
		?>
		<div class="library-dashboard-graph-wrapper" role="img" aria-label="<?php esc_attr_e( 'Rating distribution (1 to 10)', 'library' ); ?>">
			<p class="library-dashboard-graph-title"><?php esc_html_e( 'Rating distribution', 'library' ); ?></p>
			<div class="library-dashboard-graph-area" style="height: <?php echo (int) $chart_height_px; ?>px;">
				<div class="library-dashboard-graph-yaxis" aria-hidden="true">
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_max_r ) ); ?></span>
					<?php if ( null !== $axis_mid_label_r ) : ?>
						<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( $axis_mid_label_r ); ?></span>
					<?php endif; ?>
					<span class="library-dashboard-graph-yaxis-tick"><?php echo esc_html( number_format_i18n( $axis_min_r ) ); ?></span>
				</div>
				<div class="library-dashboard-graph library-dashboard-graph--ratings" style="height: <?php echo (int) $chart_height_px; ?>px;">
					<?php foreach ( $chart_ratings as $item ) : ?>
						<?php
						$val        = (int) $item['count'];
						$bar_height = 0;
						if ( $val > 0 && $max_rating_count > 0 ) {
							$bar_height = (int) round( ( $val / $max_rating_count ) * $chart_bar_max_px );
							$bar_height = max( 3, min( $chart_bar_max_px, $bar_height ) );
						}
						$is_peak = ( $val === $max_rating_count && $val > 0 );
						$title   = sprintf(
							/* translators: 1: rating value, 2: number of books. */
							__( 'Rating %1$d: %2$d book(s)', 'library' ),
							(int) $item['rating'],
							$val
						);
						?>
						<div class="library-graph-bar <?php echo $is_peak ? 'library-graph-bar--peak' : ''; ?>" aria-label="<?php echo esc_attr( $title ); ?>" title="<?php echo esc_attr( $title ); ?>">
							<div class="library-graph-bar-fill library-graph-bar-fill--sessions" style="height: <?php echo (int) $bar_height; ?>px;" aria-hidden="true"></div>
							<span class="library-graph-bar-label" aria-hidden="true"><?php echo esc_html( (string) $item['rating'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php if ( null !== $peak_rating && $max_rating_count > 0 ) : ?>
				<p class="library-dashboard-graph-peak">
					<?php
					printf(
						/* translators: 1: number of books, 2: rating value. */
						esc_html__( 'Most books: %1$s (rating %2$s)', 'library' ),
						esc_html( number_format_i18n( $max_rating_count ) ),
						esc_html( (string) $peak_rating )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
