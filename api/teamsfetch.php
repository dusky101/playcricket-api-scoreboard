<?php

function playcricket_fetch_teams()
{
    $teams = fetch_playcricket_teams();
    wp_send_json($teams);
}

add_action("wp_ajax_playcricket_fetch_teams", "playcricket_fetch_teams");
add_action("wp_ajax_nopriv_playcricket_fetch_teams", "playcricket_fetch_teams");

function get_teams($site_id = null)
{
    $api_key = get_option("playcricket_api_key");
    $api_url =
        "https://www.play-cricket.com/api/v2/teams.json?api_token=" . $api_key;

    if ($site_id) {
        $api_url =
            "https://www.play-cricket.com/api/v2/sites/" .
            $site_id .
            "/teams.json?api_token=" .
            $api_key;
    }

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        return [];
    }
    return json_decode(wp_remote_retrieve_body($response), true)["teams"];
}

function fetch_playcricket_teams() {
    $site_id = get_option("playcricket_site_id");
    $api_token = get_option("playcricket_api_key"); // Retrieve the API token stored in WordPress settings

    $endpoint = "https://www.play-cricket.com/api/v2/sites/" . $site_id . "/teams.json?&api_token=" . $api_token;
    $response = wp_remote_get($endpoint);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    $teams = $data["teams"];

    // Adjust the team names where team_name is 'Other'
    foreach ($teams as $key => $team) {
        if ($team['team_name'] === 'Other' && !empty($team['other_team_name'])) {
            $teams[$key]['team_name'] = $team['other_team_name'];
            if (!empty($team['nickname'])) {
                $teams[$key]['team_name'] .= ' (' . $team['nickname'] . ')';
            }
        }
    }

    return $teams;
}


// AJAX Handler for fetching team results with innings details
function fetch_team_results()
{
    error_log("fetch_team_results function called"); // Debug log

    $team_id = isset($_POST["team_id"])
        ? sanitize_text_field($_POST["team_id"])
        : "";
    $year = isset($_POST["year"])
        ? sanitize_text_field($_POST["year"])
        : date("Y");

    error_log("Team ID: " . $team_id . ", Year: " . $year); // Debug log

    if (empty($team_id)) {
        wp_send_json_error("Team ID is required.");
        error_log("Error in fetch_team_results: Team ID is required."); // Debug log
        wp_die();
    }

    $site_id = get_option("playcricket_site_id");
    $api_token = get_option("playcricket_api_key");
    $endpoint = "http://play-cricket.com/api/v2/result_summary.json?team_id={$team_id}&season={$year}&site_id={$site_id}&api_token={$api_token}";

    error_log("API Endpoint: " . $endpoint); // Debug log

    $response = wp_remote_get($endpoint);

    if (is_wp_error($response)) {
        wp_send_json_error("Error fetching data from PlayCricket API.");
        error_log(
            "Error in fetch_team_results: " . $response->get_error_message()
        ); // Debug log
        wp_die();
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    $output = ""; // Initialize output string

    if (empty($data["result_summary"])) {
        wp_send_json_error("No matches found for this team.");
        error_log(
            "Error in fetch_team_results: No matches found for this team."
        ); // Debug log
        wp_die();
    }

    error_log("Fetched Data: " . print_r($data, true)); // Debug log

    // Convert the fetched data to HTML format
    $output = '<div class="playcricket-results">';

    // Create team name mapping array
    $team_name_mapping = [];
    foreach ($data["result_summary"] as $match) {
        $team_name_mapping[$match["home_team_id"]] = $match["home_club_name"];
        $team_name_mapping[$match["away_team_id"]] = $match["away_club_name"];
    }

    foreach ($data["result_summary"] as $match) {
        $output .=
            '<div class="playcricket-match-header" data-match-id="' .
            esc_attr($match["id"]) .
            '">';
        $output .=
            '<span class="club-name"> ' .
            esc_html($match["home_club_name"]) .
            "</span>";
        $output .= '<span class="vs"> vs </span>';
        $output .=
            '<span class="club-name">' .
            esc_html($match["away_club_name"]) .
            "</span>";
        $result_text = str_replace(
            " - 1st XI",
            "",
            $match["result_description"]
        );
        $result_text = str_replace(
            [" - Won", " - Lost"],
            [" Won", " Lost"],
            $result_text
        );
        $output .=
            '<span class="result-description">' .
            esc_html($result_text) .
            "</span>";
        $output .=
            '<span class="match-date"> on ' .
            esc_html($match["match_date"]) .
            "</span>";
        $output .= "</div>"; // Close header div

        // Fetching match details including players
        $match_details = get_match_details($match["id"]);

        // Fetch additional match details for batsman and bowler
        //$additional_details = get__match_details($match['id']);
        // Output the match summary
        if (!empty($match["innings"])) {
            $output .= '<div class="playcricket-match-summary">';
            $output .=
                "<h4>Competition: " .
                esc_html($match["competition_name"]) .
                "</h4>";
            $output .= '<div class="playcricket-teams-summary">'; // Container for both teams

            foreach ($match["innings"] as $innings) {
                $teamName = isset(
                    $team_name_mapping[$innings["team_batting_id"]]
                )
                    ? $team_name_mapping[$innings["team_batting_id"]]
                    : "Unknown Team";

                // Each team's summary in a column
                $output .= '<div class="playcricket-team-column">';
                $output .= "<h5>" . esc_html($teamName) . "</h5>"; // Use h5 for team names
                $output .= "<p>Runs: " . esc_html($innings["runs"]) . "</p>";
                $output .=
                    "<p>Wickets: " . esc_html($innings["wickets"]) . "</p>";
                $output .= "<p>Overs: " . esc_html($innings["overs"]) . "</p>";
                $output .= "</div>"; // Close team column div
            }

            $output .= "</div>"; // Close teams summary container
            $output .= "</div>"; // Close match summary div
        }

        // Add a new container for match details right after the match summary
        $output .=
            '<div class="playcricket-match-details" style="display: none;" data-match-id="' .
            esc_attr($match["id"]) .
            '"></div>';
    }

    wp_send_json_success($output);
    wp_die();
}
add_action("wp_ajax_fetch_team_results", "fetch_team_results");
add_action("wp_ajax_nopriv_fetch_team_results", "fetch_team_results");