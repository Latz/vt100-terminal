<?php
/**
 * Admin-configurable terminal options, exposed via the Theme Customizer.
 */

/**
 * Clamps the typing-speed setting to a sane ms-per-character range.
 * @param mixed $value - Raw posted value.
 * @return int Sanitized value, clamped to 0-50.
 */
function vt100_sanitize_typing_speed($value): int {
    return min(50, max(0, absint($value)));
}

/**
 * Sanitizes the default font-size setting.
 * @param mixed $value - Raw posted value.
 * @return int Sanitized value, at least 1.
 */
function vt100_sanitize_font(mixed $value): int {
    return max(1, absint($value));
}

/**
 * Sanitizes the default posts-per-page setting.
 * @param mixed $value - Raw posted value.
 * @return int Sanitized value, at least 1.
 */
function vt100_sanitize_posts(mixed $value): int {
    return max(1, absint($value));
}

/**
 * Sanitizes the default terminal color-theme setting.
 * @param mixed $value - Raw posted value.
 * @return string One of a-e, falling back to "a".
 */
function vt100_sanitize_theme(mixed $value): string {
    return in_array($value, ['a', 'b', 'c', 'd', 'e'], true) ? $value : 'a';
}

/**
 * Sanitizes the default post-sort-order setting.
 * @param mixed $value - Raw posted value.
 * @return string "asc" or "desc", falling back to "desc".
 */
function vt100_sanitize_order(mixed $value): string {
    return in_array($value, ['asc', 'desc'], true) ? $value : 'desc';
}

/**
 * Clamps the default phosphor-glow setting to 0.0-1.0.
 * @param mixed $value - Raw posted value.
 * @return float Sanitized value, clamped to 0.0-1.0.
 */
function vt100_sanitize_glow(mixed $value): float {
    return min(1.0, max(0.0, (float) $value));
}

/**
 * Sanitizes the default scroll-behavior setting.
 * @param mixed $value - Raw posted value.
 * @return string "jump" or "smooth", falling back to "jump".
 */
function vt100_sanitize_scroll(mixed $value): string {
    return in_array($value, ['jump', 'smooth'], true) ? $value : 'jump';
}

/**
 * Sanitizes a Customizer checkbox value to a real boolean.
 * @param mixed $value - Raw posted value (Customizer checkboxes post "1" or "").
 * @return bool
 */
function vt100_sanitize_checkbox(mixed $value): bool {
    return rest_sanitize_boolean($value);
}

/**
 * Registers the terminal default-value settings and controls in the
 * Theme Customizer. Settings are bound directly to the `vt100_terminal_*`
 * wp_options rows (`type: 'option'`), so `get_option()` calls elsewhere
 * (inc/enqueue.php) keep working unchanged.
 * @param WP_Customize_Manager $wp_customize
 * @return void
 */
function vt100_customize_register(WP_Customize_Manager $wp_customize): void {
    $wp_customize->add_section('vt100_terminal_section', [
        'title'    => __('Terminal', 'vt100-terminal'),
        'priority' => 30,
    ]);

    $wp_customize->add_section('vt100_defaults_section', [
        'title'       => __('Erstbesucher-Standardwerte', 'vt100-terminal'),
        'description' => __('Diese Werte werden neuen Besuchern (ohne Cookie) beim ersten Laden des Terminals angezeigt.', 'vt100-terminal'),
        'priority'    => 31,
    ]);

    $wp_customize->add_section('vt100_features_section', [
        'title'    => __('Features', 'vt100-terminal'),
        'priority' => 32,
    ]);

    // ── Terminal section ────────────────────────────────────────────
    $wp_customize->add_setting('vt100_terminal_prompt_prefix', [
        'type'              => 'option',
        'default'           => 'user@system',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('vt100_terminal_prompt_prefix', [
        'section'     => 'vt100_terminal_section',
        'label'       => __('Prompt prefix', 'vt100-terminal'),
        'description' => __('Account@host segment shown as the first-visit terminal prompt, e.g. "user@system" (the path/suffix is appended automatically).', 'vt100-terminal'),
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('vt100_terminal_typing_speed', [
        'type'              => 'option',
        'default'           => 1,
        'sanitize_callback' => 'vt100_sanitize_typing_speed',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('vt100_terminal_typing_speed', [
        'section'     => 'vt100_terminal_section',
        'label'       => __('Typing speed (ms/char)', 'vt100-terminal'),
        'description' => __('Baseline delay per typed character, in milliseconds. Higher is slower.', 'vt100-terminal'),
        'type'        => 'number',
        'input_attrs' => ['min' => 0, 'max' => 50],
    ]);

    // ── Defaults section ────────────────────────────────────────────
    $wp_customize->add_setting('vt100_terminal_font', [
        'type'              => 'option',
        'default'           => 22,
        'sanitize_callback' => 'vt100_sanitize_font',
        'transport'         => 'postMessage',
    ]);
    $wp_customize->add_control('vt100_terminal_font', [
        'section'     => 'vt100_defaults_section',
        'label'       => __('Font size (px)', 'vt100-terminal'),
        'description' => __('Starting terminal font size for first-time visitors (px).', 'vt100-terminal'),
        'type'        => 'number',
        'input_attrs' => ['min' => 1],
    ]);

    $wp_customize->add_setting('vt100_terminal_posts', [
        'type'              => 'option',
        'default'           => 10,
        'sanitize_callback' => 'vt100_sanitize_posts',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('vt100_terminal_posts', [
        'section'     => 'vt100_defaults_section',
        'label'       => __('Posts per page', 'vt100-terminal'),
        'description' => __('Number of posts listed per page for first-time visitors.', 'vt100-terminal'),
        'type'        => 'number',
        'input_attrs' => ['min' => 1],
    ]);

    $wp_customize->add_setting('vt100_terminal_theme', [
        'type'              => 'option',
        'default'           => 'a',
        'sanitize_callback' => 'vt100_sanitize_theme',
        'transport'         => 'postMessage',
    ]);
    $wp_customize->add_control('vt100_terminal_theme', [
        'section'     => 'vt100_defaults_section',
        'label'       => __('Color theme', 'vt100-terminal'),
        'description' => __('Starting color theme for first-time visitors.', 'vt100-terminal'),
        'type'        => 'select',
        'choices'     => [
            'a' => __('Green (VT100)', 'vt100-terminal'),
            'b' => __('Dark', 'vt100-terminal'),
            'c' => __('Purple', 'vt100-terminal'),
            'd' => __('Light', 'vt100-terminal'),
            'e' => __('Amber', 'vt100-terminal'),
        ],
    ]);

    $wp_customize->add_setting('vt100_terminal_order', [
        'type'              => 'option',
        'default'           => 'desc',
        'sanitize_callback' => 'vt100_sanitize_order',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('vt100_terminal_order', [
        'section'     => 'vt100_defaults_section',
        'label'       => __('Post order', 'vt100-terminal'),
        'description' => __('Starting post sort order for first-time visitors.', 'vt100-terminal'),
        'type'        => 'select',
        'choices'     => [
            'asc'  => __('Oldest first', 'vt100-terminal'),
            'desc' => __('Newest first', 'vt100-terminal'),
        ],
    ]);

    $wp_customize->add_setting('vt100_terminal_glow', [
        'type'              => 'option',
        'default'           => 0,
        'sanitize_callback' => 'vt100_sanitize_glow',
        'transport'         => 'postMessage',
    ]);
    $wp_customize->add_control('vt100_terminal_glow', [
        'section'     => 'vt100_defaults_section',
        'label'       => __('Phosphor glow', 'vt100-terminal'),
        'description' => __('Starting phosphor-glow intensity for first-time visitors (0 = off, 0.3 = medium, 1.0 = max glow).', 'vt100-terminal'),
        'type'        => 'number',
        'input_attrs' => ['min' => 0, 'max' => 1, 'step' => 0.1],
    ]);

    $wp_customize->add_setting('vt100_terminal_scroll', [
        'type'              => 'option',
        'default'           => 'jump',
        'sanitize_callback' => 'vt100_sanitize_scroll',
        'transport'         => 'postMessage',
    ]);
    $wp_customize->add_control('vt100_terminal_scroll', [
        'section'     => 'vt100_defaults_section',
        'label'       => __('Scroll behavior', 'vt100-terminal'),
        'description' => __('Starting scroll behavior for first-time visitors.', 'vt100-terminal'),
        'type'        => 'select',
        'choices'     => [
            'jump'   => __('jump — Instant', 'vt100-terminal'),
            'smooth' => __('smooth — VT100 smooth scroll', 'vt100-terminal'),
        ],
    ]);

    // ── Features section ────────────────────────────────────────────
    $wp_customize->add_setting('vt100_terminal_glitches', [
        'type'              => 'option',
        'default'           => true,
        'sanitize_callback' => 'vt100_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('vt100_terminal_glitches', [
        'section'     => 'vt100_features_section',
        'label'       => __('Typing glitches', 'vt100-terminal'),
        'description' => __('Randomly injected glitch messages that appear every 90-150s during normal terminal use.', 'vt100-terminal'),
        'type'        => 'checkbox',
    ]);

    $wp_customize->add_setting('vt100_terminal_idle_effects', [
        'type'              => 'option',
        'default'           => true,
        'sanitize_callback' => 'vt100_sanitize_checkbox',
        'transport'         => 'refresh',
    ]);
    $wp_customize->add_control('vt100_terminal_idle_effects', [
        'section'     => 'vt100_features_section',
        'label'       => __('Idle screensaver effects', 'vt100-terminal'),
        'description' => __('Animated screensaver sequences (neon flicker, vortex, grid glitch, etc.) that start after 5 minutes of inactivity.', 'vt100-terminal'),
        'type'        => 'checkbox',
    ]);
}
add_action('customize_register', 'vt100_customize_register');

/**
 * Enqueues the postMessage live-preview script for the customizer preview
 * iframe (font/theme/glow/scroll settings only — the rest use 'refresh').
 * @return void
 */
function vt100_customize_preview_js(): void {
    wp_enqueue_script(
        'vt100-customizer-preview',
        get_theme_file_uri('assets/js/customizer-preview.js'),
        ['customize-preview'],
        VT100_VERSION,
        true
    );
}
add_action('customize_preview_init', 'vt100_customize_preview_js');
