<?php
/**
 * Plugin Name:       Nfinite Dashboard
 * Plugin URI:        https://sitesbyyogi.com/dashboard-plugin
 * Description:       A customizable WordPress workflow dashboard for tasks, clients, projects, meetings, notes, and quick-access tools.
 * Version:           2.3.0
 * Author:            SitesByYogi
 * Author URI:        https://sitesbyyogi.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       nfinite-dash
 * Domain Path:       /languages
 */

if (!defined('WPINC')) {
    die;
}

define('NFINITE_DASH_VERSION', '2.3.0');
define('NFINITE_DASH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NFINITE_DASH_PLUGIN_URL', plugin_dir_url(__FILE__));

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
 * Render the shared header used by Nfinite content workspaces.
 *
 * @param string $title     Workspace title.
 * @param string $post_type Current post type.
 * @param string $subtitle  Short workspace description.
 */
function nfinite_dash_render_content_header($title, $post_type, $subtitle = '') {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->base !== 'edit' || $screen->post_type !== $post_type) {
        return;
    }

    $nav = [
        'my_projects'       => ['label' => __('Projects', 'nfinite-dash'), 'icon' => 'portfolio'],
        'task_manager_task' => ['label' => __('Tasks', 'nfinite-dash'), 'icon' => 'yes-alt'],
        'client'            => ['label' => __('Clients', 'nfinite-dash'), 'icon' => 'groups'],
        'meetings'          => ['label' => __('Meetings', 'nfinite-dash'), 'icon' => 'calendar-alt'],
        'my_notes'          => ['label' => __('Notes', 'nfinite-dash'), 'icon' => 'edit-page'],
    ];

    $counts = wp_count_posts($post_type);
    $published = isset($counts->publish) ? (int) $counts->publish : 0;
    $total = 0;
    foreach (['publish', 'draft', 'pending', 'private'] as $status) {
        $total += isset($counts->{$status}) ? (int) $counts->{$status} : 0;
    }
    ?>
    <div class="nfinite-content-shell">
        <div class="nfinite-content-hero">
            <div class="nfinite-content-hero__main">
                <span class="nfinite-content-eyebrow"><?php esc_html_e('NFINITE WORKSPACE', 'nfinite-dash'); ?></span>
                <h1><?php echo esc_html($title); ?></h1>
                <?php if ($subtitle) : ?><p><?php echo esc_html($subtitle); ?></p><?php endif; ?>
            </div>
            <div class="nfinite-content-hero__meta">
                <div class="nfinite-content-count"><strong><?php echo esc_html(number_format_i18n($total)); ?></strong><span><?php esc_html_e('Total', 'nfinite-dash'); ?></span></div>
                <div class="nfinite-content-count"><strong><?php echo esc_html(number_format_i18n($published)); ?></strong><span><?php esc_html_e('Published', 'nfinite-dash'); ?></span></div>
                <div class="nfinite-content-clock"><span class="dashicons dashicons-clock"></span><?php echo esc_html(wp_date('M j, Y · g:i A')); ?></div>
            </div>
        </div>
        <nav class="nfinite-content-nav" aria-label="<?php esc_attr_e('Nfinite workspaces', 'nfinite-dash'); ?>">
            <a class="nfinite-content-nav__overview" href="<?php echo esc_url(admin_url('admin.php?page=nfinite-dash')); ?>"><span class="dashicons dashicons-dashboard"></span><?php esc_html_e('Overview', 'nfinite-dash'); ?></a>
            <?php foreach ($nav as $type => $item) : ?>
                <a class="<?php echo $type === $post_type ? 'is-active' : ''; ?>" href="<?php echo esc_url(admin_url('edit.php?post_type=' . $type)); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></span><?php echo esc_html($item['label']); ?>
                </a>
            <?php endforeach; ?>
            <a href="<?php echo esc_url(admin_url('profile.php')); ?>"><span class="dashicons dashicons-admin-users"></span><?php esc_html_e('Profile', 'nfinite-dash'); ?></a>
        </nav>
    </div>
    <?php
}

// Core and content types. Each CPT class self-registers its WordPress hooks.
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-task-cpt.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-client-cpt.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-my-notes-cpt.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-my-projects-cpt.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-meetings-cpt.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-settings.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/admin-menu.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-client-relationships.php';
require_once NFINITE_DASH_PLUGIN_DIR . 'includes/class-nfinite-dash-frontend-tools.php';

// Frontend shortcodes and project intake.
new Nfinite_Dash_Frontend_Tools();

/**
 * Start the plugin once. Admin assets are registered by the core loader;
 * the admin menu is owned by includes/admin-menu.php.
 */
function run_nfinite_dash() {
    $plugin = new Nfinite_Dash();
    $plugin->run();
}
add_action('plugins_loaded', 'run_nfinite_dash');

/**
 * Search clients from the dashboard client picker.
 */
function nfinite_dash_ajax_search_clients_dashboard() {
    check_ajax_referer('client_search_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Permission denied.', 'nfinite-dash')], 403);
    }

    $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
    $clients = get_posts([
        'post_type'      => 'client',
        'post_status'    => ['publish', 'draft', 'private', 'pending'],
        'posts_per_page' => 25,
        's'              => $query,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    $results = array_map(function ($client) {
        return ['id' => $client->ID, 'title' => $client->post_title];
    }, $clients);

    wp_send_json_success($results);
}
add_action('wp_ajax_search_clients_dashboard', 'nfinite_dash_ajax_search_clients_dashboard');

/**
 * Load a compact client relationship dashboard.
 */
function nfinite_dash_ajax_load_client_dashboard() {
    check_ajax_referer('client_dashboard_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Permission denied.', 'nfinite-dash')], 403);
    }

    $client_id = isset($_POST['client_id']) ? absint($_POST['client_id']) : 0;
    if (!$client_id || get_post_type($client_id) !== 'client') {
        wp_send_json_error(['message' => __('Invalid client ID.', 'nfinite-dash')]);
    }

    $sections = [
        'task_manager_task' => __('Assigned Tasks', 'nfinite-dash'),
        'my_projects'       => __('Projects', 'nfinite-dash'),
        'meetings'          => __('Scheduled Meetings', 'nfinite-dash'),
        'my_notes'          => __('Client Notes', 'nfinite-dash'),
    ];

    ob_start();
    ?>
    <div class="client-dashboard-section">
        <h2><?php echo esc_html(get_the_title($client_id)); ?></h2>
        <p><a href="<?php echo esc_url(get_edit_post_link($client_id)); ?>" class="button"><?php esc_html_e('Edit Client', 'nfinite-dash'); ?></a></p>
        <?php foreach ($sections as $post_type => $heading) : ?>
            <h3><?php echo esc_html($heading); ?></h3>
            <?php
            $items = get_posts([
                'post_type'      => $post_type,
                'post_status'    => ['publish', 'draft', 'private', 'pending'],
                'posts_per_page' => -1,
                'meta_key'       => '_assigned_client',
                'meta_value'     => $client_id,
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);
            ?>
            <?php if ($items) : ?>
                <ul>
                    <?php foreach ($items as $item) : ?>
                        <li><a href="<?php echo esc_url(get_edit_post_link($item->ID)); ?>"><?php echo esc_html($item->post_title); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No assigned items.', 'nfinite-dash'); ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php

    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_load_client_dashboard', 'nfinite_dash_ajax_load_client_dashboard');
