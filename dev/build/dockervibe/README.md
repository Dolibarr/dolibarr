# How to run an IA Agent into a container.

## Build (or rebuild) image 
sudo docker build -dev/build/dockervibe -t dockervibe --no-cache

## Run image   
sudo docker run --rm -it --network=host --mount type=bind,src="$HOME/git/dolibarr_dev",dst=/dolibarr_dev -w /dolibarr_dev dockervibe bash
