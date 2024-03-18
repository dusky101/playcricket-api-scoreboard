jQuery(document).ready(function($) {
    // Populate teams dropdown on page load
    populateTeams();
    populateYears();

    // Event handler for when the year changes
    $('#year-selector').change(function() {
        var selectedYear = $(this).val();
        var selectedTeamId = $('#team-selector').val();

        // Make sure both a team and a year are selected
        if (selectedTeamId && selectedYear) {
            // Fetch and display the league table data
            fetchLeagueTable(selectedTeamId, selectedYear);
        }
    });

    // Function to fetch and display the league table data
    function fetchLeagueTable(teamId, year) {
        $.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'fetch_league_table_data',
                team_id: teamId,
                year: year
            },
            success: function(response) {
                // Check if the response was successful and data is in the expected format
                if (response.success && response.data && Array.isArray(response.data)) {
                    // Start constructing the table HTML
                    var tableHtml = '<table class="playcricket-league-table">';

                    // Add table headings if they exist
                    if (response.data[0].headings) {
                        tableHtml += '<thead><tr>';
                        var headings = response.data[0].headings;
                        for (var key in headings) {
                            tableHtml += `<th>${headings[key]}</th>`;
                        }
                        tableHtml += '</tr></thead>';
                    }

                    // Add table body if values exist
                    if (response.data[0].values) {
                        tableHtml += '<tbody>';
                        var values = response.data[0].values;
                        values.forEach(function(row) {
                            tableHtml += '<tr>';
                            for (var key in row) {
                                // Check if the key is a valid column and has a value
                                if (key.startsWith('column_') && row[key] !== null) {
                                    tableHtml += `<td>${row[key]}</td>`;
                                }
                            }
                            tableHtml += '</tr>';
                        });
                        tableHtml += '</tbody>';
                    }

                    tableHtml += '</table>'; // End of table

                    // Display the constructed table
                    $('#league-table-container').html(tableHtml);
                } else {
                    // Handle cases where the data is not in the expected format
                    $('#league-table-container').html('<p>Error fetching league table data.</p>');
                }
            },
        });
    }

    // Function to create and return a league table HTML string
    function createLeagueTable(leagueData) {
	  // Table start with CSS class for styling
	  let tableHtml = '<table class="league-table">';

	  // Add table headings from the data
	  tableHtml += '<thead><tr>';
	  Object.values(leagueData.headings).forEach(heading => {
		tableHtml += `<th>${heading}</th>`;
	  });
	  tableHtml += '</tr></thead>';

	  // Add table body with team data
	  tableHtml += '<tbody>';
	  leagueData.values.forEach((team, index) => {
		// Apply different classes for top and bottom teams if needed
		let rowClass = index === 0 ? 'top-team' : '';
		tableHtml += `<tr class="${rowClass}">`;
		Object.keys(leagueData.headings).forEach(key => {
		  tableHtml += `<td>${team[key]}</td>`;
		});
		tableHtml += '</tr>';
	  });
	  tableHtml += '</tbody></table>';

	  // Add a key for abbreviations under the table
	  tableHtml += '<div class="league-key">' + leagueData.key + '</div>';

	  return tableHtml;
	}

    // Function to populate teams dropdown
	function populateTeams() {
		$.ajax({
			url: ajax_object.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'fetch_teams' // Nonce removed for testing
			},
			success: function(response) {
				if (response.success && response.data) {
					var teamsDropdown = $('#team-selector');
					teamsDropdown.empty().append('<option value="">Select a Team</option>');
					response.data.forEach(function(team) {
						// Check if team_name is 'Other' and use other_team_name instead
						var teamName = team.team_name === 'Other' && team.other_team_name ? team.other_team_name : team.team_name;
						var displayName = teamName;
						// If there's a nickname, append it.
						if (team.nickname) {
							displayName += ` (${team.nickname})`;
						}
						teamsDropdown.append($('<option></option>').attr('value', team.id).text(displayName));
					});
				} else {
					console.error('Error fetching teams.');
				}
			},
			error: function() {
				console.error('Error making AJAX request to fetch teams.');
			}
		});
	}

    // Function to populate years dropdown
    function populateYears() {
        var yearSelector = $('#year-selector');
        var currentYear = new Date().getFullYear();
        yearSelector.empty().append('<option value="">Select a Year</option>');
        for (var year = currentYear; year >= currentYear - 10; year--) {
            yearSelector.append($('<option></option>').attr('value', year).text(year));
        }
    }
});
