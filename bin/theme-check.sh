#!/usr/bin/env bash
set -euo pipefail

# Copies only the files a production build of this theme actually needs
# (i.e. everything except dev/test tooling) into a throwaway directory,
# runs the WP.org "Theme Check" WP-CLI command against that clean copy,
# writes the results to a Markdown report, and prints the open problems.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
THEME_SLUG="$(basename "$THEME_DIR")"
WP_ROOT="$(cd "$THEME_DIR/../../.." && pwd)"

REPORT_DIR="$SCRIPT_DIR/reports"
REPORT_FILE="$REPORT_DIR/theme-check-report.md"

DIST_SLUG="${THEME_SLUG}-distcheck"
DIST_PARENT="$(mktemp -d)"
DIST_DIR="$DIST_PARENT/$DIST_SLUG"

# register_theme_directory() must run inside a full WP bootstrap (the plain
# function isn't defined yet during --exec/--require, which run before WP
# loads), so a throwaway mu-plugin is the simplest way to register the temp
# theme root before `wp theme-check` looks up the theme.
MU_PLUGINS_DIR="$WP_ROOT/wp-content/mu-plugins"
MU_PLUGIN_FILE="$MU_PLUGINS_DIR/zz-theme-check-register-dist-dir.php"

cleanup() {
  rm -rf "$DIST_PARENT"
  rm -f "$MU_PLUGIN_FILE"
}
trap cleanup EXIT

mkdir -p "$MU_PLUGINS_DIR"
cat > "$MU_PLUGIN_FILE" <<PHP
<?php
// Temporary — written and removed by bin/theme-check.sh.
register_theme_directory('$DIST_PARENT');
// WP caches the scanned theme list; without a forced rescan, wp_get_theme()
// won't see the freshly registered directory until the cache expires.
search_theme_directories(true);
PHP

# Files/directories a production theme package must not ship — dev tooling,
# build config, and the uncompiled JS source (only build/ is loaded at runtime).
EXCLUDES=(
  --exclude='.git/'
  --exclude='.gitignore'
  --exclude='node_modules/'
  --exclude='vendor/'
  --exclude='coverage/'
  --exclude='coverage.xml'
  --exclude='.scannerwork/'
  --exclude='.phpunit.result.cache'
  --exclude='tests/'
  --exclude='bin/'
  --exclude='phpunit.xml'
  --exclude='composer.json'
  --exclude='composer.lock'
  --exclude='package.json'
  --exclude='package-lock.json'
  --exclude='vitest.config.js'
  --exclude='src/'
  --exclude='README.md'
  --exclude='*.png'
)

echo "Copying production files to $DIST_DIR ..."
mkdir -p "$DIST_DIR"
rsync -a "${EXCLUDES[@]}" "$THEME_DIR/" "$DIST_DIR/"

echo "Running wp theme-check against the clean copy ..."
mkdir -p "$REPORT_DIR"

# `wp theme-check run` itself exits 1 whenever it has findings (normal
# linter behavior, not a script failure) — only treat this as a real error
# if it didn't produce parseable JSON on stdout.
STDERR_LOG="$(mktemp)"
RESULTS_JSON=$(wp theme-check run "$DIST_SLUG" \
  --path="$WP_ROOT" \
  --format=json \
  --allow-root 2>"$STDERR_LOG") || true

if ! echo "$RESULTS_JSON" | jq empty 2>/dev/null; then
  echo "wp theme-check run failed:" >&2
  grep -v '^Deprecated:' "$STDERR_LOG" >&2 || true
  rm -f "$STDERR_LOG"
  exit 1
fi
rm -f "$STDERR_LOG"

# The clean copy lives under a "-distcheck" suffixed directory name (to avoid
# colliding with the real theme slug), which Theme Check flags as a directory
# name mismatch. That's an artifact of this script's methodology, not a real
# finding about the theme — drop it.
RESULTS_JSON=$(echo "$RESULTS_JSON" | jq '[.[] | select(.value | contains("wrong directory for the theme name") | not)]')

# --- Write the Markdown report ---------------------------------------------
{
  echo "# Theme Check Report — $THEME_SLUG"
  echo
  echo "_Generated: $(date '+%Y-%m-%d %H:%M')_"
  echo
  echo "Checked a production-only copy of the theme (dev/test tooling excluded — see \`bin/theme-check.sh\` for the exact exclude list)."
  echo

  TOTAL=$(echo "$RESULTS_JSON" | jq 'length')
  REQUIRED_COUNT=$(echo "$RESULTS_JSON" | jq '[.[] | select(.type=="REQUIRED")] | length')
  WARNING_COUNT=$(echo "$RESULTS_JSON" | jq '[.[] | select(.type=="WARNING")] | length')
  RECOMMENDED_COUNT=$(echo "$RESULTS_JSON" | jq '[.[] | select(.type=="RECOMMENDED")] | length')
  INFO_COUNT=$(echo "$RESULTS_JSON" | jq '[.[] | select(.type=="INFO")] | length')

  echo "**$TOTAL** findings — **$REQUIRED_COUNT** required, **$WARNING_COUNT** warning, $RECOMMENDED_COUNT recommended, $INFO_COUNT info."
  echo

  for TYPE in REQUIRED WARNING RECOMMENDED INFO; do
    COUNT=$(echo "$RESULTS_JSON" | jq --arg t "$TYPE" '[.[] | select(.type==$t)] | length')
    if [[ "$COUNT" -gt 0 ]]; then
      echo "## $TYPE ($COUNT)"
      echo
      echo "$RESULTS_JSON" | jq -r --arg t "$TYPE" '.[] | select(.type==$t) | "- " + .value'
      echo
    fi
  done
} > "$REPORT_FILE"

echo "Report saved to $REPORT_FILE"
echo

# --- Show the actual problems (REQUIRED + WARNING) -------------------------
PROBLEM_COUNT=$(echo "$RESULTS_JSON" | jq '[.[] | select(.type=="REQUIRED" or .type=="WARNING")] | length')

if [[ "$PROBLEM_COUNT" -eq 0 ]]; then
  echo "No REQUIRED or WARNING issues found."
  exit 0
fi

echo "== $PROBLEM_COUNT problem(s) (REQUIRED / WARNING) =="
echo "$RESULTS_JSON" | jq -r '.[] | select(.type=="REQUIRED" or .type=="WARNING") | "[\(.type)] \(.value)"'

exit 1
