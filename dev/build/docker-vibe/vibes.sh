#!/bin/bash

# Build image
#sudo docker build dev/build/docker-vibe -t docker-vibe --no-cache
sudo docker build dev/build/docker-vibe -t docker-vibe

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
  --mount "type=bind,src=$HOME/git/$GIT_DIR,dst=/$GIT_DIR" \
  --mount "type=bind,src=$HOME/.vibe,dst=/home/$(id -un)/.vibe" \
  --mount "type=bind,src=$HOME/.ssh/github-token,dst=/home/$(id -un)/.ssh/github-token,readonly" \
  -w "/$GIT_DIR" \
  docker-vibe bash
