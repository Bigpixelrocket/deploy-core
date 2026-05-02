#!/usr/bin/env bash

set -euo pipefail

#
# Supervisor Script - Run long-lived workers for supported frameworks
# ----
#
# This should be executed by supervisord on the remote server.
#
# Environment variables provided by runner script:
#   DEPLOY_RELEASE_PATH  - Absolute path to the current release directory
#   DEPLOY_SHARED_PATH   - Absolute path to the shared/ directory
#   DEPLOY_CURRENT_PATH  - Absolute path to the current/ symlink
#   DEPLOY_DOMAIN        - Site domain (example.com)
#   DEPLOY_BRANCH        - Git branch currently deployed
#   DEPLOY_PHP           - Absolute path to the PHP binary (e.g. /usr/bin/php8.4)
#
# The --max-time/--time-limit flags ensure graceful restart
# (matches default stopwaitsecs=3600).
#

cd "${DEPLOY_CURRENT_PATH}"

# ----
# Framework Detection
# ----

framework=""

if [[ -f artisan ]]; then
	framework="laravel"
elif [[ -f bin/console ]]; then
	framework="symfony"
elif [[ -f spark ]]; then
	framework="codeigniter"
fi

# ----
# Workers
# ----

if [[ $framework == "laravel" ]]; then
	#
	# Why exec is required:
	#   Without exec, the process tree looks like:
	#     supervisord -> bash (tracked) -> php (actual worker)
	#   Supervisord only tracks bash. When it sends SIGTERM to stop the program,
	#   the signal goes to bash, not PHP. PHP never gets a chance to gracefully
	#   finish the current message before shutting down.
	#
	#   With exec, bash is replaced by PHP:
	#     supervisord -> php (tracked directly)
	#   Now SIGTERM goes directly to PHP, allowing graceful shutdown within
	#   the stopwaitsecs window (default 3600s).
	#
	exec "${DEPLOY_PHP}" artisan queue:work --sleep=3 --tries=3 --max-time=3600
elif [[ $framework == "symfony" ]]; then
	#
	# Why exec is required:
	#   See Laravel notes above.
	#
	exec "${DEPLOY_PHP}" bin/console messenger:consume async --time-limit=3600
else
	echo "Unsupported framework for supervisor.sh. Customize this script for your app." >&2
	exit 1
fi
