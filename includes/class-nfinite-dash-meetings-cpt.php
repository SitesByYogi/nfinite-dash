<?php
/**
 * Meetings Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

class Nfinite_Dash_Meetings_CPT {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meeting_meta_boxes'));
        add_action('save_post', array($this, 'save_meeting_meta_box_data'));

        // ✅ Admin Table Columns
        add_filter('manage_meetings_posts_columns', array($this, 'add_meeting_columns'));
        add_action('manage_meetings_posts_custom_column', array($this, 'populate_meeting_columns'), 10, 2);

        // ✅ Sorting & Filters
        add_filter('manage_edit-meetings_sortable_columns', array($this, 'make_meeting_columns_sortable'));
        add_action('pre_get_posts', array($this, 'modify_meeting_orderby'));

        // ✅ Shortcode to List Meetings
        add_shortcode('list_meetings', array($this, 'display_meetings_shortcode'));
    }

    public function register_post_type() {
        $labels = array(
            'name'          => __('Meetings', 'nfinite-dash'),
            'singular_name' => __('Meeting', 'nfinite-dash'),
            'menu_name'     => __('Meetings', 'nfinite-dash'),
            'add_new'       => __('Add New Meeting', 'nfinite-dash'),
            'all_items'     => __('All Meetings', 'nfinite-dash'),
            'edit_item'     => __('Edit Meeting', 'nfinite-dash'),
            'view_item'     => __('View Meeting', 'nfinite-dash'),
        );
    
        $args = array(
            'labels'        => $labels,
            'public'        => true,
            'has_archive'   => true,
            'show_ui'       => true,
            'show_in_menu'  => 'admin.php?page=nfinite-dash',
            'show_in_menu'  => 'nfinite-dashboard', // THIS MOVES IT UNDER "Nfinite Dashboard"
            'menu_icon'     => 'dashicons-calendar-alt',
            'supports'      => array('title', 'editor'),
        );
    
        register_post_type('meetings', $args);
    }
    
    /**
     * ✅ Add Meeting Meta Boxes
     */
    public function add_meeting_meta_boxes() {
        add_meta_box(
            'meeting_details',
            __('Meeting Details', 'nfinite-dash'),
            array($this, 'meeting_meta_box_callback'),
            'meetings',
            'normal',
            'high'
        );
    }

    /**
     * ✅ Meta Box Callback
     */
    public function meeting_meta_box_callback($post) {
        $date         = get_post_meta($post->ID, '_meeting_date', true);
        $time         = get_post_meta($post->ID, '_meeting_time', true);
        $team         = get_post_meta($post->ID, '_meeting_team', true);
        $status       = get_post_meta($post->ID, '_meeting_status', true);
        $meet_link    = get_post_meta($post->ID, '_meeting_link', true);
        $meeting_type = get_post_meta($post->ID, '_meeting_type', true);

        wp_nonce_field('meeting_meta_save', 'meeting_meta_nonce');
        ?>

        <p>
            <label for="meeting_date"><?php _e('Date:', 'nfinite-dash'); ?></label>
            <input type="date" id="meeting_date" name="meeting_date" value="<?php echo esc_attr($date); ?>" class="widefat">
        </p>
        <p>
            <label for="meeting_time"><?php _e('Time:', 'nfinite-dash'); ?></label>
            <input type="time" id="meeting_time" name="meeting_time" value="<?php echo esc_attr($time); ?>" class="widefat">
        </p>
        <p>
            <label for="meeting_team"><?php _e('Team:', 'nfinite-dash'); ?></label>
            <input type="text" id="meeting_team" name="meeting_team" value="<?php echo esc_attr($team); ?>" class="widefat">
        </p>
        <p>
            <label for="meeting_status"><?php _e('Status:', 'nfinite-dash'); ?></label>
            <select id="meeting_status" name="meeting_status" class="widefat">
                <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                <option value="completed" <?php selected($status, 'completed'); ?>>Completed</option>
                <option value="canceled" <?php selected($status, 'canceled'); ?>>Canceled</option>
            </select>
        </p>
        <p>
            <label for="meeting_link"><?php _e('Meet Link:', 'nfinite-dash'); ?></label>
            <input type="url" id="meeting_link" name="meeting_link" value="<?php echo esc_url($meet_link); ?>" class="widefat" placeholder="https://example.com/meet">
        </p>
        <p>
            <label for="meeting_type"><?php _e('Meeting Type:', 'nfinite-dash'); ?></label>
            <select id="meeting_type" name="meeting_type" class="widefat">
                <option value="google_meet" <?php selected($meeting_type, 'google_meet'); ?>>Google Meet</option>
                <option value="zoom" <?php selected($meeting_type, 'zoom'); ?>>Zoom</option>
                <option value="microsoft_teams" <?php selected($meeting_type, 'microsoft_teams'); ?>>Microsoft Teams</option>
            </select>
        </p>

        <?php
    }

    /**
     * ✅ Save Meeting Meta Box Data
     */
    public function save_meeting_meta_box_data($post_id) {
        if (!isset($_POST['meeting_meta_nonce']) || !wp_verify_nonce($_POST['meeting_meta_nonce'], 'meeting_meta_save')) return;

        $fields = ['meeting_date', 'meeting_time', 'meeting_team', 'meeting_status', 'meeting_link', 'meeting_type'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, "_$field", sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * ✅ Add Custom Columns to Meetings Admin Table
     */
    public function add_meeting_columns($columns) {
        unset($columns['date']); // Remove default Date column
        $columns['meeting_date'] = __('Meeting Date', 'nfinite-dash');
        $columns['meeting_time'] = __('Meeting Time', 'nfinite-dash');
        $columns['meet_link']    = __('Meet Link', 'nfinite-dash');
        return $columns;
    }

    /**
     * ✅ Populate Custom Columns
     */
    public function populate_meeting_columns($column, $post_id) {
        if ($column === 'meeting_date') {
            echo esc_html(get_post_meta($post_id, '_meeting_date', true));
        }
        if ($column === 'meeting_time') {
            $time = get_post_meta($post_id, '_meeting_time', true);
            echo $time ? date('g:i A', strtotime($time)) : __('N/A', 'nfinite-dash');
        }
        if ($column === 'meet_link') {
            $meet_link = get_post_meta($post_id, '_meeting_link', true);
            echo $meet_link ? '<a href="' . esc_url($meet_link) . '" target="_blank">Join Now</a>' : __('N/A', 'nfinite-dash');
        }
    }

    /**
     * ✅ Make Columns Sortable
     */
    public function make_meeting_columns_sortable($columns) {
        $columns['meeting_date'] = '_meeting_date';
        return $columns;
    }

    /**
     * ✅ Modify Query Order for Sorting
     */
    public function modify_meeting_orderby($query) {
        if (is_admin() && $query->is_main_query()) {
            $orderby = $query->get('orderby');
            if ($orderby === '_meeting_date') {
                $query->set('meta_key', '_meeting_date');
                $query->set('orderby', 'meta_value');
            }
        }
    }
}

// ✅ Initialize Meetings CPT
new Nfinite_Dash_Meetings_CPT();
