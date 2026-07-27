<?php
/**
 * ReviewSubmission — handles review creation and validation.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Reviews
 */

namespace BeplusAdvancedReviewsForWoocommerce\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewSubmission {

	/**
	 * Create a new review.
	 *
	 * @param int   $product_id Product ID.
	 * @param array<string, mixed> $data       Review data (rating, content, author info).
	 * @return int|\WP_Error Comment ID on success, \WP_Error on failure.
	 */
	public function create_review( int $product_id, array $data ) {
		$current_user = wp_get_current_user();
		$user_id      = $current_user->exists() ? $current_user->ID : 0;

		$rating = isset( $data['rating'] ) ? absint( $data['rating'] ) : 0;
		if ( $rating < 1 || $rating > 5 ) {
			return new \WP_Error(
				'invalid_rating',
				__( 'Rating must be between 1 and 5.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		$content = isset( $data['content'] ) ? sanitize_textarea_field( $data['content'] ) : '';
		if ( empty( trim( $content ) ) ) {
			return new \WP_Error(
				'empty_content',
				__( 'Review content cannot be empty.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		$comment_data = array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => $user_id ? $current_user->display_name : sanitize_text_field( $data['author'] ?? '' ),
			'comment_author_email' => $user_id ? $current_user->user_email : sanitize_email( $data['email'] ?? '' ),
			'comment_content'      => $content,
			'comment_type'         => 'review',
			'user_id'              => $user_id,
		);

		// wp_new_comment automatically handles moderation (wp_allow_comment),
		// email notifications, and expects slashed data.
		$comment_id = wp_new_comment( wp_slash( $comment_data ), true );

		if ( is_wp_error( $comment_id ) ) {
			return $comment_id;
		}

		if ( ! $comment_id ) {
			return new \WP_Error(
				'insert_failed',
				__( 'Failed to save review.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		update_comment_meta( $comment_id, 'rating', $rating );

		return $comment_id;
	}

	/**
	 * Validate review submission data.
	 *
	 * @param array<string, mixed> $data Raw input data.
	 * @return true|\WP_Error
	 */
	public function validate_submission( array $data ) {
		if ( ! isset( $data['rating'] ) || ! is_numeric( $data['rating'] ) ) {
			return new \WP_Error(
				'missing_rating',
				__( 'Please select a rating.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		$rating = absint( $data['rating'] );
		if ( $rating < 1 || $rating > 5 ) {
			return new \WP_Error(
				'invalid_rating',
				__( 'Rating must be between 1 and 5.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		if ( ! isset( $data['content'] ) || empty( trim( $data['content'] ) ) ) {
			return new \WP_Error(
				'missing_content',
				__( 'Please write a review.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		if ( ! isset( $data['product_id'] ) || absint( $data['product_id'] ) < 1 ) {
			return new \WP_Error(
				'missing_product',
				__( 'Invalid product.', 'beplus-advanced-reviews-for-woocommerce' )
			);
		}

		// Email validation: required for guest users; logged-in users get email from profile.
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			$email = isset( $data['email'] ) ? trim( $data['email'] ) : '';
			if ( empty( $email ) ) {
				return new \WP_Error(
					'missing_email',
					__( 'Please provide your email address.', 'beplus-advanced-reviews-for-woocommerce' )
				);
			}
			if ( ! is_email( $email ) ) {
				return new \WP_Error(
					'invalid_email',
					__( 'Please provide a valid email address.', 'beplus-advanced-reviews-for-woocommerce' )
				);
			}
		}

		return true;
	}
}
