#!/bin/bash
# desktop-entrypoint.sh

USER_ID="${HOST_UID:-1000}"
GROUP_ID="${HOST_GID:-1000}"
USER_NAME="${HOST_USER:-developer}"
GROUP_NAME="${HOST_GROUP:-developer}"

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


if [ "$1" = "--yolo" ]; then
    VIBE_OPTIONS="--yolo"
else
    VIBE_OPTIONS=""
fi

echo "VIBE_OPTIONS=$VIBE_OPTIONS"

# Execute order
exec runuser -u "$USER_NAME" -- "bash" --rcfile /etc/bash.bashrc -i -c 'vibe --agent agent-power '"$VIBE_OPTIONS"'; exec bash'
#exec "$@"
