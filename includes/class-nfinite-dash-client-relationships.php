<?php
/**
 * Relationship management between Nfinite Dashboard content types.
 *
 * Hierarchy:
 * Client -> Project -> Task
 *
 * Meetings and Notes can support a project and may also be attached directly
 * to a task when a more specific connection is useful.
 *
 * @package Nfinite_Dash
 */

if (!defined('ABSPATH')) {
    exit;
}

class Nfinite_Dash_Client_Relationships {

    const PROJECT_META = '_nfinite_project';
    const LEGACY_PROJECTS_META = '_nfinite_related_projects';
    const MEETINGS_META = '_nfinite_related_meetings';
    const NOTES_META = '_nfinite_related_notes';

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_relationship_meta_boxes']);

        add_action('save_post_task_manager_task', [$this, 'save_task_relationships']);
        add_action('save_post_my_projects', [$this, 'save_project_relationships']);
        add_action('save_post_meetings', [$this, 'save_supporting_item_relationships']);
        add_action('save_post_my_notes', [$this, 'save_supporting_item_relationships']);

        add_filter('manage_task_manager_task_posts_columns', [$this, 'add_task_relationship_columns']);
        add_action('manage_task_manager_task_posts_custom_column', [$this, 'populate_task_relationship_columns'], 20, 2);

        add_filter('manage_my_projects_posts_columns', [$this, 'add_project_relationship_columns'], 30);
        add_action('manage_my_projects_posts_custom_column', [$this, 'populate_project_relationship_columns'], 30, 2);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_relationship_assets']);
        add_action('admin_head-post-new.php', [$this, 'prefill_new_task_from_project']);
    }

    /**
     * Register relationship meta boxes.
     */
    public function add_relationship_meta_boxes() {
        add_meta_box(
            'nfinite_task_relationships',
            __('Project & Connections', 'nfinite-dash'),
            [$this, 'render_task_relationships_meta_box'],
            'task_manager_task',
            'normal',
            'high'
        );

        add_meta_box(
            'nfinite_project_tasks',
            __('Project Tasks', 'nfinite-dash'),
            [$this, 'render_project_tasks_meta_box'],
            'my_projects',
            'normal',
            'high'
        );

        add_meta_box(
            'nfinite_primary_client',
            __('Client Relationship', 'nfinite-dash'),
            [$this, 'render_primary_client_meta_box'],
            'my_projects',
            'side',
            'default'
        );

        add_meta_box(
            'nfinite_meeting_project',
            __('Project Relationship', 'nfinite-dash'),
            [$this, 'render_supporting_project_meta_box'],
            'meetings',
            'side',
            'default'
        );

        add_meta_box(
            'nfinite_note_project',
            __('Project Relationship', 'nfinite-dash'),
            [$this, 'render_supporting_project_meta_box'],
            'my_notes',
            'side',
            'default'
        );

        // Meetings do not already have a client selector. Notes do in the existing CPT class.
        add_meta_box(
            'nfinite_meeting_client',
            __('Client Relationship', 'nfinite-dash'),
            [$this, 'render_primary_client_meta_box'],
            'meetings',
            'side',
            'default'
        );
    }

    /**
     * Task relationship editor. A task belongs to one project.
     */
    public function render_task_relationships_meta_box($post) {
        wp_nonce_field('nfinite_save_task_relationships', 'nfinite_task_relationships_nonce');

        $client_id  = absint(get_post_meta($post->ID, '_assigned_client', true));
        $project_id = $this->get_task_project_id($post->ID);
        $meeting_ids = $this->normalize_ids(get_post_meta($post->ID, self::MEETINGS_META, true));
        $note_ids    = $this->normalize_ids(get_post_meta($post->ID, self::NOTES_META, true));

        $clients  = $this->get_items('client');
        $projects = $this->get_items('my_projects');
        $meetings = $this->get_items('meetings');
        $notes    = $this->get_items('my_notes');

        echo '<p class="description">' . esc_html__('Tasks live inside a project. Choose the client first, then assign one primary project. Meetings and notes are optional supporting connections.', 'nfinite-dash') . '</p>';
        echo '<div class="nfinite-relationship-grid nfinite-task-hierarchy-grid">';

        echo '<div class="nfinite-relationship-group">';
        echo '<h4>' . esc_html__('Client', 'nfinite-dash') . '</h4>';
        echo '<select id="nfinite-task-client" class="widefat" name="nfinite_task_client">';
        echo '<option value="">' . esc_html__('No client', 'nfinite-dash') . '</option>';
        foreach ($clients as $client) {
            echo '<option value="' . esc_attr($client->ID) . '" ' . selected($client_id, $client->ID, false) . '>' . esc_html($client->post_title) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('If the selected project has a client, the task will inherit it automatically.', 'nfinite-dash') . '</p>';
        echo '</div>';

        echo '<div class="nfinite-relationship-group">';
        echo '<h4>' . esc_html__('Project', 'nfinite-dash') . '</h4>';
        echo '<select id="nfinite-task-project" class="widefat" name="nfinite_task_project">';
        echo '<option value="">' . esc_html__('No project', 'nfinite-dash') . '</option>';
        foreach ($projects as $project) {
            $project_client = absint(get_post_meta($project->ID, '_assigned_client', true));
            echo '<option value="' . esc_attr($project->ID) . '" data-client="' . esc_attr($project_client) . '" ' . selected($project_id, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('A task can belong to one primary project.', 'nfinite-dash') . '</p>';
        echo '</div>';

        $this->render_multi_support_selector('meetings', 'Meetings', $meetings, $meeting_ids);
        $this->render_multi_support_selector('notes', 'Notes', $notes, $note_ids);

        echo '</div>';
    }

    /**
     * Project-side task manager. This is the parent view of project/task relationships.
     */
    public function render_project_tasks_meta_box($post) {
        wp_nonce_field('nfinite_save_project_tasks', 'nfinite_project_tasks_nonce');

        $task_ids = $this->get_project_task_ids($post->ID);
        $tasks    = $this->get_items('task_manager_task');
        $new_task_url = add_query_arg([
            'post_type'       => 'task_manager_task',
            'nfinite_project' => $post->ID,
        ], admin_url('post-new.php'));

        echo '<div class="nfinite-project-task-toolbar">';
        echo '<p class="description">' . esc_html__('This project is the parent of its tasks. Assign existing tasks below or create a new task already pinned to this project.', 'nfinite-dash') . '</p>';
        echo '<a class="button button-primary" href="' . esc_url($new_task_url) . '">' . esc_html__('Add Task to Project', 'nfinite-dash') . '</a>';
        echo '</div>';

        if ($task_ids) {
            echo '<div class="nfinite-project-task-summary">';
            foreach ($task_ids as $task_id) {
                $status   = get_post_meta($task_id, '_task_status', true);
                $priority = get_post_meta($task_id, '_task_priority', true);
                $due_date = get_post_meta($task_id, '_task_due_date', true);
                echo '<div class="nfinite-project-task-row">';
                echo '<div><a href="' . esc_url(get_edit_post_link($task_id)) . '"><strong>' . esc_html(get_the_title($task_id)) . '</strong></a></div>';
                echo '<div class="nfinite-project-task-meta">';
                if ($status) {
                    echo '<span>' . esc_html(ucwords(str_replace(['_', '-'], ' ', $status))) . '</span>';
                }
                if ($priority) {
                    echo '<span>' . esc_html(ucwords(str_replace(['_', '-'], ' ', $priority))) . '</span>';
                }
                if ($due_date) {
                    echo '<span>' . esc_html($due_date) . '</span>';
                }
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p><em>' . esc_html__('No tasks are currently assigned to this project.', 'nfinite-dash') . '</em></p>';
        }

        echo '<hr>';
        echo '<label for="nfinite-project-task-filter"><strong>' . esc_html__('Assign existing tasks', 'nfinite-dash') . '</strong></label>';
        echo '<input id="nfinite-project-task-filter" type="search" class="widefat nfinite-relationship-filter" placeholder="' . esc_attr__('Filter tasks…', 'nfinite-dash') . '" data-target="#nfinite-project-tasks-select">';
        echo '<select id="nfinite-project-tasks-select" class="widefat nfinite-relationship-select" name="nfinite_project_tasks[]" multiple size="10">';
        foreach ($tasks as $task) {
            $current_project = $this->get_task_project_id($task->ID);
            $suffix = ($current_project && $current_project !== $post->ID)
                ? ' — ' . sprintf(__('currently in %s', 'nfinite-dash'), get_the_title($current_project))
                : '';
            echo '<option value="' . esc_attr($task->ID) . '" ' . selected(in_array($task->ID, $task_ids, true), true, false) . '>' . esc_html($task->post_title . $suffix) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Selecting a task here moves it into this project. Unselecting a task removes it from this project.', 'nfinite-dash') . '</p>';
    }

    /**
     * Project or meeting client assignment.
     */
    public function render_primary_client_meta_box($post) {
        wp_nonce_field('nfinite_save_primary_client', 'nfinite_primary_client_nonce');
        $selected = absint(get_post_meta($post->ID, '_assigned_client', true));
        $clients  = $this->get_items('client');

        echo '<select name="nfinite_primary_client" class="widefat">';
        echo '<option value="">' . esc_html__('No client', 'nfinite-dash') . '</option>';
        foreach ($clients as $client) {
            echo '<option value="' . esc_attr($client->ID) . '" ' . selected($selected, $client->ID, false) . '>' . esc_html($client->post_title) . '</option>';
        }
        echo '</select>';
    }

    /**
     * Meetings and Notes may belong to one project.
     */
    public function render_supporting_project_meta_box($post) {
        wp_nonce_field('nfinite_save_supporting_project', 'nfinite_supporting_project_nonce');
        $selected = absint(get_post_meta($post->ID, self::PROJECT_META, true));
        $projects = $this->get_items('my_projects');

        echo '<select name="nfinite_supporting_project" class="widefat">';
        echo '<option value="">' . esc_html__('No project', 'nfinite-dash') . '</option>';
        foreach ($projects as $project) {
            echo '<option value="' . esc_attr($project->ID) . '" ' . selected($selected, $project->ID, false) . '>' . esc_html($project->post_title) . '</option>';
        }
        echo '</select>';
    }

    private function render_multi_support_selector($key, $label, $items, $selected_ids) {
        $field_id = 'nfinite-task-' . sanitize_html_class($key);
        echo '<div class="nfinite-relationship-group">';
        echo '<h4>' . esc_html__($label, 'nfinite-dash') . '</h4>';
        echo '<input type="search" class="widefat nfinite-relationship-filter" placeholder="' . sprintf(esc_attr__('Filter %s…', 'nfinite-dash'), strtolower($label)) . '" data-target="#' . esc_attr($field_id) . '">';
        echo '<select id="' . esc_attr($field_id) . '" class="widefat nfinite-relationship-select" name="nfinite_task_' . esc_attr($key) . '[]" multiple size="7">';
        foreach ($items as $item) {
            echo '<option value="' . esc_attr($item->ID) . '" ' . selected(in_array($item->ID, $selected_ids, true), true, false) . '>' . esc_html($item->post_title) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Optional supporting connection.', 'nfinite-dash') . '</p>';
        echo '</div>';
    }

    /**
     * Save the task's primary project, client, meetings and notes.
     */
    public function save_task_relationships($post_id) {
        if (!$this->can_save($post_id, 'nfinite_task_relationships_nonce', 'nfinite_save_task_relationships')) {
            return;
        }

        $project_id = isset($_POST['nfinite_task_project']) ? absint($_POST['nfinite_task_project']) : 0;
        if ($project_id && get_post_type($project_id) !== 'my_projects') {
            $project_id = 0;
        }

        if ($project_id) {
            update_post_meta($post_id, self::PROJECT_META, $project_id);
            delete_post_meta($post_id, self::LEGACY_PROJECTS_META);
        } else {
            delete_post_meta($post_id, self::PROJECT_META);
            delete_post_meta($post_id, self::LEGACY_PROJECTS_META);
        }

        $client_id = isset($_POST['nfinite_task_client']) ? absint($_POST['nfinite_task_client']) : 0;
        if ($project_id) {
            $project_client = absint(get_post_meta($project_id, '_assigned_client', true));
            if ($project_client) {
                $client_id = $project_client;
            }
        }
        if ($client_id && get_post_type($client_id) === 'client') {
            update_post_meta($post_id, '_assigned_client', $client_id);
        } else {
            delete_post_meta($post_id, '_assigned_client');
        }

        $meeting_ids = isset($_POST['nfinite_task_meetings'])
            ? $this->validate_ids(wp_unslash($_POST['nfinite_task_meetings']), 'meetings')
            : [];
        $note_ids = isset($_POST['nfinite_task_notes'])
            ? $this->validate_ids(wp_unslash($_POST['nfinite_task_notes']), 'my_notes')
            : [];

        $meeting_ids ? update_post_meta($post_id, self::MEETINGS_META, $meeting_ids) : delete_post_meta($post_id, self::MEETINGS_META);
        $note_ids ? update_post_meta($post_id, self::NOTES_META, $note_ids) : delete_post_meta($post_id, self::NOTES_META);
    }

    /**
     * Save project client plus its child task assignments.
     */
    public function save_project_relationships($post_id) {
        if ($this->can_save($post_id, 'nfinite_primary_client_nonce', 'nfinite_save_primary_client')) {
            $client_id = isset($_POST['nfinite_primary_client']) ? absint($_POST['nfinite_primary_client']) : 0;
            if ($client_id && get_post_type($client_id) === 'client') {
                update_post_meta($post_id, '_assigned_client', $client_id);
            } else {
                delete_post_meta($post_id, '_assigned_client');
            }

            // Keep child tasks aligned with the parent project's client.
            foreach ($this->get_project_task_ids($post_id) as $task_id) {
                if ($client_id) {
                    update_post_meta($task_id, '_assigned_client', $client_id);
                } else {
                    delete_post_meta($task_id, '_assigned_client');
                }
            }
        }

        if (!$this->can_save($post_id, 'nfinite_project_tasks_nonce', 'nfinite_save_project_tasks')) {
            return;
        }

        $new_task_ids = isset($_POST['nfinite_project_tasks'])
            ? $this->validate_ids(wp_unslash($_POST['nfinite_project_tasks']), 'task_manager_task')
            : [];
        $old_task_ids = $this->get_project_task_ids($post_id);

        foreach (array_diff($old_task_ids, $new_task_ids) as $task_id) {
            if ($this->get_task_project_id($task_id) === absint($post_id)) {
                delete_post_meta($task_id, self::PROJECT_META);
                delete_post_meta($task_id, self::LEGACY_PROJECTS_META);
            }
        }

        $client_id = absint(get_post_meta($post_id, '_assigned_client', true));
        foreach ($new_task_ids as $task_id) {
            update_post_meta($task_id, self::PROJECT_META, absint($post_id));
            delete_post_meta($task_id, self::LEGACY_PROJECTS_META);
            if ($client_id) {
                update_post_meta($task_id, '_assigned_client', $client_id);
            }
        }
    }

    /**
     * Save project/client relationships on meetings and notes.
     */
    public function save_supporting_item_relationships($post_id) {
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, ['meetings', 'my_notes'], true)) {
            return;
        }

        if ($this->can_save($post_id, 'nfinite_supporting_project_nonce', 'nfinite_save_supporting_project')) {
            $project_id = isset($_POST['nfinite_supporting_project']) ? absint($_POST['nfinite_supporting_project']) : 0;
            if ($project_id && get_post_type($project_id) === 'my_projects') {
                update_post_meta($post_id, self::PROJECT_META, $project_id);
                $project_client = absint(get_post_meta($project_id, '_assigned_client', true));
                if ($project_client) {
                    update_post_meta($post_id, '_assigned_client', $project_client);
                }
            } else {
                delete_post_meta($post_id, self::PROJECT_META);
            }
        }

        if ($post_type === 'meetings' && $this->can_save($post_id, 'nfinite_primary_client_nonce', 'nfinite_save_primary_client')) {
            $client_id = isset($_POST['nfinite_primary_client']) ? absint($_POST['nfinite_primary_client']) : 0;
            if ($client_id && get_post_type($client_id) === 'client') {
                update_post_meta($post_id, '_assigned_client', $client_id);
            } else {
                delete_post_meta($post_id, '_assigned_client');
            }
        }
    }

    public function add_task_relationship_columns($columns) {
        $columns['nfinite_client']  = __('Client', 'nfinite-dash');
        $columns['nfinite_project'] = __('Project', 'nfinite-dash');
        return $columns;
    }

    public function populate_task_relationship_columns($column, $post_id) {
        if ($column === 'nfinite_client') {
            $client_id = absint(get_post_meta($post_id, '_assigned_client', true));
            echo $client_id ? $this->edit_link($client_id) : '—';
        }
        if ($column === 'nfinite_project') {
            $project_id = $this->get_task_project_id($post_id);
            echo $project_id ? $this->edit_link($project_id) : '—';
        }
    }

    public function add_project_relationship_columns($columns) {
        $columns['nfinite_client'] = __('Client', 'nfinite-dash');
        $columns['nfinite_tasks']  = __('Tasks', 'nfinite-dash');
        return $columns;
    }

    public function populate_project_relationship_columns($column, $post_id) {
        if ($column === 'nfinite_client') {
            $client_id = absint(get_post_meta($post_id, '_assigned_client', true));
            echo $client_id ? $this->edit_link($client_id) : '—';
        }
        if ($column === 'nfinite_tasks') {
            $task_ids = $this->get_project_task_ids($post_id);
            if (!$task_ids) {
                echo '—';
                return;
            }
            $complete = 0;
            foreach ($task_ids as $task_id) {
                $status = strtolower((string) get_post_meta($task_id, '_task_status', true));
                if (in_array($status, ['complete', 'completed'], true)) {
                    $complete++;
                }
            }
            echo esc_html(sprintf('%d total / %d complete', count($task_ids), $complete));
        }
    }

    /**
     * Prefill a newly-created task when opened from a Project.
     */
    public function prefill_new_task_from_project() {
        if (empty($_GET['post_type']) || $_GET['post_type'] !== 'task_manager_task' || empty($_GET['nfinite_project'])) {
            return;
        }
        $project_id = absint($_GET['nfinite_project']);
        if (!$project_id || get_post_type($project_id) !== 'my_projects') {
            return;
        }
        $client_id = absint(get_post_meta($project_id, '_assigned_client', true));
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var project = document.getElementById('nfinite-task-project');
            var client = document.getElementById('nfinite-task-client');
            if (project) {
                project.value = <?php echo wp_json_encode((string) $project_id); ?>;
            }
            if (client && <?php echo wp_json_encode((bool) $client_id); ?>) {
                client.value = <?php echo wp_json_encode((string) $client_id); ?>;
            }
        });
        </script>
        <?php
    }

    public function enqueue_relationship_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, ['task_manager_task', 'client', 'my_projects', 'meetings', 'my_notes'], true)) {
            return;
        }

        wp_enqueue_script('jquery');
        $script = <<<'JS'
(function ($) {
    'use strict';

    $(document).on('input', '.nfinite-relationship-filter', function () {
        var query = $(this).val().toLowerCase();
        var target = $(this).data('target');
        $(target).find('option').each(function () {
            $(this).prop('hidden', query && $(this).text().toLowerCase().indexOf(query) === -1);
        });
    });

    // A project's client is authoritative. Selecting a project updates the Task client picker.
    $(document).on('change', '#nfinite-task-project', function () {
        var clientId = $(this).find('option:selected').data('client');
        if (clientId) {
            $('#nfinite-task-client').val(String(clientId));
        }
    });

    // When a Task client is selected, hide projects assigned to other clients.
    function filterProjectsByClient() {
        var clientId = $('#nfinite-task-client').val();
        var $project = $('#nfinite-task-project');
        if (!$project.length) {
            return;
        }
        $project.find('option').each(function () {
            var optionClient = String($(this).data('client') || '');
            var keep = !$(this).val() || !clientId || !optionClient || optionClient === String(clientId) || $(this).is(':selected');
            $(this).prop('hidden', !keep);
        });
    }
    $(document).on('change', '#nfinite-task-client', filterProjectsByClient);
    $(filterProjectsByClient);
})(jQuery);
JS;
        wp_add_inline_script('jquery', $script);
    }

    /**
     * Canonical project ID for a task, with fallback for 2.2.0 legacy arrays.
     */
    public function get_task_project_id($task_id) {
        $project_id = absint(get_post_meta($task_id, self::PROJECT_META, true));
        if ($project_id && get_post_type($project_id) === 'my_projects') {
            return $project_id;
        }

        $legacy = $this->normalize_ids(get_post_meta($task_id, self::LEGACY_PROJECTS_META, true));
        foreach ($legacy as $legacy_project_id) {
            if (get_post_type($legacy_project_id) === 'my_projects') {
                return $legacy_project_id;
            }
        }
        return 0;
    }

    /**
     * Tasks that belong to a project. Includes legacy 2.2.0 relationships so upgrades do not hide data.
     */
    public function get_project_task_ids($project_id) {
        $project_id = absint($project_id);
        if (!$project_id) {
            return [];
        }

        $tasks = get_posts([
            'post_type'      => 'task_manager_task',
            'post_status'    => ['publish', 'draft', 'private', 'pending'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $matched = [];
        foreach ($tasks as $task_id) {
            if ($this->get_task_project_id($task_id) === $project_id) {
                $matched[] = absint($task_id);
            }
        }
        return $matched;
    }

    private function get_items($post_type) {
        return get_posts([
            'post_type'      => $post_type,
            'post_status'    => ['publish', 'draft', 'private', 'pending'],
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
    }

    private function normalize_ids($value) {
        $ids = is_array($value) ? $value : ($value ? [$value] : []);
        $ids = array_map('absint', $ids);
        return array_values(array_unique(array_filter($ids)));
    }

    private function validate_ids($ids, $post_type) {
        $valid = [];
        foreach ($this->normalize_ids($ids) as $id) {
            if (get_post_type($id) === $post_type) {
                $valid[] = $id;
            }
        }
        return $valid;
    }

    private function can_save($post_id, $nonce_field, $nonce_action) {
        if (!isset($_POST[$nonce_field]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_field])), $nonce_action)) {
            return false;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }
        if (wp_is_post_revision($post_id)) {
            return false;
        }
        return current_user_can('edit_post', $post_id);
    }

    private function edit_link($post_id) {
        $title = get_the_title($post_id);
        $url   = get_edit_post_link($post_id);
        if (!$title || !$url) {
            return '—';
        }
        return '<a href="' . esc_url($url) . '">' . esc_html($title) . '</a>';
    }
}

new Nfinite_Dash_Client_Relationships();
