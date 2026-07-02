#!/usr/bin/env bash
#
# Installs the project's local git hooks.
# Run once after cloning the repository.
#
# Usage: bash scripts/install-hooks.sh

set -e

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
GIT_HOOKS_DIR="$(git -C "${REPO_ROOT}" rev-parse --absolute-git-dir)/hooks"

echo "Installing git hooks in ${GIT_HOOKS_DIR}..."

cat > "${GIT_HOOKS_DIR}/pre-push" <<'EOF'
#!/usr/bin/env bash
exec "$(git rev-parse --show-toplevel)/scripts/pre-push" "$@"
EOF

chmod +x "${GIT_HOOKS_DIR}/pre-push"

echo "Hook pre-push installed."
echo "  - Code sniffer and PHPUnit run before each push."
echo "  - On push to main: version.php is auto-bumped and committed."
