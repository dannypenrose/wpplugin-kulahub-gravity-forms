/**
 * KulaHub Admin JavaScript
 */
jQuery(document).ready(function($) {
    // Add new API key field
    $('#add-api-key').on('click', function() {
        var index = $('.api-key-row').length;
        var newRow = '<tr class="api-key-row">' +
            '<td><input type="text" name="kulahub_api_keys[new-' + index + '][name]" value="" class="regular-text"></td>' +
            '<td><input type="password" name="kulahub_api_keys[new-' + index + '][key]" value="" class="regular-text"></td>' +
            '<td><button type="button" class="button remove-key">Remove</button></td>' +
            '</tr>';
        $('#kulahub-api-keys-table tbody').append(newRow);
    });

    // Remove API key field
    $('#kulahub-api-keys-table').on('click', '.remove-key', function() {
        if ($('.api-key-row').length > 1) {
            $(this).closest('tr').remove();
        } else {
            // Clear values if it's the last row
            $(this).closest('tr').find('input[type="text"], input[type="password"]').val('');
        }
    });

    // Test connection
    $('.test-connection').on('click', function(e) {
        e.preventDefault();
        const formId = $(this).attr('form');
        $('#' + formId).submit();
    });
}); 