<?php
// admin-club.php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Function to display the club search form
function playcricket_club_search_form() {
    // Get the options from the database
    $api_key = get_option('playcricket_api_key');
    $site_id = get_option('playcricket_site_id');
    $club_color = get_option('playcricket_club_color', '#f1f1f1'); // Default color
    $club_crest_url = get_option('playcricket_club_crest_url'); // Get the crest URL

    // Check if the API key and Site ID are set and not empty.
    if (!empty($api_key) && !empty($site_id)) {
        ?>
        <div id="playcricket-club-search-container">
            <h2>Club Customisation</h2>
            <input type="text" id="club-search-input" placeholder="Enter club name" value="<?php echo esc_attr(get_option('playcricket_selected_club_name')); ?>">
            <button type="button" id="club-search-button">Search</button> <!-- Search button -->
            <div id="club-search-results"></div> <!-- Container for search results -->

            <h3>Select Club Colour</h3>
            <input type="text" id="club-color" class="color-picker" name="playcricket_club_color" value="<?php echo esc_attr($club_color); ?>">

            <h3>Select Club Crest</h3>
            <button type="button" id="choose-image">Choose Club Crest</button>
            <input type="hidden" id="image-url" name="playcricket_club_crest_url" value="<?php echo esc_url($club_crest_url); ?>">
            <div id="image-preview"><?php if ($club_crest_url) : ?><img src="<?php echo esc_url($club_crest_url); ?>" style="max-width: 100%; height: auto;"><?php endif; ?></div>

            <?php submit_button('Save Changes'); ?> <!-- Submit button for saving the settings -->
        </div>
        <?php
        // Include the script to handle the client-side logic for the search and color picker
        playcricket_enqueue_club_search_script();
    }
}

// Function to enqueue and localize the client-side search script
function playcricket_enqueue_club_search_script() {
    wp_enqueue_script(
        'playcricket-club-search',
        plugin_dir_url(__FILE__) . 'js/club-search.js',
        array('jquery', 'wp-color-picker'), // Dependencies
        null, // Version - null will prevent caching issues
        true  // In footer
    );

    wp_localize_script('playcricket-club-search', 'playcricket_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('playcricket_nonce'),
    ));
}

// Register AJAX action for searching clubs
add_action('wp_ajax_playcricket_search_clubs', 'playcricket_search_clubs_callback');

function playcricket_search_clubs_callback() {
    // Verify nonce for security
    check_ajax_referer('playcricket_nonce', 'nonce');

    // Retrieve the search term and API token from the request
    $searchTerm = strtolower(sanitize_text_field($_POST['searchTerm']));
    $apiToken = get_option('playcricket_api_key');

    // Check if the API token is set
    if (empty($apiToken)) {
        wp_send_json_error(array('message' => 'API token is not set.'));
        wp_die();
    }

    // Construct the API URL
    $apiUrl = "https://www.play-cricket.com/api/v2/clubs.json?api_token={$apiToken}";
    $response = wp_remote_get($apiUrl);

    // Check for errors in the API response
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to retrieve clubs.'));
    } else {
        // Retrieve the body of the response and decode the JSON
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // If the response does not contain the 'clubs' key, return an error
        if (!isset($data['clubs'])) {
            wp_send_json_error(array('message' => 'Invalid API response.'));
            wp_die();
        }
        
        // Filter the clubs based on the search term
        $filteredClubs = array_filter($data['clubs'], function ($club) use ($searchTerm) {
            // Return true if the club name contains the search term
            return stripos($club['name'], $searchTerm) !== false;
        });

        // Return the filtered clubs in the JSON response
        wp_send_json_success(array('clubs' => array_values($filteredClubs)));
    }

    // End the execution to ensure a proper AJAX response
    wp_die();
}
add_action('wp_ajax_playcricket_search_clubs', 'playcricket_search_clubs_callback');

// AJAX handler for saving the selected club details
function playcricket_save_selected_club_callback() {
    // Verify nonce for security
    check_ajax_referer('playcricket_nonce', 'nonce');

    // Sanitize and save the data sent via POST
    $selectedClubName = isset($_POST['selectedClubName']) ? sanitize_text_field($_POST['selectedClubName']) : '';
    $clubColor = isset($_POST['club_color']) ? sanitize_hex_color($_POST['club_color']) : '';
    $clubCrestUrl = isset($_POST['club_crest_url']) ? esc_url_raw($_POST['club_crest_url']) : '';

    $response_messages = [];

    if ($selectedClubName) {
		error_log('Saving Club Name: ' . $selectedClubName); // Debugging before saving
        update_option('playcricket_selected_club_name', $selectedClubName);
        $response_messages[] = 'Club name saved successfully.';
    }
    if ($clubColor) {
		error_log('Saving Club Color: ' . $clubColor); // Debugging before saving
        update_option('playcricket_club_color', $clubColor);
        $response_messages[] = 'Club color saved successfully.';
    }
    if ($clubCrestUrl) {
		error_log('Saving Club Crest: ' . $clubCrestUrl); // Debugging before saving

        update_option('playcricket_club_crest_url', $clubCrestUrl);
        $response_messages[] = 'Club crest URL saved successfully.';
    }

    if (empty($response_messages)) {
		wp_send_json_error(array('message' => 'No data provided.'));
	} else {
		// Change 'message' to 'messages' and remove the implode function to keep it as an array
		wp_send_json_success(array('messages' => $response_messages));
	}


    wp_die(); // Ensure AJAX request ends properly
}
add_action('wp_ajax_playcricket_save_selected_club', 'playcricket_save_selected_club_callback');