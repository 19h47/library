<?php
/**
 * Reading pile dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var int                                                  $to_read
 * @var WP_Post|null                                         $oldest
 * @var array<int,array{name:string,url:string,text:string}> $recent_reads
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
				/* translators: %s: number of books to read. */
				esc_html( _n( '%s book to read', '%s books to read', $to_read, 'library' ) ),
				esc_html( number_format_i18n( $to_read ) )
			);
			?>
		</p>
		<?php if ( $oldest ) : ?>
			<p class="library-dashboard-secondary">
				<?php
				printf(
					/* translators: 1: book title, linked, 2: edition year. */
					esc_html__( 'Oldest: %1$s (%2$s)', 'library' ),
					'<a href="' . esc_url( (string) get_edit_post_link( $oldest->ID ) ) . '">' . esc_html( get_the_title( $oldest ) ) . '</a>',
					esc_html( substr( (string) get_post_meta( $oldest->ID, 'edition_date', true ), 0, 4 ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php
	if ( $recent_reads ) {
		$ranking = array(
			'title' => __( 'Latest reads', 'library' ),
			'items' => $recent_reads,
		);

		include __DIR__ . '/library-dashboard-ranking.php';
	}
	?>
</div>
