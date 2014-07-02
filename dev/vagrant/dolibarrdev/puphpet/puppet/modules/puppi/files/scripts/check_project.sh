#!/bin/bash
# check_project.sh - Made for Puppi
# This script runs the checks defined in $projectsdir/$project/check and then in $checksdir
# It can be used to automatically run tests during the deploy procedure

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Main functions
handle_check () {
        RETVAL=$?
        if [ "$RETVAL" = "1" ] ; then
            EXITWARN="1"
        fi
        if [ "$RETVAL" = "2" ] ; then
            EXITCRIT="1"
        fi
}

check () {
    for command in $(ls -v1 $projectsdir/$project/check) ; do
        "$projectsdir/$project/check/$command"
        handle_check
    done

    for command in $(ls -v1 $checksdir) ; do
        "$checksdir/$command" 
        handle_check
    done
}

# For nicer output when launched via cli
echo -n "\n"

# Run checks
check

# Manage general return code
if [ "$EXITCRIT" = "1" ] ; then
    exit 1
fi

if [ "$EXITWARN" = "1" ] ; then
    exit 1
fi
