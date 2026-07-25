#!/usr/bin/env bash
# Creates a deployable tar.gz of the Laravel app for shared hosting.
# Usage: ./scripts/deploy-package.sh [output-file]
# Defaults to deploy-YYYY-MM-DD.tar.gz in the project root.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
OUTPUT="${1:-"$PROJECT_ROOT/deploy-$(date +%Y-%m-%d).tar.gz"}"

echo "Packaging from: $PROJECT_ROOT"
echo "Output:         $OUTPUT"

COPYFILE_DISABLE=1 tar --no-xattrs -czf "$OUTPUT" \
    -C "$PROJECT_ROOT" \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='docs' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='database/database.sqlite' \
    --exclude='tests' \
    --exclude='CLAUDE.md' \
    --exclude='phpunit.xml' \
    --exclude='deploy-*.tar.gz' \
    --exclude='.idea' \
    --exclude='**/._*' \
    --exclude='._*' \
    --exclude='**/.DS_Store' \
    --exclude='.DS_Store' \
    .

echo "Done: $OUTPUT ($(du -sh "$OUTPUT" | cut -f1))"
