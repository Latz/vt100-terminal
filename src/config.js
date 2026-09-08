import apiFetch from "@wordpress/api-fetch";

const VT100 = typeof window !== "undefined" && window.vt100 ? window.vt100 : {};

export const WP_API = (VT100.rest_root || "/wp-json/").replace(/\/$/, "") + "/wp/v2";
export const NONCE = VT100.nonce || "";
export const SITE_NAME = VT100.site_name || "my-terminal";
export const UID = VT100.uid || "guest";
export const PROMPT_PREFIX = VT100.prompt_prefix || "user@system";
export const TYPING_SPEED = Number(VT100.typing_speed) || 1;

// wp_localize_script() stringifies booleans ("1" / "") — absent keys (e.g.
// in tests that stub window.vt100 without them) default to enabled.
export const GLITCHES_ENABLED = VT100.glitches_enabled !== "";
export const IDLE_EFFECTS_ENABLED = VT100.idle_effects_enabled !== "";

export const CONFIG_DEFAULTS = Object.freeze({
  font: Number(VT100.font) || 22,
  posts: Number(VT100.posts) || 10,
  theme: ["a", "b", "c", "d", "e"].includes(VT100.theme) ? VT100.theme : "a",
  order: ["asc", "desc"].includes(VT100.order) ? VT100.order : "desc",
  glow: Number.isFinite(Number(VT100.glow)) ? Math.min(1, Math.max(0, Number(VT100.glow))) : 0,
  scroll: ["jump", "smooth"].includes(VT100.scroll) ? VT100.scroll : "jump",
});

if (VT100.rest_root) apiFetch.use(apiFetch.createRootURLMiddleware(VT100.rest_root));
if (NONCE) apiFetch.use(apiFetch.createNonceMiddleware(NONCE));
