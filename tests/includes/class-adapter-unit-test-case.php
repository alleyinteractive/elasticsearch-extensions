<?php
/**
 * Elasticsearch Extensions Tests: Adapter_UnitTestCase class.
 *
 * @package Elasticsearch_Extensions
 * @subpackage Tests
 */

/**
 * Adapter_UnitTestCase class.
 */
abstract class Adapter_UnitTestCase extends \Pest\PestPluginWordPress\FrameworkTestCase {

	/**
	 * Flush the index.
	 */
	abstract protected static function flush(): void;

	/**
	 * Force Elasticsearch to refresh its index to make content changes
	 * available to search.
	 *
	 * Without refreshing the index, inserting content then immediately
	 * searching for it might (and almost certainly will) not return the
	 * content.
	 *
	 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html
	 */
	abstract protected static function refresh_index(): void;

	/**
	 * Index one or more posts in Elasticsearch.
	 *
	 * @param mixed $posts Can be a post ID, WP_Post object, SP_Post object, or
	 *                     an array of any of the above.
	 */
	abstract protected static function index_content( $posts ): void;

	/**
	 * Index one or more posts in Elasticsearch and refresh the index.
	 *
	 * @param mixed $posts Can be a post ID, WP_Post object, SP_Post object, or
	 *                     an array of any of the above.
	 */
	protected static function index( $posts ): void {
		$posts = is_array( $posts ) ? $posts : [ $posts ];

		static::index_content( $posts );
		static::refresh_index();
	}

	/**
	 * Create an assortment of sample content.
	 *
	 * While some of this content may not be used, it adds enough noise to the
	 * system to help catch issues that carefully crafted datasets can miss. At
	 * least, that's the story I tell myself when it catches a false positive
	 * for me.
	 */
	protected static function create_sample_content() {
		$cat_a = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'cat-a' ) );
		$cat_b = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'cat-b' ) );
		$cat_c = self::factory()->term->create( array( 'taxonomy' => 'category', 'name' => 'cat-c' ) );

		$posts_to_index = array(
			self::factory()->post->create( array( 'post_title' => 'tag-נ', 'tags_input' => array( 'tag-נ' ), 'post_date' => '2008-11-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'cats-a-b-c', 'post_date' => '2008-12-01 00:00:00', 'post_category' => array( $cat_a, $cat_b, $cat_c ), 'menu_order' => 10 ) ),
			self::factory()->post->create( array( 'post_title' => 'cats-a-and-b', 'post_date' => '2009-01-01 00:00:00', 'post_category' => array( $cat_a, $cat_b ) ) ),
			self::factory()->post->create( array( 'post_title' => 'cats-b-and-c', 'post_date' => '2009-02-01 00:00:00', 'post_category' => array( $cat_b, $cat_c ) ) ),
			self::factory()->post->create( array( 'post_title' => 'cats-a-and-c', 'post_date' => '2009-03-01 00:00:00', 'post_category' => array( $cat_a, $cat_c ) ) ),
			self::factory()->post->create( array( 'post_title' => 'cat-a', 'post_date' => '2009-04-01 00:00:00', 'post_category' => array( $cat_a ), 'menu_order' => 6 ) ),
			self::factory()->post->create( array( 'post_title' => 'cat-b', 'post_date' => '2009-05-01 00:00:00', 'post_category' => array( $cat_b ) ) ),
			self::factory()->post->create( array( 'post_title' => 'cat-c', 'post_date' => '2009-06-01 00:00:00', 'post_category' => array( $cat_c ) ) ),
			self::factory()->post->create( array( 'post_title' => 'lorem-ipsum', 'post_date' => '2009-07-01 00:00:00', 'menu_order' => 2 ) ),
			self::factory()->post->create( array( 'post_title' => 'comment-test', 'post_date' => '2009-08-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'one-trackback', 'post_date' => '2009-09-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'many-trackbacks', 'post_date' => '2009-10-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'no-comments', 'post_date' => '2009-10-02 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'one-comment', 'post_date' => '2009-11-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'contributor-post-approved', 'post_date' => '2009-12-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'embedded-video', 'post_date' => '2010-01-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'simple-markup-test', 'post_date' => '2010-02-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'raw-html-code', 'post_date' => '2010-03-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tags-a-b-c', 'tags_input' => array( 'tag-a', 'tag-b', 'tag-c' ), 'post_date' => '2010-04-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tag-a', 'tags_input' => array( 'tag-a' ), 'post_date' => '2010-05-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tag-b', 'tags_input' => array( 'tag-b' ), 'post_date' => '2010-06-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tag-c', 'tags_input' => array( 'tag-c' ), 'post_date' => '2010-07-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tags-a-and-b', 'tags_input' => array( 'tag-a', 'tag-b' ), 'post_date' => '2010-08-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tags-b-and-c', 'tags_input' => array( 'tag-b', 'tag-c' ), 'post_date' => '2010-09-01 00:00:00' ) ),
			self::factory()->post->create( array( 'post_title' => 'tags-a-and-c', 'tags_input' => array( 'tag-a', 'tag-c' ), 'post_date' => '2010-10-01 00:00:00' ) ),
		);

		// Update a few posts' modified dates for sorting tests.
		self::set_post_modified_date( $posts_to_index[1], '2020-01-02 03:04:03' );
		self::set_post_modified_date( $posts_to_index[5], '2020-01-02 03:04:02' );
		self::set_post_modified_date( $posts_to_index[8], '2020-01-02 03:04:01' );

		$parent_one = self::factory()->post->create( array( 'post_title' => 'parent-one', 'post_date' => '2007-01-01 00:00:01' ) );
		$parent_two = self::factory()->post->create( array( 'post_title' => 'parent-two', 'post_date' => '2007-01-01 00:00:02' ) );
		$posts_to_index[] = $parent_one;
		$posts_to_index[] = $parent_two;
		$posts_to_index[] = self::factory()->post->create( array( 'post_title' => 'parent-three', 'post_date' => '2007-01-01 00:00:03' ) );

		$posts_to_index[] = self::factory()->post->create( array( 'post_title' => 'child-one', 'post_parent' => $parent_one, 'post_date' => '2007-01-01 00:00:04' ) );
		$posts_to_index[] = self::factory()->post->create( array( 'post_title' => 'child-two', 'post_parent' => $parent_one, 'post_date' => '2007-01-01 00:00:05' ) );
		$posts_to_index[] = self::factory()->post->create( array( 'post_title' => 'child-three', 'post_parent' => $parent_two, 'post_date' => '2007-01-01 00:00:06' ) );
		$posts_to_index[] = self::factory()->post->create( array( 'post_title' => 'child-four', 'post_parent' => $parent_two, 'post_date' => '2007-01-01 00:00:07' ) );

		return $posts_to_index;
	}

	/**
	 * Set a post's post_modified date.
	 *
	 * WordPress core doesn't provide a way to manually set a post's
	 * post_modified date.
	 *
	 * @see https://core.trac.wordpress.org/ticket/36595
	 *
	 * @param int    $ID            Post ID.
	 * @param string $post_modified Datetime string in the format Y-m-d H:i:s.
	 */
	protected static function set_post_modified_date( $ID, $post_modified ): void {
		global $wpdb;

		$wpdb->update( $wpdb->posts, compact( 'post_modified' ), compact( 'ID' ) );

		clean_post_cache( $ID );
	}

	/**
	 * Is the current version of WordPress at least ... ?
	 *
	 * @param  float $min_version Minimum version required, e.g. 3.9.
	 * @return bool True if it is, false if it isn't.
	 */
	protected function is_wp_at_least( $min_version ): bool {
		global $wp_version;
		return floatval( $wp_version ) >= $min_version;
	}
}
