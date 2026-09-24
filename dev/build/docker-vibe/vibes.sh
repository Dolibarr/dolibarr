#!/bin/bash
# Launch a vibe container with the current directory mounted as a volume
# Syntax:  vibes.sh [--no-cache] [--yolo]

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

# Build image
sudo docker build dev/build/docker-vibe -t docker-vibe "${BUILD_ARGS[@]}"

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
  --mount "type=bind,src=/var/run/mysqld/mysqld.sock,dst=/var/run/mysqld/mysqld.sock" \
  --mount "type=bind,src=$HOME/git/$GIT_DIR,dst=$HOME/git/$GIT_DIR" \
  --mount "type=bind,src=$HOME/.vibe,dst=/home/$(id -un)/.vibe" \
  --mount "type=bind,src=$HOME/.gitconfig,dst=$HOME/.gitconfig" \
  --mount "type=bind,src=$HOME/.bash_history,dst=$HOME/.bash_history" \
  -w "$HOME/git/$GIT_DIR" \
  docker-vibe "${ARGS[@]}"
