<?php
/**
 * Template: Review List (initial server-side placeholder)
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file; variables are local to the included scope.
$product_id  = $args['product_id'] ?? 0;
$show_avatar = ! empty( $args['show_avatar'] );
$show_images = ! empty( $args['show_images'] );

if ( ! $product_id ) {
	return;
}

$repository = new \BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewRepository();
$per_page   = ! empty( $args['per_page'] ) ? absint( $args['per_page'] ) : beplus_advanced_reviews_for_woocommerce_get_load_more_count();
$result     = $repository->get_reviews( $product_id, array( 'per_page' => $per_page ) );
$reviews    = $result['reviews'] ?? array();

if ( empty( $reviews ) ) {
	echo '<p class="beplus-advanced-reviews-for-woocommerce__no-reviews">' . esc_html__( 'No reviews yet.', 'beplus-advanced-reviews-for-woocommerce' ) . '</p>';
	return;
}

$media_handler = new \BeplusAdvancedReviewsForWoocommerce\Media\MediaHandler( new \BeplusAdvancedReviewsForWoocommerce\Core\Container() );
$formatter     = new \BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewFormatter( $media_handler );
$formatted     = $formatter->format_list( $reviews );

foreach ( $formatted as $review ) {
	beplus_advanced_reviews_for_woocommerce_get_template( 'review-card.php', array(
		'review'      => $review,
		'show_avatar' => $show_avatar,
		'show_images' => $show_images,
	) );
}
