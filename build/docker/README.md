# How to use it ?

This directory is experimental. Scope of its used is not clear and not documented.
If you are looking for a process to run Dolibarr as an official Docker image, you can find it on https://hub.docker.com/r/dolibarr/dolibarr
 

# For experimental dev - TO REMOVE.

But if you want to execute the version of Dolibarr that is into this current directory as a docker process, you can do it with this commands.

export HOST_USER_ID=$(id -u)
export HOST_GROUP_ID=$(id -g)
export MYSQL_ROOT_PWD=$(tr -dc A-Za-z0-9 </dev/urandom | head -c 13; echo)

docker-compose up -d

Warning: There is no persistency of data. If you need so, you should use instead the official Docker image that you can find on https://hub.docker.com/r/dolibarr/dolibarr 
