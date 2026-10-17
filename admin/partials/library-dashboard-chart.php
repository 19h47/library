<?php
/**
 * Dashboard bar chart.
 *
 * The bars are hidden from assistive technologies, which read the same data in a table.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{title:string,column:string,items:array<int,array{label:string,name:string,value:int}>} $chart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$values   = array_column( $chart['items'], 'value' );
$max      = max( $values );
$peak     = $chart['items'][ array_search( $max, $values, true ) ];
$middle   = (int) round( $max / 2 );
$ticks    = array_unique( array( $max, $middle, 0 ) );
$bar_area = 82;
?>

<figure class="library-dashboard-graph-wrapper">
	<figcaption class="library-dashboard-graph-title"><?php echo esc_html( $chart['title'] ); ?></figcaption>

	<div class="library-dashboard-graph-area" aria-hidden="true">
		<div class="library-dashboard-graph-yaxis">
			<?php foreach ( $ticks as $tick ) : ?>
				<span><?php echo esc_html( number_format_i18n( $tick ) ); ?></span>
			<?php endforeach; ?>
		</div>
		<div class="library-dashboard-graph">
			<?php foreach ( $chart['items'] as $item ) : ?>
				<?php
				$height    = $item['value'] ? max( 3, (int) round( $item['value'] / $max * $bar_area ) ) : 0;
				$bar_title = sprintf(
					/* translators: 1: year, month or rating, 2: number of books. */
					_n( '%1$s: %2$s book', '%1$s: %2$s books', $item['value'], 'library' ),
					$item['name'],
					number_format_i18n( $item['value'] )
				);
				?>
				<div class="library-graph-bar<?php echo $item === $peak ? ' library-graph-bar--peak' : ''; ?>" title="<?php echo esc_attr( $bar_title ); ?>">
					<div class="library-graph-bar-fill" style="height: <?php echo (int) $height; ?>px;"></div>
					<span class="library-graph-bar-label"><?php echo esc_html( $item['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<table class="screen-reader-text">
		<thead>
			<tr>
				<th scope="col"><?php echo esc_html( $chart['column'] ); ?></th>
				<th scope="col"><?php esc_html_e( 'Books', 'library' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $chart['items'] as $item ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $item['name'] ); ?></th>
					<td><?php echo esc_html( number_format_i18n( $item['value'] ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p class="library-dashboard-graph-peak">
		<?php
		printf(
			/* translators: 1: number of books, 2: year, month or rating. */
			esc_html__( 'Most books: %1$s (%2$s)', 'library' ),
			esc_html( number_format_i18n( $max ) ),
			esc_html( $peak['name'] )
		);
		?>
	</p>
</figure>
