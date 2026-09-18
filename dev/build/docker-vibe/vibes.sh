#!/bin/bash

# Build image
#sudo docker build dev/build/docker-vibe -t docker-vibe --no-cache
sudo docker build dev/build/docker-vibe -t docker-vibe

# Run image
GIT_DIR=$(basename "$PWD")
export GIT_DIR
sudo docker run --rm -it -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" -e HOST_USER="$(id -un)" -e HOST_GROUP="$(id -un)" --network=host -v "$HOME/git/test:/test" -v "$HOME/.vibe:/home/$(id -un)/.vibe" --mount "type=bind,src=$HOME/git/$GIT_DIR,dst=/$GIT_DIR" -w "/$GIT_DIR" docker-vibe bash
