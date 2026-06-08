<?php
/**
 * Tests for the Storage class.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm\Tests;

use AchttienVijftien\Plugin\WPPrimaryTerm\Storage;
use WP_Term;
use WP_UnitTestCase;

/**
 * Integration tests for AchttienVijftien\Plugin\WPPrimaryTerm\Storage.
 */
class StorageTest extends WP_UnitTestCase {

	/**
	 * Taxonomy used throughout the tests.
	 *
	 * @var string
	 */
	private const TAXONOMY = 'category';

	/**
	 * Meta key for the test taxonomy.
	 *
	 * @var string
	 */
	private const META_KEY = '_primary_category';

	/**
	 * Remove plugin filters that may have been registered during a test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'achttienvijftien_primary_term_use_fallback' );
		remove_all_filters( 'achttienvijftien_primary_term' );
		parent::tear_down();
	}

	/**
	 * Create a post without any assigned terms in the test taxonomy.
	 *
	 * @return int Post ID.
	 */
	private function create_post_without_terms(): int {
		$post_id = self::factory()->post->create();
		// Posts get a default category on insert; remove it so the post has no terms.
		wp_set_object_terms( $post_id, [], self::TAXONOMY );

		return $post_id;
	}

	/**
	 * The meta key template constant is exposed for the JavaScript layer.
	 *
	 * @return void
	 */
	public function test_meta_key_template_constant(): void {
		$this->assertSame( '_primary_%s', Storage::META_KEY_TEMPLATE );
	}

	/**
	 * Constructing Storage registers a meta field per taxonomy.
	 *
	 * @return void
	 */
	public function test_register_meta_fields_registers_meta_per_taxonomy(): void {
		new Storage( [ self::TAXONOMY => get_taxonomy( self::TAXONOMY ) ] );

		$this->assertTrue( registered_meta_key_exists( 'post', self::META_KEY ) );

		$registered = get_registered_meta_keys( 'post' )[ self::META_KEY ];
		$this->assertSame( 'integer', $registered['type'] );
		$this->assertTrue( $registered['single'] );
		$this->assertTrue( $registered['show_in_rest'] );
	}

	/**
	 * An empty taxonomy yields an invalid_taxonomy error.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_empty_taxonomy_returns_error(): void {
		$result = Storage::get_primary_term_id( '', 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_taxonomy', $result->get_error_code() );
	}

	/**
	 * A non-existent taxonomy yields an invalid_taxonomy error.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_unknown_taxonomy_returns_error(): void {
		$result = Storage::get_primary_term_id( 'does_not_exist', 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_taxonomy', $result->get_error_code() );
	}

	/**
	 * A missing post ID without a current post yields an invalid_post error.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_no_post_returns_error(): void {
		$result = Storage::get_primary_term_id( self::TAXONOMY, 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_post', $result->get_error_code() );
	}

	/**
	 * A stored primary term meta value is returned as an integer.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_returns_stored_meta(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		update_post_meta( $post_id, self::META_KEY, $term_id );

		$this->assertSame( $term_id, Storage::get_primary_term_id( self::TAXONOMY, $post_id ) );
	}

	/**
	 * Without stored meta the first assigned term is used as a fallback.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_falls_back_to_first_assigned_term(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		wp_set_object_terms( $post_id, [ $term_id ], self::TAXONOMY );

		$this->assertSame( $term_id, Storage::get_primary_term_id( self::TAXONOMY, $post_id ) );
	}

	/**
	 * Without stored meta and without assigned terms zero is returned.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_without_terms_returns_zero(): void {
		$post_id = $this->create_post_without_terms();

		$this->assertSame( 0, Storage::get_primary_term_id( self::TAXONOMY, $post_id ) );
	}

	/**
	 * Disabling the fallback filter prevents falling back to assigned terms.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_respects_disabled_fallback(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		wp_set_object_terms( $post_id, [ $term_id ], self::TAXONOMY );
		add_filter( 'achttienvijftien_primary_term_use_fallback', '__return_false' );

		$this->assertSame( 0, Storage::get_primary_term_id( self::TAXONOMY, $post_id ) );
	}

	/**
	 * has_primary_term returns true when a primary term is stored.
	 *
	 * @return void
	 */
	public function test_has_primary_term_true_when_meta_set(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		update_post_meta( $post_id, self::META_KEY, $term_id );

		$this->assertTrue( Storage::has_primary_term( self::TAXONOMY, $post_id ) );
	}

	/**
	 * has_primary_term returns false when no primary term resolves.
	 *
	 * @return void
	 */
	public function test_has_primary_term_false_without_term(): void {
		$post_id = $this->create_post_without_terms();

		$this->assertFalse( Storage::has_primary_term( self::TAXONOMY, $post_id ) );
	}

	/**
	 * has_primary_term returns false for an invalid taxonomy.
	 *
	 * @return void
	 */
	public function test_has_primary_term_returns_false_for_invalid_taxonomy(): void {
		$this->assertFalse( Storage::has_primary_term( 'does_not_exist', 0 ) );
	}

	/**
	 * get_primary_term surfaces the error from an empty taxonomy.
	 *
	 * @return void
	 */
	public function test_get_primary_term_empty_taxonomy_returns_error(): void {
		$result = Storage::get_primary_term( '', 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_taxonomy', $result->get_error_code() );
	}

	/**
	 * get_primary_term surfaces the error from an invalid taxonomy.
	 *
	 * @return void
	 */
	public function test_get_primary_term_returns_error_for_invalid_taxonomy(): void {
		$result = Storage::get_primary_term( 'does_not_exist', 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_taxonomy', $result->get_error_code() );
	}

	/**
	 * get_primary_term returns the WP_Term object for the stored term.
	 *
	 * @return void
	 */
	public function test_get_primary_term_returns_term_object(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		update_post_meta( $post_id, self::META_KEY, $term_id );

		$result = Storage::get_primary_term( self::TAXONOMY, $post_id );

		$this->assertInstanceOf( WP_Term::class, $result );
		$this->assertSame( $term_id, $result->term_id );
	}

	/**
	 * get_primary_term honours the requested output type.
	 *
	 * @return void
	 */
	public function test_get_primary_term_returns_array_output(): void {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		update_post_meta( $post_id, self::META_KEY, $term_id );

		$result = Storage::get_primary_term( self::TAXONOMY, $post_id, ARRAY_A );

		$this->assertIsArray( $result );
		$this->assertSame( $term_id, $result['term_id'] );
	}

	/**
	 * get_primary_term returns null when no term resolves.
	 *
	 * @return void
	 */
	public function test_get_primary_term_returns_null_without_term(): void {
		$post_id = $this->create_post_without_terms();

		$this->assertNull( Storage::get_primary_term( self::TAXONOMY, $post_id ) );
	}

	/**
	 * The achttienvijftien_primary_term filter can override the returned term.
	 *
	 * @return void
	 */
	public function test_get_primary_term_filter_overrides_result(): void {
		$post_id = $this->create_post_without_terms();
		$sentinel = new WP_Term( (object) [ 'term_id' => 999 ] );

		add_filter(
			'achttienvijftien_primary_term',
			static function () use ( $sentinel ) {
				return $sentinel;
			}
		);

		$this->assertSame( $sentinel, Storage::get_primary_term( self::TAXONOMY, $post_id ) );
	}
}
