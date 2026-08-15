<?php
/**
 * Settings for Nfinite Dashboard.
 *
 * @package Nfinite_Dash
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return the default toolbar links used on a fresh install.
 *
 * @return array
 */
function nfinite_dash_default_toolbar_links() {
    return [
        [
            'label'      => 'Tasks',
            'url'        => admin_url('edit.php?post_type=task_manager_task&page=nfinite-task-cards'),
            'new_tab'    => 0,
        ],
        [
            'label'      => 'Clients',
            'url'        => admin_url('edit.php?post_type=client'),
            'new_tab'    => 0,
        ],
        [
            'label'      => 'Meetings',
            'url'        => admin_url('edit.php?post_type=meetings'),
            'new_tab'    => 0,
        ],
        [
            'label'      => 'My Notes',
            'url'        => admin_url('edit.php?post_type=my_notes&page=notes-cards-view'),
            'new_tab'    => 0,
        ],
        [
            'label'      => 'My Projects',
            'url'        => admin_url('edit.php?post_type=my_projects&page=my-projects-cards'),
            'new_tab'    => 0,
        ],
    ];
}

/**
 * Sanitize toolbar link repeater values.
 *
 * @param mixed $value Raw option value.
 * @return array
 */
function nfinite_dash_sanitize_toolbar_links($value) {
    if (!is_array($value)) {
        return [];
    }

    $clean = [];

    foreach ($value as $row) {
        if (!is_array($row)) {
            continue;
        }

        $label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
        $url   = isset($row['url']) ? trim(wp_unslash($row['url'])) : '';

        if ($label === '' || $url === '') {
            continue;
        }

        // Allow absolute URLs as well as WordPress admin-relative URLs.
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $url = esc_url_raw($url);
        } else {
            $url = sanitize_text_field($url);
        }

        $clean[] = [
            'label'   => $label,
            'url'     => $url,
            'new_tab' => !empty($row['new_tab']) ? 1 : 0,
        ];
    }

    return array_values($clean);
}


/**
 * Accept either a Google Calendar embed URL or Google's full iframe snippet.
 * Only the sanitized embed URL is stored.
 *
 * @param mixed $value Raw settings value.
 * @return string
 */
function nfinite_dash_sanitize_calendar_embed($value) {
    $value = trim(wp_unslash((string) $value));
    if ($value === '') {
        return '';
    }

    if (stripos($value, '<iframe') !== false && preg_match('/\\bsrc=["\\\']([^"\\\']+)["\\\']/i', $value, $matches)) {
        $value = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }

    $value = esc_url_raw($value);
    if (!$value) {
        add_settings_error('nfinite_dash_calendar_embed_url', 'invalid_calendar_url', __('Please paste a valid Google Calendar embed URL or iframe.', 'nfinite-dash'));
        return '';
    }

    $parts = wp_parse_url($value);
    $host  = isset($parts['host']) ? strtolower($parts['host']) : '';
    $path  = isset($parts['path']) ? $parts['path'] : '';
    $valid_hosts = ['calendar.google.com', 'www.google.com'];

    if (!in_array($host, $valid_hosts, true) || strpos($path, '/calendar/embed') === false) {
        add_settings_error('nfinite_dash_calendar_embed_url', 'invalid_calendar_embed', __('That looks like a Google Calendar page link, not an embed link. Use the URL from Settings → Integrate calendar → Embed code.', 'nfinite-dash'));
        return get_option('nfinite_dash_calendar_embed_url', '');
    }

    return $value;
}

/**
 * Add configured timezone to a Calendar embed URL when Google has not supplied one.
 *
 * @param string $url Calendar embed URL.
 * @return string
 */
function nfinite_dash_calendar_url_with_timezone($url) {
    $url = (string) $url;
    if ($url === '') return '';
    if (strpos($url, 'ctz=') !== false) return $url;
    $timezone = get_option('nfinite_dash_calendar_tz', get_option('timezone_string') ?: 'America/New_York');
    return add_query_arg('ctz', $timezone, $url);
}

add_action('admin_init', function () {
    register_setting('nfinite_dash_settings', 'nfinite_dash_calendar_embed_url', [
        'type'              => 'string',
        'sanitize_callback' => 'nfinite_dash_sanitize_calendar_embed',
        'default'           => '',
    ]);

    register_setting('nfinite_dash_settings', 'nfinite_dash_calendar_tz', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => get_option('timezone_string') ?: 'America/New_York',
    ]);

    register_setting('nfinite_dash_settings', 'nfinite_dash_toolbar_enabled', [
        'type'              => 'boolean',
        'sanitize_callback' => function ($value) {
            return !empty($value) ? 1 : 0;
        },
        'default'           => 1,
    ]);

    register_setting('nfinite_dash_settings', 'nfinite_dash_toolbar_links', [
        'type'              => 'array',
        'sanitize_callback' => 'nfinite_dash_sanitize_toolbar_links',
        'default'           => [],
    ]);

    add_settings_section(
        'nfinite_dash_toolbar_section',
        __('Admin Toolbar', 'nfinite-dash'),
        function () {
            echo '<p>' . esc_html__('Choose which shortcuts appear under Nfinite Dashboard in the WordPress admin toolbar. Add, remove, or reorder links without editing plugin code.', 'nfinite-dash') . '</p>';
        },
        'nfinite_dash_settings'
    );

    add_settings_field(
        'nfinite_dash_toolbar_enabled',
        __('Enable Toolbar Menu', 'nfinite-dash'),
        function () {
            $enabled = (int) get_option('nfinite_dash_toolbar_enabled', 1);
            echo '<input type="hidden" name="nfinite_dash_toolbar_enabled" value="0">';
            echo '<label><input type="checkbox" name="nfinite_dash_toolbar_enabled" value="1" ' . checked(1, $enabled, false) . '> ';
            echo esc_html__('Show Nfinite Dashboard shortcuts in the WordPress toolbar.', 'nfinite-dash') . '</label>';
        },
        'nfinite_dash_settings',
        'nfinite_dash_toolbar_section'
    );

    add_settings_field(
        'nfinite_dash_toolbar_links',
        __('Toolbar Links', 'nfinite-dash'),
        'nfinite_dash_render_toolbar_links_field',
        'nfinite_dash_settings',
        'nfinite_dash_toolbar_section'
    );

    add_settings_section(
        'nfinite_dash_calendar_section',
        __('Calendar Settings', 'nfinite-dash'),
        '__return_false',
        'nfinite_dash_settings'
    );

    add_settings_field(
        'nfinite_dash_calendar_embed_url',
        __('Google Calendar Embed URL', 'nfinite-dash'),
        function () {
            $val = get_option('nfinite_dash_calendar_embed_url', '');
            echo '<textarea class="large-text code nfinite-calendar-embed-input" rows="4" name="nfinite_dash_calendar_embed_url" placeholder="Paste the full Google iframe or https://calendar.google.com/calendar/embed?...">' . esc_textarea($val) . '</textarea>';
            echo '<p class="description">' . esc_html__('Paste either Google’s full iframe embed code or only its embed URL. Nfinite will extract and store the correct URL automatically.', 'nfinite-dash') . '</p>';
            if ($val) {
                $preview = nfinite_dash_calendar_url_with_timezone($val);
                echo '<div class="nfinite-settings-calendar-preview">';
                echo '<div class="nfinite-settings-calendar-preview__heading"><strong>' . esc_html__('Calendar Preview', 'nfinite-dash') . '</strong><span>' . esc_html__('If this loads correctly, the dashboard calendar is connected.', 'nfinite-dash') . '</span></div>';
                echo '<iframe src="' . esc_url($preview) . '" title="' . esc_attr__('Google Calendar preview', 'nfinite-dash') . '" loading="lazy"></iframe>';
                echo '</div>';
            }
        },
        'nfinite_dash_settings',
        'nfinite_dash_calendar_section'
    );

    add_settings_field(
        'nfinite_dash_calendar_tz',
        __('Calendar Timezone', 'nfinite-dash'),
        function () {
            $val = esc_attr(get_option('nfinite_dash_calendar_tz', 'America/New_York'));
            echo '<input type="text" class="regular-text" name="nfinite_dash_calendar_tz" value="' . $val . '">';
            echo '<p class="description">' . esc_html__('IANA timezone (e.g., America/New_York). Will be appended to the embed URL if missing.', 'nfinite-dash') . '</p>';
        },
        'nfinite_dash_settings',
        'nfinite_dash_calendar_section'
    );
});

/**
 * Toolbar repeater field.
 */
function nfinite_dash_render_toolbar_links_field() {
    $links = get_option('nfinite_dash_toolbar_links', []);

    // Existing installs get useful defaults until they explicitly save the toolbar field.
    if (!is_array($links) || empty($links)) {
        $links = nfinite_dash_default_toolbar_links();
    }
    ?>
    <div id="nfinite-toolbar-links" class="nfinite-toolbar-links">
        <div class="nfinite-toolbar-links__rows">
            <?php foreach ($links as $index => $link) : ?>
                <?php nfinite_dash_render_toolbar_link_row($index, $link); ?>
            <?php endforeach; ?>
        </div>
        <p>
            <button type="button" class="button" id="nfinite-add-toolbar-link"><?php esc_html_e('Add Toolbar Link', 'nfinite-dash'); ?></button>
        </p>
        <p class="description">
            <?php esc_html_e('URLs can be full external URLs or WordPress admin-relative paths such as edit.php?post_type=client.', 'nfinite-dash'); ?>
        </p>
    </div>
    <script type="text/html" id="tmpl-nfinite-toolbar-link-row">
        <?php nfinite_dash_render_toolbar_link_row('{{data.index}}', ['label' => '', 'url' => '', 'new_tab' => 0], true); ?>
    </script>
    <?php
}

/**
 * Render one toolbar link row.
 *
 * @param int|string $index Row index.
 * @param array      $link  Link data.
 * @param bool       $template Whether this is an underscore template.
 */
function nfinite_dash_render_toolbar_link_row($index, $link, $template = false) {
    $label   = isset($link['label']) ? $link['label'] : '';
    $url     = isset($link['url']) ? $link['url'] : '';
    $new_tab = !empty($link['new_tab']);

    $name_base = 'nfinite_dash_toolbar_links[' . $index . ']';
    ?>
    <div class="nfinite-toolbar-link-row">
        <span class="dashicons dashicons-move nfinite-toolbar-link-handle" aria-hidden="true"></span>
        <input type="text" class="regular-text" name="<?php echo esc_attr($name_base); ?>[label]" value="<?php echo $template ? '' : esc_attr($label); ?>" placeholder="<?php esc_attr_e('Label', 'nfinite-dash'); ?>">
        <input type="text" class="large-text code" name="<?php echo esc_attr($name_base); ?>[url]" value="<?php echo $template ? '' : esc_attr($url); ?>" placeholder="https://example.com/ or edit.php?post_type=client">
        <label class="nfinite-toolbar-link-new-tab">
            <input type="checkbox" name="<?php echo esc_attr($name_base); ?>[new_tab]" value="1" <?php echo (!$template && $new_tab) ? 'checked' : ''; ?>>
            <?php esc_html_e('New tab', 'nfinite-dash'); ?>
        </label>
        <button type="button" class="button-link-delete nfinite-remove-toolbar-link"><?php esc_html_e('Remove', 'nfinite-dash'); ?></button>
    </div>
    <?php
}

/**
 * Settings page renderer.
 */
function nfinite_dash_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap nfinite-settings-wrap"><h1>' . esc_html__('Nfinite Dashboard Settings', 'nfinite-dash') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('nfinite_dash_settings');
    do_settings_sections('nfinite_dash_settings');
    submit_button();
    echo '</form></div>';
}

/**
 * Settings-only assets for repeater controls.
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'nfinite-dash-settings') {
        return;
    }

    wp_enqueue_script('jquery-ui-sortable');

    $script = <<<'JS'
(function ($) {
    'use strict';

    function reindexRows() {
        $('#nfinite-toolbar-links .nfinite-toolbar-link-row').each(function (index) {
            $(this).find('[name]').each(function () {
                var current = $(this).attr('name');
                if (!current) return;
                $(this).attr('name', current.replace(/nfinite_dash_toolbar_links\[[^\]]+\]/, 'nfinite_dash_toolbar_links[' + index + ']'));
            });
        });
    }

    $(function () {
        var $rows = $('#nfinite-toolbar-links .nfinite-toolbar-links__rows');

        $rows.sortable({
            handle: '.nfinite-toolbar-link-handle',
            update: reindexRows
        });

        $('#nfinite-add-toolbar-link').on('click', function () {
            var index = $rows.find('.nfinite-toolbar-link-row').length;
            var template = $('#tmpl-nfinite-toolbar-link-row').html().replace(/\{\{data\.index\}\}/g, index);
            $rows.append(template);
            reindexRows();
        });

        $rows.on('click', '.nfinite-remove-toolbar-link', function () {
            $(this).closest('.nfinite-toolbar-link-row').remove();
            reindexRows();
        });
    });
})(jQuery);
JS;

    wp_add_inline_script('jquery-ui-sortable', $script);
});

/**
 * Frontend Tools settings.
 */
add_action('admin_init', function () {
    $bool = function ($value) { return !empty($value) ? 1 : 0; };
    $positive_int = function ($value) { return max(1, absint($value)); };

    register_setting('nfinite_dash_settings', 'nfinite_frontend_hero_enabled', ['type'=>'boolean','sanitize_callback'=>$bool,'default'=>1]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_directory_enabled', ['type'=>'boolean','sanitize_callback'=>$bool,'default'=>1]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_submission_enabled', ['type'=>'boolean','sanitize_callback'=>$bool,'default'=>1]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_hero_projects', ['type'=>'integer','sanitize_callback'=>$positive_int,'default'=>15]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_hero_tasks', ['type'=>'integer','sanitize_callback'=>$positive_int,'default'=>15]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_directory_posts', ['type'=>'integer','sanitize_callback'=>$positive_int,'default'=>50]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_allow_public_submissions', ['type'=>'boolean','sanitize_callback'=>$bool,'default'=>1]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_submission_require_login', ['type'=>'boolean','sanitize_callback'=>$bool,'default'=>0]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_projects_page', ['type'=>'integer','sanitize_callback'=>'absint','default'=>0]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_submission_page', ['type'=>'integer','sanitize_callback'=>'absint','default'=>0]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_submission_default_priority', [
        'type'=>'string',
        'sanitize_callback'=>function($value){ $value=sanitize_key($value); return in_array($value,['low','medium','high','urgent'],true)?$value:'medium'; },
        'default'=>'medium'
    ]);
    register_setting('nfinite_dash_settings', 'nfinite_frontend_submission_default_status', [
        'type'=>'string',
        'sanitize_callback'=>function($value){ $value=sanitize_key($value); return in_array($value,['not_started','in_progress'],true)?$value:'not_started'; },
        'default'=>'not_started'
    ]);

    add_settings_section(
        'nfinite_dash_frontend_section',
        __('Frontend Tools', 'nfinite-dash'),
        function () {
            echo '<p>' . esc_html__('Manage the public-facing Nfinite project hub, project directory, and frontend intake form. Existing shortcode names are preserved for compatibility.', 'nfinite-dash') . '</p>';
        },
        'nfinite_dash_settings'
    );

    add_settings_field('nfinite_frontend_features', __('Enabled Features', 'nfinite-dash'), function () {
        $items = [
            'nfinite_frontend_hero_enabled' => __('Projects Hero / Operations Snapshot', 'nfinite-dash'),
            'nfinite_frontend_directory_enabled' => __('Projects Directory', 'nfinite-dash'),
            'nfinite_frontend_submission_enabled' => __('Project Submission Form', 'nfinite-dash'),
        ];
        foreach ($items as $name => $label) {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="0">';
            echo '<label class="nfinite-settings-check"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked(1, (int)get_option($name,1), false) . '> ' . esc_html($label) . '</label><br>';
        }
    }, 'nfinite_dash_settings', 'nfinite_dash_frontend_section');

    add_settings_field('nfinite_frontend_shortcodes', __('Shortcodes', 'nfinite-dash'), function () {
        echo '<div class="nfinite-shortcode-list">';
        echo '<p><code>[qck_projects_hero]</code><br><span class="description">' . esc_html__('Operations hero with current projects and completed tasks.', 'nfinite-dash') . '</span></p>';
        echo '<p><code>[qck_projects_directory]</code><br><span class="description">' . esc_html__('Active and completed project directory, ranked by priority.', 'nfinite-dash') . '</span></p>';
        echo '<p><code>[qck_project_submission_form]</code><br><span class="description">' . esc_html__('Project intake form that creates Pending Review projects.', 'nfinite-dash') . '</span></p>';
        echo '</div>';
    }, 'nfinite_dash_settings', 'nfinite_dash_frontend_section');

    add_settings_field('nfinite_frontend_display_limits', __('Display Limits', 'nfinite-dash'), function () {
        $projects = absint(get_option('nfinite_frontend_hero_projects', 15));
        $tasks = absint(get_option('nfinite_frontend_hero_tasks', 15));
        $directory = absint(get_option('nfinite_frontend_directory_posts', 50));
        echo '<label>' . esc_html__('Hero projects', 'nfinite-dash') . ' <input type="number" min="1" max="100" name="nfinite_frontend_hero_projects" value="' . esc_attr($projects) . '" class="small-text"></label> &nbsp; ';
        echo '<label>' . esc_html__('Hero completed tasks', 'nfinite-dash') . ' <input type="number" min="1" max="100" name="nfinite_frontend_hero_tasks" value="' . esc_attr($tasks) . '" class="small-text"></label> &nbsp; ';
        echo '<label>' . esc_html__('Directory projects', 'nfinite-dash') . ' <input type="number" min="1" max="500" name="nfinite_frontend_directory_posts" value="' . esc_attr($directory) . '" class="small-text"></label>';
    }, 'nfinite_dash_settings', 'nfinite_dash_frontend_section');

    add_settings_field('nfinite_frontend_pages', __('Frontend Pages', 'nfinite-dash'), function () {
        $project_page = absint(get_option('nfinite_frontend_projects_page', 0));
        $submission_page = absint(get_option('nfinite_frontend_submission_page', 0));
        wp_dropdown_pages(['name'=>'nfinite_frontend_projects_page','selected'=>$project_page,'show_option_none'=>__('— Select projects page —','nfinite-dash'),'option_none_value'=>0]);
        echo '<span class="description" style="margin-left:8px">' . esc_html__('Projects page', 'nfinite-dash') . '</span><br><br>';
        wp_dropdown_pages(['name'=>'nfinite_frontend_submission_page','selected'=>$submission_page,'show_option_none'=>__('— Select submission page —','nfinite-dash'),'option_none_value'=>0]);
        echo '<span class="description" style="margin-left:8px">' . esc_html__('Submission page', 'nfinite-dash') . '</span>';
    }, 'nfinite_dash_settings', 'nfinite_dash_frontend_section');

    add_settings_field('nfinite_frontend_submission_access', __('Submission Access', 'nfinite-dash'), function () {
        $allow_public = (int)get_option('nfinite_frontend_allow_public_submissions', 1);
        $require_login = (int)get_option('nfinite_frontend_submission_require_login', 0);
        echo '<input type="hidden" name="nfinite_frontend_allow_public_submissions" value="0"><label><input type="checkbox" name="nfinite_frontend_allow_public_submissions" value="1" ' . checked(1,$allow_public,false) . '> ' . esc_html__('Allow logged-out visitors to submit projects', 'nfinite-dash') . '</label><br>';
        echo '<input type="hidden" name="nfinite_frontend_submission_require_login" value="0"><label><input type="checkbox" name="nfinite_frontend_submission_require_login" value="1" ' . checked(1,$require_login,false) . '> ' . esc_html__('Require a WordPress login before submission', 'nfinite-dash') . '</label>';
        echo '<p class="description">' . esc_html__('If login is required, anonymous submissions are blocked even when public submissions are otherwise enabled.', 'nfinite-dash') . '</p>';
    }, 'nfinite_dash_settings', 'nfinite_dash_frontend_section');

    add_settings_field('nfinite_frontend_submission_defaults', __('Submission Defaults', 'nfinite-dash'), function () {
        $priority = get_option('nfinite_frontend_submission_default_priority', 'medium');
        $status = get_option('nfinite_frontend_submission_default_status', 'not_started');
        echo '<label>' . esc_html__('Priority', 'nfinite-dash') . ' <select name="nfinite_frontend_submission_default_priority">';
        foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $value=>$label) echo '<option value="'.esc_attr($value).'" '.selected($priority,$value,false).'>'.esc_html($label).'</option>';
        echo '</select></label> &nbsp; ';
        echo '<label>' . esc_html__('Project status', 'nfinite-dash') . ' <select name="nfinite_frontend_submission_default_status">';
        foreach (['not_started'=>'Not Started','in_progress'=>'In Progress'] as $value=>$label) echo '<option value="'.esc_attr($value).'" '.selected($status,$value,false).'>'.esc_html($label).'</option>';
        echo '</select></label>';
        echo '<p class="description">' . esc_html__('The WordPress post itself is always created as Pending Review; these values control the Nfinite project metadata.', 'nfinite-dash') . '</p>';
    }, 'nfinite_dash_settings', 'nfinite_dash_frontend_section');
});
