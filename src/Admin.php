<?php
/**
 * Admin class for keeping stored primary terms in sync with assigned terms.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm;

use WP_Post;

/**
 * Admin class responsible for clearing primary terms that are no longer assigned.
 */
class Admin {

	/**
	 * Constructor.
	 *
	 * @param array   $taxonomies Associative array of enabled taxonomies with their data.
	 * @param Storage $storage    Storage handler instance.
	 */
	public function __construct(
		private array $taxonomies,
		private Storage $storage
	) {
	}

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'deleted_term_relationships', [ $this, 'clear_orphaned_primary_terms' ], 10, 3 );
	}

	/**
	 * Clear a stored primary term right after its relationship has been removed.
	 *
	 * @param mixed  $object_id Object ID the term was removed from.
	 * @param array  $tt_ids    Removed term taxonomy IDs (unused).
	 * @param string $taxonomy  Taxonomy slug.
	 *
	 * @return void
	 */
	public function clear_orphaned_primary_terms( mixed $object_id, array $tt_ids, string $taxonomy ): void {
		if ( ! isset( $this->taxonomies[ $taxonomy ] ) ) {
			return;
		}

		if ( wp_is_post_revision( $object_id ) || wp_is_post_autosave( $object_id ) ) {
			return;
		}

		$post = get_post( $object_id );

		if ( ! $post ) {
			return;
		}

		if ( ! is_object_in_taxonomy( $post->post_type, $taxonomy ) ) {
			return;
		}

		$this->maybe_clear_primary_term( $taxonomy, $object_id );
	}

	/**
	 * Clear the primary term for a taxonomy when it is no longer assigned.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $post_id  Post ID.
	 *
	 * @return void
	 */
	private function maybe_clear_primary_term( string $taxonomy, int $post_id ): void {
		$primary_term_id = $this->storage->get_stored_primary_term_id( $taxonomy, $post_id );

		// Nothing to do when no primary term is explicitly stored.
		if ( ! $primary_term_id ) {
			return;
		}

		$assigned_term_ids = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );

		if ( is_wp_error( $assigned_term_ids ) ) {
			return;
		}

		$assigned_term_ids = array_map( 'absint', $assigned_term_ids );

		// Remove the primary term when it is no longer among the assigned terms.
		if (
			! in_array( $primary_term_id, $assigned_term_ids, true )
		) {
			$this->storage->delete_primary_term( $taxonomy, $post_id );
		}
	}
}
