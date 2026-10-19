<?php
/**
 * Number of achievements earned, and of easter eggs found among them.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array<int,array> $earned Achievements earned.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$easter_eggs = count( array_filter( array_column( $earned, 'easter_egg' ) ) );
?>

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
