<?php
/**
 * Reading dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{read:int,total:int,percent:int}                                $stats
 * @var array{year:int,this_year:int,last_year:int,projection:int|null}     $year_to_date
 * @var array<int,array>                                                     $charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget">
	<div class="library-dashboard-header">
		<p class="library-dashboard-primary">
			<?php
			printf(
				/* translators: 1: total books, 2: read books, 3: percent read. */
				esc_html__( '%1$s books, %2$s read (%3$s%%)', 'library' ),
				esc_html( number_format_i18n( $stats['total'] ) ),
				esc_html( number_format_i18n( $stats['read'] ) ),
				esc_html( number_format_i18n( $stats['percent'] ) )
			);
			?>
		</p>
		<p class="library-dashboard-secondary">
			<?php
			printf(
				/* translators: 1: books read this year, 2: year, 3: books read by the same date last year. */
				esc_html__( '%1$s read in %2$d, %3$s by this date last year', 'library' ),
				esc_html( number_format_i18n( $year_to_date['this_year'] ) ),
				(int) $year_to_date['year'],
				esc_html( number_format_i18n( $year_to_date['last_year'] ) )
			);
			?>
		</p>
		<?php if ( null !== $year_to_date['projection'] ) : ?>
			<p class="library-dashboard-secondary">
				<?php
				printf(
					/* translators: %s: number of books expected by the end of the year. */
					esc_html__( 'About %s by the end of the year at this pace', 'library' ),
					esc_html( number_format_i18n( $year_to_date['projection'] ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<div class="library-dashboard-stats-wrap">
		<a class="library-dashboard-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=book' ) ); ?>">
			<?php esc_html_e( 'View all books', 'library' ); ?>
		</a>
	</div>

	<?php
	foreach ( $charts as $chart ) {
		include __DIR__ . '/library-dashboard-chart.php';
	}
	?>
</div>
