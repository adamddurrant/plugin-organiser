<?php

/**
 * Plugin Name: Notes for Plugins
 * Description: Allows admins to add and delete notes directly underneath plugins in the admin menu.
 * Version: 1.1
 * Author: Adam Durrant
 */

// Add meta box for notes under posts.
add_action('add_meta_boxes', function () {
    add_meta_box(
        'post_notes',
        __('Post Notes', 'notes-for-posts'),
        'render_notes_meta_box',
        'post',
        'normal',
        'low'
    );
});

// Save notes when a post is saved.
add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['post_notes'])) {
        update_post_meta($post_id, '_post_notes', wp_kses_post($_POST['post_notes']));
    }
});

// Render the notes meta box.
function render_notes_meta_box($post) {
    $notes = get_post_meta($post->ID, '_post_notes', true);
    $add_note_button_style = $notes ? 'display: none;' : '';  // Hide button if a note exists
    echo '<button type="button" id="toggle-post-notes" class="button" style="' . esc_attr($add_note_button_style) . '">' . __('Add Note', 'notes-for-posts') . '</button>';
    echo '<textarea style="width: 100%; height: 100px; display: none;" id="post-notes" name="post_notes">' . esc_textarea($notes) . '</textarea>';
}

// Add notes section to the plugins page.
add_filter('plugin_row_meta', function ($plugin_meta, $plugin_file, $plugin_data, $status) {
    $notes_key = 'plugin_notes_' . md5($plugin_file);
    $notes = get_option($notes_key, '');
    $add_note_link_style = $notes ? 'display: none;' : '';  // Hide link if a note exists

    $plugin_meta[] = '<div class="plugin-note-container">
        <a type="button" style="cursor: pointer; margin-top: 5px; display: block; ' . esc_attr($add_note_link_style) . '" class="toggle-plugin-notes">' . __('+ Add Note', 'notes-for-posts') . '</a>
        <textarea class="plugin-notes" data-plugin-key="' . esc_attr($notes_key) . '" style="width:100%; height:50px; display:none;">' . esc_textarea($notes) . '</textarea>
        <a class="save-plugin-notes button" style="margin-top: 5px; display:none;">' . __('Save Note', 'notes-for-posts') . '</a>
        <div class="saved-note" style="margin-top: 5px; display: ' . ($notes ? 'block' : 'none') . ';"><strong>' . esc_textarea($notes) . '</strong></div>
        <a class="delete-plugin-notes" style="margin-top: 5px; color:red; cursor: pointer; display:' . ($notes ? 'inline-block' : 'none') . ';">' . __('- Delete Note', 'notes-for-posts') . '</a>
    </div>';

    return $plugin_meta;
}, 10, 4);

// Save plugin notes via AJAX.
add_action('wp_ajax_save_plugin_notes', function () {
    if (!isset($_POST['notes_key'], $_POST['notes_value'], $_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'notes_for_plugins_nonce')) {
        wp_send_json_error(__('Invalid request.', 'notes-for-posts'));
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to manage options.', 'notes-for-posts'));
    }

    $notes_key = sanitize_key($_POST['notes_key']);  // Ensure valid key format
    $notes_value = wp_kses_post($_POST['notes_value']);

    update_option($notes_key, $notes_value);

    // Get the updated note for response
    $updated_note = esc_textarea(get_option($notes_key, ''));

    wp_send_json_success([
        'message' => __('Notes saved.', 'notes-for-posts'),
        'updated_note' => $updated_note, // Return the updated note
    ]);
});

// Delete plugin notes via AJAX.
add_action('wp_ajax_delete_plugin_notes', function () {
    if (!isset($_POST['notes_key'], $_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'notes_for_plugins_nonce')) {
        wp_send_json_error(__('Invalid request.', 'notes-for-posts'));
    }

    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('You do not have permission to manage options.', 'notes-for-posts'));
    }

    $notes_key = sanitize_key($_POST['notes_key']);

    // Delete the note
    delete_option($notes_key);

    wp_send_json_success([
        'message' => __('Notes deleted.', 'notes-for-posts'),
    ]);
});

// Enqueue Scripts and Localize Nonce
add_action('admin_enqueue_scripts', function ($hook_suffix) {
    if ('plugins.php' === $hook_suffix) {
        wp_enqueue_script('notes-for-plugins', plugin_dir_url(__FILE__) . '/notes-for-plugins.js', ['jquery'], null, true);
        wp_localize_script('notes-for-plugins', 'pluginNotes', [
            'nonce' => wp_create_nonce('notes_for_plugins_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }
});

// Add Nonce to Admin Footer for Plugin Page
add_action('admin_footer', function () {
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && 'plugins.php' === $screen->id) {
            wp_nonce_field('notes_for_plugins_nonce', 'notes_for_plugins_nonce_field');
        }
    }
});

// Check permissions and nonces for direct access (if needed).
add_action('admin_init', function () {
    // Ensure this only runs on the admin screen
    if (is_admin() && function_exists('get_current_screen')) {
        $screen = get_current_screen();
        if ($screen && 'plugins.php' === $screen->id) {
            if (!isset($_GET['action']) || !isset($_GET['plugin_notes_nonce'])) {
                return;
            }

            // Verify nonce to prevent unauthorized direct access
            if (!wp_verify_nonce($_GET['plugin_notes_nonce'], 'notes_for_plugins_nonce')) {
                wp_die(__('Invalid nonce', 'notes-for-posts'));
            }

            // Check permissions for accessing plugin actions
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have permission to access this page.', 'notes-for-posts'));
            }
        }
    }
});
