# How to use run Dolibarr with docker ?


## For a fast run of a demo of the local version, you can build the docker image from this current repository by running


sudo docker-compose build

sudo -s

export HOST_USER_ID=$(id -u)
export HOST_GROUP_ID=$(id -g)
export MYSQL_ROOT_PWD=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 13; echo)

docker-compose up -d


Warning: There is no persistency of data. This process is for dev purpose only.


## For a more robust or a production usage

If you want to execute an official Docker package, you can find it and read the doc on ihttps://hub.docker.com/r/dolibarr/dolibarr 
