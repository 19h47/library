<?php
/**
 * Reading idea dashboard widget.
 *
 * A book without cover shows an empty frame linking to its edit screen, to add one.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{id:int,reason:string,position:int,total:int,week:string}|null $idea
 * @var WP_Post|null                                                        $book
 * @var string                                                              $next_url
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="library-dashboard-widget">
	<?php if ( ! $idea || ! $book ) : ?>
		<p class="library-dashboard-secondary"><?php esc_html_e( 'Every book has been read.', 'library' ); ?></p>
	<?php else : ?>
		<?php
		$edit_url   = (string) get_edit_post_link( $book->ID );
		$page_count = (int) get_post_meta( $book->ID, 'pages', true );
		/* translators: %s: number of pages. */
		$length  = $page_count ? sprintf( _n( '%s page', '%s pages', $page_count, 'library' ), number_format_i18n( $page_count ) ) : '';
		$details = array_filter( array( Library_Books::credits( $book->ID ), $length ) );
		?>
		<div class="library-idea">
			<?php if ( has_post_thumbnail( $book ) ) : ?>
				<a class="library-idea-cover" href="<?php echo esc_url( $edit_url ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo get_the_post_thumbnail( $book, 'medium', array( 'alt' => '' ) ); ?>
				</a>
			<?php else : ?>
				<a class="library-idea-cover library-idea-cover--missing" href="<?php echo esc_url( $edit_url ); ?>">
					<?php esc_html_e( 'Add a cover', 'library' ); ?>
				</a>
			<?php endif; ?>

			<div class="library-idea-text">
				<p class="library-dashboard-primary">
					<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( get_the_title( $book ) ); ?></a>
				</p>
				<?php if ( $details ) : ?>
					<p class="library-dashboard-secondary"><?php echo esc_html( implode( ' · ', $details ) ); ?></p>
				<?php endif; ?>
				<p class="library-idea-reason"><?php echo esc_html( $idea['reason'] ); ?></p>
				<p class="library-idea-actions">
					<a class="button" href="<?php echo esc_url( $next_url ); ?>"><?php esc_html_e( 'Another idea', 'library' ); ?></a>
					<span class="library-dashboard-secondary">
						<?php
						printf(
							/* translators: 1: position of the idea, 2: number of ideas. */
							esc_html__( '%1$s of %2$s', 'library' ),
							esc_html( number_format_i18n( $idea['position'] + 1 ) ),
							esc_html( number_format_i18n( $idea['total'] ) )
						);
						?>
					</span>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>
