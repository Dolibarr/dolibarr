#!/bin/bash
# delete.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Manage script variables
if [ $1 ] ; then
    tobedeleted=$1
else
    echo "You must provide a file or directory to delete!"
    exit 2 
fi

if [ "$tobedeleted" = "/" ] ; then
    echo "Be Serious!"
    exit 2
fi

# Move file
move () {
    mkdir -p $workdir/$project/deleted
    mv $tobedeleted $workdir/$project/deleted
}

move
