<?php
/**
 * An achievement close to be earned: its subject, or else its title, and its progress.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{kind:string,title:string,subject:string,detail:string,url:string,date:string,easter_egg:bool,done:int,total:int} $achievement
 * @var int $index Position in the list, for the ID of the name.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name_id = 'library-achievement-close-' . $index;
$name    = '' !== $achievement['subject'] ? $achievement['subject'] : $achievement['title'];
$meta    = array_filter( array( '' !== $achievement['subject'] ? $achievement['title'] : '', $achievement['detail'] ) );

/* translators: 1: number done, 2: total, e.g. "13 of 14". */
$progress_text = sprintf( __( '%1$s of %2$s', 'library' ), number_format_i18n( $achievement['done'] ), number_format_i18n( $achievement['total'] ) );
?>

<li>
	<span class="library-achievement-close-name" id="<?php echo esc_attr( $name_id ); ?>">
		<?php if ( '' !== $achievement['url'] ) : ?>
			<a href="<?php echo esc_url( $achievement['url'] ); ?>"><?php echo esc_html( $name ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $name ); ?>
		<?php endif; ?>
	</span>
	<span class="library-achievement-meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
	<span class="library-achievement-progress">
		<progress max="<?php echo esc_attr( $achievement['total'] ); ?>" value="<?php echo esc_attr( $achievement['done'] ); ?>" aria-labelledby="<?php echo esc_attr( $name_id ); ?>" aria-valuetext="<?php echo esc_attr( $progress_text ); ?>"></progress>
		<span class="library-achievement-count" aria-hidden="true"><?php echo esc_html( number_format_i18n( $achievement['done'] ) . ' / ' . number_format_i18n( $achievement['total'] ) ); ?></span>
	</span>
</li>
