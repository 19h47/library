<?php
/**
 * Reading idea dashboard widget.
 *
 * @link       https://github.com/19h47/library
 * @since      6.1.1
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Shows the reading idea of the week on the dashboard, and the next ones with "Another idea".
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Reading_Idea_Widget {

	/**
	 * Query argument of the "Another idea" link: the position of the idea in the week's list.
	 *
	 * @var string
	 */
	private const QUERY_ARG = 'library_idea';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget(): void {
		wp_add_dashboard_widget( 'library_dashboard_idea', __( 'Reading idea of the week', 'library' ), array( $this, 'render' ) );
	}

	/**
	 * Render the idea at the requested position of this week's list.
	 *
	 * The "Another idea" link has no anchor to the widget: following one also scrolls the
	 * widgets wrap, whose overflow is hidden, and cuts the top of the widgets.
	 */
	public function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only position in the list.
		$position = isset( $_GET[ self::QUERY_ARG ] ) ? absint( $_GET[ self::QUERY_ARG ] ) : 0;
		$idea     = ( new Library_Reading_Idea() )->get_idea( $position );
		$book     = $idea ? get_post( $idea['id'] ) : null;
		$next_url = add_query_arg( self::QUERY_ARG, $position + 1, admin_url( 'index.php' ) );

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-idea.php';
	}
}
