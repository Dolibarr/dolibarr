#!/bin/bash
# Block the outbound SMTP ports (25, 465, 587) of the machine that runs the vibe
# containers (the VM), so that no process, inside or outside a container, can connect
# to a SMTP server (see README.md).
#
# This is required to close the last email path of the containers: they are run with
# --network=host, so they use the network stack of the VM and the image can not block
# these connections itself.
#
# The blocked connections are rejected immediately (REJECT) so the code that tries to
# send an email fails fast instead of hanging.
#
# The rules are not persistent: they are lost at the next reboot of the VM. Run this
# script again, or save them with iptables-persistent (see README.md).
#
# Syntax: sudo dev/build/docker-vibe/smtpblock.sh             Install the block (default)
#         sudo dev/build/docker-vibe/smtpblock.sh --status    Show the state
#         sudo dev/build/docker-vibe/smtpblock.sh --remove    Remove the block

PORTS="25,465,587"

if [ "$(id -u)" -ne 0 ]; then
    echo "This script must be run as root (try: sudo $0 $*)"
    exit 1
fi

action="${1:-install}"
action="${action#--}"

case "$action" in
install|status|remove)
    ;;
*)
    echo "Syntax: $0 [--install|--status|--remove]"
    exit 1
    ;;
esac

# Inserted at position 1 of the OUTPUT chain, so the block takes precedence
# over any existing ACCEPT rule.
RULE=(-p tcp -m multiport --dports "$PORTS" -j REJECT)

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
            echo "$cmd: outbound SMTP ports $PORTS already blocked."
        else
            if "$cmd" -I OUTPUT 1 "${RULE[@]}"; then
                echo "$cmd: outbound SMTP ports $PORTS blocked."
            else
                rc=1
            fi
        fi
        ;;
    status)
        if "$cmd" -C OUTPUT "${RULE[@]}" > /dev/null 2>&1; then
            echo "$cmd: outbound SMTP ports $PORTS blocked."
        else
            echo "$cmd: outbound SMTP ports $PORTS NOT blocked."
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
        echo "$cmd: outbound SMTP ports $PORTS unblocked."
        ;;
    esac
done

# If neither iptables nor ip6tables was found, or a command failed.
if [ "$found" -eq 0 ] || [ "$rc" -ne 0 ]; then
    exit 1
fi
