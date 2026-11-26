<?php
/**
 * SchemaManager — create and migrate custom database tables.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage DB
 */

namespace BeplusAdvancedReviewsForWoocommerce\DB;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SchemaManager extends AbstractModule {

	private const SCHEMA_VERSION = '1.1.5';

	public function register(): void {
		add_action( 'init', array( $this, 'maybe_create_tables' ) );
	}

	/**
	 * Create tables if schema version is outdated.
	 *
	 * @return void
	 */
	public function maybe_create_tables(): void {
		$current_version = get_option( 'beplus_advanced_reviews_for_woocommerce_schema_version', '0' );
		if ( version_compare( $current_version, self::SCHEMA_VERSION, '<' ) ) {
			$this->create_tables();
		}
	}

	/**
	 * Create the custom database tables.
	 *
	 * @return void
	 */
	public function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_media = $wpdb->prefix . 'bparfw_review_media';

		$sql = "CREATE TABLE {$table_media} (
			id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			comment_id      BIGINT UNSIGNED NOT NULL,
			attachment_id   BIGINT UNSIGNED NOT NULL,
			sort_order      TINYINT UNSIGNED NOT NULL DEFAULT 0,
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_comment (comment_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$table_votes = $wpdb->prefix . 'bparfw_review_votes';
		dbDelta( "CREATE TABLE {$table_votes} (
			comment_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (comment_id,user_id)
		) {$charset_collate};" );

		if ( '' === $wpdb->last_error ) {
			update_option( 'beplus_advanced_reviews_for_woocommerce_schema_version', self::SCHEMA_VERSION, false );
		}
	}
}
