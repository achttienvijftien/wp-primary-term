<?php
/**
 * Storage class for handling primary term data.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm;

use WP_Error;
use WP_Term;

/**
 * Storage class responsible for saving and retrieving primary terms.
 */
class Storage {

	/**
	 * Meta key template for primary terms.
	 */
	public const string META_KEY_TEMPLATE = '_primary_%s';

	/**
	 * Enabled taxonomies.
	 *
	 * @var array
	 */
	private array $taxonomies;

	/**
	 * Constructor.
	 *
	 * @param array $taxonomies Associative array of enabled taxonomies with their data.
	 */
	public function __construct( array $taxonomies ) {
		$this->taxonomies = $taxonomies;
		$this->register_meta_fields();
	}

	/**
	 * Register meta fields.
	 */
	public function register_meta_fields(): void {
		foreach ( $this->taxonomies as $slug => $taxonomy_data ) {
			$meta_key = self::get_primary_term_meta_key( $slug );

			register_meta(
				'post',
				$meta_key,
				[
					'type'              => 'integer',
					'single'            => true,
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'show_in_rest'      => true,
					'auth_callback'     => function ( $allowed, $meta_key, $object_id ) {
						return current_user_can( 'edit_post', $object_id );
					},
				]
			);
		}
	}

	/**
	 * Get the meta key for a primary term by taxonomy slug.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return string
	 */
	private static function get_primary_term_meta_key( string $taxonomy ): string {
		return sprintf( self::META_KEY_TEMPLATE, $taxonomy );
	}

	/**
	 * Check whether a post has a primary term for the given taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $post_id  Post ID.
	 *
	 * @return bool
	 */
	public static function has_primary_term( string $taxonomy, int $post_id = 0 ): bool {
		$primary_term_id = self::get_primary_term_id( $taxonomy, $post_id );

		if ( ! $primary_term_id || is_wp_error( $primary_term_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the primary term ID for the given post and taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $post_id  Post ID.
	 *
	 * @return int|\WP_Error
	 */
	public static function get_primary_term_id( string $taxonomy, int $post_id = 0 ): int|\WP_Error {
		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return new \WP_Error( 'invalid_taxonomy', __( 'Invalid taxonomy.', 'wp-primary-term' ) );
		}

		$post_id = $post_id ?: get_the_ID();

		if ( ! $post_id ) {
			return new \WP_Error( 'invalid_post', __( 'Invalid post.', 'wp-primary-term' ) );
		}

		$primary_term_id = get_post_meta(
			$post_id,
			self::get_primary_term_meta_key( $taxonomy ),
			true
		);

		// Return primary term if set.
		if ( ! empty( $primary_term_id ) ) {
			return absint( $primary_term_id );
		}

		/**
		 * Filters whether to use a fallback term when no primary term is set.
		 *
		 * @param bool   $use_fallback Whether to fall back to the first assigned term.
		 * @param string $taxonomy     Taxonomy slug.
		 * @param int    $post_id      Post ID.
		 */
		$use_fallback = apply_filters(
			'achttienvijftien_primary_term_use_fallback',
			true,
			$taxonomy,
			$post_id
		);

		if ( ! $use_fallback ) {
			return 0;
		}

		$current_taxonomy_terms = wp_get_post_terms( $post_id, $taxonomy );

		if ( is_wp_error( $current_taxonomy_terms ) ) {
			return $current_taxonomy_terms;
		}

		if ( ! empty( $current_taxonomy_terms ) ) {
			$primary_term = array_shift( $current_taxonomy_terms );

			return $primary_term->term_id;
		}

		return 0;
	}

	/**
	 * Get primary term object for the given post and taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param int    $post_id  Post ID.
	 * @param string $output   Output type; OBJECT, ARRAY_A or ARRAY_N.
	 * @param string $filter   Filter type; raw, edit, db, display, attribute or js.
	 *
	 * @return \WP_Term|array|\WP_Error|null|mixed
	 */
	public static function get_primary_term(
		string $taxonomy,
		int $post_id = 0,
		string $output = OBJECT,
		string $filter = 'raw'
	): mixed {
		$primary_term    = null;
		$primary_term_id = self::get_primary_term_id( $taxonomy, $post_id );

		if ( is_wp_error( $primary_term_id ) ) {
			return $primary_term_id;
		}

		if ( $primary_term_id ) {
			$primary_term = get_term( $primary_term_id, $taxonomy, $output, $filter );
		}

		/**
		 * Filters the primary term object for the given post and taxonomy.
		 *
		 * @param \WP_Term|array|\WP_Error|null $primary_term Primary term object, WP_Error, or null if none.
		 * @param string                        $taxonomy     Taxonomy slug.
		 * @param int                           $post_id      Post ID.
		 * @param string                        $output       Output type; OBJECT, ARRAY_A or ARRAY_N.
		 * @param string                        $filter       Filter type; raw, edit, db, display, attribute or js.
		 */
		return apply_filters(
			'achttienvijftien_primary_term',
			$primary_term,
			$taxonomy,
			$post_id,
			$output,
			$filter
		);
	}
}
