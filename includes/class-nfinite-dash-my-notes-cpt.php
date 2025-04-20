<?php
/**
 * My Notes Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

 /**
 * ✅ Display Quick Links and Date/Time on My Notes Dashboard
 */
function display_notes_dashboard_header() {
    global $pagenow, $post_type;

    // Ensure this only appears on the My Notes CPT admin page
    if ($pagenow === 'edit.php' && $post_type === 'my_notes') {
        date_default_timezone_set('America/New_York');
        $current_date_time = date('F j, Y - g:i A T');

        ?>
        <div class="wrap">
            <h1><?php echo __("Nfinite Notes Dashboard", 'nfinite-dash'); ?></h1>

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
add_action('all_admin_notices', 'display_notes_dashboard_header');


if (!class_exists('Nfinite_Dash_My_Notes_CPT')) { // ✅ Prevent duplicate declaration
    class Nfinite_Dash_My_Notes_CPT {

        public function __construct() {
            add_action('init', array($this, 'register_post_type'));
            add_action('add_meta_boxes', array($this, 'add_my_notes_meta_boxes'));
            add_action('save_post', array($this, 'save_my_notes_meta_box_data'));
            add_action('add_meta_boxes', array($this, 'add_client_assignment_meta_box'));
            add_action('save_post', array($this, 'save_client_assignment_meta_box_data'));
            add_action('admin_menu', [$this, 'register_notes_cards_page']);



            // ✅ Admin Table Columns
            add_filter('manage_my_notes_posts_columns', array($this, 'add_my_notes_columns'));
            add_action('manage_my_notes_posts_custom_column', array($this, 'populate_my_notes_columns'), 10, 2);

            // ✅ Modify Archive Query (Fixes Missing Function)
            add_action('pre_get_posts', array($this, 'modify_notes_archive_query'));
        }

        /**
         * ✅ Register My Notes Custom Post Type
         */
        public function register_post_type() {
            $labels = array(
                'name'          => __('My Notes', 'nfinite-dash'),
                'singular_name' => __('Note', 'nfinite-dash'),
                'menu_name'     => __('My Notes', 'nfinite-dash'),
                'add_new'       => __('Add New Note', 'nfinite-dash'),
                'add_new_item'  => __('Add New Note', 'nfinite-dash'),
                'all_items'     => __('All Notes', 'nfinite-dash'),
                'edit_item'     => __('Edit Note', 'nfinite-dash'),
                'view_item'     => __('View Note', 'nfinite-dash'),
            );

            $args = array(
                'labels'        => $labels,
                'public'        => false,
                'show_ui'       => true,
                'show_in_menu'  => 'admin.php?page=nfinite-dash',
                'menu_icon'     => 'dashicons-sticky',
                'supports'      => array('title', 'editor'),
                'has_archive'   => true,
                'rewrite'       => array('slug' => 'my-notes'),
            );

            register_post_type('my_notes', $args);
        }

        /**
         * ✅ Register Notes Cards Page
         */
            public function register_notes_cards_page() {
            add_submenu_page(
                'edit.php?post_type=my_notes',
                __('Notes – Card View', 'nfinite-dash'),
                __('Card View', 'nfinite-dash'),
                'edit_posts',
                'notes-cards-view',
                [$this, 'render_notes_cards_view']
            );
        }

        /**
         * ✅ Render Notes Cards View
         */        
        public function render_notes_cards_view() {
            include dirname(__FILE__, 2) . '/admin/views/notes-cards-dashboard.php';
        }
        

        /**
         * ✅ Modify Notes Archive Query to Prioritize Featured Notes
         */
        public function modify_notes_archive_query($query) {
            if (!is_admin() && $query->is_main_query() && is_post_type_archive('my_notes')) {
                $query->set('meta_query', array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_is_featured',
                        'value'   => '1',
                        'compare' => '='
                    ),
                    array(
                        'key'     => '_is_featured',
                        'compare' => 'NOT EXISTS'
                    ),
                ));
                $query->set('orderby', array(
                    'meta_value_num' => 'DESC',
                    'date'           => 'DESC',
                ));
            }
        }

        /**
         * ✅ Add Meta Boxes for Notes Links and Featured Status
         */
        public function add_my_notes_meta_boxes() {
            add_meta_box(
                'notes_links_meta_box',
                __('Links for Notes', 'nfinite-dash'),
                array($this, 'render_my_notes_links_meta_box'),
                'my_notes',
                'normal',
                'default'
            );

            add_meta_box(
                'featured_note_meta_box',
                __('Featured Note', 'nfinite-dash'),
                array($this, 'render_featured_note_meta_box'),
                'my_notes',
                'side',
                'high'
            );
        }

        /**
         * ✅ Render Featured Note Meta Box
         */
        public function render_featured_note_meta_box($post) {
            $is_featured = get_post_meta($post->ID, '_is_featured', true);
            ?>
            <label for="is_featured_note">
                <input type="checkbox" name="is_featured_note" id="is_featured_note" value="1" <?php checked($is_featured, '1'); ?> />
                <?php _e('Mark this note as featured', 'nfinite-dash'); ?>
            </label>
            <?php
        }

        /**
         * ✅ Save Meta Box Data for Links and Featured Notes
         */
        public function save_my_notes_meta_box_data($post_id) {
            if (isset($_POST['notes_links_nonce']) && wp_verify_nonce($_POST['notes_links_nonce'], 'save_notes_links_nonce')) {
                $links = [];
                if (isset($_POST['notes_links']) && is_array($_POST['notes_links'])) {
                    foreach ($_POST['notes_links'] as $link) {
                        if (!empty($link['text']) && !empty($link['url'])) {
                            $links[] = [
                                'text' => sanitize_text_field($link['text']),
                                'url'  => esc_url($link['url']),
                            ];
                        }
                    }
                }
                update_post_meta($post_id, '_notes_links', $links);
            }

            // Save Featured Status
            if (isset($_POST['is_featured_note'])) {
                update_post_meta($post_id, '_is_featured', '1');
            } else {
                delete_post_meta($post_id, '_is_featured');
            }
        }

        /**
         * ✅ Add Custom Columns for Admin View
         */
        public function add_my_notes_columns($columns) {
            $columns['featured'] = __('Featured', 'nfinite-dash');
            $columns['notes_links'] = __('Notes Links', 'nfinite-dash');
            $columns['assigned_client'] = __('Assigned Client', 'nfinite-dash'); // ✅ Add new column
            return $columns;
        }

        /**
         * ✅ Populate Custom Columns with Featured Badge and Notes Links
         */
        public function populate_my_notes_columns($column, $post_id) {
            if ($column === 'featured') {
                $is_featured = get_post_meta($post_id, '_is_featured', true);
                echo $is_featured ? '<span style="background: #f39c12; color: #fff; padding: 5px 10px; border-radius: 4px;">' . __('Featured', 'nfinite-dash') . '</span>' : __('Not Featured', 'nfinite-dash');
            }

            if ($column === 'notes_links') {
                $links = get_post_meta($post_id, '_notes_links', true);
                if (!empty($links)) {
                    echo '<ul>';
                    foreach ($links as $link) {
                        echo '<li><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html($link['text']) . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<em>' . __('No links added.', 'nfinite-dash') . '</em>';
                }
            }

            if ($column === 'assigned_client') {
                $client_id = get_post_meta($post_id, '_assigned_client', true);
                if ($client_id) {
                    echo '<a href="' . get_edit_post_link($client_id) . '">' . get_the_title($client_id) . '</a>';
                } else {
                    echo __('No Client Assigned', 'nfinite-dash');
                }
            }
        }

        /**
 * ✅ Add Meta Box for Assigning Notes to Clients
 */
public function add_client_assignment_meta_box() {
    add_meta_box(
        'client_assignment_meta_box',
        __('Assign to Client', 'nfinite-dash'),
        array($this, 'render_client_assignment_meta_box'),
        'my_notes',
        'side',
        'default'
    );
}

/**
 * ✅ Render Client Assignment Meta Box
 */
public function render_client_assignment_meta_box($post) {
    // Get the currently assigned client ID
    $assigned_client = get_post_meta($post->ID, '_assigned_client', true);

    // Fetch all clients
    $clients = get_posts([
        'post_type'      => 'client',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);

    // Security nonce
    wp_nonce_field('save_client_assignment_meta_box', 'client_assignment_nonce');

    echo '<label for="assigned_client">' . __('Select a Client:', 'nfinite-dash') . '</label>';
    echo '<select name="assigned_client" id="assigned_client">';
    echo '<option value="">' . __('— No Client Assigned —', 'nfinite-dash') . '</option>';

    foreach ($clients as $client) {
        echo '<option value="' . esc_attr($client->ID) . '" ' . selected($assigned_client, $client->ID, false) . '>';
        echo esc_html($client->post_title);
        echo '</option>';
    }

    echo '</select>';
}

/**
 * ✅ Save Assigned Client Meta Box Data
 */
public function save_client_assignment_meta_box_data($post_id) {
    // Security check
    if (!isset($_POST['client_assignment_nonce']) || !wp_verify_nonce($_POST['client_assignment_nonce'], 'save_client_assignment_meta_box')) {
        return;
    }

    // Prevent autosave issues
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Ensure the user has permission
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Save the selected client
    if (isset($_POST['assigned_client'])) {
        update_post_meta($post_id, '_assigned_client', sanitize_text_field($_POST['assigned_client']));
    }
}

        /**
 * ✅ Render Notes Links Meta Box
 */
public function render_my_notes_links_meta_box($post) {
    $links = get_post_meta($post->ID, '_notes_links', true) ?: [];
    wp_nonce_field('save_notes_links_nonce', 'notes_links_nonce');
    ?>
    <div id="notes-links-container">
        <?php foreach ($links as $index => $link): ?>
            <div class="notes-link-row">
                <input type="text" name="notes_links[<?php echo $index; ?>][text]"
                       value="<?php echo esc_attr($link['text']); ?>" placeholder="Link Text" style="width: 40%; margin-right: 10px;" />
                <input type="url" name="notes_links[<?php echo $index; ?>][url]"
                       value="<?php echo esc_attr($link['url']); ?>" placeholder="Link URL" style="width: 50%; margin-right: 10px;" />
                <button type="button" class="remove-link button">Remove</button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-new-link" class="button">Add New Link</button>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('notes-links-container');
            const addNewLinkButton = document.getElementById('add-new-link');
            addNewLinkButton.addEventListener('click', function () {
                const index = container.children.length;
                const newRow = document.createElement('div');
                newRow.classList.add('notes-link-row');
                newRow.innerHTML = `
                    <input type="text" name="notes_links[${index}][text]" placeholder="Link Text" style="width: 40%; margin-right: 10px;" />
                    <input type="url" name="notes_links[${index}][url]" placeholder="Link URL" style="width: 50%; margin-right: 10px;" />
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

    }

    // ✅ Initialize My Notes CPT
    new Nfinite_Dash_My_Notes_CPT();
}
