<?php
/**
 * Plugin — main bootstrap, activate, and deactivate.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Core
 */

namespace BeplusAdvancedReviewsForWoocommerce\Core;

use BeplusAdvancedReviewsForWoocommerce\Settings\SettingsRegistry;
use BeplusAdvancedReviewsForWoocommerce\DB\SchemaManager;
use BeplusAdvancedReviewsForWoocommerce\Blocks\BlockRegistry;
use BeplusAdvancedReviewsForWoocommerce\REST\ReviewController;
use BeplusAdvancedReviewsForWoocommerce\REST\SettingsController;
use BeplusAdvancedReviewsForWoocommerce\Media\MediaHandler;
use BeplusAdvancedReviewsForWoocommerce\Media\LocalMediaStorage;
use BeplusAdvancedReviewsForWoocommerce\Media\MediaStorageInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	private Container $container;

	public function __construct() {
		$this->container = new Container();
	}

	public function boot(): void {
		$this->register_core_services();
		$this->register_services_from_filter();
		$this->boot_registered_modules();

		add_action( 'rest_api_init', array( $this, 'init_rest_controllers' ) );
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
	}

	private function register_core_services(): void {
		$this->container->set( SettingsRegistry::class, function ( Container $c ) {
			return new SettingsRegistry( $c );
		} );

		$this->container->set( SchemaManager::class, function ( Container $c ) {
			return new SchemaManager( $c );
		} );

		$this->container->set( BlockRegistry::class, function ( Container $c ) {
			return new BlockRegistry( $c );
		} );

		$this->container->set( AssetLoader::class, function ( Container $c ) {
			return new AssetLoader( $c );
		} );

		$this->container->set( Placement::class, function ( Container $c ) {
			return new Placement( $c );
		} );

		$this->container->set( MediaHandler::class, function ( Container $c ) {
			return new MediaHandler( $c );
		} );

		$this->container->set( MediaStorageInterface::class, function ( Container $c ) {
			return new LocalMediaStorage();
		} );
	}

	private function register_services_from_filter(): void {
		$services = apply_filters( HookManager::SERVICES, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.services'.
		$this->container->register( $services );
	}

	private function boot_registered_modules(): void {
		$modules = array(
			SettingsRegistry::class,
			SchemaManager::class,
			BlockRegistry::class,
			AssetLoader::class,
			Placement::class,
			MediaHandler::class,
		);

		foreach ( $modules as $module_class ) {
			$module = $this->container->get( $module_class );
			$module->register();
		}
	}

	public function init_rest_controllers(): void {
		$review_controller = new ReviewController();
		$review_controller->register_routes();

		$settings_controller = new SettingsController();
		$settings_controller->register_routes();
	}

	/**
	 * @param array<int, array<string, mixed>> $categories
	 * @return array<int, array<string, mixed>>
	 */
	public function register_block_category( array $categories ): array {
		$categories[] = array(
			'slug'  => 'beplus-advanced-reviews-for-woocommerce',
			'title' => __( 'Beplus Advanced Reviews For Woocommerce', 'beplus-advanced-reviews-for-woocommerce' ),
		);

		return $categories;
	}


	public function activate(): void {
		$schema = new SchemaManager( $this->container );
		$schema->create_tables();
		flush_rewrite_rules();
	}

	public function deactivate(): void {
		flush_rewrite_rules();
	}
}
