<?php
/**
 * MediaHandler — image/video upload, validation, paste support, retrieval, and deletion.
 *
 * Uses a swappable MediaStorageInterface backend (default: LocalMediaStorage).
 * To switch to cloud storage, bind a different implementation in the container.
 *
 * @package BeplusAdvancedReviewsForWoocommerce
 * @subpackage Media
 */

namespace BeplusAdvancedReviewsForWoocommerce\Media;

use BeplusAdvancedReviewsForWoocommerce\Core\AbstractModule;
use BeplusAdvancedReviewsForWoocommerce\Core\HookManager;
use BeplusAdvancedReviewsForWoocommerce\Core\Container;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MediaHandler extends AbstractModule {

	private MediaStorageInterface $storage;

	public function __construct( Container $container, MediaStorageInterface $storage = null ) {
		parent::__construct( $container );
		$this->storage = $storage ?? new LocalMediaStorage();
	}

	public function register(): void {
		add_action( 'wp_ajax_bparfw_upload_media', array( $this, 'handle_ajax_upload' ) );

		add_action( 'delete_comment', array( $this, 'delete_media_for_comment' ), 10, 1 );
		add_action( 'wp_trash_comment', array( $this, 'delete_media_for_comment' ), 10, 1 );
	}

	/**
	 * Handle file uploads from $_FILES.
	 *
	 * @param int                 $comment_id Comment ID.
	 * @param array<string, mixed> $files      $_FILES array structure.
	 * @return array<int, int> Attachment IDs.
	 */
	public function upload_files( int $comment_id, array $files ): array {
		if ( ! beplus_advanced_reviews_for_woocommerce_is_images_enabled() && ! beplus_advanced_reviews_for_woocommerce_is_videos_enabled() ) {
			return array();
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$attachment_ids = array();

		if ( empty( $files['name'] ) ) {
			return $attachment_ids;
		}

		$files = $this->normalize_files_array( $files );

		foreach ( $files as $file ) {
			if ( ! empty( $file['error'] ) ) {
				continue;
			}

			$attachment_id = $this->process_upload( $comment_id, $file );
			if ( $attachment_id ) {
				$attachment_ids[] = $attachment_id;
			}
		}

		return $attachment_ids;
	}

	/**
	 * Handle a pasted/base64 image from clipboard.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $base64_data Data URL from clipboard paste.
	 * @return int|null Attachment ID or null on failure.
	 */
	public function upload_pasted_image( int $comment_id, string $base64_data ): ?int {
		if ( ! beplus_advanced_reviews_for_woocommerce_is_paste_enabled() ) {
			return null;
		}

		if ( ! function_exists( 'wp_upload_dir' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		if ( ! preg_match( '/^data:image\/(jpeg|png|webp);base64,/', $base64_data, $matches ) ) {
			return null;
		}

		$extension = $matches[1];
		if ( 'jpeg' === $extension ) {
			$extension = 'jpg';
		}

		$base64_body = substr( $base64_data, strpos( $base64_data, ',' ) + 1 );
		$decoded     = base64_decode( $base64_body ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! $decoded ) {
			return null;
		}

		$max_size = beplus_advanced_reviews_for_woocommerce_get_max_image_size();
		if ( strlen( $decoded ) > $max_size ) {
			return null;
		}

		$upload_dir = wp_upload_dir();
		$filename   = 'paste-' . $comment_id . '-' . wp_generate_password( 8, false ) . '.' . $extension;
		$file_path  = $upload_dir['path'] . '/' . $filename;

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$wp_filesystem->put_contents( $file_path, $decoded, FS_CHMOD_FILE );

		$file_array = array(
			'name'     => $filename,
			'tmp_name' => $file_path,
			'size'     => strlen( $decoded ),
		);

		$check = wp_check_filetype_and_ext( $file_path, $filename );
		if ( ! $check['ext'] || ! $check['type'] ) {
			$wp_filesystem->delete( $file_path );
			return null;
		}

		$allowed_types = array( 'jpg', 'jpeg', 'png', 'webp' );
		if ( ! in_array( $check['ext'], $allowed_types, true ) ) {
			$wp_filesystem->delete( $file_path );
			return null;
		}

		$attachment_id = $this->store_attachment( $comment_id, $file_array, $file_path );
		return $attachment_id;
	}

	/**
	 * Get media attached to a review.
	 *
	 * @param int $comment_id Comment ID.
	 * @return array<int, array<string, mixed>> List of attachment data.
	 */
	public function get_review_media( int $comment_id ): array {
		$cache_key = 'review_media_' . $comment_id;
		$cached    = wp_cache_get( $cache_key, 'bparfw_reviews' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT attachment_id FROM {$wpdb->prefix}bparfw_review_media WHERE comment_id = %d ORDER BY sort_order ASC",
				$comment_id
			)
		);

		if ( empty( $rows ) ) {
			return array();
		}

		$media = array();
		foreach ( $rows as $row ) {
			$attachment_id = (int) $row->attachment_id;
			$url           = $this->storage->get_url( $attachment_id );
			$thumbnail     = $this->storage->get_thumbnail_url( $attachment_id, 'thumbnail' );
			$mime_type     = $this->storage->get_mime_type( $attachment_id );

			if ( $url ) {
				$media[] = array(
					'id'        => $attachment_id,
					'url'       => $url,
					'thumbnail' => $thumbnail ?: $url,
					'mime_type' => $mime_type ?: '',
				);
			}
		}

		wp_cache_set( $cache_key, $media, 'bparfw_reviews' );

		return $media;
	}

	/**
	 * Delete all media attached to a comment.
	 *
	 * This is hooked into WordPress 'delete_comment' and 'wp_trash_comment'.
	 * It removes both the storage files AND the bparfw_review_media rows.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	public function delete_media_for_comment( int $comment_id ): void {
		$attachment_ids = $this->get_attachment_ids_for_comment( $comment_id );

		if ( empty( $attachment_ids ) ) {
			return;
		}

		foreach ( $attachment_ids as $attachment_id ) {
			$this->storage->delete( $attachment_id );

			do_action( HookManager::MEDIA_DELETED, $comment_id, $attachment_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.media_deleted'.
		}

		$this->remove_media_links( $comment_id );
	}

	/**
	 * Handle AJAX media upload.
	 *
	 * @return void
	 */
	public function handle_ajax_upload(): void {
		check_ajax_referer( 'wp_rest', '_wpnonce' );

		if ( ! isset( $_POST['comment_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing comment ID.', 'beplus-advanced-reviews-for-woocommerce' ) ) );
		}

		$comment_id = absint( $_POST['comment_id'] );

		if ( empty( $_FILES ) ) {
			wp_send_json_error( array( 'message' => __( 'No files uploaded.', 'beplus-advanced-reviews-for-woocommerce' ) ) );
		}

		$attachment_ids = array();
		foreach ( $_FILES as $input_name => $file_data ) {
			if ( ! empty( $file_data['name'] ) ) {
				$result = $this->upload_files( $comment_id, $file_data );
				$attachment_ids = array_merge( $attachment_ids, $result );
			}
		}

		if ( empty( $attachment_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Upload failed.', 'beplus-advanced-reviews-for-woocommerce' ) ) );
		}

		wp_send_json_success( array(
			'ids'   => $attachment_ids,
			'media' => $this->get_review_media( $comment_id ),
		) );
	}

	/**
	 * Process a single file upload and attach to review.
	 *
	 * @param int                 $comment_id Comment ID.
	 * @param array<string, mixed> $file       Single file array.
	 * @return int|null Attachment ID.
	 */
	private function process_upload( int $comment_id, array $file ): ?int {
		$file_type_check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		$mime_type       = $file_type_check['type'] ?? '';

		if ( ! $mime_type ) {
			return null;
		}

		$is_video = str_starts_with( $mime_type, 'video/' );

		if ( $is_video ) {
			if ( ! beplus_advanced_reviews_for_woocommerce_is_videos_enabled() ) {
				return null;
			}
			$max_size = beplus_advanced_reviews_for_woocommerce_get_max_video_size();
			$allowed  = array( 'video/mp4', 'video/webm', 'video/ogg' );
		} else {
			if ( ! beplus_advanced_reviews_for_woocommerce_is_images_enabled() ) {
				return null;
			}
			$max_size = beplus_advanced_reviews_for_woocommerce_get_max_image_size();
			$allowed  = array( 'image/jpeg', 'image/png', 'image/webp' );
		}

		$file_size = (int) ( $file['size'] ?? 0 );
		if ( $file_size > $max_size ) {
			return null;
		}

		if ( ! in_array( $mime_type, $allowed, true ) ) {
			return null;
		}

		$override = array(
			'test_form' => false,
			'mimes'     => array(
				'jpg'  => 'image/jpeg',
				'jpeg' => 'image/jpeg',
				'png'  => 'image/png',
				'webp' => 'image/webp',
				'mp4'  => 'video/mp4',
				'webm' => 'video/webm',
				'ogv'  => 'video/ogg',
			),
		);

		$result = wp_handle_upload( $file, $override );

		if ( isset( $result['error'] ) ) {
			return null;
		}

		return $this->store_attachment( $comment_id, $file, $result['file'] ?? '' );
	}

	/**
	 * Store an attachment via the storage backend and link it to the review.
	 *
	 * @param int                 $comment_id Comment ID.
	 * @param array<string, mixed> $file       File data array.
	 * @param string              $file_path  Actual file path on disk.
	 * @return int|null Attachment ID.
	 */
	private function store_attachment( int $comment_id, array $file, string $file_path ): ?int {
		global $wpdb;

		$result = $this->storage->store( $file_path, $file['name'] );

		if ( is_wp_error( $result ) ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Storage store() failed. comment_id=' . $comment_id . ' error=' . $result->get_error_message(), array( 'source' => 'beplus-advanced-reviews-for-woocommerce' ) );
			}
			return null;
		}

		$attachment_id = (int) $result;

		$this->storage->generate_metadata( $attachment_id, $file_path );

		$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . 'bparfw_review_media',
			array(
				'comment_id'    => $comment_id,
				'attachment_id' => $attachment_id,
				'sort_order'    => 0,
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		if ( false === $inserted ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( 'Failed to insert review media link. comment_id=' . $comment_id . ' attachment_id=' . $attachment_id . ' error=' . $wpdb->last_error, array( 'source' => 'beplus-advanced-reviews-for-woocommerce' ) );
			}
		}

		do_action( HookManager::MEDIA_UPLOADED, $comment_id, $attachment_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Constant resolves to 'beplus_advanced_reviews.media_uploaded'.

		return $attachment_id;
	}

	/**
	 * Get all attachment IDs linked to a comment.
	 *
	 * @param int $comment_id Comment ID.
	 * @return array<int, int>
	 */
	private function get_attachment_ids_for_comment( int $comment_id ): array {
		$cache_key = 'attachment_ids_' . $comment_id;
		$cached    = wp_cache_get( $cache_key, 'bparfw_reviews' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT attachment_id FROM {$wpdb->prefix}bparfw_review_media WHERE comment_id = %d",
				$comment_id
			)
		);

		if ( empty( $rows ) ) {
			wp_cache_set( $cache_key, array(), 'bparfw_reviews' );
			return array();
		}

		$result = array_map(
			function ( $row ) {
				return (int) $row->attachment_id;
			},
			$rows
		);

		wp_cache_set( $cache_key, $result, 'bparfw_reviews' );

		return $result;
	}

	/**
	 * Remove all bparfw_review_media links for a comment.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	private function remove_media_links( int $comment_id ): void {
		global $wpdb;

		$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'bparfw_review_media',
			array( 'comment_id' => $comment_id ),
			array( '%d' )
		);

		wp_cache_delete( 'review_media_' . $comment_id, 'bparfw_reviews' );
		wp_cache_delete( 'attachment_ids_' . $comment_id, 'bparfw_reviews' );
		wp_cache_delete( 'has_images_' . $comment_id, 'bparfw_reviews' );
	}

	/**
	 * Normalize the $_FILES array into a cleaner structure.
	 *
	 * @param array<string, mixed> $files $_FILES array.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_files_array( array $files ): array {
		$normalized = array();

		if ( ! is_array( $files['name'] ) ) {
			return array( $files );
		}

		foreach ( $files['name'] as $index => $name ) {
			$normalized[] = array(
				'name'     => $name,
				'type'     => $files['type'][ $index ] ?? '',
				'tmp_name' => $files['tmp_name'][ $index ] ?? '',
				'error'    => $files['error'][ $index ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $files['size'][ $index ] ?? 0,
			);
		}

		return $normalized;
	}
}
