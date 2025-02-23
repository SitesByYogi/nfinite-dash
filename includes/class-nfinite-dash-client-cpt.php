<?php
/**
 * Register the Client Custom Post Type for Nfinite Dash.
 *
 * @package    Nfinite_Dash
 * @subpackage Nfinite_Dash/includes
 */

class Nfinite_Dash_Client_CPT {

    /**
     * Constructor - Registers the CPT and Meta Boxes.
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));

        // ✅ Custom Columns in Client List
        add_filter('manage_client_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_client_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
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
            'public'      => true,  // ✅ Ensure it's visible in admin
            'show_ui'     => true,
            'menu_icon'   => 'dashicons-businessman',
            'supports'    => array( 'title', 'editor' ),
            'has_archive' => true, // ✅ Allow archives
            'rewrite'     => array( 'slug' => 'clients' ), // ✅ Friendly URL
        );

        register_post_type( 'client', $args ); // ✅ Correct Post Type Name
    }

    /**
     * ✅ Add Meta Boxes for Clients.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'client_details',
            __( 'Client Details', 'nfinite-dash' ),
            array($this, 'meta_box_callback'),
            'client', // ✅ Correct Post Type Name
            'normal',
            'high'
        );
    }

    /**
     * ✅ Meta Box Callback.
     */
    public function meta_box_callback($post) {
        $meta_fields = array(
            '_client_admin_link' => __( 'Admin Dashboard Link', 'nfinite-dash' ),
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
     * ✅ Save Meta Box Data.
     */
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['nfinite_dash_client_meta_nonce']) || 
            !wp_verify_nonce($_POST['nfinite_dash_client_meta_nonce'], 'nfinite_dash_save_client_meta')) {
            return;
        }

        if (isset($_POST['_client_admin_link'])) {
            update_post_meta($post_id, '_client_admin_link', esc_url($_POST['_client_admin_link']));
        }
    }

    /**
     * ✅ Add Custom Columns to the Admin Client List.
     */
    public function add_custom_columns($columns) {
        $columns['website_admin_link'] = __( 'Admin Dashboard', 'nfinite-dash' );
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

            case 'client_tasks':
                $this->display_assigned_posts('nfinite_tasks', $post_id);
                break;

            case 'client_meetings':
                $this->display_assigned_posts('meetings', $post_id);
                break;
        }
    }

    /**
     * ✅ Display Assigned Tasks and Meetings (Fixed Duplicate Issue).
     */
    public function display_assigned_posts($post_type, $client_id) {
        if (empty($client_id)) {
            echo __('No assigned items', 'nfinite-dash');
            return;
        }

        // ✅ Query tasks/meetings assigned to this client
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => -1, // Fetch all
            'meta_query'     => array(
                array(
                    'key'     => '_assigned_client',
                    'value'   => $client_id,
                    'compare' => '='
                )
            ),
            'fields' => 'ids', // ✅ Fetch only post IDs for efficiency
        );

        $query = new WP_Query($args);
        
        // ✅ Remove duplicates if any
        $post_ids = array_unique($query->posts); 

        if (!empty($post_ids)) {
            echo '<ul>';
            foreach ($post_ids as $post_id) {
                echo '<li><a href="' . esc_url(get_edit_post_link($post_id)) . '">' . esc_html(get_the_title($post_id)) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo __('No assigned items', 'nfinite-dash');
        }

        // ✅ Reset Post Data (Best Practice)
        wp_reset_postdata();
    }
}

// ✅ Initialize the class
new Nfinite_Dash_Client_CPT();
