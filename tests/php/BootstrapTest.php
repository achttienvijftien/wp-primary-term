<?php
/**
 * Tests for the Bootstrap class.
 *
 * @package AchttienVijftien\Plugin\WPPrimaryTerm
 * @author  1815 - Dario Bunschoten <dario@1815.nl>
 */

namespace AchttienVijftien\Plugin\WPPrimaryTerm\Tests;

use AchttienVijftien\Plugin\WPPrimaryTerm\Asset;
use AchttienVijftien\Plugin\WPPrimaryTerm\Bootstrap;
use AchttienVijftien\Plugin\WPPrimaryTerm\Storage;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use WP_Taxonomy;
use WP_UnitTestCase;

/**
 * Integration tests for AchttienVijftien\Plugin\WPPrimaryTerm\Bootstrap.
 */
class BootstrapTest extends WP_UnitTestCase {

	/**
	 * Remove the taxonomy filter that may have been registered during a test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'achttienvijftien_primary_term_taxonomies' );
		parent::tear_down();
	}

	/**
	 * Create a Bootstrap instance bypassing the private constructor and singleton.
	 *
	 * @return Bootstrap
	 */
	private function new_uninitialized_bootstrap(): Bootstrap {
		return ( new ReflectionClass( Bootstrap::class ) )->newInstanceWithoutConstructor();
	}

	/**
	 * boot() returns a Bootstrap instance.
	 *
	 * @return void
	 */
	public function test_boot_returns_instance(): void {
		$this->assertInstanceOf( Bootstrap::class, Bootstrap::boot() );
	}

	/**
	 * boot() always returns the same singleton instance.
	 *
	 * @return void
	 */
	public function test_boot_returns_singleton(): void {
		$this->assertSame( Bootstrap::boot(), Bootstrap::boot() );
	}

	/**
	 * boot() registers the init handler at priority 15.
	 *
	 * @return void
	 */
	public function test_boot_registers_init_hook(): void {
		$this->assertSame( 15, has_action( 'init', [ Bootstrap::boot(), 'init' ] ) );
	}

	/**
	 * get_enabled_taxonomies removes duplicates and unknown taxonomies.
	 *
	 * @return void
	 */
	public function test_get_enabled_taxonomies_filters_input(): void {
		add_filter(
			'achttienvijftien_primary_term_taxonomies',
			static function () {
				return [ 'category', 'category', 'does_not_exist' ];
			}
		);

		$method = new ReflectionMethod( Bootstrap::class, 'get_enabled_taxonomies' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->new_uninitialized_bootstrap() );

		$this->assertSame( [ 'category' ], array_keys( $result ) );
		$this->assertInstanceOf( WP_Taxonomy::class, $result['category'] );
	}

	/**
	 * init() bails out early when no taxonomies are enabled.
	 *
	 * @return void
	 */
	public function test_init_skips_without_enabled_taxonomies(): void {
		add_filter(
			'achttienvijftien_primary_term_taxonomies',
			static function () {
				return [ 'does_not_exist' ];
			}
		);

		$bootstrap = $this->new_uninitialized_bootstrap();
		$bootstrap->init();

		$storage = new ReflectionProperty( Bootstrap::class, 'storage' );
		$storage->setAccessible( true );

		$this->assertFalse( $storage->isInitialized( $bootstrap ) );
	}

	/**
	 * init() wires up the Asset and Storage handlers for enabled taxonomies.
	 *
	 * @return void
	 */
	public function test_init_registers_handlers_for_enabled_taxonomies(): void {
		add_filter(
			'achttienvijftien_primary_term_taxonomies',
			static function () {
				return [ 'category' ];
			}
		);

		$bootstrap = $this->new_uninitialized_bootstrap();
		$bootstrap->init();

		$reflection = new ReflectionClass( Bootstrap::class );

		$storage = $reflection->getProperty( 'storage' );
		$storage->setAccessible( true );
		$asset = $reflection->getProperty( 'asset' );
		$asset->setAccessible( true );

		$this->assertTrue( $storage->isInitialized( $bootstrap ) );
		$this->assertInstanceOf( Storage::class, $storage->getValue( $bootstrap ) );
		$this->assertTrue( $asset->isInitialized( $bootstrap ) );
		$this->assertInstanceOf( Asset::class, $asset->getValue( $bootstrap ) );

		$this->assertTrue( registered_meta_key_exists( 'post', '_primary_category' ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts' ) );
	}
}
