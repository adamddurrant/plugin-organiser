<?php

/**
 * Plugin Name: Notes for Plugins
 * Description: Allows admins to add and delete notes directly underneath plugins in the admin menu.
 * Version: 1.1
 * Author: Adam Durrant
 */

class NotesForPlugins {

    private static $instance = null;

    private function __construct() {
        add_filter('plugin_row_meta', [$this, 'addNotesSection'], 10, 4);
        add_action('wp_ajax_save_plugin_notes', [$this, 'savePluginNotes']);
        add_action('wp_ajax_delete_plugin_notes', [$this, 'deletePluginNotes']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScriptsAndStyles']);
        add_action('admin_footer', [$this, 'addNonceToAdminFooter']);
        add_action('admin_init', [$this, 'checkDirectAccess']);
        add_action('wp_ajax_save_plugin_row_color', [$this, 'savePluginRowColor']);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addNotesSection($plugin_meta, $plugin_file, $plugin_data, $status) {
        $notes_key = 'plugin_notes_' . md5($plugin_file);
        $color_key = 'plugin_row_color_' . md5($plugin_file);
        $notes = get_option($notes_key, '');
        $selected_color = get_option($color_key, '');
        $add_note_link_style = $notes ? 'display: none;' : '';

        if (!empty($selected_color)) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    const pluginRow = document.querySelector("tr[data-plugin=\'' . esc_attr($plugin_file) . '\']");
                    if (pluginRow) {
                        pluginRow.classList.add("' . esc_attr($selected_color) . '");
                    }
                });
            </script>';
        }

        $plugin_meta[] = '<div class="plugin-note-container">
            <a type="button" style="cursor: pointer; margin-top: 5px; display: block; ' . esc_attr($add_note_link_style) . '" class="toggle-plugin-notes">' . __('+ Add Note', 'notes-for-posts') . '</a>
            <textarea class="plugin-notes" data-plugin-key="' . esc_attr($notes_key) . '" style="width:100%; height:50px; display:none;">' . esc_textarea($notes) . '</textarea>
            <a class="save-plugin-notes button" style="margin-top: 5px; display:none;">' . __('Save Note', 'notes-for-posts') . '</a>
            <div class="saved-note" style="margin-top: 5px; display: ' . ($notes ? 'block' : 'none') . ';"><strong>' . esc_textarea($notes) . '</strong></div>
            <a class="delete-plugin-notes" style="margin-top: 5px; color:red; cursor: pointer; display:' . ($notes ? 'inline-block' : 'none') . ';">' . __('- Delete Note', 'notes-for-posts') . '</a>
            <select class="plugin-row-color" data-plugin-key="' . esc_attr($color_key) . '" style="margin-top: 5px;">
                <option value="">' . __('Select Color', 'notes-for-posts') . '</option>
                <option value="red"' . selected($selected_color, 'red', false) . '>' . __('Red', 'notes-for-posts') . '</option>
                <option value="green"' . selected($selected_color, 'green', false) . '>' . __('Green', 'notes-for-posts') . '</option>
                <option value="purple"' . selected($selected_color, 'purple', false) . '>' . __('Purple', 'notes-for-posts') . '</option>
            </select>
        </div>';

        return $plugin_meta;
    }

    public function savePluginNotes() {
        if (!$this->validateAjaxRequest('notes_for_plugins_nonce')) {
            wp_send_json_error(__('Invalid request.', 'notes-for-posts'));
        }

        $notes_key = sanitize_key($_POST['notes_key']);
        $notes_value = wp_kses_post($_POST['notes_value']);
        update_option($notes_key, $notes_value);

        wp_send_json_success([
            'message' => __('Notes saved.', 'notes-for-posts'),
            'updated_note' => esc_textarea(get_option($notes_key, ''))
        ]);
    }

    public function deletePluginNotes() {
        if (!$this->validateAjaxRequest('notes_for_plugins_nonce')) {
            wp_send_json_error(__('Invalid request.', 'notes-for-posts'));
        }

        $notes_key = sanitize_key($_POST['notes_key']);
        delete_option($notes_key);

        wp_send_json_success(['message' => __('Notes deleted.', 'notes-for-posts')]);
    }

    public function enqueueScriptsAndStyles($hook_suffix) {
        if ($hook_suffix === 'plugins.php') {
            wp_enqueue_script('notes-for-plugins', plugin_dir_url(__FILE__) . '/notes-for-plugins.js', ['jquery'], null, true);
            wp_localize_script('notes-for-plugins', 'pluginNotes', [
                'nonce' => wp_create_nonce('notes_for_plugins_nonce'),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);

            wp_enqueue_style(
                'custom-plugin-styles',
                plugin_dir_url(__FILE__) . 'styles.css',
                [],
                '1.0.0'
            );
        }
    }

    public function addNonceToAdminFooter() {
        $screen = get_current_screen();
        if ($screen && 'plugins.php' === $screen->id) {
            wp_nonce_field('notes_for_plugins_nonce', 'notes_for_plugins_nonce_field');
        }
    }

    public function checkDirectAccess() {
        $screen = get_current_screen();
        if ($screen && 'plugins.php' === $screen->id) {
            if (!isset($_GET['action'], $_GET['plugin_notes_nonce']) || !wp_verify_nonce($_GET['plugin_notes_nonce'], 'notes_for_plugins_nonce')) {
                wp_die(__('Invalid nonce', 'notes-for-posts'));
            }

            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have permission to access this page.', 'notes-for-posts'));
            }
        }
    }

    public function savePluginRowColor() {
        if (!$this->validateAjaxRequest('notes_for_plugins_nonce')) {
            wp_send_json_error(__('Invalid request.', 'notes-for-posts'));
        }

        $color_key = sanitize_key($_POST['color_key']);
        $selected_color = sanitize_text_field($_POST['selected_color']);
        update_option($color_key, $selected_color);

        wp_send_json_success(['message' => __('Row color updated.', 'notes-for-posts')]);
    }

    private function validateAjaxRequest($nonce_action) {
        return isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], $nonce_action) && current_user_can('manage_options');
    }
}

// Initialize the plugin.
NotesForPlugins::getInstance();
