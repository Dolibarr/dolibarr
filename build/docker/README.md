# How to use it ?

The docker-compose.yml file is used to build and run Dolibarr in the current workspace.

Before build/run, define the variable HOST_USER_ID as following:

        export HOST_USER_ID=$(id -u)

Go in repository build/docker :

        cd build/docker

And then, you can run :

        docker-compose up

This will run 3 container Docker : Dolibarr, MariaDB and PhpMyAdmin.

The URL to go to the Dolibarr is :

        http://0.0.0.0

The URL to go to PhpMyAdmin is (login/password is root/root) :

        http://0.0.0.0:8080
