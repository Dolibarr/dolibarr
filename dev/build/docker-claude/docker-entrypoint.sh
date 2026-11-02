#!/bin/bash
# docker-entrypoint.sh of the docker-claude image (launched by claudes.sh)

USER_ID="${HOST_UID:-1000}"
GROUP_ID="${HOST_GID:-1000}"
USER_NAME="${HOST_USER:-developer}"
GROUP_NAME="${HOST_GROUP:-developer}"

# Block the outbound SMTP ports (25, 465, 587) of the machine running the container (the VM).
# The container uses --network=host, so the block is installed once on the network stack shared
# with the VM (it requires the capability NET_ADMIN, added by claudes.sh).
# It guarantees that no email can be sent from the container (see dev/build/docker-vibe/README.md).
# Set the environment variable DOCKER_ALLOW_SMTP=1 to start a container without this protection.
if [ "${DOCKER_ALLOW_SMTP:-}" != "1" ]; then
    if ! /usr/local/bin/smtpblock.sh; then
        echo "ERROR: Failed to block the outbound SMTP ports 25, 465 and 587."
        echo "The container is stopped to guarantee that no email can be sent from it."
        echo "Run the container with the capability NET_ADMIN (--cap-add=NET_ADMIN),"
        echo "or set DOCKER_ALLOW_SMTP=1 to run it without the outbound SMTP ports block."
        exit 1
    fi
fi

# Create group
EXISTING_GROUP=$(getent group "$GROUP_ID" | cut -d: -f1)

if [ -n "$EXISTING_GROUP" ]; then
    echo "GID $GROUP_ID already belongs to $EXISTING_GROUP"

    if [ "$EXISTING_GROUP" != "$GROUP_NAME" ]; then
        echo Changing groupname for "$EXISTING_GROUP" to "$GROUP_NAME"
        groupmod \
            --new-name "$GROUP_NAME" \
            "$EXISTING_GROUP"
    fi
else
    echo "Creating group $GROUP_NAME with GID $GROUP_ID"

    groupadd \
        --gid "$GROUP_ID" \
        "$GROUP_NAME"

    EXISTING_GROUP="$GROUP_NAME"
fi


# Create user
EXISTING_USER=$(getent passwd "$USER_ID" | cut -d: -f1)

if [ -n "$EXISTING_USER" ]; then
    echo "UID $USER_ID already belongs to $EXISTING_USER"

    if [ "$EXISTING_USER" != "$USER_NAME" ]; then
        echo Changing username for "$EXISTING_USER" to "$USER_NAME"
        usermod \
            --login "$USER_NAME" \
            --home "/home/$USER_NAME" \
            --move-home \
            "$EXISTING_USER"
    fi
else
    echo "Creating user $USER_NAME with UID $USER_ID"

    useradd \
        --uid "$USER_ID" \
        --gid "$GROUP_ID" \
        --create-home \
        --shell /bin/bash \
        "$USER_NAME"
fi

# The home directory of the user is created by docker itself (as root) to
# receive the volume mounts of claudes.sh, so it stays owned by root and the
# user can not write into it. Give it to the user so the tools can create
# their files into it (rtk: ~/.config/rtk, git: ~/.config/git, ...).
chown "$USER_NAME:$GROUP_NAME" "/home/$USER_NAME"

# The state file of Claude Code ~/.claude.json can not be bind mounted (it is replaced at
# each write): it is a link to ~/.claude/.claude.json, into the mounted directory ~/.claude.
if [ ! -e "/home/$USER_NAME/.claude.json" ]; then
    runuser -u "$USER_NAME" -- ln -s .claude/.claude.json "/home/$USER_NAME/.claude.json"
fi

# Add the user to the group owning the Apache logs of the host (mounted by claudes.sh),
# so they can be read. The group is created when the GID does not exist into the image.
if [ -n "${HOST_APACHE_LOG_GID:-}" ] && [ "$HOST_APACHE_LOG_GID" != "0" ]; then
    LOG_GROUP=$(getent group "$HOST_APACHE_LOG_GID" | cut -d: -f1)
    if [ -z "$LOG_GROUP" ]; then
        LOG_GROUP="hostapachelog"
        groupadd --gid "$HOST_APACHE_LOG_GID" "$LOG_GROUP"
    fi
    usermod --append --groups "$LOG_GROUP" "$USER_NAME"
fi


# Allow the user to become root inside the container with a passwordless sudo
# (the account is created without a password, so a sudo asking for one would never work).
#echo "$USER_NAME ALL=(ALL) NOPASSWD:ALL" > "/etc/sudoers.d/$USER_NAME"
#chmod 440 "/etc/sudoers.d/$USER_NAME"


echo "Running as $USER_NAME ($USER_ID:$GROUP_ID)"

WORKDIR="$(pwd)"

if [ -n "$WORKDIR" ] && [ ! -L "$WORKDIR" ]; then
	echo "Create link /dolibarr"
	ln -fs "$WORKDIR" /dolibarr 2>/dev/null
fi
# Claude Code reads its project settings, skills and agents from .claude and
# its instructions from CLAUDE.md: they are links to the files shared by all
# the agents (.agents). Links are created only when the files do not exist.
if [ -n "$WORKDIR" ] && [ -d "$WORKDIR/.agents" ] && [ ! -e "$WORKDIR/.claude" ] && [ ! -L "$WORKDIR/.claude" ]; then
	echo "Create link $WORKDIR/.claude"
	runuser -u "$USER_NAME" -- ln -s .agents "$WORKDIR/.claude" 2>/dev/null
fi
if [ -n "$WORKDIR" ] && [ -f "$WORKDIR/.agents/AGENTS.md" ] && [ ! -e "$WORKDIR/CLAUDE.md" ] && [ ! -L "$WORKDIR/CLAUDE.md" ]; then
	echo "Create link $WORKDIR/CLAUDE.md"
	runuser -u "$USER_NAME" -- ln -s .agents/AGENTS.md "$WORKDIR/CLAUDE.md" 2>/dev/null
fi


# Create .cache directory
mkdir -p "/home/$USER_NAME/.cache"
chmod 700 "/home/$USER_NAME/.cache"
chown -R "$USER_NAME:$USER_NAME" "/home/$USER_NAME/.cache"

export XDG_CACHE_HOME="/home/$USER_NAME/.cache"

# Expose the host graphical session (mounted by claudes.sh) to the user.
# XDG_RUNTIME_DIR contains the sockets of the host session: the D-Bus session
# bus (used by open-on-host to open links in the browser of the host through
# the xdg-desktop-portal) and the Wayland socket (used to reach the clipboard).
# See README.md.
if [ -d "/run/user/$USER_ID" ]; then
    if [ -z "${XDG_RUNTIME_DIR:-}" ]; then
        export XDG_RUNTIME_DIR="/run/user/$USER_ID"
    fi
    if [ -S "/run/user/$USER_ID/bus" ]; then
        export DBUS_SESSION_BUS_ADDRESS="unix:path=/run/user/$USER_ID/bus"
    fi
fi

install -d -m 700 -o "$USER_NAME" -g "$USER_NAME" "/home/$USER_NAME/.ssh"

# shellcheck disable=SC2016  # $HOME must expand in the su subshell, not here
su -s /bin/sh "$USER_NAME" -c \
    'ssh-keyscan -t ed25519,rsa github.com > "$HOME/.ssh/known_hosts" 2>/dev/null'

chmod 644 "/home/$USER_NAME/.ssh/known_hosts"


# --no-yolo is the default mode (claude asks for permissions). Use --yolo to run
# claude with --dangerously-skip-permissions (the container being the sandbox).
# All the other arguments that are not options of claudes.sh are passed to claude.
# Use --rc (or rc) to launch claude in Remote Control mode (claude remote-control).
CLAUDE_OPTIONS=""
KEEP_CONTAINER=0
REMOTE_CONTROL=0
CLAUDE_ARGS=()

for arg in "$@"; do
    case "$arg" in
        --yolo)
            CLAUDE_OPTIONS="--dangerously-skip-permissions"
            ;;
        --no-yolo)
            CLAUDE_OPTIONS=""
            ;;
        --no-exit)
            KEEP_CONTAINER=1
            ;;
        rc|--rc)
            REMOTE_CONTROL=1
            ;;
        *)
            CLAUDE_ARGS+=("$arg")
            ;;
    esac
done

# The remote-control subcommand has no --dangerously-skip-permissions option:
# the yolo mode is given to the spawned sessions with --permission-mode.
if [ "$REMOTE_CONTROL" = "1" ]; then
    if [ -n "$CLAUDE_OPTIONS" ]; then
        CLAUDE_OPTIONS="remote-control --permission-mode bypassPermissions"
    else
        CLAUDE_OPTIONS="remote-control"
    fi
fi

echo "CLAUDE_OPTIONS=$CLAUDE_OPTIONS ${CLAUDE_ARGS[*]}"

# Quote the arguments so they survive the bash -c of runuser.
CLAUDE_CMD="claude $CLAUDE_OPTIONS"
if [ "${#CLAUDE_ARGS[@]}" -gt 0 ]; then
    CLAUDE_CMD="$CLAUDE_CMD $(printf '%q ' "${CLAUDE_ARGS[@]}")"
fi

# Execute order. Once claude has ended, stay into the container only when --no-exit was provided.
if [ "$KEEP_CONTAINER" = "1" ]; then
    exec runuser -u "$USER_NAME" -- "bash" --rcfile /etc/bash.bashrc -i -c "$CLAUDE_CMD; exec bash"
else
    exec runuser -u "$USER_NAME" -- "bash" --rcfile /etc/bash.bashrc -i -c "$CLAUDE_CMD"
fi
