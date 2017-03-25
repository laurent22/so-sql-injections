#!/bin/bash
set -e

PHP_CMD=/usr/bin/php7.1

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "app:crawl-questions"
$PHP_CMD $SCRIPT_DIR/bin/console -vvv app:crawl-questions >> "$SCRIPT_DIR/crawl-question-3.log" 2>&1
echo "app:find-winners"
$PHP_CMD $SCRIPT_DIR/bin/console -vvv app:find-winners
echo "app:find-users"
$PHP_CMD $SCRIPT_DIR/bin/console -vvv app:find-users

$PHP_CMD $SCRIPT_DIR/bin/console -vvv app:generate-static-website
cp "$SCRIPT_DIR/web/index.html" /var/www/laurent22.github.io/so-injections/
cd /var/www/laurent22.github.io/so-injections/
git add .
git commit -m "script auto-update" || true
git push
cd -