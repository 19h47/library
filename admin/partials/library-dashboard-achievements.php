<?php
/**
 * Achievements dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{earned:array,close:array}                      $achievements
 * @var array<int,array{name:string,url:string,text:string}> $latest
 * @var array<int,array{name:string,url:string,text:string}> $close
 * @var array<int,array{name:string,url:string,text:string}> $all
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
				/* translators: %s: number of achievements. */
				esc_html( _n( '%s achievement', '%s achievements', count( $achievements['earned'] ), 'library' ) ),
				esc_html( number_format_i18n( count( $achievements['earned'] ) ) )
			);
			?>
		</p>
		<?php if ( $achievements['close'] ) : ?>
			<p class="library-dashboard-secondary">
				<?php
				printf(
					/* translators: %s: number of achievements close to be earned. */
					esc_html( _n( '%s close to it', '%s close to it', count( $achievements['close'] ), 'library' ) ),
					esc_html( number_format_i18n( count( $achievements['close'] ) ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php
	$rankings = array(
		array(
			'title' => __( 'Latest achievements', 'library' ),
			'items' => $latest,
		),
		array(
			'title' => __( 'Close to it', 'library' ),
			'items' => $close,
		),
	);

	foreach ( array_filter( $rankings, fn( array $ranking ) => (bool) $ranking['items'] ) as $ranking ) {
		include __DIR__ . '/library-dashboard-ranking.php';
	}
	?>

	<?php if ( count( $all ) > count( $latest ) ) : ?>
		<details class="library-achievements-all">
			<summary>
				<?php
				printf(
					/* translators: %s: number of achievements. */
					esc_html__( 'All achievements (%s)', 'library' ),
					esc_html( number_format_i18n( count( $all ) ) )
				);
				?>
			</summary>
			<?php
			$ranking = array(
				'title' => '',
				'items' => $all,
			);

			include __DIR__ . '/library-dashboard-ranking.php';
			?>
		</details>
	<?php endif; ?>
</div>
