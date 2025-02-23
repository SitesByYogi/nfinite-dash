<?php
/**
 * My Notes Custom Post Type for Nfinite Dashboard
 *
 * @package Nfinite_Dash
 */

if (!class_exists('Nfinite_Dash_My_Notes_CPT')) { // ✅ Prevent duplicate declaration
    class Nfinite_Dash_My_Notes_CPT {

        public function __construct() {
            add_action('init', array($this, 'register_post_type'));
            add_action('add_meta_boxes', array($this, 'add_my_notes_meta_boxes'));
            add_action('save_post', array($this, 'save_my_notes_meta_box_data'));

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
                'singular_name' => __('My Note', 'nfinite-dash'),
                'menu_name'     => __('My Notes', 'nfinite-dash'),
                'add_new'       => __('Add New Note', 'nfinite-dash'),
                'all_items'     => __('All Notes', 'nfinite-dash'),
                'edit_item'     => __('Edit Note', 'nfinite-dash'),
                'view_item'     => __('View Note', 'nfinite-dash'),
            );

            $args = array(
                'labels'        => $labels,
                'public'        => true,
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
