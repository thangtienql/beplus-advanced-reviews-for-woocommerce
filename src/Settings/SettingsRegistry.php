<?php
/**
 * SettingsRegistry — plugin options, defaults, and settings page.
 *
 * @package BePlusAdvancedReviews
 * @subpackage Settings
 */

namespace BePlusAdvancedReviews\Settings;

use BePlusAdvancedReviews\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsRegistry extends AbstractModule {

	private const OPTION_KEY = 'beplus_advanced_reviews_settings';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page under Settings menu.
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'BePlus Reviews', 'beplus-advanced-reviews' ),
			__( 'BePlus Reviews', 'beplus-advanced-reviews' ),
			'manage_options',
			'beplus-advanced-reviews',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'beplus_advanced_reviews_settings',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);

		add_settings_section(
			'bpar_general',
			__( 'General Settings', 'beplus-advanced-reviews' ),
			'__return_empty_string',
			'beplus-advanced-reviews'
		);

		add_settings_field(
			'display_mode',
			__( 'Display Mode', 'beplus-advanced-reviews' ),
			array( $this, 'render_display_mode_field' ),
			'beplus-advanced-reviews',
			'bpar_general'
		);

		add_settings_field(
			'enable_images',
			__( 'Image Uploads', 'beplus-advanced-reviews' ),
			array( $this, 'render_checkbox_field' ),
			'beplus-advanced-reviews',
			'bpar_general',
			array( 'key' => 'enable_images', 'label' => __( 'Allow customers to upload images with reviews', 'beplus-advanced-reviews' ) )
		);

		add_settings_field(
			'enable_paste',
			__( 'Clipboard Paste', 'beplus-advanced-reviews' ),
			array( $this, 'render_checkbox_field' ),
			'beplus-advanced-reviews',
			'bpar_general',
			array( 'key' => 'enable_paste', 'label' => __( 'Allow copy/paste images from clipboard', 'beplus-advanced-reviews' ) )
		);

		add_settings_field(
			'load_more_count',
			__( 'Reviews Per Load', 'beplus-advanced-reviews' ),
			array( $this, 'render_number_field' ),
			'beplus-advanced-reviews',
			'bpar_general',
			array( 'key' => 'load_more_count', 'min' => 1, 'max' => 50 )
		);

		add_settings_field(
			'max_image_size_mb',
			__( 'Max Image Size (MB)', 'beplus-advanced-reviews' ),
			array( $this, 'render_number_field' ),
			'beplus-advanced-reviews',
			'bpar_general',
			array(
				'key'         => 'max_image_size_mb',
				'min'         => 1,
				'max'         => 20,
				'description' => __( 'Maximum size per image in megabytes. Recommended: 2 MB.', 'beplus-advanced-reviews' ),
			)
		);

		add_settings_field(
			'enable_videos',
			__( 'Video Uploads', 'beplus-advanced-reviews' ),
			array( $this, 'render_checkbox_field' ),
			'beplus-advanced-reviews',
			'bpar_general',
			array( 'key' => 'enable_videos', 'label' => __( 'Allow customers to upload videos with reviews', 'beplus-advanced-reviews' ) )
		);

		add_settings_field(
			'max_video_size_mb',
			__( 'Max Video Size (MB)', 'beplus-advanced-reviews' ),
			array( $this, 'render_number_field' ),
			'beplus-advanced-reviews',
			'bpar_general',
			array(
				'key'         => 'max_video_size_mb',
				'min'         => 1,
				'max'         => 100,
				'description' => __( 'Maximum size per video in megabytes. Recommended: 20 MB.', 'beplus-advanced-reviews' ),
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'BePlus Advanced Reviews Settings', 'beplus-advanced-reviews' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'beplus_advanced_reviews_settings' );
				do_settings_sections( 'beplus-advanced-reviews' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render display mode field.
	 *
	 * @return void
	 */
	public function render_display_mode_field(): void {
		$settings = $this->get_all();
		$current  = $settings['display_mode'] ?? 'replace';
		$options  = array(
			'keep'    => __( 'Keep default WooCommerce reviews; place block manually', 'beplus-advanced-reviews' ),
			'replace' => __( 'Replace default reviews with Advanced Reviews', 'beplus-advanced-reviews' ),
		);
		?>
		<select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]">
			<?php foreach ( $options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render checkbox field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_checkbox_field( array $args ): void {
		$settings = $this->get_all();
		$key      = $args['key'];
		$label    = $args['label'] ?? '';
		$checked  = ! empty( $settings[ $key ] );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $checked ); ?>>
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	}

	/**
	 * Render number field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function render_number_field( array $args ): void {
		$settings = $this->get_all();
		$key      = $args['key'];
		$min      = $args['min'] ?? 1;
		$max      = $args['max'] ?? 50;
		$current  = isset( $settings[ $key ] ) ? absint( $settings[ $key ] ) : 10;
		?>
		<input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $current ); ?>" min="<?php echo esc_attr( (string) $min ); ?>" max="<?php echo esc_attr( (string) $max ); ?>">
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize settings before save.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$defaults = $this->get_defaults();
		$clean    = array();

		$clean['display_mode'] = isset( $input['display_mode'] )
			? sanitize_text_field( $input['display_mode'] )
			: $defaults['display_mode'];

		$allowed_modes = array( 'keep', 'replace' );
		if ( ! in_array( $clean['display_mode'], $allowed_modes, true ) ) {
			$clean['display_mode'] = 'replace';
		}

		$clean['enable_images'] = ! empty( $input['enable_images'] );
		$clean['enable_paste']  = ! empty( $input['enable_paste'] );

		$clean['load_more_count'] = isset( $input['load_more_count'] )
			? max( 1, min( 50, absint( $input['load_more_count'] ) ) )
			: $defaults['load_more_count'];

		$clean['max_image_size_mb'] = isset( $input['max_image_size_mb'] )
			? max( 1, min( 20, absint( $input['max_image_size_mb'] ) ) )
			: $defaults['max_image_size_mb'];

		$clean['enable_videos'] = ! empty( $input['enable_videos'] );

		$clean['max_video_size_mb'] = isset( $input['max_video_size_mb'] )
			? max( 1, min( 100, absint( $input['max_video_size_mb'] ) ) )
			: $defaults['max_video_size_mb'];

		return $clean;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return array_merge( $this->get_defaults(), $saved );
	}

	/**
	 * Get display mode.
	 *
	 * @return string
	 */
	public function get_display_mode(): string {
		$settings = $this->get_all();
		return $settings['display_mode'] ?? 'replace';
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $settings Settings to save.
	 * @return bool
	 */
	public function update( array $settings ): bool {
		return update_option( self::OPTION_KEY, $settings, false );
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	private function get_defaults(): array {
		return array(
			'display_mode'     => 'replace',
			'enable_images'    => true,
			'enable_paste'     => true,
			'enable_filter'    => true,
			'enable_sort'      => true,
			'load_more_count'  => 10,
			'rating_threshold' => 0,
			'max_image_size_mb' => 2,
			'enable_videos'    => false,
			'max_video_size_mb' => 20,
		);
	}
}
