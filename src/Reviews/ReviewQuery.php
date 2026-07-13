<?php
/**
 * ReviewQuery — builds and modifies review queries.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Reviews
 */

namespace BeplusAdvancedReviewsForWoocommerce\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewQuery {

	/**
	 * Build query args for the repository.
	 *
	 * @param array<string, mixed> $params Raw request parameters.
	 * @return array<string, mixed>
	 */
	public function build_args( array $params ): array {
		$args = array();

		if ( isset( $params['page'] ) ) {
			$args['page'] = absint( $params['page'] );
		}

		if ( isset( $params['per_page'] ) ) {
			$args['per_page'] = absint( $params['per_page'] );
		}

		if ( isset( $params['rating'] ) ) {
			$args['rating'] = absint( $params['rating'] );
		}

		if ( isset( $params['has_images'] ) ) {
			$args['has_images'] = filter_var( $params['has_images'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( ! empty( $params['exclude'] ) ) {
			$exclude = array_map( 'absint', explode( ',', $params['exclude'] ) );
			$exclude = array_filter( $exclude );
			if ( ! empty( $exclude ) ) {
				$args['exclude'] = $exclude; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Custom SQL NOT IN for load-more deduplication; bounded list.
			}
		}

		if ( isset( $params['rating_threshold'] ) ) {
			$args['rating_threshold'] = absint( $params['rating_threshold'] );
		}

		if ( isset( $params['sort'] ) ) {
			$allowed_sorts = array( 'newest', 'oldest', 'highest', 'lowest' );
			$sort          = sanitize_text_field( $params['sort'] );
			if ( in_array( $sort, $allowed_sorts, true ) ) {
				$args['sort'] = $sort;
			}
		}

		$args = apply_filters( \BeplusAdvancedReviewsForWoocommerce\Core\HookManager::REVIEW_QUERY, $args, $params ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.review_query'.

		return $args;
	}
}
