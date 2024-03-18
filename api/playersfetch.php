<?php

function ajax_get_players()
{
    $team_id = $_GET["team_id"];
    $year = $_GET["year"];

    // Fetch all players (modify as needed to include year filtering)
    $all_players = fetch_players_for_team($team_id); // Implement this function to fetch all players

    // Filter players by the selected team
    $players = filter_players_by_team($all_players, $team_id);

    wp_send_json($all_players);
}