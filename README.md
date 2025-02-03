# JG Sermon Link Fixer 🎤🔧

JG Sermon Link Fixer is a custom WordPress plugin designed to streamline your sermon post management. It automatically converts Dropbox audio file links into direct-download links, updates post meta with the corrected audio URL, and sets the featured image based on congregation data. 

## Features

- **Audio Link Conversion**:  
  Converts Dropbox links into direct-download URLs and updates the post meta with the sanitized link.

- **Featured Image Handler**:  
  Sets the post's featured image using a congregation's term meta. If using JetEngine, the plugin accepts a Media ID from the custom field `congregation_image_link` and uses it directly to set the featured image.

- **Title Cleanup**:  
  Removes the "Protected:" prefix from password-protected post titles.

- **Shortcode Support**:  
  Enables shortcode rendering in JetFormBuilder forms and provides a `[get_audio_file_link post_id="123"]` shortcode to retrieve the audio file link.

- **Detailed Logging**:  
  Logs key actions and errors to a custom meta field (`sermon_error_field`) for easier debugging.

## How It Works: Step by Step

1. **Plugin Initialization**

   - The plugin loads on WordPress startup via the `plugins_loaded` hook.
   - It defines constants, loads translations, and includes the necessary class files.

2. **Hook Registration**

   - The main class `JG_Sermon_Link_Fixer` instantiates and registers several hooks:
     - **`jet-form-builder/custom-filter/update-link`**:  
       Processes the audio file URL. It sanitizes and validates the provided URL, converts Dropbox links to direct-download links, and updates the post meta.
     - **`jet-form-builder/custom-filter/update-feature-image`**:  
       Dedicated to updating the featured image. It checks for the congregation selection (using the field name `congregations-tags-select`), retrieves the associated term meta, and sets the featured image.
     - **Title Filter and Shortcode Support**:  
       It removes "Protected:" from post titles, enables shortcode rendering, and registers a shortcode for retrieving the updated audio file link.

3. **Audio File URL Processing**
   - The `update_audio_file_link_filter()` method:
     - Logs the incoming request data.
     - Validates and sanitizes the `audio_file` field.
     - If the URL points to Dropbox, converts it to a direct download link.
     - Updates the post meta with the sanitized, converted URL.
4. **Featured Image Update**

   - The `update_feature_image_handler()` and `JG_Sermon_Feature_Image_Handler::set_feature_image_from_congregation()` methods:
     - Retrieve the post ID from the form submission.
     - Check for the congregation selection field.
     - Load the term using the congregation term ID from the `congregations-tags-select` field.
     - Retrieve the custom field `congregation_image_link`, which (if using JetEngine) contains a Media ID.
     - Use the Media ID directly to set the featured image with WordPress's `set_post_thumbnail()` function.
     - Logs each step, making it easy to debug if issues arise.

5. **Shortcode Rendering**
   - Provides a shortcode `[get_audio_file_link post_id="123"]` that outputs the audio file link for the specified post ID.

## Useful Links

- [JetFormBuilder Documentation](https://jetformbuilder.com/docs/) 🚀
- [JetEngine Documentation](https://crocoblock.com/plugins/jetengine/) 📸
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/) 📚

## Getting Started

1. Upload the plugin files to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your JetFormBuilder forms to use the expected field names (`audio_file` for audio URLs and `congregations-tags-select` for congregation selection).
4. Test by submitting a form and checking the post meta and featured image assignment.

Enjoy a smoother workflow for managing your sermon audio and images! 🎉
