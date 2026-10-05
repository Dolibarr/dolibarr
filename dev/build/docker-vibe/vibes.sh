#!/bin/bash
# Launch a vibe container with the current directory mounted as a volume
# Syntax:  vibes.sh [--no-cache] [--no-yolo] [--no-exit]
# --no-yolo: run vibe without the --yolo option (--yolo is the default)
# --no-exit: stay into the container with a bash shell after vibe has ended
#            (by default, when vibe is terminated, the container is terminated too)
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

echo "Launch the docker VM with Vibe harness for AI"

# Detect if the current directory is a git clone of the Dolibarr core repository
# (github.com/Dolibarr/dolibarr, or a fork of it: any remote pointing to a
# repository named "dolibarr"): in that case it is already mounted as the
# working directory and no other dolibarr directory must be mounted.
IS_DOLIBARR_CORE=0
if git remote -v 2>/dev/null | grep origin | awk '{print $2}' | sed -e 's/\.git$//' -e 's#.*/##' | grep -qix dolibarr; then
    IS_DOLIBARR_CORE=1
fi

echo IS_DOLIBARR_CORE="$IS_DOLIBARR_CORE"

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
# The build context is created on the fly: it contains the docker-vibe directory,
# and the .pre-commit-config.yaml of the repository when it exists (it is used by
# the Dockerfile to preload the pre-commit hooks environments into the image).
CONTEXT_TAR_ARGS=(.)
if [ -f .pre-commit-config.yaml ]; then
  CONTEXT_TAR_ARGS+=(-C "$PWD" .pre-commit-config.yaml)
fi

if ! tar -cf - -C dev/build/docker-vibe "${CONTEXT_TAR_ARGS[@]}" | sudo docker build -t docker-vibe "${BUILD_ARGS[@]}" -; then
    echo "ERROR: Build of the docker-vibe image failed."
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

sudo docker run --rm -it \
  -e HOST_UID="$(id -u)" \
  -e HOST_GID="$(id -g)" \
  -e HOST_USER="$(id -un)" \
  -e HOST_GROUP="$(id -un)" \
  -e GH_TOKEN="$GH_TOKEN" \
  --network=host \
  --cap-add=NET_ADMIN \
  --mount "type=bind,src=/var/run/mysqld/mysqld.sock,dst=/var/run/mysqld/mysqld.sock" \
  --mount "type=bind,src=$HOME/git/$GIT_DIR,dst=$HOME/git/$GIT_DIR" \
  "${EXTRA_MOUNTS[@]}" \
  --mount "type=bind,src=$HOME/.vibe,dst=/home/$(id -un)/.vibe" \
  --mount "type=bind,src=$HOME/.gitconfig,dst=$HOME/.gitconfig" \
  --mount "type=bind,src=$HOME/.bash_history,dst=$HOME/.bash_history" \
  -w "$HOME/git/$GIT_DIR" \
  docker-vibe "${ARGS[@]}"
