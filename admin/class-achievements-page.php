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

namespace Library\Admin;

use Library\Achievement_Family;
use Library\Achievement_Kind;
use Library\Achievements;
use Library\Posts;
use Library\Status;
use WP_Term;

/**
 * Shows the achievements on the dashboard, the latest ones and the closest, and all of them on a page under Books,
 * by family, each one with its icon, its date and the link of its subject in the admin.
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 *
 * @phpstan-import-type Achievement from Achievements
 * @phpstan-import-type Close_Achievement from Achievements
 */
class Achievements_Page {

	/**
	 * Number of achievements in the latest ones and in the close ones of the widget.
	 *
	 * @var int
	 */
	private const int LIST_LENGTH = 5;

	/**
	 * Slug of the achievements page, under the books menu.
	 *
	 * @var string
	 */
	private const string PAGE = 'library-achievements';

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
		$hook = add_submenu_page( 'edit.php?post_type=library_book', __( 'Achievements', 'library' ), __( 'Achievements', 'library' ), 'edit_posts', self::PAGE, array( $this, 'render_page' ) );

		add_action( "admin_print_styles-$hook", array( Dashboard::class, 'enqueue_style' ) );
	}

	/**
	 * Render the widget: the latest achievements as badges, the closest with their progress, and a link to the page.
	 */
	public function render_widget(): void {
		$achievements = new Achievements()->get_achievements();
		$earned       = $achievements['earned'];
		$latest       = array_slice( $earned, 0, self::LIST_LENGTH );
		$close        = array_slice( $achievements['close'], 0, self::LIST_LENGTH );
		$page_url     = menu_page_url( self::PAGE, false );

		self::prime_targets( array( ...$latest, ...$close ) );

		include plugin_dir_path( __FILE__ ) . 'partials/library-dashboard-achievements.php';
	}

	/**
	 * Render the page: all the achievements close to be earned, then those earned by family.
	 */
	public function render_page(): void {
		$achievements     = new Achievements()->get_achievements();
		$earned           = $achievements['earned'];
		$close            = $achievements['close'];
		$families         = self::group_by_family( $earned );
		$easter_egg_kinds = count( array_filter( Achievement_Kind::cases(), fn( Achievement_Kind $kind ) => Achievement_Family::Easter_Eggs === $kind->family() ) );

		self::prime_targets( array( ...$earned, ...$close ) );

		include plugin_dir_path( __FILE__ ) . 'partials/library-achievements.php';
	}

	/**
	 * Achievements earned by family, in the order of the page; families without any are left out.
	 *
	 * @param array $earned Achievements earned.
	 * @return array<string,array{name:string,achievements:list<Achievement>}>
	 *
	 * @phpstan-param list<Achievement> $earned
	 */
	private static function group_by_family( array $earned ): array {
		$families = array();

		foreach ( Achievement_Family::cases() as $family ) {
			$achievements = array_values( array_filter( $earned, fn( array $achievement ) => Achievement_Kind::from( $achievement['kind'] )->family() === $family ) );

			if ( $achievements ) {
				$families[ $family->value ] = array(
					'name'         => $family->label(),
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
	public static function get_icon( string $kind ): string {
		return 'dashicons-' . Achievement_Kind::from( $kind )->icon();
	}

	/**
	 * Date an achievement was earned, in the site's format, or empty.
	 *
	 * @param Achievement|Close_Achievement $achievement Achievement.
	 * @return string
	 */
	public static function get_earned_date( array $achievement ): string {
		return '' !== $achievement['date'] ? date_i18n( get_option( 'date_format' ), strtotime( $achievement['date'] ) ) : '';
	}

	/**
	 * Load the books and the terms the achievements link to, all at once rather than one per link.
	 *
	 * @param list<Achievement|Close_Achievement> $achievements Achievements.
	 * @return void
	 */
	private static function prime_targets( array $achievements ): void {
		$targets = array_filter( array_column( $achievements, 'target' ) );
		$books   = wp_list_filter( $targets, array( 'type' => 'book' ) );
		$terms   = wp_list_filter( $targets, array( 'type' => 'book' ), 'NOT' );

		_prime_post_caches( array_column( $books, 'id' ), false, false );
		// A search has no ID.
		_prime_term_caches( array_column( $terms, 'id' ) );
	}

	/**
	 * Link of the subject of an achievement in the admin: the edit screen of its book, or its books in the books list,
	 * in the "Not kept" view when none of them is kept, since the "All" view leaves them out. Empty without target.
	 *
	 * @param Achievement|Close_Achievement $achievement Achievement.
	 * @return string
	 */
	public static function get_url( array $achievement ): string {
		$target = $achievement['target'];

		if ( ! $target ) {
			return '';
		}

		if ( 'book' === $target['type'] ) {
			return (string) get_edit_post_link( $target['id'], 'raw' );
		}

		$status = $target['not_kept'] ? Status::Not_Kept->value : '';

		if ( 'search' === $target['type'] ) {
			$url = admin_url( 'edit.php?post_type=library_book' );
			$url = '' !== $status ? add_query_arg( 'post_status', $status, $url ) : $url;

			return add_query_arg( 's', rawurlencode( $target['search'] ), $url );
		}

		$taxonomies = array(
			'author'     => 'library_person',
			'series'     => 'library_series',
			'collection' => 'library_publisher',
		);
		$taxonomy   = $taxonomies[ $target['type'] ];
		$term       = get_term( $target['id'], $taxonomy );

		return $term instanceof WP_Term ? Books_List::get_term_url( $term, $status ) : '';
	}
}
