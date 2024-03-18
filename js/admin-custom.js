jQuery(document).ready(function($) {
    // Function to save the API key and Site ID values and then check/display the form
    function saveAndCheckForm() {
        var apiKey = $('#playcricket_api_key').val();
        var siteId = $('#playcricket_site_id').val();

        console.log("API Key: " + apiKey); // Log the API Key
        console.log("Site ID: " + siteId); // Log the Site ID

        // Send AJAX request to save the API Key and Site ID
        $.ajax({
            url: playcricket_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'playcricket_save_api_site_id', // This should match the action hooked in PHP
                api_key: apiKey,
                site_id: siteId,
                nonce: playcricket_ajax.nonce // Assuming 'playcricket_ajax' object is localized correctly
            },
            success: function(response) {
                if (response.success) {
                    console.log(response.data.message);
                    // Show the container upon successful save
                    $('#playcricket-club-search-container').slideDown();
                } else {
                    console.error(response.data.message);
                    // Optionally handle errors, such as displaying a message to the user
                }
            },
            error: function(xhr, status, error) {
                console.error("Error saving API Key and Site ID: " + error);
            }
        });
    }

    // Bind the saveAndCheckForm function to the Confirm button click event
    $('#confirm-settings').on('click', saveAndCheckForm);
});
