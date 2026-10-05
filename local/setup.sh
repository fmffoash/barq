#!/usr/bin/env bash
# One-time setup on macOS / Linux (see docs/LOCAL-SETUP.md). Safe to run again at any time:
# every step skips itself if it was already done.
set -euo pipefail
cd "$(dirname "$0")/.."

say()  { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31mERROR: %s\033[0m\n' "$1" >&2; exit 1; }
need() { command -v "$1" >/dev/null 2>&1 || fail "'$1' was not found. $2"; }

say "Checking requirements"
need php "Install PHP 8.3+ (macOS: Laravel Herd - https://herd.laravel.com)"
need composer "Install Composer (included in Laravel Herd - https://getcomposer.org)"
need node "Install Node.js LTS - https://nodejs.org"
need npm "Install Node.js LTS - https://nodejs.org"
php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' \
  || fail "PHP 8.3 or newer is required (found $(php -r 'echo PHP_VERSION;'))."

if [ ! -f .env ]; then
  say "Creating .env from .env.example"
  cp .env.example .env
fi

say "Installing PHP packages (composer install)"
composer install --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate
fi

say "Installing and building the interface (npm ci + npm run build)"
npm ci
npm run build

say "Preparing database, template library and admin account"
setup_status=0
php artisan barq:local-setup || setup_status=$?

model="$(grep -E '^OLLAMA_MODEL=' .env | tail -n1 | cut -d= -f2- | tr -d "\"' \r")"
model="${model:-qwen3:8b}"
say "AI model ($model)"
if ! command -v ollama >/dev/null 2>&1; then
  echo "Ollama is not installed - the app works without it, only the AI features stay off."
  echo "Install it from https://ollama.com, then run:  ollama pull $model"
elif ollama list 2>/dev/null | awk 'NR > 1 { print $1 }' | grep -qx -e "$model" -e "$model:latest"; then
  echo "Already downloaded."
else
  echo "Downloading (about 5 GB for qwen3:8b - one time only)..."
  ollama pull "$model" || echo "Download failed - open the Ollama app, then run:  ollama pull $model"
fi

say "Done"
echo "Start the app any time with:  ./local/start.sh   (it opens http://127.0.0.1:8010)"
exit "$setup_status"
