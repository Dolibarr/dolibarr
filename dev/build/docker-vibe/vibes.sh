#!/bin/bash
# Launch a vibe container with the current directory mounted as a volume
# Syntax:  vibes.sh [--no-cache] [--no-yolo] [--no-exit]
# --no-yolo: run vibe without the --yolo option (--yolo is the default)
# --no-exit: stay into the container with a bash shell after vibe has ended
#            (by default, when vibe is terminated, the container is terminated too)

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

sudo docker run --rm -it \
  -e HOST_UID="$(id -u)" \
  -e HOST_GID="$(id -g)" \
  -e HOST_USER="$(id -un)" \
  -e HOST_GROUP="$(id -un)" \
  -e GH_TOKEN="$(gh auth token)" \
  --network=host \
  --cap-add=NET_ADMIN \
  --mount "type=bind,src=/var/run/mysqld/mysqld.sock,dst=/var/run/mysqld/mysqld.sock" \
  --mount "type=bind,src=$HOME/git/$GIT_DIR,dst=$HOME/git/$GIT_DIR" \
  --mount "type=bind,src=$HOME/.vibe,dst=/home/$(id -un)/.vibe" \
  --mount "type=bind,src=$HOME/.gitconfig,dst=$HOME/.gitconfig" \
  --mount "type=bind,src=$HOME/.bash_history,dst=$HOME/.bash_history" \
  -w "$HOME/git/$GIT_DIR" \
  docker-vibe "${ARGS[@]}"
