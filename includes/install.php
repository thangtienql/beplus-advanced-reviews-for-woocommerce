<?php
/**
 * Install and uninstall helpers for Beplus Advanced Reviews For Woocommerce.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set default options on activation.
 *
 * @return void
 */
function beplus_advanced_reviews_for_woocommerce_install_defaults(): void {
	$existing = get_option( 'beplus_advanced_reviews_for_woocommerce_settings', null );

	if ( null === $existing ) {
		$defaults = array(
			'display_mode'    => 'replace',
			'enable_images'   => true,
			'enable_paste'    => true,
			'enable_filter'   => true,
			'enable_sort'     => true,
			'load_more_count' => 10,
			'rating_threshold' => 0,
			'max_image_size_mb' => 2,
			'enable_videos'    => false,
			'max_video_size_mb' => 20,
			'colors'            => array(
				'primary'             => '#21652F',
				'primary_light'       => '#e9f3eb',
				'primary_hover'       => '#3a7a47',
				'primary_hover_bg'    => '#f2f8f3',
				'primary_dragover_bg' => '#e4efe6',
				'text'                => '#101010',
				'bg'                  => '#ffffff',
				'muted'               => '#8e8e8e',
				'date_muted'          => '#969696',
				'border'              => '#e0e6e1',
				'border_dark'         => '#bdc4be',
				'star_empty'          => '#cccccc',
				'error_bg'            => '#f8d7da',
				'error_text'          => '#721c24',
				'error_border'        => '#f5c6cb',
				'success_bg'          => '#d4edda',
				'success_text'        => '#155724',
				'success_border'      => '#c3e6cb',
			),
		);
		update_option( 'beplus_advanced_reviews_for_woocommerce_settings', $defaults, false );
	}

	update_option( 'beplus_advanced_reviews_for_woocommerce_schema_version', BEPLUS_ADVANCED_REVIEWS_FOR_WOOCOMMERCE_VERSION, false );
}
