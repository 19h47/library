<?php
/**
 * Achievements page: those close to be earned, then those earned by family.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array<int,array>                                                  $earned           Achievements earned, latest first.
 * @var array<int,array>                                                  $close            Achievements close to be earned, closest first.
 * @var array<string,array{name:string,achievements:array<int,array>}> $families         Achievements earned by family.
 * @var int                                                               $easter_egg_kinds Number of easter eggs to find.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap library-achievements library-achievements-page">
	<h1><?php esc_html_e( 'Achievements', 'library' ); ?></h1>

	<?php require __DIR__ . '/library-achievements-summary.php'; ?>

	<?php if ( $close ) : ?>
		<section class="library-achievements-family">
			<h2>
				<?php esc_html_e( 'Close to it', 'library' ); ?>
				<span class="count">(<?php echo esc_html( number_format_i18n( count( $close ) ) ); ?>)</span>
			</h2>
			<ul class="library-achievements-close">
				<?php
				foreach ( $close as $index => $achievement ) {
					include __DIR__ . '/library-achievement-close.php';
				}
				?>
			</ul>
		</section>
	<?php endif; ?>

	<?php foreach ( $families as $family => $group ) : ?>
		<?php
		$count = number_format_i18n( count( $group['achievements'] ) );

		if ( 'easter-eggs' === $family ) {
			/* translators: 1: number done, 2: total, e.g. "13 of 14". */
			$count = sprintf( __( '%1$s of %2$s', 'library' ), $count, number_format_i18n( $easter_egg_kinds ) );
		}
		?>
		<section class="library-achievements-family">
			<h2>
				<?php echo esc_html( $group['name'] ); ?>
				<span class="count">(<?php echo esc_html( $count ); ?>)</span>
			</h2>
			<ul class="library-achievements-badges">
				<?php
				foreach ( $group['achievements'] as $achievement ) {
					include __DIR__ . '/library-achievement.php';
				}
				?>
			</ul>
		</section>
	<?php endforeach; ?>
</div>
