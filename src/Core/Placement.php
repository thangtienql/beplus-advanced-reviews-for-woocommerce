<?php
/**
 * Placement — display mode logic (keep/replace).
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Core
 */

namespace BeplusAdvancedReviewsForWoocommerce\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Placement extends AbstractModule {

	private const BLOCK_NAME = 'beplus-advanced-reviews/advanced-review';

	private static bool $rendered = false;

	public function register(): void {
		$mode = beplus_advanced_reviews_for_woocommerce_get_display_mode();

		if ( 'replace' === $mode ) {
			add_filter( 'woocommerce_product_tabs', array( $this, 'replace_reviews_tab' ), 98 );
		}
	}

	/**
	 * Mark that the block has been rendered elsewhere on the page.
	 */
	public static function mark_rendered(): void {
		self::$rendered = true;
	}

	/**
	 * Replace the default WooCommerce reviews tab with Advanced Reviews.
	 *
	 * @param array<string, mixed> $tabs
	 * @return array<string, mixed>
	 */
	public function replace_reviews_tab( array $tabs ): array {
		unset( $tabs['reviews'] );

		if ( $this->is_block_already_present() ) {
			return $tabs;
		}

		$tabs['advanced_reviews'] = array(
			'title'    => esc_html__( 'Reviews', 'beplus-advanced-reviews-for-woocommerce' ),
			'priority' => 30,
			'callback' => array( $this, 'render_block' ),
		);

		return $tabs;
	}

	/**
	 * Render the block output.
	 */
	public function render_block(): void {
		if ( $this->is_block_already_present() ) {
			return;
		}

		self::$rendered = true;

		$block_instance = beplus_advanced_reviews_for_woocommerce_get_block_instance();
		if ( $block_instance ) {
			echo $block_instance; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Check if the Advanced Reviews block is already present on the page.
	 *
	 * Checks already-rendered instances (static flag), raw post content, and the
	 * active Single Product block template (FSE / Site Editor).
	 */
	private function is_block_already_present(): bool {
		if ( self::$rendered ) {
			return true;
		}

		$post = get_post();
		if ( $post instanceof \WP_Post && has_block( self::BLOCK_NAME, $post->post_content ) ) {
			return true;
		}

		if ( function_exists( 'get_block_template' ) ) {
			$template = get_block_template( get_stylesheet() . '//single-product' );
			if ( ! $template ) {
				$template = get_block_template( get_stylesheet() . '//single' );
			}
			if ( $template && has_block( self::BLOCK_NAME, $template->content ) ) {
				return true;
			}
		}

		return false;
	}
}
