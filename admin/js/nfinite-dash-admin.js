(function ($) {
    'use strict';

    $(document).ready(function () {
        // ✅ Handle Status & Priority Dropdown Changes for Tasks
        $('.task-meta-dropdown').change(function () {
            var postID = $(this).data('task-id');
            var metaKey = $(this).data('meta-key');
            var metaValue = $(this).val();

            $.ajax({
                url: taskManagerAjax.ajax_url, // ✅ WordPress AJAX URL
                type: 'POST',
                data: {
                    action: 'task_manager_update_meta',
                    _ajax_nonce: taskManagerAjax.nonce, // ✅ Security nonce
                    post_id: postID,
                    meta_key: metaKey,
                    meta_value: metaValue
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Task updated successfully.");
                    } else {
                        console.log("Failed to update task.");
                    }
                },
                error: function () {
                    console.log("AJAX error: Unable to update task.");
                }
            });
        });

        // ✅ Handle Status & Priority Dropdown Changes for My Projects
        $('.project-meta-dropdown').change(function () {
            var postID = $(this).data('project-id');
            var metaKey = $(this).data('meta-key');
            var metaValue = $(this).val();

            $.ajax({
                url: myProjectsAjax.ajax_url, // ✅ WordPress AJAX URL
                type: 'POST',
                data: {
                    action: 'my_projects_update_meta',
                    _ajax_nonce: myProjectsAjax.nonce, // ✅ Security nonce
                    post_id: postID,
                    meta_key: metaKey,
                    meta_value: metaValue
                },
                success: function (response) {
                    if (response.success) {
                        console.log("Project updated successfully.");
                    } else {
                        console.log("Failed to update project.");
                    }
                },
                error: function () {
                    console.log("AJAX error: Unable to update project.");
                }
            });
        });
    });

})(jQuery);
