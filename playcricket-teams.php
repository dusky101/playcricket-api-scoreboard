<?php
/**
 * Plugin Name: PlayCricket Easy API Connector
 * Plugin URI:  https://github.com/dusky101/playcricket-connector
 * Description: Fetch and display teams and results from PlayCricket API.
 * Version: 1.3
 * Author: TechWhisperers PE
 * Author URI:  https://techwhispererspod.com/wordpress-plugins
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

define('PLAYCRICKET_PLUGIN_URL', plugin_dir_url(__FILE__));

// Initialize a global variable to store the settings page hook suffix.
global $playcricket_settings_page;

require_once plugin_dir_path(__FILE__) . "addons/playcricket-league.php";

// No global enqueue function here for scripts and styles; they will be enqueued within the shortcode handler(s) directly.

require_once plugin_dir_path(__FILE__) . "admin/admin-settings.php";
require_once plugin_dir_path(__FILE__) . "admin/admin-club.php";

// Register the scripts for the admin side of the site.
function playcricket_admin_enqueue_scripts($hook)
{
    global $playcricket_settings_page;

    // Check if we are on the plugin's settings page.
    if ($hook != $playcricket_settings_page) {
        return;
    }

    wp_enqueue_style("wp-color-picker");
    wp_enqueue_media();
    wp_enqueue_style(
        "playcricket-admin-style",
        plugin_dir_url(__FILE__) . "css/admin-style.css"
    );
    wp_enqueue_script(
        "playcricket-admin-custom",
        plugin_dir_url(__FILE__) . "js/admin-custom.js",
        ["jquery", "wp-color-picker"],
        null,
        true
    );
    wp_enqueue_script(
        "playcricket-club-search",
        plugin_dir_url(__FILE__) . "js/club-search.js",
        ["jquery"],
        null,
        true
    );

    wp_localize_script("playcricket-admin-custom", "playcricket_ajax", [
        "ajax_url" => admin_url("admin-ajax.php"),
        "nonce" => wp_create_nonce("playcricket_nonce"),
    ]);
}
add_action("admin_enqueue_scripts", "playcricket_admin_enqueue_scripts");

// Function to create the admin menu page.
function playcricket_teams_menu()
{
    global $playcricket_settings_page;
    $playcricket_settings_page = add_menu_page(
        "PlayCricket Settings",
        "PlayCricket",
        "manage_options",
        "playcricket-settings",
        "playcricket_teams_settings_page"
    );
}
add_action("admin_menu", "playcricket_teams_menu");

// Include the additional files.
require_once plugin_dir_path(__FILE__) . 'api/teamsfetch.php';
require_once plugin_dir_path(__FILE__) . 'api/matchfetch.php';
require_once plugin_dir_path(__FILE__) . 'api/playersfetch.php';
require_once plugin_dir_path(__FILE__) . "b-and-b.php";
require_once plugin_dir_path(__FILE__) . 'shortcode/shortcode.php';


function playcricket_teams_settings_page() {
    echo '<div class="wrap">';
    echo "<h1>PlayCricket Teams Settings</h1>";
    
    // Add instructional text here
    echo "<p>To begin using this plugin, you must first have an API key from Play-Cricket. ";
    echo "You need to be an administrator of your club on Play-Cricket to obtain an API key. ";
    echo "Visit <a href='https://www.play-cricket.com' target='_blank'>Play-Cricket's website</a> ";
    echo "to sign an agreement and receive your session key.</p>";
    
    echo '<form id="playcricket-settings-form" method="post" action="options.php">';
    settings_fields("playcricket_settings_group");
    do_settings_sections("playcricket-teams-settings");
    // The actual search form and additional settings are rendered within admin-club.php
    playcricket_club_search_form(); // This function will handle its own internal conditions
    echo "</form>";
    echo "</div>";
}


// Function to add a settings link to the plugin in the plugins list
function playcricket_add_settings_link($links)
{
    $settingsLink =
        '<a href="' .
        admin_url("admin.php?page=playcricket-settings") .
        '">' .
        __("Settings") .
        "</a>";
    array_unshift($links, $settingsLink);
    return $links;
}

// Hook the above function to the 'plugin_action_links_' filter
add_filter(
    "plugin_action_links_" . plugin_basename(__FILE__),
    "playcricket_add_settings_link"
);

