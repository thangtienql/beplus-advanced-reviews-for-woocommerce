<?php
/**
 * VerifiedPurchaseChecker — batch check if reviewers have purchased a product.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Verified
 */

namespace BeplusAdvancedReviewsForWoocommerce\Verified;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VerifiedPurchaseChecker {

	private const CACHE_GROUP = 'bparfw_verified';
	private const CACHE_TTL   = 300; // 5 minutes.

	/**
	 * Check which emails correspond to verified purchasers for a product.
	 *
	 * @param int                $product_id Product ID.
	 * @param array<int, string> $emails     List of reviewer emails.
	 * @return array<string, bool> Map of email => is_verified.
	 */
	public function check( int $product_id, array $emails ): array {
		$emails = array_unique( array_filter( $emails ) );
		if ( empty( $emails ) || $product_id < 1 ) {
			return array();
		}

		$settings         = beplus_advanced_reviews_for_woocommerce_get_settings();
		$verified_enabled = ! empty( $settings['enable_verified'] );

		if ( ! $verified_enabled ) {
			return array_fill_keys( $emails, false );
		}

		$cache_key = 'verified_' . $product_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		$cached    = is_array( $cached ) ? $cached : array();

		// Find emails not in cache.
		$uncached = array_filter(
			$emails,
			function ( $email ) use ( $cached ) {
				return ! array_key_exists( $email, $cached );
			}
		);

		if ( ! empty( $uncached ) ) {
			$queried = $this->query_verified_emails( $product_id, array_values( $uncached ) );

			// Merge: emails from query = true, rest = false.
			foreach ( $uncached as $email ) {
				$cached[ $email ] = in_array( $email, $queried, true );
			}

			wp_cache_set( $cache_key, $cached, self::CACHE_GROUP, self::CACHE_TTL );
		}

		$result = array();
		foreach ( $emails as $email ) {
			$result[ $email ] = $cached[ $email ] ?? false;
		}

		return $result;
	}

	/**
	 * Get all verified emails for a product from the orders table.
	 *
	 * @param int                $product_id Product ID.
	 * @param array<int, string> $emails     Emails to check (empty = all).
	 * @return array<int, string> List of verified emails.
	 */
	public function get_verified_emails( int $product_id, array $emails = array() ): array {
		$emails = array_unique( array_filter( $emails ) );

		$settings = beplus_advanced_reviews_for_woocommerce_get_settings();
		if ( empty( $settings['enable_verified'] ) ) {
			return array();
		}

		return $this->query_verified_emails( $product_id, $emails );
	}

	/**
	 * Bust cache for a specific product.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function bust_cache_for_product( int $product_id ): void {
		wp_cache_delete( 'verified_' . $product_id, self::CACHE_GROUP );
	}

	/**
	 * Query WooCommerce orders for verified emails using wc_get_orders (HPOS-compatible).
	 *
	 * @param int                $product_id Product ID.
	 * @param array<int, string> $emails     Emails to filter by (empty = all).
	 * @return array<int, string>
	 */
	private function query_verified_emails( int $product_id, array $emails ): array {
		$statuses = $this->get_valid_wc_statuses();
		if ( empty( $statuses ) ) {
			return array();
		}

		$args = array(
			'status'       => $statuses,
			'limit'        => -1,
			'return'       => 'ids',
			'type'         => 'shop_order',
		);

		if ( ! empty( $emails ) ) {
			$args['customer'] = $emails;
		}

		$order_ids = wc_get_orders( $args );

		if ( empty( $order_ids ) ) {
			return array();
		}

		// Collect billing emails from orders that contain the product.
		$verified = array();
		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			foreach ( $order->get_items() as $item ) {
				if ( ! ( $item instanceof \WC_Order_Item_Product ) ) {
					continue;
				}
				if ( $item->get_product_id() === $product_id ) {
					$email = $order->get_billing_email();
					if ( ! empty( $email ) && ! in_array( $email, $verified, true ) ) {
						$verified[] = $email;
					}
					break; // Found the product, no need to check other items in this order.
				}
			}
		}

		return $verified;
	}

	/**
	 * Get the valid WooCommerce order statuses based on plugin settings.
	 *
	 * @return array<int, string>
	 */
	private function get_valid_wc_statuses(): array {
		$settings = beplus_advanced_reviews_for_woocommerce_get_settings();
		$option   = $settings['verified_statuses'] ?? 'completed_processing';

		if ( 'completed' === $option ) {
			return array( 'completed' );
		}

		return array( 'completed', 'processing' );
	}
}
