<?php
/**
 * Clean up helpful votes when a review is permanently deleted.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

namespace BeplusAdvancedReviewsForWoocommerce\Reviews;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewVoteModule extends AbstractModule {

	public function register(): void {
		add_action( 'deleted_comment', array( $this, 'delete_votes' ), 10, 2 );
	}

	public function delete_votes( int $comment_id, \WP_Comment $comment ): void {
		if ( 'review' === $comment->comment_type ) {
			( new ReviewVoteRepository() )->delete_for_comment( $comment_id );
		}
	}
}
