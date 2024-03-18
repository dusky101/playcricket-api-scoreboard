<?php
// Make sure to include this file in your theme's functions.php or the main plugin file.

/**
 * Register AJAX actions for authenticated and non-authenticated users.
 */
add_action('wp_ajax_fetch_batsman_bowler_details', 'fetch_batsman_bowler_details');
add_action('wp_ajax_nopriv_fetch_batsman_bowler_details', 'fetch_batsman_bowler_details');

/**
 * Handles the AJAX request to fetch batsman and bowler details for a specific match ID.
 */
function fetch_batsman_bowler_details() {
    if (!isset($_POST['match_id'])) {
        wp_send_json_error('Match ID is required.');
        wp_die();
    }

    $match_id = sanitize_text_field($_POST['match_id']);
    $match_details_json = get_match_details_by_id($match_id);

    if (!$match_details_json) {
        wp_send_json_error('Failed to fetch match details.');
        wp_die();
    }

    $match_details = json_decode($match_details_json, true);
	
	// Retrieve the selected club name and check if it's properly retrieved
	$selectedClubName = get_option('playcricket_selected_club_name');
	if (empty($selectedClubName)) {
		error_log('Error: Selected club name could not be retrieved or is not set.');
		// Handle the error appropriately, maybe set a default value or exit the function
	}

	// Retrieve the club color and check if it's properly retrieved
	$clubColor = get_option('playcricket_club_color', '#000000'); // Corrected default color to a valid hex
	if (empty($clubColor)) {
		error_log('Error: Club color could not be retrieved or is not set.');
		// Handle the error appropriately
	}

	// Retrieve the club crest URL and check if it's properly retrieved
	$clubCrestUrl = get_option('playcricket_club_crest_url');
	if (empty($clubCrestUrl)) {
		error_log('Error: Club crest URL could not be retrieved or is not set.');
		// Handle the error appropriately
	}


    // Directly retrieve team names based on your match details structure
    $homeTeamName = $match_details['match_details'][0]['home_club_name'];
    $awayTeamName = $match_details['match_details'][0]['away_club_name'];

    $data = [
        'homeTeamName' => $homeTeamName,
        'awayTeamName' => $awayTeamName,
        'homeBatsmen' => [],
        'awayBatsmen' => [],
        'homeBowlers' => [],
        'awayBowlers' => []
    ];

    // Populate homeBatsmen, awayBatsmen, homeBowlers, awayBowlers based on the match details
    foreach ($match_details['match_details'][0]['innings'] as $innings) {
        $team_batting_id = $innings['team_batting_id'];
        $isHomeTeamBatting = ($team_batting_id === $match_details['match_details'][0]['home_team_id']);

        foreach ($innings['bat'] as $batsman) {
            $batsmanDetails = [
		        'position' => isset($batsman['position']) ? $batsman['position'] : 'N/A', // Add position
                'name' => $batsman['batsman_name'],
                'runs' => $batsman['runs'],
                'balls' => isset($batsman['balls']) ? $batsman['balls'] : 'N/A',
                'how_out' => $batsman['how_out'],
				'bowler_name' => isset($batsman['bowler_name']) ? $batsman['bowler_name'] : '',
            	'fielder_name' => isset($batsman['fielder_name']) && $batsman['how_out'] === 'ct' ? $batsman['fielder_name'] : ''
            ];

            if ($isHomeTeamBatting) {
                $data['homeBatsmen'][] = $batsmanDetails;
            } else {
                $data['awayBatsmen'][] = $batsmanDetails;
            }
        }

        foreach ($innings['bowl'] as $bowler) {
            $bowlerDetails = [
                'name' => $bowler['bowler_name'],
                'overs' => $bowler['overs'],
                'runs' => $bowler['runs'],
                'wickets' => $bowler['wickets'],
                'maidens' => isset($bowler['maidens']) ? $bowler['maidens'] : 'N/A'
            ];

            if ($isHomeTeamBatting) {
                $data['awayBowlers'][] = $bowlerDetails;
            } else {
                $data['homeBowlers'][] = $bowlerDetails;
            }
        }
    }
// Build HTML for Batsmen and Bowlers details.
$output = '';

// Home Team Details
    $output .= '<div class="playcricket-match-details">';
    $output .= '<div class="playcricket-innings">';
    // Check if the home team name matches the selected club name
    if ($homeTeamName === $selectedClubName) {
        $output .= "<h3><img src='" . esc_url($clubCrestUrl) . "' alt='Club Crest' class='club-crest'>" . "<span style='color: " . esc_attr($clubColor) . ";'>" . esc_html($homeTeamName) . "</span> Innings</h3>";
    } else {
        $output .= "<h3>". esc_html($homeTeamName) . " Innings</h3>";
    }
    $output .= '<div class="playcricket-players-details">';

// Home Team Batsmen
$output .= '<div class="playcricket-batters">';
$output .= '<h4>Batsmen</h4>';
$output .= '<ul>';

foreach ($data['homeBatsmen'] as $batsman) {
    $output .= '<li class="batter">';
    // Batsman name should be inside this div and clickable
	$output .= '<div class="batter-main">' . esc_html($batsman['position']) . '. ' . esc_html($batsman['name']) . '</div>';
	
    // This additional information will be hidden by default and shown when .batter-main is clicked
	$output .= '<div class="batter-additional" style="display: none;">';

	if ($batsman['how_out'] === 'not out') {
		// Display "Not Out" in blue next to the runs if the player is not out
		$output .= 'Runs: ' . esc_html($batsman['runs']) . ' <span style="color: blue;">Not Out</span>';
	} elseif ($batsman['how_out'] === 'did not bat') {
		// Display "Did Not Bat" in dark blue if the player did not bat
		$output .= '<span style="color: darkblue;">Did Not Bat</span>';
	} else {
		// If the player is out, display how they were out and include runs
		$output .= 'Runs: ' . esc_html($batsman['runs']) . ', How out: ' . esc_html($batsman['how_out']);

		// Include bowler and fielder details if available
		if (!empty($batsman['bowler_name'])) {
			$output .= ', Bowled by ' . esc_html($batsman['bowler_name']);
		}

		if ($batsman['how_out'] === 'ct' && !empty($batsman['fielder_name'])) {
			$output .= ', Caught by ' . esc_html($batsman['fielder_name']);
		}
	}

	$output .= '</div>'; // End of batter-additional
    $output .= '</li>';
}

$output .= '</ul>';
$output .= '</div>'; // End of playcricket-batters

// Home Team Bowlers (Bowling against the Away Team)
$output .= '<div class="playcricket-bowlers">';
$output .= '<h4>Bowlers</h4>';
$output .= '<ul>';

foreach ($data['awayBowlers'] as $bowler) {
    $output .= '<li class="bowler">' . esc_html($bowler['name']) . ' - Overs: ' . esc_html($bowler['overs']) . ', Maidens: ' . esc_html($bowler['maidens']) . ', Runs: ' . esc_html($bowler['runs']) . ', Wickets: ' . esc_html($bowler['wickets']) . '</li>';
}

$output .= '</ul>';
$output .= '</div>'; // End of playcricket-bowlers
$output .= '</div>'; // End of playcricket-players-details
$output .= '</div>'; // End of playcricket-innings for the home team
$output .= '</div>'; // End of Match Details

// Away Team Details
$output .= '<div class="playcricket-match-details">';
$output .= '<div class="playcricket-innings">';
// Check if the away team name matches the selected club name
if ($awayTeamName === $selectedClubName) {
    $output .= "<h3><img src='" . esc_url($clubCrestUrl) . "' alt='Club Crest' style='height: 20px; width: auto; vertical-align: middle; margin-right: 5px;'>" . "<span style='color: " . esc_attr($clubColor) . ";'>" . esc_html($awayTeamName) . "</span> Innings</h3>";
} else {
    $output .= "<h3>" . esc_html($awayTeamName) . " Innings</h3>";
}
$output .= '<div class="playcricket-players-details">';

// Away Team Batsmen
$output .= '<div class="playcricket-batters">';
$output .= '<h4>Batsmen</h4>';
$output .= '<ul>';

foreach ($data['awayBatsmen'] as $batsman) {
    $output .= '<li class="batter">';
    // Batsman name should be inside this div and clickable
	$output .= '<div class="batter-main">' . esc_html($batsman['position']) . '. ' . esc_html($batsman['name']) . '</div>';    
    
	// This additional information will be hidden by default and shown when .batter-main is clicked
	$output .= '<div class="batter-additional" style="display: none;">';

	if ($batsman['how_out'] === 'not out') {
		// Display "Not Out" in blue next to the runs if the player is not out
		$output .= 'Runs: ' . esc_html($batsman['runs']) . ' <span style="color: blue;">Not Out</span>';
	} elseif ($batsman['how_out'] === 'did not bat') {
		// Display "Did Not Bat" in dark blue if the player did not bat
		$output .= '<span style="color: darkblue;">Did Not Bat</span>';
	} else {
		// If the player is out, display how they were out and include runs
		$output .= 'Runs: ' . esc_html($batsman['runs']) . ', How out: ' . esc_html($batsman['how_out']);

		// Include bowler and fielder details if available
		if (!empty($batsman['bowler_name'])) {
			$output .= ', Bowled by ' . esc_html($batsman['bowler_name']);
		}

		if ($batsman['how_out'] === 'ct' && !empty($batsman['fielder_name'])) {
			$output .= ', Caught by ' . esc_html($batsman['fielder_name']);
		}
	}

	$output .= '</div>'; // End of batter-additional
    $output .= '</li>';
}

$output .= '</ul>';
$output .= '</div>'; // End of playcricket-batters
	
// Away Team Bowlers
$output .= '<div class="playcricket-bowlers">';
$output .= '<h4>Bowlers</h4>';
$output .= '<ul>';

foreach ($data['homeBowlers'] as $bowler) {
    $output .= '<li class="bowler">' . esc_html($bowler['name']) . ' - Overs: ' . esc_html($bowler['overs']) . ', Maidens: ' . esc_html($bowler['maidens']) . ', Runs: ' . esc_html($bowler['runs']) . ', Wickets: ' . esc_html($bowler['wickets']) . '</li>';
}

$output .= '</ul>';
$output .= '</div>'; // End of playcricket-bowlers
$output .= '</div>'; // End of playcricket-players-details
$output .= '</div>'; // End of playcricket-innings for the away team
$output .= '</div>'; // End of Match Details

// Return the response with the HTML output
wp_send_json_success(['html' => $output]);
wp_die();

}

/**
 * Function to fetch match details by match ID.
 * Assumes an API token is stored and the API endpoint is known.
 * 
 * @param string $match_id The match ID to fetch details for.
 * @return string|false The API response, or false on failure.
 */
function get_match_details_by_id($match_id) {
    $api_token = get_option('playcricket_api_key');
    $endpoint = "http://play-cricket.com/api/v2/match_detail.json?&match_id={$match_id}&api_token=" . $api_token;
    $response = wp_remote_get($endpoint);

    if (is_wp_error($response)) {
        return false;
    }

    return wp_remote_retrieve_body($response);
}
