#!/bin/bash
set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

while true; do
	echo "app:crawl-questions"
	php bin/console -vvv app:crawl-questions >> "$SCRIPT_DIR/crawl-question-3.log" 2>&1
	echo "app:find-winners"
	php bin/console -vvv app:find-winners
	echo "app:find-users"
	php bin/console -vvv app:find-users

	php bin/console -vvv app:generate-static-website
	cp "$SCRIPT_DIR/web/index.html" /var/www/laurent22.github.io/so-injections/
	cd /var/www/laurent22.github.io/so-injections/
	git add .
	git commit -m "script auto-update" || true
	git push
	cd $SCRIPT_DIR
	
	sleep 30m
done
