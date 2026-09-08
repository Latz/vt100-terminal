<?php
/**
 * Tests for inc/customizer.php: sanitize callbacks and Customizer registration.
 */
class CustomizerTest extends WP_UnitTestCase {

    // ── Sanitize callbacks ──────────────────────────────────────────

    public function test_sanitize_typing_speed_clamps_to_0_50() {
        // absint() takes the magnitude first, so a negative input lands on
        // its positive equivalent rather than clamping to the 0 floor.
        $this->assertSame(5, vt100_sanitize_typing_speed(-5));
        $this->assertSame(50, vt100_sanitize_typing_speed(999));
        $this->assertSame(25, vt100_sanitize_typing_speed('25'));
    }

    public function test_sanitize_font_has_minimum_of_1() {
        $this->assertSame(1, vt100_sanitize_font(0));
        // Same absint() magnitude behavior as typing_speed above.
        $this->assertSame(10, vt100_sanitize_font(-10));
        $this->assertSame(22, vt100_sanitize_font('22'));
    }

    public function test_sanitize_posts_has_minimum_of_1() {
        $this->assertSame(1, vt100_sanitize_posts(0));
        $this->assertSame(10, vt100_sanitize_posts('10'));
    }

    public function test_sanitize_theme_falls_back_to_a_for_unknown_value() {
        $this->assertSame('b', vt100_sanitize_theme('b'));
        $this->assertSame('a', vt100_sanitize_theme('nonexistent'));
        $this->assertSame('a', vt100_sanitize_theme(null));
    }

    public function test_sanitize_order_falls_back_to_desc_for_unknown_value() {
        $this->assertSame('asc', vt100_sanitize_order('asc'));
        $this->assertSame('desc', vt100_sanitize_order('sideways'));
    }

    public function test_sanitize_glow_clamps_to_0_1() {
        $this->assertSame(0.0, vt100_sanitize_glow(-0.5));
        $this->assertSame(1.0, vt100_sanitize_glow(5));
        $this->assertSame(0.3, vt100_sanitize_glow('0.3'));
    }

    public function test_sanitize_scroll_falls_back_to_jump_for_unknown_value() {
        $this->assertSame('smooth', vt100_sanitize_scroll('smooth'));
        $this->assertSame('jump', vt100_sanitize_scroll('teleport'));
    }

    public function test_sanitize_checkbox_converts_customizer_values_to_bool() {
        $this->assertTrue(vt100_sanitize_checkbox('1'));
        $this->assertTrue(vt100_sanitize_checkbox(true));
        $this->assertFalse(vt100_sanitize_checkbox(''));
        $this->assertFalse(vt100_sanitize_checkbox(false));
        $this->assertFalse(vt100_sanitize_checkbox('0'));
    }

    // ── Customizer registration ─────────────────────────────────────

    /**
     * Builds a fresh WP_Customize_Manager and runs the theme's registration
     * callback against it, the same way WordPress does on 'customize_register'.
     * @return WP_Customize_Manager
     */
    private function register(): WP_Customize_Manager {
        if (!class_exists('WP_Customize_Manager')) {
            require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
        }
        $wp_customize = new WP_Customize_Manager();
        vt100_customize_register($wp_customize);
        return $wp_customize;
    }

    public function test_registers_all_three_sections() {
        $wp_customize = $this->register();

        $this->assertNotNull($wp_customize->get_section('vt100_terminal_section'));
        $this->assertNotNull($wp_customize->get_section('vt100_defaults_section'));
        $this->assertNotNull($wp_customize->get_section('vt100_features_section'));
    }

    public function test_registers_all_ten_settings_bound_to_options() {
        $wp_customize = $this->register();

        $names = [
            'vt100_terminal_prompt_prefix',
            'vt100_terminal_typing_speed',
            'vt100_terminal_font',
            'vt100_terminal_posts',
            'vt100_terminal_theme',
            'vt100_terminal_order',
            'vt100_terminal_glow',
            'vt100_terminal_scroll',
            'vt100_terminal_glitches',
            'vt100_terminal_idle_effects',
        ];

        foreach ($names as $name) {
            $setting = $wp_customize->get_setting($name);
            $this->assertNotNull($setting, "Setting {$name} was not registered");
            $this->assertSame('option', $setting->type, "Setting {$name} should be bound to type=option");

            $control = $wp_customize->get_control($name);
            $this->assertNotNull($control, "Control {$name} was not registered");
        }
    }

    public function test_feature_toggles_default_to_enabled_in_features_section() {
        $wp_customize = $this->register();

        foreach (['vt100_terminal_glitches', 'vt100_terminal_idle_effects'] as $name) {
            $setting = $wp_customize->get_setting($name);
            $this->assertTrue($setting->default);

            $control = $wp_customize->get_control($name);
            $this->assertSame('vt100_features_section', $control->section);
            $this->assertSame('checkbox', $control->type);
        }
    }

    /**
     * type=option settings must write straight to the matching wp_options
     * row, so inc/enqueue.php's plain get_option() calls keep working.
     */
    public function test_option_bound_setting_persists_to_get_option() {
        // save() checks the current user's 'edit_theme_options' capability
        // (WP_Customize_Setting::check_capabilities()), so it needs an admin.
        $admin = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);

        $wp_customize = $this->register();
        $setting = $wp_customize->get_setting('vt100_terminal_font');

        // save() only writes the manager's posted value, so simulate a
        // Customizer POST via set_post_value() before saving.
        $wp_customize->set_post_value('vt100_terminal_font', '30');
        $setting->save();

        $this->assertSame(30, (int) get_option('vt100_terminal_font'));
    }

    public function test_theme_setting_uses_sanitize_theme_callback() {
        $wp_customize = $this->register();
        $setting = $wp_customize->get_setting('vt100_terminal_theme');

        $this->assertSame('c', $setting->sanitize('c'));
        $this->assertSame('a', $setting->sanitize('not-a-real-theme'));
    }

    public function test_customize_preview_js_enqueues_preview_script() {
        do_action('customize_preview_init');

        $this->assertTrue(wp_script_is('vt100-customizer-preview', 'enqueued'));
    }
}
