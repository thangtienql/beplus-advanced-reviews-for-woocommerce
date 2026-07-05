<?php
/**
 * AssetLoader — enqueue admin and front-end scripts/styles.
 *
 * @package BePlusAdvancedReviews
 * @subpackage Core
 */

namespace BePlusAdvancedReviews\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AssetLoader extends AbstractModule {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_data' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Enqueue bparData as a standalone data script so the block viewScript
	 * always has it available, regardless of context (product page or not).
	 */
	public function enqueue_block_data(): void {
		wp_register_script( 'beplus-advanced-reviews-data', false );
		wp_localize_script(
			'beplus-advanced-reviews-data',
			'bparData',
			$this->get_bpar_data()
		);
		wp_enqueue_script( 'beplus-advanced-reviews-data' );
	}

	/**
	 * Get data for frontend JS.
	 *
	 * @return array<string, mixed>
	 */
	private function get_bpar_data(): array {
		$settings = beplus_advanced_reviews_get_settings();
		return array(
			'restUrl'         => rest_url( 'beplus-advanced-reviews/v1/' ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'maxUploadSize'   => beplus_advanced_reviews_get_max_image_size(),
			'maxUploadSizeMb' => $settings['max_image_size_mb'] ?? 2,
			'allowedTypes'    => array( 'image/jpeg', 'image/png', 'image/webp' ),
			'pasteEnabled'    => true,
			'videosEnabled'   => ! empty( $settings['enable_videos'] ),
			'maxVideoSize'    => beplus_advanced_reviews_get_max_video_size(),
			'maxVideoSizeMb'  => $settings['max_video_size_mb'] ?? 20,
			'videoTypes'      => array( 'video/mp4', 'video/webm', 'video/ogg' ),
			'i18n'            => array(
				'noReviews'       => __( 'No reviews yet.', 'beplus-advanced-reviews' ),
				'loadMore'        => __( 'Load More', 'beplus-advanced-reviews' ),
				'submitSuccess'   => __( 'Review submitted!', 'beplus-advanced-reviews' ),
				'submitError'     => __( 'Something went wrong.', 'beplus-advanced-reviews' ),
				'ratingRequired'  => __( 'Please select a rating.', 'beplus-advanced-reviews' ),
				'contentRequired' => __( 'Please write a review.', 'beplus-advanced-reviews' ),
				'imageTooLarge'   => sprintf(
					/* translators: %s: max size in MB */
					__( 'Image must be smaller than %s MB.', 'beplus-advanced-reviews' ),
					( $settings['max_image_size_mb'] ?? 2 )
				),
				'videoTooLarge'   => sprintf(
					/* translators: %s: max size in MB */
					__( 'Video must be smaller than %s MB.', 'beplus-advanced-reviews' ),
					( $settings['max_video_size_mb'] ?? 20 )
				),
			),
		);
	}

	public function enqueue_admin( string $hook_suffix ): void {
		$settings_hook = 'settings_page_beplus-advanced-reviews';
		if ( false === strpos( $hook_suffix, $settings_hook ) ) {
			return;
		}

		wp_enqueue_style(
			'beplus-advanced-reviews-admin',
			$this->plugin_url . 'admin/css/admin.css',
			array(),
			$this->version
		);

		wp_enqueue_script(
			'beplus-advanced-reviews-admin',
			$this->plugin_url . 'admin/js/settings.js',
			array( 'wp-element' ),
			$this->version,
			true
		);

		wp_localize_script(
			'beplus-advanced-reviews-admin',
			'bparAdmin',
			array(
				'restUrl' => rest_url( 'beplus-advanced-reviews/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

}
