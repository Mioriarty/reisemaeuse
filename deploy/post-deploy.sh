#!/usr/bin/env bash
# Wird nach einem Deploy ausgeführt - entweder von netcups
# "Zusätzliche Bereitstellungsaktionen" oder vom Cronjob, der jede Minute
# prüft, ob ein neuer Commit ausgecheckt wurde.
set -euo pipefail

cd "$(dirname "$0")/.."

PHP="${PHP_BIN:-php}"

"$PHP" artisan wandermaeuse:post-deploy

echo "Deploy abgeschlossen."
