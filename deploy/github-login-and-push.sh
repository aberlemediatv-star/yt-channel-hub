#!/usr/bin/env bash
# Einmalig ausfuehren: GitHub CLI anmelden (Browser) und main pushen.
set -euo pipefail
export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
if ! command -v gh >/dev/null 2>&1; then
  echo "Bitte zuerst: brew install gh"
  exit 1
fi
# GitHub-Login-URL in Safari oeffnen (siehe gh help environment → GH_BROWSER).
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
chmod +x "$SCRIPT_DIR/safari-browser.sh" 2>/dev/null || true
export GH_BROWSER="$SCRIPT_DIR/safari-browser.sh"
echo ">>> GitHub anmelden (Safari oeffnet sich; z. B. aberlemediatv@gmail.com)"
gh auth login --hostname github.com --git-protocol https --web
echo ">>> Git fuer HTTPS mit gh verknuepfen"
gh auth setup-git
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT"
echo ">>> Push nach origin main"
git push -u origin main
echo "Fertig."
