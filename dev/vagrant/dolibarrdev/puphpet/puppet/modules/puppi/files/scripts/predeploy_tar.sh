#!/bin/bash
# predeploy_tar.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script unpacks (tar) file from the download dir (storedir) to the predeploydir"
    echo "It has the following options:"
    echo "\$1 (Required) - Name of the variable that identifies the tar to predeploy"
    echo 
    echo "Examples:"
    echo "predeploy_tar.sh tarfile"
}

# Check Arguments
if [ $1 ] ; then
    deployfilevar=$1
    deployfile="$(eval "echo \${$(echo ${deployfilevar})}")"
else
    showhelp
    exit 2 
fi

# Untar  file
untar () {
    cd $predeploydir
#    file $deployfile | grep gzip 2>&1>/dev/null
#    if [ $? == "0"] ; then
        tar -zxf $deployfile
#    else
#        tar -xvf $deployfile
#    fi
}

untar
