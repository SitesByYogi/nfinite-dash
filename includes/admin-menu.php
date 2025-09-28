<?php

function nfinite_dashboard_admin_menu() {
    // ✅ Main Dashboard Menu (Top Level)
    add_menu_page(
        __('Nfinite Dashboard', 'nfinite-dash'), // Page title
        __('Nfinite Dashboard', 'nfinite-dash'), // Menu title
        'manage_options',
        'nfinite-dash', // Slug (must match parent slug in submenus)
        '',
        'dashicons-analytics',
        2
    );

    // ✅ Correctly Nesting Submenus Under "Nfinite Dashboard"
    add_submenu_page(
        'nfinite-dash',
        __('Dashboard Overview', 'nfinite-dash'),
        __('Dashboard Overview', 'nfinite-dash'),
        'manage_options',
        'nfinite-dash',
        'nfinite_dashboard_render_page' // Main dashboard function
    );

    add_submenu_page(
        'nfinite-dash',
        __('Clients', 'nfinite-dash'),
        __('Clients', 'nfinite-dash'),
        'manage_options',
        'edit.php?post_type=client'
    );

    add_submenu_page(
        'nfinite-dash',
        __('Tasks', 'nfinite-dash'),
        __('Tasks', 'nfinite-dash'),
        'manage_options',
        'edit.php?post_type=task_manager_task&page=nfinite-task-cards'
    );

    add_submenu_page(
        'nfinite-dash',
        __('My Notes', 'nfinite-dash'),
        __('My Notes', 'nfinite-dash'),
        'manage_options',
        'edit.php?post_type=my_notes&page=notes-cards-view'
    );

    add_submenu_page(
        'nfinite-dash',
        __('My Projects', 'nfinite-dash'),
        __('My Projects', 'nfinite-dash'),
        'manage_options',
        'edit.php?post_type=my_projects&page=my-projects-cards'
    );

    add_submenu_page(
        'nfinite-dash',
        __('Meetings', 'nfinite-dash'),
        __('Meetings', 'nfinite-dash'),
        'manage_options',
        'edit.php?post_type=meetings'
    );

    // Settings (for calendar embed + more later)
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

/**
 * ✅ Render the Main Dashboard Page
 */
function nfinite_dashboard_render_page() {
    echo '<h1>Nfinite Dashboard Overview</h1>';
    echo '<p>Welcome to your custom dashboard!</p>';
}

// Add Toolbar Menu Items to Admin Bar
add_action('admin_bar_menu', 'nfinite_dashboard_toolbar_links', 999);

function nfinite_dashboard_toolbar_links($wp_admin_bar) {
    // ✅ Add Parent Menu (Nfinite Dashboard)
    $wp_admin_bar->add_node([
        'id'    => 'nfinite-dashboard',
        'title' => 'Nfinite Dashboard',
        'href'  => admin_url('admin.php?page=nfinite-dash'), // Ensure this matches your primary dashboard link
    ]);

    // ✅ Add Task Manager as a Submenu
    $wp_admin_bar->add_node([
        'id'     => 'task-manager',
        'parent' => 'nfinite-dashboard',
        'title'  => 'Tasks',
        'href'   => admin_url('edit.php?post_type=task_manager_task&page=nfinite-task-cards'),
    ]);

    // ✅ Add Clients as a Submenu
    $wp_admin_bar->add_node([
        'id'     => 'clients',
        'parent' => 'nfinite-dashboard',
        'title'  => 'Clients',
        'href'   => admin_url('edit.php?post_type=client'),
    ]);

    // ✅ Add Meetings as a Submenu
    $wp_admin_bar->add_node([
        'id'     => 'meetings',
        'parent' => 'nfinite-dashboard',
        'title'  => 'Meetings',
        'href'   => admin_url('edit.php?post_type=meetings'),
    ]);

    // ✅ Add My Notes as a Submenu
    $wp_admin_bar->add_node([
        'id'     => 'my-notes',
        'parent' => 'nfinite-dashboard',
        'title'  => 'My Notes',
        'href'   => admin_url('edit.php?post_type=my_notes&page=notes-cards-view'),
    ]);

    // ✅ Add My Projects as a Submenu
    $wp_admin_bar->add_node([
        'id'     => 'my-projects',
        'parent' => 'nfinite-dashboard',
        'title'  => 'My Projects',
        'href'   => admin_url('edit.php?post_type=my_projects&page=my-projects-cards'),
    ]);

    // ✅ Add External Links Under My Notes
    $external_links = [
        'my-creds' => [
            'title'  => 'Credentials',
            'href'   => 'https://docs.google.com/spreadsheets/d/14beEQJTCq6aal3pqgurEft4q5wk38pzsBAsSP6xsqUM/edit?gid=0#gid=0',
        ],
        'chat-gpt' => [
            'title'  => 'ChatGPT',
            'href'   => 'https://chatgpt.com/',
        ],
        'gmail' => [
            'title'  => 'Gmail',
            'href'   => 'https://gmail.com/',
        ],
    ];

    foreach ($external_links as $id => $link) {
        $wp_admin_bar->add_node([
            'id'     => $id,
            'parent' => 'my-notes', // Grouped under "My Notes"
            'title'  => $link['title'],
            'href'   => $link['href'],
            'meta'   => [
                'target' => '_blank', // Opens in a new tab
            ],
        ]);
    }
}

