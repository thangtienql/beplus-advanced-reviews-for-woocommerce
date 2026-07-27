<?php
/**
 * VerifiedPurchaseModule — hooks for cache busting on order status changes.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Verified
 */

namespace BeplusAdvancedReviewsForWoocommerce\Verified;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VerifiedPurchaseModule extends AbstractModule {

	private VerifiedPurchaseChecker $checker;

	public function __construct( \BeplusAdvancedReviewsForWoocommerce\Core\Container $container ) {
		parent::__construct( $container );
		$this->checker = new VerifiedPurchaseChecker();
	}

	public function register(): void {
		add_action( 'woocommerce_order_status_completed', array( $this, 'bust_cache_for_order' ) );
		add_action( 'woocommerce_order_status_processing', array( $this, 'bust_cache_for_order' ) );
	}

	/**
	 * Bust verified cache for all products in an order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function bust_cache_for_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			if ( ! ( $item instanceof \WC_Order_Item_Product ) ) {
				continue;
			}
			$product_id = $item->get_product_id();
			if ( $product_id ) {
				$this->checker->bust_cache_for_product( $product_id );
			}
		}
	}
}
