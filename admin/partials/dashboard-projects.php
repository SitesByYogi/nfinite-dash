<?php
/**
 * Dashboard Projects Section
 *
 * Displays project information in a 3-column grid format.
 *
 * @package Nfinite_Dash
 */

// Fetch Active Projects (Exclude Completed Projects)
$projects = get_posts([
    'post_type'      => 'my_projects',
    'post_status'    => ['publish', 'draft', 'private', 'pending'],
    'posts_per_page' => 6,
    'orderby'        => 'date',
    'order'          => 'DESC',
    // Keep this in sync with the dashboard summary and standalone card view.
    // Projects created before status tracking existed should still be visible.
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

?>

<div class="dashboard-projects-grid">
    <?php if ($projects): ?>
        <?php foreach ($projects as $project): 
            $project_id  = $project->ID;
            $status      = get_post_meta($project_id, '_project_status', true);
            $priority = get_post_meta($project_id, '_project_priority', true);
            $priority = $priority ?: 'medium'; // fallback for unset priorities
            $priority_class = 'priority-' . strtolower($priority);

            $links       = get_post_meta($project_id, '_my_project_links', true);

            // Tasks belong to one primary project. Include 2.2.0 legacy assignments during upgrade.
            $project_task_ids = [];
            $all_task_ids = get_posts([
                'post_type'      => 'task_manager_task',
                'post_status'    => ['publish', 'draft', 'private', 'pending'],
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
            foreach ($all_task_ids as $candidate_task_id) {
                $task_project_id = absint(get_post_meta($candidate_task_id, '_nfinite_project', true));
                if (!$task_project_id) {
                    $legacy_projects = get_post_meta($candidate_task_id, '_nfinite_related_projects', true);
                    $legacy_projects = is_array($legacy_projects) ? array_map('absint', $legacy_projects) : ($legacy_projects ? [absint($legacy_projects)] : []);
                    $task_project_id = !empty($legacy_projects) ? reset($legacy_projects) : 0;
                }
                if ($task_project_id === $project_id) {
                    $project_task_ids[] = absint($candidate_task_id);
                }
            }
            $new_task_url = add_query_arg([
                'post_type' => 'task_manager_task',
                'nfinite_project' => $project_id,
            ], admin_url('post-new.php'));
        ?>

            <?php $priority_class = 'priority-' . strtolower($priority); ?> 
            <div class="project-card <?php echo esc_attr($priority_class); ?>">
                <h3 class="project-title">
                    <a href="<?php echo get_edit_post_link($project_id); ?>">
                        <?php echo esc_html($project->post_title); ?>
                    </a>
                </h3>

                <p><strong><?php _e('Status:', 'nfinite-dash'); ?></strong>
                    <select class="project-status-dropdown" data-project-id="<?php echo esc_attr($project_id); ?>" data-meta-key="_project_status">
                        <option value="not_started" <?php selected($status, 'not_started'); ?>>Not Started</option>
                        <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
                        <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
                    </select>
                </p>

                <p><strong><?php _e('Priority:', 'nfinite-dash'); ?></strong>
                    <select class="project-status-dropdown" data-project-id="<?php echo esc_attr($project_id); ?>" data-meta-key="_project_priority">
                        <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
                        <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
                        <option value="high" <?php selected($priority, 'high'); ?>>High</option>
                        <option value="urgent" <?php selected($priority, 'urgent'); ?>>Urgent</option>
                    </select>
                </p>

                <?php if (!empty($links)): ?>
                    <ul class="project-links">
                        <?php foreach ($links as $link): ?>
                            <li>
                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank">
                                    <?php echo esc_html($link['text']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="nfinite-project-card-tasks">
                    <div class="nfinite-project-card-tasks-heading">
                        <strong><?php _e('Tasks', 'nfinite-dash'); ?></strong>
                        <span><?php echo esc_html(count($project_task_ids)); ?></span>
                    </div>
                    <?php if ($project_task_ids): ?>
                        <ul>
                            <?php foreach (array_slice($project_task_ids, 0, 5) as $task_id):
                                $task_status = get_post_meta($task_id, '_task_status', true);
                            ?>
                                <li>
                                    <a href="<?php echo esc_url(get_edit_post_link($task_id)); ?>"><?php echo esc_html(get_the_title($task_id)); ?></a>
                                    <?php if ($task_status): ?>
                                        <small><?php echo esc_html(ucwords(str_replace(['_', '-'], ' ', $task_status))); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (count($project_task_ids) > 5): ?>
                            <p class="nfinite-project-card-more"><?php echo esc_html(sprintf(__('+ %d more', 'nfinite-dash'), count($project_task_ids) - 5)); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="nfinite-project-card-empty"><?php _e('No tasks yet.', 'nfinite-dash'); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($new_task_url); ?>" class="button button-small"><?php _e('Add Task', 'nfinite-dash'); ?></a>
                </div>

                <div class="project-actions">
                    <a href="<?php echo get_edit_post_link($project_id); ?>" class="button button-secondary">
                        <?php _e('Edit Project', 'nfinite-dash'); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p><?php _e('No projects found.', 'nfinite-dash'); ?></p>
    <?php endif; ?>
</div>

<br>
<!-- View Buttons for Notes Section -->
<div class="projects-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=my_projects'); ?>" class="button button-primary"><?php _e('Add New Project', 'nfinite-dash'); ?></a>
    <a href="<?php echo admin_url('edit.php?post_type=my_projects'); ?>" class="button"><?php _e('View All Projects', 'nfinite-dash'); ?></a>
</div>



