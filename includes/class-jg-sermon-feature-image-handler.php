<?php
// File: includes/class-jg-sermon-feature-image-handler.php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Security check: exit if accessed directly.
}

// Log that this file has been loaded.
error_log( 'JG Sermon Feature Image Handler file loaded.' );

/**
 * Class JG_Sermon_Feature_Image_Handler
 *
 * Handles setting the featured image of a post based on a congregation term's meta image.
 */
class JG_Sermon_Feature_Image_Handler {

	/**
	 * Append a message to the sermon_error_field custom meta for the given post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $message Message to log.
	 */
	private function save_error( $post_id, $message ) {
		$existing = get_post_meta( $post_id, 'sermon_error_field', true );
		if ( ! $existing ) {
			$existing = '';
		}
		// Add a timestamp to each message.
		$timestamp = current_time( 'mysql' );
		$existing .= "\n[$timestamp] " . $message;
		update_post_meta( $post_id, 'sermon_error_field', $existing );
	}

	/**
	 * Set the post's featured image based on the congregation's term meta.
	 *
	 * @param int $post_id The ID of the post to update.
	 * @param int $congregation_term_id The term ID of the congregation.
	 */
	public function set_feature_image_from_congregation( $post_id, $congregation_term_id ) {

		$this->save_error( $post_id, "Entered set_feature_image_from_congregation() method." );
		$this->save_error( $post_id, "=== Starting featured image process ===" );

		// Validate the term exists in the expected taxonomy.
		$term = get_term( $congregation_term_id, 'congregations-tags' );
		if ( ! $term || is_wp_error( $term ) ) {
			$this->save_error( $post_id, "Invalid term ID: " . $congregation_term_id );
			return;
		}
		$this->save_error( $post_id, "Valid term retrieved: " . $term->name );

		// Get the value stored in the term meta for this congregation.
		$image_value = get_term_meta( $congregation_term_id, 'congregation_image_link', true );
		if ( ! $image_value ) {
			$this->save_error( $post_id, "No congregation_image_link found for term ID " . $congregation_term_id );
			return;
		}
		$this->save_error( $post_id, "Raw congregation_image_link meta value: " . $image_value );

		// If the value is numeric, assume it's a valid Media ID and use it directly.
		if ( is_numeric( $image_value ) ) {
			$attachment_id = absint( $image_value );
			$this->save_error( $post_id, "Using media ID as attachment ID: " . $attachment_id );
		} else {
			// Otherwise, assume it's a URL and try to obtain the attachment ID.
			$image_url = esc_url_raw( $image_value );
			$attachment_id = attachment_url_to_postid( $image_url );
			if ( ! $attachment_id ) {
				$this->save_error( $post_id, "No valid attachment found for URL, attempting sideload." );
				// Optionally, perform media sideload here...
				// (This code block is only necessary if your field might sometimes be a URL.)
			}
		}

		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
			$this->save_error( $post_id, "Failed to obtain a valid attachment ID." );
			return;
		}

		$set_thumb = set_post_thumbnail( $post_id, $attachment_id );
		if ( $set_thumb ) {
			$this->save_error( $post_id, "Featured image set successfully for post ID " . $post_id . " using attachment ID " . $attachment_id );
		} else {
			$this->save_error( $post_id, "Failed to set the featured image for post ID " . $post_id );
		}

		$this->save_error( $post_id, "=== Finished featured image process ===" );
	}
}

error_log('Testing image handler with hard-coded term ID 125492.');
$feature_image_handler = new JG_Sermon_Feature_Image_Handler();
$feature_image_handler->set_feature_image_from_congregation( $post_id, 125492 );

if ( ! empty( $request['congregations-tags-select'] ) ) {
	$congregation_term_id = absint( $request['congregations-tags-select'] );
	error_log( 'JG Sermon Link Fixer: Found congregations-tags-select value: ' . $congregation_term_id );
	if ( $congregation_term_id ) {
		$feature_image_handler = new JG_Sermon_Feature_Image_Handler();
		$feature_image_handler->set_feature_image_from_congregation( $post_id, $congregation_term_id );
	}
} else {
	error_log( 'JG Sermon Link Fixer: No congregations-tags-select field found in the request.' );
}