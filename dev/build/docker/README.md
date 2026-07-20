# How to build and run your local version of Dolibarr, quickly with Docker ?


## For a fast run of a demo of the local version, you can build a docker image pointing to your source repository

This process using the Dockerfile and docker-compose.yml file is a config file to use to build and run Dolibarr in the 
current workspace with Docker. This docker image is intended for **development usage** (See next chapter for a production usage).

Warning: There is no persistency of database. This process is for dev purpose only.


Build the containers:

	git clone https://github.com/Dolibarr/dolibarr.git dolibarr 
	
	cd dolibarr/dev/build/docker

	sudo docker-compose build [--no-cache]
	or
	sudo docker compose build [--no-cache]

	
Launch the containers:

	sudo -s
	export HOST_USER_ID=$(id -u)
	export HOST_GROUP_ID=$(id -g)
	export MYSQL_ROOT_PWD=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 13; echo)
	
	docker-compose up -d
	or 
	docker compose up -d

Check that server process is on:
	
	docker compose ps

Check also logs of starting container:

	docker logs dolibarr-web-dev

The URL to go to the installed Dolibarr is :

    http://0.0.0.0:8080

To delete processes:

	docker compose kill stop


## For a more robust or a production usage

If you want to execute an official Docker package, you can find it and read the doc on 

*https://hub.docker.com/r/dolibarr/dolibarr*
