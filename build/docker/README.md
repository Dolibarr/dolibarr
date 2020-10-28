# How to use it ?

The docker-compose.yml file is used to build and run Dolibarr in the current workspace.
This docker image intended for developpement usage.
For production usage you should consider other contributor reference like https://hub.docker.com/r/tuxgasy/dolibarr 

Before build/run, define the variable HOST_USER_ID as following:

        export HOST_USER_ID=$(id -u)

Go in repository build/docker :

        cd build/docker

And then, you can run :

        docker-compose up

This will run 4 containers Docker : Dolibarr, MariaDB, PhpMyAdmin and MailDev.

The URL to go to the Dolibarr is :

        http://0.0.0.0

The URL to go to PhpMyAdmin is (login/password is root/root) :

        http://0.0.0.0:8080
        
In Dolibarr configuration Email let PHP mail function, To see all mail send by Dolibarr go to maildev

        http://0.0.0.0:8081

Setup the database connection during the installation process, please use mariadb (name of the database container) as database host.
Setup documents folder, during the installation process, to /var/documents
