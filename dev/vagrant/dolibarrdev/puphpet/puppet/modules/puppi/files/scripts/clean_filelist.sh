#!/bin/bash
# clean_filelist.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script is used to cleanup a list of files to download from unwanted data"
    echo "It has 1 optional argument:"
    echo "The prefix, present in the list, to cut out when defining files to deploy"
    echo "The list file is defined as $downloadedfile , these variables are gathered from the Puppi runtime"
    echo "  config file."
    echo 
    echo "Example:"
    echo "clean_filelist.sh http://svn.example42.com/myproject"
}


if [ $1 ] ; then
    prefix=$1
else
    prefix=""
fi

deployfilelist=$downloadedfile

# Clean list
cleanlist () {

    sed -i "s/^$prefix//g" $deployfilelist

}

cleanlist
