<?php
/**
 * Handles Client Relationships for Tasks, Meetings, and Projects.
 *
 * @package Nfinite_Dash
 */

class Nfinite_Dash_Client_Relationships {

    public function __construct() {
        // ✅ Add Relationship Meta Boxes
        add_action('add_meta_boxes', array($this, 'add_client_meta_boxes'));

        // ✅ Save Assigned Client
        add_action('save_post', array($this, 'save_assigned_client'));

        // ✅ Admin Column Display
        add_filter('manage_task_manager_task_posts_columns', array($this, 'add_client_column'));
        add_filter('manage_meetings_posts_columns', array($this, 'add_client_column'));
        add_filter('manage_my_projects_posts_columns', array($this, 'add_client_column'));

        // ✅ Populate Admin Columns
        add_action('manage_task_manager_task_posts_custom_column', array($this, 'populate_client_column'), 10, 2);
        add_action('manage_meetings_posts_custom_column', array($this, 'populate_client_column'), 10, 2);
        add_action('manage_my_projects_posts_custom_column', array($this, 'populate_client_column'), 10, 2);

        // ✅ AJAX Search for Clients
        add_action('wp_ajax_search_clients', array($this, 'ajax_search_clients'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_client_search_scripts'));
    }

    /**
     * ✅ Add Client Relationship Meta Boxes
     */
    public function add_client_meta_boxes() {
        $post_types = ['task_manager_task', 'meetings', 'my_projects']; // List of CPTs

        foreach ($post_types as $post_type) {
            add_meta_box(
                'client_relationship_meta_box',
                __('Assign Client', 'nfinite-dash'),
                array($this, 'render_client_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * ✅ Render Client Dropdown in Meta Box
     */
    public function render_client_meta_box($post) {
        $selected_client = get_post_meta($post->ID, '_assigned_client', true);
        $clients = get_posts([
            'post_type' => 'client',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        echo '<label for="assigned_client">' . __('Select a Client:', 'nfinite-dash') . '</label>';
        echo '<select id="assigned_client" name="assigned_client">';
        echo '<option value="">' . __('None', 'nfinite-dash') . '</option>';

        foreach ($clients as $client) {
            $selected = ($client->ID == $selected_client) ? 'selected' : '';
            echo '<option value="' . esc_attr($client->ID) . '" ' . $selected . '>' . esc_html($client->post_title) . '</option>';
        }

        echo '</select>';
    }

    /**
     * ✅ Save Assigned Client Data
     */
    public function save_assigned_client($post_id) {
        if (isset($_POST['assigned_client'])) {
            update_post_meta($post_id, '_assigned_client', sanitize_text_field($_POST['assigned_client']));
        }
    }

    /**
     * ✅ Add "Assigned Client" Column to Admin Table
     */
    public function add_client_column($columns) {
        $columns['assigned_client'] = __('Assigned Client', 'nfinite-dash');
        return $columns;
    }

    /**
     * ✅ Populate "Assigned Client" Column in Admin
     */
    public function populate_client_column($column, $post_id) {
        if ($column === 'assigned_client') {
            $client_id = get_post_meta($post_id, '_assigned_client', true);
            if ($client_id) {
                $client_name = trim(preg_replace('/^\d+/', '', get_the_title($client_id))); // Remove any leading numbers
                $client_link = get_edit_post_link($client_id);
    
                if ($client_name && $client_link) {
                    echo '<a href="' . esc_url($client_link) . '">' . esc_html($client_name) . '</a>';
                } else {
                    echo __('None', 'nfinite-dash');
                }
            } else {
                echo __('None', 'nfinite-dash');
            }
        }
    }
    

    /**
     * ✅ AJAX Search for Clients
     */
    public function ajax_search_clients() {
        $term = sanitize_text_field($_GET['term']);
        $clients = get_posts([
            'post_type' => 'client',
            's' => $term,
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $results = [];
        foreach ($clients as $client) {
            $results[] = [
                'id'   => $client->ID,
                'text' => $client->post_title,
            ];
        }

        wp_send_json($results);
    }

    /**
     * ✅ Enqueue Select2 for Better Client Search in Dropdown
     */
    public function enqueue_client_search_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('jquery');
            ?>
            <script>
                jQuery(document).ready(function ($) {
                    $('#assigned_client').select2({
                        ajax: {
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    action: 'search_clients',
                                    term: params.term, // Search term
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: data.map(function (client) {
                                        return { id: client.id, text: client.text };
                                    }),
                                };
                            },
                            cache: true,
                        },
                    });
                });
            </script>
            <?php
        }
    }
}

// ✅ Initialize the Class
new Nfinite_Dash_Client_Relationships();
