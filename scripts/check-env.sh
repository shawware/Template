#!/usr/bin/env bash
# Validates a .env file using the same phpdotenv parser Laravel uses.
# Usage: ./scripts/check-env.sh [path-to-.env]
# Defaults to .env in the project root.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
ENV_FILE="${1:-"$PROJECT_ROOT/.env"}"
ENV_DIR="$(dirname "$ENV_FILE")"
ENV_NAME="$(basename "$ENV_FILE")"

PHP=$(command -v /usr/local/php85/bin/php 2>/dev/null || command -v php)

$PHP -r "
require '$PROJECT_ROOT/vendor/autoload.php';
try {
    \Dotenv\Dotenv::create(\Dotenv\Repository\RepositoryBuilder::createWithNoAdapters()->make(), '$ENV_DIR', '$ENV_NAME')->load();
    echo \"OK: $ENV_FILE\n\";
} catch (\Throwable \$e) {
    echo 'INVALID: ' . \$e->getMessage() . \"\n\";
    exit(1);
}
"
