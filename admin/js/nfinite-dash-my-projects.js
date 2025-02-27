jQuery(document).ready(function ($) {
    console.log("✅ Nfinite Dashboard Projects Script Loaded");

    function updateProjectMeta(projectId, metaKey, metaValue) {
        if (!projectId || !metaKey) {
            console.error("❌ Missing projectId or metaKey for AJAX update.");
            return;
        }

        console.log("🔄 Sending AJAX Request:", { projectId, metaKey, metaValue });

        $.ajax({
            url: myProjectsAjax.ajax_url,
            type: "POST",
            data: {
                action: "my_projects_update_meta",
                post_id: projectId,
                meta_key: metaKey,
                meta_value: metaValue,
                _ajax_nonce: myProjectsAjax.nonce
            },
            success: function (response) {
                console.log("✅ AJAX Response:", response);

                if (response.success) {
                    console.log(`🎯 Updated ${metaKey} successfully.`);

                    // ✅ Sync all dropdowns across ALL sections
                    $(".project-status-dropdown[data-project-id='" + projectId + "'][data-meta-key='" + metaKey + "']").val(metaValue);
                    $(".project-meta-dropdown[data-project-id='" + projectId + "'][data-meta-key='" + metaKey + "']").val(metaValue);
                    $("#project_status, #project_priority").val(metaValue);
                } else {
                    console.error(`❌ Failed to update ${metaKey}:`, response.data);
                    alert(`❌ Failed to update ${metaKey}. Server message: ${response.data.message}`);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error:", error);
                alert("❌ AJAX Error: Unable to update project.");
            }
        });
    }

    $(document).on("change", ".project-status-dropdown, .project-meta-dropdown, #project_status, #project_priority", function () {
        let projectId = $(this).data("project-id") || $("#post_ID").val();
        let metaKey = $(this).attr("name") || $(this).data("meta-key");
        let metaValue = $(this).val();

        updateProjectMeta(projectId, metaKey, metaValue);
    });
});
