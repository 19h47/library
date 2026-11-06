<?php
/**
 * Terms lists of the admin: the books not kept and to buy of a term, the terms without books, and the messages of a term saved.
 *
 * @link       https://github.com/19h47/library
 * @since      6.1.1
 *
 * @package    Library
 * @subpackage Library/admin
 */

namespace Library\Admin;

use Library\Posts;
use Library\Status;
use Library\Taxonomies;
use WP_Term;

/**
 * Adds "Not kept" and "To buy" columns and a "Without books" view to the lists of people, publishers, series, original
 * languages and forms, and names their messages.
 *
 * @since      6.1.1
 * @package    Library
 * @subpackage Library/admin
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Terms_List {

	/**
	 * The query var of the view of the terms without books.
	 *
	 * @var string
	 */
	public const string WITHOUT_BOOKS = 'library_without_books';

	/**
	 * Statuses counted in a column of their own, by column: the count of core only counts the books in the library.
	 *
	 * @var array<string,string>
	 */
	private const array STATUS_COLUMNS = array(
		'library_not_kept' => Status::Not_Kept->value,
		'library_to_buy'   => Status::To_Buy->value,
	);

	/**
	 * Books of each status by term ID, by taxonomy; a term without books has none.
	 *
	 * @var array<string,array<int,array<string,int>>>
	 */
	private array $counts = array();

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_filter( 'term_updated_messages', array( $this, 'add_term_messages' ) );

		add_action( 'load-edit-tags.php', array( $this, 'show_without_books' ) );

		foreach ( Taxonomies::TAXONOMIES as $taxonomy ) {
			add_filter( "manage_edit-{$taxonomy}_columns", array( $this, 'add_status_columns' ) );
			add_filter( "manage_{$taxonomy}_custom_column", array( $this, 'render_status_column' ), 10, 3 );
			add_filter( "views_edit-{$taxonomy}", array( $this, 'add_views' ) );
		}
	}

	/**
	 * Add the messages shown when a person, a publisher, a series, a language or a form is added, updated or deleted.
	 *
	 * @param array $messages Array of arrays of messages to be displayed, keyed by taxonomy name.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/term_updated_messages/
	 *
	 * @return array
	 */
	public function add_term_messages( array $messages ): array {
		$messages['library_person'] = array(
			0 => '',
			1 => __( 'Person added.', 'library' ),
			2 => __( 'Person deleted.', 'library' ),
			3 => __( 'Person updated.', 'library' ),
			4 => __( 'Person not added.', 'library' ),
			5 => __( 'Person not updated.', 'library' ),
			6 => __( 'People deleted.', 'library' ),
		);

		$messages['library_publisher'] = array(
			0 => '',
			1 => __( 'Publisher added.', 'library' ),
			2 => __( 'Publisher deleted.', 'library' ),
			3 => __( 'Publisher updated.', 'library' ),
			4 => __( 'Publisher not added.', 'library' ),
			5 => __( 'Publisher not updated.', 'library' ),
			6 => __( 'Publishers deleted.', 'library' ),
		);

		$messages['library_series'] = array(
			0 => '',
			1 => __( 'Series added.', 'library' ),
			2 => __( 'Series deleted.', 'library' ),
			3 => __( 'Series updated.', 'library' ),
			4 => __( 'Series not added.', 'library' ),
			5 => __( 'Series not updated.', 'library' ),
			6 => __( 'Series deleted.', 'library' ),
		);

		$messages['library_language'] = array(
			0 => '',
			1 => __( 'Language added.', 'library' ),
			2 => __( 'Language deleted.', 'library' ),
			3 => __( 'Language updated.', 'library' ),
			4 => __( 'Language not added.', 'library' ),
			5 => __( 'Language not updated.', 'library' ),
			6 => __( 'Languages deleted.', 'library' ),
		);

		$messages['library_form'] = array(
			0 => '',
			1 => __( 'Form added.', 'library' ),
			2 => __( 'Form deleted.', 'library' ),
			3 => __( 'Form updated.', 'library' ),
			4 => __( 'Form not added.', 'library' ),
			5 => __( 'Form not updated.', 'library' ),
			6 => __( 'Forms deleted.', 'library' ),
		);

		return $messages;
	}

	/**
	 * Add a column per status after the count, which only counts the books in the library, named after the status.
	 *
	 * @param string[] $columns Columns.
	 * @return string[]
	 */
	public function add_status_columns( array $columns ): array {
		foreach ( self::STATUS_COLUMNS as $column => $status ) {
			$columns[ $column ] = get_post_status_object( $status )->label;
		}

		return $columns;
	}

	/**
	 * Number of books of a term in the status of the column, linked to their view.
	 *
	 * @param string $content     Column content.
	 * @param string $column_name Column name.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public function render_status_column( string $content, string $column_name, int $term_id ): string {
		$status = self::STATUS_COLUMNS[ $column_name ] ?? null;
		$term   = get_term( $term_id );

		if ( ! $status || ! $term instanceof WP_Term ) {
			return $content;
		}

		$books = $this->get_counts( $term->taxonomy )[ $term_id ][ $status ] ?? 0;

		if ( ! $books ) {
			return number_format_i18n( $books );
		}

		$url = add_query_arg(
			array(
				'post_type'     => 'library_book',
				'post_status'   => $status,
				$term->taxonomy => $term->slug,
			),
			admin_url( 'edit.php' )
		);

		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( number_format_i18n( $books ) ) );
	}

	/**
	 * Views of the list: all the terms, and those without books, in any status, theirs or of the terms under them. The
	 * terms without books are in alphabetical order, in a flat list: a term of a tree may be without books under a term
	 * with some.
	 *
	 * @param string[] $views Views, empty in a terms list.
	 * @return string[]
	 */
	public function add_views( array $views ): array {
		$taxonomy      = get_current_screen()->taxonomy;
		$url           = add_query_arg(
			array(
				'taxonomy'  => $taxonomy,
				'post_type' => 'library_book',
			),
			admin_url( 'edit-tags.php' )
		);
		$without_url   = add_query_arg(
			array(
				self::WITHOUT_BOOKS => 1,
				'orderby'           => 'name',
				'order'             => 'asc',
			),
			$url
		);
		$terms         = (int) wp_count_terms( array( 'taxonomy' => $taxonomy ) );
		$without_books = $terms - count( $this->get_counts( $taxonomy ) );
		/* translators: %s: number of terms. */
		$without_label = _n_noop( 'Without books <span class="count">(%s)</span>', 'Without books <span class="count">(%s)</span>', 'library' );
		$all_label     = sprintf( '%s <span class="count">(%s)</span>', esc_html( get_taxonomy( $taxonomy )->labels->all_items ), number_format_i18n( $terms ) );

		$views['all']                 = $this->get_view_link( $url, $all_label, ! self::is_without_books() );
		$views[ self::WITHOUT_BOOKS ] = $this->get_view_link( $without_url, sprintf( translate_nooped_plural( $without_label, $without_books, 'library' ), number_format_i18n( $without_books ) ), self::is_without_books() );

		return $views;
	}

	/**
	 * In the view of the terms without books, leave out of the list the terms with some.
	 *
	 * @return void
	 */
	public function show_without_books(): void {
		if ( ! in_array( get_current_screen()->taxonomy, Taxonomies::TAXONOMIES, true ) || ! self::is_without_books() ) {
			return;
		}

		add_filter( 'get_terms_args', array( $this, 'exclude_terms_with_books' ), 10, 2 );

		// The list queries its terms and counts them before the header of the page; the form beside it then offers them all as parents.
		add_action(
			'admin_enqueue_scripts',
			function (): void {
				remove_filter( 'get_terms_args', array( $this, 'exclude_terms_with_books' ) );
			}
		);
	}

	/**
	 * Leave the terms with books out of a query of the terms of one taxonomy.
	 *
	 * @param array    $args       Arguments of the query.
	 * @param string[] $taxonomies Taxonomies of the query.
	 * @return array
	 */
	public function exclude_terms_with_books( array $args, array $taxonomies ): array {
		if ( 1 !== count( $taxonomies ) || ! in_array( $taxonomies[0], Taxonomies::TAXONOMIES, true ) ) {
			return $args;
		}

		$args['exclude'] = array( ...wp_parse_id_list( $args['exclude'] ), ...array_keys( $this->get_counts( $taxonomies[0] ) ) );

		return $args;
	}

	/**
	 * Whether the list shows the terms without books.
	 *
	 * @return bool
	 */
	private static function is_without_books(): bool {
		return ! empty( $_GET[ self::WITHOUT_BOOKS ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- A view, which changes nothing.
	}

	/**
	 * Link of a view of the list, current or not.
	 *
	 * @param string $url     URL of the view.
	 * @param string $label   Label of the view, HTML.
	 * @param bool   $current Whether the list shows it.
	 * @return string
	 */
	private function get_view_link( string $url, string $label, bool $current ): string {
		$attributes = $current ? ' class="current" aria-current="page"' : '';

		return sprintf( '<a href="%s"%s>%s</a>', esc_url( $url ), $attributes, $label );
	}

	/**
	 * Count the books of every term of a taxonomy by status in one query, those in the trash left out; a term also counts
	 * the books of the terms under it: the name of a person those of their other names, a publisher those of its
	 * collections and their sub-collections.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return array<int,array<string,int>>
	 */
	private function get_counts( string $taxonomy ): array {
		if ( isset( $this->counts[ $taxonomy ] ) ) {
			return $this->counts[ $taxonomy ];
		}

		global $wpdb;

		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin terms list only.
			$wpdb->prepare(
				"SELECT DISTINCT tt.term_id, p.ID AS book_id, p.post_status
				FROM {$wpdb->term_relationships} AS tr
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = %s
				INNER JOIN {$wpdb->posts} AS p ON p.ID = tr.object_id AND p.post_type = 'library_book' AND p.post_status NOT IN ( 'trash', 'auto-draft' )",
				$taxonomy
			)
		);
		$books = array();

		foreach ( $rows as $row ) {
			foreach ( array( (int) $row->term_id, ...get_ancestors( (int) $row->term_id, $taxonomy, 'taxonomy' ) ) as $term_id ) {
				$books[ $term_id ][ $row->post_status ][ $row->book_id ] = true;
			}
		}

		$this->counts[ $taxonomy ] = array_map( fn( array $statuses ) => array_map( 'count', $statuses ), $books );

		return $this->counts[ $taxonomy ];
	}
}
