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
use BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewVoteRepository;
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
			'/' . $this->rest_base . '/(?P<id>\d+)/helpful',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'set_helpful_vote' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'voted' => array(
						'type'     => 'boolean',
						'required' => true,
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
			'verified_only'    => $request->get_param( 'verified_only' ),
			'sort'             => $request->get_param( 'sort' ),
			'exclude'          => $request->get_param( 'exclude' ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Used in custom SQL for load-more deduplication with a small bounded list.
			'rating_threshold' => $rating_threshold,
		);

		$args            = $this->review_query->build_args( $params );
		$include_helpful = ReviewVoteRepository::is_available_for_product( $product_id );
		if ( ! $include_helpful && 'helpful' === ( $args['sort'] ?? '' ) ) {
			return new \WP_Error(
				'bparfw_helpful_unavailable',
				__( 'Helpful votes are not available for this product.', 'beplus-advanced-reviews-for-woocommerce' ),
				array( 'status' => 404 )
			);
		}
		$result  = $this->repository->get_reviews( $product_id, $args );
		$reviews = $this->formatter->format_list( $result['reviews'], get_current_user_id(), $include_helpful );
		$reviews = apply_filters( HookManager::REVIEW_RESULTS, $reviews, $product_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.review_results'.

		$response = array(
			'reviews'      => $reviews,
			'total'        => $result['total'],
			'pages'        => $result['pages'],
			'page'         => $result['page'],
			'has_more'     => $result['page'] < $result['pages'],
		);

		$response = rest_ensure_response( $response );
			$response->header( 'Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0' );
			$response->header( 'Pragma', 'no-cache' );
			$response->header( 'Expires', 'Thu, 01 Jan 1970 00:00:00 GMT' );

			return $response;
		}

	/**
	 * Set the authenticated viewer's helpful vote for an approved product review.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function set_helpful_vote( \WP_REST_Request $request ) {
		$url_params = $request->get_url_params();
		$comment_id = absint( $url_params['id'] ?? 0 );
		$comment    = $comment_id ? get_comment( $comment_id ) : null;
		if (
			! $comment || 'review' !== $comment->comment_type || '1' !== $comment->comment_approved
			|| ! ReviewVoteRepository::is_available_for_product( (int) $comment->comment_post_ID )
		) {
			return new \WP_Error(
				'bparfw_review_not_found',
				__( 'Review not found.', 'beplus-advanced-reviews-for-woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$viewer_id = get_current_user_id();
		$votes     = new ReviewVoteRepository();
		// WordPress validates and sanitizes the route's boolean argument before dispatch.
		$saved     = $votes->set_vote( $comment_id, $viewer_id, true === $request->get_param( 'voted' ) );
		$summaries = $saved ? $votes->get_summaries( array( $comment_id ), $viewer_id ) : null;
		if ( null === $summaries ) {
			return new \WP_Error(
				'bparfw_vote_failed',
				__( 'Could not save your vote. Try again.', 'beplus-advanced-reviews-for-woocommerce' ),
				array( 'status' => 500 )
			);
		}

		$response = rest_ensure_response( $summaries[ $comment_id ] ?? array(
			'helpful_count'    => 0,
			'viewer_has_voted' => false,
		) );
		$response->header( 'Cache-Control', 'private, no-store' );
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

			$files          = $this->media_handler->normalize_request_files( $request->get_file_params() );
			$paste_image    = $request->get_param( 'paste_image' );
			$paste_image    = is_string( $paste_image ) ? $paste_image : '';
			$paste_position = null;
			if ( $request->has_param( 'paste_position' ) ) {
				$position = $request->get_param( 'paste_position' );
				$is_integer = is_int( $position ) || ( is_string( $position ) && ctype_digit( $position ) );
				if ( '' === $paste_image || ! $is_integer || (int) $position < 0 || (int) $position > count( $files ) ) {
					ob_end_clean();
					return new \WP_Error(
						'invalid_paste_position',
						__( 'The pasted image position is invalid.', 'beplus-advanced-reviews-for-woocommerce' ),
						array( 'status' => 400 )
					);
				}
				$paste_position = (int) $position;
			}

			$comment_id = $this->submission->create_review( $params['product_id'], $params );
			if ( is_wp_error( $comment_id ) ) {
				ob_end_clean();
				return $comment_id;
			}

			$uploaded     = $this->media_handler->upload_review_media( $comment_id, $files, $paste_image, $paste_position );
			$failed_count = count( $files ) + ( '' !== $paste_image ? 1 : 0 ) - count( $uploaded );

			do_action( HookManager::REVIEW_SUBMITTED, $comment_id, $params['product_id'] ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.review_submitted'.

			$review = $this->repository->get_review_by_id( $comment_id );
			$data   = $review ? $this->formatter->format( $review ) : null;

			ob_end_clean();

			$response = array(
				'success' => true,
				'message' => __( 'Review submitted successfully!', 'beplus-advanced-reviews-for-woocommerce' ),
				'review'  => $data,
			);
			if ( $failed_count > 0 ) {
				$response['media_failed_count'] = $failed_count;
				$response['message']            = __( 'Your review was saved, but some attachments could not be uploaded.', 'beplus-advanced-reviews-for-woocommerce' );
			}
			return rest_ensure_response( $response );
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
			'verified_only' => array(
				'description'       => __( 'Show only reviews from verified purchasers.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'boolean',
				'default'           => false,
			),
			'sort'       => array(
				'description'       => __( 'Sort order: newest, oldest, highest, lowest, helpful.', 'beplus-advanced-reviews-for-woocommerce' ),
				'type'              => 'string',
				'default'           => 'newest',
				'enum'              => array( 'newest', 'oldest', 'highest', 'lowest', 'helpful' ),
			),
		);
	}
}
