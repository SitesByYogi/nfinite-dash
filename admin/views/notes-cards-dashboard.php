<div class="wrap">
    <h1><?php _e('Notes – Card View', 'nfinite-dash'); ?></h1>

    <!-- 🔘 Top Action Buttons -->
    <div class="notes-buttons" style="margin-bottom: 20px;">
        <a href="<?php echo admin_url('edit.php?post_type=my_notes'); ?>" class="button"><?php _e('View List View', 'nfinite-dash'); ?></a>
        <a href="<?php echo admin_url('post-new.php?post_type=my_notes'); ?>" class="button button-primary"><?php _e('Add New Note', 'nfinite-dash'); ?></a>
    </div>

    <?php
    // ✅ Fetch Pinned Notes
    $featured_notes = get_posts([
        'post_type'      => 'my_notes',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => '_is_featured',
                'value'   => '1',
                'compare' => '='
            ]
        ]
    ]);

    // ✅ Fetch All Other Notes (not featured)
    $regular_notes = get_posts([
        'post_type'      => 'my_notes',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => '_is_featured',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key'     => '_is_featured',
                'value'   => '1',
                'compare' => '!='
            ]
        ]
    ]);
    ?>

    <!-- 📌 Section: Pinned Notes -->
    <?php if ($featured_notes): ?>
        <h2 style="margin-top: 30px;"><?php _e('📌 Pinned Notes', 'nfinite-dash'); ?></h2>
        <div class="dashboard-notes-grid">
            <?php foreach ($featured_notes as $note):
                $note_id     = $note->ID;
                $links       = get_post_meta($note_id, '_notes_links', true);
            ?>
                <div class="note-card">
                    <h3 class="note-title">
                        <a href="<?php echo get_edit_post_link($note_id); ?>">
                            <?php echo esc_html($note->post_title); ?>
                        </a>
                    </h3>

                    <span class="featured-badge"><?php _e('Featured', 'nfinite-dash'); ?></span>

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
        </div>
    <?php endif; ?>

    <!-- 🗒️ Section: All Other Notes -->
    <?php if ($regular_notes): ?>
        <h2 style="margin-top: 40px;"><?php _e('🗒️ All Notes', 'nfinite-dash'); ?></h2>
        <div class="dashboard-notes-grid">
            <?php foreach ($regular_notes as $note):
                $note_id     = $note->ID;
                $links       = get_post_meta($note_id, '_notes_links', true);
            ?>
                <div class="note-card">
                    <h3 class="note-title">
                        <a href="<?php echo get_edit_post_link($note_id); ?>">
                            <?php echo esc_html($note->post_title); ?>
                        </a>
                    </h3>

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
        </div>
    <?php endif; ?>

    <?php if (!$featured_notes && !$regular_notes): ?>
        <p><?php _e('No notes found.', 'nfinite-dash'); ?></p>
    <?php endif; ?>
</div>

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
                    console.log('✅ Note updated.');
                } else {
                    alert('❌ Failed to update featured status.');
                }
            },
            error: function () {
                alert('❌ AJAX error.');
            }
        });
    }

    $('.featured-note-checkbox').on('change', function () {
        let noteId = $(this).data('note-id');
        let isFeatured = $(this).is(':checked');
        updateFeaturedStatus(noteId, isFeatured);
    });
});
</script>
