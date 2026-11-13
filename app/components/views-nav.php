<?php
/**
 * The views of the app, a strip of pills, the one shown current: the links of its header. library-nav names them in the
 * title of the document.
 *
 * @link       https://github.com/19h47/library
 * @since      9.41.0
 *
 * @package    Library
 * @subpackage Library/app/components
 *
 * @var array{views: array<string,array{label:string,url:string}>, current?: string} $args
 */

$args = wp_parse_args( $args, array( 'current' => '' ) );
?>
<nav>
	<ul class="library-strip library-views-nav" data-list="views">
		<?php foreach ( $args['views'] as $name => $item ) : ?>
			<li>
				<a
					class="app-pill"
					href="<?php echo esc_url( $item['url'] ); ?>"
					<?php echo $name === $args['current'] ? 'aria-current="page"' : ''; ?>
				><?php echo esc_html( $item['label'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
