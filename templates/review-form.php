<?php
/**
 * Template: Review Submission Form
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file; variables are local to the included scope.
$product_id  = $args['product_id'] ?? 0;
$show_media  = ! empty( $args['show_media'] );
if ( ! $show_media && isset( $args['show_images'] ) ) {
	$show_media = ! empty( $args['show_images'] );
}
$images_enabled = beplus_advanced_reviews_for_woocommerce_is_images_enabled();
$videos_enabled = beplus_advanced_reviews_for_woocommerce_is_videos_enabled();
$user = wp_get_current_user();
$write_review_label  = beplus_advanced_reviews_for_woocommerce_get_display_label( 'label_write_review', __( 'Write a Review', 'beplus-advanced-reviews-for-woocommerce' ) );
$submit_review_label = beplus_advanced_reviews_for_woocommerce_get_display_label( 'label_submit_review', __( 'Submit Review', 'beplus-advanced-reviews-for-woocommerce' ) );
?>
<div class="beplus-advanced-reviews-for-woocommerce__submit-form-wrapper">
	<h3 class="beplus-advanced-reviews-for-woocommerce__submit-form-title"><?php echo esc_html( $write_review_label ); ?></h3>
	<form class="beplus-advanced-reviews-for-woocommerce__submit-form" method="post" enctype="multipart/form-data">
		<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product_id ); ?>">

		<div class="beplus-advanced-reviews-for-woocommerce__star-rating">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<input
					type="radio"
					id="bpar-star-<?php echo esc_attr( (string) $i ); ?>"
					name="rating"
					value="<?php echo esc_attr( (string) $i ); ?>"
					class="beplus-advanced-reviews-for-woocommerce__star-input"
					required
				>
				<label
					for="bpar-star-<?php echo esc_attr( (string) $i ); ?>"
					class="beplus-advanced-reviews-for-woocommerce__star-label"
					aria-label="<?php
					/* translators: %d: Number of stars */
					echo esc_attr( sprintf( __( '%d stars', 'beplus-advanced-reviews-for-woocommerce' ), $i ) );
					?>"
					title="<?php
					/* translators: %d: Number of stars */
					echo esc_attr( sprintf( __( '%d stars', 'beplus-advanced-reviews-for-woocommerce' ), $i ) );
					?>"
				><?php echo beplus_advanced_reviews_for_woocommerce_star_icon(); // phpcs:ignore ?></label>
			<?php endfor; ?>
		</div>

		<?php if ( ! $user->exists() ) : ?>
			<label for="bpar-author"><?php esc_html_e( 'Your name *', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
			<input
				id="bpar-author"
				type="text"
				name="author"
				placeholder="<?php esc_attr_e( 'Enter your name', 'beplus-advanced-reviews-for-woocommerce' ); ?>"
				required
			>
			<label for="bpar-email"><?php esc_html_e( 'Your email *', 'beplus-advanced-reviews-for-woocommerce' ); ?></label>
			<input
				id="bpar-email"
				type="email"
				name="email"
				placeholder="<?php esc_attr_e( 'Enter your email', 'beplus-advanced-reviews-for-woocommerce' ); ?>"
				required
			>
		<?php endif; ?>

		<textarea
			name="content"
			placeholder="<?php esc_attr_e( 'Write your review...', 'beplus-advanced-reviews-for-woocommerce' ); ?>"
			required
			aria-label="<?php esc_attr_e( 'Review content', 'beplus-advanced-reviews-for-woocommerce' ); ?>"
		></textarea>

		<?php if ( $show_media && ( $images_enabled || $videos_enabled ) ) : ?>
			<?php
			$max_image_mb = beplus_advanced_reviews_for_woocommerce_get_settings()['max_image_size_mb'] ?? 2;
			$max_video_mb = beplus_advanced_reviews_for_woocommerce_get_settings()['max_video_size_mb'] ?? 20;
			
			$accept_array = array();
			if ( $images_enabled ) {
				$accept_array[] = 'image/jpeg,image/png,image/webp';
			}
			if ( $videos_enabled ) {
				$accept_array[] = 'video/mp4,video/webm,video/ogg';
			}
			$accept_types = implode( ',', $accept_array );
			$paste_enabled = $images_enabled && beplus_advanced_reviews_for_woocommerce_is_paste_enabled();
			$add_label = $images_enabled && $videos_enabled
				? __( 'Add photos or videos', 'beplus-advanced-reviews-for-woocommerce' )
				: ( $images_enabled
					? __( 'Add photos', 'beplus-advanced-reviews-for-woocommerce' )
					: __( 'Add videos', 'beplus-advanced-reviews-for-woocommerce' ) );
			$hints = array();
			if ( $images_enabled ) {
				/* translators: %s: maximum image size in MB. */
				$hints[] = sprintf( __( 'JPEG, PNG, WebP up to %s MB', 'beplus-advanced-reviews-for-woocommerce' ), $max_image_mb );
			}
			if ( $videos_enabled ) {
				/* translators: %s: maximum video size in MB. */
				$hints[] = sprintf( __( 'MP4, WebM, OGG up to %s MB', 'beplus-advanced-reviews-for-woocommerce' ), $max_video_mb );
			}
			?>
			<div class="beplus-advanced-reviews-for-woocommerce__attachments">
				<p class="beplus-advanced-reviews-for-woocommerce__attachments-title">
					<?php esc_html_e( 'Attachments', 'beplus-advanced-reviews-for-woocommerce' ); ?>
					<span class="beplus-advanced-reviews-for-woocommerce__attachments-count" hidden></span>
				</p>
				<div class="beplus-advanced-reviews-for-woocommerce__attachments-grid">
					<ol class="beplus-advanced-reviews-for-woocommerce__media-preview" role="list" aria-label="<?php esc_attr_e( 'Review attachments', 'beplus-advanced-reviews-for-woocommerce' ); ?>" hidden></ol>
					<button type="button" class="beplus-advanced-reviews-for-woocommerce__upload-zone">
						<span class="beplus-advanced-reviews-for-woocommerce__upload-zone-icon" aria-hidden="true">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
								<path d="M12 5v14M5 12h14"/>
							</svg>
						</span>
						<span class="beplus-advanced-reviews-for-woocommerce__upload-zone-text"><?php echo esc_html( $add_label ); ?></span>
						<span class="beplus-advanced-reviews-for-woocommerce__upload-zone-instructions">
							<?php echo $paste_enabled
								? esc_html__( 'Drop files here or paste an image', 'beplus-advanced-reviews-for-woocommerce' )
								: esc_html__( 'Or drop files here', 'beplus-advanced-reviews-for-woocommerce' ); ?>
						</span>
					</button>
					<input type="file" class="beplus-advanced-reviews-for-woocommerce__upload-zone-input" name="media[]" multiple accept="<?php echo esc_attr( $accept_types ); ?>" hidden>
				</div>
				<p class="beplus-advanced-reviews-for-woocommerce__upload-zone-hint"><?php echo esc_html( implode( ' · ', $hints ) ); ?></p>
			</div>
		<?php endif; ?>

		<button type="submit" class="beplus-advanced-reviews-for-woocommerce__submit-btn">
			<span data-request-label="<?php echo esc_attr( $submit_review_label ); ?>"><?php echo esc_html( $submit_review_label ); ?></span>
			<span class="beplus-advanced-reviews-for-woocommerce__submit-spinner" aria-hidden="true"></span>
		</button>
	</form>
</div>
