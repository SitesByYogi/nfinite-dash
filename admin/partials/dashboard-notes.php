<?php
/**
 * Dashboard Notes Section
 *
 * Displays featured notes in a 3-column grid format.
 *
 * @package Nfinite_Dash
 */

// Fetch Featured Notes
$notes = get_posts([
    'post_type'      => 'my_notes',
    'posts_per_page' => 6, // Show up to 6 notes
    'meta_query'     => [
        [
            'key'     => '_is_featured',
            'value'   => '1',
            'compare' => '=',
        ],
    ],
]);
?>

<div class="dashboard-notes-grid">
    <?php if ($notes): ?>
        <?php foreach ($notes as $note): 
            $note_id  = $note->ID;
            $links    = get_post_meta($note_id, '_notes_links', true);
            $is_featured = get_post_meta($note_id, '_is_featured', true);
            ?>
            <div class="note-card">
                <h3 class="note-title">
                    <a href="<?php echo get_edit_post_link($note_id); ?>">
                        <?php echo esc_html($note->post_title); ?>
                    </a>
                </h3>

                <?php if ($is_featured): ?>
                    <span class="featured-badge"><?php _e('Featured', 'nfinite-dash'); ?></span>
                <?php endif; ?>

                <p class="note-excerpt"><?php echo wp_trim_words(get_the_excerpt($note_id), 15, '...'); ?></p>

                <?php if (!empty($links)): ?>
                    <ul class="note-links">
                        <?php foreach ($links as $link): ?>
                            <li>
                                <a href="<?php echo esc_url($link['url']); ?>" target="_blank">
                                    <?php echo esc_html($link['text']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="note-actions">
                    <a href="<?php echo get_edit_post_link($note_id); ?>" class="button button-secondary">
                        <?php _e('Edit Note', 'nfinite-dash'); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p><?php _e('No featured notes found.', 'nfinite-dash'); ?></p>
    <?php endif; ?>
</div>

<div class="notes-buttons">
    <a href="<?php echo admin_url('post-new.php?post_type=my_notes'); ?>" class="button button-primary"><?php _e('Add New Note', 'nfinite-dash'); ?></a>
    <a href="<?php echo admin_url('edit.php?post_type=my_notes'); ?>" class="button"><?php _e('View All Notes', 'nfinite-dash'); ?></a>
</div>


<!-- ✅ JavaScript for AJAX Inline Editing -->
<script>
jQuery(document).ready(function ($) {
    function updateFeaturedStatus(noteId, isFeatured) {
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'update_note_featured_status',
                note_id: noteId,
                is_featured: isFeatured ? '1' : '',
                _ajax_nonce: '<?php echo wp_create_nonce("update_note_featured_status_nonce"); ?>'
            },
            success: function (response) {
                if (response.success) {
                    alert('Note featured status updated.');
                } else {
                    alert('Failed to update featured status.');
                }
            },
            error: function () {
                alert('An error occurred while updating featured status.');
            }
        });
    }

    $('.featured-note-checkbox').on('change', function () {
        var noteId = $(this).data('note-id');
        var isFeatured = $(this).is(':checked');
        updateFeaturedStatus(noteId, isFeatured);
    });
});
</script>
