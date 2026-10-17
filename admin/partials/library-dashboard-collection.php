<?php
/**
 * Collection dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{books:int,authors:int,publishers:int,translated:int} $counts
 * @var array<int,array{title:string,items:array}>                $rankings
 * @var array<int,array>                                          $charts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$translated_percent = $counts['books'] ? (int) round( $counts['translated'] / $counts['books'] * 100 ) : 0;
?>

<div class="library-dashboard-widget">
	<div class="library-dashboard-header">
		<p class="library-dashboard-primary">
			<?php
			printf(
				/* translators: 1: number of books, 2: number of authors, 3: number of publishers. */
				esc_html__( '%1$s books, %2$s authors, %3$s publishers', 'library' ),
				esc_html( number_format_i18n( $counts['books'] ) ),
				esc_html( number_format_i18n( $counts['authors'] ) ),
				esc_html( number_format_i18n( $counts['publishers'] ) )
			);
			?>
		</p>
		<p class="library-dashboard-secondary">
			<?php
			printf(
				/* translators: 1: number of translated books, 2: percent of the books. */
				esc_html__( '%1$s translated (%2$d%%)', 'library' ),
				esc_html( number_format_i18n( $counts['translated'] ) ),
				(int) $translated_percent
			);
			?>
		</p>
	</div>

	<?php
	foreach ( array_filter( $rankings, fn( array $ranking ) => $ranking['items'] ) as $ranking ) {
		include __DIR__ . '/library-dashboard-ranking.php';
	}

	foreach ( $charts as $chart ) {
		include __DIR__ . '/library-dashboard-chart.php';
	}
	?>
</div>
