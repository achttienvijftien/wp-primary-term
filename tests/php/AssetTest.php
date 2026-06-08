<?php
/**
 * Tests for the Asset class.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm\Tests;

use AchttienVijftien\Plugin\WPPrimaryTerm\Asset;
use WP_UnitTestCase;

/**
 * Integration tests for AchttienVijftien\Plugin\WPPrimaryTerm\Asset.
 */
class AssetTest extends WP_UnitTestCase {

	/**
	 * Script handle registered by the plugin.
	 *
	 * @var string
	 */
	private const HANDLE = 'wp-primary-term';

	/**
	 * Load the plugin admin helpers and start each test with a clean script registry.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
	}

	/**
	 * Reset the global post and script registry after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unset( $GLOBALS['post'] );
		$GLOBALS['wp_scripts'] = null;
		parent::tear_down();
	}

	/**
	 * Build an Asset instance enabled for the category taxonomy.
	 *
	 * @return Asset
	 */
	private function make_asset(): Asset {
		return new Asset( [ 'category' => get_taxonomy( 'category' ) ] );
	}

	/**
	 * Set the global post to a freshly created post of the given type.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return void
	 */
	private function set_global_post( string $post_type ): void {
		$post_id = self::factory()->post->create( [ 'post_type' => $post_type ] );
		$GLOBALS['post'] = get_post( $post_id );
	}

	/**
	 * Build an Asset for the category taxonomy with build path methods stubbed.
	 *
	 * @param array<string,string> $paths Method name (get_asset_file / get_script_file) => path to return.
	 *
	 * @return Asset
	 */
	private function make_asset_with_paths( array $paths ): Asset {
		$asset = $this->getMockBuilder( Asset::class )
			->setConstructorArgs( [ [ 'category' => get_taxonomy( 'category' ) ] ] )
			->onlyMethods( array_keys( $paths ) )
			->getMock();

		foreach ( $paths as $method => $path ) {
			$asset->method( $method )->willReturn( $path );
		}

		return $asset;
	}

	/**
	 * register_hooks hooks the enqueue callback onto admin_enqueue_scripts.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_enqueue_action(): void {
		$asset = $this->make_asset();
		$asset->register_hooks();

		$this->assertSame(
			10,
			has_action( 'admin_enqueue_scripts', [ $asset, 'enqueue_admin_assets' ] )
		);
	}

	/**
	 * No assets are enqueued outside of the post edit screens.
	 *
	 * @return void
	 */
	public function test_enqueue_skips_on_non_edit_screen(): void {
		$this->set_global_post( 'post' );
		$this->make_asset()->enqueue_admin_assets( 'edit.php' );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * No assets are enqueued when there is no current post type.
	 *
	 * @return void
	 */
	public function test_enqueue_skips_without_post_type(): void {
		unset( $GLOBALS['post'] );
		$this->make_asset()->enqueue_admin_assets( 'post.php' );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * No assets are enqueued for post types without editor support.
	 *
	 * @return void
	 */
	public function test_enqueue_skips_when_post_type_lacks_editor_support(): void {
		register_post_type( 'pt_no_editor', [ 'supports' => [ 'title' ] ] );
		$this->set_global_post( 'pt_no_editor' );

		$this->make_asset()->enqueue_admin_assets( 'post.php' );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * No assets are enqueued when no enabled taxonomy targets the post type.
	 *
	 * @return void
	 */
	public function test_enqueue_skips_without_relevant_taxonomy(): void {
		register_post_type( 'pt_with_editor', [ 'supports' => [ 'editor' ] ] );
		$this->set_global_post( 'pt_with_editor' );

		$this->make_asset()->enqueue_admin_assets( 'post.php' );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * Assets and localized data are enqueued for a relevant taxonomy.
	 *
	 * @return void
	 */
	public function test_enqueue_loads_assets_for_relevant_taxonomy(): void {
		$this->set_global_post( 'post' );

		$this->make_asset()->enqueue_admin_assets( 'post.php' );

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );

		$data = wp_scripts()->get_data( self::HANDLE, 'data' );
		$this->assertIsString( $data );
		$this->assertStringContainsString( 'wpPrimaryTerm', $data );
		$this->assertStringContainsString( '_primary_%s', $data );
	}

	/**
	 * Assets still enqueue when the build asset file is missing.
	 *
	 * The version falls back to the script file's modified time and the dependencies
	 * to the hardcoded defaults, so the plugin keeps working without the asset file.
	 *
	 * @return void
	 */
	public function test_enqueue_works_without_asset_file(): void {
		$this->set_global_post( 'post' );

		$asset = $this->make_asset_with_paths(
			[ 'get_asset_file' => '/does/not/exist/wp-primary-term.asset.php' ]
		);
		$asset->enqueue_admin_assets( 'post.php' );

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * Nothing is enqueued and no error occurs when the build script file is missing.
	 *
	 * @return void
	 */
	public function test_enqueue_skips_without_script_file(): void {
		$this->set_global_post( 'post' );

		$asset = $this->make_asset_with_paths(
			[ 'get_script_file' => '/does/not/exist/wp-primary-term.js' ]
		);
		$asset->enqueue_admin_assets( 'post.php' );

		$this->assertFalse( wp_script_is( self::HANDLE, 'enqueued' ) );
	}

	/**
	 * A corrupt (unparseable) asset file is caught and the script still enqueues using fallbacks.
	 *
	 * @return void
	 */
	public function test_enqueue_survives_corrupt_asset_file(): void {
		$this->set_global_post( 'post' );

		$asset = $this->make_asset_with_paths(
			[ 'get_asset_file' => dirname( __DIR__ ) . '/fixtures/corrupt-asset.php' ]
		);
		$asset->enqueue_admin_assets( 'post.php' );

		$this->assertTrue( wp_script_is( self::HANDLE, 'enqueued' ) );
	}
}
