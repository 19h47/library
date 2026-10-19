<?php
/**
 * An achievement: its icon, title, subject, detail and date.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{kind:string,title:string,subject:string,detail:string,url:string,date:string,easter_egg:bool} $achievement
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$meta = array_filter( array( $achievement['detail'], Library_Achievements::date( $achievement ) ) );
?>

<li class="library-achievement<?php echo $achievement['easter_egg'] ? ' library-achievement--easter-egg' : ''; ?>">
	<span class="library-achievement-icon dashicons <?php echo esc_attr( Library_Achievements::icon( $achievement['kind'] ) ); ?>" aria-hidden="true"></span>
	<span class="library-achievement-text">
		<span class="library-achievement-title"><?php echo esc_html( $achievement['title'] ); ?></span>
		<?php if ( '' !== $achievement['subject'] ) : ?>
			<span class="library-achievement-subject">
				<?php if ( '' !== $achievement['url'] ) : ?>
					<a href="<?php echo esc_url( $achievement['url'] ); ?>"><?php echo esc_html( $achievement['subject'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $achievement['subject'] ); ?>
				<?php endif; ?>
			</span>
		<?php endif; ?>
		<?php if ( $meta ) : ?>
			<span class="library-achievement-meta"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
		<?php endif; ?>
	</span>
</li>
