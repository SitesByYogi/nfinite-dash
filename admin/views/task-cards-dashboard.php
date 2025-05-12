<div class="wrap">
    <h1><?php _e('Nfinite Task Cards', 'task-manager'); ?></h1>

    <div class="dashboard-tasks-grid">
        <?php
        $tasks = get_posts([
    'post_type'      => 'task_manager_task',
    'posts_per_page' => -1, // get all for now, limit after sorting
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

// ✅ Manually sort by priority
usort($tasks, function ($a, $b) {
    $priority_map = ['urgent' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
    
    $a_priority = get_post_meta($a->ID, '_task_priority', true);
    $b_priority = get_post_meta($b->ID, '_task_priority', true);

    return ($priority_map[$b_priority] ?? 0) <=> ($priority_map[$a_priority] ?? 0);
});

// ✅ Optional: limit to top 6 after sorting | set to show all
// $tasks = array_slice($tasks, 0, 6);

        if ($tasks) {
            foreach ($tasks as $task) {
                $task_id     = $task->ID;
                $client_id   = get_post_meta($task_id, '_assigned_client', true);
                $client_name = $client_id ? get_the_title($client_id) : __('Unassigned', 'nfinite-dash');
                $client_edit_link = $client_id ? get_edit_post_link($client_id) : '#';
                $due_date    = get_post_meta($task_id, '_task_due_date', true);
                $priority    = get_post_meta($task_id, '_task_priority', true);
                $status      = get_post_meta($task_id, '_task_status', true);
                ?>
                <?php $priority_class = 'priority-' . strtolower($priority); ?>
                <div class="task-card <?php echo esc_attr($priority_class); ?>">

                    <h3 class="task-title">
                        <a href="<?php echo get_edit_post_link($task_id); ?>">
                            <?php echo esc_html($task->post_title); ?>
                        </a>
                    </h3>
        
                    <p><strong><?php _e('Assigned Client:', 'nfinite-dash'); ?></strong>
                        <a href="<?php echo esc_url($client_edit_link); ?>">
                            <?php echo esc_html($client_name); ?>
                        </a>
                    </p>
        
                    <p><strong><?php _e('Due Date:', 'nfinite-dash'); ?></strong>
                        <?php echo esc_html($due_date ? date('F j, Y', strtotime($due_date)) : __('No Due Date', 'nfinite-dash')); ?>
                    </p>
        
                    <!-- Priority Dropdown -->
                    <label for="task-priority-<?php echo esc_attr($task_id); ?>"><?php _e('Priority:', 'nfinite-dash'); ?></label>
                    <select class="task-meta-dropdown" id="task-priority-<?php echo esc_attr($task_id); ?>" data-task-id="<?php echo esc_attr($task_id); ?>" data-meta-key="_task_priority">
                        <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
                        <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
                        <option value="high" <?php selected($priority, 'high'); ?>>High</option>
                        <option value="urgent" <?php selected($priority, 'urgent'); ?>>Urgent</option>
                    </select>
        
                    <!-- Status Dropdown -->
                    <label for="task-status-<?php echo esc_attr($task_id); ?>"><?php _e('Status:', 'nfinite-dash'); ?></label>
                    <select class="task-meta-dropdown" id="task-status-<?php echo esc_attr($task_id); ?>" data-task-id="<?php echo esc_attr($task_id); ?>" data-meta-key="_task_status">
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                        <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
                        <option value="complete" <?php selected($status, 'complete'); ?>>Complete</option>
                    </select>
        
                    <div class="task-actions">
                        <a href="<?php echo get_edit_post_link($task_id); ?>" class="button button-secondary"><?php _e('Edit Task', 'nfinite-dash'); ?></a>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<p>No active tasks found.</p>';
        }
        ?>
    </div>
     <!-- Footer Buttons -->
     <div class="tasks-buttons" style="margin-top: 20px;">
        <a href="<?php echo admin_url('post-new.php?post_type=task_manager_task'); ?>" class="button button-primary"><?php _e('Add New Task', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('edit.php?post_type=task_manager_task'); ?>" class="button"><?php _e('View All Tasks', 'nfinite-dash'); ?></a>
    </div>

    <!-- ✅ List View Section -->
    <?php
// ✅ Fetch Completed Tasks Only
$completed_tasks = get_posts([
    'post_type'      => 'task_manager_task',
    'posts_per_page' => -1,
    'meta_query'     => [
        [
            'key'     => '_task_status',
            'value'   => 'complete',
            'compare' => '='
        ]
    ]
]);
?>

<h2 style="margin-top: 40px;"><?php _e('Completed Tasks', 'nfinite-dash'); ?></h2>

<table class="widefat fixed striped">
    <thead>
        <tr>
            <th><?php _e('Task', 'nfinite-dash'); ?></th>
            <th><?php _e('Client', 'nfinite-dash'); ?></th>
            <th><?php _e('Due Date', 'nfinite-dash'); ?></th>
            <th><?php _e('Priority', 'nfinite-dash'); ?></th>
            <th><?php _e('Status', 'nfinite-dash'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($completed_tasks) {
            foreach ($completed_tasks as $task) {
                $task_id     = $task->ID;
                $client_id   = get_post_meta($task_id, '_assigned_client', true);
                $client_name = $client_id ? get_the_title($client_id) : __('Unassigned', 'nfinite-dash');
                $client_edit_link = $client_id ? get_edit_post_link($client_id) : '#';
                $due_date    = get_post_meta($task_id, '_task_due_date', true);
                $priority    = get_post_meta($task_id, '_task_priority', true);
                $status      = get_post_meta($task_id, '_task_status', true);
                ?>
                <tr>
                    <td><a href="<?php echo get_edit_post_link($task_id); ?>"><?php echo esc_html($task->post_title); ?></a></td>
                    <td><a href="<?php echo esc_url($client_edit_link); ?>"><?php echo esc_html($client_name); ?></a></td>
                    <td><?php echo esc_html($due_date ? date('F j, Y', strtotime($due_date)) : '—'); ?></td>
                    <td><?php echo ucfirst($priority); ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="5">' . __('No completed tasks found.', 'nfinite-dash') . '</td></tr>';
        }
        ?>
    </tbody>
</table>
</div>

<script>
jQuery(document).ready(function ($) {
    $(document).on("change", ".task-meta-dropdown", function () {
        const taskId = $(this).data("task-id");
        const metaKey = $(this).data("meta-key");
        const metaValue = $(this).val();

        $.ajax({
            url: taskManagerAjax.ajax_url,
            type: "POST",
            data: {
                action: "task_manager_update_meta",
                task_id: taskId,
                meta_key: metaKey,
                meta_value: metaValue,
                _ajax_nonce: taskManagerAjax.nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log("✅ Updated", metaKey, "to", metaValue);
                } else {
                    console.error("❌ AJAX Response Error:", response);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Failed:", error);
            }
        });
    });
});
</script>
