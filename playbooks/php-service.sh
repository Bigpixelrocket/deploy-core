#!/usr/bin/env bash

#
# PHP-FPM Service
#
# Controls PHP-FPM service lifecycle (start/stop/restart) via systemctl.
# Supports operating on multiple PHP versions at once.
#
# Required Environment Variables:
#   DEPLOY_OUTPUT_FILE   - Output file path
#   DEPLOY_PERMS         - Permissions: root|sudo|none
#   DEPLOY_ACTION        - Service action: start|stop|restart
#   DEPLOY_PHP_VERSIONS  - Comma-separated PHP versions (e.g., "8.3,8.4")
#
# Output:
#   status: success
#

set -o pipefail
export DEBIAN_FRONTEND=noninteractive

[[ -z $DEPLOY_OUTPUT_FILE ]] && echo "Error: DEPLOY_OUTPUT_FILE required" && exit 1
[[ -z $DEPLOY_PERMS ]] && echo "Error: DEPLOY_PERMS required" && exit 1
[[ -z $DEPLOY_ACTION ]] && echo "Error: DEPLOY_ACTION required" && exit 1
[[ -z $DEPLOY_PHP_VERSIONS ]] && echo "Error: DEPLOY_PHP_VERSIONS required" && exit 1
export DEPLOY_PERMS

# Shared helpers are automatically inlined when executing playbooks remotely
# source "$(dirname "$0")/helpers.sh"

# ----
# Service Operations
# ----

#
# Execute the requested service action for a single PHP version
#
# Arguments:
#   $1 - PHP version (e.g., "8.4")

execute_action_for_version() {
	local version=$1
	local service="php${version}-fpm"

	case $DEPLOY_ACTION in
		start | restart)
			echo "→ Running systemctl ${DEPLOY_ACTION} ${service}..."
			if ! run_cmd systemctl "$DEPLOY_ACTION" "$service"; then
				echo "Error: Failed to ${DEPLOY_ACTION} ${service}" >&2
				return 1
			fi
			verify_service_active "$service"
			;;
		stop)
			echo "→ Running systemctl stop ${service}..."
			if ! run_cmd systemctl stop "$service"; then
				echo "Error: Failed to stop ${service}" >&2
				return 1
			fi
			verify_service_stopped "$service"
			;;
		*)
			echo "Error: Invalid action '${DEPLOY_ACTION}'" >&2
			return 1
			;;
	esac
}

#
# Verify service is active
#
# Arguments:
#   $1 - Service name

verify_service_active() {
	local service=$1
	echo "→ Verifying ${service} is running..."
	local max_wait=10
	local waited=0

	while ! systemctl is-active --quiet "$service" 2> /dev/null; do
		if ((waited >= max_wait)); then
			echo "Error: ${service} failed to start" >&2
			return 1
		fi
		sleep 1
		waited=$((waited + 1))
	done
}

#
# Verify service is stopped
#
# Arguments:
#   $1 - Service name

verify_service_stopped() {
	local service=$1
	echo "→ Verifying ${service} is stopped..."
	local max_wait=10
	local waited=0

	while systemctl is-active --quiet "$service" 2> /dev/null; do
		if ((waited >= max_wait)); then
			echo "Error: ${service} failed to stop" >&2
			return 1
		fi
		sleep 1
		waited=$((waited + 1))
	done
}

# ----
# Main Execution
# ----

main() {
	local failed=0

	# Parse comma-separated versions
	IFS=',' read -ra versions <<< "$DEPLOY_PHP_VERSIONS"

	for version in "${versions[@]}"; do
		if ! execute_action_for_version "$version"; then
			failed=1
		fi
	done

	if ((failed)); then
		exit 1
	fi

	if ! cat > "$DEPLOY_OUTPUT_FILE" <<- EOF; then
		status: success
	EOF
		echo "Error: Failed to write output file" >&2
		exit 1
	fi
}

main "$@"
