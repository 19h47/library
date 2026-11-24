<?php
/**
 * Fields folded under a summary, such as the ones few books have in the form of a book: its summary says its label and
 * how many of its fields are filled in, as it opens them. Its root is its custom element, fields-disclosure, which counts
 * them as they change; its place gives it its fields, `fields`, which prints them, and a class of its own, `class`.
 *
 * @link       https://github.com/19h47/library
 * @since      10.6.1
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{label: string, fields: callable(): void, class?: string} $args
 */

$args = wp_parse_args( $args, array( 'class' => '' ) );
?>
<fields-disclosure>
	<details class="<?php echo esc_attr( trim( "library-disclosure {$args['class']}" ) ); ?>">
		<summary class="app-text-s app-text-muted app-font-ui app-uppercase"><span data-field="label"><?php echo esc_html( $args['label'] ); ?></span></summary>
		<?php ( $args['fields'] )(); ?>
	</details>
</fields-disclosure>
