#!/bin/bash
# Block the outbound SMTP ports (25, 465, 587) of the machine that runs the vibe
# containers (the VM), so that no process, inside or outside a container, can connect
# to a SMTP server (see README.md).
#
# This is required to close the last email path of the containers: they are run with
# --network=host, so they use the network stack of the VM and the image can not block
# these connections itself.
#
# With --cgroup, the block is scoped to the processes of a cgroup v2 path (typically the
# scope of one container started by vibes.sh) instead of the whole machine: the other
# processes of the VM (an MTA running on a production server for example) can still
# connect to these ports. The path is resolved by the kernel into the initial cgroup
# namespace, so it must be the path seen from the host: vibes.sh starts the containers
# with --cgroupns=host, so the path read from /proc/self/cgroup into the container is
# the right one. It requires the unified cgroup v2 hierarchy and the xt_cgroup netfilter
# module (default on the current distributions, Ubuntu 21.10+).
#
# The blocked connections are rejected immediately (REJECT) so the code that tries to
# send an email fails fast instead of hanging.
#
# The rules are not persistent: they are lost at the next reboot of the VM. Run this
# script again, or save them with iptables-persistent (see README.md).
#
# Syntax: sudo dev/build/docker-vibe/smtpblock.sh [--cgroup PATH]       Install the block (default)
#         sudo dev/build/docker-vibe/smtpblock.sh --status [--cgroup PATH]  Show the state
#         sudo dev/build/docker-vibe/smtpblock.sh --remove [--cgroup PATH]  Remove the block

PORTS="25,465,587"

if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root (try: sudo $0 $*)"
    exit 1
fi

# The action is install by default. --cgroup scopes the rule to the processes of the
# given cgroup v2 path, so only the container is blocked instead of the whole machine
# (see the header).
action="install"
CGROUP_PATH=""

while [ $# -gt 0 ]; do
    case "$1" in
    install|status|remove|--install|--status|--remove)
        action="${1#--}"
        shift
        ;;
    --cgroup)
        if [ $# -lt 2 ]; then
            echo "Missing cgroup path after --cgroup."
            exit 1
        fi
        CGROUP_PATH="$2"
        shift 2
        ;;
    *)
        echo "Syntax: $0 [--install|--status|--remove] [--cgroup PATH]"
        exit 1
        ;;
    esac
done

# Inserted at position 1 of the OUTPUT chain, so the block takes precedence
# over any existing ACCEPT rule.
# With a cgroup path, only the connections of the processes of that cgroup are
# rejected: once the container is destroyed, the cgroup does not exist anymore and
# the rule left behind does not match anything.
if [ -n "$CGROUP_PATH" ]; then
    RULE=(-p tcp -m multiport --dports "$PORTS" -m cgroup --path "$CGROUP_PATH" -j REJECT)
else
    RULE=(-p tcp -m multiport --dports "$PORTS" -j REJECT)
fi

# Scope shown into the messages of the actions below
SCOPE_MSG=""
if [ -n "$CGROUP_PATH" ]; then
    SCOPE_MSG=" (cgroup $CGROUP_PATH)"
fi

found=0
rc=0

for cmd in iptables ip6tables; do
    if ! command -v "$cmd" > /dev/null 2>&1; then
        echo "$cmd not found, skipped."
        continue
    fi
    found=1

    case "$action" in
    install)
        if "$cmd" -C OUTPUT "${RULE[@]}" > /dev/null 2>&1; then
            echo "$cmd: outbound SMTP ports $PORTS already blocked$SCOPE_MSG."
        else
            if "$cmd" -I OUTPUT 1 "${RULE[@]}"; then
                echo "$cmd: outbound SMTP ports $PORTS blocked$SCOPE_MSG."
            else
                rc=1
            fi
        fi
        ;;
    status)
        if "$cmd" -C OUTPUT "${RULE[@]}" > /dev/null 2>&1; then
            echo "$cmd: outbound SMTP ports $PORTS blocked$SCOPE_MSG."
        else
            echo "$cmd: outbound SMTP ports $PORTS NOT blocked$SCOPE_MSG."
        fi
        ;;
    remove)
        while "$cmd" -C OUTPUT "${RULE[@]}" > /dev/null 2>&1; do
            if ! "$cmd" -D OUTPUT "${RULE[@]}"; then
                echo "Failed to delete the $cmd rule."
                rc=1
                break
            fi
        done
        echo "$cmd: outbound SMTP ports $PORTS unblocked$SCOPE_MSG."
        ;;
    esac
done

# If neither iptables nor ip6tables was found, or a command failed.
if [ "$found" -eq 0 ] || [ "$rc" -ne 0 ]; then
    exit 1
fi
