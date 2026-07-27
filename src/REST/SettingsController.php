<?php
/**
 * SettingsController — REST API for plugin settings.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage REST
 */

namespace BeplusAdvancedReviewsForWoocommerce\REST;

use BeplusAdvancedReviewsForWoocommerce\Settings\SettingsRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsController extends \WP_REST_Controller {

	private SettingsRegistry $registry;

	public function __construct() {
		$this->namespace = 'beplus-advanced-reviews-for-woocommerce/v1';
		$this->rest_base = 'settings';
		$this->registry  = new SettingsRegistry( new \BeplusAdvancedReviewsForWoocommerce\Core\Container() );
	}

	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);
	}

	/**
	 * Get current settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings(): \WP_REST_Response {
		return rest_ensure_response( $this->registry->get_all() );
	}

	/**
	 * Update settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_settings( \WP_REST_Request $request ) {
		$params    = $request->get_json_params();
		$existing  = $this->registry->get_all();
		$merged    = array_merge( $existing, $params ?? array() );
		$sanitized = $this->registry->sanitize_settings( $merged );
		$updated   = $this->registry->update( $sanitized );

		if ( ! $updated ) {
			return new \WP_Error(
				'save_failed',
				__( 'Failed to save settings.', 'beplus-advanced-reviews-for-woocommerce' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response( array(
			'success'  => true,
			'settings' => $sanitized,
		) );
	}

	/**
	 * Permission callback.
	 *
	 * @return bool
	 */
	public function can_manage(): bool {
		return current_user_can( 'manage_options' );
	}
}
