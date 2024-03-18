jQuery(document).ready(function($) {
	
    $(document).on('click', '.batter-main', function() {
        // Toggle the visibility of the associated .batter-additional details
        $(this).next('.batter-additional').slideToggle();
        // Toggle the 'active' class to change the color and pop effect
        $(this).toggleClass('active');
    });
	
    // Event handler for match header clicks
    $(document).on('click', '.playcricket-match-header', function() {
        var matchId = $(this).data('match-id');
        var matchSummaryContainer = $(this).next('.playcricket-match-summary');
        var matchDetailsContainer = matchSummaryContainer.next('.playcricket-match-details[data-match-id="' + matchId + '"]');

        // Toggle the visibility of match summary
        matchSummaryContainer.slideToggle();

        // Check if the detailed match info is already loaded
        if (!matchDetailsContainer.hasClass('loaded')) {
            // Fetch the detailed match info (batsman and bowler details)
            $.ajax({
                url: frontendajax.ajaxurl,
                method: 'POST',
                dataType: 'json',
                data: {
                    'action': 'fetch_batsman_bowler_details',
                    'match_id': matchId
                },
                beforeSend: function() {
                    matchDetailsContainer.html('<p>Loading details...</p>'); // Show a loading message
                },
                success: function(response) {
                    if (response.success) {
                        // Populate the container with the HTML content received from the server
                        matchDetailsContainer.html(response.data.html).slideDown().addClass('loaded');
                    } else {
                        console.error('Error fetching match details: No data returned.');
                        matchDetailsContainer.html('<p>Error loading details. Please try again.</p>');
                    }
                },
                error: function(error) {
                    console.error('Failed to fetch match details:', error);
                    matchDetailsContainer.html('<p>Error loading details. Please check your connection and try again.</p>');
                }
            });
        } else {
            // If already loaded, toggle visibility
            matchDetailsContainer.slideToggle();
        }
    });
});
