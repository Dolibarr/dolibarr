#!/bin/bash
# service.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to manage one or more services"
    echo "It requires AT LEAST 2 arguments:"
    echo "First argument (\$1 - required) is the script command (stop|start|restart|reload)"
    echo "Second argument and following (\$2 - required) is the space separated list of sevices to manage"
    echo
    echo "Examples:"
    echo "service.sh stop monit puppet"
}

# Check arguments
if [ $1 ] ; then
    servicecommand=$1
else
    showhelp
    exit 2
fi


if [ $# -ge 2 ] ; then
    shift
    services=$@
else
    showhelp
    exit 2
fi

# Manage service
service () {
    for serv in $services ; do
        /etc/init.d/$serv $servicecommand
    done
}

service
