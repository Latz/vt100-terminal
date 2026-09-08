<?php
/**
 * VT100 Terminal Theme Functions (React / wp-element)
 */

define('VT100_VERSION', '1.0.0');

/**
 * require_once's a theme include file if it exists, degrading gracefully
 * (logged, not fatal) instead of taking the whole site down when a
 * missing/renamed include would otherwise trigger a hard fatal error.
 * @param string $path Absolute path to the PHP file to include.
 * @return bool True if the file was found and included.
 */
function vt100_safe_require_once(string $path): bool {
    if (!file_exists($path)) {
        error_log("vt100: missing required include {$path}");
        return false;
    }
    require_once $path;
    return true;
}

vt100_safe_require_once(get_template_directory() . '/inc/setup.php');
vt100_safe_require_once(get_template_directory() . '/inc/uid.php');
vt100_safe_require_once(get_template_directory() . '/inc/enqueue.php');
vt100_safe_require_once(get_template_directory() . '/inc/customizer.php');

// Seed the visitor counter with a random 3-digit hex value on first run.
add_action('after_setup_theme', function () {
    if (false === get_option('vt100_user_counter')) {
        update_option('vt100_user_counter', random_int(0x100, 0xfff), false);
    }

    // add_option() only inserts when the row doesn't exist yet. Without this,
    // the first time a feature toggle is switched OFF, update_option() would
    // compare the new value (false) against get_option()'s own "missing"
    // fallback (also false) and silently skip the write — the row would
    // never actually get created, so the toggle could never persist as off.
    add_option('vt100_terminal_glitches', true);
    add_option('vt100_terminal_idle_effects', true);
});

// Remove theme-scoped state when switching away from this theme.
add_action('switch_theme', function () {
    delete_option('vt100_user_counter');
});
