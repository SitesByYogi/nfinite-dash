<?php
/**
 * Dashboard Projects Section
 *
 * Displays project information in a 3-column grid format.
 *
 * @package Nfinite_Dash
 */

// Fetch Active Projects (Include "Not Started" and "In Progress", Exclude "Completed")
$projects = get_posts([
    'post_type'      => 'my_projects',
    'posts_per_page' => 6, // Show up to 6 projects
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
        'relation' => 'OR', // ✅ Ensures we include projects with "not_started" & "in_progress"
        [
            'key'     => '_my_project_status',
            'value'   => 'completed',
            'compare' => '!=', // ✅ Exclude completed projects
        ],
        [
            'key'     => '_my_project_status',
            'compare' => 'NOT EXISTS', // ✅ Include projects where status is NOT set
        ],
    ],
]);

?>

<div class="dashboard-projects-grid">
    <?php if ($projects): ?>
        <?php foreach ($projects as $project): 
            $project_id  = $project->ID;
            $status      = get_post_meta($project_id, '_my_project_status', true) ?: 'not_started'; // ✅ Default to "not_started"
            $links       = get_post_meta($project_id, '_my_project_links', true);
            ?>

            <div class="project-card">
                <h3 class="project-title">
                    <a href="<?php echo get_edit_post_link($project_id); ?>">
                        <?php echo esc_html($project->post_title); ?>
                    </a>
                </h3>

                <p><strong><?php _e('Status:', 'nfinite-dash'); ?></strong>
                    <select class="project-status-dropdown" data-project-id="<?php echo esc_attr($project_id); ?>">
                        <option value="not_started" <?php selected($status, 'not_started'); ?>>Not Started</option>
                        <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
                        <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
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

<div class="projects-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=my_projects'); ?>" class="button button-primary"><?php _e('Add New Project', 'nfinite-dash'); ?></a>
    <a href="<?php echo admin_url('edit.php?post_type=my_projects'); ?>" class="button"><?php _e('View All Projects', 'nfinite-dash'); ?></a>
</div>



