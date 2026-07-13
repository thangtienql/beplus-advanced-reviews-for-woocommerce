<?php
/**
 * HookManager — constants for all plugin hooks and filters.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Core
 */

namespace BeplusAdvancedReviewsForWoocommerce\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HookManager {

	public const SERVICES           = 'beplus_advanced_reviews.services';
	public const BLOCKS             = 'beplus_advanced_reviews.blocks';
	public const REVIEW_QUERY       = 'beplus_advanced_reviews.review_query';
	public const REVIEW_RESULTS     = 'beplus_advanced_reviews.review_results';
	public const REVIEW_SUBMITTED   = 'beplus_advanced_reviews.review_submitted';
	public const MEDIA_UPLOADED     = 'beplus_advanced_reviews.media_uploaded';
	public const MEDIA_DELETED      = 'beplus_advanced_reviews.media_deleted';

	public static function template_paths(): string {
		return 'beplus_advanced_reviews_for_woocommerce_template_paths';
	}
}
