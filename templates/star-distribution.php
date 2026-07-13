<?php
/**
 * Template: Star Distribution (server-side initial render)
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file; variables are local to the included scope.
$product_id = $args['product_id'] ?? 0;

if ( ! $product_id ) {
	return;
}

$repository = new \BeplusAdvancedReviewsForWoocommerce\Reviews\ReviewRepository();
$data       = $repository->get_star_distribution( $product_id );

if ( ! $data['total'] ) {
	echo '<p class="beplus-advanced-reviews-for-woocommerce__no-reviews">' . esc_html__( 'No reviews yet.', 'beplus-advanced-reviews-for-woocommerce' ) . '</p>';
	return;
}
?>
<div class="beplus-advanced-reviews-for-woocommerce__distribution-header">
	<div class="beplus-advanced-reviews-for-woocommerce__average">
		<span class="beplus-advanced-reviews-for-woocommerce__average-value"><?php echo esc_html( number_format_i18n( (float) $data['average'], 1 ) ); ?></span>
		<div>
			<span class="beplus-advanced-reviews-for-woocommerce__average-stars">
				<?php echo beplus_advanced_reviews_for_woocommerce_render_stars( (int) round( $data['average'] ), 1 ); // phpcs:ignore ?>
			</span>
			<span class="beplus-advanced-reviews-for-woocommerce__total">
				<?php
				/* translators: %d: number of reviews */
				printf( esc_html( _n( '%d review', '%d reviews', (int) $data['total'], 'beplus-advanced-reviews-for-woocommerce' ) ), (int) $data['total'] );
				?>
			</span>
		</div>
	</div>
	<div class="beplus-advanced-reviews-for-woocommerce__distribution-bars">
		<?php for ( $s = 5; $s >= 1; $s-- ) : ?>
			<?php
			$count   = $data['stars'][ (string) $s ] ?? 0;
			$percent = $data['total'] > 0 ? ( $count / $data['total'] * 100 ) : 0;
			?>
			<div class="beplus-advanced-reviews-for-woocommerce__distribution-bar-row">
				<span class="beplus-advanced-reviews-for-woocommerce__distribution-bar-label"><?php echo esc_html( (string) $s ); ?> <?php echo beplus_advanced_reviews_for_woocommerce_star_icon(); // phpcs:ignore ?></span>
				<div class="beplus-advanced-reviews-for-woocommerce__distribution-bar-track">
					<div
						class="beplus-advanced-reviews-for-woocommerce__distribution-bar-fill"
						style="width:<?php echo esc_attr( (string) round( $percent, 2 ) ); ?>%"
						role="progressbar"
						aria-valuenow="<?php echo esc_attr( (string) $count ); ?>"
						aria-valuemin="0"
						aria-valuemax="<?php echo esc_attr( (string) $data['total'] ); ?>"
					></div>
				</div>
				<span class="beplus-advanced-reviews-for-woocommerce__distribution-bar-count"><?php echo esc_html( (string) $count ); ?></span>
			</div>
		<?php endfor; ?>
	</div>
</div>
