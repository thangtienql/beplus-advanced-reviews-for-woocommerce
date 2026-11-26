<?php
/**
 * Store and read helpful votes for product reviews.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

namespace BeplusAdvancedReviewsForWoocommerce\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewVoteRepository {

	public static function is_available_for_product( int $product_id ): bool {
		return 'product' === get_post_type( $product_id )
			&& 'publish' === get_post_status( $product_id )
			&& ! post_password_required( $product_id );
	}

	public function set_vote( int $comment_id, int $user_id, bool $voted ): bool {
		global $wpdb;

		$table = $wpdb->prefix . 'bparfw_review_votes';
		if ( ! $voted ) {
			return false !== $wpdb->delete( $table, array( 'comment_id' => $comment_id, 'user_id' => $user_id ), array( '%d', '%d' ) );
		}

		return false !== $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (comment_id, user_id) VALUES (%d, %d)
				ON DUPLICATE KEY UPDATE comment_id = comment_id",
				$comment_id,
				$user_id
			)
		);
	}

	/**
	 * Fetch totals and the viewer's vote in one query. Missing IDs have no votes.
	 *
	 * @param int[] $comment_ids Review IDs.
	 * @param int   $viewer_id   Viewer ID, or zero for public data only.
	 * @return array<int, array{helpful_count: int, viewer_has_voted: bool}>|null Null on a database error.
	 */
	public function get_summaries( array $comment_ids, int $viewer_id = 0 ): ?array {
		global $wpdb;

		if ( empty( $comment_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $comment_ids ), '%d' ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_id, COUNT(*) AS helpful_count, MAX(user_id = %d) AS viewer_has_voted
				FROM {$wpdb->prefix}bparfw_review_votes
				WHERE comment_id IN ({$placeholders})
				GROUP BY comment_id",
				array_merge( array( $viewer_id ), $comment_ids )
			)
		);
		if ( '' !== $wpdb->last_error ) {
			return null;
		}

		$summaries = array();
		foreach ( $rows ?? array() as $row ) {
			$summaries[ (int) $row->comment_id ] = array(
				'helpful_count'    => (int) $row->helpful_count,
				'viewer_has_voted' => (bool) $row->viewer_has_voted,
			);
		}
		return $summaries;
	}

	public function delete_for_comment( int $comment_id ): void {
		global $wpdb;

		$wpdb->delete( $wpdb->prefix . 'bparfw_review_votes', array( 'comment_id' => $comment_id ), array( '%d' ) );
	}
}
