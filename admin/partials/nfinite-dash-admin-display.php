<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

date_default_timezone_set('America/New_York');
$current_date_time = date('F j, Y - g:i A T');
?>

<div class="wrap">
    <h1><?php echo __("Nfinite Dashboard", 'nfinite-dash'); ?></h1>

    <!-- ✅ Quick Links -->
    <div class="dashboard-quick-links">
        <a href="<?php echo admin_url('edit.php?post_type=my_projects'); ?>" class="quick-link"><?php _e('My Projects', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('edit.php?post_type=my_notes'); ?>" class="quick-link"><?php _e('My Notes', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('edit.php?post_type=task_manager_task'); ?>" class="quick-link"><?php _e('Tasks', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('edit.php?post_type=meetings'); ?>" class="quick-link"><?php _e('Meetings', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('edit.php?post_type=client'); ?>" class="quick-link"><?php _e('Clients', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('profile.php'); ?>" class="quick-link"><?php _e('My Profile', 'nfinite-dash'); ?></a>
    </div>

    <!-- ✅ Date & Time -->
    <div class="dashboard-date-time">
        <p class="dashboard-date-time-text"><?php echo esc_html($current_date_time); ?></p>
    </div>

    <div class="dashboard-section">
    <h2><?php _e('Clients', 'nfinite-dash'); ?></h2>
    <?php include plugin_dir_path(__FILE__) . '/dashboard-clients.php'; ?>
</div>

<div class="dashboard-section">
    <h2><?php _e('Tasks', 'nfinite-dash'); ?></h2>
    <?php include plugin_dir_path(__FILE__) . '/dashboard-tasks.php'; ?>
</div>

<div class="dashboard-section">
    <h2><?php _e('Meetings', 'nfinite-dash'); ?></h2>
    <?php include plugin_dir_path(__FILE__) . '/dashboard-meetings.php'; ?>
</div>

<div class="dashboard-section">
    <h2><?php _e('Notes', 'nfinite-dash'); ?></h2>
    <?php include plugin_dir_path(__FILE__) . '/dashboard-notes.php'; ?>
</div>

<div class="dashboard-section">
    <h2><?php _e('My Projects', 'nfinite-dash'); ?></h2>
    <?php include plugin_dir_path(__FILE__) . '/dashboard-projects.php'; ?>
</div>

    


    <!-- ✅ General WordPress Admin Links Section -->
    <div class="dashboard-section dashboard-wp-links">
        <h2><?php echo esc_html__('WordPress Admin Links', 'nfinite-dash'); ?></h2>
        <div class="dashboard-wp-links-grid">
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="wp-admin-link"><?php echo esc_html__('Plugins', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('themes.php')); ?>" class="wp-admin-link"><?php echo esc_html__('Themes', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" class="wp-admin-link"><?php echo esc_html__('Settings', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('tools.php')); ?>" class="wp-admin-link"><?php echo esc_html__('Tools', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('users.php')); ?>" class="wp-admin-link"><?php echo esc_html__('Users', 'nfinite-dash'); ?></a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=site-health')); ?>" class="wp-admin-link"><?php echo esc_html__('Site Health', 'nfinite-dash'); ?></a>
        </div>
    </div>
</div>

