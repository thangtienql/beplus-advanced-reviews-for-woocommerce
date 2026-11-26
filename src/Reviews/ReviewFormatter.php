<?php
/**
 * ReviewFormatter — shapes review data for API responses.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Reviews
 */

namespace BeplusAdvancedReviewsForWoocommerce\Reviews;

use BeplusAdvancedReviewsForWoocommerce\Media\MediaHandler;
use BeplusAdvancedReviewsForWoocommerce\Verified\VerifiedPurchaseChecker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewFormatter {

	private MediaHandler $media_handler;
	private VerifiedPurchaseChecker $verified_checker;

	public function __construct( MediaHandler $media_handler, ?VerifiedPurchaseChecker $verified_checker = null ) {
		$this->media_handler    = $media_handler;
		$this->verified_checker = $verified_checker ?? new VerifiedPurchaseChecker();
	}

	/**
	 * Format a single review row into the API shape.
	 *
	 * @param object              $review       Database row.
	 * @param array<string, bool> $verified_map Optional map of email => is_verified.
	 * @return array<string, mixed>
	 */
	public function format( object $review, array $verified_map = array() ): array {
		$comment_id = (int) $review->comment_ID;
		$rating     = isset( $review->rating_value ) ? (int) $review->rating_value : 0;
		$images     = $this->media_handler->get_review_media( $comment_id );
		$email      = $review->comment_author_email ?? '';

		$is_verified = false;
		if ( array_key_exists( $email, $verified_map ) ) {
			$is_verified = $verified_map[ $email ];
		}

		return array(
			'id'          => $comment_id,
			'product_id'  => (int) $review->comment_post_ID,
			'rating'      => $rating,
			'author'      => $review->comment_author ?? '',
			'content'     => wp_kses_post( $review->comment_content ?? '' ),
			'avatar'      => get_avatar_url( $review->comment_author_email ?? '', array( 'size' => 64 ) ),
			'has_images'  => ! empty( $images ),
			'images'      => $images,
			'is_verified' => $is_verified,
			'created_at'  => $review->comment_date ?? '',
			'date_human'  => sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'beplus-advanced-reviews-for-woocommerce' ),
				human_time_diff( strtotime( $review->comment_date ?? '' ) )
			),
		);
	}

	/**
	 * Format a list of reviews with batch verified check.
	 *
	 * @param array<int, object> $reviews   Array of database rows.
	 * @param int                $viewer_id       Authenticated viewer, or zero for public output.
	 * @param bool               $include_helpful Whether the product allows helpful votes.
	 * @return array<int, array<string, mixed>>
	 */
	public function format_list( array $reviews, int $viewer_id = 0, bool $include_helpful = true ): array {
		if ( empty( $reviews ) ) {
			return array();
		}

		// Batch check verified status for all reviewer emails at once.
		$emails     = array();
		$product_id = 0;
		foreach ( $reviews as $review ) {
			$email = $review->comment_author_email ?? '';
			if ( $email ) {
				$emails[] = $email;
			}
			if ( ! $product_id && ! empty( $review->comment_post_ID ) ) {
				$product_id = (int) $review->comment_post_ID;
			}
		}
		$emails = array_unique( $emails );

		$verified_map = $this->verified_checker->check( $product_id, $emails );
		$review_ids   = array_map( static function ( object $review ): int {
			return (int) $review->comment_ID;
		}, $reviews );
		$votes = $include_helpful ? ( new ReviewVoteRepository() )->get_summaries( $review_ids, $viewer_id ) : null;

		$formatted = array();
		foreach ( $reviews as $review ) {
			$item    = $this->format( $review, $verified_map );
			if ( null !== $votes ) {
				$summary = $votes[ $item['id'] ] ?? array();
				$item['helpful_count'] = $summary['helpful_count'] ?? 0;
				if ( $viewer_id > 0 ) {
					$item['viewer_has_voted'] = $summary['viewer_has_voted'] ?? false;
				}
			}
			$formatted[] = $item;
		}

		return $formatted;
	}
}
