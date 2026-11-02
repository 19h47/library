<?php
/**
 * Cache of what is computed from every book: the books themselves, the reading idea, the achievements and the reading order.
 *
 * @link       https://github.com/19h47/library
 * @since      9.4.0
 *
 * @package    Library
 * @subpackage Library/includes
 */

/**
 * Keeps in transients what takes every book to compute, until a book changes: its post, its meta, its terms, or the
 * name of a term. A change makes a new generation, whose transients are new, the former ones expiring within a day.
 *
 * @since      9.4.0
 * @package    Library
 * @subpackage Library/includes
 * @author     Jérémy Levron <jeremylevron@19h47.fr>
 */
class Library_Cache {

	/**
	 * Option counting the changes of the books, which names the transients of the cache.
	 *
	 * @var string
	 */
	private const GENERATION = 'library_cache_generation';

	/**
	 * Whether the books changed during the current request: the generation changes once.
	 *
	 * @var bool
	 */
	private static bool $changed = false;

	/**
	 * Version of the plugin, as its header says, once read.
	 *
	 * @var string|null
	 */
	private static ?string $version = null;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'save_post_library_book', array( $this, 'forget' ) );
		add_action( 'delete_post', array( $this, 'forget_book' ) );
		add_action( 'added_post_meta', array( $this, 'forget_meta' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'forget_meta' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'forget_meta' ), 10, 3 );
		add_action( 'set_object_terms', array( $this, 'forget_terms' ), 10, 4 );
		add_action( 'edited_term', array( $this, 'forget_term' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'forget_term' ), 10, 3 );
	}

	/**
	 * The value of `$key`, as `$compute` returns it, kept until a book changes, in the language of the request and for the
	 * version of the plugin: the reading idea and the achievements say their reasons and titles in it, and a new version
	 * may change what a value holds. A value of the day, such as the reading idea, which reads the date, has the day in its
	 * key. A request that changed a book keeps nothing: the generation changes once, at its first change, and the value may
	 * predate the next ones.
	 *
	 * @param string   $key     Key, such as achievements.
	 * @param callable $compute Function that computes the value.
	 * @return mixed
	 */
	public static function remember( string $key, callable $compute ) {
		$generation = (int) get_option( self::GENERATION, 0 );
		$name       = sprintf( 'library_%s_%s', $key, md5( $generation . determine_locale() . self::get_version() ) );
		$value      = get_transient( $name );

		if ( false !== $value ) {
			return $value;
		}

		$value = $compute();

		if ( ! self::$changed ) {
			// An expiration keeps it out of the options loaded on every page, and lets the transients of former generations go.
			set_transient( $name, $value, DAY_IN_SECONDS );
		}

		return $value;
	}

	/**
	 * Version of the plugin, from its header.
	 *
	 * @return string
	 */
	private static function get_version(): string {
		self::$version ??= get_file_data( LIBRARY_FILE, array( 'Version' => 'Version' ) )['Version'];

		return self::$version;
	}

	/**
	 * Forget what was computed from the books: a new generation, once per request.
	 *
	 * @return void
	 */
	public function forget(): void {
		if ( self::$changed ) {
			return;
		}

		self::$changed = true;
		update_option( self::GENERATION, (int) get_option( self::GENERATION, 0 ) + 1, true );
	}

	/**
	 * Forget once a book is deleted.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function forget_book( int $post_id ): void {
		if ( 'library_book' === get_post_type( $post_id ) ) {
			$this->forget();
		}
	}

	/**
	 * Forget once a meta of a book changes, whatever wrote it, such as the REST API; the meta of WordPress, such as its
	 * edit lock, change nothing computed.
	 *
	 * @param int|int[] $meta_ids Meta ID(s).
	 * @param int       $post_id  Post ID.
	 * @param string    $meta_key Meta key.
	 * @return void
	 */
	public function forget_meta( $meta_ids, int $post_id, string $meta_key ): void {
		if ( str_starts_with( $meta_key, 'library_' ) && 'library_book' === get_post_type( $post_id ) ) {
			$this->forget();
		}
	}

	/**
	 * Forget once the terms of a book change, such as its people.
	 *
	 * @param int    $object_id Object ID.
	 * @param array  $terms     Terms.
	 * @param array  $tt_ids    Term taxonomy IDs.
	 * @param string $taxonomy  Taxonomy.
	 * @return void
	 */
	public function forget_terms( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {
		if ( in_array( $taxonomy, Library_Taxonomies::TAXONOMIES, true ) ) {
			$this->forget();
		}
	}

	/**
	 * Forget once a term of the books is renamed or deleted, such as a person: the reasons and achievements name them.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function forget_term( int $term_id, int $tt_id, string $taxonomy ): void {
		if ( in_array( $taxonomy, Library_Taxonomies::TAXONOMIES, true ) ) {
			$this->forget();
		}
	}
}
