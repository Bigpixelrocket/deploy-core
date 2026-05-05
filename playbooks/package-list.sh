#!/usr/bin/env bash

#
# Package List
#
# Configures package repositories and optionally gathers available PHP versions.
#
# Output:
#   status: success
#   repos_configured: true
#
# Output (with DEPLOY_GATHER_PHP=true):
#   status: success
#   repos_configured: true
#   php:
#     "8.4":
#       extensions: [cli, fpm, mysql, curl, mbstring]
#     "8.3":
#       extensions: [cli, fpm, mysql, curl, mbstring]
#

set -o pipefail
export DEBIAN_FRONTEND=noninteractive

[[ -z $DEPLOY_OUTPUT_FILE ]] && echo "Error: DEPLOY_OUTPUT_FILE required" && exit 1
[[ -z $DEPLOY_PERMS ]] && echo "Error: DEPLOY_PERMS required" && exit 1
export DEPLOY_PERMS

# Shared helpers are automatically inlined when executing playbooks remotely
# source "$(dirname "$0")/helpers.sh"

# ----
# Helper Functions
# ----

#
# Smart apt update with timestamp-based throttling
#
# Arguments:
#   $1 - force (optional): true to bypass throttling, false/empty for normal behavior
#
# Returns:
#   0 on success, 1 on failure

configure_php_ppa() {
	local codename=""
	local current_contents=""
	local fallback_uri="https://mirror.dogado.de/ppa.launchpad.net/ondrej/php/ubuntu/"
	local key_fingerprint="B8DC7E53946656EFBCE4C1DD71DAEAAB4AD4CAB6"
	local key_url="https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x${key_fingerprint}"
	local keyring="/etc/apt/keyrings/ondrej-php.gpg"
	local keyring_tmp=""
	local key_tmp=""
	local legacy_source_removed=false
	local primary_uri="https://ppa.launchpadcontent.net/ondrej/php/ubuntu/"
	local source_file="/etc/apt/sources.list.d/ondrej-php.sources"
	local source_contents=""
	local source_path

	if [[ -r /etc/os-release ]]; then
		# shellcheck source=/dev/null
		. /etc/os-release
		codename="${VERSION_CODENAME:-}"
	fi

	if [[ -z $codename ]]; then
		echo "Error: Failed to detect Ubuntu codename" >&2
		return 1
	fi

	if [[ ! -f $keyring ]]; then
		if ! ensure_php_ppa_tools; then
			return 1
		fi

		echo "→ Installing PHP PPA signing key..."
		key_tmp=$(mktemp /tmp/ondrej-php.XXXXXX.asc) || {
			echo "Error: Failed to create PHP PPA signing key temp file" >&2
			return 1
		}
		keyring_tmp=$(mktemp /tmp/ondrej-php.XXXXXX.gpg) || {
			rm -f "$key_tmp"
			echo "Error: Failed to create PHP PPA keyring temp file" >&2
			return 1
		}
		chmod 0600 "$key_tmp" "$keyring_tmp"

		if ! run_cmd mkdir -p /etc/apt/keyrings; then
			rm -f "$key_tmp" "$keyring_tmp"
			echo "Error: Failed to create APT keyring directory" >&2
			return 1
		fi

		if ! curl -fsSL "$key_url" -o "$key_tmp"; then
			rm -f "$key_tmp" "$keyring_tmp"
			echo "Error: Failed to download PHP PPA signing key" >&2
			return 1
		fi

		if ! gpg --dearmor --yes -o "$keyring_tmp" "$key_tmp"; then
			rm -f "$key_tmp" "$keyring_tmp"
			echo "Error: Failed to install PHP PPA signing key" >&2
			return 1
		fi

		if ! run_cmd install -m 0644 "$keyring_tmp" "$keyring"; then
			rm -f "$key_tmp" "$keyring_tmp"
			echo "Error: Failed to install PHP PPA signing key" >&2
			return 1
		fi

		rm -f "$key_tmp" "$keyring_tmp"
		if ! run_cmd chmod 0644 "$keyring"; then
			echo "Error: Failed to set PHP PPA signing key permissions" >&2
			return 1
		fi
		repo_added=true
	fi

	source_contents=$(cat <<- EOF
		Types: deb
		URIs: ${primary_uri} ${fallback_uri}
		Suites: ${codename}
		Components: main
		Languages: none
		Signed-By: ${keyring}
	EOF
	)
	current_contents=$(cat "$source_file" 2> /dev/null || printf '')

	while IFS= read -r source_path; do
		[[ $source_path == "$source_file" ]] && continue
		if run_cmd rm -f "$source_path"; then
			legacy_source_removed=true
		fi
	done < <(grep -rl "ondrej/php" /etc/apt/sources.list.d/ 2> /dev/null || true)

	if [[ $current_contents == "$source_contents" ]]; then
		[[ $legacy_source_removed == true ]] && repo_added=true
		return 0
	fi

	echo "→ Adding PHP PPA with fallback mirror..."
	if ! printf '%s\n' "$source_contents" | run_cmd tee "$source_file" > /dev/null; then
		echo "Error: Failed to write PHP PPA source" >&2
		return 1
	fi

	repo_added=true
}

ensure_php_ppa_tools() {
	local packages=()

	if ! command -v curl > /dev/null 2>&1; then
		packages+=("curl")
	fi

	if ! command -v gpg > /dev/null 2>&1; then
		packages+=("gnupg")
	fi

	if [[ ! -f /etc/ssl/certs/ca-certificates.crt ]]; then
		packages+=("ca-certificates")
	fi

	if ((${#packages[@]} == 0)); then
		return 0
	fi

	echo "→ Installing PHP PPA source tools..."
	if ! apt_get_with_retry install -y "${packages[@]}"; then
		echo "Error: Failed to install PHP PPA source tools" >&2
		return 1
	fi
}

smart_apt_update() {
	local force=${1:-false}
	local timestamp_file="/tmp/deployer-apt-last-update"
	local threshold_seconds=$((24 * 60 * 60)) # 24 hours
	local now current_timestamp age

	now=$(date +%s)

	# Check if we need to update
	if [[ $force == false && -f $timestamp_file ]]; then
		current_timestamp=$(cat "$timestamp_file" 2> /dev/null || echo "0")
		age=$((now - current_timestamp))

		if ((age < threshold_seconds)); then
			echo "→ Using cached package list (cached $((age / 3600)) hours ago)..."
			return 0
		fi
	fi

	# Perform update
	echo "→ Updating package lists..."
	if ! apt_get_with_retry update; then
		echo "Error: Failed to update package lists" >&2
		return 1
	fi

	# Update timestamp
	echo "$now" > "$timestamp_file"
}

#
# Get PHP versions and extensions with caching
#
# Arguments:
#   $1 - force (optional): true to bypass cache and re-detect
#
# Side effects:
#   Sets PHP_CACHE_YAML with the YAML structure (or empty string)

get_php_with_cache() {
	PHP_CACHE_YAML=""
	local force=${1:-false}
	local cache_file="/tmp/deploy-core-cache"
	local threshold_seconds=$((24 * 60 * 60)) # 24 hours
	local now current_timestamp age

	# Check if PHP gathering is requested
	if [[ $DEPLOY_GATHER_PHP != 'true' ]]; then
		return 0
	fi

	now=$(date +%s)

	# Check if we can use cache
	if [[ $force == false && -f $cache_file ]]; then
		# Read timestamp from first line of cache
		current_timestamp=$(head -n1 "$cache_file" 2> /dev/null || echo "0")
		age=$((now - current_timestamp))

		if ((age < threshold_seconds)); then
			echo "→ Using cached PHP version and extensions list (cached $((age / 3600)) hours ago)..."
			# Return cached YAML (skip first line which is timestamp)
			PHP_CACHE_YAML=$(tail -n +2 "$cache_file" 2> /dev/null || printf '')
			return 0
		fi
	fi

	# Perform fresh detection
	echo "→ Detecting available PHP versions..."

	local php_versions
	php_versions=$(apt-cache search "^php[0-9]+\.[0-9]+-fpm$" 2> /dev/null | grep -oP 'php\K[0-9]+\.[0-9]+' | sort -V -u)

	if [[ -z $php_versions ]]; then
		echo "Error: No PHP versions found in repositories" >&2
		exit 1
	fi

	# Build YAML structure
	local yaml_output="php:"

	echo "→ Detecting available PHP extensions..."
	for version in $php_versions; do
		local extensions
		extensions=$(apt-cache search "^php${version}-" 2> /dev/null | grep -oP "php${version}-\K[a-z0-9]+" | sort -u)

		if [[ -z $extensions ]]; then
			continue
		fi

		yaml_output="${yaml_output}\n  \"${version}\":"
		yaml_output="${yaml_output}\n    extensions:"

		for ext in $extensions; do
			yaml_output="${yaml_output}\n      - ${ext}"
		done
	done

	# Cache the results (timestamp on first line, YAML on subsequent lines)
	{
		echo "$now"
		echo -e "$yaml_output"
	} > "$cache_file"

	# Store the YAML for callers
	PHP_CACHE_YAML="$yaml_output"
}

# ----
# Main Execution
# ----

main() {
	local repo_added=false

	#
	# Initial apt update
	# ----

	if ! smart_apt_update; then
		exit 1
	fi

	#
	# PHP repository
	# ----

	if ! configure_php_ppa; then
		exit 1
	fi

	#
	# Update apt again, only if we added new repositories
	# ----

	if [[ $repo_added == true ]]; then
		if ! smart_apt_update true; then
			exit 1
		fi
	fi

	#
	# Detect PHP versions and extensions (optional, with caching)
	# ----

	local yaml_php=""

	# Pass force=true if repos were added to invalidate cache
	if [[ $repo_added == true ]]; then
		get_php_with_cache true
	else
		get_php_with_cache
	fi

	yaml_php="$PHP_CACHE_YAML"

	#
	# Write output YAML
	# ----

	if [[ -n $yaml_php ]]; then
		{
			echo "status: success"
			echo "repos_configured: true"
			printf '%b\n' "$yaml_php"
		} > "$DEPLOY_OUTPUT_FILE"
	else
		{
			echo "status: success"
			echo "repos_configured: true"
		} > "$DEPLOY_OUTPUT_FILE"
	fi

	if [[ ! -f $DEPLOY_OUTPUT_FILE ]]; then
		echo "Error: Failed to write output file" >&2
		exit 1
	fi
}

main "$@"
