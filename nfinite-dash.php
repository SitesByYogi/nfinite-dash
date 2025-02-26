<?php
/**
 * Plugin Name:       Nfinite Dashboard
 * Plugin URI:        https://sitesbyyogi.com/dashboard-plugin
 * Description:       Nfinite Dashboard is a custom WordPress admin dashboard designed to streamline workflow, enhance productivity, and provide quick access to essential tools. Built specifically for WordPress professionals, it replaces the default dashboard with a fully customizable interface that keeps everything organized and accessible in one place.
 * Version:           2.1.0
 * Author:            SitesByYogi
 * Author URI:        https://sitesbyyogi.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nfinite-dash
 * Domain Path:       /languages
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

/**
 * Plugin Version
 */
define('NFINITE_DASH_VERSION', '2.1.0');

/**
 * Define Plugin Path and URL
 */
define('NFINITE_DASH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NFINITE_DASH_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Activation & Deactivation Hooks
 */
function activate_nfinite_dash() {
    require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-activator.php';
    Nfinite_Dash_Activator::activate();
}

function deactivate_nfinite_dash() {
    require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-deactivator.php';
    Nfinite_Dash_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_nfinite_dash');
register_deactivation_hook(__FILE__, 'deactivate_nfinite_dash');

/**
 * Include Core Plugin Files
 */
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-task-cpt.php'; // Task CPT
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-client-cpt.php'; // Clients CPT
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-my-notes-cpt.php'; // Notes CPT
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-my-projects-cpt.php'; // Notes CPT
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-meetings-cpt.php'; // Notes CPT
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-client-relationships.php';


/**
 * Start the Plugin
 */
function run_nfinite_dash() {
    $plugin = new Nfinite_Dash();
    $plugin->run();

    // ✅ Initialize Admin Functionality
    if (is_admin()) {
        require_once NFINITE_DASH_PLUGIN_DIR . 'admin/class-nfinite-dash-admin.php';
        new Nfinite_Dash_Admin('nfinite-dash', NFINITE_DASH_VERSION);
    }
}
add_action('plugins_loaded', 'run_nfinite_dash');


/**
 * Initialize the Admin Functionality
 */
if ( is_admin() ) {
    require_once NFINITE_DASH_PLUGIN_DIR . 'admin/class-nfinite-dash-admin.php';

    $plugin_admin = new Nfinite_Dash_Admin( 'nfinite-dash', NFINITE_DASH_VERSION );

    // Hook to Add Admin Menu
    add_action( 'admin_menu', array( $plugin_admin, 'add_admin_menu' ) );

    // Enqueue Styles & Scripts for Admin
    add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
    add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );
}

// ✅ Handle AJAX Requests for Inline Editing
function update_task_meta_ajax() {
    check_ajax_referer('update_task_meta_nonce', '_ajax_nonce');

    $task_id    = intval($_POST['task_id']);
    $meta_key   = sanitize_text_field($_POST['meta_key']);
    $meta_value = sanitize_text_field($_POST['meta_value']);

    if (!current_user_can('edit_post', $task_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    if (update_post_meta($task_id, $meta_key, $meta_value)) {
        wp_send_json_success(['message' => 'Updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Update failed']);
    }
}
add_action('wp_ajax_update_task_meta', 'update_task_meta_ajax');

// ✅ Handle AJAX Requests for Updating Meeting Status
function update_meeting_status_ajax() {
    check_ajax_referer('update_meeting_status_nonce', '_ajax_nonce');

    $meeting_id    = intval($_POST['meeting_id']);
    $meeting_status = sanitize_text_field($_POST['meeting_status']);

    if (!current_user_can('edit_post', $meeting_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    if (update_post_meta($meeting_id, '_meeting_status', $meeting_status)) {
        wp_send_json_success(['message' => 'Updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Update failed']);
    }
}
add_action('wp_ajax_update_meeting_status', 'update_meeting_status_ajax');

// ✅ Handle AJAX Requests for Updating Project Meta Data
function update_project_meta_ajax() {
    check_ajax_referer('update_project_meta_nonce', '_ajax_nonce');

    $project_id  = intval($_POST['project_id']);
    $meta_key    = sanitize_text_field($_POST['meta_key']);
    $meta_value  = sanitize_text_field($_POST['meta_value']);

    if (!current_user_can('edit_post', $project_id)) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    if (update_post_meta($project_id, $meta_key, $meta_value)) {
        wp_send_json_success(['message' => 'Updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Update failed']);
    }
}
add_action('wp_ajax_update_project_meta', 'update_project_meta_ajax');

// ✅ Search Clients by Name
add_action('wp_ajax_search_clients_dashboard', function () {
    check_ajax_referer('client_search_nonce', 'nonce');
    $query = sanitize_text_field($_POST['query']);

    $clients = get_posts([
        'post_type'      => 'client',
        'posts_per_page' => -1,
        's'              => $query
    ]);

    if ($clients) {
        $results = array_map(function ($client) {
            return ['id' => $client->ID, 'title' => $client->post_title];
        }, $clients);
        wp_send_json_success($results);
    } else {
        wp_send_json_error(['message' => __('No clients found.', 'nfinite-dash')]);
    }
});

// ✅ Load Client Dashboard with Assigned Tasks, Meetings, and Notes
add_action('wp_ajax_load_client_dashboard', function () {
    check_ajax_referer('client_dashboard_nonce', 'nonce');
    $client_id = intval($_POST['client_id']);

    if (!$client_id) {
        wp_send_json_error(['message' => __('Invalid client ID.', 'nfinite-dash')]);
    }

    ob_start();
    ?>
    <div class="client-dashboard-section">
        <h2><?php echo esc_html(get_the_title($client_id)); ?></h2>
        <p>
            <a href="<?php echo get_edit_post_link($client_id); ?>" class="button"><?php _e('Edit Client', 'nfinite-dash'); ?></a>
            <a href="<?php echo get_permalink($client_id); ?>" class="button"><?php _e('View Client', 'nfinite-dash'); ?></a>
        </p>

        <!-- ✅ Assigned Tasks -->
        <h3><?php _e('Assigned Tasks', 'nfinite-dash'); ?></h3>
        <ul>
            <?php
            $tasks = get_posts(['post_type' => 'task_manager_task', 'meta_key' => '_assigned_client', 'meta_value' => $client_id]);
            if ($tasks) {
                foreach ($tasks as $task) {
                    echo '<li><a href="' . get_edit_post_link($task->ID) . '">' . esc_html($task->post_title) . '</a></li>';
                }
            } else {
                echo '<p>No assigned tasks.</p>';
            }
            ?>
        </ul>

        <!-- ✅ Assigned Meetings -->
        <h3><?php _e('Scheduled Meetings', 'nfinite-dash'); ?></h3>
        <ul>
            <?php
            $meetings = get_posts(['post_type' => 'meetings', 'meta_key' => '_assigned_client', 'meta_value' => $client_id]);
            if ($meetings) {
                foreach ($meetings as $meeting) {
                    echo '<li><a href="' . get_edit_post_link($meeting->ID) . '">' . esc_html($meeting->post_title) . '</a></li>';
                }
            } else {
                echo '<p>No scheduled meetings.</p>';
            }
            ?>
        </ul>

        <!-- ✅ Assigned Notes -->
        <h3><?php _e('Client Notes', 'nfinite-dash'); ?></h3>
        <ul>
            <?php
            $notes = get_posts(['post_type' => 'my_notes', 'meta_key' => '_assigned_client', 'meta_value' => $client_id]);
            if ($notes) {
                foreach ($notes as $note) {
                    echo '<li><a href="' . get_edit_post_link($note->ID) . '">' . esc_html($note->post_title) . '</a></li>';
                }
            } else {
                echo '<p>No notes found.</p>';
            }
            ?>
        </ul>
    </div>
    <?php
    wp_send_json_success(ob_get_clean());
});

