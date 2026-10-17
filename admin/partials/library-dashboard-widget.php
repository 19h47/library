<?php
/**
 * Dashboard widget view for Library.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{read:int,total:int,percent:int}                    $stats
 * @var array{count:int,average:float|null,chart:array}          $ratings
 * @var array<int,array{title:string,column:string,items:array}> $charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget">
	<div class="library-dashboard-header">
		<p class="library-dashboard-period">
			<?php esc_html_e( 'Library', 'library' ); ?>
		</p>
		<p class="library-dashboard-primary">
			<?php
			printf(
				/* translators: 1: total books, 2: read books, 3: percent read. */
				esc_html__( '%1$d books, %2$d read (%3$d%%)', 'library' ),
				(int) $stats['total'],
				(int) $stats['read'],
				(int) $stats['percent']
			);
			?>
		</p>
		<?php if ( $ratings['count'] ) : ?>
			<p class="library-dashboard-secondary">
				<?php
				printf(
					/* translators: 1: number of books rated, 2: average rating (e.g. 7.5). */
					esc_html__( '%1$d rated, average %2$s/10', 'library' ),
					(int) $ratings['count'],
					esc_html( number_format_i18n( $ratings['average'], 1 ) )
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
