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
    }

    /**
     * ✅ Register Task Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'          => __('Tasks', 'nfinite-dash'),
            'singular_name' => __('Task', 'nfinite-dash'),
            'menu_name'     => __('Tasks', 'nfinite-dash'),
            'add_new'       => __('Add New Task', 'nfinite-dash'),
            'all_items'     => __('All Tasks', 'nfinite-dash'),
            'edit_item'     => __('Edit Task', 'nfinite-dash'),
            'view_item'     => __('View Task', 'nfinite-dash'),
        );

        $args = array(
            'labels'        => $labels,
            'public'        => true,
            'show_ui'       => true,
            'show_in_menu'  => 'admin.php?page=nfinite-dash',
            'menu_icon'     => 'dashicons-list-view',
            'supports'      => array('title', 'editor'),
            'has_archive'   => false,
        );

        register_post_type('task_manager_task', $args);
    }

    /**
     * ✅ Add Task Meta Boxes
     */
    public function add_task_meta_boxes() {
        add_meta_box(
            'task_details_meta_box',
            __('Task Details', 'nfinite-dash'),
            array($this, 'render_task_details_meta_box'),
            'task_manager_task',
            'normal',
            'default'
        );
    }

    /**
     * ✅ Render Task Details Meta Box (FIXED CALLBACK METHOD)
     */
    public function render_task_details_meta_box($post) {
        $task_status   = get_post_meta($post->ID, '_task_status', true);
        $task_priority = get_post_meta($post->ID, '_task_priority', true);
        $task_due_date = get_post_meta($post->ID, '_task_due_date', true);

        wp_nonce_field('save_task_meta_data', 'task_meta_nonce');

        ?>
        <p>
            <label for="task_status"><?php _e('Status', 'nfinite-dash'); ?>:</label>
            <select name="task_status" id="task_status">
                <option value="pending" <?php selected($task_status, 'pending'); ?>>Pending</option>
                <option value="in_progress" <?php selected($task_status, 'in_progress'); ?>>In Progress</option>
                <option value="complete" <?php selected($task_status, 'complete'); ?>>Complete</option>
            </select>
        </p>

        <p>
            <label for="task_priority"><?php _e('Priority', 'nfinite-dash'); ?>:</label>
            <select name="task_priority" id="task_priority">
                <option value="low" <?php selected($task_priority, 'low'); ?>>Low</option>
                <option value="medium" <?php selected($task_priority, 'medium'); ?>>Medium</option>
                <option value="high" <?php selected($task_priority, 'high'); ?>>High</option>
                <option value="urgent" <?php selected($task_priority, 'urgent'); ?>>Urgent</option>
            </select>
        </p>

        <p>
            <label for="task_due_date"><?php _e('Due Date', 'nfinite-dash'); ?>:</label>
            <input type="date" name="task_due_date" id="task_due_date" value="<?php echo esc_attr($task_due_date); ?>" />
        </p>
        <?php
    }

    /**
     * ✅ Save Task Meta Data
     */
    public function save_task_meta_box_data($post_id) {
        if (!isset($_POST['task_meta_nonce']) || !wp_verify_nonce($_POST['task_meta_nonce'], 'save_task_meta_data')) {
            return;
        }

        if (array_key_exists('task_status', $_POST)) {
            update_post_meta($post_id, '_task_status', sanitize_text_field($_POST['task_status']));
        }

        if (array_key_exists('task_priority', $_POST)) {
            update_post_meta($post_id, '_task_priority', sanitize_text_field($_POST['task_priority']));
        }

        if (array_key_exists('task_due_date', $_POST)) {
            update_post_meta($post_id, '_task_due_date', sanitize_text_field($_POST['task_due_date']));
        }
    }

    /**
     * ✅ Add Task Columns in Admin Dashboard
     */
    public function add_task_columns($columns) {
        $columns['task_status']   = __('Status', 'nfinite-dash');
        $columns['task_priority'] = __('Priority', 'nfinite-dash');
        $columns['task_due_date'] = __('Due Date', 'nfinite-dash');
        return $columns;
    }

    /**
     * ✅ Populate Task Columns
     */
    public function populate_task_columns($column, $post_id) {
        if ($column === 'task_status') {
            echo esc_html(get_post_meta($post_id, '_task_status', true));
        }
        if ($column === 'task_priority') {
            echo esc_html(get_post_meta($post_id, '_task_priority', true));
        }
        if ($column === 'task_due_date') {
            echo esc_html(get_post_meta($post_id, '_task_due_date', true));
        }
    }
}

// ✅ Initialize Task Manager CPT
new Nfinite_Dash_Task_CPT();
