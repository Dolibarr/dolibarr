# How to use or run Dolibarr with Docker ?


## For a fast run of a demo of the local version, you can build the docker image from the source repository by running

	git clone https://github.com/Dolibarr/dolibarr.git dolibarr 
	
	cd dolibarr/docker

	sudo docker-compose build

	sudo -s

	export HOST_USER_ID=$(id -u)
	export HOST_GROUP_ID=$(id -g)
	export MYSQL_ROOT_PWD=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 13; echo)
	
	docker-compose up -d


Warning: There is no persistency of data. This process is for dev purpose only.


## For a more robust or a production usage

If you want to execute an official Docker package, you can find it and read the doc on 

*https://hub.docker.com/r/dolibarr/dolibarr*
