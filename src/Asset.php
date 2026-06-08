<?php
/**
 * Asset management class for admin scripts and styles.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm;

/**
 * Asset class responsible for enqueuing admin assets.
 */
class Asset {

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
	}

	/**
	 * Register WordPress hooks.
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Enqueue admin assets on post edit screens.
	 *
	 * @param string|mixed $hook The current admin page hook.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( mixed $hook ): void {
		// Only load on post edit screens.
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		// Get current post type.
		$current_post_type = get_post_type();
		if ( ! $current_post_type ) {
			return;
		}

		// Currently only supports Gutenberg enabled post types.
		if ( ! post_type_supports( $current_post_type, 'editor' ) ) {
			return;
		}

		// Filter taxonomies that are relevant for this post type.
		$relevant_taxonomies = array_filter(
			$this->taxonomies,
			function ( $taxonomy_data ) use ( $current_post_type ) {
				return in_array( $current_post_type, $taxonomy_data->object_type, true );
			}
		);

		// Don't load assets if no relevant taxonomies.
		if ( empty( $relevant_taxonomies ) ) {
			return;
		}

		$asset_file  = $this->get_asset_file();
		$script_file = $this->get_script_file();

		if ( ! file_exists( $script_file ) ) {
			return;
		}

		try {
			$asset = file_exists( $asset_file ) && is_readable( $asset_file ) ? (array) require $asset_file : [];
		} catch ( \Throwable ) {
			$asset = [];
		}

		$version = $asset['version'] ?? (string) filemtime( $script_file );
		$deps    = $asset['dependencies'] ?? [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data' ];

		wp_enqueue_script(
			'wp-primary-term',
			plugins_url( '/build/wp-primary-term.js', __DIR__ ),
			$deps,
			$version,
			true
		);

		// Prepare taxonomy data for JavaScript.
		$taxonomy_data = [];
		foreach ( $relevant_taxonomies as $slug => $taxonomy ) {
			$taxonomy_data[ $slug ] = [
				'name'         => $slug,
				'label'        => $taxonomy->labels->singular_name,
				'hierarchical' => $taxonomy->hierarchical,
			];
		}

		wp_localize_script(
			'wp-primary-term',
			'wpPrimaryTerm',
			[
				'taxonomies'      => $taxonomy_data,
				'metaKeyTemplate' => Storage::META_KEY_TEMPLATE,
				'labels'          => [
					'select' => __( 'Select primary term', 'wp-primary-term' ),
					// translators: %s = singular term label.
					'meta'   => __( 'Primary %s', 'wp-primary-term' ),
				],
			]
		);
	}

	/**
	 * Absolute path to the generated build asset metadata file.
	 *
	 * @return string
	 */
	protected function get_asset_file(): string {
		return dirname( __DIR__ ) . '/build/wp-primary-term.asset.php';
	}

	/**
	 * Absolute path to the built script file.
	 *
	 * @return string
	 */
	protected function get_script_file(): string {
		return dirname( __DIR__ ) . '/build/wp-primary-term.js';
	}
}
