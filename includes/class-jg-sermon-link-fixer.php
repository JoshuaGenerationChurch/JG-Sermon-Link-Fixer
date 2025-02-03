<?php
// File: includes/class-jg-sermon-link-fixer.php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Security check: exit if accessed directly.
}

/**
 * Class JG_Sermon_Link_Fixer
 *
 * Handles:
 *  - Updating audio file links (especially Dropbox -> direct).
 *  - Removing "Protected:" prefix from titles.
 *  - Enabling shortcode rendering in JetFormBuilder forms.
 *  - Shortcode to retrieve audio file link by post_id.
 */
class JG_Sermon_Link_Fixer {

	/**
	 * Constructor: Set up hooks.
	 */
	public function __construct() {

		// Load the congregation feature image handler.
		require_once JG_SERMON_LINK_FIXER_PLUGIN_DIR . 'includes/class-jg-sermon-feature-image-handler.php';

		// Update audio file link (Dropbox -> direct) and handle featured image.
		add_filter(
			'jet-form-builder/custom-filter/update-link',
			[ $this, 'update_audio_file_link_filter' ],
			10,
			3
		);

		// New hook dedicated to updating the featured image.
		add_filter(
			'jet-form-builder/custom-filter/update-feature-image',
			[ $this, 'update_feature_image_handler' ],
			10,
			3
		);

		// Remove 'Protected:' prefix from password-protected post titles.
		add_filter( 'protected_title_format', [ $this, 'remove_protected_prefix' ] );

		// Enable shortcode rendering in JetFormBuilder.
		add_filter( 'jet-form-builder/render-shortcodes', '__return_true' );

		// Register shortcode [get_audio_file_link post_id="123"].
		add_shortcode( 'get_audio_file_link', [ $this, 'get_audio_file_link_shortcode' ] );
	}

	/**
	 * Filter: update_audio_file_link_filter
	 *
	 * This method:
	 *  - Converts a Dropbox link into a direct-download link and saves it as post meta.
	 *  - Checks if a congregation is selected in the form submission.
	 *  - Delegates setting the congregation image as the featured image to the feature image handler.
	 *
	 * @param mixed  $result         The current filter result.
	 * @param array  $request        The form submission data.
	 * @param object $action_handler The JetFormBuilder action handler object.
	 *
	 * @return mixed $result (unchanged, since we only do post-meta updates here).
	 */
	public function update_audio_file_link_filter( $result, $request, $action_handler ) {

		// Log the entire request array to check for the expected keys.
		error_log( 'JG Sermon Link Fixer - Request Data: ' . print_r( $request, true ) );

		// Ensure the 'audio_file' field exists and sanitize the URL.
		if ( empty( $request['audio_file'] ) ) {
			error_log( 'JG Sermon Link Fixer: audio_file field is empty.' );
			return $result; // Nothing to process.
		}

		$audio_file = filter_var( $request['audio_file'], FILTER_SANITIZE_URL );
		// Remove trailing ampersands or extra spaces that might interfere with URL parsing.
		$audio_file = rtrim( $audio_file, " &" );

		if ( ! filter_var( $audio_file, FILTER_VALIDATE_URL ) ) {
			error_log( 'JG Sermon Link Fixer: Invalid audio file URL provided.' );
			return $result;
		}

		// If this is a Dropbox URL, replace the domain and append the dl=1 parameter.
		if ( strpos( $audio_file, 'dropbox.com' ) !== false ) {
			// Replace "www.dropbox.com" with "dl.dropboxusercontent.com"
			$modified_url = str_ireplace( 'www.dropbox.com', 'dl.dropboxusercontent.com', $audio_file );
			// Append the dl=1 parameter.
			if ( strpos( $modified_url, '?' ) !== false ) {
				$dropbox_link = $modified_url . '&dl=1';
			} else {
				$dropbox_link = $modified_url . '?dl=1';
			}
		} else {
			$dropbox_link = $audio_file;
		}

		// Retrieve the post ID from the action handler or form data.
		$post_id = 0;
		if ( method_exists( $action_handler, 'get_inserted_post_id' ) ) {
			$post_id = absint( $action_handler->get_inserted_post_id() );
		}
		if ( ! $post_id && ! empty( $request['inserted_post_id'] ) ) {
			$post_id = absint( $request['inserted_post_id'] );
		}
		if ( ! $post_id ) {
			error_log( 'JG Sermon Link Fixer: No post ID found. Skipping audio meta update.' );
			return $result;
		}

		// Update the "audio_file" post meta if the post exists.
		if ( get_post_status( $post_id ) ) {
			update_post_meta( $post_id, 'audio_file', esc_url_raw( $dropbox_link ) );
			error_log( 'JG Sermon Link Fixer: Updated "audio_file" meta for post ID ' . $post_id );
		} else {
			error_log( 'JG Sermon Link Fixer: Post does not exist for ID ' . $post_id );
		}

		// Check if a congregation has been selected and set the featured image.
		if ( ! empty( $request['congregations_tags'] ) ) {
			$congregation_term_id = absint( $request['congregations_tags'] );
			error_log( 'JG Sermon Link Fixer: Found congregations_tags value: ' . $congregation_term_id );
			if ( $congregation_term_id ) {
				$feature_image_handler = new JG_Sermon_Feature_Image_Handler();
				$feature_image_handler->set_feature_image_from_congregation( $post_id, $congregation_term_id );
			}
		} else {
			error_log( 'JG Sermon Link Fixer: No congregations_tags field found in the request.' );
		}

		return $result;
	}

	/**
	 * New filter method: update_feature_image_handler.
	 *
	 * This method retrieves the post ID and the congregation term field from the request,
	 * then delegates setting the featured image to the feature image handler.
	 *
	 * @param mixed  $result         The current filter result.
	 * @param array  $request        The form submission data.
	 * @param object $action_handler The JetFormBuilder action handler object.
	 *
	 * @return mixed $result (unchanged, since we only do post-meta updates here).
	 */
	public function update_feature_image_handler( $result, $request, $action_handler ) {
		// Retrieve the post ID from the action handler or form data.
		$post_id = 0;
		if ( method_exists( $action_handler, 'get_inserted_post_id' ) ) {
			$post_id = absint( $action_handler->get_inserted_post_id() );
		}
		if ( ! $post_id && ! empty( $request['inserted_post_id'] ) ) {
			$post_id = absint( $request['inserted_post_id'] );
		}
		
		if ( ! $post_id ) {
			error_log( 'JG Sermon Link Fixer: No post ID found for image update.' );
			throw new Action_Exception( 'Invalid post ID for image update.' );
		}
		
		// Check if a congregation has been selected using the proper field name.
		if ( ! empty( $request['congregations-tags-select'] ) ) {
			$congregation_term_id = absint( $request['congregations-tags-select'] );
			error_log( 'JG Sermon Link Fixer: Found congregations-tags-select value: ' . $congregation_term_id );
			if ( $congregation_term_id ) {
				$feature_image_handler = new JG_Sermon_Feature_Image_Handler();
				$feature_image_handler->set_feature_image_from_congregation( $post_id, $congregation_term_id );
			} else {
				throw new Action_Exception( 'Invalid congregation selected for image update.' );
			}
		} else {
			error_log( 'JG Sermon Link Fixer: No congregations-tags-select field found in the request for image update.' );
			throw new Action_Exception( 'Missing congregation selection for image update.' );
		}
		
		return $result;
	}

	/**
	 * Remove 'Protected:' prefix from password-protected posts.
	 *
	 * @param string $title Current protected title format.
	 * @return string The modified title format (just '%s').
	 */
	public function remove_protected_prefix( $title ) {
		return '%s';
	}

	/**
	 * Shortcode: [get_audio_file_link post_id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML-escaped audio file URL or empty string if not found.
	 */
	public function get_audio_file_link_shortcode( $atts ) {
		$atts    = shortcode_atts( [ 'post_id' => 0 ], $atts, 'get_audio_file_link' );
		$post_id = absint( $atts['post_id'] );
		if ( ! $post_id ) {
			return '';
		}
		$audio_link = get_post_meta( $post_id, 'audio_file', true );
		if ( ! $audio_link ) {
			return '';
		}
		return esc_url( $audio_link );
	}
}

