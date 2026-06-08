<?php
/**
 * Tests for the global helper functions.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm\Tests;

use WP_Term;
use WP_UnitTestCase;

/**
 * Integration tests for the helper functions defined in functions.php.
 */
class FunctionsTest extends WP_UnitTestCase {

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
	 * Create a post that has a stored primary term.
	 *
	 * @return array{0:int,1:int} Post ID and term ID.
	 */
	private function create_post_with_primary_term(): array {
		$post_id = self::factory()->post->create();
		$term_id = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		update_post_meta( $post_id, self::META_KEY, $term_id );

		return [ $post_id, $term_id ];
	}

	/**
	 * The plugin registers the global helper functions.
	 *
	 * @return void
	 */
	public function test_helper_functions_are_defined(): void {
		$this->assertTrue( function_exists( 'has_primary_term' ) );
		$this->assertTrue( function_exists( 'get_primary_term' ) );
		$this->assertTrue( function_exists( 'get_primary_term_id' ) );
	}

	/**
	 * get_primary_term_id() delegates to the Storage class.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_delegates(): void {
		[ $post_id, $term_id ] = $this->create_post_with_primary_term();

		$this->assertSame( $term_id, get_primary_term_id( self::TAXONOMY, $post_id ) );
	}

	/**
	 * has_primary_term() delegates to the Storage class.
	 *
	 * @return void
	 */
	public function test_has_primary_term_delegates(): void {
		[ $post_id ] = $this->create_post_with_primary_term();

		$this->assertTrue( has_primary_term( self::TAXONOMY, $post_id ) );
	}

	/**
	 * get_primary_term() delegates to the Storage class.
	 *
	 * @return void
	 */
	public function test_get_primary_term_delegates(): void {
		[ $post_id, $term_id ] = $this->create_post_with_primary_term();

		$result = get_primary_term( self::TAXONOMY, $post_id );

		$this->assertInstanceOf( WP_Term::class, $result );
		$this->assertSame( $term_id, $result->term_id );
	}

	/**
	 * The helper surfaces Storage errors for an invalid taxonomy.
	 *
	 * @return void
	 */
	public function test_get_primary_term_id_returns_error_for_invalid_taxonomy(): void {
		$this->assertWPError( get_primary_term_id( 'does_not_exist', 0 ) );
	}
}
