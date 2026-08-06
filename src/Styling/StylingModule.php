<?php
/**
 * StylingModule — injects admin-controlled color overrides as inline CSS.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Styling
 */

namespace BeplusAdvancedReviewsForWoocommerce\Styling;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StylingModule extends AbstractModule {

	/**
	 * Map of settings keys to CSS custom property names.
	 *
	 * @var array<string, string>
	 */
	private const COLOR_MAP = array(
		'primary'             => '--bpar-primary',
		'primary_light'       => '--bpar-primary-light',
		'primary_hover'       => '--bpar-primary-hover',
		'primary_hover_bg'    => '--bpar-primary-hover-bg',
		'primary_dragover_bg' => '--bpar-primary-dragover-bg',
		'text'                => '--bpar-text',
		'bg'                  => '--bpar-bg',
		'muted'               => '--bpar-muted',
		'date_muted'          => '--bpar-date-muted',
		'border'              => '--bpar-border',
		'border_dark'         => '--bpar-border-dark',
		'star_empty'          => '--bpar-star-empty',
		'error_bg'            => '--bpar-error-bg',
		'error_text'          => '--bpar-error-text',
		'error_border'        => '--bpar-error-border',
		'success_bg'          => '--bpar-success-bg',
		'success_text'        => '--bpar-success-text',
		'success_border'      => '--bpar-success-border',
	);

	/**
	 * Default color values — must match SettingsRegistry::get_defaults()['colors'].
	 *
	 * @var array<string, string>
	 */
	private const DEFAULTS = array(
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
	);

	public function register(): void {
		add_action( 'wp_head', array( $this, 'output_inline_css' ), 9999 );
	}

	/**
	 * Output inline <style> block overriding --bpar-* custom properties.
	 *
	 * Only outputs when admin has changed at least one color from its default.
	 *
	 * @return void
	 */
	public function output_inline_css(): void {
		if ( ! function_exists( 'beplus_advanced_reviews_for_woocommerce_get_settings' ) ) {
			return;
		}

		$settings = beplus_advanced_reviews_for_woocommerce_get_settings();
		$colors   = $settings['colors'] ?? array();

		if ( ! is_array( $colors ) ) {
			return;
		}

		// Only output tokens that differ from defaults — the rest stay in style.css.
		// Compare case-insensitively: native <input type="color"> always returns lowercase hex.
		$overrides = '';
		foreach ( self::COLOR_MAP as $key => $var ) {
			$current = isset( $colors[ $key ] ) ? strtoupper( (string) $colors[ $key ] ) : '';
			$default = strtoupper( self::DEFAULTS[ $key ] );
			if ( $current !== '' && $current !== $default ) {
				$overrides .= "  {$var}: " . esc_attr( $current ) . ";\n";
			}
		}

		if ( '' === $overrides ) {
			return;
		}

		echo '<style id="bparfw-custom-colors">' . "\n:root {\n" . $overrides . "}\n</style>" . "\n";
	}
}
