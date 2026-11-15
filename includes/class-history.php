<?php
/**
 * History of the record of a book: who changed what, as its revisions keep it.
 *
 * @link       https://github.com/19h47/library
 * @since      10.0.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

namespace Library;

use WP_Post;
use WP_Term;

/**
 * Keeps the record of a book the readers share in its revisions, each by the reader who saved it: its title and excerpt,
 * as core does, its meta, which Meta registers with their revisions, and its names, people, publishers, series,
 * languages and forms, which core does not revision: each revision keeps their term IDs, in `library_terms`, which
 * restoring it gives the book back. A book added has a revision at once, the record as it came. The comparison of two
 * revisions shows the meta and the names, as their forms do. The shelves of the readers are theirs, never revisioned.
 *
 * @since      10.0.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class History {

	/**
	 * Meta key of the term IDs of a revision, by taxonomy, as JSON.
	 *
	 * @var string
	 */
	public const string TERMS = 'library_terms';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_after_insert_post', array( $this, 'keep_first_revision' ), 10, 3 );
		add_action( '_wp_put_post_revision', array( $this, 'keep_terms' ), 10, 2 );
		add_filter( 'wp_save_post_revision_post_has_changed', array( $this, 'compare_terms' ), 10, 3 );
		add_action( 'wp_restore_post_revision', array( $this, 'restore_terms' ), 10, 2 );
		// Only where revisions are compared: core compares the fields of a revision with the book before saving one.
		add_action( 'load-revision.php', array( $this, 'show_fields' ) );
		add_action( 'wp_ajax_get-revision-diffs', array( $this, 'show_fields' ), 0 );
	}

	/**
	 * Keep the record of a book added, as it came: core saves a revision once a post is updated only.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 * @param bool    $update  Whether the post was updated.
	 * @return void
	 */
	public function keep_first_revision( int $post_id, WP_Post $post, bool $update ): void {
		if ( ! $update && 'library_book' === $post->post_type && ! wp_get_post_revisions( $post_id ) ) {
			wp_save_post_revision( $post_id );
		}
	}

	/**
	 * Term IDs of a book, by taxonomy, each list in order.
	 *
	 * @param int $book_id Book ID.
	 * @return array<string,int[]>
	 */
	private static function get_terms( int $book_id ): array {
		$terms = array();

		foreach ( Taxonomies::TAXONOMIES as $taxonomy ) {
			$term_ids = wp_get_object_terms( $book_id, $taxonomy, array( 'fields' => 'ids' ) );
			$term_ids = is_array( $term_ids ) ? array_map( 'intval', $term_ids ) : array();

			sort( $term_ids );

			$terms[ $taxonomy ] = $term_ids;
		}

		return $terms;
	}

	/**
	 * Term IDs a revision keeps, by taxonomy; none for a revision of before.
	 *
	 * @param int $revision_id Revision ID.
	 * @return array<string,int[]>
	 */
	private static function get_revision_terms( int $revision_id ): array {
		$terms = json_decode( (string) get_metadata( 'post', $revision_id, self::TERMS, true ), true );

		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * Keep the term IDs of a book on its revision, as it is saved: on the revision itself, not on its book, which the
	 * functions of the post meta would write instead.
	 *
	 * @param int $revision_id Revision ID.
	 * @param int $post_id     Post ID.
	 * @return void
	 */
	public function keep_terms( int $revision_id, int $post_id ): void {
		if ( 'library_book' === get_post_type( $post_id ) ) {
			update_metadata( 'post', $revision_id, self::TERMS, wp_slash( (string) wp_json_encode( self::get_terms( $post_id ) ) ) );
		}
	}

	/**
	 * A book whose names changed since its latest revision has changed, its title, excerpt and meta aside.
	 *
	 * @param bool    $post_has_changed Whether the post has changed.
	 * @param WP_Post $latest_revision  Latest revision.
	 * @param WP_Post $post             Post.
	 * @return bool
	 */
	public function compare_terms( bool $post_has_changed, WP_Post $latest_revision, WP_Post $post ): bool {
		if ( $post_has_changed || 'library_book' !== $post->post_type ) {
			return $post_has_changed;
		}

		return self::get_revision_terms( $latest_revision->ID ) !== self::get_terms( $post->ID );
	}

	/**
	 * Give a book restored the names of its revision, those still in the library.
	 *
	 * @param int $post_id     Post ID.
	 * @param int $revision_id Revision ID.
	 * @return void
	 */
	public function restore_terms( int $post_id, int $revision_id ): void {
		if ( 'library_book' !== get_post_type( $post_id ) ) {
			return;
		}

		foreach ( self::get_revision_terms( $revision_id ) as $taxonomy => $term_ids ) {
			if ( in_array( $taxonomy, Taxonomies::TAXONOMIES, true ) ) {
				wp_set_object_terms( $post_id, array_filter( array_map( 'intval', $term_ids ), fn( int $term_id ) => (bool) term_exists( $term_id, $taxonomy ) ), $taxonomy );
			}
		}
	}

	/**
	 * Show the meta and the names of a book in the comparison of its revisions.
	 *
	 * @return void
	 */
	public function show_fields(): void {
		add_filter( '_wp_post_revision_fields', array( $this, 'add_fields' ), 10, 2 );

		foreach ( array( ...Meta::KEYS, 'library_edition_languages', ...Meta::ROLE_KEYS, self::TERMS ) as $key ) {
			add_filter( "_wp_post_revision_field_{$key}", array( $this, 'render_field' ), 10, 3 );
		}
	}

	/**
	 * The fields of a book its revisions compare, by key, with their labels: its meta, then its names.
	 *
	 * @param array<string,string> $fields Fields, by key.
	 * @param array<string,mixed>  $post   Post, as an array.
	 * @return array<string,string>
	 */
	public function add_fields( array $fields, array $post ): array {
		if ( 'library_book' !== ( $post['post_type'] ?? '' ) && 'library_book' !== get_post_type( (int) ( $post['post_parent'] ?? 0 ) ) ) {
			return $fields;
		}

		foreach ( array( ...Meta::KEYS, 'library_edition_languages', ...Meta::ROLE_KEYS ) as $key ) {
			$fields[ $key ] = Meta::get_label( $key );
		}

		$fields[ self::TERMS ] = __( 'Names', 'library' );

		return $fields;
	}

	/**
	 * The value of a field of a revision, as its comparison shows it: a meta as typed, a list of names one by line, as
	 * the library names them now, and the names of the book, one taxonomy by line.
	 *
	 * @param string  $value    Value, as core reads it.
	 * @param string  $field    Field, a meta key or the names.
	 * @param WP_Post $revision Revision.
	 * @return string
	 */
	public function render_field( $value, string $field, WP_Post $revision ): string {
		$names = fn( array $term_ids ): string => implode( ', ', wp_list_pluck( array_filter( array_map( fn( $term_id ) => get_term( (int) $term_id ), $term_ids ), fn( $term ) => $term instanceof WP_Term ), 'name' ) );

		if ( self::TERMS === $field ) {
			$lines = array();

			foreach ( self::get_revision_terms( $revision->ID ) as $taxonomy => $term_ids ) {
				$object = get_taxonomy( $taxonomy );

				if ( $object ) {
					$lines[] = $object->labels->name . ': ' . $names( $term_ids );
				}
			}

			return implode( "\n", $lines );
		}

		if ( 'library_edition_languages' === $field || in_array( $field, Meta::ROLE_KEYS, true ) ) {
			return $names( get_metadata( 'post', $revision->ID, $field, false ) );
		}

		if ( 'library_collective' === $field ) {
			return rest_sanitize_boolean( $value ) ? __( 'Yes', 'library' ) : '';
		}

		return (string) $value;
	}
}
