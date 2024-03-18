<?php
// Ensure this file is being included within a WordPress environment.
if (!defined("WPINC")) {
    die();
}

// Retrieve API key and Site ID from the WordPress options table.
$api_key = get_option("playcricket_api_key");
$site_id = get_option("playcricket_site_id");

// Fetch competitions for a specific team
function fetch_competitions_for_team($team_id, $year)
{
    global $api_key, $site_id;
    $endpoint = "https://www.play-cricket.com/api/v2/result_summary.json?site_id={$site_id}&team_id={$team_id}&season={$year}&api_token={$api_key}";
    $response = wp_remote_get($endpoint);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return !empty($data["result_summary"]) ? $data["result_summary"] : false;
}

function determine_competition_id($team_id, $year)
{
    $competitions = fetch_competitions_for_team($team_id, $year);

    if (!$competitions || empty($competitions)) {
        return false; // Or handle the error as appropriate
    }

    // Example: Select the first competition ID as the correct one.
    // You might need more sophisticated logic based on your criteria.
    foreach ($competitions as $competition) {
        if (!empty($competition["competition_id"])) {
            return $competition["competition_id"];
        }
    }

    return false; // If no competition ID was determined, return false or handle as appropriate
}

// AJAX handler for fetching teams
function handle_fetch_teams_ajax()
{
    //check_ajax_referer('playcricket_ajax_nonce', 'security');

    $teams = fetch_playcricket_teams(); // Assuming this function fetches the teams as per your setup
    if (false === $teams) {
        wp_send_json_error("Failed to fetch teams");
    } else {
        wp_send_json_success($teams);
    }
    wp_die(); // Terminate the execution of the script.
}
add_action("wp_ajax_fetch_teams", "handle_fetch_teams_ajax");
add_action("wp_ajax_nopriv_fetch_teams", "handle_fetch_teams_ajax");

// Fetch competition IDs based on year
function fetch_competition_ids($year)
{
    global $api_key, $site_id;
    $api_url = "http://play-cricket.com/api/v2/result_summary.json?site_id={$site_id}&season={$year}&api_token={$api_key}";

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return []; // Handle error appropriately.
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $competition_ids = [];
    if (!empty($data["result_summary"])) {
        foreach ($data["result_summary"] as $summary) {
            if (!empty($summary["competition_id"])) {
                $competition_ids[] = [
                    "id" => $summary["competition_id"],
                    "name" => $summary["competition_name"],
                ];
            }
        }
    }

    return $competition_ids;
}

// AJAX handler for fetching competition IDs
function handle_fetch_competition_ids_ajax()
{
    //check_ajax_referer('playcricket_ajax_nonce', 'security');

    $year = isset($_POST["year"])
        ? sanitize_text_field($_POST["year"])
        : date("Y");
    wp_send_json_success(fetch_competition_ids($year));
    wp_die(); // Terminate the execution of the script.
}
add_action(
    "wp_ajax_fetch_competition_ids_for_year",
    "handle_fetch_competition_ids_ajax"
);
add_action(
    "wp_ajax_nopriv_fetch_competition_ids_for_year",
    "handle_fetch_competition_ids_ajax"
);

// Fetch league table data based on competition ID and year
function fetch_league_table_data($competition_id)
{
    wp_enqueue_style(
        "playcricket-league-style",
        PLAYCRICKET_PLUGIN_URL . "css/playcricket-league.css"
    );

    global $api_key;
    $api_url = "http://play-cricket.com/api/v2/league_table.json?division_id={$competition_id}&api_token={$api_key}";

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        // Handle the error appropriately and return a meaningful error message.
        return [
            "success" => false,
            "data" => "An error occurred while retrieving the league table.",
        ];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data["league_table"])) {
        // Specific error for league table format issues.
        return [
            "success" => false,
            "data" => "Incorrect league table data format.",
        ];
    }

    return ["success" => true, "data" => $data["league_table"]];
}

// Register shortcode to display team and competition selectors
add_shortcode("playcricket_league", function () {
    wp_enqueue_script(
        "playcricket-league-js",
        PLAYCRICKET_PLUGIN_URL . "js/playcricket-league.js",
        ["jquery"],
        null,
        true
    );
    wp_localize_script("playcricket-league-js", "ajax_object", [
        "ajaxurl" => admin_url("admin-ajax.php"),
    ]);
    wp_enqueue_style(
        "playcricket-league-style",
        PLAYCRICKET_PLUGIN_URL . "css/playcricket-league.css"
    );

    // Nonce for AJAX requests
    //$nonce = wp_create_nonce('playcricket_ajax_nonce');

    // Initial HTML for the team selector dropdown
    $html = '<div id="playcricket-selectors-container">';
    $html .= "<h4>Select a Team:</h4>";
    $html .= '<select id="team-selector">';
    $html .= '<option value="">Select a Team</option>'; // Placeholder option for teams
    $html .= "</select>";

    // HTML for the year selector dropdown
    $html .= "<h4>Select a Year:</h4>";
    $html .= '<select id="year-selector">';
    $html .= '<option value="">Select a Year</option>'; // Default placeholder option for years
    $currentYear = date("Y"); // Get the current year
    for ($year = $currentYear; $year > $currentYear - 11; $year--) {
        // Go back 10 years from the current year
        $html .= '<option value="' . $year . '">' . $year . "</option>";
    }
    $html .= "</select>"; // End of year selector dropdown

    // // Placeholder for the competition selector dropdown, to be populated based on the selected team
    // $html .= '<h4>Select a Competition:</h4>';
    // $html .= '<select id="competition-selector" style="margin-top: 10px;">';
    // $html .= '<option value="">Select a Competition</option>'; // Initial option
    // $html .= '</select>';

    // Container for displaying the league table, to be populated based on the selected competition
    $html .= '<div id="league-table-container" style="margin-top: 20px;">';
    $html .= "Select a team and competition to view the league table.";
    $html .= "</div>"; // Closing the main container div

    $html .= "</div>"; // Closing the main container div

    // Return the HTML content to be rendered by the shortcode
    return $html;
});

function handle_fetch_league_table_data_ajax()
{
    $team_id = isset($_POST["team_id"])
        ? sanitize_text_field($_POST["team_id"])
        : "";
    $year = isset($_POST["year"])
        ? sanitize_text_field($_POST["year"])
        : date("Y");

    $competition_id = determine_competition_id($team_id, $year);

    if (!$competition_id) {
        wp_send_json_error("Could not determine competition ID");
        wp_die();
    }

    $result = fetch_league_table_data($competition_id);
    if (!$result["success"]) {
        wp_send_json_error($result["data"]);
    } else {
        wp_send_json_success($result["data"]);
    }
    wp_die();
}
add_action(
    "wp_ajax_fetch_league_table_data",
    "handle_fetch_league_table_data_ajax"
);
add_action(
    "wp_ajax_nopriv_fetch_league_table_data",
    "handle_fetch_league_table_data_ajax"
);

function handle_fetch_competitions_for_team_ajax()
{
    // Uncomment the nonce check if you wish to use nonces for security
    // check_ajax_referer('playcricket_ajax_nonce', 'security');

    // Retrieve the team ID and year from the POST request
    $team_id = isset($_POST["team_id"])
        ? sanitize_text_field($_POST["team_id"])
        : "";
    $year = isset($_POST["year"])
        ? sanitize_text_field($_POST["year"])
        : date("Y"); // Use the current year as a default

    // Check that both the team ID and year are provided
    if (!$team_id || !$year) {
        wp_send_json_error("Missing team ID or year");
        wp_die();
    }

    // Call the fetch_competitions_for_team function with both parameters
    $competitions = fetch_competitions_for_team($team_id, $year);

    // Check the result and return the appropriate response
    if (false === $competitions) {
        wp_send_json_error("Failed to fetch competitions");
    } else {
        wp_send_json_success($competitions);
    }

    // Terminate the execution of the script
    wp_die();
}

// Hooks for AJAX actions
add_action(
    "wp_ajax_fetch_competitions_for_team",
    "handle_fetch_competitions_for_team_ajax"
);
add_action(
    "wp_ajax_nopriv_fetch_competitions_for_team",
    "handle_fetch_competitions_for_team_ajax"
);
