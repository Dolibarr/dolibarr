#!/bin/bash
# desktop-entrypoint.sh

USER_ID="${HOST_UID:-1000}"
GROUP_ID="${HOST_GID:-1000}"
USER_NAME="${HOST_USER:-developer}"
GROUP_NAME="${HOST_GROUP:-developer}"

# Block the outbound SMTP ports (25, 465, 587) of the machine running the container (the VM).
# The container uses --network=host, so the block is installed once on the network stack shared
# with the VM (it requires the capability NET_ADMIN, added by vibes.sh).
# It guarantees that no email can be sent from the container (see dev/build/docker-vibe/README.md).
# Set the environment variable VIBE_ALLOW_SMTP=1 to start a container without this protection.
if [ "${VIBE_ALLOW_SMTP}" != "1" ]; then
    if ! /usr/local/bin/smtpblock.sh; then
        echo "ERROR: Failed to block the outbound SMTP ports 25, 465 and 587."
        echo "The container is stopped to guarantee that no email can be sent from it."
        echo "Run the container with the capability NET_ADMIN (--cap-add=NET_ADMIN),"
        echo "or set VIBE_ALLOW_SMTP=1 to run it without the outbound SMTP ports block."
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
if [ -n "$WORKDIR" ] && [ ! -L "$WORKDIR/.vibeignore" ]; then
	echo "Create link $WORKDIR/.vibeignore"
	ln -fs .agentsignore .vibeignore 2>/dev/null
fi
if [ -n "$WORKDIR" ] && [ ! -L "$WORKDIR/.vibe" ]; then
	echo "Create link $WORKDIR/.vibe"
	ln -fs .agents .vibe 2>/dev/null
fi
#if [ -n "$WORKDIR" ] && [ ! -L "$WORKDIR/AGENTS.md" ]; then
#	echo "Create link $WORKDIR/AGENTS.md"
#	ln -fs .agents/AGENTS.md AGENTS.md 2>/dev/null
#fi


# Create .cache directory
mkdir -p "/home/$USER_NAME/.cache"
chmod 700 "/home/$USER_NAME/.cache"
chown -R "$USER_NAME:$USER_NAME" "/home/$USER_NAME/.cache"

export XDG_CACHE_HOME="/home/$USER_NAME/.cache"

install -d -m 700 -o "$USER_NAME" -g "$USER_NAME" "/home/$USER_NAME/.ssh"

# shellcheck disable=SC2016  # $HOME must expand in the su subshell, not here
su -s /bin/sh "$USER_NAME" -c \
    'ssh-keyscan -t ed25519,rsa github.com > "$HOME/.ssh/known_hosts" 2>/dev/null'

chmod 644 "/home/$USER_NAME/.ssh/known_hosts"


# --yolo is the default mode. Use --no-yolo to disable it.
VIBE_OPTIONS="--yolo"
KEEP_CONTAINER=0

for arg in "$@"; do
    case "$arg" in
        --yolo)
            VIBE_OPTIONS="--yolo"
            ;;
        --no-yolo)
            VIBE_OPTIONS=""
            ;;
        --no-exit)
            KEEP_CONTAINER=1
            ;;
    esac
done

echo "VIBE_OPTIONS=$VIBE_OPTIONS"

# Execute order. Once vibe has ended, stay into the container only when --no-exit was provided.
if [ "$KEEP_CONTAINER" = "1" ]; then
    exec runuser -u "$USER_NAME" -- "bash" --rcfile /etc/bash.bashrc -i -c 'vibe --agent agent-power '"$VIBE_OPTIONS"'; exec bash'
else
    exec runuser -u "$USER_NAME" -- "bash" --rcfile /etc/bash.bashrc -i -c 'vibe --agent agent-power '"$VIBE_OPTIONS"
fi
