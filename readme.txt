=== VT100 Terminal ===
Contributors: Latz
Tags: custom-colors, translation-ready, one-column
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A classic WordPress theme with a React (wp-element) VT100-style terminal UI for reading posts, pages, and comments.

== Description ==

VT100 Terminal renders the site as an interactive terminal emulator. Visitors type commands (`ls`, `cat`, `grep`, `cd`, `read`, `reply`, and others) to browse posts, pages, categories, tags, and comments, all served through the WordPress core REST API (`/wp/v2`). Content is fetched client-side and rendered as plain text — the theme does not render any raw HTML from post content client-side.

A lightweight visitor identifier is used to attribute comment replies posted through the terminal UI. No account/login is required to browse or comment; comment moderation follows the site's normal WordPress Discussion Settings.

Site-wide first-visit defaults (color theme, font size, phosphor glow, etc.) and feature toggles (typing glitches, idle screensaver effects) are configured under Appearance → Customize.

This is a classic (non-block) theme — it does not use Full Site Editing, block templates, or theme.json.

== Installation ==

1. Upload the theme directory to `/wp-content/themes/`.
2. Activate the theme through the 'Themes' screen in wp-admin.
3. Run `npm install && npm run build` from the theme directory to produce the `build/` assets before activating, if installing from source rather than a packaged release.

== Changelog ==

= 1.0.0 =
* Initial release — classic-theme port of HX29 Terminal.

== Credits ==

Built with `@wordpress/scripts`, `@wordpress/element`, `@wordpress/api-fetch`, `@wordpress/url`, and `react-terminal-ui`.
