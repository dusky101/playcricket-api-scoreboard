jQuery(document).ready(function($) {
    // Custom dropdown functionality
    $('.custom-dropdown .selected-value').on('touchend click', function(event) {
    event.stopPropagation(); // Stop the event from bubbling up to parent elements
    event.preventDefault(); // Prevent the default behavior of the event, which is text selection

    // Toggle the options display
    $(this).next('.options-container').toggleClass('open');
});

    $('.custom-dropdown .option').on('click', function(event) {
        // Prevent any parent handlers from being notified of the event
        event.stopPropagation();
        // Get the selected value and text
        var value = $(this).data('value');
        var text = $(this).text();
        // Update the display and store the selected value
        var dropdown = $(this).closest('.custom-dropdown');
        dropdown.find('.selected-value').text(text).data('value', value);
        // Close the dropdown
        dropdown.find('.options-container').removeClass('open');
    });

    // Clicking outside the dropdowns closes them
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.custom-dropdown').length) {
            $('.custom-dropdown .options-container').removeClass('open');
        }
    });

    // AJAX call when the 'Show Results' button is clicked
    $('#playcricket-show-results').on('click', function() {
        var teamId = $('.playcricket-team-selector-dropdown .selected-value').data('value');
        var year = $('.playcricket-year-selector-dropdown .selected-value').data('value');
        
            if (teamId && year) {
                $.ajax({
                    url: frontendajax.ajaxurl,
                    method: 'POST',
                    dataType: 'json', // Expecting a JSON response
                    data: {
                        'action': 'fetch_team_results',
                        'team_id': teamId,
                        'year': year
                    },
                    success: function(response) {
                        // Make sure to check for the success property in the response
                        if (response && response.success) {
                            $('#playcricket-results-container').html(response.data);
                        } else {
                            // Handle cases where success is false or not present
                            console.error('Error fetching results: ', response);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Failed to fetch results: ', textStatus, errorThrown);
                    }
                });
            } else {
                console.error('Please select both team and year');
            }
    });
});
