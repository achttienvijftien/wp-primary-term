<?php
/**
 * Tests for the Admin class.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm\Tests;

use AchttienVijftien\Plugin\WPPrimaryTerm\Admin;
use AchttienVijftien\Plugin\WPPrimaryTerm\Storage;
use WP_UnitTestCase;

/**
 * Integration tests for AchttienVijftien\Plugin\WPPrimaryTerm\Admin.
 */
class AdminTest extends WP_UnitTestCase {

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
	 * Build an Admin instance enabled for the category taxonomy.
	 *
	 * @return Admin
	 */
	private function make_admin(): Admin {
		$taxonomies = [ self::TAXONOMY => get_taxonomy( self::TAXONOMY ) ];

		return new Admin( $taxonomies, new Storage( $taxonomies ) );
	}

	/**
	 * register_hooks hooks the cleanup callback onto deleted_term_relationships.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_deleted_relationships_action(): void {
		$admin = $this->make_admin();
		$admin->register_hooks();

		$this->assertSame(
			10,
			has_action( 'deleted_term_relationships', [ $admin, 'clear_orphaned_primary_terms' ] )
		);
	}

	/**
	 * A primary term that is no longer assigned to the post is cleared.
	 *
	 * @return void
	 */
	public function test_clears_primary_term_when_no_longer_assigned(): void {
		$post_id = self::factory()->post->create();
		$term_a  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );
		$term_b  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		// Only term B remains assigned, but the primary still points at term A.
		wp_set_object_terms( $post_id, [ $term_b ], self::TAXONOMY );
		update_post_meta( $post_id, self::META_KEY, $term_a );

		$this->make_admin()->clear_orphaned_primary_terms( $post_id, [], self::TAXONOMY );

		$this->assertFalse( metadata_exists( 'post', $post_id, self::META_KEY ) );
	}

	/**
	 * A primary term is cleared when the post has no assigned terms at all.
	 *
	 * @return void
	 */
	public function test_clears_primary_term_when_no_terms_assigned(): void {
		$post_id = self::factory()->post->create();
		$term_a  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		// Remove every term in the taxonomy while the primary still points at term A.
		wp_set_object_terms( $post_id, [], self::TAXONOMY );
		update_post_meta( $post_id, self::META_KEY, $term_a );

		$this->make_admin()->clear_orphaned_primary_terms( $post_id, [], self::TAXONOMY );

		$this->assertFalse( metadata_exists( 'post', $post_id, self::META_KEY ) );
	}

	/**
	 * A primary term that is still assigned to the post is kept.
	 *
	 * @return void
	 */
	public function test_keeps_primary_term_when_still_assigned(): void {
		$post_id = self::factory()->post->create();
		$term_a  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );
		$term_b  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		wp_set_object_terms( $post_id, [ $term_a, $term_b ], self::TAXONOMY );
		update_post_meta( $post_id, self::META_KEY, $term_a );

		$this->make_admin()->clear_orphaned_primary_terms( $post_id, [], self::TAXONOMY );

		$this->assertSame( $term_a, (int) get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * Nothing is changed when no primary term is stored for the post.
	 *
	 * @return void
	 */
	public function test_does_nothing_without_stored_primary_term(): void {
		$post_id = self::factory()->post->create();
		$term_a  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		wp_set_object_terms( $post_id, [ $term_a ], self::TAXONOMY );

		$this->make_admin()->clear_orphaned_primary_terms( $post_id, [], self::TAXONOMY );

		$this->assertFalse( metadata_exists( 'post', $post_id, self::META_KEY ) );
	}

	/**
	 * Deselecting the primary term on the post clears the stored meta through the hook.
	 *
	 * @return void
	 */
	public function test_clears_primary_term_when_relationship_removed(): void {
		$admin = $this->make_admin();
		$admin->register_hooks();

		$post_id = self::factory()->post->create();
		$term_a  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );
		$term_b  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		wp_set_object_terms( $post_id, [ $term_a, $term_b ], self::TAXONOMY );
		update_post_meta( $post_id, self::META_KEY, $term_a );

		// Removing term A drops its relationship and fires deleted_term_relationships.
		wp_set_object_terms( $post_id, [ $term_b ], self::TAXONOMY );

		$this->assertFalse( metadata_exists( 'post', $post_id, self::META_KEY ) );
	}

	/**
	 * Deleting the primary term globally clears the stored meta through the hook.
	 *
	 * @return void
	 */
	public function test_clears_primary_term_when_term_deleted(): void {
		$admin = $this->make_admin();
		$admin->register_hooks();

		$post_id = self::factory()->post->create();
		$term_a  = self::factory()->term->create( [ 'taxonomy' => self::TAXONOMY ] );

		wp_set_object_terms( $post_id, [ $term_a ], self::TAXONOMY );
		update_post_meta( $post_id, self::META_KEY, $term_a );

		// Deleting the term removes its relationships and fires deleted_term_relationships.
		wp_delete_term( $term_a, self::TAXONOMY );

		$this->assertFalse( metadata_exists( 'post', $post_id, self::META_KEY ) );
	}

	/**
	 * A relationship removal on a taxonomy the plugin does not manage is ignored.
	 *
	 * @return void
	 */
	public function test_ignores_unmanaged_taxonomy(): void {
		$admin = $this->make_admin();
		$admin->register_hooks();

		$post_id = self::factory()->post->create();
		$tag     = self::factory()->term->create( [ 'taxonomy' => 'post_tag' ] );

		wp_set_object_terms( $post_id, [ $tag ], 'post_tag' );
		update_post_meta( $post_id, '_primary_post_tag', $tag );

		// Removing the tag fires the hook for a taxonomy that is not enabled.
		wp_set_object_terms( $post_id, [], 'post_tag' );

		$this->assertSame( $tag, (int) get_post_meta( $post_id, '_primary_post_tag', true ) );
	}
}