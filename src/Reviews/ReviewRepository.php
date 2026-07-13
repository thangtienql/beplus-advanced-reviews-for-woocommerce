<?php
/**
 * ReviewRepository — data access for WooCommerce product reviews.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Reviews
 */

namespace BeplusAdvancedReviewsForWoocommerce\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewRepository {

	/**
	 * Cache group for all review queries.
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'bparfw_reviews';

	/**
	 * Get reviews for a product.
	 *
	 * @param int   $product_id Product ID.
	 * @param array<string, mixed> $args       Query arguments.
	 * @return array<string, mixed>
	 */
	public function get_reviews( int $product_id, array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'page'             => 1,
			'per_page'         => 10,
			'rating'           => 0,
			'has_images'       => false,
			'sort'             => 'newest',
			'rating_threshold' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$page             = max( 1, absint( $args['page'] ) );
		$per_page         = max( 1, min( 50, absint( $args['per_page'] ) ) );
		$offset           = ( $page - 1 ) * $per_page;
		$rating_threshold = max( 0, min( 5, absint( $args['rating_threshold'] ) ) );

		$where  = "c.comment_type = 'review' AND c.comment_approved = '1' AND c.comment_post_ID = %d";
		$params = array( $product_id );

		if ( $args['rating'] > 0 && $args['rating'] <= 5 ) {
			$where   .= ' AND cm.meta_key = %s AND cm.meta_value = %d';
			$params[] = 'rating';
			$params[] = absint( $args['rating'] );
		}

		if ( $rating_threshold > 0 ) {
			$where   .= ' AND CAST(COALESCE(cm_rating.meta_value, 0) AS UNSIGNED) >= %d';
			$params[] = $rating_threshold;
		}

		if ( $args['has_images'] ) {
			$where .= ' AND EXISTS (SELECT 1 FROM ' . $wpdb->prefix . 'bparfw_review_media rm WHERE rm.comment_id = c.comment_ID)';
		}

		if ( ! empty( $args['exclude'] ) && is_array( $args['exclude'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $args['exclude'] ), '%d' ) );
			$where .= " AND c.comment_ID NOT IN ({$placeholders})";
			$params = array_merge( $params, $args['exclude'] );
		}

		switch ( $args['sort'] ) {
			case 'oldest':
				$orderby = 'c.comment_date ASC';
				break;
			case 'highest':
				$orderby = 'COALESCE(cm2.meta_value, 0) DESC';
				break;
			case 'lowest':
				$orderby = 'COALESCE(cm2.meta_value, 0) ASC';
				break;
			case 'newest':
			default:
				$orderby = 'c.comment_date DESC';
				break;
		}

		$join = '';
		if ( in_array( $args['sort'], array( 'highest', 'lowest' ), true ) ) {
			$join .= " LEFT JOIN {$wpdb->commentmeta} cm2 ON c.comment_ID = cm2.comment_id AND cm2.meta_key = 'rating'";
		}

		if ( $args['rating'] > 0 && $args['rating'] <= 5 ) {
			$join .= " INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id";
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $join, $where, $orderby are safely built above; $params count matches placeholders at runtime.
		$query = $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS c.*, cm_rating.meta_value as rating_value
			FROM {$wpdb->comments} c
			LEFT JOIN {$wpdb->commentmeta} cm_rating ON c.comment_ID = cm_rating.comment_id AND cm_rating.meta_key = 'rating'
			{$join}
			WHERE {$where}
			ORDER BY {$orderby}
			LIMIT %d OFFSET %d",
			array_merge( $params, array( $per_page, $offset ) )
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

		$reviews  = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Paginated listing with dynamic filters; not suitable for object cache. $query is safely prepared.
		$total    = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Must run immediately after SQL_CALC_FOUND_ROWS.
		$pages    = $per_page > 0 ? ceil( $total / $per_page ) : 0;

		return array(
			'reviews' => $reviews ?? array(),
			'total'   => (int) $total,
			'pages'   => (int) $pages,
			'page'    => $page,
		);
	}

	/**
	 * Get star distribution for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array<string, mixed>
	 */
	public function get_star_distribution( int $product_id ): array {
		$cache_key = 'star_dist_' . $product_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT
				COUNT(*) as total,
				AVG(CAST(cm.meta_value AS UNSIGNED)) as average,
				SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) = 5 THEN 1 ELSE 0 END) as stars_5,
				SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) = 4 THEN 1 ELSE 0 END) as stars_4,
				SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) = 3 THEN 1 ELSE 0 END) as stars_3,
				SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) = 2 THEN 1 ELSE 0 END) as stars_2,
				SUM(CASE WHEN CAST(cm.meta_value AS UNSIGNED) = 1 THEN 1 ELSE 0 END) as stars_1
			FROM {$wpdb->comments} c
			INNER JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = 'rating'
			WHERE c.comment_type = 'review'
				AND c.comment_approved = '1'
				AND c.comment_post_ID = %d",
			$product_id
		);

		$row = $wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $row ) {
			$result = array(
				'product_id' => $product_id,
				'total'      => 0,
				'average'    => 0.0,
				'stars'      => array( '5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0 ),
			);
		} else {
			$result = array(
				'product_id' => $product_id,
				'total'      => (int) $row->total,
				'average'    => round( (float) $row->average, 1 ),
				'stars'      => array(
					'5' => (int) $row->stars_5,
					'4' => (int) $row->stars_4,
					'3' => (int) $row->stars_3,
					'2' => (int) $row->stars_2,
					'1' => (int) $row->stars_1,
				),
			);
		}

		wp_cache_set( $cache_key, $result, self::CACHE_GROUP );

		return $result;
	}

	/**
	 * Check if a review has images attached.
	 *
	 * @param int $comment_id Comment ID.
	 * @return bool
	 */
	public function has_images( int $comment_id ): bool {
		$cache_key = 'has_images_' . $comment_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return (bool) $cached;
		}

		global $wpdb;

		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}bparfw_review_media WHERE comment_id = %d",
				$comment_id
			)
		);

		$result = (int) $count > 0;
		wp_cache_set( $cache_key, $result, self::CACHE_GROUP );

		return $result;
	}

	/**
	 * Get a single review by comment ID.
	 *
	 * @param int $comment_id Comment ID.
	 * @return object|null
	 */
	public function get_review_by_id( int $comment_id ) {
		$cache_key = 'review_' . $comment_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$result = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT c.*, cm.meta_value as rating_value
				FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = 'rating'
				WHERE c.comment_ID = %d AND c.comment_type = 'review'",
				$comment_id
			)
		);

		wp_cache_set( $cache_key, $result, self::CACHE_GROUP );

		return $result;
	}
}
