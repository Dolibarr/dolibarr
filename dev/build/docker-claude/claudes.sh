#!/bin/bash
# Launch a Claude Code container with the current directory mounted as a volume
# Syntax:  claudes.sh [--no-cache] [--yolo|--no-yolo] [--no-exit] [--rc] [claude options...]
# --yolo:    run claude with the option --dangerously-skip-permissions
# --no-yolo: run claude without the option --dangerously-skip-permissions (it is the default)
# --rc:      run claude in Remote Control mode (claude remote-control), with the permission
#            mode bypassPermissions for the spawned sessions (only if --yolo is provided)
#            (rc, without the dashes, is still accepted as an alias)
# --no-exit: stay into the container with a bash shell after claude has ended
#            (by default, when claude is terminated, the container is terminated too)
# Other arguments are passed to claude (for example: claudes.sh --continue).
#
# The configuration directory ~/.claude of the host (credentials, settings,
# memory, sessions) is shared with the container (see README.md).
#
# When the current directory is not a git clone of the Dolibarr core repository
# (github.com/Dolibarr/dolibarr), the directory dolibarr_dev or dolibarr found
# next to the current directory is also mounted into the container, so the
# Dolibarr sources are available into the container at the same path as on the host.

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

echo "Launch the docker VM with Claude Code harness for AI"

# Detect if the current directory is a git clone of the Dolibarr core repository
# (github.com/Dolibarr/dolibarr, or a fork of it: any remote pointing to a
# repository named "dolibarr"): in that case it is already mounted as the
# working directory and no other dolibarr directory must be mounted.
IS_DOLIBARR_CORE=0
if git remote -v 2>/dev/null | grep origin | awk '{print $2}' | sed -e 's/\.git$//' -e 's#.*/##' | grep -qix dolibarr; then
    IS_DOLIBARR_CORE=1
fi

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

# Build image
# The build context is created on the fly: it contains the docker-claude directory,
# the scripts shared with docker-vibe (open-on-host, xdg-open-shim, smtpblock.sh),
# and the .pre-commit-config.yaml of the repository when it exists (it is used by
# the Dockerfile to preload the pre-commit hooks environments into the image).
CONTEXT_TAR_ARGS=(. -C "$PWD/dev/build/docker-vibe" open-on-host xdg-open-shim smtpblock.sh)
if [ -f .pre-commit-config.yaml ]; then
  CONTEXT_TAR_ARGS+=(-C "$PWD" .pre-commit-config.yaml)
fi

if ! tar -cf - -C dev/build/docker-claude "${CONTEXT_TAR_ARGS[@]}" | sudo docker build -t docker-claude "${BUILD_ARGS[@]}" -; then
    echo "ERROR: Build of the docker-claude image failed."
    echo "The container is not started to avoid running an outdated image."
    exit 1
fi

# Run image
GIT_DIR=$(basename "$PWD")
export GIT_DIR

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

# Pass the host graphical session to the container so that Claude Code can read
# the clipboard of the host (xclip on the X server or XWayland, wl-paste) and
# open links in the browser of the host (open-on-host through the D-Bus
# session bus and the xdg-desktop-portal, see README.md). Nothing is added
# when claudes.sh is started outside a graphical session: Claude Code then
# falls back to its own mechanisms (OSC 52, host terminal selection with Shift).
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
# so the links opened by claude (open-on-host, see README.md) are ignored
# without it. Run the container unconfined so its processes may talk to the
# D-Bus session bus of the host. Only added when AppArmor is enabled on the
# host (the option makes docker fail on the hosts without AppArmor).
SECURITY_ARGS=()
if [ "$(cat /sys/module/apparmor/parameters/enabled 2>/dev/null)" = "Y" ]; then
    SECURITY_ARGS+=(--security-opt apparmor=unconfined)
fi

# Share the Claude Code configuration of the host (credentials, settings, plugins,
# memory and sessions of the projects) with the container. It is mounted at the
# same path, so the absolute paths it contains stay valid. The state file
# ~/.claude.json (MCP servers, projects...) is replaced at each write, so it can
# not be bind mounted safely: into the container, ~/.claude.json is a link to
# ~/.claude/.claude.json (see docker-entrypoint.sh). The state file of the host is
# copied there at each launch, so the container gets the MCP servers of the host
# (the changes made into the container are lost at the next launch).
mkdir -p "$HOME/.claude"
if [ -f "$HOME/.claude.json" ] && [ ! -L "$HOME/.claude.json" ]; then
    echo "Copy $HOME/.claude.json into $HOME/.claude/.claude.json"
    cp -p "$HOME/.claude.json" "$HOME/.claude/.claude.json"
fi
CLAUDE_ENV_ARGS=()
# Pass the API key or the OAuth token of the host when one is set (otherwise the
# credentials of ~/.claude/.credentials.json are used).
# DOCKER_ALLOW_SMTP=1 starts the container without the block of the outbound SMTP ports.
# TERM and COLORTERM give the color support of the host terminal (docker sets TERM=xterm by default,
# so Claude Code would fall back to 16 colors and display, for example, its orange logo in red).
for VAR in ANTHROPIC_API_KEY CLAUDE_CODE_OAUTH_TOKEN DOCKER_ALLOW_SMTP TERM COLORTERM; do
    VAR_VALUE=$(printenv "$VAR" || true)
    if [ -n "$VAR_VALUE" ]; then
        CLAUDE_ENV_ARGS+=(-e "$VAR=$VAR_VALUE")
    fi
done

sudo docker run --rm -it \
  -e HOST_UID="$(id -u)" \
  -e HOST_GID="$(id -g)" \
  -e HOST_USER="$(id -un)" \
  -e HOST_GROUP="$(id -un)" \
  -e GH_TOKEN="$GH_TOKEN" \
  "${GRAPHIC_ARGS[@]}" \
  "${TEST_ARGS[@]}" \
  "${LOG_ARGS[@]}" \
  "${TX_ARGS[@]}" \
  "${SECURITY_ARGS[@]}" \
  "${CLAUDE_ENV_ARGS[@]}" \
  --network=host \
  --cap-add=NET_ADMIN \
  --mount "type=bind,src=/var/run/mysqld/mysqld.sock,dst=/var/run/mysqld/mysqld.sock" \
  --mount "type=bind,src=$HOME/git/$GIT_DIR,dst=$HOME/git/$GIT_DIR" \
  "${EXTRA_MOUNTS[@]}" \
  --mount "type=bind,src=$HOME/.claude,dst=$HOME/.claude" \
  --mount "type=bind,src=$HOME/.gitconfig,dst=$HOME/.gitconfig" \
  --mount "type=bind,src=$HOME/.bash_history,dst=$HOME/.bash_history" \
  -w "$HOME/git/$GIT_DIR" \
  docker-claude "${ARGS[@]}"
