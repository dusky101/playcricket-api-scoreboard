<?php

// Shortcode to display PlayCricket Teams or Fixtures
function playcricket_display($atts) {
    // Conditionally enqueue the styles for this shortcode
    wp_enqueue_style("playcricket-style", PLAYCRICKET_PLUGIN_URL . "css/style.css");
    wp_enqueue_style("details-style", PLAYCRICKET_PLUGIN_URL . "css/details.css");

    // Conditionally enqueue the scripts for this shortcode
    wp_enqueue_script(
        "playcricket-ajax",
        PLAYCRICKET_PLUGIN_URL . "js/ajax.js",
        array("jquery"),
        null,
        true
    );
    wp_enqueue_script(
        "playcricket-script",
        PLAYCRICKET_PLUGIN_URL .  "js/script.js",
        array("jquery"),
        null,
        true
    );
    wp_enqueue_script(
        "details-js",
        PLAYCRICKET_PLUGIN_URL .  "js/details.js",
        array("jquery"),
        null,
        true
    );

    // Localize scripts for AJAX
    $ajax_nonce = wp_create_nonce("playcricket_ajax_nonce");
    wp_localize_script("playcricket-ajax", "frontendajax", [
        "ajaxurl" => admin_url("admin-ajax.php"),
        "nonce" => $ajax_nonce,
    ]);

    $a = shortcode_atts(["type" => "teams"], $atts);

    if ($a["type"] === "teams") {
        $teams = fetch_playcricket_teams();
        $current_year = date("Y");

        if ($teams && !empty($teams)) {
            // Start building the output for the team selector dropdown
            $output =
                '<div class="custom-dropdown playcricket-team-selector-dropdown">';
            $output .=
                '<div class="selected-value" data-dropdown="team-selector">Select Team</div>';
            $output .= '<div class="options-container">';
            foreach ($teams as $team) {
                $output .=
                    '<div class="option" data-value="' .
                    esc_attr($team["id"]) .
                    '">' .
                    esc_html($team["team_name"]) .
                    "</div>";
            }
            $output .= "</div></div>"; // Close team selector dropdown

            // Year selector dropdown
            $output .=
                '<div class="custom-dropdown playcricket-year-selector-dropdown">';
            $output .=
                '<div class="selected-value" data-dropdown="year-selector">Select Year</div>';
            $output .= '<div class="options-container">';
            for ($year = $current_year; $year >= $current_year - 10; $year--) {
                $output .=
                    '<div class="option" data-value="' .
                    $year .
                    '">' .
                    $year .
                    "</div>";
            }
            $output .= "</div></div>"; // Close year selector dropdown

            // Button to show results
            $output .=
                '<button id="playcricket-show-results">Show Results</button>';

            $output .= "</div>"; // Close custom-dropdown

            // Container to load results
            $output .= '<div id="playcricket-results-container"></div>';
            $output .= "</div>";

            return $output;
        }
    }
    return "No teams found.";
}
add_shortcode("playcricket", "playcricket_display");
