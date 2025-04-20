<?php
/**
 * Dashboard Tasks Section
 *
 * Displays a list of active tasks with priority and status.
 *
 * @package Nfinite_Dash
 */

// Fetch Active Tasks (Exclude Completed Tasks)
$tasks = get_posts([
    'post_type'      => 'task_manager_task',
    'posts_per_page' => 6,
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

?>
<div class="dashboard-tasks-grid">
    <?php if ($tasks): ?>
        <?php foreach ($tasks as $task): 
            $task_id    = $task->ID;
            $client_id  = get_post_meta($task_id, '_assigned_client', true);
            $client_name = $client_id ? get_the_title($client_id) : __('Unassigned', 'nfinite-dash');
            $client_edit_link = $client_id ? get_edit_post_link($client_id) : '#';
            $due_date   = get_post_meta($task_id, '_task_due_date', true);
            $priority   = get_post_meta($task_id, '_task_priority', true);
            $status     = get_post_meta($task_id, '_task_status', true);
        ?>
            <div class="task-card">
                <h3 class="task-title">
                    <a href="<?php echo get_edit_post_link($task_id); ?>">
                        <?php echo esc_html($task->post_title); ?>
                    </a>
                </h3>

                <p><strong><?php _e('Assigned Client:', 'nfinite-dash'); ?></strong> 
                    <a href="<?php echo esc_url($client_edit_link); ?>"><?php echo esc_html($client_name); ?></a>
                </p>

                <p><strong><?php _e('Due Date:', 'nfinite-dash'); ?></strong> 
                    <?php echo esc_html($due_date ? date('F j, Y', strtotime($due_date)) : __('No Due Date', 'nfinite-dash')); ?>
                </p>

                <!-- ✅ Editable Priority Dropdown -->
<label for="task-priority-<?php echo esc_attr($task_id); ?>"><?php _e('Priority:', 'nfinite-dash'); ?></label>
<select class="task-meta-dropdown" id="task-priority-<?php echo esc_attr($task_id); ?>" data-task-id="<?php echo esc_attr($task_id); ?>" data-meta-key="_task_priority">
    <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
    <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
    <option value="high" <?php selected($priority, 'high'); ?>>High</option>
    <option value="urgent" <?php selected($priority, 'urgent'); ?>>Urgent</option>
</select>

<!-- ✅ Editable Status Dropdown -->
<label for="task-status-<?php echo esc_attr($task_id); ?>"><?php _e('Status:', 'nfinite-dash'); ?></label>
<select class="task-meta-dropdown" id="task-status-<?php echo esc_attr($task_id); ?>" data-task-id="<?php echo esc_attr($task_id); ?>" data-meta-key="_task_status">
    <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
    <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
    <option value="complete" <?php selected($status, 'complete'); ?>>Complete</option>
</select>


                <!-- Edit Button -->
                <div class="task-actions">
                    <a href="<?php echo get_edit_post_link($task_id); ?>" class="button button-secondary">
                        <?php _e('Edit Task', 'nfinite-dash'); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p><?php _e('No active tasks found.', 'nfinite-dash'); ?></p>
    <?php endif; ?>
</div>
<br>
<div class="tasks-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=task_manager_task'); ?>" class="button button-primary"><?php _e('Add New Task', 'nfinite-dash'); ?></a>
    <a href="<?php echo admin_url('edit.php?post_type=task_manager_task&page=nfinite-task-cards'); ?>" class="button"><?php _e('View All Tasks', 'nfinite-dash'); ?></a>
</div>

<!-- ✅ JavaScript for AJAX Inline Editing -->
<script>
jQuery(document).ready(function ($) {
    console.log("Nfinite Dashboard Script Loaded");

    /**
     * ✅ Function to update Task Metadata via AJAX
     */
    function updateTaskMeta(taskId, metaKey, metaValue) {
        if (!taskId || !metaKey) {
            console.error("Missing taskId or metaKey for AJAX update.");
            return;
        }

        console.log("Updating Task:", { taskId, metaKey, metaValue });

        $.ajax({
            url: taskManagerAjax.ajax_url, // Ensure taskManagerAjax is localized
            type: "POST",
            data: {
                action: "task_manager_update_meta",
                task_id: taskId,
                meta_key: metaKey,
                meta_value: metaValue,
                _ajax_nonce: taskManagerAjax.nonce // Ensure correct nonce
            },
            success: function (response) {
                if (response.success) {
                    console.log(`Updated ${metaKey} successfully.`);
                } else {
                    console.error(`Failed to update ${metaKey}:`, response.data);
                    alert(`Failed to update ${metaKey}`);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", error);
                alert("AJAX Error: Unable to update task.");
            }
        });
    }

    /**
     * ✅ Event Listener for Task Dropdown Changes
     */
    $(document).on("change", ".task-meta-dropdown", function () {
        let taskId = $(this).data("task-id");
        let metaKey = $(this).data("meta-key");
        let metaValue = $(this).val();

        updateTaskMeta(taskId, metaKey, metaValue);
    });
});

</script>

