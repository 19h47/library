<?php
/**
 * Achievements dashboard widget and page.
 *
 * @link       https://github.com/19h47/library
 * @since      6.1.1
 *
 * @package    Library
 * @subpackage Library/admin
 */

/**
 * Shows the achievements on the dashboard, the latest ones and the closest, and all of them on a page under Books,
 * by family, each one with its icon, its date and the link of its subject in the admin.
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Achievements_Page {

	/**
	 * Number of achievements in the latest ones and in the close ones of the widget.
	 *
	 * @var int
	 */
	private const LIST_LENGTH = 5;

	/**
	 * Slug of the achievements page, under the books menu.
	 *
	 * @var string
	 */
	private const PAGE = 'library-achievements';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Register the dashboard widget.
	 */
	public function register_widget(): void {
		wp_add_dashboard_widget( 'library_dashboard_achievements', __( 'Achievements', 'library' ), array( $this, 'render_widget' ) );
	}

	/**
	 * Add the achievements page under the books menu, with the dashboard styles.
	 */
	public function add_page(): void {
		$hook = add_submenu_page( 'edit.php?post_type=book', __( 'Achievements', 'library' ), __( 'Achievements', 'library' ), 'edit_posts', self::PAGE, array( $this, 'render_page' ) );

		add_action( "admin_print_styles-$hook", array( Library_Dashboard::class, 'enqueue_style' ) );
	}

	/**
	 * Render the widget: the latest achievements as badges, the closest with their progress, and a link to the page.
	 */
	public function render_widget(): void {
		$achievements = ( new Library_Achievements() )->get_achievements();
		$earned       = $achievements['earned'];
		$latest       = array_slice( $earned, 0, self::LIST_LENGTH );
		$close        = array_slice( $achievements['close'], 0, self::LIST_LENGTH );
		$page_url     = menu_page_url( self::PAGE, false );

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-achievements.php';
	}

	/**
	 * Render the page: all the achievements close to be earned, then those earned by family.
	 */
	public function render_page(): void {
		$achievements     = ( new Library_Achievements() )->get_achievements();
		$earned           = $achievements['earned'];
		$close            = $achievements['close'];
		$families         = self::by_family( $earned );
		$easter_egg_kinds = count( Library_Achievements::KINDS['easter-eggs'] );

		include plugin_dir_path( __FILE__ ) . 'partials/library-achievements.php';
	}

	/**
	 * Achievements earned by family, in the order of the page; families without any are left out.
	 *
	 * @param array<int,array> $earned Achievements earned.
	 * @return array<string,array{name:string,achievements:array<int,array>}>
	 */
	private static function by_family( array $earned ): array {
		$names    = array(
			'completions' => __( 'Completions', 'library' ),
			'milestones'  => __( 'Milestones', 'library' ),
			'discoveries' => __( 'Discoveries', 'library' ),
			'languages'   => __( 'Languages and translators', 'library' ),
			'easter-eggs' => __( 'Easter eggs', 'library' ),
		);
		$families = array();

		foreach ( $names as $family => $name ) {
			$achievements = array_values( array_filter( $earned, fn( array $achievement ) => isset( Library_Achievements::KINDS[ $family ][ $achievement['kind'] ] ) ) );

			if ( $achievements ) {
				$families[ $family ] = array(
					'name'         => $name,
					'achievements' => $achievements,
				);
			}
		}

		return $families;
	}

	/**
	 * Dashicon class of a kind of achievement.
	 *
	 * @param string $kind Kind.
	 * @return string
	 */
	public static function icon( string $kind ): string {
		$icons = array_merge( ...array_values( Library_Achievements::KINDS ) );

		return 'dashicons-' . $icons[ $kind ];
	}

	/**
	 * Date an achievement was earned, in the site's format, or empty.
	 *
	 * @param array $achievement Achievement.
	 * @return string
	 */
	public static function date( array $achievement ): string {
		return '' !== $achievement['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $achievement['date'] ) ) : '';
	}

	/**
	 * Link of the subject of an achievement in the admin: the edit screen of its book, or its books in the books list,
	 * in the "Not kept" view when none of them is kept, since the "All" view leaves them out. Empty without target.
	 *
	 * @param array $achievement Achievement.
	 * @return string
	 */
	public static function url( array $achievement ): string {
		$target = $achievement['target'];

		if ( ! $target ) {
			return '';
		}

		if ( 'book' === $target['type'] ) {
			return (string) get_edit_post_link( $target['id'], 'raw' );
		}

		$url = admin_url( 'edit.php?post_type=book' );

		if ( $target['not_kept'] ) {
			$url = add_query_arg( 'post_status', Library_Posts::NOT_KEPT, $url );
		}

		if ( 'search' === $target['type'] ) {
			return add_query_arg( 's', rawurlencode( $target['search'] ), $url );
		}

		$taxonomy = 'author' === $target['type'] ? 'library-author' : 'library-publisher';
		$term     = get_term( $target['id'], $taxonomy );

		return $term instanceof WP_Term ? add_query_arg( $taxonomy, $term->slug, $url ) : '';
	}
}
