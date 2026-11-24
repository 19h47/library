<?php
/**
 * A cover that opens its book, such as a volume of a series or a latest reading: its reading state said after its title
 * to assistive technologies only, as the covers count them above. `current`, a book being read, as the home shows it:
 * its cover a step larger, its title and its status under it, its title in its frame without a cover.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.1
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{current?: bool} $args
 */

use Library\Markup;

$args = wp_parse_args( $args, array( 'current' => false ) );
?>
<a class="<?php echo esc_attr( $args['current'] ? 'library-cover-link library-cover-link--current' : 'library-cover-link' ); ?>" data-part="link">
	<?php Markup::render_component( 'book-cover' ); ?>
	<?php if ( $args['current'] ) : ?>
		<span class="library-cover-link__title" data-field="title"></span>
		<span class="app-text-s app-font-ui app-uppercase library-tag library-tag--status" data-field="status" hidden></span>
	<?php else : ?>
		<span class="sr-only" data-field="state" hidden></span>
	<?php endif; ?>
</a>
