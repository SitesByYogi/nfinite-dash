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

        // ✅ Sorting & Filtering
        add_filter('manage_edit-meetings_sortable_columns', array($this, 'make_meeting_columns_sortable'));
        add_action('pre_get_posts', array($this, 'filter_upcoming_meetings'));
        add_action('pre_get_posts', array($this, 'modify_meeting_orderby'));

        // ✅ Add "View Past Meetings" Button
        add_action('restrict_manage_posts', array($this, 'add_past_meetings_button'));
    }

    public function register_post_type() {
        $args = array(
            'labels' => array(
                'name'          => __('Meetings', 'nfinite-dash'),
                'singular_name' => __('Meeting', 'nfinite-dash'),
                'menu_name'     => __('Meetings', 'nfinite-dash'),
                'add_new'       => __('Add New Meeting', 'nfinite-dash'),
                'all_items'     => __('All Meetings', 'nfinite-dash'),
                'edit_item'     => __('Edit Meeting', 'nfinite-dash'),
                'view_item'     => __('View Meeting', 'nfinite-dash'),
            ),
            'public'        => true,
            'has_archive'   => true,
            'show_ui'       => true,
            'show_in_menu'  => 'nfinite-dashboard',
            'menu_icon'     => 'dashicons-calendar-alt',
            'supports'      => array('title', 'editor'),
        );
        register_post_type('meetings', $args);
    }

    /**
     * ✅ Make Meeting Columns Sortable
     */
    public function make_meeting_columns_sortable($columns) {
        $columns['meeting_date'] = 'meeting_date';
        $columns['meeting_time'] = 'meeting_time';
        return $columns;
    }

    /**
     * ✅ Modify Query Order for Sorting
     */
    public function modify_meeting_orderby($query) {
        if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'meetings') {
            $orderby = $query->get('orderby');
            if ($orderby === 'meeting_date') {
                $query->set('meta_key', '_meeting_date');
                $query->set('orderby', 'meta_value');
            }
            if ($orderby === 'meeting_time') {
                $query->set('meta_key', '_meeting_time');
                $query->set('orderby', 'meta_value');
            }
        }
    }

    /**
     * ✅ Add Meta Boxes for Meetings (Date, Time, Link, Status, Type)
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
     * ✅ Meeting Meta Box Callback (Date, Time, Link, Status, Type)
     */
    public function meeting_meta_box_callback($post) {
        $meeting_date   = get_post_meta($post->ID, '_meeting_date', true);
        $meeting_time   = get_post_meta($post->ID, '_meeting_time', true);
        $meeting_status = get_post_meta($post->ID, '_meeting_status', true) ?: 'pending';
        $meeting_link   = get_post_meta($post->ID, '_meeting_link', true);
        $meeting_type   = get_post_meta($post->ID, '_meeting_type', true) ?: 'google_meet';

        wp_nonce_field('meeting_save_meta_box_data', 'meeting_meta_box_nonce');

        ?>
        <p>
            <label for="meeting_date"><?php _e('Meeting Date:', 'nfinite-dash'); ?></label>
            <input type="date" name="meeting_date" id="meeting_date" value="<?php echo esc_attr($meeting_date); ?>" />
        </p>
        <p>
            <label for="meeting_time"><?php _e('Meeting Time:', 'nfinite-dash'); ?></label>
            <input type="time" name="meeting_time" id="meeting_time" value="<?php echo esc_attr($meeting_time); ?>" />
        </p>
        <p>
            <label for="meeting_status"><?php _e('Meeting Status:', 'nfinite-dash'); ?></label>
            <select name="meeting_status" id="meeting_status">
                <option value="pending" <?php selected($meeting_status, 'pending'); ?>>Pending</option>
                <option value="completed" <?php selected($meeting_status, 'completed'); ?>>Completed</option>
                <option value="canceled" <?php selected($meeting_status, 'canceled'); ?>>Canceled</option>
            </select>
        </p>
        <p>
            <label for="meeting_link"><?php _e('Meeting Link:', 'nfinite-dash'); ?></label>
            <input type="url" name="meeting_link" id="meeting_link" value="<?php echo esc_attr($meeting_link); ?>" placeholder="https://example.com/meet" />
        </p>
        <p>
            <label for="meeting_type"><?php _e('Meeting Type:', 'nfinite-dash'); ?></label>
            <select name="meeting_type" id="meeting_type">
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
        if (!isset($_POST['meeting_meta_box_nonce']) || !wp_verify_nonce($_POST['meeting_meta_box_nonce'], 'meeting_save_meta_box_data')) {
            return;
        }

        $fields = [
            '_meeting_date'   => 'sanitize_text_field',
            '_meeting_time'   => 'sanitize_text_field',
            '_meeting_status' => 'sanitize_text_field',
            '_meeting_link'   => 'esc_url_raw',
            '_meeting_type'   => 'sanitize_text_field',
        ];

        foreach ($fields as $field => $sanitizer) {
            if (isset($_POST[ltrim($field, '_')])) {
                update_post_meta($post_id, $field, call_user_func($sanitizer, $_POST[ltrim($field, '_')]));
            }
        }
    }

    /**
 * ✅ Filter: Show Only Upcoming Meetings (Based on Meeting Date)
 */
public function filter_upcoming_meetings($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'meetings') {
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        $current_date = current_time('Y-m-d'); // Get today's date

        if ($filter_status !== 'past') {
            // ✅ Show meetings where the meeting date is today or in the future
            $query->set('meta_query', array(
                array(
                    'key'     => '_meeting_date',
                    'value'   => $current_date,
                    'compare' => '>=',
                    'type'    => 'DATE',
                )
            ));
        }
    }
}


    /**
     * ✅ Add "View Past Meetings" Button to Admin Table
     */
    public function add_past_meetings_button() {
        global $typenow;

        if ($typenow === 'meetings') {
            $current_filter = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
            $is_past = ($current_filter === 'past');
            $button_label = $is_past ? __('View Upcoming Meetings', 'nfinite-dash') : __('View Past Meetings', 'nfinite-dash');
            $button_url   = admin_url('edit.php?post_type=meetings');

            if (!$is_past) {
                $button_url = add_query_arg('filter_status', 'past', $button_url);
            }

            echo '<a href="' . esc_url($button_url) . '" class="button">' . esc_html($button_label) . '</a>';
        }
    }

    /**
     * ✅ Add Custom Columns to Meetings Admin Table
     */
    public function add_meeting_columns($columns) {
        unset($columns['date']);
        $columns['meeting_date'] = __('Meeting Date', 'nfinite-dash');
        $columns['meeting_time'] = __('Meeting Time', 'nfinite-dash');
        $columns['meeting_status'] = __('Status', 'nfinite-dash');
        $columns['meeting_link'] = __('Join Link', 'nfinite-dash');
        return $columns;
    }

    /**
     * ✅ Populate Custom Columns
     */
    public function populate_meeting_columns($column, $post_id) {
        $meta_value = get_post_meta($post_id, '_' . $column, true);
        if ($column === 'meeting_link') {
            echo $meta_value ? '<a href="' . esc_url($meta_value) . '" target="_blank">Join Now</a>' : __('N/A', 'nfinite-dash');
        } else {
            echo esc_html($meta_value);
        }
    }
}

// ✅ Initialize Meetings CPT
new Nfinite_Dash_Meetings_CPT();
