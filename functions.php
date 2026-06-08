<?php
/**
 * Global helper functions for primary term functionality.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

if ( ! function_exists( 'has_primary_term' ) ) {
	/**
	 * Check whether a post has a primary term for the given taxonomy.
	 *
	 * @param  string $taxonomy Taxonomy slug.
	 * @param  int    $post_id  Post ID.
	 * @return bool|\WP_Error
	 */
	function has_primary_term( string $taxonomy, int $post_id = 0 ): bool|\WP_Error {
		return \AchttienVijftien\Plugin\WPPrimaryTerm\Storage::has_primary_term( $taxonomy, $post_id );
	}
}

if ( ! function_exists( 'get_primary_term' ) ) {
	/**
	 * Get primary term for the given post and taxonomy.
	 *
	 * @param  string $taxonomy Taxonomy slug.
	 * @param  int    $post_id  Post ID.
	 * @param  string $output   Output type; OBJECT, ARRAY_A or ARRAY_N.
	 * @param  string $filter   Filter type; raw, edit, db, display, attribute or js.
	 * @return \WP_Term|array|\WP_Error|null
	 */
	function get_primary_term(
		string $taxonomy,
		int $post_id = 0,
		string $output = OBJECT,
		string $filter = 'raw'
	): \WP_Term|array|\WP_Error|null {
		return \AchttienVijftien\Plugin\WPPrimaryTerm\Storage::get_primary_term( $taxonomy, $post_id, $output, $filter );
	}
}

if ( ! function_exists( 'get_primary_term_id' ) ) {
	/**
	 * Get the primary term ID for the given post and taxonomy.
	 *
	 * @param  string $taxonomy Taxonomy slug.
	 * @param  int    $post_id  Post ID.
	 * @return int|\WP_Error
	 */
	function get_primary_term_id( string $taxonomy, int $post_id = 0 ): int|\WP_Error {
		return \AchttienVijftien\Plugin\WPPrimaryTerm\Storage::get_primary_term_id( $taxonomy, $post_id );
	}
}
