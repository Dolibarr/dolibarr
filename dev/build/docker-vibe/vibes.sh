#!/bin/bash
# Launch a vibe container with the current directory mounted as a volume
# Syntax:  vibes.sh [--no-cache] [--no-yolo] [--no-exit]
# --no-yolo: run vibe without the --yolo option (--yolo is the default)
# --no-exit: stay into the container with a bash shell after vibe has ended
#            (by default, when vibe is terminated, the container is terminated too)
#
# When the current directory is not the Dolibarr core sources (the first line of
# its README.md file is not "# DOLIBARR ERP & CRM"), the directory dolibarr_dev or dolibarr found
# next to the current directory is also mounted into the container, so the
# Dolibarr sources are available into the container at the same path as on the host.
# When it is the Dolibarr core sources without a documents directory, the directory
# dolibarr_documents found next to it (when it exists) is mounted as its documents directory.

# Extract --no-cache flag from arguments (consumed by docker build, not passed to container)
BUILD_ARGS=()
ARGS=()
for arg in "$@"; do
  if [[ "$arg" == "--no-cache" ]]; then
    BUILD_ARGS+=(--no-cache)
  else
    ARGS+=("$arg")
  fi
done

echo "Launch the docker VM with Vibe harness for AI"

# Detect if the current directory is the Dolibarr core sources (the first line of
# the README.md file is "# DOLIBARR ERP & CRM"): in that case it is already mounted
# as the working directory and no other dolibarr directory must be mounted.
IS_DOLIBARR_CORE=0
if [ -f README.md ] && [ "$(head -n 1 README.md | tr -d '\r')" = "# DOLIBARR ERP & CRM" ]; then
    IS_DOLIBARR_CORE=1
fi
# Previous detection, using the git remote (github.com/Dolibarr/dolibarr, or a fork of it:
# any remote pointing to a repository named "dolibarr")
#if git remote -v 2>/dev/null | grep origin | awk '{print $2}' | sed -e 's/\.git$//' -e 's#.*/##' | grep -qix dolibarr; then
#    IS_DOLIBARR_CORE=1
#fi

echo IS_DOLIBARR_CORE="$IS_DOLIBARR_CORE"
echo DOL_CTI_ADMIN_LOGIN="$DOL_CTI_ADMIN_LOGIN"
echo DOL_CTI_ADMIN_PASSWORD="${DOL_CTI_ADMIN_PASSWORD:0:3}..."

GH_TOKEN=$(gh auth token)
if [ -z "$GH_TOKEN" ]; then
    echo "GH_TOKEN is not set."
else
	echo GH_TOKEN="${GH_TOKEN:0:4}...${GH_TOKEN: -4}"
fi

set -o errexit
set -o nounset
set -o pipefail

# Directory containing this script (the docker-vibe build directory), whatever the current
# directory is. Symbolic links are resolved so a link to vibes.sh (in ~/bin for example) works too.
SCRIPT_DIR=$(cd "$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")" && pwd)

# Build image
# The build context is created on the fly: it contains the docker-vibe directory,
# and the .pre-commit-config.yaml of the repository when it exists (it is used by
# the Dockerfile to preload the pre-commit hooks environments into the image).
CONTEXT_TAR_ARGS=(.)
if [ -f .pre-commit-config.yaml ]; then
  CONTEXT_TAR_ARGS+=(-C "$PWD" .pre-commit-config.yaml)
fi

if ! tar -cf - -C "$SCRIPT_DIR" "${CONTEXT_TAR_ARGS[@]}" | sudo docker build -t docker-vibe "${BUILD_ARGS[@]}" -; then
    echo "ERROR: Build of the docker-vibe image failed."
    echo "The container is not started to avoid running an outdated image."
    exit 1
fi

# Run image
GIT_DIR=$(basename "$PWD")
export GIT_DIR

# Directory mounted as the working directory: ~/git/GIT_DIR (development context). When it does not exist
# (production context, sources outside of ~/git), the current directory is mounted at the same path.
WORK_DIR="$HOME/git/$GIT_DIR"
if [ ! -d "$WORK_DIR" ]; then
    WORK_DIR="$PWD"
fi

# Files of the host home directory mounted only when they exist (production context), as docker refuses
# to start with a missing bind source.
mkdir -p "$HOME/.vibe"
HOME_MOUNTS=()
for HOME_FILE in .gitconfig .bash_history; do
    if [ -f "$HOME/$HOME_FILE" ]; then
        HOME_MOUNTS+=(--mount "type=bind,src=$HOME/$HOME_FILE,dst=$HOME/$HOME_FILE")
    fi
done

# When working on something else than the Dolibarr core (a module for example),
# also mount the sibling directory dolibarr_dev or dolibarr (in this order of
# preference) when it exists next to the current directory.
EXTRA_MOUNTS=()
if [ "$IS_DOLIBARR_CORE" -eq 0 ]; then
    PARENT_DIR=$(dirname "$PWD")
    for DOLIBARR_DIR in dolibarr_dev dolibarr; do
        if [ -d "$PARENT_DIR/$DOLIBARR_DIR" ]; then
            EXTRA_MOUNTS+=(--mount "type=bind,src=$PARENT_DIR/$DOLIBARR_DIR,dst=$PARENT_DIR/dolibarr")
            break
        fi
    done
fi

# When working on the Dolibarr core without a documents directory into the working directory,
# mount the sibling directory dolibarr_documents (when it exists) as the documents directory.
# An empty documents directory is the mount point left by a previous run, so it is reused.
if [ "$IS_DOLIBARR_CORE" -eq 1 ]; then
    DOCUMENTS_DIR="$(dirname "$WORK_DIR")/dolibarr_documents"
    if [ -d "$DOCUMENTS_DIR" ] && [ ! -L "$WORK_DIR/documents" ]; then
        if [ ! -e "$WORK_DIR/documents" ] || { [ -d "$WORK_DIR/documents" ] && [ -z "$(ls -A "$WORK_DIR/documents")" ]; }; then
            echo "Mount $DOCUMENTS_DIR as $WORK_DIR/documents"
            EXTRA_MOUNTS+=(--mount "type=bind,src=$DOCUMENTS_DIR,dst=$WORK_DIR/documents")
        fi
    fi
fi

# Pass the host graphical session to the container so that Vibe can copy to
# the clipboard of the host (pyperclip/xclip on the X server or XWayland) and
# open links in the browser of the host (open-on-host through the D-Bus
# session bus and the xdg-desktop-portal, see README.md). Nothing is added
# when vibes.sh is started outside a graphical session: Vibe then falls back
# to its own mechanisms (OSC 52, host terminal selection with Shift).
GRAPHIC_ARGS=()
for VAR in DISPLAY WAYLAND_DISPLAY XDG_SESSION_TYPE XDG_RUNTIME_DIR XAUTHORITY; do
    VAR_VALUE=$(printenv "$VAR" || true)
    if [ -n "$VAR_VALUE" ]; then
        GRAPHIC_ARGS+=(-e "$VAR=$VAR_VALUE")
    fi
done
if [ -d /tmp/.X11-unix ]; then
    GRAPHIC_ARGS+=(--mount "type=bind,src=/tmp/.X11-unix,dst=/tmp/.X11-unix")
fi
if [ -n "${XDG_RUNTIME_DIR:-}" ] && [ -d "${XDG_RUNTIME_DIR}" ]; then
    # Contains the Wayland socket, the D-Bus session bus socket and the
    # XWayland authorization cookie of the host session.
    GRAPHIC_ARGS+=(--mount "type=bind,src=${XDG_RUNTIME_DIR},dst=${XDG_RUNTIME_DIR}")
fi
if [ -z "${XAUTHORITY:-}" ] && [ -f "$HOME/.Xauthority" ]; then
    # X11 session without XAUTHORITY exported: the cookie is in ~/.Xauthority.
    GRAPHIC_ARGS+=(-e "XAUTHORITY=$HOME/.Xauthority" --mount "type=bind,src=$HOME/.Xauthority,dst=$HOME/.Xauthority")
fi

# Pass the audio session of the host to the container so that the voice
# features of Vibe (microphone capture and speech playback through the
# PortAudio library installed into the image) work into the container:
# the container has no sound card, PortAudio must reach the audio server
# (PulseAudio, or PipeWire that provides the same socket) of the host
# session. The socket lives into XDG_RUNTIME_DIR, already mounted and
# passed by the graphic section above, PULSE_SERVER points PortAudio at
# it and the PulseAudio cookie of the host is mounted read-only so the
# server accepts the connection. Nothing is added outside a session with
# an audio server: Vibe then disables its voice features.
AUDIO_ARGS=()
if [ -n "${XDG_RUNTIME_DIR:-}" ] && [ -S "${XDG_RUNTIME_DIR}/pulse/native" ]; then
    AUDIO_ARGS+=(-e "PULSE_SERVER=unix:${XDG_RUNTIME_DIR}/pulse/native")
    if [ -d "$HOME/.config/pulse" ]; then
        AUDIO_ARGS+=(--mount "type=bind,src=$HOME/.config/pulse,dst=$HOME/.config/pulse,readonly")
    fi
fi

# Pass the Dolibarr PHPUnit credentials to the container: the REST API tests
# (test/phpunit/AbstractRestAPITest.php) and SecurityLoginTest.php use them to
# log in with a dedicated account instead of the admin/admin default.
# Nothing is added when the variables are not set on the host.
TEST_ARGS=()
for VAR in DOL_CTI_ADMIN_LOGIN DOL_CTI_ADMIN_PASSWORD; do
    VAR_VALUE=$(printenv "$VAR" || true)
    if [ -n "$VAR_VALUE" ]; then
        TEST_ARGS+=(-e "$VAR=$VAR_VALUE")
    fi
done

# DOCKER_ALLOW_SMTP=1 starts the container without the block of the outbound SMTP ports.
if [ "${DOCKER_ALLOW_SMTP:-}" = "1" ]; then
    TEST_ARGS+=(-e "DOCKER_ALLOW_SMTP=1")
fi

# Make the Apache logs of the host readable into the container (read-only mount).
# The GID owning the directory (adm on Debian/Ubuntu) is passed so the entrypoint
# can add the user to this group, as the directory is not readable by others.
LOG_ARGS=()
if [ -d /var/log/apache2 ]; then
    LOG_ARGS+=(-e "HOST_APACHE_LOG_GID=$(stat -c %g /var/log/apache2)" --mount "type=bind,src=/var/log/apache2,dst=/var/log/apache2,readonly")
fi

# Share the Transifex credentials of the host with the tx client of the container (read-only mount).
TX_ARGS=()
if [ -f "$HOME/.transifexrc" ]; then
    TX_ARGS+=(--mount "type=bind,src=$HOME/.transifexrc,dst=$HOME/.transifexrc,readonly")
fi

# The AppArmor mediation of the dbus-daemon of the host refuses the connections
# coming from the default docker profile (docker-default declares no dbus rule),
# so the clicks on the chat links (open-on-host, see README.md) are ignored
# without it. Run the container unconfined so its processes may talk to the
# D-Bus session bus of the host. Only added when AppArmor is enabled on the
# host (the option makes docker fail on the hosts without AppArmor).
SECURITY_ARGS=()
if [ "$(cat /sys/module/apparmor/parameters/enabled 2>/dev/null)" = "Y" ]; then
    SECURITY_ARGS+=(--security-opt apparmor=unconfined)
fi

sudo docker run --rm -it \
  -e HOST_UID="$(id -u)" \
  -e HOST_GID="$(id -g)" \
  -e HOST_USER="$(id -un)" \
  -e HOST_GROUP="$(id -un)" \
  -e GH_TOKEN="$GH_TOKEN" \
  "${GRAPHIC_ARGS[@]}" \
  "${AUDIO_ARGS[@]}" \
  "${TEST_ARGS[@]}" \
  "${LOG_ARGS[@]}" \
  "${TX_ARGS[@]}" \
  "${SECURITY_ARGS[@]}" \
  --network=host \
  --cap-add=NET_ADMIN \
  --mount "type=bind,src=/var/run/mysqld/mysqld.sock,dst=/var/run/mysqld/mysqld.sock" \
  --mount "type=bind,src=$WORK_DIR,dst=$WORK_DIR" \
  "${EXTRA_MOUNTS[@]}" \
  --mount "type=bind,src=$HOME/.vibe,dst=/home/$(id -un)/.vibe" \
  "${HOME_MOUNTS[@]}" \
  -w "$WORK_DIR" \
  docker-vibe "${ARGS[@]}"
