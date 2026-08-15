<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_date_time = wp_date('l, F j · g:i A T', current_time('timestamp'));

$active_projects = get_posts([
    'post_type'      => 'my_projects',
    'post_status'    => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
        'relation' => 'OR',
        [
            'key'     => '_project_status',
            'value'   => 'completed',
            'compare' => '!=',
        ],
        [
            'key'     => '_project_status',
            'compare' => 'NOT EXISTS',
        ],
    ],
]);

$open_tasks = get_posts([
    'post_type'      => 'task_manager_task',
    'post_status'    => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_query'     => [
        'relation' => 'OR',
        [
            'key'     => '_task_status',
            'value'   => ['pending', 'in_progress'],
            'compare' => 'IN',
        ],
        [
            'key'     => '_task_status',
            'compare' => 'NOT EXISTS',
        ],
    ],
]);

$client_counts = wp_count_posts('client');
$total_clients = 0;
foreach (['publish', 'draft', 'private', 'pending'] as $status_key) {
    $total_clients += isset($client_counts->{$status_key}) ? (int) $client_counts->{$status_key} : 0;
}

$upcoming_meetings = get_posts([
    'post_type'      => 'meetings',
    'post_status'    => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_meeting_date',
    'meta_value'     => wp_date('Y-m-d', current_time('timestamp')),
    'meta_compare'   => '>=',
]);
?>

<div class="wrap nfinite-dashboard">
    <section class="nfinite-hero">
        <div class="nfinite-hero__copy">
            <span class="nfinite-eyebrow"><?php esc_html_e('WORKSPACE', 'nfinite-dash'); ?></span>
            <h1><?php esc_html_e('Nfinite Dashboard', 'nfinite-dash'); ?></h1>
            <p><?php esc_html_e('Projects, tasks, clients, meetings, and notes — organized in one place.', 'nfinite-dash'); ?></p>
        </div>
        <div class="nfinite-hero__time">
            <span class="dashicons dashicons-clock" aria-hidden="true"></span>
            <span><?php echo esc_html($current_date_time); ?></span>
        </div>
    </section>

    <div class="nfinite-stats" aria-label="<?php esc_attr_e('Workspace summary', 'nfinite-dash'); ?>">
        <a class="nfinite-stat" href="<?php echo esc_url(admin_url('edit.php?post_type=my_projects&page=my-projects-cards')); ?>">
            <span class="nfinite-stat__icon"><span class="dashicons dashicons-portfolio"></span></span>
            <span class="nfinite-stat__body"><strong><?php echo esc_html(count($active_projects)); ?></strong><small><?php esc_html_e('Active Projects', 'nfinite-dash'); ?></small></span>
            <span class="dashicons dashicons-arrow-right-alt2 nfinite-stat__arrow"></span>
        </a>
        <a class="nfinite-stat" href="<?php echo esc_url(admin_url('edit.php?post_type=task_manager_task&page=nfinite-task-cards')); ?>">
            <span class="nfinite-stat__icon"><span class="dashicons dashicons-yes-alt"></span></span>
            <span class="nfinite-stat__body"><strong><?php echo esc_html(count($open_tasks)); ?></strong><small><?php esc_html_e('Open Tasks', 'nfinite-dash'); ?></small></span>
            <span class="dashicons dashicons-arrow-right-alt2 nfinite-stat__arrow"></span>
        </a>
        <a class="nfinite-stat" href="<?php echo esc_url(admin_url('edit.php?post_type=client')); ?>">
            <span class="nfinite-stat__icon"><span class="dashicons dashicons-groups"></span></span>
            <span class="nfinite-stat__body"><strong><?php echo esc_html($total_clients); ?></strong><small><?php esc_html_e('Clients', 'nfinite-dash'); ?></small></span>
            <span class="dashicons dashicons-arrow-right-alt2 nfinite-stat__arrow"></span>
        </a>
        <a class="nfinite-stat" href="<?php echo esc_url(admin_url('edit.php?post_type=meetings')); ?>">
            <span class="nfinite-stat__icon"><span class="dashicons dashicons-calendar-alt"></span></span>
            <span class="nfinite-stat__body"><strong><?php echo esc_html(count($upcoming_meetings)); ?></strong><small><?php esc_html_e('Upcoming Meetings', 'nfinite-dash'); ?></small></span>
            <span class="dashicons dashicons-arrow-right-alt2 nfinite-stat__arrow"></span>
        </a>
    </div>

    <nav class="dashboard-quick-links" aria-label="<?php esc_attr_e('Nfinite quick links', 'nfinite-dash'); ?>">
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=my_projects')); ?>" class="quick-link quick-link--primary"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e('New Project', 'nfinite-dash'); ?></a>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=task_manager_task')); ?>" class="quick-link"><span class="dashicons dashicons-plus-alt2"></span><?php esc_html_e('New Task', 'nfinite-dash'); ?></a>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=client')); ?>" class="quick-link"><span class="dashicons dashicons-admin-users"></span><?php esc_html_e('New Client', 'nfinite-dash'); ?></a>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=meetings')); ?>" class="quick-link"><span class="dashicons dashicons-calendar-alt"></span><?php esc_html_e('New Meeting', 'nfinite-dash'); ?></a>
        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=my_notes')); ?>" class="quick-link"><span class="dashicons dashicons-edit"></span><?php esc_html_e('New Note', 'nfinite-dash'); ?></a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=nfinite-dash-settings')); ?>" class="quick-link"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e('Settings', 'nfinite-dash'); ?></a>
    </nav>

    <section class="dashboard-section dashboard-section--featured">
        <div class="nfinite-section-heading">
            <div><span class="nfinite-section-kicker"><?php esc_html_e('WORK', 'nfinite-dash'); ?></span><h2><?php esc_html_e('Projects', 'nfinite-dash'); ?></h2></div>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=my_projects&page=my-projects-cards')); ?>"><?php esc_html_e('View all projects', 'nfinite-dash'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a>
        </div>
        <?php include plugin_dir_path(__FILE__) . '/dashboard-projects.php'; ?>
    </section>

    <section class="dashboard-section dashboard-section--featured">
        <div class="nfinite-section-heading">
            <div><span class="nfinite-section-kicker"><?php esc_html_e('ACTION', 'nfinite-dash'); ?></span><h2><?php esc_html_e('Tasks', 'nfinite-dash'); ?></h2></div>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=task_manager_task&page=nfinite-task-cards')); ?>"><?php esc_html_e('View all tasks', 'nfinite-dash'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a>
        </div>
        <?php include plugin_dir_path(__FILE__) . '/dashboard-tasks.php'; ?>
    </section>

    <div class="nfinite-secondary-grid">
        <section class="dashboard-section">
            <div class="nfinite-section-heading"><div><span class="nfinite-section-kicker"><?php esc_html_e('RELATIONSHIPS', 'nfinite-dash'); ?></span><h2><?php esc_html_e('Clients', 'nfinite-dash'); ?></h2></div></div>
            <?php include plugin_dir_path(__FILE__) . '/dashboard-clients.php'; ?>
        </section>

        <section class="dashboard-section">
            <div class="nfinite-section-heading"><div><span class="nfinite-section-kicker"><?php esc_html_e('SCHEDULE', 'nfinite-dash'); ?></span><h2><?php esc_html_e('Meetings', 'nfinite-dash'); ?></h2></div></div>
            <?php include plugin_dir_path(__FILE__) . '/dashboard-meetings.php'; ?>
        </section>
    </div>

    <section class="dashboard-section">
        <div class="nfinite-section-heading"><div><span class="nfinite-section-kicker"><?php esc_html_e('REFERENCE', 'nfinite-dash'); ?></span><h2><?php esc_html_e('Notes', 'nfinite-dash'); ?></h2></div></div>
        <?php include plugin_dir_path(__FILE__) . '/dashboard-notes.php'; ?>
    </section>

    <section class="dashboard-section dashboard-wp-links">
        <div class="nfinite-section-heading">
            <div><span class="nfinite-section-kicker"><?php esc_html_e('WORDPRESS', 'nfinite-dash'); ?></span><h2><?php esc_html_e('Admin Shortcuts', 'nfinite-dash'); ?></h2></div>
        </div>
        <div class="dashboard-wp-links-grid">
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="wp-admin-link"><span class="dashicons dashicons-admin-plugins"></span><?php esc_html_e('Plugins', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('themes.php')); ?>" class="wp-admin-link"><span class="dashicons dashicons-admin-appearance"></span><?php esc_html_e('Themes', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" class="wp-admin-link"><span class="dashicons dashicons-admin-settings"></span><?php esc_html_e('Settings', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('tools.php')); ?>" class="wp-admin-link"><span class="dashicons dashicons-admin-tools"></span><?php esc_html_e('Tools', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('users.php')); ?>" class="wp-admin-link"><span class="dashicons dashicons-admin-users"></span><?php esc_html_e('Users', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=site-health')); ?>" class="wp-admin-link"><span class="dashicons dashicons-heart"></span><?php esc_html_e('Site Health', 'nfinite-dash'); ?></a>
        </div>
    </section>
</div>
