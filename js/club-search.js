jQuery(document).ready(function($) {
    // Initialize WP Color Picker
    $('.color-picker').wpColorPicker();

    // Image selection via WordPress Media Library
    var imageFrame;
    $('#choose-image').on('click', function(e) {
        e.preventDefault();

        if (imageFrame) {
            imageFrame.open();
            return;
        }

        imageFrame = wp.media({
            title: 'Select Club Crest',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        imageFrame.on('select', function() {
            var attachment = imageFrame.state().get('selection').first().toJSON();
            $('#image-url').val(attachment.url);
            $('#image-preview').html('<img src="' + attachment.url + '" alt="" style="max-width: 100%; height: auto;">');

            // Save the club crest URL to the database
            saveClubDetails('club_crest_url', attachment.url);
        });

        imageFrame.open();
    });

    // Club search button click event
    $('#club-search-button').on('click', function() {
        var searchTerm = $('#club-search-input').val().toLowerCase();
        $('#club-search-results').empty();

        $.ajax({
            url: playcricket_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'playcricket_search_clubs',
                nonce: playcricket_ajax.nonce,
                searchTerm: searchTerm
            },
            success: function(response) {
                if (response.success) {
                    $.each(response.data.clubs, function(i, club) {
                        var clubDiv = $('<div class="club-result">' + club.name + '<button class="select-club">Select</button></div>');
                        // Bind click event to select a club
                        clubDiv.find('.select-club').on('click', function() {
                            // Save the selected club name to the database
                            saveClubDetails('selected_club_name', club.name);
                        });
                        $('#club-search-results').append(clubDiv);
                    });

                    if (response.data.clubs.length === 0) {
                        $('#club-search-results').append('<div class="club-error">No clubs found with that name.</div>');
                    }
                } else {
                    $('#club-search-results').append('<div class="club-error">Error searching clubs.</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#club-search-results').append('<div class="club-error">Error: ' + error + '</div>');
            }
        });
    });

    // Save the club details to the database
    function saveClubDetails() {
    var data = {
        'action': 'playcricket_save_selected_club',
        'nonce': playcricket_ajax.nonce, // Replace with your nonce variable
        'selectedClubName': $('#club-search-input').val(),
        'club_color': $('#club-color').val(),
        'club_crest_url': $('#image-url').val() // Ensure you have the right selector for the image URL
    };

    $.post(playcricket_ajax.ajax_url, data, function(response) {
    if (response.success) {
        // If messages are in an array, join them into a single string.
        var messages = response.data.messages.join('\n');
        alert(messages); // Show all messages in one alert.
    } else {
        alert('Error: ' + response.data.message);
    }
}, 'json').fail(function(error) {
    alert('Error: ' + error);
});

	}


    // Save changes when the form is submitted
    $('#playcricket-settings-form').on('submit', function(e) {
        e.preventDefault(); // Prevent the form from submitting normally
        // Save all the details on form submission
        saveClubDetails('club_color', $('#club-color').val());
        saveClubDetails('club_crest_url', $('#image-url').val());
        saveClubDetails('selected_club_name', $('#club-search-input').val());
    });
});
