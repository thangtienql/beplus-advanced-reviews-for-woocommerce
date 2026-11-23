<?php
/**
 * AssetLoader — enqueue admin and front-end scripts/styles.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Core
 */

namespace BeplusAdvancedReviewsForWoocommerce\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AssetLoader extends AbstractModule {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_data' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Enqueue bparfwData as a standalone data script so the block viewScript
	 * always has it available, regardless of context (product page or not).
	 */
	public function enqueue_block_data(): void {
		wp_register_script( 'beplus-advanced-reviews-for-woocommerce-data', false, array(), BEPLUS_ADVANCED_REVIEWS_FOR_WOOCOMMERCE_VERSION, false );
		wp_localize_script(
			'beplus-advanced-reviews-for-woocommerce-data',
			'bparfwData',
			$this->get_bparfw_data()
		);
		wp_enqueue_script( 'beplus-advanced-reviews-for-woocommerce-data' );
	}

	/**
	 * Get data for frontend JS.
	 *
	 * @return array<string, mixed>
	 */
	private function get_bparfw_data(): array {
		$settings = beplus_advanced_reviews_for_woocommerce_get_settings();
		return array(
			'restUrl'         => rest_url( 'beplus-advanced-reviews-for-woocommerce/v1/' ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'maxUploadSize'   => beplus_advanced_reviews_for_woocommerce_get_max_image_size(),
			'maxUploadSizeMb' => $settings['max_image_size_mb'] ?? 2,
			'allowedTypes'    => array( 'image/jpeg', 'image/png', 'image/webp' ),
			'pasteEnabled'    => ! empty( $settings['enable_paste'] ) && ! empty( $settings['enable_images'] ),
			'imagesEnabled'   => ! empty( $settings['enable_images'] ),
			'videosEnabled'   => ! empty( $settings['enable_videos'] ),
			'maxVideoSize'    => beplus_advanced_reviews_for_woocommerce_get_max_video_size(),
			'maxVideoSizeMb'  => $settings['max_video_size_mb'] ?? 20,
			'videoTypes'      => array( 'video/mp4', 'video/webm', 'video/ogg' ),
			'i18n'            => array(
				'noReviews'       => __( 'No reviews yet.', 'beplus-advanced-reviews-for-woocommerce' ),
				'loadMore'        => __( 'Load More', 'beplus-advanced-reviews-for-woocommerce' ),
				'updatingReviews' => __( 'Updating reviews…', 'beplus-advanced-reviews-for-woocommerce' ),
				'loadingMoreReviews' => __( 'Loading more reviews…', 'beplus-advanced-reviews-for-woocommerce' ),
				'reviewsLoadError' => __( 'Could not load reviews.', 'beplus-advanced-reviews-for-woocommerce' ),
				'updatingDistribution' => __( 'Updating rating summary…', 'beplus-advanced-reviews-for-woocommerce' ),
				'distributionLoadError' => __( 'Could not update the rating summary.', 'beplus-advanced-reviews-for-woocommerce' ),
				'retryRequest' => __( 'Try again', 'beplus-advanced-reviews-for-woocommerce' ),
				'submittingReview' => __( 'Submitting review…', 'beplus-advanced-reviews-for-woocommerce' ),
				'submitSuccess'   => __( 'Review submitted!', 'beplus-advanced-reviews-for-woocommerce' ),
				'submitError'     => __( 'Something went wrong.', 'beplus-advanced-reviews-for-woocommerce' ),
				'pastedImage'    => __( 'Pasted image', 'beplus-advanced-reviews-for-woocommerce' ),
				/* translators: %s: attachment name. */
				'moveMediaEarlier' => __( 'Move %s earlier', 'beplus-advanced-reviews-for-woocommerce' ),
				/* translators: %s: attachment name. */
				'moveMediaLater' => __( 'Move %s later', 'beplus-advanced-reviews-for-woocommerce' ),
				/* translators: %s: video name. */
				'playVideoPreview' => __( 'Play preview of %s', 'beplus-advanced-reviews-for-woocommerce' ),
				/* translators: %s: video name. */
				'pauseVideoPreview' => __( 'Pause preview of %s', 'beplus-advanced-reviews-for-woocommerce' ),
				/* translators: %s: attachment name. */
				'removeMedia'    => __( 'Remove %s', 'beplus-advanced-reviews-for-woocommerce' ),
				/* translators: 1: attachment name, 2: new position, 3: total attachments. */
				'mediaMoved'     => __( '%1$s moved to position %2$d of %3$d.', 'beplus-advanced-reviews-for-woocommerce' ),
				'mediaTypeNotAllowed' => __( 'This attachment format is not supported.', 'beplus-advanced-reviews-for-woocommerce' ),
				'ratingRequired'  => __( 'Please select a rating.', 'beplus-advanced-reviews-for-woocommerce' ),
				'contentRequired' => __( 'Please write a review.', 'beplus-advanced-reviews-for-woocommerce' ),
				'imageTooLarge'   => sprintf(
					/* translators: %s: max size in MB */
					__( 'Image must be smaller than %s MB.', 'beplus-advanced-reviews-for-woocommerce' ),
					( $settings['max_image_size_mb'] ?? 2 )
				),
				'videoTooLarge'   => sprintf(
					/* translators: %s: max size in MB */
					__( 'Video must be smaller than %s MB.', 'beplus-advanced-reviews-for-woocommerce' ),
					( $settings['max_video_size_mb'] ?? 20 )
				),
				'imageNotAllowed' => __( 'Image uploads are not allowed.', 'beplus-advanced-reviews-for-woocommerce' ),
				'videoNotAllowed' => __( 'Video uploads are not allowed.', 'beplus-advanced-reviews-for-woocommerce' ),
				'emailRequired'    => __( 'Please provide your email address.', 'beplus-advanced-reviews-for-woocommerce' ),
				'invalidEmail'     => __( 'Please provide a valid email address.', 'beplus-advanced-reviews-for-woocommerce' ),
				'verifiedPurchase' => __( 'Verified Purchase', 'beplus-advanced-reviews-for-woocommerce' ),
			),
		);
	}

	public function enqueue_admin( string $hook_suffix ): void {
		$hooks = array(
			'settings_page_beplus-advanced-reviews-for-woocommerce',
			'woocommerce_page_beplus-advanced-reviews-for-woocommerce',
		);
		$found = false;
		foreach ( $hooks as $h ) {
			if ( false !== strpos( $hook_suffix, $h ) ) {
				$found = true;
				break;
			}
		}
		if ( ! $found ) {
			return;
		}

		wp_enqueue_style(
			'beplus-advanced-reviews-for-woocommerce-admin',
			$this->plugin_url . 'admin/css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'beplus-advanced-reviews-for-woocommerce-admin',
			$this->plugin_url . 'admin/js/settings.js',
			array( 'wp-element' ),
			$this->version,
			true
		);

		wp_localize_script(
			'beplus-advanced-reviews-for-woocommerce-admin',
			'bparfwAdmin',
			array(
				'restUrl' => rest_url( 'beplus-advanced-reviews-for-woocommerce/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

}
