#!/usr/bin/env bash
# Starts the app on http://127.0.0.1:8010 and opens it in the browser (see docs/LOCAL-SETUP.md).
# Keep the terminal open while you work; Ctrl+C stops the app.
set -euo pipefail
cd "$(dirname "$0")/.."

PORT="${BARQ_PORT:-8010}"
URL="http://127.0.0.1:${PORT}"

open_browser() {
  if command -v open >/dev/null 2>&1; then open "$1"
  elif command -v xdg-open >/dev/null 2>&1; then xdg-open "$1" >/dev/null 2>&1 || true
  else echo "Open $1 in your browser."
  fi
}

if curl -fsS -o /dev/null --max-time 2 "$URL/up" 2>/dev/null; then
  echo "Already running at $URL"
  open_browser "$URL"
  exit 0
fi

[ -f .env ] || { echo "Not set up yet - run ./local/setup.sh first."; exit 1; }

if command -v ollama >/dev/null 2>&1 \
  && ! curl -fsS -o /dev/null --max-time 2 http://127.0.0.1:11434/api/tags 2>/dev/null; then
  echo "Starting Ollama in the background..."
  nohup ollama serve >/dev/null 2>&1 &
fi

# Opens the browser as soon as the server answers, while the server itself runs in the
# foreground below (so Ctrl+C stops it cleanly, together with all its worker processes).
(
  for _ in $(seq 1 60); do
    if curl -fsS -o /dev/null --max-time 1 "$URL/up" 2>/dev/null; then open_browser "$URL"; exit 0; fi
    sleep 0.5
  done
) &

# PHP's own web server (not `artisan serve`, which can't pass -d settings): the app accepts
# images up to 8 MB (PHP's default limit is 2 MB) and AI replies can take over a minute.
# Several workers: a slow AI request doesn't freeze the other pages.
echo "Running on $URL - keep this terminal open while you work (Ctrl+C stops the app)."
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
exec php -d upload_max_filesize=10M -d post_max_size=64M -d max_execution_time=0 -d memory_limit=512M \
  -S "127.0.0.1:${PORT}" -t public local/server.php
