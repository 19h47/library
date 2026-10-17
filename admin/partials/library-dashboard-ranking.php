<?php
/**
 * Dashboard ranking: a titled, ordered list of linked entries.
 *
 * @package Library
 * @subpackage Library/admin/partials
 *
 * @var array{title:string,items:array<int,array{name:string,url:string,text:string}>} $ranking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<section class="library-dashboard-ranking">
	<h3 class="library-dashboard-graph-title"><?php echo esc_html( $ranking['title'] ); ?></h3>
	<ol>
		<?php foreach ( $ranking['items'] as $entry ) : ?>
			<li>
				<a href="<?php echo esc_url( $entry['url'] ); ?>"><?php echo esc_html( $entry['name'] ); ?></a>
				<span class="library-dashboard-ranking-text"><?php echo esc_html( $entry['text'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
