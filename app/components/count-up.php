<?php
/**
 * A figure that counts up to its value as it comes on the screen, once, slowing down to it, while assistive technologies
 * read its value at once; without motion, its value at once. Its root is its custom element, count-up, whose `value` its
 * place sets; `attributes`, those of its root, such as the field it is.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.2
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{attributes?: array<string, string|bool|null>} $args
 */

use Library\Markup;

$args = wp_parse_args( $args, array( 'attributes' => array() ) );

Markup::render_tag( '<count-up>', $args['attributes'] );
?>
	<span class="sr-only" data-part="said"></span>
	<span aria-hidden="true" data-part="shown"></span>
</count-up>
