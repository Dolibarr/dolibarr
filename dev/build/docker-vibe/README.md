# How to run an IA Agent into a container.

## Prepare api key
With the payment mode of Mistral, the credential is stored into the OS system and not into the .vibe/.env directory so is not available into another docker container.
So you must first get it from your OS system keystore with:
````
secret-tool search --all xdg:schema org.freedesktop.Secret.Generic
````
And then copy the value in entry "secret" for section "MISTRAL_API_KEY" into the .vibe/.env file
````
MISTRAL_API_KEY='<your_api_key>'
````
So now when running the container, the .vibe/.env file has your paid key that will be used to set the environment variable MISTRAL_API_KEY.

## You can add an alias into your /etc/bash.bashrc or ~/.bashrc the line
````
alias vibes='dev/build/docker-vibe/vibes.sh'
````
 

## Run vibe into a container
````
alias vibes='dev/build/docker-vibe/vibes.sh'
or
dev/build/docker-vibe/vibes.sh
````


This script will build the docker image, and then run it with the current directory mounted into the container and launch vibe.

### Build (or rebuild) image of the container. 
sudo docker build -dev/build/docker-vibe -t dockervibe --no-cache

### Run image
GIT_DIR=`basename $PWD` sudo docker run --rm -it -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" -e HOST_USER="$(id -un)" --network=host -v "$HOME/git/test:/test" -v "$HOME/.vibe:/home/$(id -un)/.vibe" --mount type=bind,src="$HOME/git/$GIT_DIR",dst=/$GIT_DIR -w /$GIT_DIR dockervibe bash
