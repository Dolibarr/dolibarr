# How to use it ?

The docker-compose.yml file is a sample of a config file to use to build and run
Dolibarr in the current workspace with Docker. This docker image is intended for
**development usage**. For production usage you should consider other
contributor reference like https://hub.docker.com/r/dolibarr/dolibarr.

Before build/run, define the variable HOST_USER_ID as following:

        export HOST_USER_ID=$(id -u)

Go in repository build/docker :

        cd dev/build/docker

And then, depending on whether you want to run with a MariaDB database or
PostgreSQL database, you can run:

        docker compose -f docker-compose.yml -f mariadb.yml up

or

        docker compose -f docker-compose.yml -f postgres.yml up

This will run 4 containers Docker : Dolibarr, MariaDB, PhpMyAdmin and MailDev.
In the case of PostgreSQL, only Dolibarr, MailDev and the PostgreSQL database
will be running.

The URL to go to the Dolibarr is :

        http://0.0.0.0

The URL to go to PhpMyAdmin is (login/password is root/root) :

        http://0.0.0.0:8080

In Dolibarr configuration Email let PHP mail function, To see all mail send by
Dolibarr go to maildev

        http://0.0.0.0:8081

Setup the database connection during the installation process, please use
mariadb or postgres (name of the database container) as database host.

## Setup your custom modules

You can setup your own modules from your development folder by using volume
mounts and docker compose override. For instance for your module "yourmodule"
located in `/path/to/your/module_folder`, you can edit `yourmodule.yml` and
write:

        ---
        services:
            web:
                volumes:
                    - /path/to/your/module_folder:/var/www/html/custom/yourmodule/

This will add your module at runtime inside the dolibarr custom plugins and it
will automatically be synced with your development environment.

Then, you can start by extending one of the commands above, for instance for
mariadb:

        docker compose \
            -f docker-compose.yml \
            -f postgres.yml \
            -f yourmodule.yml \
            up
