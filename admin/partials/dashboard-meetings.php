<?php
/**
 * Dashboard Meetings Section
 *
 * Displays upcoming meetings in a 3-column grid format.
 *
 * @package Nfinite_Dash
 */

// Fetch Upcoming Meetings (Ensure meta_key `_meeting_date` exists)
$meetings = get_posts([
    'post_type'      => 'meetings',
    'posts_per_page' => 6, // Show up to 6 upcoming meetings
    'meta_key'       => '_meeting_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [
        [
            'key'     => '_meeting_date',
            'value'   => date('Y-m-d'),
            'compare' => '>=', // Show only future meetings
            'type'    => 'DATE',
        ],
    ],
]);

?>
    <div class="nfinite-calendar-wrapper">
    <?php
$embed = get_option('nfinite_dash_calendar_embed_url', '');
$tz    = get_option('nfinite_dash_calendar_tz', 'America/New_York');

if ($embed && strpos($embed, 'ctz=') === false) {
    $sep   = (strpos($embed, '?') === false) ? '?' : '&';
    $embed = $embed . $sep . 'ctz=' . rawurlencode($tz);
}
?>

<h2 class="nfinite-calendar-title">📅 <?php esc_html_e('Upcoming Meetings Calendar', 'nfinite-dash'); ?></h2>

<?php if (empty($embed)) : ?>
    <div class="notice notice-warning" style="margin:0 0 16px 0;">
        <p><?php esc_html_e('No Google Calendar is configured. Add one in Nfinite Dashboard → Settings.', 'nfinite-dash'); ?></p>
    </div>
<?php else : ?>
    <iframe 
        class="nfinite-calendar"
        src="<?php echo esc_url($embed); ?>"
        style="border:0;"
        width="100%"
        height="600"
        frameborder="0"
        scrolling="no"></iframe>
<?php endif; ?>

</div>
    <div class="dashboard-meetings-grid">
      <?php 
      /*
    $current_time = current_time('Y-m-d H:i:s'); // Get the current time in WP timezone
    $valid_meetings = [];

    if (!empty($meetings)) :
        foreach ($meetings as $meeting) :
            $meeting_id       = $meeting->ID;
            $meeting_date     = get_post_meta($meeting_id, '_meeting_date', true) ?: __('No Date Set', 'nfinite-dash');
            $meeting_time     = get_post_meta($meeting_id, '_meeting_time', true) ?: __('No Start Time', 'nfinite-dash');
            $meeting_end_time = get_post_meta($meeting_id, '_meeting_end_time', true) ?: __('No End Time', 'nfinite-dash');
            $meeting_link     = get_post_meta($meeting_id, '_meeting_link', true);
            $meeting_type     = get_post_meta($meeting_id, '_meeting_type', true) ?: 'other';
            $meeting_status   = get_post_meta($meeting_id, '_meeting_status', true) ?: 'pending';

            // Convert time format to 12-hour AM/PM
            $formatted_start_time = strtotime($meeting_time) ? date('g:i A', strtotime($meeting_time)) : __('No Start Time', 'nfinite-dash');
            $formatted_end_time   = strtotime($meeting_end_time) ? date('g:i A', strtotime($meeting_end_time)) : __('No End Time', 'nfinite-dash');

            // Convert end time to full datetime format for comparison
            $meeting_end_datetime = strtotime("{$meeting_date} {$meeting_end_time}");

            // Skip meeting if its end time has passed
            if ($meeting_end_datetime && $meeting_end_datetime < strtotime($current_time)) {
                continue;
            }

            $valid_meetings[] = $meeting; // Store valid meetings
    ?>
            <div class="meeting-card">
                <h3 class="meeting-title">
                    <a href="<?php echo get_edit_post_link($meeting_id); ?>">
                        <?php echo esc_html($meeting->post_title); ?>
                    </a>
                </h3>

                <p><strong><?php _e('Date:', 'nfinite-dash'); ?></strong> <?php echo esc_html($meeting_date); ?></p>
                <p><strong><?php _e('Start Time:', 'nfinite-dash'); ?></strong> <?php echo esc_html($formatted_start_time); ?></p>
                <p><strong><?php _e('End Time:', 'nfinite-dash'); ?></strong> <?php echo esc_html($formatted_end_time); ?></p>
                <p><strong><?php _e('Status:', 'nfinite-dash'); ?></strong> <?php echo esc_html(ucfirst($meeting_status)); ?></p>

                <?php if ($meeting_link) : ?>
                    <p>
                        <a href="<?php echo esc_url($meeting_link); ?>" target="_blank" class="button button-primary">
                            <?php _e('Join Meeting', 'nfinite-dash'); ?>
                        </a>
                    </p>
                <?php else : ?>
                    <p class="no-meeting-link"><?php _e('No meeting link available.', 'nfinite-dash'); ?></p>
                <?php endif; ?>

                <div class="meeting-actions">
                    <a href="<?php echo get_edit_post_link($meeting_id); ?>" class="button button-secondary">
                        <?php _e('Edit Meeting', 'nfinite-dash'); ?>
                    </a>
                </div>
            </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if (empty($valid_meetings)) : ?>
        <p><?php _e('No upcoming meetings.', 'nfinite-dash'); ?></p>
    <?php endif; ?>
</div>

<!-- ✅ Add New Meeting & View All Meetings Buttons -->
<div class="meetings-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=meetings'); ?>" class="button button-primary">
        <?php _e('Add New Meeting', 'nfinite-dash'); ?>
    </a>
    <a href="<?php echo admin_url('edit.php?post_type=meetings'); ?>" class="button">
        <?php _e('View All Meetings', 'nfinite-dash'); ?>
    </a>
</div>

*/