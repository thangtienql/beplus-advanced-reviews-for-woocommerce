<?php
/**
 * SettingsRegistry — plugin options, defaults, and settings page.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Settings
 */

namespace BeplusAdvancedReviewsForWoocommerce\Settings;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SettingsRegistry extends AbstractModule {

	private const OPTION_KEY = 'beplus_advanced_reviews_for_woocommerce_settings';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Advanced Reviews', 'beplus-advanced-reviews-for-woocommerce' ),
			__( 'Advanced Reviews', 'beplus-advanced-reviews-for-woocommerce' ),
			'manage_options',
			'beplus-advanced-reviews-for-woocommerce',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'beplus_advanced_reviews_for_woocommerce_settings',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_all();
		?>
		<div class="wrap bparfw-settings">
			<h1><?php echo esc_html__( 'Advanced Reviews Settings', 'beplus-advanced-reviews-for-woocommerce' ); ?></h1>
			<p class="description bparfw-settings__intro">
				<?php echo esc_html__( 'Configure how product reviews and their media attachments behave on your store.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
			</p>

			<nav class="bparfw-settings__tabs" aria-label="<?php echo esc_attr__( 'Settings sections', 'beplus-advanced-reviews-for-woocommerce' ); ?>">
				<a href="#tab-general" class="bparfw-settings__tab is-active" data-bparfw-tab="general">
					<?php echo esc_html__( 'General', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</a>
				<a href="#tab-media" class="bparfw-settings__tab" data-bparfw-tab="media">
					<?php echo esc_html__( 'Media', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</a>
				<a href="#tab-styling" class="bparfw-settings__tab" data-bparfw-tab="styling">
					<?php echo esc_html__( 'Styling', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</a>
			</nav>

			<form method="post" action="options.php" class="bparfw-settings__form">
				<?php
				settings_fields( 'beplus_advanced_reviews_for_woocommerce_settings' );
				?>

				<div class="bparfw-settings__panel is-active" data-bparfw-panel="general">
					<?php $this->render_display_card( $settings ); ?>
					<?php $this->render_behavior_card( $settings ); ?>
					<?php $this->render_verified_card( $settings ); ?>
				</div>

				<div class="bparfw-settings__panel" data-bparfw-panel="media">
					<?php $this->render_image_card( $settings ); ?>
					<?php $this->render_video_card( $settings ); ?>
				</div>

				<div class="bparfw-settings__panel" data-bparfw-panel="styling">
					<?php $this->render_styling_card( $settings ); ?>
				</div>

				<div class="bparfw-settings__actions">
					<?php submit_button( __( 'Save Changes', 'beplus-advanced-reviews-for-woocommerce' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Render display settings card.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_display_card( array $settings ): void {
		$display_mode = $settings['display_mode'] ?? 'replace';
		$load_count   = absint( $settings['load_more_count'] ?? 10 );
		?>
		<div class="bparfw-settings__card">
			<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Display Settings', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>

			<div class="bparfw-settings__choice-list">
				<label class="bparfw-settings__choice">
					<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]" value="replace" <?php checked( 'replace', $display_mode ); ?>>
					<span class="bparfw-settings__choice-text">
						<strong><?php echo esc_html__( 'Replace default reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?></strong>
						<span><?php echo esc_html__( 'Completely replace the standard WooCommerce reviews tab with the Advanced Reviews block.', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
					</span>
				</label>
				<label class="bparfw-settings__choice">
					<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[display_mode]" value="keep" <?php checked( 'keep', $display_mode ); ?>>
					<span class="bparfw-settings__choice-text">
						<strong><?php echo esc_html__( 'Keep default reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?></strong>
						<span><?php echo esc_html__( 'Keep the built-in WooCommerce reviews. Place the Advanced Reviews block manually where needed.', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
					</span>
				</label>
			</div>

			<table class="form-table" role="presentation" style="margin-top:1.25rem;">
				<tbody>
					<tr>
						<th scope="row">
							<label for="bparfw-load-more"><?php echo esc_html__( 'Reviews per load', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input type="number" id="bparfw-load-more" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[load_more_count]" value="<?php echo esc_attr( (string) $load_count ); ?>" min="1" max="50" class="small-text">
							<p class="description"><?php echo esc_html__( 'Number of reviews shown before the "Load More" button appears.', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render behavior settings card.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_behavior_card( array $settings ): void {
		$threshold     = absint( $settings['rating_threshold'] ?? 0 );
		$enable_filter = ! empty( $settings['enable_filter'] );
		$enable_sort   = ! empty( $settings['enable_sort'] );
		?>
		<div class="bparfw-settings__card">
			<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Review Behavior', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="bparfw-rating-threshold"><?php echo esc_html__( 'Minimum rating to display', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
						</th>
						<td>
							<select id="bparfw-rating-threshold" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rating_threshold]" style="min-width:200px;">
								<option value="0" <?php selected( 0, $threshold ); ?>><?php echo esc_html__( 'Show all reviews', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
								<option value="1" <?php selected( 1, $threshold ); ?>><?php echo esc_html__( '1 star and above', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
								<option value="2" <?php selected( 2, $threshold ); ?>><?php echo esc_html__( '2 stars and above', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
								<option value="3" <?php selected( 3, $threshold ); ?>><?php echo esc_html__( '3 stars and above', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
								<option value="4" <?php selected( 4, $threshold ); ?>><?php echo esc_html__( '4 stars and above', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
								<option value="5" <?php selected( 5, $threshold ); ?>><?php echo esc_html__( '5 stars only', 'beplus-advanced-reviews-for-woocommerce' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'Hide reviews below this star rating from the front-end display.', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>

			<div class="bparfw-image__options" style="margin-top:1rem;">
				<label class="bparfw-filter-row__checkbox">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_filter]" value="1" <?php checked( $enable_filter ); ?>>
					<?php echo esc_html__( 'Show filter bar (ratings & images filter)', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</label>
				<br>
				<label class="bparfw-filter-row__checkbox" style="margin-top:8px;">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_sort]" value="1" <?php checked( $enable_sort ); ?>>
					<?php echo esc_html__( 'Show sort controls (newest, oldest, highest, lowest)', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</label>
			</div>
		</div>
		<?php
	}

	/**
	 * Render image settings card.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_image_card( array $settings ): void {
		$enable_images = ! empty( $settings['enable_images'] );
		$enable_paste  = ! empty( $settings['enable_paste'] );
		$max_size_mb   = absint( $settings['max_image_size_mb'] ?? 2 );
		?>
		<div class="bparfw-settings__card">
			<div class="bparfw-settings__card-header">
				<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Image Uploads', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>
				<label class="bparfw-toggle" for="bparfw-enable-images">
					<input type="checkbox" id="bparfw-enable-images" class="bparfw-toggle__input" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_images]" value="1" <?php checked( $enable_images ); ?> data-bparfw-toggle="images">
					<span class="bparfw-toggle__track" aria-hidden="true">
						<span class="bparfw-toggle__thumb"></span>
					</span>
					<span class="bparfw-toggle__state" data-bparfw-state-label="images"><?php echo $enable_images ? esc_html__( 'On', 'beplus-advanced-reviews-for-woocommerce' ) : esc_html__( 'Off', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
				</label>
			</div>

			<p class="description bparfw-image__off-note" data-bparfw-off-note="images" <?php echo $enable_images ? 'hidden' : ''; ?>>
				<?php echo esc_html__( 'Image uploads are off. Enable this to allow customers to attach photos to their reviews.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
			</p>

			<div class="bparfw-image__panel" data-bparfw-panel-section="images" <?php echo $enable_images ? '' : 'hidden'; ?>>
				<p class="description bparfw-image__intro">
					<?php echo esc_html__( 'Customers can upload images via the file picker or paste directly from their clipboard into the review form. Accepted formats: JPEG, PNG, WebP.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="bparfw-max-image-size"><?php echo esc_html__( 'Max image size (MB)', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="bparfw-max-image-size" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_image_size_mb]" value="<?php echo esc_attr( (string) $max_size_mb ); ?>" min="1" max="20" class="small-text">
								<p class="description"><?php echo esc_html__( 'Maximum file size per image. Recommended: 2–5 MB.', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<div class="bparfw-image__options">
					<label class="bparfw-filter-row__checkbox">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_paste]" value="1" <?php checked( $enable_paste ); ?>>
						<?php echo esc_html__( 'Allow copy/paste images from clipboard', 'beplus-advanced-reviews-for-woocommerce' ); ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render video settings card.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_video_card( array $settings ): void {
		$enable_videos    = ! empty( $settings['enable_videos'] );
		$max_video_size   = absint( $settings['max_video_size_mb'] ?? 20 );
		?>
		<div class="bparfw-settings__card">
			<div class="bparfw-settings__card-header">
				<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Video Uploads', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>
				<label class="bparfw-toggle" for="bparfw-enable-videos">
					<input type="checkbox" id="bparfw-enable-videos" class="bparfw-toggle__input" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_videos]" value="1" <?php checked( $enable_videos ); ?> data-bparfw-toggle="videos">
					<span class="bparfw-toggle__track" aria-hidden="true">
						<span class="bparfw-toggle__thumb"></span>
					</span>
					<span class="bparfw-toggle__state" data-bparfw-state-label="videos"><?php echo $enable_videos ? esc_html__( 'On', 'beplus-advanced-reviews-for-woocommerce' ) : esc_html__( 'Off', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
				</label>
			</div>

			<p class="description bparfw-video__off-note" data-bparfw-off-note="videos" <?php echo $enable_videos ? 'hidden' : ''; ?>>
				<?php echo esc_html__( 'Video uploads are off. Enable this to allow customers to attach short video clips to their reviews.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
			</p>

			<div class="bparfw-video__panel" data-bparfw-panel-section="videos" <?php echo $enable_videos ? '' : 'hidden'; ?>>
				<p class="description bparfw-video__intro">
					<?php echo esc_html__( 'Customers can upload video reviews alongside text and images. Accepted formats: MP4, WebM, OGG.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="bparfw-max-video-size"><?php echo esc_html__( 'Max video size (MB)', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="bparfw-max-video-size" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[max_video_size_mb]" value="<?php echo esc_attr( (string) $max_video_size ); ?>" min="1" max="100" class="small-text">
								<p class="description"><?php echo esc_html__( 'Maximum file size per video. Recommended: 20–50 MB.', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}

	/**
	 * Render verified purchase settings card.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_verified_card( array $settings ): void {
		$enable_verified  = ! empty( $settings['enable_verified'] );
		$verified_status  = $settings['verified_statuses'] ?? 'completed_processing';
		?>
		<div class="bparfw-settings__card">
			<div class="bparfw-settings__card-header">
				<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Verified Purchase', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>
				<label class="bparfw-toggle" for="bparfw-enable-verified">
					<input type="checkbox" id="bparfw-enable-verified" class="bparfw-toggle__input" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[enable_verified]" value="1" <?php checked( $enable_verified ); ?> data-bparfw-toggle="verified">
					<span class="bparfw-toggle__track" aria-hidden="true">
						<span class="bparfw-toggle__thumb"></span>
					</span>
					<span class="bparfw-toggle__state" data-bparfw-state-label="verified"><?php echo $enable_verified ? esc_html__( 'On', 'beplus-advanced-reviews-for-woocommerce' ) : esc_html__( 'Off', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
				</label>
			</div>

			<p class="description bparfw-image__off-note" data-bparfw-off-note="verified" <?php echo $enable_verified ? 'hidden' : ''; ?>>
				<?php echo esc_html__( 'Verified Purchase badges are off. Enable this to show a "Verified Purchase" badge on reviews from customers who bought the product.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
			</p>

			<div class="bparfw-image__panel" data-bparfw-panel-section="verified" <?php echo $enable_verified ? '' : 'hidden'; ?>>
				<p class="description bparfw-image__intro">
					<?php echo esc_html__( 'Reviews from customers who have purchased this product will display a "Verified Purchase" badge. Customers can also filter to show only verified reviews.', 'beplus-advanced-reviews-for-woocommerce' ); ?>
				</p>

				<div class="bparfw-settings__choice-list" style="margin-top:1rem;">
					<label class="bparfw-settings__choice">
						<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[verified_statuses]" value="completed_processing" <?php checked( 'completed_processing', $verified_status ); ?>>
						<span class="bparfw-settings__choice-text">
							<strong><?php echo esc_html__( 'Completed + Processing', 'beplus-advanced-reviews-for-woocommerce' ); ?></strong>
							<span><?php echo esc_html__( 'Count an order as verified when its status is either Completed or Processing.', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
						</span>
					</label>
					<label class="bparfw-settings__choice">
						<input type="radio" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[verified_statuses]" value="completed" <?php checked( 'completed', $verified_status ); ?>>
						<span class="bparfw-settings__choice-text">
							<strong><?php echo esc_html__( 'Completed only', 'beplus-advanced-reviews-for-woocommerce' ); ?></strong>
							<span><?php echo esc_html__( 'Only count an order as verified when its status is Completed.', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
						</span>
					</label>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a single color picker row.
	 *
	 * @param string                $key      Color settings key.
	 * @param string                $label    Human-readable label.
	 * @param string                $desc     Description text.
	 * @param array<string, mixed>  $settings Plugin settings.
	 * @param array<string, string> $defaults Default color values.
	 * @return void
	 */
	private function render_color_field( string $key, string $label, string $desc, array $settings, array $defaults ): void {
		$colors    = $settings['colors'] ?? array();
		$value     = $colors[ $key ] ?? $defaults[ $key ] ?? '#000000';
		$default   = $defaults[ $key ] ?? '#000000';
		// Compare case-insensitively: sanitize_settings() uppercases stored
		// colors, while get_defaults() uses mixed case for some keys.
		$is_custom = ( strtoupper( $value ) !== strtoupper( $default ) );
		$field_name = self::OPTION_KEY . '[colors][' . $key . ']';
		$field_id   = 'bparfw-color-' . $key;
		/* translators: %s: Color setting label. */
		$choose_label = sprintf( __( 'Choose %s', 'beplus-advanced-reviews-for-woocommerce' ), $label );
		/* translators: %s: Color setting label. */
		$hex_label = sprintf( __( 'Hex value for %s', 'beplus-advanced-reviews-for-woocommerce' ), $label );
		/* translators: %s: Color setting label. */
		$reset_label = sprintf( __( 'Reset %s to default', 'beplus-advanced-reviews-for-woocommerce' ), $label );
		?>
		<div class="bparfw-color-field" data-bparfw-color-field>
			<div class="bparfw-color-field__info">
				<label class="bparfw-color-field__label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
				<p class="bparfw-color-field__desc"><?php echo esc_html( $desc ); ?></p>
			</div>
			<div class="bparfw-color-field__controls">
				<label class="bparfw-color-field__swatch" data-bparfw-color-swatch="<?php echo esc_attr( $key ); ?>" style="background-color:<?php echo esc_attr( $value ); ?>" aria-label="<?php echo esc_attr( $choose_label ); ?>">
					<input type="color" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="bparfw-color-field__native" data-bparfw-color-input="<?php echo esc_attr( $key ); ?>" data-bparfw-color-default="<?php echo esc_attr( $default ); ?>">
				</label>
				<input type="text" class="bparfw-color-field__hex" data-bparfw-color-hex="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" maxlength="7" aria-label="<?php echo esc_attr( $hex_label ); ?>">
				<span class="bparfw-color-field__status" data-bparfw-color-status="<?php echo esc_attr( $key ); ?>">
					<?php if ( $is_custom ) : ?>
						<span class="bparfw-color-field__chip bparfw-color-field__chip--custom"><?php echo esc_html__( 'Custom', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
					<?php else : ?>
						<span class="bparfw-color-field__chip bparfw-color-field__chip--default"><?php echo esc_html__( 'Default', 'beplus-advanced-reviews-for-woocommerce' ); ?></span>
					<?php endif; ?>
				</span>
				<button type="button" class="bparfw-color-field__reset" data-bparfw-color-reset="<?php echo esc_attr( $key ); ?>" <?php echo $is_custom ? '' : 'hidden'; ?> aria-label="<?php echo esc_attr( $reset_label ); ?>">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M20.49 9A9 9 0 005.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 013.51 15"/></svg>
				</button>
			</div>
			<p id="<?php echo esc_attr( $field_id . '-error' ); ?>" class="bparfw-color-field__error" data-bparfw-color-error="<?php echo esc_attr( $key ); ?>" hidden><?php echo esc_html__( 'Enter a valid hex color (e.g. #FF0000).', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render styling settings card.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return void
	 */
	private function render_styling_card( array $settings ): void {
		$defaults = $this->get_defaults()['colors'];
		?>
		<div class="bparfw-settings__card">
			<h2 class="bparfw-settings__card-title"><?php echo esc_html__( 'Frontend Colors', 'beplus-advanced-reviews-for-woocommerce' ); ?></h2>
			<p class="description" style="margin-bottom:1.25rem;"><?php echo esc_html__( 'Customize every color used on the front-end review display. Leave a color at its default to keep the theme-aware styling.', 'beplus-advanced-reviews-for-woocommerce' ); ?></p>

			<div class="bparfw-color-grid">

			<fieldset class="bparfw-color-group">
				<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Primary Accent', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
				<?php
				$this->render_color_field( 'primary', __( 'Primary Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Main accent color used for stars, buttons, links, borders and derived tones.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				?>
			</fieldset>

			<fieldset class="bparfw-color-group">
				<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Stars', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
				<?php
				$this->render_color_field( 'star_empty', __( 'Empty Star', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Color for unfilled stars in the rating display.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				?>
			</fieldset>

			<fieldset class="bparfw-color-group">
				<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Text & Background', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
				<?php
				$this->render_color_field( 'text', __( 'Text Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Main text color for review content, labels, dates, and select dropdowns.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				$this->render_color_field( 'bg', __( 'Background Color', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color for cards, panels, and inputs.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				$this->render_color_field( 'button_text', __( 'Button Text', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Text color for Submit, Load More, and Write a Review buttons.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				?>
			</fieldset>

			<fieldset class="bparfw-color-group">
				<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Error State', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
				<?php
				$this->render_color_field( 'error_bg', __( 'Error Background', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color for error messages and alerts.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				$this->render_color_field( 'error_text', __( 'Error Text', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Text color for error messages.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				$this->render_color_field( 'error_border', __( 'Error Border', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Border color for error states on inputs.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				?>
			</fieldset>

			<fieldset class="bparfw-color-group bparfw-color-group--wide">
				<legend class="bparfw-color-group__legend"><?php echo esc_html__( 'Success State', 'beplus-advanced-reviews-for-woocommerce' ); ?></legend>
				<?php
				$this->render_color_field( 'success_bg', __( 'Success Background', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Background color for success messages.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				$this->render_color_field( 'success_text', __( 'Success Text', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Text color for success messages.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				$this->render_color_field( 'success_border', __( 'Success Border', 'beplus-advanced-reviews-for-woocommerce' ), __( 'Border color for success states.', 'beplus-advanced-reviews-for-woocommerce' ), $settings, $defaults );
				?>
			</fieldset>
			</div>

			<div class="bparfw-color-group__actions">
				<button type="button" class="button button-secondary" id="bparfw-reset-colors"><?php echo esc_html__( 'Reset to Defaults', 'beplus-advanced-reviews-for-woocommerce' ); ?></button>
			</div>
		</div>
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
			$clean['display_mode'] = 'keep';
		}

		$clean['enable_images'] = ! empty( $input['enable_images'] );
		$clean['enable_paste']  = ! empty( $input['enable_paste'] );

		$clean['load_more_count'] = isset( $input['load_more_count'] )
			? max( 1, min( 50, absint( $input['load_more_count'] ) ) )
			: $defaults['load_more_count'];

		$clean['rating_threshold'] = isset( $input['rating_threshold'] )
			? max( 0, min( 5, absint( $input['rating_threshold'] ) ) )
			: $defaults['rating_threshold'];

		$clean['max_image_size_mb'] = isset( $input['max_image_size_mb'] )
			? max( 1, min( 20, absint( $input['max_image_size_mb'] ) ) )
			: $defaults['max_image_size_mb'];

		$clean['enable_videos'] = ! empty( $input['enable_videos'] );

		$clean['max_video_size_mb'] = isset( $input['max_video_size_mb'] )
			? max( 1, min( 100, absint( $input['max_video_size_mb'] ) ) )
			: $defaults['max_video_size_mb'];

		$clean['enable_filter'] = ! empty( $input['enable_filter'] );

		$clean['enable_sort'] = ! empty( $input['enable_sort'] );

		$clean['enable_verified'] = ! empty( $input['enable_verified'] );

		$clean['verified_statuses'] = isset( $input['verified_statuses'] )
			? sanitize_text_field( $input['verified_statuses'] )
			: $defaults['verified_statuses'];

		$allowed_status_opts = array( 'completed', 'completed_processing' );
		if ( ! in_array( $clean['verified_statuses'], $allowed_status_opts, true ) ) {
			$clean['verified_statuses'] = 'completed_processing';
		}

		// Sanitize colors — normalize to uppercase for case-insensitive comparison.
		$color_defaults   = $defaults['colors'] ?? array();
		$clean['colors']  = array();
		foreach ( $color_defaults as $color_key => $color_default ) {
			$raw                            = $input['colors'][ $color_key ] ?? '';
			$sanitized                      = sanitize_hex_color( $raw );
			$clean['colors'][ $color_key ]  = $sanitized ? strtoupper( $sanitized ) : $color_default;
		}

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

	public function get_display_mode(): string {
		$settings = $this->get_all();
		return $settings['display_mode'] ?? 'keep';
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
			'display_mode'      => 'keep',
			'enable_images'     => true,
			'enable_paste'      => true,
			'enable_filter'     => true,
			'enable_sort'       => true,
			'load_more_count'   => 10,
			'rating_threshold'  => 0,
			'max_image_size_mb' => 2,
			'enable_videos'     => false,
			'max_video_size_mb' => 20,
			'enable_verified'   => true,
			'verified_statuses' => 'completed_processing',
			'colors'            => array(
				'primary'             => '#000000',
				'text'                => '#000000',
				'bg'                  => '#FFFFFF',
				'button_text'        => '#ffffff',
				'star_empty'          => '#CFCFCF',
				'error_bg'            => '#f8d7da',
				'error_text'          => '#721c24',
				'error_border'        => '#f5c6cb',
				'success_bg'          => '#d4edda',
				'success_text'        => '#155724',
				'success_border'      => '#c3e6cb',
			),
		);
	}
}
