#!/bin/bash
# Copyright (C) 2025		MDW	<mdeweerd@users.noreply.github.com>

set -euo pipefail

# This script retrieves the list of changed PHP files for a pull request
# using the GitHub API and sets two outputs:
#   - any_changed: "true" if at least one PHP file changed, "false" otherwise
#   - all_changed_files: space-separated list of changed PHP file paths
#
# Required environment variables:
#   GITHUB_TOKEN      - GitHub token with repo access
#   GITHUB_REPOSITORY - "owner/repo"
#   GITHUB_EVENT_PATH - Path to the event JSON payload

# Verify required environment variables are set
if [[ -z "${GITHUB_TOKEN:-}" ]]; then
	echo "GITHUB_TOKEN is not set" >&2
	exit 1
fi
if [[ -z "${GITHUB_REPOSITORY:-}" ]]; then
	echo "GITHUB_REPOSITORY is not set" >&2
	exit 1
fi
if [[ -z "${GITHUB_EVENT_PATH:-}" ]]; then
	echo "GITHUB_EVENT_PATH is not set" >&2
	exit 1
fi

# Extract the pull request number from the event payload
pr_number=$(jq --raw-output '.pull_request.number' "$GITHUB_EVENT_PATH")
if [[ "$pr_number" == "null" ]]; then
	echo "Not a pull request event"
	exit 0
fi

# Split repository into owner and repo name
# Split repository into owner and repo name using Bash parameter expansion
owner="${GITHUB_REPOSITORY%%/*}"  # Extract text before the first '/'
repo="${GITHUB_REPOSITORY##*/}"   # Extract text after the last '/'

page=1
per_page=100
changed_php_files=()

# Loop through all pages to gather changed files
while true; do
	response=$(curl -s -H "Authorization: token ${GITHUB_TOKEN}" \
		"https://api.github.com/repos/${owner}/${repo}/pulls/${pr_number}/files?per_page=${per_page}&page=${page}")

	# Filter for files ending with .php and add them to the list
	mapfile -t files < <(echo "$response" | jq -r '.[] | select(.filename | test("\\.php$")) | .filename')
	changed_php_files+=("${files[@]}")

	# Check if we have reached the last page (less than per_page results)
	count=$(echo "$response" | jq 'length')
	if (( count < per_page )); then
		break
	fi
	((page++))
done


# Build a space-separated string of changed PHP files
# This does not cope with files that have spaces.
# But such files do not exist in the project (at least not for the
# files we are filtering).
all_changed_files=$(IFS=" " ; echo "${changed_php_files[*]}")


# Determine changed files flag
if [ -z "$all_changed_files" ]; then
    any_changed="false"
else
    any_changed="true"
fi

# Set outputs for GitHub Actions if GITHUB_OUTPUT is available
if [ -n "${GITHUB_OUTPUT:-}" ]; then
	echo "any_changed=${any_changed}" >> "$GITHUB_OUTPUT"
	echo "all_changed_files=${all_changed_files}" >> "$GITHUB_OUTPUT"
else
	# Otherwise, print the outputs
	echo "any_changed=${any_changed}"
	echo "all_changed_files=${all_changed_files}"
fi
