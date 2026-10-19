<?php
/**
 * Achievements dashboard widget.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array<int,array> $earned   Achievements earned, latest first.
 * @var array<int,array> $latest   Latest achievements.
 * @var array<int,array> $close    Achievements closest to be earned, with the books done and their total.
 * @var string           $page_url Link to the achievements page, or empty when the user cannot see it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget library-achievements">
	<?php require __DIR__ . '/library-achievements-summary.php'; ?>

	<?php if ( $latest ) : ?>
		<section>
			<h3 class="library-dashboard-graph-title"><?php esc_html_e( 'Latest achievements', 'library' ); ?></h3>
			<ul class="library-achievements-badges">
				<?php
				foreach ( $latest as $achievement ) {
					include __DIR__ . '/library-achievement.php';
				}
				?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $close ) : ?>
		<section>
			<h3 class="library-dashboard-graph-title"><?php esc_html_e( 'Close to it', 'library' ); ?></h3>
			<ul class="library-achievements-close">
				<?php
				foreach ( $close as $index => $achievement ) {
					include __DIR__ . '/library-achievement-close.php';
				}
				?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $page_url ) : ?>
		<p class="library-achievements-more">
			<a href="<?php echo esc_url( $page_url ); ?>">
				<?php
				printf(
					/* translators: %s: number of achievements. */
					esc_html__( 'All achievements (%s)', 'library' ),
					esc_html( number_format_i18n( count( $earned ) ) )
				);
				?>
			</a>
		</p>
	<?php endif; ?>
</div>
