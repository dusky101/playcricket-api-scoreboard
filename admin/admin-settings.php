<?php
// Ensure this file is being included by a parent file
if (!defined("ABSPATH")) {
    exit();
}

function playcricket_register_settings()
{
    add_settings_section(
        "playcricket_setting_section",
        "API Settings",
        null,
        "playcricket-teams-settings"
    );

    // Register API Key and Site ID settings
    register_setting("playcricket_settings_group", "playcricket_api_key");
    add_settings_field(
        "playcricket-api-key",
        "API Key",
        "playcricket_api_key_callback",
        "playcricket-teams-settings",
        "playcricket_setting_section"
    );

    register_setting("playcricket_settings_group", "playcricket_site_id");
    add_settings_field(
        "playcricket-site-id",
        "Site ID",
        "playcricket_site_id_callback",
        "playcricket-teams-settings",
        "playcricket_setting_section"
    );
}

function playcricket_api_key_callback()
{
    $api_key = get_option("playcricket_api_key");
    echo "<input type='text' id='playcricket_api_key' name='playcricket_api_key' value='" .
        esc_attr($api_key) .
        "' class='regular-text'>";
}

function playcricket_site_id_callback()
{
    $site_id = get_option("playcricket_site_id");
    echo "<input type='text' id='playcricket_site_id' name='playcricket_site_id' value='" .
        esc_attr($site_id) .
        "' class='regular-text'>";
    // Add the Confirm button below the Site ID field
    echo '<button id="confirm-settings" type="button" class="button button-secondary">Confirm</button>';
}

add_action("admin_init", "playcricket_register_settings");

// Add this at the end of your admin-settings.php file
function playcricket_save_api_and_site_id() {
    // Check for the nonce for security
    check_ajax_referer('playcricket_nonce', 'nonce');

    // Validate user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
        wp_die();
    }

    // Sanitize and save the provided API Key and Site ID
    $api_key = sanitize_text_field($_POST['api_key'] ?? '');
    $site_id = sanitize_text_field($_POST['site_id'] ?? '');

    if (!empty($api_key) && !empty($site_id)) {
        update_option('playcricket_api_key', $api_key);
        update_option('playcricket_site_id', $site_id);

        // Success response
        wp_send_json_success(['message' => 'API Key and Site ID updated successfully.']);
    } else {
        // Error response
        wp_send_json_error(['message' => 'API Key or Site ID cannot be empty.']);
    }

    wp_die();
}
add_action('wp_ajax_playcricket_save_api_site_id', 'playcricket_save_api_and_site_id');
