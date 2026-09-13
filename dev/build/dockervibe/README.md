# How to run an IA Agent into a container.

## Build (or rebuild) image 
sudo docker build -dev/build/dockervibe -t dockervibe --no-cache

## Run image
GIT_DIR=`basename $PWD` sudo docker run --rm -it -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" -e HOST_USER="$(id -un)" --network=host -v "$HOME/git/test:/test" -v "$HOME/.vibe:/home/$(id -un)/.vibe" --mount type=bind,src="$HOME/git/$GIT_DIR",dst=/$GIT_DIR -w /$GIT_DIR dockervibe bash
