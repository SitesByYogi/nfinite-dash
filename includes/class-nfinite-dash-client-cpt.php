<?php
/**
 * Register the Client Custom Post Type for Nfinite Dash.
 *
 * @package    Nfinite_Dash
 * @subpackage Nfinite_Dash/includes
 */

 /**
 * ✅ Display Quick Links and Date/Time on Clients Dashboard
 */
function display_client_dashboard_header() {
    global $pagenow, $post_type;

    // Ensure this only appears on the Clients CPT admin page
    if ($pagenow === 'edit.php' && $post_type === 'client') {
        date_default_timezone_set('America/New_York');
        $current_date_time = date('F j, Y - g:i A T');

        ?>
        <div class="wrap">
            <h1><?php echo __("Nfinite Clients Dashboard", 'nfinite-dash'); ?></h1>

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
add_action('all_admin_notices', 'display_client_dashboard_header');

class Nfinite_Dash_Client_CPT {

    /**
     * Constructor - Registers the CPT and Meta Boxes.
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('add_meta_boxes', array($this, 'add_client_notes_meta_box'));
        add_action('save_post', array($this, 'save_client_notes_meta_box_data'));
        add_action('pre_get_posts', array($this, 'sort_clients_alphabetically'));
    
        // ✅ Custom Columns in Client List
        add_filter('manage_client_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_client_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        
        // ✅ Remove Published Date Column
        add_filter('manage_edit-client_columns', array($this, 'remove_unwanted_columns'));
    }
    
    /**
     * ✅ Remove Published Date Column from Client List Table.
     */
    public function remove_unwanted_columns($columns) {
        unset($columns['date']); // Removes the Published Date column
        return $columns;
    }
    

    /**
     * ✅ Register the Client CPT.
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Clients', 'nfinite-dash' ),
            'singular_name'      => __( 'Client', 'nfinite-dash' ),
            'add_new'            => __( 'Add New Client', 'nfinite-dash' ),
            'add_new_item'       => __( 'Add New Client', 'nfinite-dash' ),
            'edit_item'          => __( 'Edit Client', 'nfinite-dash' ),
            'new_item'           => __( 'New Client', 'nfinite-dash' ),
            'view_item'          => __( 'View Client', 'nfinite-dash' ),
            'search_items'       => __( 'Search Clients', 'nfinite-dash' ),
            'not_found'          => __( 'No clients found', 'nfinite-dash' ),
            'not_found_in_trash' => __( 'No clients found in trash', 'nfinite-dash' ),
        );

        $args = array(
            'labels'      => $labels,
            'public'      => true,
            'show_ui'     => true,
            'menu_icon'   => 'dashicons-businessman',
            'supports'    => array( 'title', 'editor' ),
            'has_archive' => true,
            'rewrite'     => array( 'slug' => 'clients' ),
        );

        register_post_type( 'client', $args );
    }

    /**
     * ✅ Add Meta Boxes for Clients.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'client_details',
            __( 'Client Details', 'nfinite-dash' ),
            array($this, 'meta_box_callback'),
            'client',
            'normal',
            'high'
        );

        // ✅ Add back Assigned Items Box
        add_meta_box(
            'client_assigned_items',
            __( 'Assigned Tasks, Meetings & Notes', 'nfinite-dash' ),
            array($this, 'assigned_items_meta_box'),
            'client',
            'normal',
            'high'
        );
    }

    /**
 * ✅ Sort Clients Alphabetically in Admin Table
 */
public function sort_clients_alphabetically($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'client') {
        $query->set('orderby', 'title'); // ✅ Order by title
        $query->set('order', 'ASC'); // ✅ Ascending order (A-Z)
    }
}

    /**
     * ✅ Meta Box Callback for Client Details.
     */
    public function meta_box_callback($post) {
        $meta_fields = array(
            '_client_admin_link' => __( 'Admin Dashboard Link', 'nfinite-dash' ),
            '_client_home_url'   => __( 'Website Home URL', 'nfinite-dash' ),
        );

        // Security nonce
        wp_nonce_field( 'nfinite_dash_save_client_meta', 'nfinite_dash_client_meta_nonce' );

        foreach ($meta_fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . ':</label>';
            echo '<input type="url" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" size="50" /><br>';
        }
    }

    /**
 * ✅ Add Client Notes Meta Box.
 */
public function add_client_notes_meta_box() {
    add_meta_box(
        'client_notes',
        __('Client Notes', 'nfinite-dash'),
        array($this, 'client_notes_meta_box_callback'),
        'client',
        'normal',
        'high'
    );
}

/**
 * ✅ Callback function to display the Client Notes Meta Box.
 */
public function client_notes_meta_box_callback($post) {
    // Use nonce for security verification
    wp_nonce_field('client_notes_save_meta_box_data', 'client_notes_meta_box_nonce');

    // Retrieve existing value from the database
    $client_notes = get_post_meta($post->ID, '_client_notes', true);

    echo '<label for="client_notes">';
    _e('Add your client notes here:', 'nfinite-dash');
    echo '</label><br>';
    echo '<textarea id="client_notes" name="client_notes" rows="5" style="width:100%;">' . esc_textarea($client_notes) . '</textarea>';
}

/**
 * ✅ Save Client Notes Meta Box Data.
 */
public function save_client_notes_meta_box_data($post_id) {
    // Verify if our nonce is set
    if (!isset($_POST['client_notes_meta_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid
    if (!wp_verify_nonce($_POST['client_notes_meta_box_nonce'], 'client_notes_save_meta_box_data')) {
        return;
    }

    // Check if it's an autosave. If so, do nothing
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions
    if (isset($_POST['post_type']) && 'client' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Make sure data is set
    if (!isset($_POST['client_notes'])) {
        return;
    }

    // Sanitize user input
    $client_notes = sanitize_textarea_field($_POST['client_notes']);

    // Update the meta field in the database
    update_post_meta($post_id, '_client_notes', $client_notes);
}


    /**
     * ✅ Meta Box Callback for Assigned Tasks, Meetings & Notes.
     */
    public function assigned_items_meta_box($post) {
        echo '<h3>' . __('Assigned Tasks', 'nfinite-dash') . '</h3>';
        $this->display_assigned_posts('task_manager_task', $post->ID);

        echo '<h3>' . __('Assigned Meetings', 'nfinite-dash') . '</h3>';
        $this->display_assigned_posts('meetings', $post->ID);

        echo '<h3>' . __('Client Notes', 'nfinite-dash') . '</h3>';
        $this->display_assigned_posts('my_notes', $post->ID);
    }

    /**
     * ✅ Save Meta Box Data.
     */
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['nfinite_dash_client_meta_nonce']) || 
            !wp_verify_nonce($_POST['nfinite_dash_client_meta_nonce'], 'nfinite_dash_save_client_meta')) {
            return;
        }

        foreach (['_client_admin_link', '_client_home_url'] as $meta_key) {
            if (isset($_POST[$meta_key])) {
                update_post_meta($post_id, $meta_key, esc_url($_POST[$meta_key]));
            }
        }
    }

    /**
     * ✅ Add Custom Columns to the Admin Client List.
     */
    public function add_custom_columns($columns) {
        $columns['website_admin_link'] = __( 'Admin Dashboard', 'nfinite-dash' );
        $columns['website_home_url'] = __( 'Website Home URL', 'nfinite-dash' );
        $columns['client_tasks'] = __( 'Assigned Tasks', 'nfinite-dash' );
        $columns['client_meetings'] = __( 'Assigned Meetings', 'nfinite-dash' );
        return $columns;
    }

    /**
     * ✅ Populate Custom Columns with Client Meta Data.
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'website_admin_link':
                $admin_link = get_post_meta($post_id, '_client_admin_link', true);
                echo $admin_link ? "<a href='" . esc_url($admin_link) . "' target='_blank'>Admin Dashboard</a>" : __('No Admin Link', 'nfinite-dash');
                break;

            case 'website_home_url':
                $home_url = get_post_meta($post_id, '_client_home_url', true);
                echo $home_url ? "<a href='" . esc_url($home_url) . "' target='_blank'>View Site</a>" : __('No Home URL', 'nfinite-dash');
                break;

            case 'client_tasks':
                $this->display_assigned_posts('task_manager_task', $post_id);
                break;

            case 'client_meetings':
                $this->display_assigned_posts('meetings', $post_id);
                break;

            case 'client_notes':
                $this->display_assigned_posts('my_notes', $post_id);
                break;
        }
    }

    /**
     * ✅ Display Assigned Tasks, Meetings, or Notes.
     */
    public function display_assigned_posts($post_type, $client_id) {
        $query = new WP_Query([
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'     => '_assigned_client',
                    'value'   => $client_id,
                    'compare' => '='
                ]
            ],
            'fields' => 'ids',
        ]);

        if ($query->have_posts()) {
            echo '<ul>';
            foreach ($query->posts as $post_id) {
                echo '<li><a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html(get_the_title($post_id)) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo __('No assigned items', 'nfinite-dash');
        }
        wp_reset_postdata();
    }
}

// ✅ Initialize the class
new Nfinite_Dash_Client_CPT();
