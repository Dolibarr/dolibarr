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

Vibe is launched with the option `--yolo` by default. If you want to run vibe without this option, you can run
````
vibes --no-yolo
````

When you exit vibe (for example with CTRL+C), the container is stopped too, so you return immediately to your host.
If you want to stay into the container with a bash shell after vibe has ended, you can run
````
vibes --no-exit
````

### Build (or rebuild) image of the container.
The build context is created on the fly to include the `.pre-commit-config.yaml` of the repository (see the pre-commit section), so build with the same command as `vibes.sh`:
````
tar -cf - -C dev/build/docker-vibe . -C "$PWD" .pre-commit-config.yaml | sudo docker build -t docker-vibe --no-cache -
````

### Run image
GIT_DIR=`basename $PWD` sudo docker run --rm -it -e HOST_UID="$(id -u)" -e HOST_GID="$(id -g)" -e HOST_USER="$(id -un)" --network=host -v "$HOME/git/test:/test" -v "$HOME/.vibe:/home/$(id -un)/.vibe" --mount type=bind,src="$HOME/git/$GIT_DIR",dst=/$GIT_DIR -w /$GIT_DIR dockervibe bash

## pre-commit
The tool `pre-commit` is installed into the image (see https://pre-commit.com).
Hooks for the Dolibarr repository are defined into the file `.pre-commit-config.yaml` at the repository root.
To activate the hooks on your commits, run into the container, once:
````
pre-commit install
````

The hooks environments are preloaded into the image when it is built (they are the downloads you see at the first use of pre-commit elsewhere).
They are stored into the directory `/var/cache/pre-commit`, declared with `PRE_COMMIT_HOME` in the Dockerfile, and are already present at the first run into the container.
If the `.pre-commit-config.yaml` of the repository changes after the image was built (new revision of a hook), only the changed hooks are downloaded at the first run.
