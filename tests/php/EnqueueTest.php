<?php
/**
 * Robustness tests for inc/enqueue.php (robustness.md High #3, #4).
 */
class EnqueueTest extends WP_UnitTestCase {

    public function tear_down() {
        unset($_COOKIE['vt100_uid']);
        foreach (['vt100_terminal_prompt_prefix', 'vt100_terminal_typing_speed', 'vt100_terminal_font', 'vt100_terminal_posts', 'vt100_terminal_theme', 'vt100_terminal_order', 'vt100_terminal_glow', 'vt100_terminal_scroll', 'vt100_terminal_glitches', 'vt100_terminal_idle_effects'] as $option) {
            delete_option($option);
        }
        wp_dequeue_style('vt100-style');
        wp_deregister_style('vt100-style');
        wp_dequeue_script('vt100-terminal');
        wp_deregister_script('vt100-terminal');
        parent::tear_down();
    }

    /**
     * Extracts and JSON-decodes the wp_localize_script() payload attached
     * to the 'vt100-terminal' script handle.
     * @return array
     */
    private function localized_data(): array {
        $inline = wp_scripts()->get_data('vt100-terminal', 'data');
        $this->assertIsString($inline, 'Expected localized inline data on vt100-terminal');
        preg_match('/var vt100 = (\{.*\});/s', $inline, $matches);
        $this->assertNotEmpty($matches, 'Could not find "var vt100 = {...};" in localized data');
        return json_decode($matches[1], true);
    }

    public function test_enqueue_scripts_enqueues_style_and_script() {
        vt100_enqueue_scripts();

        $this->assertTrue(wp_style_is('vt100-style', 'enqueued'));
        $this->assertTrue(wp_script_is('vt100-terminal', 'enqueued'));
    }

    public function test_enqueue_scripts_localizes_current_option_values() {
        update_option('vt100_terminal_prompt_prefix', 'root@vt100');
        update_option('vt100_terminal_typing_speed', 5);
        update_option('vt100_terminal_font', 30);
        update_option('vt100_terminal_posts', 15);
        update_option('vt100_terminal_theme', 'e');
        update_option('vt100_terminal_order', 'asc');
        update_option('vt100_terminal_glow', 0.5);
        update_option('vt100_terminal_scroll', 'smooth');
        update_option('vt100_terminal_glitches', false);
        update_option('vt100_terminal_idle_effects', false);

        vt100_enqueue_scripts();

        $localized = $this->localized_data();

        // wp_localize_script() stringifies every scalar leaf value before
        // handing it to JS, so numeric options come back as numeric strings
        // and booleans come back as "1" (true) or "" (false).
        $this->assertSame('root@vt100', $localized['prompt_prefix']);
        $this->assertSame('5', $localized['typing_speed']);
        $this->assertSame('30', $localized['font']);
        $this->assertSame('15', $localized['posts']);
        $this->assertSame('e', $localized['theme']);
        $this->assertSame('asc', $localized['order']);
        $this->assertSame('0.5', $localized['glow']);
        $this->assertSame('smooth', $localized['scroll']);
        $this->assertSame('', $localized['glitches_enabled']);
        $this->assertSame('', $localized['idle_effects_enabled']);
    }

    public function test_enqueue_scripts_falls_back_to_defaults_when_no_options_set() {
        vt100_enqueue_scripts();

        $localized = $this->localized_data();

        $this->assertSame('user@system', $localized['prompt_prefix']);
        $this->assertSame('1', $localized['typing_speed']);
        $this->assertSame('22', $localized['font']);
        $this->assertSame('10', $localized['posts']);
        $this->assertSame('a', $localized['theme']);
        $this->assertSame('desc', $localized['order']);
        $this->assertSame('jump', $localized['scroll']);
        $this->assertSame('1', $localized['glitches_enabled']);
        $this->assertSame('1', $localized['idle_effects_enabled']);
    }

    public function test_enqueue_scripts_marks_uid_new_when_no_cookie_present() {
        unset($_COOKIE['vt100_uid']);

        vt100_enqueue_scripts();

        $this->assertSame('1', $this->localized_data()['uid_new']);
    }

    public function test_enqueue_scripts_marks_uid_returning_when_cookie_present() {
        $_COOKIE['vt100_uid'] = 'abc123';

        vt100_enqueue_scripts();

        $this->assertSame('0', $this->localized_data()['uid_new']);
    }

    /**
     * #3: filemtime() on a missing file must not produce a warning/`false`
     * version — a fallback version string is used instead.
     */
    public function test_asset_version_falls_back_when_file_missing() {
        $missing_path = get_stylesheet_directory() . '/does-not-exist-' . uniqid() . '.css';

        $version = vt100_get_asset_version($missing_path, VT100_VERSION);

        $this->assertSame(VT100_VERSION, $version);
    }

    public function test_asset_version_uses_filemtime_when_file_exists() {
        $version = vt100_get_asset_version(get_stylesheet_directory() . '/style.css', 'fallback');

        $this->assertIsInt($version);
        $this->assertGreaterThan(0, $version);
    }

    /**
     * #4: a missing build/index.asset.php must not crash the enqueue path —
     * safe defaults are used instead of "undefined array key" warnings.
     */
    public function test_build_asset_defaults_when_manifest_missing() {
        $missing_path = get_theme_file_path('build/does-not-exist-' . uniqid() . '.asset.php');

        $asset = vt100_get_build_asset($missing_path);

        $this->assertSame([], $asset['dependencies']);
        $this->assertSame(VT100_VERSION, $asset['version']);
    }

    /**
     * #4: a malformed manifest (missing the "dependencies" key entirely, or
     * holding a non-array value) must still yield a safe, well-typed result.
     */
    public function test_build_asset_defaults_when_manifest_malformed() {
        $malformed_path = get_temp_dir() . 'vt100-malformed-asset-' . uniqid() . '.php';
        file_put_contents($malformed_path, "<?php return ['version' => '1.2.3'];\n");

        try {
            $asset = vt100_get_build_asset($malformed_path);

            $this->assertSame([], $asset['dependencies']);
            $this->assertSame('1.2.3', $asset['version']);
        } finally {
            unlink($malformed_path);
        }
    }
}
