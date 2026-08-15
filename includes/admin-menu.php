<?php
/**
 * Nfinite Dashboard admin menu and toolbar.
 *
 * @package Nfinite_Dash
 */

if (!defined('ABSPATH')) {
    exit;
}

function nfinite_dashboard_admin_menu() {
    add_menu_page(
        __('Nfinite Dashboard', 'nfinite-dash'),
        __('Nfinite Dashboard', 'nfinite-dash'),
        'manage_options',
        'nfinite-dash',
        'nfinite_dashboard_render_page',
        'dashicons-analytics',
        2
    );

    add_submenu_page(
        'nfinite-dash',
        __('Dashboard Overview', 'nfinite-dash'),
        __('Dashboard Overview', 'nfinite-dash'),
        'manage_options',
        'nfinite-dash',
        'nfinite_dashboard_render_page'
    );

    add_submenu_page('nfinite-dash', __('Clients', 'nfinite-dash'), __('Clients', 'nfinite-dash'), 'manage_options', 'edit.php?post_type=client');
    add_submenu_page('nfinite-dash', __('Tasks', 'nfinite-dash'), __('Tasks', 'nfinite-dash'), 'manage_options', 'edit.php?post_type=task_manager_task&page=nfinite-task-cards');
    add_submenu_page('nfinite-dash', __('My Notes', 'nfinite-dash'), __('My Notes', 'nfinite-dash'), 'manage_options', 'edit.php?post_type=my_notes&page=notes-cards-view');
    add_submenu_page('nfinite-dash', __('My Projects', 'nfinite-dash'), __('My Projects', 'nfinite-dash'), 'manage_options', 'edit.php?post_type=my_projects&page=my-projects-cards');
    add_submenu_page('nfinite-dash', __('Meetings', 'nfinite-dash'), __('Meetings', 'nfinite-dash'), 'manage_options', 'edit.php?post_type=meetings');

    add_submenu_page(
        'nfinite-dash',
        __('Settings', 'nfinite-dash'),
        __('Settings', 'nfinite-dash'),
        'manage_options',
        'nfinite-dash-settings',
        'nfinite_dash_render_settings_page'
    );
}
add_action('admin_menu', 'nfinite_dashboard_admin_menu');

function nfinite_dashboard_render_page() {
    $display_file = NFINITE_DASH_PLUGIN_DIR . 'admin/partials/nfinite-dash-admin-display.php';

    if (file_exists($display_file)) {
        include $display_file;
        return;
    }

    echo '<div class="wrap"><h1>' . esc_html__('Nfinite Dashboard', 'nfinite-dash') . '</h1></div>';
}

/**
 * Resolve a saved toolbar URL.
 *
 * Admin-relative paths are converted to admin URLs while absolute URLs are left intact.
 *
 * @param string $url Saved toolbar URL.
 * @return string
 */
function nfinite_dash_resolve_toolbar_url($url) {
    $url = trim((string) $url);

    if ($url === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $url)) {
        return esc_url($url);
    }

    return esc_url(admin_url(ltrim($url, '/')));
}

/**
 * Dynamic Nfinite toolbar shortcuts.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function nfinite_dashboard_toolbar_links($wp_admin_bar) {
    if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
        return;
    }

    if (!(int) get_option('nfinite_dash_toolbar_enabled', 1)) {
        return;
    }

    $wp_admin_bar->add_node([
        'id'    => 'nfinite-dashboard',
        'title' => esc_html__('Nfinite Dashboard', 'nfinite-dash'),
        'href'  => admin_url('admin.php?page=nfinite-dash'),
    ]);

    $links = get_option('nfinite_dash_toolbar_links', []);
    if (!is_array($links) || empty($links)) {
        $links = function_exists('nfinite_dash_default_toolbar_links') ? nfinite_dash_default_toolbar_links() : [];
    }

    foreach ($links as $index => $link) {
        $label = isset($link['label']) ? sanitize_text_field($link['label']) : '';
        $href  = isset($link['url']) ? nfinite_dash_resolve_toolbar_url($link['url']) : '';

        if ($label === '' || $href === '') {
            continue;
        }

        $node = [
            'id'     => 'nfinite-toolbar-link-' . absint($index),
            'parent' => 'nfinite-dashboard',
            'title'  => esc_html($label),
            'href'   => $href,
        ];

        if (!empty($link['new_tab'])) {
            $node['meta'] = [
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
            ];
        }

        $wp_admin_bar->add_node($node);
    }
}
add_action('admin_bar_menu', 'nfinite_dashboard_toolbar_links', 999);
