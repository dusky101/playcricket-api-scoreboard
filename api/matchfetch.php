<?php

// AJAX Handler for fetching match summaries
function playcricket_fetch_match_summaries()
{
    $site_id = isset($_POST["site_id"])
        ? sanitize_text_field($_POST["site_id"])
        : "";
    $year = isset($_POST["year"])
        ? sanitize_text_field($_POST["year"])
        : date("Y");
    $matches = fetch_playcricket_match_summaries($site_id, $year);
    wp_send_json($matches);
}
add_action(
    "wp_ajax_playcricket_fetch_match_summaries",
    "playcricket_fetch_match_summaries"
);

add_action(
    "wp_ajax_nopriv_playcricket_fetch_match_summaries",
    "playcricket_fetch_match_summaries"
);

function fetch_playcricket_match_summaries($site_id, $year)
{
    $api_token = get_option("playcricket_api_key"); // Retrieve the API key stored in WordPress settings
    $endpoint =
        "https://www.play-cricket.com/api/v2/sites/" .
        $site_id .
        "/matches.json?season=" .
        $year .
        "&api_token=" .
        $api_token;
    $response = wp_remote_get($endpoint);
    if (is_wp_error($response)) {
        return false; // Handle the error appropriately
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    return $data["matches"]; // Assuming 'matches' is the correct key in the response
}

function get_match_details($match_id)
{
    $api_key = get_option("playcricket_api_key");
    $api_url =
        "http://play-cricket.com/api/v2/match_detail.json?match_id=" .
        $match_id .
        "&api_token=" .
        $api_key;

    $response = wp_remote_get($api_url);
    if (is_wp_error($response)) {
        // Handle the error appropriately
        return [];
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

// Function to fetch match details
function fetch_match_details()
{
    $match_id = isset($_POST["match_id"])
        ? sanitize_text_field($_POST["match_id"])
        : "";
    $data = get_match_details_by_id($match_id);

    if (!$data) {
        echo json_encode(["error" => "Failed to fetch match details."]);
        wp_die();
    }

    echo $data; // Directly output the fetched data
    wp_die();
}
add_action("wp_ajax_fetch_match_details", "fetch_match_details");
add_action("wp_ajax_nopriv_fetch_match_details", "fetch_match_details");

function get_additional_match_details($match_id)
{
    $api_token = get_option("playcricket_api_key"); // Retrieve the API token
    $endpoint =
        "http://play-cricket.com/api/v2/match_detail.json?&match_id={$match_id}&api_token=" .
        $api_token;
    error_log("API Endpoint: " . $endpoint); // Debug log

    $response = wp_remote_get($endpoint);

    if (is_wp_error($response)) {
        // Log error and return an empty array to indicate failure
        error_log(
            "Failed to fetch match details: " . $response->get_error_message()
        );
        return [];
    }

    // Decode the response body
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data) || !isset($data["match_details"][0]["innings"])) {
        // Log error and return an empty array to indicate failure
        error_log("No innings details found in match details.");
        return [];
    }

    // Return the innings details
    return $data["match_details"][0]["innings"];
}