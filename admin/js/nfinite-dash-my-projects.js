jQuery(document).ready(function ($) {
    console.log("✅ Nfinite Dashboard Projects Script Loaded");

    /**
     * ✅ Function to update Project Metadata via AJAX
     */
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

                    // ✅ Sync all dropdowns with the same project ID across pages
                    $(".project-status-dropdown[data-project-id='" + projectId + "']").val(metaValue);
                    $(".project-meta-dropdown[data-project-id='" + projectId + "'][data-meta-key='" + metaKey + "']").val(metaValue);
                } else {
                    console.error(`❌ Failed to update ${metaKey}:`, response.data);
                    alert(`❌ Failed to update ${metaKey}`);
                }
            },
            error: function (xhr, status, error) {
                console.error("❌ AJAX Error:", error);
                alert("❌ AJAX Error: Unable to update project.");
            }
        });
    }

    /**
     * ✅ Event Listener for Dropdown Changes
     */
    $(document).on("change", ".project-status-dropdown", function () {
        let projectId = $(this).data("project-id");
        let metaKey = "_my_project_status";
        let metaValue = $(this).val();

        updateProjectMeta(projectId, metaKey, metaValue);
    });

    $(document).on("change", "#project_status, #project_priority", function () {
        let projectId = $("#post_ID").val();
        let metaKey = $(this).attr("name");
        let metaValue = $(this).val();

        updateProjectMeta(projectId, metaKey, metaValue);
    });
});
