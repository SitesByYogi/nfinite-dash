<?php
/**
 * Task Manager Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

class Nfinite_Dash_Task_CPT {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_task_meta_boxes'));
        add_action('save_post', array($this, 'save_task_meta_box_data'));

        // ✅ Admin Table Columns
        add_filter('manage_task_manager_task_posts_columns', array($this, 'add_task_columns'));
        add_action('manage_task_manager_task_posts_custom_column', array($this, 'populate_task_columns'), 10, 2);
        add_filter('manage_edit-task_manager_task_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_get_posts', array($this, 'filter_tasks'));

        // ✅ Remove Published Date Column
        add_filter('manage_edit-task_manager_task_columns', array($this, 'remove_unwanted_columns'));

        // ✅ AJAX Handling for Inline Editing
        add_action('wp_ajax_task_manager_update_meta', array($this, 'update_meta_via_ajax'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_inline_edit_scripts'));

        // ✅ Add Completed Tasks Button
        add_action('restrict_manage_posts', array($this, 'add_completed_tasks_button'));
    }

    /**
     * ✅ Register Task Custom Post Type
     */
    public function register_post_type() {
        $args = array(
            'labels'      => array(
                'name'          => __('Tasks', 'task-manager'),
                'singular_name' => __('Task', 'task-manager'),
                'menu_name'     => __('Tasks', 'task-manager'),
                'add_new'       => __('Add New Task', 'task-manager'),
                'all_items'     => __('All Tasks', 'task-manager'),
            ),
            'public'      => false,
            'show_ui'     => true,
            'menu_icon'   => 'dashicons-list-view',
            'supports'    => array('title', 'editor'),
        );
        register_post_type('task_manager_task', $args);
    }

    /**
     * ✅ Remove Published Date Column from the Task Dashboard
     */
    public function remove_unwanted_columns($columns) {
        unset($columns['date']); // Removes the Published Date column
        return $columns;
    }

    /**
 * ✅ Filter: Show Pending & In Progress by Default, or Completed if Selected
 */
public function filter_tasks($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'task_manager_task') {
        
        // Check if we're filtering for completed tasks
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

        if ($filter_status === 'complete') {
            // ✅ Show only completed tasks
            $query->set('meta_query', array(
                array(
                    'key'     => '_task_status',
                    'value'   => 'complete',
                    'compare' => '='
                )
            ));
        } else {
            // ✅ Default: Show pending & in progress tasks, including tasks with NO `_task_status` set
            $query->set('meta_query', array(
                'relation' => 'OR',
                array(
                    'key'     => '_task_status',
                    'value'   => ['pending', 'in_progress'],
                    'compare' => 'IN',
                ),
                array(
                    'key'     => '_task_status',
                    'compare' => 'NOT EXISTS', // Includes tasks where no status is set
                )
            ));
        }
    }
}

    /**
     * ✅ Add Task Meta Boxes (Including Due Date)
     */
    public function add_task_meta_boxes() {
        add_meta_box(
            'task_details',
            __('Task Details', 'task-manager'),
            array($this, 'task_meta_box_callback'),
            'task_manager_task',
            'normal',
            'high'
        );

        add_meta_box(
            'task_due_date',
            __('Task Due Date', 'task-manager'),
            array($this, 'due_date_meta_box_callback'),
            'task_manager_task',
            'side',
            'default'
        );
    }

    /**
     * ✅ Task Meta Box Callback (Status, Priority)
     */
    public function task_meta_box_callback($post) {
        $fields = array(
            '_task_status'   => __('Task Status', 'task-manager'),
            '_task_priority' => __('Priority', 'task-manager'),
        );

        wp_nonce_field('task_manager_save_meta_box_data', 'task_manager_meta_box_nonce');

        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . ':</label>';

            $options = ($key === '_task_status') ?
                ['pending' => 'Pending', 'in_progress' => 'In Progress', 'complete' => 'Complete'] :
                ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

            echo '<select name="' . esc_attr($key) . '">';
            foreach ($options as $option_value => $option_label) {
                echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
            }
            echo '</select><br>';
        }
    }

    /**
     * ✅ Due Date Meta Box Callback
     */
    public function due_date_meta_box_callback($post) {
        $due_date = get_post_meta($post->ID, '_task_due_date', true);
        echo '<label for="task_due_date">' . __('Select Due Date:', 'task-manager') . '</label>';
        echo '<input type="date" name="task_due_date" id="task_due_date" value="' . esc_attr($due_date) . '" />';
    }

    /**
     * ✅ Save Task Meta Box Data (Including Due Date)
     */
    public function save_task_meta_box_data($post_id) {
        if (!isset($_POST['task_manager_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['task_manager_meta_box_nonce'], 'task_manager_save_meta_box_data')) {
            return;
        }

        foreach (['_task_status', '_task_priority', 'task_due_date'] as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, '_' . $key, sanitize_text_field($_POST[$key]));
            }
        }
    }    

    /**
     * ✅ Add Custom Columns to Tasks Admin Table (Including Due Date)
     */
    public function add_task_columns($columns) {
        unset($columns['date']); // Remove Published Date
        $columns['task_status']   = __('Status', 'task-manager');
        $columns['task_priority'] = __('Priority', 'task-manager');
        $columns['task_due_date'] = __('Due Date', 'task-manager');
        return $columns;
    }

    /**
     * ✅ Populate Custom Columns (Including Status & Priority Dropdowns)
     */
    public function populate_task_columns($column, $post_id) {
        $meta_value = get_post_meta($post_id, '_' . $column, true);

        if ($column === 'task_due_date') {
            echo esc_html($meta_value ? date('F j, Y', strtotime($meta_value)) : '—');
        } elseif ($column === 'task_status' || $column === 'task_priority') {
            echo '<select class="task-meta-dropdown" data-task-id="' . esc_attr($post_id) . '" data-meta-key="' . esc_attr($column) . '">';

            $options = ($column === 'task_status') ?
                ['pending' => 'Pending', 'in_progress' => 'In Progress', 'complete' => 'Complete'] :
                ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

            foreach ($options as $option_value => $option_label) {
                echo '<option value="' . esc_attr($option_value) . '" ' . selected($meta_value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
            }
            echo '</select>';
        }
    }
    

    /**
     * ✅ Add "View Completed Tasks" Button
     */
    public function add_completed_tasks_button() {
        global $typenow;

        if ($typenow === 'task_manager_task') {
            $current_filter = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

            $is_completed = ($current_filter === 'complete');
            $button_label = $is_completed ? __('View Active Tasks', 'task-manager') : __('View Completed Tasks', 'task-manager');
            $button_url   = admin_url('edit.php?post_type=task_manager_task');

            if (!$is_completed) {
                $button_url = add_query_arg('filter_status', 'complete', $button_url);
            }

            echo '<a href="' . esc_url($button_url) . '" class="button">' . esc_html($button_label) . '</a>';
        }
    }


    /**
     * ✅ Make Columns Sortable (Including Due Date)
     */
    public function make_columns_sortable($columns) {
        $columns['task_due_date'] = '_task_due_date';
        return $columns;
    }

    /**
     * ✅ AJAX Handler for Updating Meta Fields
     */
    public function update_meta_via_ajax() {
        check_ajax_referer('task_manager_update_meta', '_ajax_nonce');

        $post_id = intval($_POST['post_id']);
        $meta_key = sanitize_text_field($_POST['meta_key']);
        $meta_value = sanitize_text_field($_POST['meta_value']);

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied.');
        }

        if (update_post_meta($post_id, '_' . $meta_key, $meta_value)) {
            wp_send_json_success(['message' => 'Updated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to update.']);
        }
    }    

    /**
     * ✅ Enqueue JavaScript for Inline Editing
     */
    public function enqueue_inline_edit_scripts($hook) {
        if ($hook === 'edit.php' && get_current_screen()->post_type === 'task_manager_task') {
            wp_enqueue_script(
                'nfinite-dash-admin',
                plugin_dir_url(__FILE__) . 'admin/js/nfinite-dash-admin.js',
                ['jquery'],
                '1.0',
                true
            );
    
            wp_localize_script('nfinite-dash-admin', 'taskManagerAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('task_manager_update_meta'),
            ]);
        }
    }
    
}

// ✅ Initialize Task CPT
$task_cpt = new Nfinite_Dash_Task_CPT();


