<?php
/**
 * Task Manager Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

 /**
 * ✅ Display Quick Links and Date/Time on Tasks Dashboard
 */
function display_task_dashboard_header() {
    global $pagenow, $post_type;

    // Ensure this only appears on the Tasks CPT admin page
    if ($pagenow === 'edit.php' && $post_type === 'task_manager_task') {
        date_default_timezone_set('America/New_York');
        $current_date_time = date('F j, Y - g:i A T');

        ?>
        <div class="wrap">
            <h1><?php echo __("Nfinite Tasks Dashboard", 'nfinite-dash'); ?></h1>

            <!-- ✅ Quick Links -->
            <div class="dashboard-quick-links">
                <a href="<?php echo admin_url('edit.php?post_type=my_projects'); ?>" class="quick-link"><?php _e('My Projects', 'nfinite-dash'); ?></a>
                <a href="<?php echo admin_url('edit.php?post_type=my_notes'); ?>" class="quick-link"><?php _e('My Notes', 'nfinite-dash'); ?></a>
                <a href="<?php echo admin_url('edit.php?post_type=task_manager_task'); ?>" class="quick-link"><?php _e('Tasks', 'nfinite-dash'); ?></a>
                <a href="<?php echo admin_url('edit.php?post_type=meetings'); ?>" class="quick-link"><?php _e('Meetings', 'nfinite-dash'); ?></a>
                <a href="<?php echo admin_url('edit.php?post_type=client'); ?>" class="quick-link"><?php _e('Clients', 'nfinite-dash'); ?></a>
                <a href="<?php echo admin_url('profile.php'); ?>" class="quick-link"><?php _e('My Profile', 'nfinite-dash'); ?></a>
            </div>

            <!-- ✅ Date & Time -->
            <div class="dashboard-date-time">
                <p class="dashboard-date-time-text"><?php echo esc_html($current_date_time); ?></p>
            </div>
        </div>
        <?php
    }
}
add_action('all_admin_notices', 'display_task_dashboard_header');


class Nfinite_Dash_Task_CPT {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies')); // ✅ Register Categories & Tags
        add_action('add_meta_boxes', array($this, 'add_task_meta_boxes'));
        add_action('save_post', array($this, 'save_task_meta_box_data'));
        add_action('save_post_task_manager_task', array($this, 'save_task_meta_box_data'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_task_manager_scripts'));

        // ✅ Admin Table Columns
        add_filter('manage_task_manager_task_posts_columns', array($this, 'add_task_columns'));
        add_action('manage_task_manager_task_posts_custom_column', array($this, 'populate_task_columns'), 10, 2);
        add_filter('manage_edit-task_manager_task_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_get_posts', array($this, 'filter_tasks'));

        // ✅ Remove Published Date Column
        add_filter('manage_edit-task_manager_task_columns', array($this, 'remove_unwanted_columns'));

        // ✅ AJAX Handling for Inline Editing
        add_action('wp_ajax_task_manager_update_meta', array($this, 'task_manager_update_meta'));
        add_action('wp_ajax_nopriv_task_manager_update_meta', array($this, 'task_manager_update_meta'));

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
                'add_new_item'  => __('Add New Task', 'task-manager'),
                'all_items'     => __('All Tasks', 'task-manager'),
            ),
            'public'      => false,
            'show_ui'     => true,
            'menu_icon'   => 'dashicons-list-view',
            'supports'    => array('title', 'editor'),
            'taxonomies'  => array('task_category', 'task_tag'), // ✅ Attach Taxonomies
        );
        register_post_type('task_manager_task', $args);
    }

    /**
     * ✅ Register Categories & Tags for Tasks
     */
    public function register_taxonomies() {
        // ✅ Task Categories (Hierarchical)
        register_taxonomy('task_category', 'task_manager_task', array(
            'labels'       => array(
                'name'          => __('Task Categories', 'task-manager'),
                'singular_name' => __('Task Category', 'task-manager'),
                'add_new_item'  => __('Add New Task Category', 'task-manager'),
                'edit_item'     => __('Edit Task Category', 'task-manager'),
            ),
            'hierarchical' => true,
            'show_ui'      => true,
            'show_admin_column' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => 'task-category'),
        ));

        // ✅ Task Tags (Non-Hierarchical)
        register_taxonomy('task_tag', 'task_manager_task', array(
            'labels'       => array(
                'name'          => __('Task Tags', 'task-manager'),
                'singular_name' => __('Task Tag', 'task-manager'),
                'add_new_item'  => __('Add New Task Tag', 'task-manager'),
                'edit_item'     => __('Edit Task Tag', 'task-manager'),
            ),
            'hierarchical' => false,
            'show_ui'      => true,
            'show_admin_column' => true,
            'query_var'    => true,
            'rewrite'      => array('slug' => 'task-tag'),
        ));
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
        // ✅ Security: Nonce Field
        wp_nonce_field('task_manager_save_meta_box_data', 'task_manager_meta_box_nonce');
    
        $fields = array(
            '_task_status'   => __('Task Status', 'task-manager'),
            '_task_priority' => __('Priority', 'task-manager'),
        );
    
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . ':</label>';
    
            $options = ($key === '_task_status') ? 
                ['pending' => 'Pending', 'in_progress' => 'In Progress', 'complete' => 'Complete'] :
                ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    
            echo '<select name="' . esc_attr($key) . '" id="' . esc_attr($key) . '">';
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
        // ✅ Security: Nonce Field
        wp_nonce_field('task_manager_save_meta_box_data', 'task_manager_meta_box_nonce');
    
        $due_date = get_post_meta($post->ID, '_task_due_date', true);
        echo '<label for="task_due_date">' . __('Select Due Date:', 'task-manager') . '</label>';
        echo '<input type="date" name="_task_due_date" id="task_due_date" value="' . esc_attr($due_date) . '" />';
    }

    /**
     * ✅ Save Task Meta Box Data (Including Due Date)
     */
    public function save_task_meta_box_data($post_id) {
        // ✅ Security Check: Verify nonce
        if (!isset($_POST['task_manager_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['task_manager_meta_box_nonce'], 'task_manager_save_meta_box_data')) {
            return;
        }
    
        // ✅ Prevent Auto-Save Overwrite
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    
        // ✅ Ensure the user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    
        // ✅ Update Status, Priority, and Due Date
        $meta_keys = ['_task_status', '_task_priority', '_task_due_date'];
    
        foreach ($meta_keys as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
            } else {
                delete_post_meta($post_id, $key); // ✅ Remove if no value is set
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
    
        if ($column === 'task_status' || $column === 'task_priority') {
            echo '<select class="task-status-dropdown" data-task-id="' . esc_attr($post_id) . '" data-meta-key="' . esc_attr($column) . '">';
    
            $options = ($column === 'task_status')
                ? ['pending' => 'Pending', 'in_progress' => 'In Progress', 'complete' => 'Complete']
                : ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
    
            foreach ($options as $option_value => $option_label) {
                echo '<option value="' . esc_attr($option_value) . '" ' . selected($meta_value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
            }
    
            echo '</select>';
        }
    
        if ($column === 'task_due_date') {
            echo esc_html($meta_value ? date('F j, Y', strtotime($meta_value)) : '—');
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
 * ✅ AJAX Handler for Updating Task Metadata
 */

 function task_manager_update_meta() {
    check_ajax_referer('task_manager_update_meta', '_ajax_nonce');

    if (empty($_POST['task_id']) || empty($_POST['meta_key']) || !isset($_POST['meta_value'])) {
        wp_send_json_error(['message' => '❌ Missing required parameters.', 'data' => $_POST]);
    }

    $post_id = intval($_POST['task_id']);
    $meta_key = '_' . ltrim(sanitize_text_field($_POST['meta_key']), '_'); // ✅ Ensure meta key starts with `_`
    $meta_value = sanitize_text_field($_POST['meta_value']);

    // ✅ Debug Logging
    error_log("🔄 Updating task meta - Task ID: {$post_id}, Meta Key: {$meta_key}, Meta Value: {$meta_value}");

    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => '❌ Permission denied.', 'post_id' => $post_id]);
    }

    if (update_post_meta($post_id, $meta_key, $meta_value)) {
        error_log("✅ Successfully updated {$meta_key} to {$meta_value}");
        wp_send_json_success([
            'message' => '✅ Updated successfully.',
            'post_id' => $post_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        ]);
    } else {
        error_log("❌ Failed to update {$meta_key}");
        wp_send_json_error([
            'message' => '❌ Failed to update.',
            'post_id' => $post_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        ]);
    }
}



// ✅ Properly closing this function to prevent syntax errors

    /**
     * ✅ Enqueue JavaScript for Task Manager Dropdowns & AJAX
     */
    public function enqueue_task_manager_scripts($hook) {
        $screen = get_current_screen();
    
        // ✅ Load script only on Task Manager pages
        if (in_array($hook, ['edit.php', 'post.php']) && $screen->post_type === 'task_manager_task') {
    
            wp_enqueue_script(
                'nfinite-dash-task-cpt',
                plugins_url('admin/js/nfinite-dash-task-cpt.js', dirname(__FILE__)), // ✅ Corrected path
                ['jquery'],
                time(),
                true
            );
    
            wp_localize_script('nfinite-dash-task-cpt', 'taskManagerAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('task_manager_update_meta'),
            ]);
        }
    }
    
    
}

// ✅ Initialize Task CPT
$task_cpt = new Nfinite_Dash_Task_CPT();