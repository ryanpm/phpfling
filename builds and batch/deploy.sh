#!/bin/bash

current_branch=$(git  rev-parse --abbrev-ref HEAD)
echo Current  Branch: $current_branch

echo 1 : Singapore-Staging
echo 2 : Singapore-Production

echo Choose Environment: 
read env

if [ $env -eq 1 ]; then
	if [ "$current_branch" == "Singapore-Staging" ]; then
		php5 phploy.php -s staging
	else
		echo "You are not in Singapore-Staging"
	fi

elif [ $env -eq 2 ]; then
	if [ "$current_branch" == "Singapore-Production" ]; then
		php5 phploy.php -s production
	else
		echo "You are not in Singapore-Production"
	fi
fi