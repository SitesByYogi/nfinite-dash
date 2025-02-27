jQuery(document).ready(function ($) {
    console.log("✅ Nfinite Dashboard Script Loaded");

    function updateTaskMeta(taskId, metaKey, metaValue) {
        if (!taskId || !metaKey) {
            console.error("❌ Missing taskId or metaKey for AJAX update.");
            return;
        }

        console.log("🔄 Sending AJAX Request:", { taskId, metaKey, metaValue });

        $.ajax({
            url: taskManagerAjax.ajax_url,
            type: "POST",
            data: {
                action: "task_manager_update_meta",
                task_id: taskId,
                meta_key: metaKey,
                meta_value: metaValue,
                _ajax_nonce: taskManagerAjax.nonce
            },
            success: function (response) {
                console.log("✅ AJAX Response:", response);

                if (response.success) {
                    console.log(`🎯 Updated ${metaKey} successfully.`);

                    // ✅ Sync all dropdowns with the same task ID & meta key
                    $(".task-meta-dropdown[data-task-id='" + taskId + "'][data-meta-key='" + metaKey + "']")
                        .val(metaValue);
                } else {
                    console.error(`❌ Failed to update ${metaKey}:`, response.data);
                    alert(`❌ Failed to update ${metaKey}`);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error:", error);
                alert("❌ AJAX Error: Unable to update task.");
            }
        });
    }

    $(document).on("change", ".task-meta-dropdown", function () {
        let taskId = $(this).data("task-id");
        let metaKey = $(this).data("meta-key");
        let metaValue = $(this).val();

        updateTaskMeta(taskId, metaKey, metaValue);
    });
});
