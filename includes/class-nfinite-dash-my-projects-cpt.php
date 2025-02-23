<?php
/**
 * My Projects Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

class Nfinite_Dash_My_Projects_CPT {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_project_meta_boxes'));
        add_action('save_post', array($this, 'save_project_meta_box_data'));

        // ✅ Admin Table Columns
        add_filter('manage_my_projects_posts_columns', array($this, 'add_project_columns'));
        add_action('manage_my_projects_posts_custom_column', array($this, 'populate_project_columns'), 10, 2);

        // ✅ Sorting & Filters
        add_filter('manage_edit-my_projects_sortable_columns', array($this, 'make_project_columns_sortable'));
        add_action('pre_get_posts', array($this, 'modify_project_orderby'));
    }

    /**
     * ✅ Register My Projects Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name'          => __('My Projects', 'nfinite-dash'),
            'singular_name' => __('Project', 'nfinite-dash'),
            'menu_name'     => __('My Projects', 'nfinite-dash'),
            'add_new'       => __('Add New Project', 'nfinite-dash'),
            'all_items'     => __('All Projects', 'nfinite-dash'),
            'edit_item'     => __('Edit Project', 'nfinite-dash'),
            'view_item'     => __('View Project', 'nfinite-dash'),
        );

        $args = array(
            'labels'        => $labels,
            'public'        => false,
            'show_ui'       => true,
            'menu_icon'     => 'dashicons-portfolio',
            'supports'      => array('title', 'editor'),
            'has_archive'   => false,
            'taxonomies'    => array('my_project_category', 'my_project_tag'),
            'show_in_nav_menus' => false,
            'show_in_rest'  => false,
        );

        register_post_type('my_projects', $args);
    }

    /**
     * ✅ Register Project Categories and Tags
     */
    public function register_taxonomies() {
        // Categories
        register_taxonomy('my_project_category', 'my_projects', array(
            'labels'            => array(
                'name'          => __('Project Categories', 'nfinite-dash'),
                'singular_name' => __('Project Category', 'nfinite-dash'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'rewrite'           => array('slug' => 'my-project-category'),
        ));

        // Tags
        register_taxonomy('my_project_tag', 'my_projects', array(
            'labels'            => array(
                'name'          => __('Project Tags', 'nfinite-dash'),
                'singular_name' => __('Project Tag', 'nfinite-dash'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_in_menu'  => 'admin.php?page=nfinite-dash',
            'show_admin_column' => true,
            'rewrite'           => array('slug' => 'my-project-tag'),
        ));
    }

    /**
     * ✅ Add Project Meta Boxes
     */
    public function add_project_meta_boxes() {
        add_meta_box(
            'project_details',
            __('Project Details', 'nfinite-dash'),
            array($this, 'project_meta_box_callback'),
            'my_projects',
            'normal',
            'high'
        );
    }

    /**
     * ✅ Meta Box Callback (Includes Status, Priority, and Links)
     */
    public function project_meta_box_callback($post) {
        $status   = get_post_meta($post->ID, '_my_project_status', true);
        $priority = get_post_meta($post->ID, '_my_project_priority', true);
        $links    = get_post_meta($post->ID, '_my_project_links', true) ?: [];

        wp_nonce_field('my_projects_save_meta_box_data', 'my_projects_meta_box_nonce');
        ?>

        <p>
            <label for="my_project_status"><?php _e('Project Status:', 'nfinite-dash'); ?></label>
            <select name="my_project_status" id="my_project_status">
                <option value="not_started" <?php selected($status, 'not_started'); ?>>Not Started</option>
                <option value="in_progress" <?php selected($status, 'in_progress'); ?>>In Progress</option>
                <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
            </select>
        </p>
        <p>
            <label for="my_project_priority"><?php _e('Priority:', 'nfinite-dash'); ?></label>
            <select name="my_project_priority" id="my_project_priority">
                <option value="low" <?php selected($priority, 'low'); ?>>Low</option>
                <option value="medium" <?php selected($priority, 'medium'); ?>>Medium</option>
                <option value="high" <?php selected($priority, 'high'); ?>>High</option>
                <option value="urgent" <?php selected($priority, 'urgent'); ?>>Urgent</option>
            </select>
        </p>

        <!-- ✅ Project Links -->
        <div id="my-project-links-container">
            <label><?php _e('Project Links:', 'nfinite-dash'); ?></label>
            <?php foreach ($links as $index => $link): ?>
                <div class="my-project-link-row">
                    <input type="text" name="my_project_links[<?php echo $index; ?>][text]" 
                        value="<?php echo esc_attr($link['text']); ?>" placeholder="Link Text" />
                    <input type="url" name="my_project_links[<?php echo $index; ?>][url]" 
                        value="<?php echo esc_attr($link['url']); ?>" placeholder="Link URL" />
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * ✅ Save Project Meta Box Data
     */
    public function save_project_meta_box_data($post_id) {
        if (!isset($_POST['my_projects_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['my_projects_meta_box_nonce'], 'my_projects_save_meta_box_data')) {
            return;
        }

        update_post_meta($post_id, '_my_project_status', sanitize_text_field($_POST['my_project_status']));
        update_post_meta($post_id, '_my_project_priority', sanitize_text_field($_POST['my_project_priority']));
        update_post_meta($post_id, '_my_project_links', $_POST['my_project_links']);
    }

    /**
     * ✅ Add Custom Columns to My Projects Admin Table
     */
    public function add_project_columns($columns) {
        $columns['my_project_status']   = __('Status', 'nfinite-dash');
        $columns['my_project_priority'] = __('Priority', 'nfinite-dash');
        return $columns;
    }

    /**
     * ✅ Populate Custom Columns
     */
    public function populate_project_columns($column, $post_id) {
        if ($column === 'my_project_status') {
            echo esc_html(get_post_meta($post_id, '_my_project_status', true));
        }

        if ($column === 'my_project_priority') {
            echo esc_html(get_post_meta($post_id, '_my_project_priority', true));
        }
    }

    /**
     * ✅ Make Columns Sortable
     */
    public function make_project_columns_sortable($columns) {
        $columns['my_project_status']   = '_my_project_status';
        $columns['my_project_priority'] = '_my_project_priority';
        return $columns;
    }

    /**
     * ✅ Modify Query Order for Sorting
     */
    public function modify_project_orderby($query) {
        if (is_admin() && $query->is_main_query()) {
            $orderby = $query->get('orderby');
            if (in_array($orderby, ['_my_project_status', '_my_project_priority'])) {
                $query->set('meta_key', $orderby);
                $query->set('orderby', 'meta_value');
            }
        }
    }
}

// ✅ Initialize My Projects CPT
new Nfinite_Dash_My_Projects_CPT();
