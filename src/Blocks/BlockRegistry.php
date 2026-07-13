<?php
/**
 * BlockRegistry — auto-discovers and registers Gutenberg blocks under blocks/.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Blocks
 */

namespace BeplusAdvancedReviewsForWoocommerce\Blocks;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;
use BeplusAdvancedReviewsForWoocommerce\Core\HookManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BlockRegistry extends AbstractModule {

	public function register(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Scan blocks/ directory and register each block via block.json metadata.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
			return;
		}

		$blocks_dir = $this->plugin_dir . 'blocks/';
		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		$dirs = glob( $blocks_dir . '*', GLOB_ONLYDIR );
		if ( ! is_array( $dirs ) ) {
			return;
		}

		foreach ( $dirs as $dir ) {
			$block_json = $dir . '/block.json';
			if ( file_exists( $block_json ) ) {
				register_block_type_from_metadata( $dir );
			}
		}

		$extra_blocks = apply_filters( HookManager::BLOCKS, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.blocks'.
		foreach ( $extra_blocks as $block_dir ) {
			if ( file_exists( $block_dir . '/block.json' ) ) {
				register_block_type_from_metadata( $block_dir );
			}
		}
	}
}
