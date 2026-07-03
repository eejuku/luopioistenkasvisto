#!/usr/bin/env bash
#
# Run the Playwright e2e suite against a small WordPress/PHP version matrix to
# confirm the plugin works on both ends of its declared support window:
#
#   - declared minimum : WP 6.0  / PHP 7.4  (readme.txt "Requires at least" / "Requires PHP")
#   - latest           : WP latest / PHP 8.3
#
# If it passes on the floor and on latest, everything in between is covered, so
# there is no need to bump the declared minimums.
#
# Each row destroys and recreates wp-env so the seeded page tree starts clean and
# the requested core/PHP versions actually take effect. This is slow but correct.
#
# Cores are pinned via **zip URLs** rather than git refs (WordPress/WordPress#x.y):
# git-ref cores all share one cached clone and a `git checkout` to the next row's
# version aborts on the dirty working tree left by the previous row. Zip cores are
# downloaded + extracted fresh, so switching versions can't collide.
#
# WP_DEBUG_DISPLAY is forced off for the run (errors still go to the debug log via
# WP_DEBUG_LOG). Older WP cores can emit PHP deprecations on newer PHP, and any
# on-screen output during login breaks the auth cookie ("Cookies are blocked due to
# unexpected output") — a core/runtime artifact, nothing to do with this plugin.
# Applied via a temporary .wp-env.override.json that is restored/removed on exit.
#
# Usage:  npm run test:e2e:matrix     (or)   bash scripts/test-matrix.sh
# Requires: Docker running, dependencies installed (npm ci), Playwright browsers
# installed (npx playwright install chromium).

set -uo pipefail
cd "$(dirname "$0")/.." || exit 1

OVERRIDE=".wp-env.override.json"
OVERRIDE_BAK=".wp-env.override.json.matrix-bak"

cleanup() {
	npx @wordpress/env destroy --force >/dev/null 2>&1 || true
	rm -f "$OVERRIDE"
	# Restore a pre-existing override file if we shadowed one.
	[ -f "$OVERRIDE_BAK" ] && mv "$OVERRIDE_BAK" "$OVERRIDE"
}
trap cleanup EXIT

# Preserve any existing override, then write ours (turn on-screen debug off).
[ -f "$OVERRIDE" ] && mv "$OVERRIDE" "$OVERRIDE_BAK"
printf '{\n\t"config": {\n\t\t"WP_DEBUG_DISPLAY": false\n\t}\n}\n' >"$OVERRIDE"

# Rows: "WP_CORE|PHP_VERSION|LABEL"  -- WP_CORE is a zip URL wp-env can download.
ROWS=(
	"https://wordpress.org/wordpress-6.0.zip|7.4|WP 6.0 / PHP 7.4 (declared minimum)"
	"https://wordpress.org/latest.zip|8.3|WP latest / PHP 8.3"
)

declare -a RESULTS=()
overall=0

for row in "${ROWS[@]}"; do
	IFS='|' read -r core php label <<<"$row"

	echo
	echo "============================================================"
	echo "  $label"
	echo "============================================================"

	export WP_ENV_CORE="$core"
	export WP_ENV_PHP_VERSION="$php"

	# Clean slate so the new core/PHP install and a fresh DB are used.
	npx @wordpress/env destroy --force >/dev/null 2>&1 || true

	if ! npx @wordpress/env start; then
		echo "FAILED to start wp-env for: $label"
		RESULTS+=("FAIL (env)  $label")
		overall=1
		continue
	fi

	if npx playwright test; then
		RESULTS+=("PASS        $label")
	else
		RESULTS+=("FAIL (test) $label")
		overall=1
	fi
done

echo
echo "============================================================"
echo "  Matrix summary"
echo "============================================================"
for r in "${RESULTS[@]}"; do
	echo "  $r"
done

exit "$overall"
