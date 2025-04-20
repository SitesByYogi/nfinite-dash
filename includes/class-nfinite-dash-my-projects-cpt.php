<?php
/**
 * My Projects Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

 /**
 * ✅ Display Quick Links and Date/Time on My Projects Dashboard
 */
function display_projects_dashboard_header() {
    global $pagenow, $post_type;

    // Ensure this only appears on the My Projects CPT admin page
    if ($pagenow === 'edit.php' && $post_type === 'my_projects') {
        date_default_timezone_set('America/New_York');
        $current_date_time = date('F j, Y - g:i A T');

        ?>
        <div class="wrap">
            <h1><?php echo __("Nfinite Projects Dashboard", 'nfinite-dash'); ?></h1>

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
add_action('all_admin_notices', 'display_projects_dashboard_header');

class Nfinite_Dash_My_Projects_CPT {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_project_meta_boxes'));
        add_action('save_post_my_projects', array($this, 'save_project_meta_box_data'));
        add_action('admin_menu', [$this, 'register_my_projects_cards_page']);


        // ✅ Admin Table Columns
        add_filter('manage_my_projects_posts_columns', array($this, 'add_project_columns'));
        add_action('manage_my_projects_posts_custom_column', array($this, 'populate_project_columns'), 10, 2);
        add_filter('manage_edit-my_projects_sortable_columns', array($this, 'make_columns_sortable'));
        add_action('pre_get_posts', array($this, 'modify_project_orderby'));

        // ✅ AJAX Handling for Inline Editing
        add_action('wp_ajax_update_project_meta', 'update_project_meta');
        add_action('wp_ajax_my_projects_update_meta', array($this, 'update_meta_via_ajax'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_inline_edit_scripts'));
    }

    /**
     * ✅ Register My Projects Admin Page
     */
    public function register_my_projects_cards_page() {
    add_submenu_page(
        'edit.php?post_type=my_projects',
        __('My Projects – Card View', 'nfinite-dash'),
        __('Card View', 'nfinite-dash'),
        'edit_posts',
        'my-projects-cards',
        [$this, 'render_my_projects_cards_view']
    );
}

    /**
     * ✅ Render My Projects Cards View
     */
    public function render_my_projects_cards_view() {
        include dirname(__FILE__, 2) . '/admin/views/my-projects-cards.php';
    }
    


    /**
     * ✅ Register My Projects Custom Post Type
     */
    public function register_post_type() {
        $args = array(
            'labels'      => array(
                'name'          => __('My Projects', 'my-projects'),
                'singular_name' => __('Project', 'my-projects'),
                'menu_name'     => __('My Projects', 'my-projects'),
                'add_new'       => __('Add New Project', 'my-projects'),
                'add_new_item'  => __('Add New Project', 'my-projects'),
                'all_items'     => __('All Projects', 'my-projects'),
            ),
            'public'      => false,
            'show_ui'     => true,
            'menu_icon'   => 'dashicons-portfolio',
            'supports'    => array('title', 'editor'),
        );
        register_post_type('my_projects', $args);
    }

    /**
     * ✅ Register Project Taxonomies
     */
    public function register_taxonomies() {
        register_taxonomy('my_project_category', 'my_projects', array(
            'labels'        => array(
                'name'          => __('Project Categories', 'my-projects'),
                'singular_name' => __('Project Category', 'my-projects'),
            ),
            'hierarchical'  => true,
            'show_ui'       => true,
        ));

        register_taxonomy('my_project_tag', 'my_projects', array(
            'labels'        => array(
                'name'          => __('Project Tags', 'my-projects'),
                'singular_name' => __('Project Tag', 'my-projects'),
            ),
            'hierarchical'  => false,
            'show_ui'       => true,
        ));
    }

    
    /**
     * ✅ Add Project Meta Boxes (Including Project Links)
     */
    public function add_project_meta_boxes() {
        add_meta_box(
            'project_details',
            __('Project Details', 'my-projects'),
            array($this, 'project_meta_box_callback'),
            'my_projects',
            'normal',
            'high'
        );

        add_meta_box(
            'project_links',
            __('Project Links', 'my-projects'),
            array($this, 'project_links_meta_box_callback'),
            'my_projects',
            'normal',
            'default'
        );
    }

    /**
     * ✅ Project Meta Box Callback (Status, Priority)
     */
    public function project_meta_box_callback($post) {
        $status = get_post_meta($post->ID, '_project_status', true);
        $priority = get_post_meta($post->ID, '_project_priority', true);
    
        wp_nonce_field('my_projects_save_meta_box_data', 'my_projects_meta_box_nonce');
    
        ?>
        <p>
            <label for="project_status"><?php _e('Project Status:', 'my-projects'); ?></label>
            <select id="project_status" name="_project_status" data-project-id="<?php echo esc_attr($post->ID); ?>" data-meta-key="project_status">
                <option value="not_started" <?php selected($status, 'not_started'); ?>>Not Started</option>
                <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
                <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
            </select>
        </p>
        <p>
            <label for="project_priority"><?php _e('Priority:', 'my-projects'); ?></label>
            <select id="project_priority" name="_project_priority" data-project-id="<?php echo esc_attr($post->ID); ?>" data-meta-key="project_priority">
                <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
                <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
                <option value="high" <?php selected($priority, 'high'); ?>>High</option>
                <option value="urgent" <?php selected($priority, 'urgent'); ?>>Urgent</option>
            </select>
        </p>
        <?php
    }       

    /**
     * ✅ Project Links Meta Box Callback (Similar to My Notes Links)
     */
    public function project_links_meta_box_callback($post) {
        $project_links = get_post_meta($post->ID, '_my_project_links', true) ?: [];
        wp_nonce_field('save_project_links_nonce', 'project_links_nonce');
        ?>
        <div id="project-links-container">
            <?php foreach ($project_links as $index => $link): ?>
                <div class="project-link-row">
                    <input type="text" name="project_links[<?php echo $index; ?>][text]"
                           value="<?php echo esc_attr($link['text']); ?>" placeholder="Link Text" style="width: 40%; margin-right: 10px;" />
                    <input type="url" name="project_links[<?php echo $index; ?>][url]"
                           value="<?php echo esc_attr($link['url']); ?>" placeholder="Link URL" style="width: 50%; margin-right: 10px;" />
                    <button type="button" class="remove-link button">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-new-link" class="button">Add New Link</button>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('project-links-container');
                const addNewLinkButton = document.getElementById('add-new-link');

                addNewLinkButton.addEventListener('click', function () {
                    const index = container.children.length;
                    const newRow = document.createElement('div');
                    newRow.classList.add('project-link-row');
                    newRow.innerHTML = `
                        <input type="text" name="project_links[${index}][text]" placeholder="Link Text" style="width: 40%; margin-right: 10px;" />
                        <input type="url" name="project_links[${index}][url]" placeholder="Link URL" style="width: 50%; margin-right: 10px;" />
                        <button type="button" class="remove-link button">Remove</button>
                    `;
                    container.appendChild(newRow);
                    newRow.querySelector('.remove-link').addEventListener('click', function () {
                        newRow.remove();
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * ✅ Save Project Meta Box Data (Including Project Links)
     */
    public function save_project_meta_box_data($post_id) {
        // ✅ Security Checks
        if (!isset($_POST['my_projects_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['my_projects_meta_box_nonce'], 'my_projects_save_meta_box_data')) {
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
    
        // ✅ Update Status and Priority
        $meta_keys = ['_project_status', '_project_priority'];
    
        foreach ($meta_keys as $key) {
            if (isset($_POST[str_replace('_', '', $key)])) { // Fix incorrect field names
                update_post_meta($post_id, $key, sanitize_text_field($_POST[str_replace('_', '', $key)]));
            }
        }
    
        // ✅ Save Project Links
        if (!isset($_POST['project_links_nonce']) || 
            !wp_verify_nonce($_POST['project_links_nonce'], 'save_project_links_nonce')) {
            return;
        }
    
        $links = [];
        if (isset($_POST['project_links']) && is_array($_POST['project_links'])) {
            foreach ($_POST['project_links'] as $link) {
                if (!empty($link['text']) && !empty($link['url'])) {
                    $links[] = [
                        'text' => sanitize_text_field($link['text']),
                        'url'  => esc_url($link['url']),
                    ];
                }
            }
        }
    
        update_post_meta($post_id, '_my_project_links', $links);
    }    

   /**
 * ✅ Add Custom Columns to My Projects Admin Table (Removes Published Date)
 */
public function add_project_columns($columns) {
    // Start with only the necessary columns
    $new_columns = [];

    // Keep checkbox and title columns
    if (isset($columns['cb'])) {
        $new_columns['cb'] = $columns['cb'];
    }
    if (isset($columns['title'])) {
        $new_columns['title'] = $columns['title'];
    }

    // Add custom columns
    $new_columns['project_status']   = __('Status', 'my-projects');
    $new_columns['project_priority'] = __('Priority', 'my-projects');
    $new_columns['project_links']    = __('Links', 'my-projects');

    // Remove the default 'date' column (Published Date)
    return $new_columns;
}

        /**
 * ✅ Populate Custom Columns (Including Project Links) with Dropdowns
 */
public function populate_project_columns($column, $post_id) {
    $meta_value = get_post_meta($post_id, '_' . $column, true);

    if ($column === 'project_status' || $column === 'project_priority') {
        echo '<select class="project-meta-dropdown" data-project-id="' . esc_attr($post_id) . '" data-meta-key="' . esc_attr($column) . '">';

        $options = ($column === 'project_status')
            ? ['not_started' => 'Not Started', 'in_progress' => 'In Progress', 'completed' => 'Completed']
            : ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];

        foreach ($options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($meta_value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
        }

        echo '</select>';
    }
}


    /**
     * ✅ Sorting Logic for Columns
     */
    public function make_columns_sortable($columns) {
        $columns['project_status']   = '_project_status';
        $columns['project_priority'] = '_project_priority';
        return $columns;
    }

    /**
     * ✅ Sorting Order by Meta Fields
     */
    public function modify_project_orderby($query) {
        if (is_admin() && $query->is_main_query()) {
            $orderby = $query->get('orderby');
            if (in_array($orderby, ['_project_status', '_project_priority'])) {
                $query->set('meta_key', $orderby);
                $query->set('orderby', 'meta_value');
            }
        }
    }

   /**
 * ✅ AJAX Handler to Update Meta Fields for My Projects
 */
public function update_meta_via_ajax() {
    check_ajax_referer('my_projects_update_meta', '_ajax_nonce');

    if (empty($_POST['post_id']) || empty($_POST['meta_key']) || !isset($_POST['meta_value'])) {
        wp_send_json_error(['message' => 'Missing required parameters.', 'data' => $_POST]);
    }

    $post_id = intval($_POST['post_id']);
    $meta_key = '_' . ltrim(sanitize_text_field($_POST['meta_key']), '_'); // Ensure meta key starts with `_`
    $meta_value = sanitize_text_field($_POST['meta_value']);

    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(['message' => 'Permission denied.', 'post_id' => $post_id]);
    }

    if (update_post_meta($post_id, $meta_key, $meta_value)) {
        wp_send_json_success([
            'message' => 'Updated successfully.',
            'post_id' => $post_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Failed to update.',
            'post_id' => $post_id,
            'meta_key' => $meta_key,
            'meta_value' => $meta_value
        ]);
    }
}



function update_project_meta() {
    // Security check
    check_ajax_referer('update_project_meta_nonce', 'security');

    $project_id = intval($_POST['project_id']);
    $meta_key = sanitize_text_field($_POST['meta_key']);
    $meta_value = sanitize_text_field($_POST['meta_value']);

    // Check permissions
    if (!current_user_can('edit_post', $project_id)) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    // Update metadata
    if (update_post_meta($project_id, $meta_key, $meta_value)) {
        wp_send_json_success(['message' => 'Updated successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update.']);
    }
}


    /**
 * ✅ Enqueue JavaScript for Inline Editing in My Projects
 */
public function enqueue_inline_edit_scripts($hook) {
    $screen = get_current_screen();

    // ✅ Ensure script loads on the correct admin pages
    if (in_array($hook, ['edit.php', 'post.php', 'index.php']) || $screen->post_type === 'my_projects') {
        wp_enqueue_script('jquery'); // ✅ Ensure jQuery loads first

        wp_enqueue_script(
            'nfinite-dash-my-projects',
            plugins_url('admin/js/nfinite-dash-my-projects.js', dirname(__FILE__)), 
            ['jquery'], // ✅ Define jQuery as a dependency
            time(), 
            true
        );

        wp_localize_script('nfinite-dash-my-projects', 'myProjectsAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('my_projects_update_meta'),
        ]);
    }
}

}

// ✅ Initialize My Projects CPT
new Nfinite_Dash_My_Projects_CPT();
