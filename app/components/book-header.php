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
<header class="library-book-header">
	<?php
	Markup::render_component(
		'book-cover',
		array(
			'large'      => true,
			'attributes' => array( 'aria-hidden' => 'true' ),
		)
	);
	?>
	<div class="library-book-header__heading">
		<<?php echo esc_html( $heading ); ?> class="library-heading library-book-header__title" data-part="title" tabindex="-1">
			<span data-field="main_title"></span>
			<span class="library-book-header__subtitle" data-field="subtitle"></span>
		</<?php echo esc_html( $heading ); ?>>
		<p class="app-text-s app-text-muted app-font-ui app-uppercase library-book-header__credits" data-field="credits"></p>
		<p class="library-book-header__translation" data-field="translation"></p>
	</div>
	<p class="library-book-header__excerpt" data-field="excerpt"></p>
</header>
