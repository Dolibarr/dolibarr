#!/bin/bash
# desktop-entrypoint.sh

USER_ID="${HOST_UID:-100}"
GROUP_ID="${HOST_GID:-1000}"
USER_NAME="${HOST_USER:-developer}"

echo "Add user $USER_NAME with uid $USER_ID and gid $GROUP_ID"

# Create group
if ! getent group "$GROUP_ID" >/dev/null; then
    groupadd --gid "$GROUP_ID" "$USER_NAME"
fi

# Create user
if ! getent passwd "$USER_ID" >/dev/null; then
    useradd \
        --uid "$USER_ID" \
        --gid "$GROUP_ID" \
        --create-home \
        --shell /bin/bash \
        "$USER_NAME"
fi

echo "Running as $USER_NAME ($USER_ID:$GROUP_ID)"

ln -fs /dolibarr_dev /dolibarr 2>/dev/null

# Execute order
exec runuser -u "$USER_NAME" -- "$@" --rcfile /etc/bash.bashrc -i -c 'vibe; exec bash'
