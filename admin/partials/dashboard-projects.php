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
    'posts_per_page' => 6,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
        [
            'key'     => '_project_status',
            'value'   => 'completed',
            'compare' => '!=',
        ],
    ],
]);

?>

<div class="dashboard-projects-grid">
    <?php if ($projects): ?>
        <?php foreach ($projects as $project): 
            $project_id  = $project->ID;
            $status      = get_post_meta($project_id, '_project_status', true);
            $priority    = get_post_meta($project_id, '_project_priority', true);
            $links       = get_post_meta($project_id, '_my_project_links', true);
        ?>

            <div class="project-card">
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



