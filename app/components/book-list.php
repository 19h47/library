<?php
/**
 * The books of a list as cards, or as a grid of their covers, loading the next ones as its end comes, with what it
 * says while it loads or finds none: the books found, or the list its name gives, such as toBuy.
 *
 * @link       https://github.com/19h47/library
 * @since      9.26.3
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{list?: string|null} $args
 */

use Library\Markup;

$args       = wp_parse_args( $args, array( 'list' => null ) );
$attributes = array( 'list' => $args['list'] );
$state      = Markup::set_attributes( '<library-status class="app-status" hidden></library-status>', $attributes );
$books      = Markup::set_attributes( '<library-list>', $attributes );
?>
<?php echo $state; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
<?php echo $books; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built by WP_HTML_Tag_Processor, which escapes the attributes. ?>
	<ul class="app-list__items"></ul>
	<div class="library-list__more"></div>
</library-list>
