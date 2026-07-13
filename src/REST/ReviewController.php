<?php
/**
 * ReviewController — REST API for review listing, submission, and distribution.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage REST
 */

namespace BeplusAdvancedReviewsForWoocommerce\REST;

use BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewRepository;
use BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewFormatter;
use BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewQuery;
use BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewSubmission;
use BeplusAdvancedReviewsForWoocommerce\Media\MediaHandler;
use BeplusAdvancedReviewsForWoocommerce\Core\HookManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ReviewController extends \WP_REST_Controller {

	private ReviewRepository $repository;
	private ReviewFormatter $formatter;
	private ReviewQuery $review_query;
	private ReviewSubmission $submission;
	private MediaHandler $media_handler;

	public function __construct() {
		$this->namespace  = 'beplus-advanced-reviews-for-woocommerce/v1';
		$this->rest_base  = 'reviews';

		$this->media_handler = new MediaHandler( new \BeplusAdvancedReviewsForWoocommerce\Core\Container() );
		$this->repository    = new ReviewRepository();
		$this->formatter     = new ReviewFormatter( $this->media_handler );
		$this->review_query  = new ReviewQuery();
		$this->submission    = new ReviewSubmission();
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => '__return_true',
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'can_create_item' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'can_manage_reviews' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return absint( $param ) > 0;
							},
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/distribution',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_star_distribution' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'product_id' => array(
							'required'          => true,
							'validate_callback' => function ( $param ) {
								return absint( $param ) > 0;
							},
						),
					),
				),
			)
		);
	}

	/**
	 * Get paginated reviews.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( $product_id < 1 ) {
			return new \WP_Error(
				'missing_product_id',
				__( 'Product ID is required.', 'beplus-advanced-reviews-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$settings = beplus_advanced_reviews_for_woocommerce_get_settings();
		$rating_threshold = absint( $settings['rating_threshold'] ?? 0 );

		$params = array(
			'product_id'       => $product_id,
			'page'             => $request->get_param( 'page' ),
			'per_page'         => $request->get_param( 'per_page' ),
			'rating'           => $request->get_param( 'rating' ),
			'has_images'       => $request->get_param( 'has_images' ),
			'sort'             => $request->get_param( 'sort' ),
			'exclude'          => $request->get_param( 'exclude' ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Used in custom SQL for load-more deduplication with a small bounded list.
			'rating_threshold' => $rating_threshold,
		);

		$args    = $this->review_query->build_args( $params );
		$result  = $this->repository->get_reviews( $product_id, $args );

		$reviews = $this->formatter->format_list( $result['reviews'] );
		$reviews = apply_filters( HookManager::REVIEW_RESULTS, $reviews, $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.review_results'.

		$response = array(
			'reviews'      => $reviews,
			'total'        => $result['total'],
			'pages'        => $result['pages'],
			'page'         => $result['page'],
			'has_more'     => $result['page'] < $result['pages'],
		);

		$response = rest_ensure_response( $response );
			$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'Pragma', 'no-cache' );
			$response->header( 'Expires', 'Thu, 01 Jan 1970 00:00:00 GMT' );

			return $response;
		}

	/**
	 * Create a new review.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		try {
			ob_start();
			
			$params = array(
				'product_id' => absint( $request->get_param( 'product_id' ) ),
				'rating'     => absint( $request->get_param( 'rating' ) ),
				'content'    => sanitize_textarea_field( $request->get_param( 'content' ) ),
				'author'     => sanitize_text_field( $request->get_param( 'author' ) ),
				'email'      => sanitize_email( $request->get_param( 'email' ) ),
			);

			$valid = $this->submission->validate_submission( $params );
			if ( is_wp_error( $valid ) ) {
				ob_end_clean();
				return $valid;
			}

			$comment_id = $this->submission->create_review( $params['product_id'], $params );
			if ( is_wp_error( $comment_id ) ) {
				ob_end_clean();
				return $comment_id;
			}

		$file_params = $request->get_file_params();
		$base64_data = $request->get_param( 'paste_image' );

		if ( ! empty( $file_params ) ) {
			foreach ( $file_params as $input_name => $file_data ) {
				if ( ! empty( $file_data['name'] ) ) {
					$this->media_handler->upload_files( $comment_id, $file_data );
				}
			}
		}

		if ( ! empty( $base64_data ) ) {
			$this->media_handler->upload_pasted_image( $comment_id, $base64_data );
		}

			do_action( HookManager::REVIEW_SUBMITTED, $comment_id, $params['product_id'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.review_submitted'.

			$review = $this->repository->get_review_by_id( $comment_id );
			$data   = $review ? $this->formatter->format( $review ) : null;

			ob_end_clean();
			
			return rest_ensure_response( array(
				'success'   => true,
				'message'   => __( 'Review submitted successfully!', 'beplus-advanced-reviews-for-woocommerce' ),
				'review'    => $data,
			) );
		} catch ( \Throwable $e ) {
			ob_end_clean();
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), array( 'source' => 'beplus-advanced-reviews-for-woocommerce' ) );
			}
			return new \WP_Error( 'internal_error', $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Delete a review.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$comment_id = absint( $request->get_param( 'id' ) );

		$this->media_handler->delete_media_for_comment( $comment_id );

		$deleted = wp_delete_comment( $comment_id, true );

		if ( ! $deleted ) {
			return new \WP_Error(
				'delete_failed',
				__( 'Failed to delete review.', 'beplus-advanced-reviews-for-woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Review deleted.', 'beplus-advanced-reviews-for-woocommerce' ),
		) );
	}

	/**
	 * Get star distribution for a product.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_star_distribution( $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		$data       = $this->repository->get_star_distribution( $product_id );

		$response = rest_ensure_response( $data );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', 'Thu, 01 Jan 1970 00:00:00 GMT' );

		return $response;
	}

	/**
	 * Permission callback for creating reviews.
	 *
	 * @return bool
	 */
	public function can_create_item(): bool {
		if ( is_user_logged_in() ) {
			return true;
		}

		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) )
			: '';

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Permission callback for managing reviews.
	 *
	 * @return bool
	 */
	public function can_manage_reviews(): bool {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Get collection params for the reviews endpoint.
	 *
	 * @return array<string, mixed>
	 */
	public function get_collection_params(): array {
		return array(
			'product_id' => array(
				'description'       => __( 'Product ID.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
			'page'       => array(
				'description'       => __( 'Page number.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page'   => array(
				'description'       => __( 'Items per page.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'integer',
				'default'           => 10,
				'maximum'           => 50,
				'sanitize_callback' => 'absint',
			),
			'rating'     => array(
				'description'       => __( 'Filter by star rating (1-5).', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'has_images' => array(
				'description'       => __( 'Show only reviews with images.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'boolean',
				'default'           => false,
			),
			'sort'       => array(
				'description'       => __( 'Sort order: newest, oldest, highest, lowest.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'string',
				'default'           => 'newest',
				'enum'              => array( 'newest', 'oldest', 'highest', 'lowest' ),
			),
		);
	}
}
