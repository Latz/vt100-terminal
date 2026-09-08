<?php
/**
 * Tests for functions.php: bootstrap wiring and the after_setup_theme /
 * switch_theme lifecycle hooks (visitor-counter seed/cleanup).
 */
class FunctionsTest extends WP_UnitTestCase {

    public function tear_down() {
        delete_option('vt100_user_counter');
        delete_option('vt100_terminal_glitches');
        delete_option('vt100_terminal_idle_effects');
        parent::tear_down();
    }

    /**
     * The four vt100_safe_require_once() calls at the bottom of
     * functions.php must have actually pulled in inc/setup.php,
     * inc/uid.php, inc/enqueue.php and inc/customizer.php.
     */
    public function test_all_theme_includes_were_loaded() {
        $this->assertTrue(function_exists('vt100_setup'), 'inc/setup.php was not loaded');
        $this->assertTrue(function_exists('vt100_get_or_create_uid'), 'inc/uid.php was not loaded');
        $this->assertTrue(function_exists('vt100_enqueue_scripts'), 'inc/enqueue.php was not loaded');
        $this->assertTrue(function_exists('vt100_customize_register'), 'inc/customizer.php was not loaded');
    }

    public function test_version_constant_is_defined() {
        $this->assertTrue(defined('VT100_VERSION'));
        $this->assertIsString(VT100_VERSION);
    }

    public function test_after_setup_theme_seeds_counter_when_missing() {
        delete_option('vt100_user_counter');
        // Re-firing after_setup_theme also re-invokes vt100_setup(), which
        // triggers core's known "register title-tag before wp_loaded" notice
        // (wp_loaded has already fired once test execution reaches here).
        $this->setExpectedIncorrectUsage("add_theme_support( 'title-tag' )");

        do_action('after_setup_theme');

        $counter = get_option('vt100_user_counter');
        $this->assertNotFalse($counter);
        $this->assertGreaterThanOrEqual(0x100, $counter);
        $this->assertLessThanOrEqual(0xfff, $counter);
    }

    public function test_after_setup_theme_does_not_overwrite_existing_counter() {
        update_option('vt100_user_counter', 0x200, false);
        $this->setExpectedIncorrectUsage("add_theme_support( 'title-tag' )");

        do_action('after_setup_theme');

        $this->assertSame(0x200, get_option('vt100_user_counter'));
    }

    public function test_switch_theme_removes_counter_option() {
        update_option('vt100_user_counter', 0x321, false);

        do_action('switch_theme');

        $this->assertFalse(get_option('vt100_user_counter'));
    }

    /**
     * Regression test: update_option($opt, false) silently no-ops when the
     * option row doesn't exist yet, because get_option()'s own "missing"
     * fallback is also false, so WP core sees "no change" and skips the
     * write. after_setup_theme must pre-seed both feature toggles with
     * add_option() so a first-ever "turn this off" in the Customizer
     * actually persists instead of silently reverting to the default.
     */
    public function test_after_setup_theme_seeds_feature_toggles_so_disabling_them_persists() {
        delete_option('vt100_terminal_glitches');
        delete_option('vt100_terminal_idle_effects');
        $this->setExpectedIncorrectUsage("add_theme_support( 'title-tag' )");

        do_action('after_setup_theme');

        $this->assertNotFalse(get_option('vt100_terminal_glitches'), 'Option row was never created');
        $this->assertNotFalse(get_option('vt100_terminal_idle_effects'), 'Option row was never created');

        // With the row seeded, turning a toggle off must now actually stick.
        update_option('vt100_terminal_glitches', false);
        $this->assertFalse(get_option('vt100_terminal_glitches', true));
    }
}
