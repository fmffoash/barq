#!/usr/bin/env bash
# Gets the latest code from GitHub and rebuilds everything - the local version of the server
# deploy (see docs/LOCAL-SETUP.md). Your data (database + uploaded images) is not touched.
set -euo pipefail
cd "$(dirname "$0")/.."

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

say "Getting the latest code (git pull)"
git pull --ff-only

say "PHP packages"
composer install --no-interaction

say "Interface (npm ci + npm run build)"
npm ci
npm run build

say "Database and caches"
php artisan optimize:clear
php artisan migrate --force
# تحديثات مكتبة القوالب (نصوص/ألوان/خطوط جديدة) — آمنة على المشاريع الموجودة، وبتتخطّى لو المكتبة على آخر نسخة.
php artisan barq:seed-template-library --if-outdated

php artisan barq:doctor || true

say "Updated"
echo "If the app is already running, stop it (Ctrl+C) and start it again with ./local/start.sh"
