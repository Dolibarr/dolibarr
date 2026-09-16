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
	ln -fs $WORKDIR /dolibarr 2>/dev/null
fi
if [ -n "$WORKDIR" ] && [ ! -L "$WORKDIR/.vibeignore" ]; then
	echo "Create link $WORKDIR/.vibeignore"
	ln -fs .agentsignore .vibeignore 2>/dev/null
fi
if [ -n "$WORKDIR" ] && [ ! -L "$WORKDIR/.vibe" ]; then
	echo "Create link $WORKDIR/.vibe"
	ln -fs .agents .vibe 2>/dev/null
fi

# Execute order
exec runuser -u "$USER_NAME" -- "$@" --rcfile /etc/bash.bashrc -i -c 'vibe --agent agent-power; exec bash'
#exec "$@"
