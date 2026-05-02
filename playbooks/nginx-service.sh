#!/usr/bin/env bash

#
# Nginx Service
#
# Controls Nginx service lifecycle (start/stop/restart/reload) via systemctl.
#
# Required Environment Variables:
#   DEPLOY_OUTPUT_FILE  - Output file path
#   DEPLOY_PERMS        - Permissions: root|sudo|none
#   DEPLOY_ACTION       - Action: start|stop|restart|reload
#
# Output:
#   status: success
#

set -o pipefail
export DEBIAN_FRONTEND=noninteractive

[[ -z $DEPLOY_OUTPUT_FILE ]] && echo "Error: DEPLOY_OUTPUT_FILE required" && exit 1
[[ -z $DEPLOY_PERMS ]] && echo "Error: DEPLOY_PERMS required" && exit 1
[[ -z $DEPLOY_ACTION ]] && echo "Error: DEPLOY_ACTION required" && exit 1
export DEPLOY_PERMS

# Shared helpers are automatically inlined when executing playbooks remotely
# source "$(dirname "$0")/helpers.sh"

# ----
# Service Operations
# ----

#
# Execute the requested service action
#
# Validates the action and executes the corresponding systemctl command

execute_action() {
	case $DEPLOY_ACTION in
		start)
			echo "→ Starting Nginx..."
			if ! run_cmd systemctl start nginx; then
				echo "Error: Failed to start Nginx" >&2
				exit 1
			fi
			verify_service_active
			;;
		restart)
			echo "→ Restarting Nginx..."
			if ! run_cmd systemctl restart nginx; then
				echo "Error: Failed to restart Nginx" >&2
				exit 1
			fi
			verify_service_active
			;;
		stop)
			echo "→ Stopping Nginx..."
			if ! run_cmd systemctl stop nginx; then
				echo "Error: Failed to stop Nginx" >&2
				exit 1
			fi
			verify_service_stopped
			;;
		reload)
			echo "→ Testing Nginx configuration..."
			if ! run_cmd nginx -t 2>&1; then
				echo "Error: Nginx configuration test failed" >&2
				exit 1
			fi
			echo "→ Reloading Nginx..."
			if ! run_cmd systemctl reload nginx; then
				echo "Error: Failed to reload Nginx" >&2
				exit 1
			fi
			;;
		*)
			echo "Error: Invalid action '${DEPLOY_ACTION}'. Valid: start|stop|restart|reload" >&2
			exit 1
			;;
	esac
}

#
# Verify Nginx service is active

verify_service_active() {
	echo "→ Verifying Nginx is running..."
	local max_wait=10
	local waited=0

	while ! systemctl is-active --quiet nginx 2> /dev/null; do
		if ((waited >= max_wait)); then
			echo "Error: Nginx service failed to start within ${max_wait} seconds" >&2
			exit 1
		fi
		sleep 1
		waited=$((waited + 1))
	done
}

#
# Verify Nginx service is stopped

verify_service_stopped() {
	echo "→ Verifying Nginx is stopped..."
	local max_wait=10
	local waited=0

	while systemctl is-active --quiet nginx 2> /dev/null; do
		if ((waited >= max_wait)); then
			echo "Error: Nginx service failed to stop within ${max_wait} seconds" >&2
			exit 1
		fi
		sleep 1
		waited=$((waited + 1))
	done
}

# ----
# Main Execution
# ----

main() {
	execute_action

	if ! cat > "$DEPLOY_OUTPUT_FILE" <<- EOF; then
		status: success
	EOF
		echo "Error: Failed to write output file" >&2
		exit 1
	fi
}

main "$@"
