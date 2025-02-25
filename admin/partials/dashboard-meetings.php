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

<div class="dashboard-meetings-grid">
    <?php if (!empty($meetings)) : ?>
        <?php foreach ($meetings as $meeting) : 
            $meeting_id    = $meeting->ID;
            $meeting_date  = get_post_meta($meeting_id, '_meeting_date', true) ?: __('No Date Set', 'nfinite-dash');
            $meeting_time  = get_post_meta($meeting_id, '_meeting_time', true) ?: __('No Time Set', 'nfinite-dash');
            $meeting_link  = get_post_meta($meeting_id, '_meeting_link', true);
            $meeting_type  = get_post_meta($meeting_id, '_meeting_type', true) ?: 'other';
            $meeting_status = get_post_meta($meeting_id, '_meeting_status', true) ?: 'pending';

            // Convert meeting type to readable format
            switch ($meeting_type) {
                case 'google_meet': 
                    $meeting_type_label = 'Google Meet'; 
                    break;
                case 'zoom': 
                    $meeting_type_label = 'Zoom'; 
                    break;
                case 'microsoft_teams': 
                    $meeting_type_label = 'Microsoft Teams'; 
                    break;
                default: 
                    $meeting_type_label = 'Other'; 
                    break;
            }

            // Convert time format to 12-hour AM/PM
            $formatted_time = strtotime($meeting_time) ? date('g:i A', strtotime($meeting_time)) : __('No Time Set', 'nfinite-dash');

            ?>
            <div class="meeting-card">
                <h3 class="meeting-title">
                    <a href="<?php echo get_edit_post_link($meeting_id); ?>">
                        <?php echo esc_html($meeting->post_title); ?>
                    </a>
                </h3>

                <p><strong><?php _e('Date:', 'nfinite-dash'); ?></strong> <?php echo esc_html($meeting_date); ?></p>
                <p><strong><?php _e('Time:', 'nfinite-dash'); ?></strong> <?php echo esc_html($formatted_time); ?></p>
                <p><strong><?php _e('Type:', 'nfinite-dash'); ?></strong> <?php echo esc_html($meeting_type_label); ?></p>
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
    <?php else: ?>
        <p><?php _e('No upcoming meetings.', 'nfinite-dash'); ?></p>
    <?php endif; ?>
</div>

<div class="meetings-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=meetings'); ?>" class="button button-primary"><?php _e('Add New Meeting', 'nfinite-dash'); ?></a>
    <a href="<?php echo admin_url('edit.php?post_type=meetings'); ?>" class="button"><?php _e('View All Meetings', 'nfinite-dash'); ?></a>
</div>
