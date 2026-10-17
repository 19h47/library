<?php
/**
 * Achievements dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array<int,array> $earned      Achievements earned, latest first.
 * @var array<int,array> $latest      Latest achievements.
 * @var array<int,array> $close       Achievements closest to be earned, with the books done and their total.
 * @var int              $easter_eggs Number of easter eggs found.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget library-achievements">
	<div class="library-dashboard-header library-achievements-header">
		<p class="library-dashboard-primary">
			<?php
			printf(
				/* translators: %s: number of achievements. */
				esc_html( _n( '%s achievement', '%s achievements', count( $earned ), 'library' ) ),
				esc_html( number_format_i18n( count( $earned ) ) )
			);
			?>
		</p>
		<?php if ( $easter_eggs ) : ?>
			<p class="library-achievements-easter-eggs">
				<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %s: number of easter eggs found. */
					esc_html( _n( 'including %s easter egg', 'including %s easter eggs', $easter_eggs, 'library' ) ),
					esc_html( number_format_i18n( $easter_eggs ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>

	<?php if ( $latest ) : ?>
		<section>
			<h3 class="library-dashboard-graph-title"><?php esc_html_e( 'Latest achievements', 'library' ); ?></h3>
			<ul class="library-achievements-badges">
				<?php
				foreach ( $latest as $achievement ) {
					include __DIR__ . '/library-dashboard-achievement.php';
				}
				?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $close ) : ?>
		<section>
			<h3 class="library-dashboard-graph-title"><?php esc_html_e( 'Close to it', 'library' ); ?></h3>
			<ul class="library-achievements-close">
				<?php foreach ( $close as $index => $achievement ) : ?>
					<?php $name_id = 'library-achievement-close-' . $index; ?>
					<li>
						<span class="library-achievement-close-name" id="<?php echo esc_attr( $name_id ); ?>">
							<?php if ( '' !== $achievement['url'] ) : ?>
								<a href="<?php echo esc_url( $achievement['url'] ); ?>"><?php echo esc_html( '' !== $achievement['subject'] ? $achievement['subject'] : $achievement['title'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( '' !== $achievement['subject'] ? $achievement['subject'] : $achievement['title'] ); ?>
							<?php endif; ?>
						</span>
						<span class="library-achievement-meta"><?php echo esc_html( implode( ' · ', array_filter( array( '' !== $achievement['subject'] ? $achievement['title'] : '', $achievement['detail'] ) ) ) ); ?></span>
						<span class="library-achievement-progress">
							<?php
							/* translators: 1: number done, 2: total, e.g. "13 of 14". */
							$progress_text = sprintf( __( '%1$s of %2$s', 'library' ), number_format_i18n( $achievement['done'] ), number_format_i18n( $achievement['total'] ) );
							?>
							<progress max="<?php echo esc_attr( $achievement['total'] ); ?>" value="<?php echo esc_attr( $achievement['done'] ); ?>" aria-labelledby="<?php echo esc_attr( $name_id ); ?>" aria-valuetext="<?php echo esc_attr( $progress_text ); ?>"></progress>
							<span class="library-achievement-count" aria-hidden="true"><?php echo esc_html( number_format_i18n( $achievement['done'] ) . ' / ' . number_format_i18n( $achievement['total'] ) ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( count( $earned ) > count( $latest ) ) : ?>
		<details class="library-achievements-all">
			<summary>
				<?php
				printf(
					/* translators: %s: number of achievements. */
					esc_html__( 'All achievements (%s)', 'library' ),
					esc_html( number_format_i18n( count( $earned ) ) )
				);
				?>
			</summary>
			<ul class="library-achievements-list">
				<?php
				foreach ( $earned as $achievement ) {
					include __DIR__ . '/library-dashboard-achievement.php';
				}
				?>
			</ul>
		</details>
	<?php endif; ?>
</div>
