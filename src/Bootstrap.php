<?php
/**
 * Bootstrap class for initializing the primary term functionality.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm;

/**
 * Bootstrap class responsible for initialization and taxonomy filtering.
 */
class Bootstrap {

	/**
	 * Singleton instance.
	 *
	 * @var Bootstrap|null
	 */
	private static ?Bootstrap $instance = null;

	/**
	 * Asset handler instance.
	 *
	 * @var Asset
	 */
	private Asset $asset;

	/**
	 * Storage handler instance.
	 *
	 * @var Storage
	 */
	private Storage $storage;

	/**
	 * Admin handler instance.
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', [ $this, 'init' ], 15 );
	}

	/**
	 * Initialize plugin functionality.
	 */
	public function init(): void {
		$taxonomies = $this->get_enabled_taxonomies();

		// Only initialize if taxonomies are enabled.
		if ( empty( $taxonomies ) ) {
			return;
		}

		// Instantiate and register hooks.
		$this->asset   = new Asset( $taxonomies );
		$this->storage = new Storage( $taxonomies );
		$this->admin   = new Admin( $taxonomies, $this->storage );

		$this->asset->register_hooks();
		$this->admin->register_hooks();
	}

	/**
	 * Initialize the primary term functionality.
	 *
	 * @return static The singleton instance.
	 */
	public static function boot(): static {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Get enabled taxonomies with their associated post types.
	 *
	 * @return array Associative array with taxonomy slug as key and taxonomy object as value.
	 */
	private function get_enabled_taxonomies(): array {
		/**
		 * Filter to enable primary term functionality for specific taxonomies.
		 *
		 * @param array $taxonomies List of taxonomy slugs. Default empty array.
		 */
		$taxonomy_slugs = apply_filters( 'achttienvijftien_primary_term_taxonomies', [] );
		$taxonomy_slugs = array_filter( array_unique( $taxonomy_slugs ), 'taxonomy_exists' );

		$taxonomies = [];
		foreach ( $taxonomy_slugs as $slug ) {
			$taxonomy_obj = get_taxonomy( $slug );
			if ( $taxonomy_obj ) {
				$taxonomies[ $slug ] = $taxonomy_obj;
			}
		}

		return $taxonomies;
	}
}
