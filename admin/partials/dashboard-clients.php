<?php
/**
 * Dashboard Clients Section
 *
 * Displays a search bar, client list, and assigned tasks/meetings/notes.
 *
 * @package Nfinite_Dash
 */

?>

<!-- ✅ Client Search and Dashboard -->
<div class="dashboard-client-search">
    <label for="client-search"><?php _e('Search Clients:', 'nfinite-dash'); ?></label>
    <input type="text" id="client-search" placeholder="<?php _e('Enter client name...', 'nfinite-dash'); ?>">
    <button id="client-search-button" class="button button-primary"><?php _e('Search', 'nfinite-dash'); ?></button>
</div>

<div id="client-results" class="dashboard-client-results"></div>
<div id="client-dashboard" class="dashboard-client-dashboard"></div>

<div class="dashboard-client-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=client'); ?>" class="button button-primary">
        <?php _e('Add New Client', 'nfinite-dash'); ?>
    </a>
    <a href="<?php echo admin_url('edit.php?post_type=client'); ?>" class="button">
        <?php _e('View All Clients', 'nfinite-dash'); ?>
    </a>
</div>

<!-- ✅ JavaScript for AJAX Client Search and Dashboard -->
<script>
jQuery(document).ready(function ($) {
    function performClientSearch() {
        const query = $('#client-search').val();
        if (!query) {
            $('#client-results').html('<p>Please enter a search term.</p>');
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            method: 'POST',
            data: {
                action: 'search_clients_dashboard',
                query: query,
                nonce: '<?php echo wp_create_nonce('client_search_nonce'); ?>',
            },
            success: function (response) {
                if (response.success) {
                    let resultsHtml = '<ul>';
                    response.data.forEach(client => {
                        resultsHtml += `<li><a href="#" class="load-client-dashboard" data-client-id="${client.id}">${client.title}</a></li>`;
                    });
                    resultsHtml += '</ul>';
                    $('#client-results').html(resultsHtml);
                } else {
                    $('#client-results').html('<p>' + response.data.message + '</p>');
                }
            }
        });
    }

    $('#client-search-button').on('click', function () {
        performClientSearch();
    });

    $('#client-search').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            performClientSearch();
        }
    });

    $(document).on('click', '.load-client-dashboard', function (e) {
        e.preventDefault();
        const clientId = $(this).data('client-id');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            method: 'POST',
            data: {
                action: 'load_client_dashboard',
                client_id: clientId,
                nonce: '<?php echo wp_create_nonce('client_dashboard_nonce'); ?>',
            },
            success: function (response) {
                $('#client-dashboard').html(response.data);
            }
        });
    });
});
</script>
