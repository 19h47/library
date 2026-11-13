<?php
/**
 * The search of the books, a search landmark: its field, then its button, which the keyboard of a phone does not always
 * show, Enter or the button submitting it; then the keywords that narrow it, as the help of the books list explains
 * them, a name between quotes, which keep its spaces, hidden until the field is focused. library-search follows the
 * typing; submitted, it searches at once, or opens the styleguide or the reference of the API typed.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{id: string} $args
 */

use Library\Markup;
use Library\Meta;

$keywords = array(
	'series:""'              => get_taxonomy( 'library_series' )->labels->singular_name,
	'author:""'              => Meta::get_role_choices()['author'],
	'publisher:""'           => get_taxonomy( 'library_publisher' )->labels->singular_name,
	/* translators: %s: current year. */
	'read:' . wp_date( 'Y' ) => sprintf( __( 'Read in %s', 'library' ), wp_date( 'Y' ) ),
	'rating:9'               => __( 'Rated 9 or more', 'library' ),
);
?>
<form class="library-search" role="search">
	<label class="sr-only" for="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Search the library', 'library' ); ?></label>
	<p class="library-search__row">
		<input
			id="<?php echo esc_attr( $args['id'] ); ?>"
			type="search"
			placeholder="<?php esc_attr_e( 'Title, author, series, ISBN…', 'library' ); ?>"
			autocomplete="off"
			enterkeyhint="search"
		>
		<?php // A control, as those of the toolbar. ?>
		<button
			class="app-control"
			type="submit"
			aria-label="<?php echo esc_attr_x( 'Search', 'verb', 'library' ); ?>"
			title="<?php echo esc_attr_x( 'Search', 'verb', 'library' ); ?>"
		>
			<?php Markup::render_icon( 'search' ); ?>
		</button>
	</p>
	<p class="library-strip library-search__keywords" data-list="keywords" hidden>
		<span class="sr-only"><?php esc_html_e( 'Narrow the search to:', 'library' ); ?></span>
		<?php foreach ( $keywords as $keyword => $label ) : ?>
			<button class="app-pill" type="button" data-keyword="<?php echo esc_attr( $keyword ); ?>"><?php echo esc_html( $label ); ?></button>
		<?php endforeach; ?>
	</p>
</form>
