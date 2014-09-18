#!/bin/bash
# checkwardir.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to check if a webapp directory is successfully created or removed"
    echo " after the (un)deploy of a war file"
    echo "It implies that a directory with the name of the war file is created in the same path"
    echo "-p <warname> - Waits until war created dir is present"
    echo "-a <warname> - Wait until war created dir is absent"
    echo "-s <seconds> - Wait some more seconds after the check"
    echo "-c <configentry> - Name of the runtime config variable that contains the warname"
    echo "Examples:"
    echo "checkwardir.sh -p /store/tomcat/myapp/webapps/myapp.war"
    echo "checkwardir.sh -a /store/tomcat/myoldapp/webapps/myoldapp.war"
}

seconds=2

while [ $# -gt 0 ]; do
  case "$1" in
    -s)
      seconds=$2
      shift 2
      ;;
    -p)
      check="present"
      warname=$2
      shift 2
      ;;
    -a)
      check="absent"
      warname=$2
      shift 2
      ;;
    -c)
      warname="$(eval "echo \${$(echo ${2})}")"      
      shift 2
      ;;
    *)
      showhelp
      exit
      ;;
  esac
done

checkdir () {
    wardir=${warname%\.*}
    while true
       do
        if [ $check == absent ] ; then
            [ ! -d $wardir ] && break
        else
            [ -f $wardir/WEB-INF/web.xml ] && break
        fi
        sleep $seconds
    done
}

checkdir
