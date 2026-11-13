<?php
/**
 * The fields of a book, as its page shows them, a group each for its work, for the edition of its ISBN and for the copy
 * in hand, as the three dates of a book: a group without a field known takes no room. The translators are named by its
 * translation line, the date of its reading by its line. fillBookPage() fills them.
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
use Library\Meta;

$args    = wp_parse_args( $args, array( 'level' => 3 ) );
$heading = Markup::get_heading( $args['level'] );
$groups  = array(
	_x( 'Work', 'fields of a book', 'library' )    => array(
		'authors'        => __( 'Authors', 'library' ),
		...array_map( array( Meta::class, 'get_label' ), array_diff_key( Meta::ROLE_KEYS, array( 'translator' => true ) ) ),
		'published_date' => Meta::get_label( 'library_published_date' ),
		'languages'      => get_taxonomy( 'library_language' )->labels->name_for_book,
		'form'           => get_taxonomy( 'library_form' )->labels->singular_name,
	),
	_x( 'Edition', 'fields of a book', 'library' ) => array(
		'publishers'        => get_taxonomy( 'library_publisher' )->labels->singular_name,
		'edition_date'      => Meta::get_label( 'library_edition_date' ),
		'isbn'              => Meta::get_label( 'library_isbn' ),
		'pages'             => Meta::get_label( 'library_pages' ),
		'edition_languages' => Meta::get_label( 'library_edition_languages' ),
	),
	_x( 'Copy', 'fields of a book', 'library' )    => array(
		'printed_date' => Meta::get_label( 'library_printed_date' ),
		'added_date'   => __( 'Added', 'library' ),
	),
);
?>
<div class="library-book-fields">
	<?php foreach ( $groups as $group => $fields ) : ?>
		<section class="library-book-fields__group">
			<<?php echo esc_html( $heading ); ?> class="library-title"><?php echo esc_html( $group ); ?></<?php echo esc_html( $heading ); ?>>
			<dl class="library-fields library-fields--aside">
				<?php foreach ( $fields as $name => $label ) : ?>
					<div data-row="<?php echo esc_attr( $name ); ?>">
						<dt class="app-text-s app-text-muted app-font-ui app-uppercase"><?php echo esc_html( $label ); ?></dt>
						<dd data-field="<?php echo esc_attr( $name ); ?>"></dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</section>
	<?php endforeach; ?>
</div>
