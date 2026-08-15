<?php
/**
 * Frontend tools for Nfinite Dashboard.
 *
 * @package Nfinite_Dash
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nfinite_Dash_Frontend_Tools {

    public function __construct() {
        add_action('init', [$this, 'register_shortcodes'], 20);

        add_action('admin_post_qck_submit_project', [$this, 'handle_project_submission']);
        add_action('admin_post_nopriv_qck_submit_project', [$this, 'handle_project_submission']);
    }

    public function register_shortcodes() {
        add_shortcode('qck_projects_hero', [$this, 'render_projects_hero']);
        add_shortcode('qck_projects_directory', [$this, 'render_projects_directory']);
        add_shortcode('qck_project_submission_form', [$this, 'render_project_submission_form']);
    }

    private function enabled($option) {
        return (bool) get_option($option, 1);
    }

    private function project_status_label($status) {
        $labels = [
            'not_started' => __('Not Started', 'nfinite-dash'),
            'in_progress' => __('In Progress', 'nfinite-dash'),
            'completed'   => __('Completed', 'nfinite-dash'),
        ];
        return $labels[$status] ?? __('Current', 'nfinite-dash');
    }

    private function priority_label($priority) {
        $labels = [
            'low'    => __('Low Priority', 'nfinite-dash'),
            'medium' => __('Medium Priority', 'nfinite-dash'),
            'high'   => __('High Priority', 'nfinite-dash'),
            'urgent' => __('Urgent', 'nfinite-dash'),
        ];
        return $labels[$priority] ?? __('Priority Pending', 'nfinite-dash');
    }

    private function project_category_name($post_id) {
        $terms = get_the_terms($post_id, 'my_project_category');
        return (!is_wp_error($terms) && !empty($terms)) ? $terms[0]->name : __('General Project', 'nfinite-dash');
    }

    private function get_summary($post_id, $words = 28) {
        $summary = get_the_excerpt($post_id);
        if (!$summary) {
            $summary = wp_strip_all_tags(get_post_field('post_content', $post_id));
        }
        return wp_trim_words($summary, max(8, absint($words)), '...');
    }

    private function get_task_project_id($task_id) {
        $project_id = absint(get_post_meta($task_id, '_nfinite_project', true));
        if ($project_id) {
            return $project_id;
        }
        $legacy = get_post_meta($task_id, '_nfinite_related_projects', true);
        if (is_array($legacy)) {
            foreach ($legacy as $candidate) {
                $candidate = absint($candidate);
                if ($candidate) return $candidate;
            }
        }
        return 0;
    }

    private function get_project_task_ids($project_id) {
        $all = get_posts([
            'post_type'      => 'task_manager_task',
            'post_status'    => ['publish', 'draft', 'private', 'pending'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);
        $matches = [];
        foreach ($all as $task_id) {
            if ($this->get_task_project_id($task_id) === absint($project_id)) {
                $matches[] = $task_id;
            }
        }
        return $matches;
    }

    private function active_project_meta_query() {
        return [
            'relation' => 'OR',
            [
                'key'     => '_project_status',
                'value'   => ['not_started', 'in_progress'],
                'compare' => 'IN',
            ],
            [
                'key'     => '_project_status',
                'compare' => 'NOT EXISTS',
            ],
            [
                'key'     => '_project_status',
                'value'   => '',
                'compare' => '=',
            ],
        ];
    }

    public function render_projects_hero($atts) {
        if (!$this->enabled('nfinite_frontend_hero_enabled')) {
            return '';
        }

        $atts = shortcode_atts([
            'posts'         => get_option('nfinite_frontend_hero_projects', 15),
            'tasks'         => get_option('nfinite_frontend_hero_tasks', 15),
            'show_summary'  => 'yes',
            'summary_words' => 24,
        ], $atts, 'qck_projects_hero');

        $posts_per_page = max(1, absint($atts['posts']));
        $tasks_per_page = max(1, absint($atts['tasks']));
        $show_summary   = strtolower((string) $atts['show_summary']) === 'yes';
        $summary_words  = max(8, absint($atts['summary_words']));

        $projects_query = new WP_Query([
            'post_type'      => 'my_projects',
            'posts_per_page' => $posts_per_page,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $this->active_project_meta_query(),
        ]);

        $active_projects_query = new WP_Query([
            'post_type'      => 'my_projects',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => $this->active_project_meta_query(),
        ]);

        $completed_projects_query = new WP_Query([
            'post_type'      => 'my_projects',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_key'       => '_project_status',
            'meta_value'     => 'completed',
        ]);

        $completed_tasks_query = new WP_Query([
            'post_type'      => 'task_manager_task',
            'posts_per_page' => $tasks_per_page,
            'post_status'    => 'publish',
            'meta_key'       => '_task_status',
            'meta_value'     => 'complete',
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ]);

        $completed_tasks_count_query = new WP_Query([
            'post_type'      => 'task_manager_task',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_key'       => '_task_status',
            'meta_value'     => 'complete',
        ]);

        $counts = wp_count_posts('my_projects');
        $total_projects   = isset($counts->publish) ? (int) $counts->publish : 0;
        $total_active     = count($active_projects_query->posts);
        $total_completed  = count($completed_projects_query->posts);
        $total_done_tasks = count($completed_tasks_count_query->posts);

        $instance_id = 'nfinite-projects-hero-' . wp_rand(1000, 99999);

        ob_start();
        ?>
        <section id="<?php echo esc_attr($instance_id); ?>" class="nfinite-front nfinite-projects-hero">
            <div class="nfinite-front-wrap nfinite-hero-grid">
                <div class="nfinite-hero-copy">
                    <div class="nfinite-eyebrow"><?php esc_html_e('QCK Ops Platform', 'nfinite-dash'); ?></div>
                    <h1><?php esc_html_e('Current projects, priorities, and execution visibility', 'nfinite-dash'); ?></h1>
                    <p class="nfinite-lead"><?php esc_html_e('This hub provides a live view into active projects, priorities, and execution across QCK. It gives immediate visibility into what’s in progress, what’s completed, and where focus is being applied as we build and refine our internal systems.', 'nfinite-dash'); ?></p>
                    <div class="nfinite-stats">
                        <div class="nfinite-stat"><strong><?php echo esc_html($total_projects); ?></strong><span><?php esc_html_e('Total published projects', 'nfinite-dash'); ?></span></div>
                        <div class="nfinite-stat"><strong><?php echo esc_html($total_active); ?></strong><span><?php esc_html_e('Current projects', 'nfinite-dash'); ?></span></div>
                        <div class="nfinite-stat"><strong><?php echo esc_html($total_completed); ?></strong><span><?php esc_html_e('Completed projects', 'nfinite-dash'); ?></span></div>
                        <div class="nfinite-stat"><strong><?php echo esc_html($total_done_tasks); ?></strong><span><?php esc_html_e('Completed tasks', 'nfinite-dash'); ?></span></div>
                    </div>
                    <?php
                    $projects_page_id = absint(get_option('nfinite_frontend_projects_page', 0));
                    $submission_page_id = absint(get_option('nfinite_frontend_submission_page', 0));
                    if ($projects_page_id || $submission_page_id) : ?>
                        <div class="nfinite-hero-actions">
                            <?php if ($projects_page_id) : ?><a class="nfinite-front-button" href="<?php echo esc_url(get_permalink($projects_page_id)); ?>"><?php esc_html_e('View Projects', 'nfinite-dash'); ?></a><?php endif; ?>
                            <?php if ($submission_page_id && $this->enabled('nfinite_frontend_submission_enabled')) : ?><a class="nfinite-front-button is-secondary" href="<?php echo esc_url(get_permalink($submission_page_id)); ?>"><?php esc_html_e('Submit Project', 'nfinite-dash'); ?></a><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="nfinite-panel">
                    <div class="nfinite-panel-head"><h2><?php esc_html_e('Operations Snapshot', 'nfinite-dash'); ?></h2><p><?php esc_html_e('Current projects and completed tasks pulled dynamically.', 'nfinite-dash'); ?></p></div>
                    <div class="nfinite-tabs" role="tablist">
                        <button type="button" class="nfinite-tab-btn is-active" data-nfinite-tab="projects"><?php esc_html_e('Current Projects', 'nfinite-dash'); ?></button>
                        <button type="button" class="nfinite-tab-btn" data-nfinite-tab="completed-tasks"><?php printf(esc_html__('Completed Tasks (%d)', 'nfinite-dash'), $total_done_tasks); ?></button>
                    </div>

                    <div class="nfinite-tab-pane is-active" data-nfinite-pane="projects"><div class="nfinite-scroll-area"><div class="nfinite-list">
                        <?php if ($projects_query->have_posts()) : while ($projects_query->have_posts()) : $projects_query->the_post();
                            $post_id = get_the_ID();
                            $status = get_post_meta($post_id, '_project_status', true);
                            $priority = get_post_meta($post_id, '_project_priority', true);
                            ?>
                            <div class="nfinite-list-item">
                                <div><a class="nfinite-item-title" href="<?php echo esc_url(current_user_can('edit_post', $post_id) ? get_edit_post_link($post_id) : get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title()); ?></a>
                                <span class="nfinite-item-meta"><?php echo esc_html($this->project_category_name($post_id)); ?> • <?php echo esc_html($this->priority_label($priority)); ?></span>
                                <?php if ($show_summary) : ?><span class="nfinite-item-summary"><?php echo esc_html($this->get_summary($post_id, $summary_words)); ?></span><?php endif; ?></div>
                                <span class="nfinite-badge <?php echo esc_attr($status ?: 'not_started'); ?>"><?php echo esc_html($this->project_status_label($status)); ?></span>
                            </div>
                        <?php endwhile; wp_reset_postdata(); else : ?><div class="nfinite-empty"><?php esc_html_e('No current projects found yet.', 'nfinite-dash'); ?></div><?php endif; ?>
                    </div></div></div>

                    <div class="nfinite-tab-pane" data-nfinite-pane="completed-tasks"><div class="nfinite-scroll-area"><div class="nfinite-list">
                        <?php if ($completed_tasks_query->have_posts()) : while ($completed_tasks_query->have_posts()) : $completed_tasks_query->the_post();
                            $task_id = get_the_ID();
                            $priority = get_post_meta($task_id, '_task_priority', true);
                            $project_id = $this->get_task_project_id($task_id);
                            ?>
                            <div class="nfinite-list-item">
                                <div><a class="nfinite-item-title" href="<?php echo esc_url(current_user_can('edit_post', $task_id) ? get_edit_post_link($task_id) : get_permalink($task_id)); ?>"><?php echo esc_html(get_the_title()); ?></a>
                                <span class="nfinite-item-meta"><?php echo esc_html($this->priority_label($priority)); ?><?php if ($project_id) : ?> • <?php echo esc_html(get_the_title($project_id)); ?><?php endif; ?></span>
                                <?php if ($show_summary) : ?><span class="nfinite-item-summary"><?php echo esc_html($this->get_summary($task_id, $summary_words)); ?></span><?php endif; ?></div>
                                <span class="nfinite-badge completed"><?php esc_html_e('Complete', 'nfinite-dash'); ?></span>
                            </div>
                        <?php endwhile; wp_reset_postdata(); else : ?><div class="nfinite-empty"><?php esc_html_e('No completed tasks found yet.', 'nfinite-dash'); ?></div><?php endif; ?>
                    </div></div></div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    public function render_projects_directory($atts) {
        if (!$this->enabled('nfinite_frontend_directory_enabled')) {
            return '';
        }

        $atts = shortcode_atts([
            'posts'         => get_option('nfinite_frontend_directory_posts', 50),
            'show_summary'  => 'yes',
            'summary_words' => 28,
        ], $atts, 'qck_projects_directory');

        $query = new WP_Query([
            'post_type'      => 'my_projects',
            'posts_per_page' => max(1, absint($atts['posts'])),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $active = [];
        $completed = [];
        foreach ($query->posts as $project) {
            $status = get_post_meta($project->ID, '_project_status', true);
            if ($status === 'completed') $completed[] = $project; else $active[] = $project;
        }

        $priority_order = ['urgent', 'high', 'medium', 'low'];
        usort($active, function ($a, $b) use ($priority_order) {
            $ar = array_search(get_post_meta($a->ID, '_project_priority', true), $priority_order, true);
            $br = array_search(get_post_meta($b->ID, '_project_priority', true), $priority_order, true);
            $ar = $ar === false ? 999 : $ar;
            $br = $br === false ? 999 : $br;
            return $ar === $br ? strtotime($b->post_date) <=> strtotime($a->post_date) : $ar <=> $br;
        });
        usort($completed, fn($a, $b) => strtotime($b->post_modified) <=> strtotime($a->post_modified));

        $show_summary = strtolower((string) $atts['show_summary']) === 'yes';
        $summary_words = max(8, absint($atts['summary_words']));

        ob_start();
        ?>
        <section class="nfinite-front nfinite-projects-directory">
            <div class="nfinite-project-section">
                <div class="nfinite-directory-head"><h2><?php esc_html_e('Current Projects', 'nfinite-dash'); ?> <span class="nfinite-count"><?php echo esc_html(count($active)); ?></span></h2><p><?php esc_html_e('Active projects ranked by priority, with urgent projects displayed first.', 'nfinite-dash'); ?></p></div>
                <?php if ($active) : ?><div class="nfinite-project-grid"><?php foreach ($active as $project) $this->render_project_card($project, $show_summary, $summary_words); ?></div><?php else : ?><div class="nfinite-empty"><?php esc_html_e('There are no active projects right now.', 'nfinite-dash'); ?></div><?php endif; ?>
            </div>
            <?php if ($completed) : ?><div class="nfinite-project-section nfinite-completed-section"><div class="nfinite-directory-head"><h2><?php esc_html_e('Completed Projects', 'nfinite-dash'); ?> <span class="nfinite-count"><?php echo esc_html(count($completed)); ?></span></h2><p><?php esc_html_e('Finished projects are kept separate from the active project queue.', 'nfinite-dash'); ?></p></div><div class="nfinite-project-grid"><?php foreach ($completed as $project) $this->render_project_card($project, $show_summary, $summary_words); ?></div></div><?php endif; ?>
        </section>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    private function render_project_card($project, $show_summary, $summary_words) {
        $post_id = $project->ID;
        $status = get_post_meta($post_id, '_project_status', true);
        $priority = get_post_meta($post_id, '_project_priority', true);
        $edit_link = current_user_can('edit_post', $post_id) ? get_edit_post_link($post_id) : '';
$task_ids = $this->get_project_task_ids($post_id);
        $done = 0;
        foreach ($task_ids as $task_id) {
            if (get_post_meta($task_id, '_task_status', true) === 'complete') $done++;
        }
        ?>
        <article class="nfinite-project-card nfinite-status-<?php echo esc_attr($status ?: 'not_started'); ?>">
            <?php if ($edit_link) : ?><a class="nfinite-project-title" href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a><?php else : ?><div class="nfinite-project-title"><?php echo esc_html(get_the_title($post_id)); ?></div><?php endif; ?>
            <div class="nfinite-project-meta"><?php echo esc_html($this->project_category_name($post_id)); ?> <span>•</span> <?php echo esc_html($this->priority_label($priority)); ?></div>
            <?php if ($show_summary) : ?><p class="nfinite-project-summary"><?php echo esc_html($this->get_summary($post_id, $summary_words)); ?></p><?php endif; ?>
            <?php if ($task_ids) : ?><div class="nfinite-project-progress"><div><span><?php esc_html_e('Task progress', 'nfinite-dash'); ?></span><strong><?php echo esc_html($done . ' / ' . count($task_ids)); ?></strong></div><div class="nfinite-progress-track"><span style="width:<?php echo esc_attr(round(($done / count($task_ids)) * 100)); ?>%"></span></div></div><?php endif; ?>
            <div class="nfinite-project-footer"><span class="nfinite-badge <?php echo esc_attr($status ?: 'not_started'); ?>"><?php echo esc_html($this->project_status_label($status)); ?></span><?php if ($edit_link) : ?><a class="nfinite-project-action" href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit Project', 'nfinite-dash'); ?></a><?php endif; ?></div>
        </article>
        <?php
    }

    public function render_project_submission_form($atts) {
        if (!$this->enabled('nfinite_frontend_submission_enabled')) {
            return '';
        }

        $allow_public = (bool) get_option('nfinite_frontend_allow_public_submissions', 1);
        $require_login = (bool) get_option('nfinite_frontend_submission_require_login', 0);
        if (($require_login || !$allow_public) && !is_user_logged_in()) {
            return '<div class="nfinite-front nfinite-form-message nfinite-form-error">' . esc_html__('You must be logged in to submit a project.', 'nfinite-dash') . '</div>';
        }

        $atts = shortcode_atts(['title' => __('Submit a Project', 'nfinite-dash')], $atts, 'qck_project_submission_form');
        $success = isset($_GET['qck_project_submitted']) && sanitize_text_field(wp_unslash($_GET['qck_project_submitted'])) === 'success';
        $error = isset($_GET['qck_project_error']) ? sanitize_key(wp_unslash($_GET['qck_project_error'])) : '';
        $messages = [
            'missing_fields' => __('Please complete all required fields.', 'nfinite-dash'),
            'invalid_email'  => __('Please enter a valid email address.', 'nfinite-dash'),
            'security'       => __('The security check failed. Please refresh the page and try again.', 'nfinite-dash'),
            'spam'           => __('Your submission could not be accepted.', 'nfinite-dash'),
            'rate_limit'     => __('Too many submissions were received. Please try again later.', 'nfinite-dash'),
            'insert_failed'  => __('The project could not be submitted. Please try again.', 'nfinite-dash'),
            'login_required' => __('You must be logged in to submit a project.', 'nfinite-dash'),
            'disabled'       => __('Project submissions are currently disabled.', 'nfinite-dash'),
        ];
        $categories = get_terms(['taxonomy' => 'my_project_category', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        $user = wp_get_current_user();
        $default_priority = get_option('nfinite_frontend_submission_default_priority', 'medium');

        ob_start(); ?>
        <section class="nfinite-front nfinite-project-submission"><div class="nfinite-submission-panel">
            <div class="nfinite-submission-head"><h2><?php echo esc_html($atts['title']); ?></h2><p><?php esc_html_e('Submit a new project request. The project will be reviewed before it is added to the active project directory.', 'nfinite-dash'); ?></p></div>
            <?php if ($success) : ?><div class="nfinite-form-message nfinite-form-success"><?php esc_html_e('Your project was submitted successfully and is awaiting review.', 'nfinite-dash'); ?></div><?php endif; ?>
            <?php if ($error && isset($messages[$error])) : ?><div class="nfinite-form-message nfinite-form-error"><?php echo esc_html($messages[$error]); ?></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="qck_submit_project"><input type="hidden" name="qck_form_started" value="<?php echo esc_attr(time()); ?>"><?php wp_nonce_field('qck_submit_project_action', 'qck_submit_project_nonce'); ?>
                <div class="nfinite-honeypot" aria-hidden="true"><label>Website<input type="text" name="qck_website" value="" tabindex="-1" autocomplete="off"></label></div>
                <div class="nfinite-form-grid">
                    <div class="nfinite-form-field"><label><?php esc_html_e('Your Name', 'nfinite-dash'); ?> <span>*</span></label><input type="text" name="qck_submitter_name" required maxlength="100" autocomplete="name" value="<?php echo esc_attr(is_user_logged_in() ? $user->display_name : ''); ?>"></div>
                    <div class="nfinite-form-field"><label><?php esc_html_e('Your Email', 'nfinite-dash'); ?> <span>*</span></label><input type="email" name="qck_submitter_email" required maxlength="190" autocomplete="email" value="<?php echo esc_attr(is_user_logged_in() ? $user->user_email : ''); ?>"></div>
                    <div class="nfinite-form-field nfinite-full"><label><?php esc_html_e('Project Title', 'nfinite-dash'); ?> <span>*</span></label><input type="text" name="qck_project_title" required maxlength="180"></div>
                    <div class="nfinite-form-field"><label><?php esc_html_e('Priority', 'nfinite-dash'); ?> <span>*</span></label><select name="qck_project_priority" required><?php foreach (['low'=>'Low Priority','medium'=>'Medium Priority','high'=>'High Priority','urgent'=>'Urgent'] as $value=>$label) : ?><option value="<?php echo esc_attr($value); ?>" <?php selected($default_priority, $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></div>
                    <div class="nfinite-form-field"><label><?php esc_html_e('Category', 'nfinite-dash'); ?></label><select name="qck_project_category"><option value=""><?php esc_html_e('General Project', 'nfinite-dash'); ?></option><?php if (!is_wp_error($categories)) foreach ($categories as $category) : ?><option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option><?php endforeach; ?></select></div>
                    <div class="nfinite-form-field nfinite-full"><label><?php esc_html_e('Project Summary', 'nfinite-dash'); ?> <span>*</span></label><textarea name="qck_project_summary" required maxlength="3000" placeholder="<?php esc_attr_e('Describe the request, desired outcome, important details, and any relevant deadlines.', 'nfinite-dash'); ?>"></textarea><small><?php esc_html_e('Do not include passwords, login credentials, private API keys, or other sensitive information.', 'nfinite-dash'); ?></small></div>
                </div>
                <div class="nfinite-submit-row"><p><?php esc_html_e('Submissions are reviewed before appearing in the project directory.', 'nfinite-dash'); ?></p><button type="submit" class="nfinite-submit-button"><?php esc_html_e('Submit Project', 'nfinite-dash'); ?></button></div>
            </form>
        </div></section>
        <?php return ob_get_clean();
    }

    public function handle_project_submission() {
        $redirect_url = wp_get_referer() ?: home_url('/');
        $redirect_error = function ($code) use ($redirect_url) {
            $clean = remove_query_arg(['qck_project_submitted', 'qck_project_error'], $redirect_url);
            wp_safe_redirect(add_query_arg('qck_project_error', sanitize_key($code), $clean));
            exit;
        };

        if (!$this->enabled('nfinite_frontend_submission_enabled')) $redirect_error('disabled');
        $allow_public = (bool) get_option('nfinite_frontend_allow_public_submissions', 1);
        $require_login = (bool) get_option('nfinite_frontend_submission_require_login', 0);
        if (($require_login || !$allow_public) && !is_user_logged_in()) $redirect_error('login_required');

        $nonce = isset($_POST['qck_submit_project_nonce']) ? sanitize_text_field(wp_unslash($_POST['qck_submit_project_nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'qck_submit_project_action')) $redirect_error('security');

        $honeypot = isset($_POST['qck_website']) ? sanitize_text_field(wp_unslash($_POST['qck_website'])) : '';
        if ($honeypot !== '') $redirect_error('spam');

        $started = isset($_POST['qck_form_started']) ? absint($_POST['qck_form_started']) : 0;
        if (!$started || (time() - $started) < 3 || (time() - $started) > DAY_IN_SECONDS) $redirect_error('spam');

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $rate_key = 'nfinite_project_submission_' . md5($remote_ip);
        if (get_transient($rate_key)) $redirect_error('rate_limit');

        $name = isset($_POST['qck_submitter_name']) ? sanitize_text_field(wp_unslash($_POST['qck_submitter_name'])) : '';
        $email = isset($_POST['qck_submitter_email']) ? sanitize_email(wp_unslash($_POST['qck_submitter_email'])) : '';
        $title = isset($_POST['qck_project_title']) ? sanitize_text_field(wp_unslash($_POST['qck_project_title'])) : '';
        $summary = isset($_POST['qck_project_summary']) ? sanitize_textarea_field(wp_unslash($_POST['qck_project_summary'])) : '';
        $priority = isset($_POST['qck_project_priority']) ? sanitize_key(wp_unslash($_POST['qck_project_priority'])) : '';
        $category_id = isset($_POST['qck_project_category']) ? absint($_POST['qck_project_category']) : 0;

        if ($name === '' || $email === '' || $title === '' || $summary === '' || $priority === '') $redirect_error('missing_fields');
        if (!is_email($email)) $redirect_error('invalid_email');
        if (!in_array($priority, ['low','medium','high','urgent'], true)) $priority = get_option('nfinite_frontend_submission_default_priority', 'medium');

        if ($category_id) {
            $term = get_term($category_id, 'my_project_category');
            if (!$term || is_wp_error($term) || $term->taxonomy !== 'my_project_category') $category_id = 0;
        }

        $project_status = get_option('nfinite_frontend_submission_default_status', 'not_started');
        if (!in_array($project_status, ['not_started','in_progress'], true)) $project_status = 'not_started';

        $project_id = wp_insert_post([
            'post_type'    => 'my_projects',
            'post_status'  => 'pending',
            'post_title'   => $title,
            'post_content' => $summary,
            'post_excerpt' => wp_trim_words($summary, 45, '...'),
            'meta_input'   => [
                '_project_status'          => $project_status,
                '_project_priority'        => $priority,
                '_qck_submitter_name'      => $name,
                '_qck_submitter_email'     => $email,
                '_qck_public_submission'   => 'yes',
                '_qck_submission_ip_hash'  => hash('sha256', $remote_ip),
                '_qck_submission_datetime' => current_time('mysql'),
            ],
        ], true);
        if (is_wp_error($project_id) || !$project_id) $redirect_error('insert_failed');
        if ($category_id) wp_set_object_terms($project_id, [$category_id], 'my_project_category', false);
        set_transient($rate_key, 1, 5 * MINUTE_IN_SECONDS);

        // Preserve the original snippet behavior: notify the site administrator.
        $admin_email = get_option('admin_email');
        if ($admin_email && is_email($admin_email)) {
            $edit_url = admin_url('post.php?post=' . absint($project_id) . '&action=edit');
            wp_mail(
                $admin_email,
                sprintf(__('New Project Submission: %s', 'nfinite-dash'), $title),
                implode("\n", [
                    __('A new project was submitted through the Nfinite frontend project form.', 'nfinite-dash'),
                    '',
                    __('Project:', 'nfinite-dash') . ' ' . $title,
                    __('Submitted by:', 'nfinite-dash') . ' ' . $name,
                    __('Email:', 'nfinite-dash') . ' ' . $email,
                    __('Priority:', 'nfinite-dash') . ' ' . ucfirst($priority),
                    '',
                    __('Review the submission:', 'nfinite-dash'),
                    $edit_url,
                ])
            );
        }

        $clean = remove_query_arg(['qck_project_submitted', 'qck_project_error'], $redirect_url);
        wp_safe_redirect(add_query_arg('qck_project_submitted', 'success', $clean));
        exit;
    }
}
