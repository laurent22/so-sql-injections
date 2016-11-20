#!/bin/bash
set -e

while true; do
	echo "app:find-winners"
	php bin/console -vvv app:find-winners
	echo "app:find-users"
	php bin/console -vvv app:find-users
	sleep 5
done
