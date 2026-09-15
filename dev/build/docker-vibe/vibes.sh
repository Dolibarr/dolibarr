#!/bin/bash

# Build image
#sudo docker build dev/build/docker-vibe -t docker-vibe --no-cache
sudo docker build dev/build/docker-vibe -t docker-vibe

# Run image
export GIT_DIR=`basename $PWD`
sudo docker run --rm -it -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" -e HOST_USER="$(id -un)" -e HOST_GROUP="$(id -un)" --network=host -v "$HOME/git/test:/test" -v "$HOME/.vibe:/home/$(id -un)/.vibe" --mount type=bind,src="$HOME/git/$GIT_DIR",dst=/$GIT_DIR -w /$GIT_DIR docker-vibe bash
