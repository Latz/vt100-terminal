/**
 * Live-updates visual terminal defaults (font size, color theme, phosphor
 * glow, scroll behavior) in the Customizer preview iframe via postMessage,
 * mirroring applyConfig() in src/storage.js but bypassing the vt100_config
 * cookie so the preview always reflects the in-progress Customizer value.
 */
(function (wp) {
  if (!wp || !wp.customize) return;

  wp.customize("vt100_terminal_font", function (value) {
    value.bind(function (newval) {
      document.documentElement.style.setProperty("--fsize", newval + "px");
    });
  });

  wp.customize("vt100_terminal_glow", function (value) {
    value.bind(function (newval) {
      document.documentElement.style.setProperty("--glow", newval);
    });
  });

  wp.customize("vt100_terminal_theme", function (value) {
    value.bind(function (newval) {
      if (newval && newval !== "a") {
        document.documentElement.dataset.theme = newval;
      } else {
        delete document.documentElement.dataset.theme;
      }
    });
  });

  wp.customize("vt100_terminal_scroll", function (value) {
    value.bind(function (newval) {
      if (newval === "smooth") {
        document.documentElement.dataset.scroll = "smooth";
      } else {
        delete document.documentElement.dataset.scroll;
      }
    });
  });
})(window.wp);
