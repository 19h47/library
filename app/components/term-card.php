<?php
/**
 * The card of a term, an .app-item, such as an author of a series or a series of a person: its name, a link to its page,
 * its description, then the line that sums up its books. renderAuthor() and renderSeries() fill it; the place that holds
 * it, such as the item of a list, sets where it goes.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{level?: int} $args
 */

use Library\Markup;

$args    = wp_parse_args( $args, array( 'level' => 4 ) );
$heading = Markup::get_heading( $args['level'] );
?>
<article class="app-item library-term-card" data-part="card">
	<<?php echo esc_html( $heading ); ?> class="library-term-card__name" data-part="name"></<?php echo esc_html( $heading ); ?>>
	<div class="library-term-card__description" data-html="description" hidden></div>
	<p class="app-item__meta" data-field="line"></p>
</article>
