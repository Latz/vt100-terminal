<?php
// No-JS fallback content, rendered inside <noscript> below #vt100-root.
get_header();
?>
<noscript>
<div id="terminal-container" class="vt100-fallback-container">
  <div id="terminal-output">
    <div><?php esc_html_e('VT100 Terminal — JavaScript required for full functionality.', 'vt100-terminal'); ?></div>
    <div>&nbsp;</div>
    <div><?php esc_html_e('Recent posts:', 'vt100-terminal'); ?></div>
    <?php
    $posts = get_posts(['posts_per_page' => 5, 'post_status' => 'publish']);
    foreach ($posts as $i => $post) {
        echo '<div>' . ($i + 1) . '. ' . esc_html($post->post_title) . '</div>';
    }
    ?>
  </div>
</div>
</noscript>
<?php get_footer(); ?>
