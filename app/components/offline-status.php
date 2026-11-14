<?php
/**
 * The status of the app offline, an .app-status: the books shown are the ones loaded before, and nothing can be saved.
 * Its root is its custom element, offline-status, which shows it while the browser says it is offline.
 *
 * @link       https://github.com/19h47/library
 * @since      9.45.0
 *
 * @package    Library
 * @subpackage Library/app/components
 */

?>
<offline-status class="app-status" role="status" hidden><?php esc_html_e( 'Offline: the books shown are the ones loaded before, and nothing can be saved.', 'library' ); ?></offline-status>
