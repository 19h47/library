<?php
/**
 * Ratings dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{count:int,average:float|null,chart:array} $ratings
 * @var array<int,array>                                $charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget">
	<div class="library-dashboard-header">
		<?php if ( $ratings['count'] ) : ?>
			<p class="library-dashboard-primary">
				<?php
				printf(
					/* translators: 1: number of books rated, 2: average rating (e.g. 7.5). */
					esc_html__( '%1$s rated, average %2$s/10', 'library' ),
					esc_html( number_format_i18n( $ratings['count'] ) ),
					esc_html( number_format_i18n( $ratings['average'], 1 ) )
				);
				?>
			</p>
		<?php else : ?>
			<p class="library-dashboard-secondary"><?php esc_html_e( 'No rated books yet.', 'library' ); ?></p>
		<?php endif; ?>
	</div>

	<?php
	foreach ( $charts as $chart ) {
		include __DIR__ . '/library-dashboard-chart.php';
	}
	?>
</div>
