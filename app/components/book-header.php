<?php
/**
 * The title page of a book, as its page shows it: its cover, its heading, its title above its subtitle, smaller, as on
 * its cover, its credits and its translation, then its excerpt. fillBookPage() fills it.
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

$args    = wp_parse_args( $args, array( 'level' => 2 ) );
$heading = Markup::get_heading( $args['level'] );
?>
<header class="library-book__header">
	<img class="library-cover library-book__cover" data-part="cover" data-field="cover" alt="" loading="lazy" hidden>
	<div class="library-book__heading">
		<<?php echo esc_html( $heading ); ?> class="library-heading library-book__title" data-part="title" tabindex="-1">
			<span data-field="main_title"></span>
			<span class="library-book__subtitle" data-field="subtitle"></span>
		</<?php echo esc_html( $heading ); ?>>
		<p class="app-text-s app-text-muted app-font-ui app-uppercase library-book__meta" data-field="credits"></p>
		<p class="library-book__translation" data-field="translation"></p>
	</div>
	<p class="library-book__excerpt" data-field="excerpt"></p>
</header>
