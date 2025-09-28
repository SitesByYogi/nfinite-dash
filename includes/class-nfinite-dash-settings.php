<?php
// Settings page for Nfinite Dash plugin
// Register settings and fields

add_action('admin_init', function () {
    register_setting('nfinite_dash_settings', 'nfinite_dash_calendar_embed_url', [
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);

    register_setting('nfinite_dash_settings', 'nfinite_dash_calendar_tz', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => get_option('timezone_string') ?: 'America/New_York',
    ]);

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
            $val = esc_url(get_option('nfinite_dash_calendar_embed_url', ''));
            echo '<input type="url" class="regular-text code" name="nfinite_dash_calendar_embed_url" value="' . $val . '" placeholder="https://calendar.google.com/calendar/embed?...">';
            echo '<p class="description">' . esc_html__('Paste the Google Calendar embed URL from Google Calendar → Settings → Integrate calendar.', 'nfinite-dash') . '</p>';
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

// Page renderer
function nfinite_dash_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="wrap"><h1>' . esc_html__('Nfinite Dash Settings', 'nfinite-dash') . '</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('nfinite_dash_settings');
    do_settings_sections('nfinite_dash_settings');
    submit_button();
    echo '</form></div>';
}
