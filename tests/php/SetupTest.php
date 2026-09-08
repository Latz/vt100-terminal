<?php
/**
 * Robustness tests for inc/setup.php (robustness.md High #5).
 */
class SetupTest extends WP_UnitTestCase {

    public function test_setup_registers_expected_theme_supports() {
        // wp_loaded has already fired by the time tests run, so re-calling
        // vt100_setup() here re-triggers core's known "register title-tag
        // support before wp_loaded" notice — expected in this test context.
        $this->setExpectedIncorrectUsage("add_theme_support( 'title-tag' )");

        vt100_setup();

        $this->assertTrue(current_theme_supports('post-thumbnails'));
        $this->assertTrue(current_theme_supports('responsive-embeds'));
        $this->assertTrue(current_theme_supports('html5'));
    }

    public function test_setup_loads_theme_textdomain() {
        // load_theme_textdomain() in modern WP only registers the domain's
        // path for just-in-time loading; the .mo file isn't actually read
        // until the first translated string is requested. Only
        // languages/vt100-terminal-de_DE.mo ships with the theme.
        switch_to_locale('de_DE');
        $this->setExpectedIncorrectUsage("add_theme_support( 'title-tag' )");

        vt100_setup();
        $translated = __('Recent posts:', 'vt100-terminal');

        $this->assertSame('Neueste Beiträge:', $translated);
        $this->assertTrue(is_textdomain_loaded('vt100-terminal'));

        restore_previous_locale();
    }

    /**
     * The wp_head hook (registered in inc/setup.php) must echo both the
     * REST-origin preconnect tag and the font preload tag when the font
     * file exists (which it does in this repo's assets/fonts/ directory).
     */
    public function test_wp_head_outputs_preconnect_and_font_preload() {
        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();

        $this->assertStringContainsString('rel="preconnect"', $output);
        $this->assertStringContainsString('rel="preload"', $output);
        $this->assertStringContainsString('glasstty.woff2', $output);
    }

    /**
     * #5: a missing font file must not produce a preload tag pointing at a
     * 404 — the tag is simply omitted.
     */
    public function test_font_preload_tag_empty_when_file_missing() {
        $missing_path = get_theme_file_path('assets/fonts/does-not-exist-' . uniqid() . '.woff2');

        $tag = vt100_font_preload_tag($missing_path, 'https://example.test/font.woff2');

        $this->assertSame('', $tag);
    }

    public function test_font_preload_tag_present_when_file_exists() {
        $real_path = get_theme_file_path('assets/fonts/glasstty.woff2');

        $tag = vt100_font_preload_tag($real_path, 'https://example.test/font.woff2');

        $this->assertStringContainsString('rel="preload"', $tag);
        $this->assertStringContainsString('https://example.test/font.woff2', $tag);
    }
}
