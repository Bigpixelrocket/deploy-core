#!/usr/bin/env bash

#
# Memcached Service
#
# Controls Memcached service lifecycle (start/stop/restart) via systemctl.
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
		start | restart)
			echo "→ Running systemctl ${DEPLOY_ACTION} memcached..."
			if ! run_cmd systemctl "$DEPLOY_ACTION" memcached; then
				echo "Error: Failed to ${DEPLOY_ACTION} Memcached" >&2
				exit 1
			fi
			verify_service_active
			;;
		stop)
			echo "→ Running systemctl stop memcached..."
			if ! run_cmd systemctl stop memcached; then
				echo "Error: Failed to stop Memcached" >&2
				exit 1
			fi
			verify_service_stopped
			;;
		*)
			echo "Error: Invalid action '${DEPLOY_ACTION}'" >&2
			exit 1
			;;
	esac
}

#
# Verify Memcached service is active

verify_service_active() {
	echo "→ Verifying Memcached is running..."
	local max_wait=10
	local waited=0

	while ! systemctl is-active --quiet memcached 2> /dev/null; do
		if ((waited >= max_wait)); then
			echo "Error: Memcached service failed to start" >&2
			exit 1
		fi
		sleep 1
		waited=$((waited + 1))
	done
}

#
# Verify Memcached service is stopped

verify_service_stopped() {
	echo "→ Verifying Memcached is stopped..."
	local max_wait=10
	local waited=0

	while systemctl is-active --quiet memcached 2> /dev/null; do
		if ((waited >= max_wait)); then
			echo "Error: Memcached service failed to stop" >&2
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
